<?php

namespace Tests\Feature;

use App\Services\DeploymentDatabaseService;
use App\Services\DeploymentStore;
use App\Services\LocalGitDeploymentService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
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
