<?php

namespace Tests\Feature;

use App\Services\DeploymentDatabaseService;
use App\Services\DeploymentStore;
use App\Services\LocalGitDeploymentService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use RuntimeException;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class LocalGitDeploymentServiceTest extends TestCase
{
    private string $applicationPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->applicationPath = Storage::disk('local')->path('local-git-checkout-'.Str::uuid());
        File::ensureDirectoryExists($this->applicationPath);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->applicationPath);

        parent::tearDown();
    }

    public function test_status_reviews_incoming_commits_and_blocks_tracked_server_edits_while_preserving_untracked_uploads(): void
    {
        config(['deployment.application_path' => $this->applicationPath]);
        $this->runGit(['init', '--initial-branch=main']);
        $this->runGit(['config', 'user.name', 'Deployment test']);
        $this->runGit(['config', 'user.email', 'deployment-test@example.invalid']);
        File::put($this->applicationPath.'/tracked.php', '<?php return "v1";');
        $this->runGit(['add', 'tracked.php']);
        $this->runGit(['commit', '-m', 'Initial deployed code']);
        $previousCommit = trim($this->runGit(['rev-parse', 'HEAD'])->getOutput());

        File::put($this->applicationPath.'/tracked.php', '<?php return "v2";');
        $this->runGit(['add', 'tracked.php']);
        $this->runGit(['commit', '-m', 'Reviewed GitHub update']);
        $remoteCommit = trim($this->runGit(['rev-parse', 'HEAD'])->getOutput());
        $this->runGit(['remote', 'add', 'origin', 'https://github.com/company/portfolio.git']);
        $this->runGit(['update-ref', 'refs/remotes/origin/main', $remoteCommit]);
        $this->runGit(['reset', '--hard', $previousCommit]);
        File::ensureDirectoryExists($this->applicationPath.'/storage');
        File::put($this->applicationPath.'/storage/customer-upload.png', 'preserve this upload');

        $database = Mockery::mock(DeploymentDatabaseService::class);
        $database->shouldNotReceive('check');
        $this->instance(DeploymentDatabaseService::class, $database);
        $service = $this->app->make(LocalGitDeploymentService::class);
        $settings = $this->settings(['remote_name' => 'upstream']);

        $review = $service->status($settings);

        $this->assertSame('Update Available', $review['status']);
        $this->assertSame($previousCommit, $review['local']);
        $this->assertSame($remoteCommit, $review['remote']);
        $this->assertSame('Reviewed GitHub update', $review['commits'][0]['subject']);
        $this->assertSame('https://github.com/company/portfolio.git', trim($this->runGit(['remote', 'get-url', 'upstream'])->getOutput()));
        $this->assertSame('upstream', trim($this->runGit(['remote'])->getOutput()));
        $this->assertContains('?? storage/', $review['untracked_files']);
        $this->assertSame('preserve this upload', File::get($this->applicationPath.'/storage/customer-upload.png'));

        File::put($this->applicationPath.'/tracked.php', '<?php return "host edit";');
        $blocked = $service->status($settings);

        $this->assertSame('Local Changes Detected', $blocked['status']);
        $this->assertNotEmpty($blocked['local_changes']);
        $this->assertSame('preserve this upload', File::get($this->applicationPath.'/storage/customer-upload.png'));
    }

    public function test_disabled_server_git_does_not_run_git_commands(): void
    {
        $settings = $this->settings(['enabled' => false]);
        $status = $this->app->make(LocalGitDeploymentService::class)->status($settings);

        $this->assertSame('Disabled', $status['status']);
    }

    public function test_source_rollback_saves_a_database_backup_and_runs_health_check_without_reversing_migrations(): void
    {
        Storage::fake('local');
        config(['deployment.application_path' => $this->applicationPath]);
        $this->runGit(['init', '--initial-branch=main']);
        $this->runGit(['config', 'user.name', 'Deployment test']);
        $this->runGit(['config', 'user.email', 'deployment-test@example.invalid']);
        File::put($this->applicationPath.'/tracked.php', '<?php return "before";');
        $this->runGit(['add', 'tracked.php']);
        $this->runGit(['commit', '-m', 'Previous version']);
        $previousCommit = trim($this->runGit(['rev-parse', 'HEAD'])->getOutput());
        File::put($this->applicationPath.'/tracked.php', '<?php return "deployed";');
        $this->runGit(['add', 'tracked.php']);
        $this->runGit(['commit', '-m', 'Deployed version']);
        $deployedCommit = trim($this->runGit(['rev-parse', 'HEAD'])->getOutput());
        $this->runGit(['remote', 'add', 'origin', 'https://github.com/company/portfolio.git']);
        $this->runGit(['update-ref', 'refs/remotes/origin/main', $deployedCommit]);
        $deploymentId = (string) Str::uuid();
        $store = $this->app->make(DeploymentStore::class);
        $store->saveRelease([
            'id' => $deploymentId,
            'transport' => 'git',
            'repository' => 'company/portfolio',
            'branch' => 'main',
            'commit' => $deployedCommit,
            'deployed_commit' => $deployedCommit,
            'previous_commit' => $previousCommit,
            'status' => 'completed',
            'created_at' => now()->toIso8601String(),
        ]);
        $report = ['ok' => true, 'checks' => [], 'pending' => [], 'repairable' => false];
        $database = Mockery::mock(DeploymentDatabaseService::class);
        $database->shouldReceive('backup')->once()->with(Mockery::type('string'))->andReturn('git-deployment/backups/rollback.sql.gz');
        $database->shouldReceive('check')->once()->andReturn($report);
        $this->instance(DeploymentDatabaseService::class, $database);
        $settings = $this->settings(['clear_cache' => false]);

        $rollback = $this->app->make(LocalGitDeploymentService::class)->rollback($settings, $deploymentId, 12, 'Administrator');

        $this->assertSame('completed', $rollback['status']);
        $this->assertSame($deploymentId, $rollback['rollback_of']);
        $this->assertSame('not_reversed', $rollback['migration_status']);
        $this->assertSame('passed', $rollback['health_status']);
        $this->assertSame('git-deployment/backups/rollback.sql.gz', $rollback['backup']);
        $this->assertSame($previousCommit, trim($this->runGit(['rev-parse', 'HEAD'])->getOutput()));
        Storage::disk('local')->assertMissing('git-deployment/maintenance.json');
    }

    public function test_reviewed_deployments_create_a_database_backup_by_default(): void
    {
        Storage::fake('local');
        ['previous' => $previousCommit, 'reviewed' => $reviewedCommit] = $this->createReviewedUpdate();
        $report = ['ok' => true, 'checks' => [], 'pending' => [], 'repairable' => false];
        $database = Mockery::mock(DeploymentDatabaseService::class);
        $database->shouldReceive('check')->twice()->andReturn($report);
        $database->shouldReceive('backup')->once()->with(Mockery::type('string'))->andReturn('git-deployment/backups/before.sql.gz');
        $this->instance(DeploymentDatabaseService::class, $database);
        Artisan::shouldReceive('call')->once()->with('migrate', ['--force' => true, '--no-interaction' => true])->andReturn(0);
        $settings = $this->settings(['clear_cache' => false]);

        $release = $this->app->make(LocalGitDeploymentService::class)->deploy($settings, $reviewedCommit, 12, 'Administrator');

        $this->assertSame('completed', $release['status']);
        $this->assertSame($previousCommit, $release['previous_commit']);
        $this->assertSame($reviewedCommit, trim($this->runGit(['rev-parse', 'HEAD'])->getOutput()));
        $this->assertSame('successful', $release['migration_status']);
        $this->assertSame('passed', $release['health_status']);
        $this->assertSame('git-deployment/backups/before.sql.gz', $release['backup']);
        $this->assertSame($release['backup'], $this->app->make(DeploymentStore::class)->release($release['id'])['backup']);
        Storage::disk('local')->assertMissing('git-deployment/maintenance.json');
    }

    public function test_skipping_the_backup_still_deploys_the_reviewed_commit_and_runs_selected_checks(): void
    {
        Storage::fake('local');
        ['reviewed' => $reviewedCommit] = $this->createReviewedUpdate();
        $report = ['ok' => true, 'checks' => [], 'pending' => [], 'repairable' => false];
        $database = Mockery::mock(DeploymentDatabaseService::class);
        $database->shouldReceive('check')->twice()->andReturn($report);
        $database->shouldNotReceive('backup');
        $this->instance(DeploymentDatabaseService::class, $database);
        Artisan::shouldReceive('call')->once()->with('migrate', ['--force' => true, '--no-interaction' => true])->andReturn(0);
        $settings = $this->settings(['clear_cache' => false]);

        $release = $this->app->make(LocalGitDeploymentService::class)->deploy($settings, $reviewedCommit, 12, 'Administrator', backupDatabase: false);

        $this->assertSame('completed', $release['status']);
        $this->assertSame($reviewedCommit, trim($this->runGit(['rev-parse', 'HEAD'])->getOutput()));
        $this->assertSame('successful', $release['migration_status']);
        $this->assertSame('passed', $release['health_status']);
        $this->assertNull($release['backup']);
        $this->assertNull($this->app->make(DeploymentStore::class)->release($release['id'])['backup']);
        $this->assertContains('Database backup skipped by request.', $release['logs']);
        Storage::disk('local')->assertMissing('git-deployment/maintenance.json');
    }

    public function test_a_requested_backup_failure_stops_deployment_before_source_files_change(): void
    {
        Storage::fake('local');
        ['previous' => $previousCommit, 'reviewed' => $reviewedCommit] = $this->createReviewedUpdate();
        $database = Mockery::mock(DeploymentDatabaseService::class);
        $database->shouldReceive('check')->once()->andReturn(['ok' => true, 'checks' => [], 'pending' => [], 'repairable' => false]);
        $database->shouldReceive('backup')->once()->andThrow(new RuntimeException('Database backup failed.'));
        $this->instance(DeploymentDatabaseService::class, $database);
        Artisan::shouldReceive('call')->never();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Database backup failed.');

        try {
            $this->app->make(LocalGitDeploymentService::class)->deploy($this->settings(), $reviewedCommit, 12, 'Administrator');
        } finally {
            $this->assertSame($previousCommit, trim($this->runGit(['rev-parse', 'HEAD'])->getOutput()));
            $this->assertSame([], $this->app->make(DeploymentStore::class)->history());
            Storage::disk('local')->assertMissing('git-deployment/maintenance.json');
        }
    }

    /** @return array{previous: string, reviewed: string} */
    private function createReviewedUpdate(): array
    {
        config(['deployment.application_path' => $this->applicationPath]);
        $this->runGit(['init', '--initial-branch=main']);
        $this->runGit(['config', 'user.name', 'Deployment test']);
        $this->runGit(['config', 'user.email', 'deployment-test@example.invalid']);
        File::put($this->applicationPath.'/tracked.php', '<?php return "before";');
        $this->runGit(['add', 'tracked.php']);
        $this->runGit(['commit', '-m', 'Previous version']);
        $previousCommit = trim($this->runGit(['rev-parse', 'HEAD'])->getOutput());
        File::put($this->applicationPath.'/tracked.php', '<?php return "reviewed";');
        $this->runGit(['add', 'tracked.php']);
        $this->runGit(['commit', '-m', 'Reviewed update']);
        $reviewedCommit = trim($this->runGit(['rev-parse', 'HEAD'])->getOutput());
        $this->runGit(['clone', '--bare', $this->applicationPath, $this->applicationPath.'/remote.git']);
        $this->runGit(['remote', 'add', 'origin', 'https://github.com/company/portfolio.git']);
        $this->runGit(['reset', '--hard', $previousCommit]);
        $this->configureLocalFetchProxy();

        return ['previous' => $previousCommit, 'reviewed' => $reviewedCommit];
    }

    private function configureLocalFetchProxy(): void
    {
        File::put($this->applicationPath.'/git-proxy.php', <<<'PHP'
<?php

$arguments = array_slice($argv, 1);
if (($arguments[0] ?? '') === 'fetch') {
    $arguments = ['fetch', '--prune', __DIR__.'/remote.git', 'main:refs/remotes/origin/main'];
}
$process = proc_open(array_merge(['git'], $arguments), [0 => STDIN, 1 => STDOUT, 2 => STDERR], $pipes, __DIR__);
exit(is_resource($process) ? proc_close($process) : 1);
PHP);

        if (PHP_OS_FAMILY === 'Windows') {
            $launcher = $this->applicationPath.'/git-proxy.bat';
            File::put($launcher, '@echo off'."\r\n".'"'.PHP_BINARY.'" "%~dp0git-proxy.php" %*'."\r\n");
        } else {
            $launcher = $this->applicationPath.'/git-proxy';
            File::put($launcher, "#!/bin/sh\nexec ".escapeshellarg(PHP_BINARY).' '.escapeshellarg($this->applicationPath.'/git-proxy.php').' "$@"'."\n");
            chmod($launcher, 0755);
        }

        config(['deployment.git_binary' => $launcher]);
    }

    /** @param list<string> $arguments */
    private function runGit(array $arguments): Process
    {
        $process = new Process(array_merge(['git'], $arguments), $this->applicationPath, null, null, 20);
        $process->mustRun();

        return $process;
    }

    /** @return array<string, bool|string> */
    private function settings(array $changes = []): array
    {
        return array_replace([
            'enabled' => true,
            'repository' => 'company/portfolio',
            'repository_type' => 'public',
            'branch' => 'main',
            'remote_name' => 'origin',
            'authentication' => 'none',
            'credential' => '',
            'run_migrations' => true,
            'clear_cache' => true,
            'health_check' => true,
        ], $changes);
    }
}
