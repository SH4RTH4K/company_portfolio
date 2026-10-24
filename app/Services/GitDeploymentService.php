<?php

namespace App\Services;

use App\Models\SiteSetting;
use Closure;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

class GitDeploymentService
{
    public function __construct(
        private DeploymentStore $store,
        private GitRepositoryClient $github,
        private DeploymentDatabaseService $database,
    ) {}

    /** @return array<string, mixed> */
    public function inspect(): array
    {
        $settings = $this->store->settings();

        if (! $settings['enabled']) {
            throw new RuntimeException('Turn on repository integration in the saved settings before checking GitHub.');
        }

        $preview = $this->github->inspect($settings);
        $preview['settings_hash'] = $this->settingsHash($settings);
        $preview['inspected_at'] = time();
        $this->store->writeData('preview', $preview);

        return $preview;
    }

    /** @return array<string, mixed> */
    public function prepare(string $commit, int $actorId, string $actor): array
    {
        return $this->locked(fn (): array => $this->prepareRelease($commit, $actorId, $actor));
    }

    /** @return array<string, mixed> */
    private function prepareRelease(string $commit, int $actorId, string $actor): array
    {
        $settings = $this->store->settings();
        $preview = $this->store->readData('preview');

        if (! $settings['enabled']) {
            throw new RuntimeException('Turn on repository integration in the saved settings before preparing a release.');
        }

        if (($preview['commit'] ?? null) !== $commit
            || ($preview['settings_hash'] ?? null) !== $this->settingsHash($settings)
            || ($preview['inspected_at'] ?? 0) < time() - 1800) {
            throw new RuntimeException('Check the repository again, then prepare the commit shown in its preview.');
        }

        $release = [
            'id' => (string) Str::uuid(), 'repository' => $settings['repository'], 'branch' => $settings['branch'],
            'commit' => $commit, 'php_version' => $settings['php_version'], 'delivery' => $settings['delivery'],
            'message' => $preview['message'] ?? '',
            'status' => 'building', 'actor_id' => $actorId, 'actor' => $actor,
            'created_at' => now()->toIso8601String(), 'approved_at' => null, 'backup' => null,
            'run_id' => null, 'artifact_id' => null, 'logs' => ['Release build requested. Production files have not been changed.'],
        ];
        $this->store->saveRelease($release);

        try {
            $run = $this->github->dispatch(array_replace($settings, ['delivery' => 'zip']), $commit);
            $release['run_id'] = $run['run_id'];
            $release['run_url'] = $run['run_url'];
            $this->store->saveRelease($release);
        } catch (Throwable $exception) {
            $release['status'] = 'failed';
            $release['logs'][] = 'The build could not be started. Check GitHub authentication and workflow setup.';
            $this->store->saveRelease($release);
            throw $exception;
        }

        return $release;
    }

    /** @return array<string, mixed> */
    public function refresh(string $id, ?int $runId = null): array
    {
        return $this->locked(function () use ($id, $runId): array {
            $release = $this->store->release($id);

            if (! in_array($release['status'], ['building', 'deploying'], true)) {
                throw new RuntimeException('This release is not waiting for a workflow result.');
            }

            $runId = $release['run_id'] ?: $runId;

            if (! $runId) {
                throw new RuntimeException('GitHub accepted the request without returning a run ID. Enter its run ID from the repository Actions page.');
            }

            $run = $this->github->run($this->settingsFor($release), $runId);

            $expectedDelivery = $release['approved_at'] ? $release['delivery'] : 'zip';
            $expectedRunName = 'Deployment '.$release['commit'].' ('.$expectedDelivery.')';

            if ($run['commit'] !== $release['commit'] && ($run['name'] ?? '') !== $expectedRunName) {
                throw new RuntimeException('The GitHub Actions run does not match the confirmed commit. Refresh the repository and choose the reviewed build.');
            }

            $release['run_id'] = $runId;
            $release['run_url'] = $run['url'];

            if ($run['status'] === 'completed') {
                if ($run['conclusion'] !== 'success') {
                    $release['status'] = $release['approved_at'] ? 'verification_failed' : 'failed';
                    $release['logs'][] = 'GitHub workflow did not succeed. Review its logs; no database repair has been run.';
                } else {
                    $artifact = $release['approved_at'] ? null : collect($run['artifacts'])->firstWhere('name', 'deployment-release-'.$release['commit']);

                    if (! $release['approved_at'] && ! $artifact) {
                        throw new RuntimeException('The workflow did not publish the artifact for the selected commit. Check the release workflow.');
                    }

                    if ($artifact) {
                        $release['artifact_id'] = $artifact['id'];
                        $release['build_run_id'] = $runId;
                    }
                    $release['status'] = $release['approved_at'] ? 'awaiting_verification' : 'ready';
                    $release['logs'][] = $release['approved_at']
                        ? 'FTPS workflow completed. Verify uploaded files and finalize the database.'
                        : 'Tests and release packaging succeeded. Review and confirm deployment next.';
                }
            }

            $this->store->saveRelease($release);

            return $release;
        });
    }

    /** @return array<string, mixed> */
    public function approve(string $id, string $commit, string $actor): array
    {
        return $this->locked(function () use ($id, $commit, $actor): array {
            $release = $this->store->release($id);

            if ($release['status'] !== 'ready' || ! hash_equals($release['commit'], $commit)) {
                throw new RuntimeException('Only a ready release with the exact reviewed commit can be deployed.');
            }

            if ($this->store->hasActiveDeployment()) {
                throw new RuntimeException('Finish or recover the active deployment before starting another.');
            }

            $release = $this->cacheArtifact($release);
            $current = $this->installedManifest();
            $this->assertManagedFilesClean($current);
            $check = $this->database->check($current['required_schema'] ?? []);

            if (! $check['ok'] && ! $check['repairable']) {
                $this->store->writeData('database-report', $check);
                throw new RuntimeException('Database readiness failed. Review the database checks before deploying.');
            }

            $release['previous_development_mode'] = SiteSetting::where('key', 'development_mode_enabled')->value('value_en') ?? '0';
            if ($current !== []) {
                $release['previous_manifest_path'] = 'git-deployment/releases/'.$id.'/previous-manifest.json';
                Storage::disk('local')->put($release['previous_manifest_path'], json_encode($current, JSON_THROW_ON_ERROR));
            }
            $this->store->saveRelease($release);
            $this->store->writeData('maintenance', ['deployment' => $id, 'started_at' => now()->toIso8601String()]);
            $this->setDevelopmentMode('1');

            try {
                $release['backup'] = $this->database->backup($id.'-'.Str::lower(Str::random(8)));
                foreach (['config:clear', 'route:clear', 'event:clear', 'clear-compiled'] as $command) {
                    if (Artisan::call($command, ['--no-interaction' => true]) !== 0) {
                        throw new RuntimeException('Application caches could not be prepared for upload. Check bootstrap/cache permissions before retrying.');
                    }
                }
            } catch (Throwable $exception) {
                $this->setDevelopmentMode($release['previous_development_mode']);
                Storage::disk('local')->delete('git-deployment/maintenance.json');
                throw $exception;
            }

            $release['approved_at'] = now()->toIso8601String();
            $release['approved_by'] = $actor;
            $release['status'] = 'awaiting_verification';
            $release['logs'][] = 'Deployment confirmed; schema-and-data SQL backup saved. Public development mode enabled during upload.';
            $this->store->saveRelease($release);

            if ($release['delivery'] === 'ftps') {
                try {
                    if (! isset($release['build_run_id']) || ! is_int($release['build_run_id']) || $release['build_run_id'] < 1) {
                        throw new RuntimeException('The original reviewed build run is missing. Refresh the successful ZIP build before starting FTPS delivery.');
                    }

                    $run = $this->github->dispatch(array_replace($this->settingsFor($release), ['artifact_run_id' => (string) $release['build_run_id']]), $commit);
                    $release['run_id'] = $run['run_id'];
                    $release['run_url'] = $run['run_url'];
                    $release['status'] = 'deploying';
                    $release['logs'][] = 'FTPS delivery started for the confirmed commit.';
                } catch (Throwable $exception) {
                    $release['status'] = 'verification_failed';
                    $release['logs'][] = 'FTPS delivery could not be started. Recover using the verified ZIP or retry after checking GitHub.';
                    $this->store->saveRelease($release);
                    throw $exception;
                }
            }

            $this->store->saveRelease($release);

            return $release;
        });
    }

    public function archive(string $id): string
    {
        return $this->locked(function () use ($id): string {
            $release = $this->store->release($id);

            if (! $release['approved_at']) {
                throw new RuntimeException('Confirm deployment and create its database backup before downloading the installable release.');
            }

            $release = $this->cacheArtifact($release);

            return Storage::disk('local')->path($release['archive_path']);
        });
    }

    /** @return array<string, mixed> */
    public function rollback(string $id, int $actorId, string $actor): array
    {
        return $this->locked(function () use ($id, $actorId, $actor): array {
            $source = $this->store->release($id);
            $settings = $this->store->settings();

            if ($source['status'] !== 'completed' || $source['repository'] !== $settings['repository'] || $source['branch'] !== $settings['branch']) {
                throw new RuntimeException('Rollback is available only for a completed release from the configured repository and branch.');
            }

            if ($this->store->hasActiveDeployment()) {
                throw new RuntimeException('Finish or recover the active deployment before starting a rollback.');
            }

            $archivePath = $source['archive_path'] ?? null;
            $manifestPath = $source['manifest_path'] ?? null;
            $archiveMatches = [];
            $manifestMatches = [];

            if (! is_string($archivePath) || preg_match('#\Agit-deployment/releases/([a-f0-9-]{36})/release\.zip\z#', $archivePath, $archiveMatches) !== 1
                || ! is_string($manifestPath) || preg_match('#\Agit-deployment/releases/([a-f0-9-]{36})/manifest\.json\z#', $manifestPath, $manifestMatches) !== 1
                || ! hash_equals($archiveMatches[1], $manifestMatches[1])
                || ! Storage::disk('local')->exists($archivePath) || ! Storage::disk('local')->exists($manifestPath)) {
                throw new RuntimeException('The verified release package is no longer available in private storage. Build a new release before continuing.');
            }

            $targetManifest = $this->releaseManifest($source);

            if ($targetManifest['commit'] !== $source['commit']) {
                throw new RuntimeException('The stored release package does not match its deployment history record.');
            }

            $current = $this->installedManifest();

            $this->assertManagedFilesClean($current);

            if (($current['commit'] ?? null) === $targetManifest['commit']) {
                throw new RuntimeException('This release is already installed.');
            }

            $databaseCheck = $this->database->check($current['required_schema'] ?? []);

            if (! $databaseCheck['ok'] && ! $databaseCheck['repairable']) {
                $this->store->writeData('database-report', $databaseCheck);
                throw new RuntimeException('Database readiness failed. Review the database checks before rolling back application files.');
            }

            $rollbackId = (string) Str::uuid();
            $rollback = array_replace($source, [
                'id' => $rollbackId,
                'commit' => $targetManifest['commit'],
                'delivery' => 'zip',
                'status' => 'awaiting_verification',
                'message' => 'Rollback to '.$targetManifest['commit'],
                'actor_id' => $actorId,
                'actor' => $actor,
                'created_at' => now()->toIso8601String(),
                'approved_at' => now()->toIso8601String(),
                'approved_by' => $actor,
                'backup' => null,
                'database_report' => null,
                'rollback_of' => $id,
                'rollback_from_commit' => $current['commit'] ?? null,
                'previous_development_mode' => SiteSetting::where('key', 'development_mode_enabled')->value('value_en') ?? '0',
                'previous_manifest_path' => null,
                'logs' => ['Reviewed source rollback requested. The database schema and migration history will not be reversed automatically.'],
            ]);
            unset($rollback['run_id'], $rollback['run_url'], $rollback['build_run_id']);

            if ($current !== []) {
                $rollback['previous_manifest_path'] = 'git-deployment/releases/'.$rollbackId.'/previous-manifest.json';
                Storage::disk('local')->put($rollback['previous_manifest_path'], json_encode($current, JSON_THROW_ON_ERROR));
            }

            $this->store->saveRelease($rollback);
            $this->store->writeData('maintenance', ['deployment' => $rollbackId, 'started_at' => now()->toIso8601String()]);
            $this->setDevelopmentMode('1');

            try {
                $rollback['backup'] = $this->database->backup($rollbackId.'-'.Str::lower(Str::random(8)));

                foreach (['config:clear', 'route:clear', 'event:clear', 'clear-compiled'] as $command) {
                    if (Artisan::call($command, ['--no-interaction' => true]) !== 0) {
                        throw new RuntimeException('Application caches could not be prepared for rollback upload. Check bootstrap/cache permissions before retrying.');
                    }
                }

                $rollback['logs'][] = 'Fresh database backup saved before rollback upload. Download the selected package and finalize it after uploading through cPanel; database migrations will not be reversed.';
                $this->store->saveRelease($rollback);

                return $rollback;
            } catch (Throwable $exception) {
                try {
                    $this->setDevelopmentMode($rollback['previous_development_mode']);
                    Storage::disk('local')->delete('git-deployment/maintenance.json');
                    $rollback['status'] = 'failed';
                } catch (Throwable) {
                    $rollback['status'] = 'verification_failed';
                }

                $rollback['logs'][] = 'Rollback preparation failed before file delivery. Review the backup and recovery instructions.';
                $this->store->saveRelease($rollback);

                throw $exception;
            }
        });
    }

    /** @return array<string, mixed> */
    public function finalize(string $id): array
    {
        return $this->locked(function () use ($id): array {
            $release = $this->store->release($id);

            if (! in_array($release['status'], ['awaiting_verification', 'verification_failed'], true) || ! $release['backup']) {
                throw new RuntimeException('Confirm and finish uploading this release before finalizing it.');
            }

            if (! Storage::disk('local')->exists($release['backup'])) {
                throw new RuntimeException('The pre-deployment database backup is missing. Restore it before running migrations.');
            }

            $release = $this->cacheArtifact($release);
            $expected = $this->releaseManifest($release);
            $current = $this->installedManifest();

            if (($current['commit'] ?? null) !== $release['commit'] || ($current['files'] ?? null) !== $expected['files']) {
                throw new RuntimeException('The installed release does not match the confirmed artifact. Complete the ZIP or FTPS upload first.');
            }

            $this->verifyInstalledFiles($expected);

            $report = $this->database->repair($expected['required_schema']);
            $this->store->writeData('database-report', $report);
            $release['database_report'] = $report;
            $release['status'] = $report['ok'] ? 'completed' : 'verification_failed';
            $release['logs'][] = $report['ok']
                ? 'Installed files verified; migrations, database read/write and schema checks passed.'
                : 'Database verification failed. Public development mode remains enabled. Review the report and recover before retrying.';

            if ($report['ok']) {
                $this->setDevelopmentMode($release['previous_development_mode']);
                Storage::disk('local')->delete('git-deployment/maintenance.json');
                $release['completed_at'] = now()->toIso8601String();
            }

            $this->store->saveRelease($release);

            return $release;
        });
    }

    public function recover(string $id): void
    {
        $this->locked(function () use ($id): void {
            $release = $this->store->release($id);

            if (! $release['approved_at'] || ! in_array($release['status'], ['awaiting_verification', 'verification_failed'], true)) {
                throw new RuntimeException('Wait for delivery to stop before completing a manual recovery.');
            }

            $previous = ! empty($release['previous_manifest_path'])
                ? $this->decodeManifest(Storage::disk('local')->get($release['previous_manifest_path'])) : [];
            $current = $this->installedManifest();

            if (($previous['commit'] ?? null) !== ($current['commit'] ?? null)
                || ($previous['files'] ?? null) !== ($current['files'] ?? null)) {
                throw new RuntimeException('Restore the previous release and its manifest in cPanel before completing recovery. If the original installation had no manifest, remove the new deployment-manifest.json after restoring its files.');
            }

            if ($previous !== []) {
                $this->verifyInstalledFiles($previous);
            }

            $report = $this->database->check($previous['required_schema'] ?? []);
            $this->store->writeData('database-report', $report);

            if (! $report['ok']) {
                throw new RuntimeException('The restored database has unresolved issues. Review the database report before leaving maintenance.');
            }

            $this->setDevelopmentMode($release['previous_development_mode']);
            Storage::disk('local')->delete('git-deployment/maintenance.json');
            $release['status'] = 'recovered';
            $release['logs'][] = 'Administrator confirmed manual recovery; the previous release identity and database checks passed.';
            $this->store->saveRelease($release);
        });
    }

    /** @return array<string, mixed> */
    public function checkDatabase(): array
    {
        $manifest = $this->installedManifest();
        $report = $this->database->check($manifest['required_schema'] ?? []);
        $this->store->writeData('database-report', $report);

        return $report;
    }

    /** @return array<string, mixed> */
    public function reviewServerState(): array
    {
        $manifest = $this->installedManifest();
        $changedFiles = $manifest === [] ? [] : $this->fileDifferences($manifest);
        $databaseReport = $this->database->check($manifest['required_schema'] ?? []);
        $review = [
            'status' => $manifest === [] ? 'baseline_missing' : ($changedFiles === [] && $databaseReport['ok'] ? 'ready' : 'attention'),
            'ok' => $changedFiles === [] && $databaseReport['ok'],
            'manifest_found' => $manifest !== [],
            'commit' => $manifest['commit'] ?? null,
            'changed_files' => $changedFiles,
            'database' => $databaseReport,
            'checked_at' => now()->toIso8601String(),
        ];

        $this->store->writeData('database-report', $databaseReport);
        $this->store->writeData('server-review', $review);

        return $review;
    }

    /** @return array<string, mixed> */
    public function installedManifest(): array
    {
        $path = config('deployment.manifest_path', base_path('deployment-manifest.json'));

        if (! is_file($path)) {
            return [];
        }

        $size = filesize($path);

        if ($size === false || $size > 10 * 1024 * 1024) {
            throw new RuntimeException('The installed deployment manifest is too large. Restore a valid release manifest.');
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException('The installed deployment manifest could not be read. Restore a valid release manifest.');
        }

        return $this->decodeManifest($contents);
    }

    /** @param array<string, mixed> $release
     * @return array<string, mixed>
     */
    private function cacheArtifact(array $release): array
    {
        if (($release['cached_artifact_id'] ?? null) === $release['artifact_id']
            && Storage::disk('local')->exists($release['archive_path'] ?? '')
            && Storage::disk('local')->exists($release['manifest_path'] ?? '')) {
            return $release;
        }

        if (! extension_loaded('zip') || ! $release['artifact_id']) {
            throw new RuntimeException('A successful release artifact and the PHP ZIP extension are required.');
        }

        $disk = Storage::disk('local');
        $directory = 'git-deployment/releases/'.$release['id'];
        $disk->makeDirectory($directory);
        $outerPath = $disk->path($directory.'/artifact.zip');
        $disk->delete($directory.'/artifact.zip');
        $this->github->downloadArtifact($this->settingsFor($release), (int) $release['artifact_id'], $outerPath);
        $outer = new ZipArchive;

        if ($outer->open($outerPath) !== true) {
            $disk->delete($directory.'/artifact.zip');
            throw new RuntimeException('The downloaded artifact is not a valid ZIP.');
        }

        try {
            $manifestJson = $outer->getFromName('release-manifest.json', 10 * 1024 * 1024 + 1);
            $checksum = $outer->getFromName('release.sha256', 200);
            $manifest = $this->decodeManifest(is_string($manifestJson) ? $manifestJson : '');

            if ($manifest['commit'] !== $release['commit'] || $manifest['php_version'] !== $release['php_version']
                || ! is_string($checksum) || ! preg_match('/\A([a-f0-9]{64})\s+release\.zip\s*\z/', $checksum, $matches)) {
                throw new RuntimeException('The release identity or checksum is invalid.');
            }

            $stat = $outer->statName('release.zip');

            if (! $stat || $stat['size'] > 512 * 1024 * 1024) {
                throw new RuntimeException('The release ZIP is missing or exceeds the 512 MB deployment limit.');
            }

            $archivePath = $directory.'/release.zip';
            $input = $outer->getStream('release.zip');

            if ($input === false) {
                throw new RuntimeException('The release ZIP could not be read.');
            }

            try {
                if (! $disk->put($archivePath, $input)) {
                    throw new RuntimeException('The release ZIP could not be stored.');
                }
            } finally {
                fclose($input);
            }

            if (! hash_equals($matches[1], hash_file('sha256', $disk->path($archivePath)))) {
                throw new RuntimeException('Release checksum verification failed.');
            }

            $inner = new ZipArchive;

            if ($inner->open($disk->path($archivePath)) !== true) {
                throw new RuntimeException('The release ZIP could not be opened.');
            }

            try {
                if ($inner->getFromName('deployment-manifest.json', 10 * 1024 * 1024 + 1) !== $manifestJson) {
                    throw new RuntimeException('Release manifests do not match.');
                }

                for ($index = 0; $index < $inner->numFiles; $index++) {
                    $path = $inner->getNameIndex($index);

                    if ($path !== 'bootstrap/cache/' && $path !== 'deployment-manifest.json' && ! isset($manifest['files'][$path])) {
                        throw new RuntimeException('The release ZIP contains an unexpected file.');
                    }
                }
            } finally {
                $inner->close();
            }

            $release['archive_path'] = $archivePath;
            $release['manifest_path'] = $directory.'/manifest.json';
            $release['cached_artifact_id'] = $release['artifact_id'];
            $disk->put($release['manifest_path'], $manifestJson);
            $this->store->saveRelease($release);
        } finally {
            $outer->close();
            $disk->delete($directory.'/artifact.zip');
        }

        return $release;
    }

    /** @return array<string, mixed> */
    private function decodeManifest(string $json): array
    {
        try {
            $manifest = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            throw new RuntimeException('The deployment manifest is missing or invalid.');
        }

        if (! is_array($manifest) || ($manifest['version'] ?? null) !== 1
            || ! is_string($manifest['commit'] ?? null) || ! preg_match('/\A[a-f0-9]{40}\z/', $manifest['commit'])
            || ! in_array($manifest['php_version'] ?? null, ['8.3', '8.4'], true)
            || ! is_array($manifest['required_schema'] ?? null) || ! is_array($manifest['files'] ?? null)
            || $manifest['required_schema'] === [] || $manifest['files'] === []) {
            throw new RuntimeException('The deployment manifest does not contain valid release, schema and file requirements.');
        }

        foreach ($manifest['files'] as $path => $hash) {
            if (! is_string($path) || preg_match('/[\\\\\x00-\x1f:]/', $path)
                || in_array('..', explode('/', $path), true)
                || ! preg_match('#\A(?:app/|bootstrap/|config/|database/|public/|resources/|routes/|lang/|vendor/|artisan\z|composer\.(?:json|lock)\z)#', $path)
                || preg_match('#(?:^|/)(?:\.env(?:\.[^/]*)?|\.git|node_modules)(?:/|$)|\A(?:storage/|public/storage/|bootstrap/cache/)#', $path)
                || ! is_string($hash) || ! preg_match('/\A[a-f0-9]{64}\z/', $hash)) {
                throw new RuntimeException('The deployment manifest contains an unsafe file path or checksum.');
            }
        }

        foreach ($manifest['required_schema'] as $table => $requirements) {
            if (! is_string($table) || ! preg_match('/\A[A-Za-z0-9_]+\z/', $table) || ! is_array($requirements)
                || ! is_array($requirements['columns'] ?? null) || ! is_array($requirements['indexes'] ?? [])) {
                throw new RuntimeException('The deployment manifest contains invalid schema requirements.');
            }

            $identifiers = $requirements['columns'];

            foreach ($requirements['indexes'] ?? [] as $indexColumns) {
                if (! is_array($indexColumns)) {
                    throw new RuntimeException('The deployment manifest contains invalid schema identifiers.');
                }

                $identifiers = array_merge($identifiers, $indexColumns);
            }

            foreach ($identifiers as $column) {
                if (! is_string($column) || ! preg_match('/\A[A-Za-z0-9_]+\z/', $column)) {
                    throw new RuntimeException('The deployment manifest contains invalid schema identifiers.');
                }
            }
        }

        return $manifest;
    }

    /** @param array<string, mixed> $manifest */
    private function verifyInstalledFiles(array $manifest): void
    {
        if ($this->fileDifferences($manifest) !== []) {
            throw new RuntimeException('Uploaded file verification failed. Complete or recover the release upload before database changes.');
        }
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return list<array{path: string, status: string}>
     */
    private function fileDifferences(array $manifest): array
    {
        $applicationPath = config('deployment.application_path', base_path());
        $applicationRoot = realpath($applicationPath);

        if ($applicationRoot === false) {
            throw new RuntimeException('The application root could not be resolved. Review hosting paths before deployment.');
        }

        $differences = [];

        foreach ($manifest['files'] as $path => $hash) {
            $absolute = $applicationPath.DIRECTORY_SEPARATOR.$path;
            $resolved = realpath($absolute);

            if (is_link($absolute) || $resolved === false || ! str_starts_with($resolved, $applicationRoot.DIRECTORY_SEPARATOR)) {
                $differences[] = ['path' => $path, 'status' => 'missing or unsafe path'];

                continue;
            }

            if (! is_file($absolute)) {
                $differences[] = ['path' => $path, 'status' => 'missing'];

                continue;
            }

            $actualHash = hash_file('sha256', $absolute);

            if (! is_string($actualHash) || ! hash_equals($hash, $actualHash)) {
                $differences[] = ['path' => $path, 'status' => 'modified'];
            }
        }

        return $differences;
    }

    /** @param array<string, mixed> $manifest */
    private function assertManagedFilesClean(array $manifest): void
    {
        if ($manifest === []) {
            return;
        }

        $changedFiles = $this->fileDifferences($manifest);

        if ($changedFiles === []) {
            return;
        }

        $databaseReport = $this->database->check($manifest['required_schema'] ?? []);
        $this->store->writeData('database-report', $databaseReport);
        $this->store->writeData('server-review', [
            'status' => 'attention',
            'ok' => false,
            'manifest_found' => true,
            'commit' => $manifest['commit'] ?? null,
            'changed_files' => $changedFiles,
            'database' => $databaseReport,
            'checked_at' => now()->toIso8601String(),
        ]);

        throw new RuntimeException('Tracked server files have changed since the last verified release. Review or restore those files before deploying. Untracked uploads and storage files are left in place.');
    }

    /** @param array<string, mixed> $release
     * @return array<string, mixed>
     */
    private function releaseManifest(array $release): array
    {
        return $this->decodeManifest(Storage::disk('local')->get($release['manifest_path']));
    }

    /** @param array<string, mixed> $release
     * @return array<string, string>
     */
    private function settingsFor(array $release): array
    {
        $settings = $this->store->settings();

        if ($settings['repository'] !== $release['repository']) {
            throw new RuntimeException('Configure the repository used by this release before continuing.');
        }

        return array_replace($settings, array_intersect_key($release, array_flip(['repository', 'branch', 'php_version', 'delivery'])));
    }

    /** @param array<string, string> $settings */
    private function settingsHash(array $settings): string
    {
        return hash('sha256', json_encode($settings, JSON_THROW_ON_ERROR));
    }

    private function setDevelopmentMode(string $value): void
    {
        SiteSetting::updateOrCreate(['key' => 'development_mode_enabled'], ['value_en' => $value, 'value_bn' => null]);
        Cache::forget('site_settings');
    }

    private function locked(Closure $operation): mixed
    {
        $lock = Cache::store('file')->lock('git-repository-deployment', 3600);

        if (! $lock->get()) {
            throw new RuntimeException('Another deployment operation is running. Wait for it to finish.');
        }

        try {
            return $operation();
        } finally {
            $lock->release();
        }
    }
}
