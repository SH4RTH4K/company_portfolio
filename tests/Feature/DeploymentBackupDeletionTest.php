<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DeploymentStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DeploymentBackupDeletionTest extends TestCase
{
    use RefreshDatabase;

    private const RELEASE_ID = '11111111-1111-4111-8111-111111111111';

    private const OTHER_RELEASE_ID = '22222222-2222-4222-8222-222222222222';

    private const BACKUP_PATH = 'git-deployment/backups/before.sql.gz';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Http::preventStrayRequests();
    }

    #[DataProvider('terminalReleases')]
    public function test_super_admin_can_delete_a_previous_backup_and_keep_its_deployment_history(string $transport, string $status): void
    {
        $this->travelTo(Carbon::parse('2026-10-25T02:00:00+00:00'));
        $admin = User::factory()->create(['name' => 'Backup Administrator', 'role' => 'super_admin']);
        $release = $this->release(['transport' => $transport, 'delivery' => $transport, 'status' => $status]);
        $this->store()->saveRelease($release);
        Storage::disk('local')->put(self::BACKUP_PATH, 'deployment SQL backup');
        Storage::disk('local')->put('backups/independent-database.gz', 'independent system backup');
        Storage::disk('local')->put('git-deployment/releases/package.zip', 'release artifact');

        $this->actingAs($admin)->delete(route('admin.git-repository.backup.delete', self::RELEASE_ID))
            ->assertRedirect(route('admin.git-repository.index'))
            ->assertSessionHas('success', 'Database backup deleted. Deployment history was kept.');

        Storage::disk('local')->assertMissing(self::BACKUP_PATH);
        $this->assertSame('independent system backup', Storage::disk('local')->get('backups/independent-database.gz'));
        $this->assertSame('release artifact', Storage::disk('local')->get('git-deployment/releases/package.zip'));
        $this->assertSame(array_replace($release, [
            'backup' => null,
            'backup_deleted_at' => '2026-10-25T02:00:00+00:00',
            'logs' => ['Deployment finished.', 'Database backup deleted by Backup Administrator.'],
        ]), $this->store()->release(self::RELEASE_ID));
        $this->assertCount(1, $this->store()->history());
        $this->get(route('admin.git-repository.backup', self::RELEASE_ID))->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_guests_cannot_delete_a_previous_database_backup(): void
    {
        $release = $this->release();
        $this->store()->saveRelease($release);
        Storage::disk('local')->put(self::BACKUP_PATH, 'keep this backup');

        $this->delete(route('admin.git-repository.backup.delete', self::RELEASE_ID))
            ->assertRedirect(route('admin.login'));

        $this->assertSame('keep this backup', Storage::disk('local')->get(self::BACKUP_PATH));
        $this->assertSame($release, $this->store()->release(self::RELEASE_ID));
    }

    #[DataProvider('unprivilegedRoles')]
    public function test_unprivileged_roles_cannot_delete_a_previous_database_backup(string $role): void
    {
        $release = $this->release();
        $this->store()->saveRelease($release);
        Storage::disk('local')->put(self::BACKUP_PATH, 'keep this backup');

        $this->actingAs(User::factory()->create(['role' => $role]))
            ->delete(route('admin.git-repository.backup.delete', self::RELEASE_ID))
            ->assertForbidden();

        $this->assertSame('keep this backup', Storage::disk('local')->get(self::BACKUP_PATH));
        $this->assertSame($release, $this->store()->release(self::RELEASE_ID));
    }

    #[DataProvider('nonterminalStatuses')]
    public function test_unfinished_deployment_backups_cannot_be_deleted(string $status): void
    {
        $release = $this->release(['status' => $status]);
        $this->store()->saveRelease($release);
        Storage::disk('local')->put(self::BACKUP_PATH, 'keep this backup');

        $this->actingAs(User::factory()->create(['role' => 'super_admin']))
            ->delete(route('admin.git-repository.backup.delete', self::RELEASE_ID))
            ->assertRedirect(route('admin.git-repository.index'))
            ->assertSessionHas('error', 'Finish or recover this deployment before deleting its database backup.');

        $this->assertSame('keep this backup', Storage::disk('local')->get(self::BACKUP_PATH));
        $this->assertSame($release, $this->store()->release(self::RELEASE_ID));
    }

    public function test_the_maintenance_deployments_backup_cannot_be_deleted_even_with_a_terminal_status(): void
    {
        $release = $this->release();
        $this->store()->saveRelease($release);
        $this->store()->writeData('maintenance', ['deployment' => self::RELEASE_ID]);
        Storage::disk('local')->put(self::BACKUP_PATH, 'keep this backup');

        $this->actingAs(User::factory()->create(['role' => 'super_admin']))
            ->delete(route('admin.git-repository.backup.delete', self::RELEASE_ID))
            ->assertRedirect(route('admin.git-repository.index'))
            ->assertSessionHas('error', 'Finish or recover this deployment before deleting its database backup.');

        $this->assertSame('keep this backup', Storage::disk('local')->get(self::BACKUP_PATH));
        $this->assertSame($release, $this->store()->release(self::RELEASE_ID));
        $this->assertSame(['deployment' => self::RELEASE_ID], $this->store()->readData('maintenance'));
    }

    public function test_a_previous_backup_can_be_deleted_while_another_deployment_is_pending(): void
    {
        $this->store()->saveRelease($this->release());
        $activeRelease = $this->release([
            'id' => self::OTHER_RELEASE_ID,
            'status' => 'awaiting_verification',
            'backup' => 'git-deployment/backups/active.sql.gz',
        ]);
        $this->store()->saveRelease($activeRelease);
        $this->store()->writeData('maintenance', ['deployment' => self::OTHER_RELEASE_ID]);
        Storage::disk('local')->put(self::BACKUP_PATH, 'previous backup');
        Storage::disk('local')->put($activeRelease['backup'], 'active backup');

        $this->actingAs(User::factory()->create(['role' => 'super_admin']))
            ->delete(route('admin.git-repository.backup.delete', self::RELEASE_ID))
            ->assertRedirect(route('admin.git-repository.index'))
            ->assertSessionHas('success');

        Storage::disk('local')->assertMissing(self::BACKUP_PATH);
        $this->assertSame('active backup', Storage::disk('local')->get($activeRelease['backup']));
        $this->assertSame($activeRelease, $this->store()->release(self::OTHER_RELEASE_ID));
        $this->assertSame(['deployment' => self::OTHER_RELEASE_ID], $this->store()->readData('maintenance'));
    }

    #[DataProvider('unsafeBackupPaths')]
    public function test_missing_or_unsafe_backup_paths_return_404_without_deleting_other_files(?string $path): void
    {
        $release = $this->release(['backup' => $path]);
        $this->store()->saveRelease($release);
        Storage::disk('local')->put('secret.sql.gz', 'outside the deployment backup directory');
        Storage::disk('local')->put('git-deployment/settings.enc', 'private deployment settings');
        Storage::disk('local')->put('backups/system.sql.gz', 'independent system backup');

        $this->actingAs(User::factory()->create(['role' => 'super_admin']))
            ->delete(route('admin.git-repository.backup.delete', self::RELEASE_ID))
            ->assertNotFound();

        $this->assertSame('outside the deployment backup directory', Storage::disk('local')->get('secret.sql.gz'));
        $this->assertSame('private deployment settings', Storage::disk('local')->get('git-deployment/settings.enc'));
        $this->assertSame('independent system backup', Storage::disk('local')->get('backups/system.sql.gz'));
        $this->assertSame($release, $this->store()->release(self::RELEASE_ID));
    }

    public function test_a_missing_deployment_returns_404_without_deleting_existing_backups(): void
    {
        Storage::disk('local')->put(self::BACKUP_PATH, 'keep this backup');

        $this->actingAs(User::factory()->create(['role' => 'super_admin']))
            ->delete(route('admin.git-repository.backup.delete', self::RELEASE_ID))
            ->assertNotFound();

        $this->assertSame('keep this backup', Storage::disk('local')->get(self::BACKUP_PATH));
    }

    public function test_a_storage_deletion_failure_keeps_the_backup_and_its_history_reference(): void
    {
        $release = $this->release();
        $this->store()->saveRelease($release);
        $disk = Storage::disk('local');
        $disk->put(self::BACKUP_PATH, 'keep this backup');
        $failingDisk = Mockery::mock($disk);
        $failingDisk->shouldReceive('delete')->once()->with(self::BACKUP_PATH)->andReturn(false);
        Storage::set('local', $failingDisk);

        $this->actingAs(User::factory()->create(['role' => 'super_admin']))
            ->delete(route('admin.git-repository.backup.delete', self::RELEASE_ID))
            ->assertRedirect(route('admin.git-repository.index'))
            ->assertSessionHas('error', 'The database backup could not be deleted. Check private storage permissions and try again.');

        $this->assertSame('keep this backup', $disk->get(self::BACKUP_PATH));
        $this->assertSame($release, $this->store()->release(self::RELEASE_ID));
    }

    public function test_an_in_progress_deployment_operation_blocks_backup_deletion(): void
    {
        $release = $this->release();
        $this->store()->saveRelease($release);
        Storage::disk('local')->put(self::BACKUP_PATH, 'keep this backup');
        $admin = User::factory()->create(['role' => 'super_admin']);
        $lock = Cache::store('file')->lock('git-repository-deployment', 3600);
        $this->assertTrue($lock->get());

        try {
            $this->actingAs($admin)->delete(route('admin.git-repository.backup.delete', self::RELEASE_ID))
                ->assertRedirect(route('admin.git-repository.index'))
                ->assertSessionHas('error', 'Another deployment operation is running. Wait for it to finish.');

            $this->assertSame('keep this backup', Storage::disk('local')->get(self::BACKUP_PATH));
            $this->assertSame($release, $this->store()->release(self::RELEASE_ID));
        } finally {
            $lock->release();
        }
    }

    /** @return array<string, array{string, string}> */
    public static function terminalReleases(): array
    {
        $releases = [];
        foreach (['git', 'zip'] as $transport) {
            foreach (['completed', 'failed', 'recovered'] as $status) {
                $releases[$transport.' '.$status] = [$transport, $status];
            }
        }

        return $releases;
    }

    /** @return array<string, array{string}> */
    public static function unprivilegedRoles(): array
    {
        return ['admin' => ['admin'], 'editor' => ['editor']];
    }

    /** @return array<string, array{string}> */
    public static function nonterminalStatuses(): array
    {
        return [
            'building' => ['building'],
            'ready' => ['ready'],
            'deploying' => ['deploying'],
            'awaiting verification' => ['awaiting_verification'],
            'verification failed' => ['verification_failed'],
        ];
    }

    /** @return array<string, array{?string}> */
    public static function unsafeBackupPaths(): array
    {
        return [
            'no backup' => [null],
            'missing file' => [self::BACKUP_PATH],
            'parent directory traversal' => ['git-deployment/backups/../../secret.sql.gz'],
            'settings traversal' => ['git-deployment/backups/../settings.enc'],
            'windows traversal' => ['git-deployment/backups/..\\..\\secret.sql.gz'],
            'independent system backup' => ['backups/system.sql.gz'],
            'nested directory' => ['git-deployment/backups/nested/before.sql.gz'],
            'wrong extension' => ['git-deployment/backups/before.zip'],
        ];
    }

    private function store(): DeploymentStore
    {
        return $this->app->make(DeploymentStore::class);
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    private function release(array $changes = []): array
    {
        return array_replace([
            'id' => self::RELEASE_ID,
            'transport' => 'git',
            'repository' => 'company/portfolio',
            'branch' => 'main',
            'commit' => '0123456789abcdef0123456789abcdef01234567',
            'delivery' => 'git',
            'status' => 'completed',
            'actor_id' => 1,
            'actor' => 'Deployment administrator',
            'created_at' => '2026-10-24T09:00:00+00:00',
            'approved_at' => '2026-10-24T09:05:00+00:00',
            'backup' => self::BACKUP_PATH,
            'logs' => ['Deployment finished.'],
        ], $changes);
    }
}
