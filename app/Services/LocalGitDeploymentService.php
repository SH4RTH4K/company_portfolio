<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class LocalGitDeploymentService
{
    public function __construct(
        private DeploymentStore $store,
        private DeploymentDatabaseService $database,
    ) {}

    /**
     * @param  array<string, mixed>  $settings
     */
    public function initializeRepository(array $settings): bool
    {
        $this->validateConfiguration($settings);
        $root = $this->applicationPath();
        $gitDirectory = $root.DIRECTORY_SEPARATOR.'.git';

        if (is_dir($gitDirectory)) {
            return false;
        }

        if (file_exists($gitDirectory) || is_link($gitDirectory)) {
            throw new RuntimeException('The application .git path is not a safe directory. Review it before connecting this installation.');
        }

        $initialized = false;

        try {
            $this->gitOrFail($settings, ['init'], 'Git could not initialize this cPanel installation. Confirm Git is available to PHP and the application directory is writable.');
            $initialized = true;
            $this->gitOrFail($settings, ['remote', 'add', $settings['remote_name'], $this->remoteUrl($settings)], 'Git could not configure the repository remote.');
            $this->gitOrFail($settings, ['fetch', '--prune', $settings['remote_name'], $settings['branch']], 'Git could not fetch the configured branch. Check repository access, authentication and hosting network access.');

            $remoteBranch = $settings['remote_name'].'/'.$settings['branch'];
            $this->gitOrFail($settings, ['branch', '--force', $settings['branch'], $remoteBranch], 'Git could not create the configured local branch.');
            $this->gitOrFail($settings, ['symbolic-ref', 'HEAD', 'refs/heads/'.$settings['branch']], 'Git could not select the configured local branch.');
            $this->gitOrFail($settings, ['reset', '--mixed', $remoteBranch], 'Git could not compare the uploaded application files with the repository.');

            return true;
        } catch (Throwable $exception) {
            if ($initialized && is_dir($gitDirectory) && ! is_link($gitDirectory)) {
                File::deleteDirectory($gitDirectory);
            }

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>|null  $settings
     * @return array<string, mixed>
     */
    public function status(?array $settings = null): array
    {
        $settings ??= $this->store->settings();
        if (! ($settings['enabled'] ?? false)) {
            return $this->baseStatus();
        }

        return $this->locked(fn (): array => $this->statusWithoutLock($settings));
    }

    /** @return array<string, mixed> */
    private function baseStatus(): array
    {
        return [
            'configured' => false,
            'status' => 'Disabled',
            'message' => 'Git repository integration is disabled.',
            'local' => null,
            'remote' => null,
            'commits' => [],
            'local_commits' => [],
            'changed_files' => [],
            'local_changes' => [],
            'untracked_files' => [],
            'local_changes_match_target' => false,
        ];
    }

    /** @param array<string, mixed> $settings */
    private function statusWithoutLock(array $settings): array
    {
        $base = $this->baseStatus();
        $this->validateConfiguration($settings);
        $this->initializeRepository($settings);
        $this->assertRepository();

        $currentBranch = trim($this->git($settings, ['branch', '--show-current'])['output']);

        if ($currentBranch !== $settings['branch']) {
            return array_replace($base, [
                'configured' => true,
                'status' => 'Branch Not Found',
                'message' => 'This checkout is not on the branch configured in repository settings.',
                'local' => trim($this->git($settings, ['rev-parse', 'HEAD'])['output']),
            ]);
        }

        $local = trim($this->git($settings, ['rev-parse', 'HEAD'])['output']);
        $this->configureRemote($settings);

        $remoteBranch = $settings['remote_name'].'/'.$settings['branch'];
        $branch = $this->git($settings, ['rev-parse', '--verify', $remoteBranch], false);

        if ($branch['code'] !== 0) {
            return array_replace($base, [
                'configured' => true,
                'status' => 'Branch Not Found',
                'message' => 'The configured branch was not found on this remote. Test the repository connection and branch name.',
                'local' => $local,
            ]);
        }

        $target = trim($branch['output']);
        $ahead = trim($this->git($settings, ['rev-list', '--count', $remoteBranch.'..HEAD'])['output']) !== '0';
        $behindCount = (int) trim($this->git($settings, ['rev-list', '--count', 'HEAD..'.$remoteBranch])['output']);
        $commits = $this->commitList($settings, 'HEAD..'.$remoteBranch);
        $localCommits = $this->commitList($settings, $remoteBranch.'..HEAD');
        $worktreeLines = array_values(array_filter(preg_split('/\R/', trim($this->git($settings, ['status', '--porcelain'])['output'])) ?: []));
        $trackedChanges = array_values(array_filter($worktreeLines, static fn (string $line): bool => ! str_starts_with($line, '??') && ! str_starts_with($line, '!!')));
        $localChangesMatchTarget = $trackedChanges !== []
            && $this->git($settings, ['diff', '--quiet', $remoteBranch, '--'], false)['code'] === 0;
        $status = $trackedChanges !== [] && ! $localChangesMatchTarget
            ? 'Local Changes Detected'
            : ($ahead ? 'Diverged Branch' : ($behindCount > 0 ? 'Update Available' : 'Up to date'));
        $message = match ($status) {
            'Diverged Branch' => 'The server has commits that are not on GitHub. Preserve and review them before synchronizing the branch.',
            'Update Available' => $behindCount.' update(s) are available for review.',
            'Local Changes Detected' => 'Deployment is blocked because tracked server-side changes exist. Review them before continuing.',
            default => 'The server is synchronized with the configured GitHub branch.',
        };
        $changedOutput = trim($this->git($settings, ['diff', '--name-status', 'HEAD', $remoteBranch])['output']);

        return [
            'configured' => true,
            'status' => $status,
            'message' => $message,
            'local' => $local,
            'remote' => $target,
            'commits' => $commits,
            'local_commits' => $localCommits,
            'changed_files' => array_values(array_filter(preg_split('/\R/', $changedOutput) ?: [])),
            'local_changes' => $trackedChanges,
            'untracked_files' => array_values(array_filter($worktreeLines, static fn (string $line): bool => str_starts_with($line, '??'))),
            'local_changes_match_target' => $localChangesMatchTarget,
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public function fetch(array $settings): array
    {
        if (! ($settings['enabled'] ?? false)) {
            throw new RuntimeException('Turn on repository integration in saved settings before checking GitHub.');
        }

        return $this->locked(fn (): array => $this->fetchWithoutLock($settings));
    }

    /** @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private function fetchWithoutLock(array $settings): array
    {
        if (! ($settings['enabled'] ?? false)) {
            throw new RuntimeException('Turn on repository integration in saved settings before checking GitHub.');
        }

        $this->validateConfiguration($settings);
        $this->initializeRepository($settings);
        $this->assertRepository();
        $this->configureRemote($settings);
        $this->gitOrFail($settings, ['fetch', '--prune', $settings['remote_name'], $settings['branch']], 'Git could not fetch the configured repository. Check repository access, authentication, branch settings and hosting network access.');

        $status = $this->statusWithoutLock($settings);
        $status['checked_at'] = now()->toIso8601String();

        return $status;
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public function resetToRemote(array $settings, bool $discardLocalChanges = false): array
    {
        return $this->locked(function () use ($settings, $discardLocalChanges): array {
            $status = $this->fetchWithoutLock($settings);
            $hasServerCommits = $status['local_commits'] !== [];
            $cleanDivergence = $status['status'] === 'Diverged Branch';
            $discardableDivergence = $discardLocalChanges && $hasServerCommits && $status['status'] === 'Local Changes Detected';

            if (! $hasServerCommits || (! $cleanDivergence && ! $discardableDivergence)) {
                throw new RuntimeException('No recoverable server-only commits were found. Review tracked server changes before trying again.');
            }

            $recoveryBranch = 'deployment-recovery/'.now()->format('YmdHis').'-'.Str::lower(Str::random(6));
            $this->gitOrFail($settings, ['branch', $recoveryBranch, 'HEAD'], 'The server-only branch history could not be preserved.');
            $this->gitOrFail($settings, ['reset', '--hard', $settings['remote_name'].'/'.$settings['branch']], 'The branch could not be synchronized. Its previous history remains on '.$recoveryBranch.'.');
            $result = $this->statusWithoutLock($settings);
            $result['recovery_branch'] = $recoveryBranch;

            return $result;
        });
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public function deploy(array $settings, string $reviewedCommit, int $actorId, string $actor, bool $discardLocalChanges = false): array
    {
        return $this->locked(function () use ($settings, $reviewedCommit, $actorId, $actor, $discardLocalChanges): array {
            $status = $this->fetchWithoutLock($settings);

            if ($discardLocalChanges && $status['status'] === 'Local Changes Detected') {
                $this->gitOrFail($settings, ['reset', '--hard', 'HEAD'], 'The tracked local changes could not be discarded.');
                $status = $this->fetchWithoutLock($settings);
            }

            if (! preg_match('/\A[a-f0-9]{40}\z/', $reviewedCommit) || ! hash_equals($reviewedCommit, (string) $status['remote'])) {
                throw new RuntimeException('The GitHub branch changed after review. Check for updates again before deploying.');
            }

            if ($status['status'] !== 'Update Available') {
                throw new RuntimeException($status['message']);
            }

            $preflight = $this->database->check();
            $this->store->writeData('database-report', $preflight);

            if (! $preflight['ok'] && (! $settings['run_migrations'] || ! $preflight['repairable'])) {
                throw new RuntimeException('Database readiness failed. Review the database checks and required cPanel privileges before deploying.');
            }

            $id = (string) Str::uuid();
            $backup = $this->database->backup($id);

            if (! empty($status['local_changes_match_target'])) {
                $this->gitOrFail($settings, ['reset', '--hard', $status['remote']], 'The tracked application files could not be aligned with the reviewed remote update.');
            }

            $release = [
                'id' => $id,
                'transport' => 'git',
                'repository' => $settings['repository'],
                'branch' => $settings['branch'],
                'commit' => $status['remote'],
                'previous_commit' => $status['local'],
                'message' => $status['commits'][0]['subject'] ?? 'Server Git deployment',
                'delivery' => 'git',
                'status' => 'deploying',
                'actor_id' => $actorId,
                'actor' => $actor,
                'created_at' => now()->toIso8601String(),
                'approved_at' => now()->toIso8601String(),
                'backup' => $backup,
                'migration_status' => 'not_run',
                'health_status' => 'not_run',
                'database_report' => [],
                'logs' => ['Repository access, server file review and database backup completed.'],
            ];

            $this->store->saveRelease($release);

            try {
                $this->store->writeData('maintenance', ['deployment' => $id, 'started_at' => now()->toIso8601String()]);
                $this->gitOrFail($settings, ['merge', '--ff-only', $settings['remote_name'].'/'.$settings['branch']], 'Fast-forward deployment was not possible. Review the server branch history.');
                $release['deployed_commit'] = trim($this->git($settings, ['rev-parse', 'HEAD'])['output']);

                if ($settings['run_migrations']) {
                    $this->runArtisan('migrate', '--force', '--no-interaction');
                    $release['migration_status'] = 'successful';
                } else {
                    $release['migration_status'] = 'skipped';
                }

                if ($settings['clear_cache']) {
                    $this->clearAndRebuildCaches();
                    $release['logs'][] = 'Application caches cleared and supported caches rebuilt.';
                }

                if ($settings['health_check']) {
                    $report = $this->database->check();

                    if (! $report['ok'] && $settings['run_migrations'] && $report['repairable']) {
                        $report = $this->database->repair([], $settings['clear_cache']);
                    }

                    $release['database_report'] = $report;
                    $release['health_status'] = $report['ok'] ? 'passed' : 'attention';
                    $this->store->writeData('database-report', $report);

                    if (! $report['ok']) {
                        throw new RuntimeException('The source update is installed, but database health checks need attention. Review the database report; no destructive database reset was attempted.');
                    }
                } else {
                    $release['health_status'] = 'skipped';
                }

                $release['status'] = 'completed';
                $release['completed_at'] = now()->toIso8601String();
                $release['logs'][] = 'Fast-forward deployment and selected post-deployment checks completed.';
            } catch (Throwable $exception) {
                if ($settings['health_check']) {
                    try {
                        $report = $this->database->check();
                        $release['database_report'] = $report;
                        $release['health_status'] = $report['ok'] ? 'passed' : 'attention';
                        $this->store->writeData('database-report', $report);
                    } catch (Throwable) {
                        $release['health_status'] = 'attention';
                        $release['logs'][] = 'The follow-up database health check could not run.';
                    }
                }

                $release['status'] = 'failed';
                $release['failed_stage'] = $release['migration_status'] === 'successful' ? 'database_or_finish' : 'source_or_migration';
                $release['error_summary'] = $this->safeError($exception->getMessage());
                $release['completed_at'] = now()->toIso8601String();
                $release['logs'][] = 'Deployment stopped for review. Source rollback does not reverse database migrations.';
                $this->store->saveRelease($release);

                throw $exception;
            } finally {
                Storage::disk('local')->delete('git-deployment/maintenance.json');
            }

            $this->store->saveRelease($release);

            return $release;
        });
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public function rollback(array $settings, string $id, int $actorId, string $actor): array
    {
        return $this->locked(function () use ($settings, $id, $actorId, $actor): array {
            $deployment = $this->store->release($id);

            if (($deployment['transport'] ?? null) !== 'git'
                || ($deployment['status'] ?? null) !== 'completed'
                || ! is_string($deployment['previous_commit'] ?? null)) {
                throw new RuntimeException('Only a completed server Git deployment with a previous commit can be rolled back.');
            }

            $this->validateConfiguration($settings);
            $this->assertRepository();
            $this->configureRemote($settings);

            if (trim($this->git($settings, ['branch', '--show-current'])['output']) !== $settings['branch']) {
                throw new RuntimeException('The checkout is no longer on the configured branch. Review repository settings before rolling back.');
            }

            $current = trim($this->git($settings, ['rev-parse', 'HEAD'])['output']);
            $expected = $deployment['deployed_commit'] ?? $deployment['commit'];

            if ($this->git($settings, ['merge-base', '--is-ancestor', $expected, $current], false)['code'] !== 0) {
                throw new RuntimeException('This deployment is not in the current source history. Check the current Git status before rolling back.');
            }

            $tracked = trim($this->git($settings, ['status', '--porcelain'])['output']);
            $trackedLines = array_filter(preg_split('/\R/', $tracked) ?: [], static fn (string $line): bool => $line !== '' && ! str_starts_with($line, '??'));

            if ($trackedLines !== []) {
                throw new RuntimeException('Tracked server changes must be reviewed before source rollback.');
            }

            $rollbackId = (string) Str::uuid();
            $backup = $this->database->backup($rollbackId);
            $release = [
                'id' => $rollbackId,
                'transport' => 'git',
                'repository' => $settings['repository'],
                'branch' => $settings['branch'],
                'commit' => $deployment['previous_commit'],
                'previous_commit' => $current,
                'message' => 'Rollback of deployment '.substr($id, 0, 8),
                'delivery' => 'git',
                'status' => 'deploying',
                'actor_id' => $actorId,
                'actor' => $actor,
                'created_at' => now()->toIso8601String(),
                'approved_at' => now()->toIso8601String(),
                'rollback_of' => $id,
                'backup' => $backup,
                'migration_status' => 'not_reversed',
                'health_status' => 'not_run',
                'database_report' => [],
                'logs' => ['A fresh database backup was saved before source rollback. Database migrations will not be reversed.'],
            ];
            $this->store->saveRelease($release);

            try {
                $this->store->writeData('maintenance', ['deployment' => $rollbackId, 'started_at' => now()->toIso8601String()]);
                $this->gitOrFail($settings, ['reset', '--hard', $deployment['previous_commit']], 'Source rollback failed. The branch could not be returned to its previous commit.');
                $release['deployed_commit'] = trim($this->git($settings, ['rev-parse', 'HEAD'])['output']);

                if ($settings['clear_cache']) {
                    $this->clearAndRebuildCaches();
                    $release['logs'][] = 'Application caches cleared and supported caches rebuilt.';
                }

                $report = $settings['health_check'] ? $this->database->check() : [];
                $release['database_report'] = $report;
                $release['health_status'] = $report === [] ? 'skipped' : ($report['ok'] ? 'passed' : 'attention');

                if ($report !== []) {
                    $this->store->writeData('database-report', $report);
                }

                $release['status'] = 'completed';
                $release['completed_at'] = now()->toIso8601String();
                $release['logs'][] = 'Source rollback completed. Database migrations were not reversed.';
            } catch (Throwable $exception) {
                if ($settings['health_check']) {
                    try {
                        $report = $this->database->check();
                        $release['database_report'] = $report;
                        $release['health_status'] = $report['ok'] ? 'passed' : 'attention';
                        $this->store->writeData('database-report', $report);
                    } catch (Throwable) {
                        $release['health_status'] = 'attention';
                        $release['logs'][] = 'The follow-up database health check could not run.';
                    }
                }

                $release['status'] = 'failed';
                $release['error_summary'] = $this->safeError($exception->getMessage());
                $release['completed_at'] = now()->toIso8601String();
                $release['logs'][] = 'Rollback needs review. The source may already be at the previous commit; database migrations were not reversed.';
                $this->store->saveRelease($release);

                throw $exception;
            } finally {
                Storage::disk('local')->delete('git-deployment/maintenance.json');
            }

            $this->store->saveRelease($release);

            return $release;
        });
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function validateConfiguration(array $settings): void
    {
        $branch = (string) ($settings['branch'] ?? '');

        if (! is_string($settings['repository'] ?? null)
            || preg_match('/\A[A-Za-z0-9](?:[A-Za-z0-9-]{0,38})\/[A-Za-z0-9_.-]{1,100}\z/', $settings['repository']) !== 1
            || preg_match('/\A[A-Za-z0-9][A-Za-z0-9._\/-]*\z/', $branch) !== 1
            || str_contains($branch, '..')
            || str_contains($branch, '@{')
            || str_contains($branch, '//')
            || str_contains($branch, '/.')
            || str_ends_with($branch, '/')
            || str_ends_with($branch, '.')
            || preg_match('/\.lock(?:\/|\z)/', $branch) === 1
            || preg_match('/\A[A-Za-z0-9](?:[A-Za-z0-9._-]{0,48}[A-Za-z0-9])?\z/', (string) ($settings['remote_name'] ?? '')) !== 1) {
            throw new RuntimeException('Repository URL, branch or remote name is invalid.');
        }

        if (! in_array($settings['repository_type'] ?? null, ['public', 'private'], true)
            || ! in_array($settings['authentication'] ?? null, ['none', 'ssh', 'pat'], true)) {
            throw new RuntimeException('Choose a valid repository type and authentication method.');
        }

        if ($settings['repository_type'] === 'public' && $settings['authentication'] !== 'none') {
            throw new RuntimeException('Public repositories use None authentication.');
        }

        if ($settings['repository_type'] === 'private'
            && (! in_array($settings['authentication'], ['ssh', 'pat'], true) || trim((string) ($settings['credential'] ?? '')) === '')) {
            throw new RuntimeException('Private repositories require an SSH deploy key or personal access token.');
        }

        if ($settings['authentication'] === 'pat' && preg_match('/\A[A-Za-z0-9_]+\z/', (string) $settings['credential']) !== 1) {
            throw new RuntimeException('The personal access token contains unsupported characters.');
        }

        if ($settings['authentication'] === 'ssh'
            && preg_match('/\A-----BEGIN [A-Z0-9 ]+PRIVATE KEY-----[\s\S]+-----END [A-Z0-9 ]+PRIVATE KEY-----\s*\z/', trim((string) $settings['credential'])) !== 1) {
            throw new RuntimeException('Enter a valid OpenSSH private deploy key.');
        }
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function assertRepositoryMatches(array $settings, string $actual): void
    {
        if ($this->normalizedRepository($actual) !== strtolower($settings['repository'])) {
            throw new RuntimeException('The configured repository does not match the remote already registered in this application directory. Review its .git configuration before changing repositories.');
        }
    }

    /** @param array<string, mixed> $settings */
    private function configureRemote(array $settings): void
    {
        $selected = $this->git($settings, ['remote', 'get-url', $settings['remote_name']], false);

        if ($selected['code'] !== 0) {
            $remoteOutput = trim($this->git($settings, ['remote'])['output']);
            $remotes = array_values(array_filter(preg_split('/\R/', $remoteOutput) ?: []));
            $matchingRemotes = [];

            foreach ($remotes as $remoteName) {
                $candidate = $this->git($settings, ['remote', 'get-url', $remoteName], false);

                if ($candidate['code'] === 0 && $this->normalizedRepository(trim($candidate['output'])) === strtolower($settings['repository'])) {
                    $matchingRemotes[] = $remoteName;
                }
            }

            if (count($matchingRemotes) !== 1) {
                throw new RuntimeException('The configured remote name was not found, and this checkout does not have exactly one matching GitHub remote to rename. Review .git remotes before changing this setting.');
            }

            $this->gitOrFail($settings, ['remote', 'rename', $matchingRemotes[0], $settings['remote_name']], 'Git could not rename the matching repository remote.');
            $selected = $this->git($settings, ['remote', 'get-url', $settings['remote_name']]);
        }

        $actual = trim($selected['output']);
        $this->assertRepositoryMatches($settings, $actual);

        if ($actual !== $this->remoteUrl($settings)) {
            $this->gitOrFail($settings, ['remote', 'set-url', $settings['remote_name'], $this->remoteUrl($settings)], 'Git could not update the remote authentication URL.');
        }
    }

    private function normalizedRepository(string $url): string
    {
        $pattern = '#\A(?:https://github\.com/|git@github\.com:)([A-Za-z0-9][A-Za-z0-9-]{0,38})/([A-Za-z0-9_.-]{1,100})(?:\.git)?/?\z#i';

        if (preg_match($pattern, trim($url), $matches) !== 1) {
            return '';
        }

        $repositoryName = preg_replace('/\.git$/i', '', $matches[2]) ?? $matches[2];

        return strtolower($matches[1].'/'.$repositoryName);
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array{code: int, output: string, error: string}
     */
    private function git(array $settings, array $arguments, bool $throw = true): array
    {
        $environment = ['GIT_TERMINAL_PROMPT' => '0', 'GCM_INTERACTIVE' => 'Never'];
        $temporaryFiles = [];
        $credential = (string) ($settings['credential'] ?? '');

        if ($credential !== '' && $settings['authentication'] === 'ssh') {
            $keyPath = $this->temporaryPath('key');
            $this->writePrivateFile($keyPath, rtrim($credential)."\n", 0600);
            $temporaryFiles[] = $keyPath;
            $environment['GIT_SSH_COMMAND'] = 'ssh -i '.escapeshellarg($keyPath).' -o IdentitiesOnly=yes -o StrictHostKeyChecking=accept-new';
        } elseif ($credential !== '' && $settings['authentication'] === 'pat') {
            $tokenPath = $this->temporaryPath('token');
            $this->writePrivateFile($tokenPath, $credential, 0600);
            $temporaryFiles[] = $tokenPath;
            $askpassPath = $this->temporaryPath(PHP_OS_FAMILY === 'Windows' ? 'askpass.bat' : 'askpass');
            $askpass = PHP_OS_FAMILY === 'Windows'
                ? "@echo off\r\necho %~1 | findstr /I /C:\"Username\" >nul\r\nif %errorlevel%==0 (echo x-access-token) else (type \"%GIT_DEPLOY_TOKEN_FILE%\")\r\n"
                : "#!/bin/sh\ncase \"$1\" in *[Uu]sername*) printf '%s\\n' 'x-access-token' ;; *) cat \"$GIT_DEPLOY_TOKEN_FILE\" ;; esac\n";
            $this->writePrivateFile($askpassPath, $askpass, 0700);
            $temporaryFiles[] = $askpassPath;
            $environment['GIT_ASKPASS'] = $askpassPath;
            $environment['GIT_DEPLOY_TOKEN_FILE'] = $tokenPath;
        }

        try {
            $process = new Process(array_merge([$this->gitBinary()], $arguments), $this->applicationPath(), $environment, null, 180);
            $process->run();
            $result = [
                'code' => $process->getExitCode() ?? 1,
                'output' => trim($process->getOutput()),
                'error' => trim($process->getErrorOutput()),
            ];

            if ($throw && $result['code'] !== 0) {
                throw new RuntimeException($this->safeError($result['error'] ?: $result['output'] ?: 'Git operation failed.'));
            }

            return $result;
        } finally {
            foreach ($temporaryFiles as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $settings
     * @param  list<string>  $arguments
     */
    private function gitOrFail(array $settings, array $arguments, string $message): void
    {
        $result = $this->git($settings, $arguments, false);

        if ($result['code'] !== 0) {
            $details = $this->safeError($result['error'] ?: $result['output']);

            throw new RuntimeException($details === '' ? $message : $message.' '.$details);
        }
    }

    private function gitBinary(): string
    {
        $configured = trim((string) config('deployment.git_binary', ''));
        $candidates = $configured !== ''
            ? [$configured]
            : (array) config('deployment.git_binary_candidates', ['git', '/usr/bin/git', '/usr/local/bin/git']);

        foreach (array_unique(array_filter(array_map('trim', $candidates))) as $candidate) {
            try {
                $process = new Process([$candidate, '--version'], $this->applicationPath(), null, null, 10);
                $process->run();

                if ($process->getExitCode() === 0) {
                    return $candidate;
                }
            } catch (Throwable) {
                continue;
            }
        }

        throw new RuntimeException('Git is not available to the PHP process. Select ZIP delivery for this cPanel host, or set APPLICATION_UPDATE_GIT_BINARY to the Git executable path in .env. SSH access to the cPanel account is not required for server Git.');
    }

    /** @return list<array{hash: string, short: string, author: string, date: string, subject: string}> */
    private function commitList(array $settings, string $range): array
    {
        $result = $this->git($settings, ['log', '--format=%H%x1f%h%x1f%an%x1f%aI%x1f%s', $range, '-n', '50']);
        $rows = [];

        foreach (array_filter(preg_split('/\R/', trim($result['output'])) ?: []) as $line) {
            $parts = explode("\x1f", $line, 5);

            if (count($parts) === 5) {
                $rows[] = ['hash' => $parts[0], 'short' => $parts[1], 'author' => $parts[2], 'date' => $parts[3], 'subject' => $parts[4]];
            }
        }

        return $rows;
    }

    private function assertRepository(): void
    {
        $gitDirectory = $this->applicationPath().DIRECTORY_SEPARATOR.'.git';

        if (! is_dir($gitDirectory) || is_link($gitDirectory)) {
            throw new RuntimeException('This installation is not managed by a safe local Git checkout. Check that .git is a directory created for this application.');
        }
    }

    private function remoteUrl(array $settings): string
    {
        $repository = $settings['repository'];

        return $settings['authentication'] === 'ssh'
            ? 'git@github.com:'.$repository.'.git'
            : 'https://github.com/'.$repository.'.git';
    }

    private function applicationPath(): string
    {
        $path = (string) config('deployment.application_path', base_path());

        if ($path === '' || ! is_dir($path) || is_link($path)) {
            throw new RuntimeException('The configured Laravel application directory is unavailable or unsafe.');
        }

        return rtrim($path, DIRECTORY_SEPARATOR);
    }

    private function temporaryPath(string $suffix): string
    {
        $directory = storage_path('app/.git-deploy');
        File::ensureDirectoryExists($directory, 0700);

        if (PHP_OS_FAMILY !== 'Windows' && ! chmod($directory, 0700)) {
            throw new RuntimeException('The temporary Git credential directory is not private. Check cPanel storage permissions.');
        }

        $path = $directory.DIRECTORY_SEPARATOR.Str::uuid().'.'.$suffix;

        return $path;
    }

    private function writePrivateFile(string $path, string $contents, int $mode): void
    {
        $previousMask = umask(0077);

        try {
            if (File::put($path, $contents) === false || (PHP_OS_FAMILY !== 'Windows' && ! chmod($path, $mode))) {
                throw new RuntimeException('A temporary Git credential could not be stored securely.');
            }
        } catch (Throwable $exception) {
            if (is_file($path)) {
                @unlink($path);
            }

            throw $exception;
        } finally {
            umask($previousMask);
        }
    }

    private function runArtisan(string ...$arguments): void
    {
        if (Artisan::call($arguments[0], array_fill_keys(array_slice($arguments, 1), true)) !== 0) {
            throw new RuntimeException('A selected post-deployment Artisan task failed. Review storage and bootstrap/cache permissions in cPanel.');
        }
    }

    private function clearAndRebuildCaches(): void
    {
        foreach (['optimize:clear', 'package:discover', 'config:cache', 'view:cache'] as $command) {
            if (Artisan::call($command, ['--no-interaction' => true]) !== 0) {
                throw new RuntimeException('Application caches could not be cleared and rebuilt. Check bootstrap/cache and storage permissions in cPanel.');
            }
        }
    }

    private function safeError(string $error): string
    {
        $error = preg_replace('/(?:token|password|secret|private.?key|authorization)[^\s]*/i', '[REDACTED]', substr($error, 0, 2000)) ?? '';

        return trim($error);
    }

    private function locked(\Closure $callback): mixed
    {
        $lock = Cache::store('file')->lock('git-repository-deployment', 3600);

        if (! $lock->get()) {
            throw new RuntimeException('Another deployment operation is already running.');
        }

        try {
            return $callback();
        } finally {
            $lock->release();
        }
    }
}
