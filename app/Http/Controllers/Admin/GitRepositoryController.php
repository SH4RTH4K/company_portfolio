<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DeploymentStore;
use App\Services\GitDeploymentService;
use App\Services\LocalGitDeploymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class GitRepositoryController extends Controller
{
    public function __construct(
        private DeploymentStore $store,
        private GitDeploymentService $deployments,
        private LocalGitDeploymentService $localGit,
    ) {}

    public function index(): View
    {
        $settings = $this->store->settings();
        $tokenConfigured = $settings['token'] !== '';
        $credentialConfigured = $settings['credential'] !== '';
        unset($settings['token'], $settings['credential']);
        $manifestError = null;
        $gitStatus = null;
        $gitError = null;

        if ($settings['enabled'] && $settings['delivery'] === 'git') {
            try {
                $checkedStatus = $this->store->readData('git-status');
                $gitStatus = $this->localGit->status(array_replace($settings, ['credential' => $this->store->settings()['credential']]));

                if (($checkedStatus['remote'] ?? null) === ($gitStatus['remote'] ?? null)
                    && is_string($checkedStatus['checked_at'] ?? null)) {
                    $gitStatus['checked_at'] = $checkedStatus['checked_at'];
                }

                $this->store->writeData('git-status', $gitStatus);
            } catch (Throwable $exception) {
                $gitError = $exception->getMessage();
                $gitStatus = null;
            }
        }

        if ($settings['delivery'] === 'git') {
            $installed = [];
        } else {
            try {
                $installed = $this->deployments->installedManifest();
            } catch (Throwable) {
                $installed = [];
                $manifestError = 'The installed deployment manifest is invalid or incomplete.';
            }
        }

        $history = array_slice($this->store->history(), 0, 30);

        foreach ($history as &$release) {
            if (($release['transport'] ?? null) === 'git') {
                $release['can_rollback'] = $release['status'] === 'completed'
                    && $release['repository'] === $settings['repository']
                    && $release['branch'] === $settings['branch']
                    && is_string($release['previous_commit'] ?? null);
            } else {
                $release['can_rollback'] = $release['status'] === 'completed'
                    && $release['repository'] === $settings['repository']
                    && $release['branch'] === $settings['branch']
                    && ($installed['commit'] ?? null) !== $release['commit']
                    && is_string($release['archive_path'] ?? null)
                    && is_string($release['manifest_path'] ?? null)
                    && Storage::disk('local')->exists($release['archive_path'])
                    && Storage::disk('local')->exists($release['manifest_path']);
            }
        }
        unset($release);

        return view('admin.git_repository.index', [
            'settings' => $settings,
            'tokenConfigured' => $tokenConfigured,
            'credentialConfigured' => $credentialConfigured,
            'preview' => $this->store->readData('preview'),
            'gitStatus' => $gitStatus,
            'gitError' => $gitError,
            'history' => $history,
            'databaseReport' => $this->store->readData('database-report'),
            'serverReview' => $this->store->readData('server-review'),
            'installed' => $installed,
            'manifestError' => $manifestError,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $input = $request->all();

        if (array_key_exists('repository', $input) && is_string($input['repository'])) {
            $input['repository'] = $this->normalizeRepositoryInput($input['repository']);
        }

        $validator = Validator::make($input, [
            'repository' => ['required', 'string', 'max:200', 'regex:/\A[A-Za-z0-9](?:[A-Za-z0-9-]{0,38})\/[A-Za-z0-9_.-]{1,100}\z/', 'not_regex:/\/(?:\.|\.\.)\z/'],
            'branch' => ['required', 'string', 'max:200', 'regex:/\A[A-Za-z0-9][A-Za-z0-9._\/-]*\z/', 'not_regex:/\.\.|@\{|\/\/|\/\.|\.lock(?:\/|$)|\/$|\.$/'],
            'remote_name' => ['required', 'string', 'max:50', 'regex:/\A[A-Za-z0-9](?:[A-Za-z0-9._-]{0,48}[A-Za-z0-9])?\z/'],
            'repository_type' => ['required', 'in:public,private'],
            'authentication' => ['required', 'in:none,ssh,pat'],
            'credential' => ['nullable', 'string', 'max:10000'],
            'remove_credential' => ['nullable', 'boolean'],
            'run_migrations' => ['nullable', 'boolean'],
            'clear_cache' => ['nullable', 'boolean'],
            'health_check' => ['nullable', 'boolean'],
            'token' => ['nullable', 'string', 'max:500', 'regex:/\A[A-Za-z0-9_]+\z/'],
            'remove_token' => ['nullable', 'boolean'],
            'enabled' => ['required', 'boolean'],
            'php_version' => ['required', 'in:8.3,8.4'],
            'delivery' => ['required', 'in:git,zip,ftps'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput($request->except(['token', 'credential']));
        }

        $validated = $validator->validated();
        $saved = $this->store->settings();
        $credential = filled($validated['credential'] ?? null) ? $validated['credential'] : $saved['credential'];

        if ($validated['repository_type'] === 'private' && ! in_array($validated['authentication'], ['ssh', 'pat'], true)) {
            return back()->withErrors(['authentication' => 'Private repositories require an SSH deploy key or personal access token.'])->withInput($request->except(['token', 'credential']));
        }

        if ($validated['repository_type'] === 'private'
            && blank($validated['credential'] ?? null)
            && ($saved['credential'] === '' || $request->boolean('remove_credential'))) {
            return back()->withErrors(['credential' => 'A credential is required for a private repository.'])->withInput($request->except(['token', 'credential']));
        }

        if ($validated['repository_type'] === 'public' && $validated['authentication'] !== 'none') {
            return back()->withErrors(['authentication' => 'Public repositories use None authentication.'])->withInput($request->except(['token', 'credential']));
        }

        if ($validated['repository_type'] === 'private'
            && $validated['authentication'] === 'pat'
            && preg_match('/\A[A-Za-z0-9_]+\z/', $credential) !== 1) {
            return back()->withErrors(['credential' => 'The personal access token contains unsupported characters.'])->withInput($request->except(['token', 'credential']));
        }

        if ($validated['repository_type'] === 'private'
            && $validated['authentication'] === 'ssh'
            && preg_match('/\A-----BEGIN [A-Z0-9 ]+PRIVATE KEY-----[\s\S]+-----END [A-Z0-9 ]+PRIVATE KEY-----\s*\z/', trim($credential)) !== 1) {
            return back()->withErrors(['credential' => 'Enter a valid OpenSSH private deploy key.'])->withInput($request->except(['token', 'credential']));
        }

        return $this->perform(function () use ($validated, $request, $saved): void {
            $lock = Cache::store('file')->lock('git-repository-deployment', 3600);

            if (! $lock->get()) {
                throw new RuntimeException('A deployment operation is running. Wait before changing its settings.');
            }

            try {
                if ($this->store->hasActiveDeployment()) {
                    throw new RuntimeException('Finish or recover the active deployment before changing repository settings.');
                }

                $settings = array_replace($saved, array_intersect_key($validated, $saved));
                $settings['enabled'] = $request->boolean('enabled');
                $settings['run_migrations'] = $request->boolean('run_migrations');
                $settings['clear_cache'] = $request->boolean('clear_cache');
                $settings['health_check'] = $request->boolean('health_check');
                $settings['token'] = $request->filled('token') ? $validated['token'] : $saved['token'];
                $settings['credential'] = $request->filled('credential') ? $validated['credential'] : $saved['credential'];

                if ($request->boolean('remove_token')) {
                    $settings['token'] = '';
                }

                if ($request->boolean('remove_credential')) {
                    $settings['credential'] = '';
                }

                if ($settings['repository_type'] === 'public') {
                    $settings['authentication'] = 'none';
                    $settings['credential'] = '';
                }

                $this->store->saveSettings($settings);

                if ($settings['enabled'] && $settings['delivery'] === 'git') {
                    $this->localGit->initializeRepository($settings);
                }
            } finally {
                $lock->release();
            }
        }, 'Repository settings saved.');
    }

    private function normalizeRepositoryInput(string $repository): string
    {
        $repository = trim($repository);

        if (preg_match('#\Ahttps://github\.com/([A-Za-z0-9][A-Za-z0-9-]{0,38})/([A-Za-z0-9_.-]{1,100})/?\z#i', $repository, $matches) === 1
            || preg_match('#\Agit@github\.com:([A-Za-z0-9][A-Za-z0-9-]{0,38})/([A-Za-z0-9_.-]{1,100})\.git\z#i', $repository, $matches) === 1) {
            $name = $matches[2];

            if (str_ends_with(strtolower($name), '.git')) {
                $name = substr($name, 0, -4);
            }

            return $matches[1].'/'.$name;
        }

        return $repository;
    }

    public function inspect(): RedirectResponse
    {
        if ($this->store->settings()['delivery'] === 'git') {
            return $this->perform(function (): void {
                $settings = $this->store->settings();
                $status = $this->localGit->fetch($settings);
                $this->store->writeData('git-status', $status);
            }, 'GitHub connection checked. Review incoming commits and server changes below.');
        }

        return $this->perform(fn () => $this->deployments->inspect(), 'Repository access checked. Review the selected commit below.');
    }

    public function prepare(Request $request): RedirectResponse
    {
        abort_if($this->store->settings()['delivery'] === 'git', 422, 'Server Git deployments use the reviewed update action below.');
        $validated = $request->validate(['commit' => ['required', 'regex:/\A[a-f0-9]{40}\z/']]);

        return $this->perform(fn () => $this->deployments->prepare($validated['commit'], (int) $request->user()->id, $request->user()->name), 'Release build requested. Refresh its status when GitHub finishes.');
    }

    public function deployWithGit(Request $request): RedirectResponse
    {
        $this->assertServerGitDelivery();
        $validated = $request->validate([
            'commit' => ['required', 'regex:/\A[a-f0-9]{40}\z/'],
            'confirmation' => ['accepted'],
        ]);

        return $this->perform(function () use ($request, $validated): void {
            $release = $this->localGit->deploy($this->store->settings(), $validated['commit'], (int) $request->user()->id, $request->user()->name);
            $this->store->writeData('git-status', $this->localGit->status($this->store->settings()));

            if ($release['health_status'] === 'attention') {
                throw new RuntimeException('The source was deployed, but database health checks need attention. Review the saved checks and backup before continuing.');
            }
        }, 'Reviewed Git update deployed. Migrations, cache work and the database health check completed.');
    }

    public function discardLocalChangesAndDeploy(Request $request): RedirectResponse
    {
        $this->assertServerGitDelivery();
        $validated = $request->validate([
            'commit' => ['required', 'regex:/\A[a-f0-9]{40}\z/'],
            'confirmation' => ['required', 'in:DISCARD LOCAL CHANGES'],
        ]);

        return $this->perform(function () use ($request, $validated): void {
            $this->localGit->deploy($this->store->settings(), $validated['commit'], (int) $request->user()->id, $request->user()->name, true);
            $this->store->writeData('git-status', $this->localGit->status($this->store->settings()));
        }, 'Tracked server changes were discarded and the reviewed Git update was deployed.');
    }

    public function pullLatest(Request $request): RedirectResponse
    {
        $this->assertServerGitDelivery();
        $validated = $request->validate([
            'commit' => ['required', 'regex:/\A[a-f0-9]{40}\z/'],
            'confirmation' => ['accepted'],
        ]);

        return $this->perform(function () use ($request, $validated): void {
            $this->localGit->deploy($this->store->settings(), $validated['commit'], (int) $request->user()->id, $request->user()->name);
            $this->store->writeData('git-status', $this->localGit->status($this->store->settings()));
        }, 'The reviewed update was deployed with a database backup and selected checks.');
    }

    public function resetToRemote(Request $request): RedirectResponse
    {
        $this->assertServerGitDelivery();
        $data = $request->validate(['confirmation' => ['required', 'in:RESET TO GITHUB,RESET TO GITHUB AND DISCARD LOCAL CHANGES']]);
        $discardLocalChanges = $data['confirmation'] === 'RESET TO GITHUB AND DISCARD LOCAL CHANGES';

        return $this->perform(function () use ($discardLocalChanges): void {
            $result = $this->localGit->resetToRemote($this->store->settings(), $discardLocalChanges);
            $this->store->writeData('git-status', $result);
        }, $discardLocalChanges
            ? 'The branch now matches GitHub. Tracked changes were discarded; untracked uploads were kept.'
            : 'The branch now matches GitHub. Server-only commits were preserved on a recovery branch.');
    }

    public function refresh(Request $request, string $deployment): RedirectResponse
    {
        $validated = $request->validate(['run_id' => ['nullable', 'integer', 'min:1']]);

        return $this->perform(fn () => $this->deployments->refresh($deployment, isset($validated['run_id']) ? (int) $validated['run_id'] : null), 'Workflow status refreshed.');
    }

    public function approve(Request $request, string $deployment): RedirectResponse
    {
        $validated = $request->validate([
            'commit' => ['required', 'regex:/\A[a-f0-9]{40}\z/'],
            'confirmation' => ['accepted'],
        ]);

        return $this->perform(fn () => $this->deployments->approve($deployment, $validated['commit'], $request->user()->name), 'Deployment confirmed and database backup saved. Complete the upload, then finalize deployment.');
    }

    public function download(string $deployment): BinaryFileResponse|RedirectResponse
    {
        try {
            $path = $this->deployments->archive($deployment);

            return response()->download($path, 'deployment-'.$this->store->release($deployment)['commit'].'.zip');
        } catch (HttpExceptionInterface $exception) {
            throw $exception;
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }

    public function backup(string $deployment): StreamedResponse
    {
        $release = $this->store->release($deployment);
        $path = $release['backup'] ?? null;
        abort_unless(is_string($path) && str_starts_with($path, 'git-deployment/backups/') && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, 'before-deployment-'.$deployment.'.sql.gz');
    }

    public function finalize(Request $request, string $deployment): RedirectResponse
    {
        $request->validate(['confirmation' => ['accepted']]);

        return $this->perform(function () use ($deployment): void {
            $release = $this->deployments->finalize($deployment);

            if ($release['status'] !== 'completed') {
                throw new RuntimeException('Database verification needs attention. Review the checks below; public development mode remains enabled.');
            }
        }, 'Deployment files and database verified successfully. The previous development mode setting has been restored.');
    }

    public function checkDatabase(): RedirectResponse
    {
        return $this->perform(fn () => $this->deployments->checkDatabase(), 'Database checks completed. Review the results below.');
    }

    public function reviewServerState(): RedirectResponse
    {
        if ($this->store->settings()['delivery'] === 'git') {
            return $this->perform(function (): void {
                $settings = $this->store->settings();
                $gitStatus = $this->localGit->status($settings);
                $databaseReport = $this->deployments->checkDatabase();
                $checkedAt = $this->store->readData('git-status')['checked_at'] ?? null;
                $gitStatus['checked_at'] = is_string($checkedAt) ? $checkedAt : null;
                $localChanges = array_map(static fn (string $line): array => ['path' => substr($line, 3), 'status' => trim(substr($line, 0, 2))], $gitStatus['local_changes']);
                $review = [
                    'status' => $localChanges === [] && $databaseReport['ok'] ? 'ready' : 'attention',
                    'ok' => $localChanges === [] && $databaseReport['ok'],
                    'manifest_found' => true,
                    'commit' => $gitStatus['local'],
                    'changed_files' => $localChanges,
                    'untracked_files' => $gitStatus['untracked_files'],
                    'database' => $databaseReport,
                    'checked_at' => now()->toIso8601String(),
                ];
                $this->store->writeData('git-status', $gitStatus);
                $this->store->writeData('server-review', $review);
            }, 'Server files and database reviewed. Check any tracked changes before deploying.');
        }

        return $this->perform(fn () => $this->deployments->reviewServerState(), 'Server state reviewed. Check file integrity and database results below.');
    }

    public function recover(Request $request, string $deployment): RedirectResponse
    {
        $request->validate(['confirmation' => ['required', 'in:RECOVERED']]);

        return $this->perform(fn () => $this->deployments->recover($deployment), 'Manual recovery verified. The previous development mode setting has been restored.');
    }

    public function rollback(Request $request, string $deployment): RedirectResponse
    {
        $request->validate(['confirmation' => ['accepted']]);

        if (($this->store->release($deployment)['transport'] ?? null) === 'git') {
            $this->assertServerGitDelivery();

            return $this->perform(function () use ($request, $deployment): void {
                $this->localGit->rollback($this->store->settings(), $deployment, (int) $request->user()->id, $request->user()->name);
                $this->store->writeData('git-status', $this->localGit->status($this->store->settings()));
            }, 'Source rollback completed with a fresh database backup. Database migrations were not reversed.');
        }

        return $this->perform(
            fn () => $this->deployments->rollback($deployment, (int) $request->user()->id, $request->user()->name),
            'Rollback package prepared with a fresh database backup. Upload the selected release through cPanel, then finalize and check the database.'
        );
    }

    private function assertServerGitDelivery(): void
    {
        $settings = $this->store->settings();

        abort_unless($settings['enabled'] && $settings['delivery'] === 'git', 422, 'Enable repository integration and select Server Git delivery first.');
    }

    private function perform(\Closure $operation, string $success): RedirectResponse
    {
        try {
            $operation();

            return redirect()->route('admin.git-repository.index')->with('success', $success);
        } catch (HttpExceptionInterface $exception) {
            throw $exception;
        } catch (RuntimeException $exception) {
            return redirect()->route('admin.git-repository.index')->with('error', $exception->getMessage());
        } catch (Throwable) {
            return redirect()->route('admin.git-repository.index')->with('error', 'The deployment operation could not finish. Check hosting permissions and storage, then retry. Credentials have not been included in this message.');
        }
    }
}
