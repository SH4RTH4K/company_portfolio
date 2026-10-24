<?php

namespace Tests\Feature;

use App\Services\DeploymentDatabaseService;
use App\Services\DeploymentStore;
use App\Services\GitDeploymentService;
use App\Services\GitRepositoryClient;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class GitDeploymentServiceTest extends TestCase
{
    private const COMMIT = '0123456789abcdef0123456789abcdef01234567';

    private const OTHER_COMMIT = 'fedcba9876543210fedcba9876543210fedcba98';

    private const RELEASE_ID = '11111111-1111-4111-8111-111111111111';

    public function test_refresh_rejects_a_workflow_run_for_a_different_commit(): void
    {
        Storage::fake('local');

        $release = [
            'id' => self::RELEASE_ID,
            'repository' => 'company/portfolio',
            'branch' => 'main',
            'commit' => self::COMMIT,
            'php_version' => '8.4',
            'delivery' => 'zip',
            'status' => 'building',
            'run_id' => 123,
            'approved_at' => null,
        ];
        $store = Mockery::mock(DeploymentStore::class);
        $store->shouldReceive('release')->once()->with(self::RELEASE_ID)->andReturn($release);
        $store->shouldReceive('settings')->once()->andReturn([
            'repository' => 'company/portfolio',
            'branch' => 'main',
            'token' => 'github_pat_test',
            'php_version' => '8.4',
            'delivery' => 'zip',
        ]);
        $github = Mockery::mock(GitRepositoryClient::class);
        $github->shouldReceive('run')->once()->andReturn([
            'run_id' => 123,
            'status' => 'completed',
            'conclusion' => 'success',
            'commit' => self::OTHER_COMMIT,
            'url' => 'https://github.com/company/portfolio/actions/runs/123',
            'artifacts' => [],
        ]);
        $database = Mockery::mock(DeploymentDatabaseService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not match the confirmed commit');

        (new GitDeploymentService($store, $github, $database))->refresh(self::RELEASE_ID);
    }
}
