<?php

namespace Tests\Feature;

use App\Services\DeploymentDatabaseService;
use Illuminate\Foundation\PackageManifest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class DeploymentDatabaseTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $temporaryDirectories = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryDirectories as $directory) {
            File::deleteDirectory($directory);
        }

        parent::tearDown();
    }

    public function test_connection_failures_give_hosting_guidance_without_exposing_connection_details(): void
    {
        $originalConnection = config('database.default');
        config([
            'database.default' => 'deployment_unavailable',
            'database.connections.deployment_unavailable' => [
                'driver' => 'sqlite',
                'database' => sys_get_temp_dir().'/credential-secret-do-not-leak/missing.sqlite',
            ],
        ]);

        try {
            $result = $this->app->make(DeploymentDatabaseService::class)->check();
        } finally {
            config(['database.default' => $originalConnection]);
        }

        $this->assertFalse($result['ok']);
        $this->assertFalse($result['repairable']);
        $this->assertSame([], $result['pending']);
        $this->assertStringContainsString('cPanel', $result['checks'][0]['message']);
        $this->assertStringNotContainsString('credential-secret-do-not-leak', json_encode($result, JSON_THROW_ON_ERROR));
    }

    public function test_database_checks_leave_existing_cache_values_and_locks_untouched(): void
    {
        DB::table('cache')->insert(['key' => 'existing-entry', 'value' => 'preserve', 'expiration' => 2000000000]);
        DB::table('cache_locks')->insert(['key' => 'deployment-lock', 'owner' => 'existing-owner', 'expiration' => 2000000000]);
        $transactionLevel = DB::connection()->transactionLevel();

        $result = $this->app->make(DeploymentDatabaseService::class)->check();

        $this->assertTrue($result['ok'], json_encode($result, JSON_THROW_ON_ERROR));
        $this->assertDatabaseCount('cache', 1);
        $this->assertDatabaseHas('cache', ['key' => 'existing-entry', 'value' => 'preserve']);
        $this->assertDatabaseHas('cache_locks', ['key' => 'deployment-lock', 'owner' => 'existing-owner']);
        $this->assertSame($transactionLevel, DB::connection()->transactionLevel());
    }

    public function test_read_only_database_is_reported_without_leaving_a_permission_probe(): void
    {
        $this->migration('2099_01_01_000006_block_read_only_repair', <<<'PHP'
Schema::table('site_settings', function (Blueprint $table): void {
    $table->string('read_only_repair_column')->nullable();
});
PHP);
        DB::statement('PRAGMA query_only = ON');

        try {
            $result = $this->app->make(DeploymentDatabaseService::class)->repair();
        } finally {
            DB::statement('PRAGMA query_only = OFF');
        }

        $permissions = collect($result['checks'])->firstWhere('name', 'permissions');
        $this->assertFalse($result['ok']);
        $this->assertFalse($result['repairable']);
        $this->assertFalse($permissions['ok']);
        $this->assertStringContainsString('INSERT', $permissions['message']);
        $this->assertStringNotContainsString('SQLSTATE', $permissions['message']);
        $this->assertDatabaseCount('cache', 0);
        $this->assertFalse(Schema::hasColumn('site_settings', 'read_only_repair_column'));
    }

    public function test_schema_drift_is_reported_without_guessing_a_repair(): void
    {
        $requirements = ['users' => ['columns' => ['id', 'unknown_release_column'], 'indexes' => [['name', 'email']]]];

        $result = $this->app->make(DeploymentDatabaseService::class)->repair($requirements);

        $this->assertFalse($result['ok']);
        $this->assertFalse($result['repairable']);
        $this->assertSame([], $result['pending']);
        $this->assertStringContainsString('missing columns: unknown_release_column', collect($result['checks'])->firstWhere('name', 'schema:users')['message']);
        $this->assertStringContainsString('missing indexes: name, email', collect($result['checks'])->firstWhere('name', 'schema:users')['message']);
        $this->assertFalse(Schema::hasColumn('users', 'unknown_release_column'));
    }

    public function test_missing_migration_history_requires_review_instead_of_recreating_existing_tables(): void
    {
        Schema::drop('migrations');

        $result = $this->app->make(DeploymentDatabaseService::class)->repair();

        $this->assertFalse($result['ok']);
        $this->assertFalse($result['repairable']);
        $this->assertStringContainsString('Restore the migration history', collect($result['checks'])->firstWhere('name', 'migrations')['message']);
        $this->assertFalse(Schema::hasTable('migrations'));
        $this->assertTrue(Schema::hasTable('users'));
    }

    public function test_missing_cache_table_does_not_report_write_permissions_as_verified(): void
    {
        Schema::drop('cache');

        $result = $this->app->make(DeploymentDatabaseService::class)->check();

        $this->assertFalse($result['ok']);
        $permissions = collect($result['checks'])->firstWhere('name', 'permissions');
        $this->assertFalse($permissions['ok']);
        $this->assertStringContainsString('safe write-permission check is unavailable', $permissions['message']);
        $this->assertFalse(Schema::hasTable('cache'));
    }

    public function test_known_pending_migrations_are_applied_without_resetting_existing_data_or_locks(): void
    {
        $this->isolateCacheDirectories();
        $this->migration('2099_01_01_000001_add_deployment_note_to_site_settings', <<<'PHP'
Schema::table('site_settings', function (Blueprint $table): void {
    $table->string('deployment_note')->nullable()->index();
});
PHP);
        DB::table('site_settings')->insert(['key' => 'preserved', 'value_en' => 'Before migration']);
        DB::table('cache')->insert(['key' => 'existing-entry', 'value' => 'preserve', 'expiration' => 2000000000]);
        DB::table('cache_locks')->insert(['key' => 'deployment-lock', 'owner' => 'existing-owner', 'expiration' => 2000000000]);
        $requirements = ['site_settings' => ['columns' => ['key', 'deployment_note'], 'indexes' => [['deployment_note']]]];
        $service = $this->app->make(DeploymentDatabaseService::class);
        $before = $service->check($requirements);

        $result = $service->repair($requirements);

        $this->assertSame(['2099_01_01_000001_add_deployment_note_to_site_settings'], $before['pending']);
        $this->assertTrue($before['repairable']);
        $this->assertTrue($result['ok'], json_encode($result, JSON_THROW_ON_ERROR));
        $this->assertSame([], $result['pending']);
        $this->assertDatabaseHas('migrations', ['migration' => '2099_01_01_000001_add_deployment_note_to_site_settings']);
        $this->assertDatabaseHas('site_settings', ['key' => 'preserved', 'value_en' => 'Before migration', 'deployment_note' => null]);
        $this->assertDatabaseHas('cache', ['key' => 'existing-entry', 'value' => 'preserve']);
        $this->assertDatabaseHas('cache_locks', ['key' => 'deployment-lock', 'owner' => 'existing-owner']);
    }

    public function test_finalization_clears_stale_runtime_caches_and_rebuilds_package_discovery(): void
    {
        $this->isolateCacheDirectories();
        $cachedPaths = [
            $this->app->getCachedRoutesPath(),
            $this->app->getCachedEventsPath(),
            $this->app->getCachedServicesPath(),
            $this->app->getCachedConfigPath(),
            config('view.compiled').'/stale-view.php',
        ];

        foreach ($cachedPaths as $path) {
            File::put($path, '<?php return ["stale" => true];');
        }

        File::put($this->app->getCachedPackagesPath(), '<?php return ["stale-package" => []];');
        DB::table('cache')->insert(['key' => 'existing-entry', 'value' => 'preserve', 'expiration' => 2000000000]);
        DB::table('cache_locks')->insert(['key' => 'deployment-lock', 'owner' => 'existing-owner', 'expiration' => 2000000000]);

        $result = $this->app->make(DeploymentDatabaseService::class)->repair();

        $this->assertTrue($result['ok'], json_encode($result, JSON_THROW_ON_ERROR));

        foreach ($cachedPaths as $path) {
            $this->assertFileDoesNotExist($path);
        }

        $this->assertFileExists($this->app->getCachedPackagesPath());
        $this->assertStringNotContainsString('stale-package', File::get($this->app->getCachedPackagesPath()));
        $this->assertDatabaseHas('cache', ['key' => 'existing-entry', 'value' => 'preserve']);
        $this->assertDatabaseHas('cache_locks', ['key' => 'deployment-lock', 'owner' => 'existing-owner']);
    }

    public function test_failed_migration_keeps_completed_history_and_sanitizes_the_failure(): void
    {
        $this->isolateCacheDirectories();
        $this->migration('2099_01_01_000002_create_deployment_completed_fixture', <<<'PHP'
Schema::create('deployment_completed_fixture', function (Blueprint $table): void {
    $table->id();
});
PHP);
        $this->migration('2099_01_01_000003_fail_deployment_fixture', 'throw new \RuntimeException("credential-secret SQLSTATE select sensitive-data");');

        $result = $this->app->make(DeploymentDatabaseService::class)->repair();

        $this->assertFalse($result['ok']);
        $this->assertSame(['2099_01_01_000003_fail_deployment_fixture'], $result['pending']);
        $this->assertDatabaseHas('migrations', ['migration' => '2099_01_01_000002_create_deployment_completed_fixture']);
        $this->assertDatabaseMissing('migrations', ['migration' => '2099_01_01_000003_fail_deployment_fixture']);
        $this->assertTrue(Schema::hasTable('deployment_completed_fixture'));
        $this->assertStringContainsString('Completed migrations remain recorded', collect($result['checks'])->firstWhere('name', 'repair')['message']);
        $this->assertStringNotContainsString('credential-secret', json_encode($result, JSON_THROW_ON_ERROR));
        Schema::drop('deployment_completed_fixture');
    }

    public function test_backup_contains_actual_schema_and_data_from_before_migration(): void
    {
        Storage::fake('local');
        $this->isolateCacheDirectories();
        $this->migration('2099_01_01_000004_add_after_backup_column', <<<'PHP'
Schema::table('site_settings', function (Blueprint $table): void {
    $table->string('after_backup_column')->nullable();
});
PHP);
        DB::table('site_settings')->insert(['key' => 'pre-deployment-marker', 'value_en' => 'Preserved SQL data']);
        $service = $this->app->make(DeploymentDatabaseService::class);

        $path = $service->backup('deployment-before-migration');
        $result = $service->repair(['site_settings' => ['columns' => ['key', 'after_backup_column']]]);

        $this->assertTrue($result['ok'], json_encode($result, JSON_THROW_ON_ERROR));
        $this->assertSame('git-deployment/backups/deployment-before-migration.sql.gz', $path);
        Storage::disk('local')->assertExists($path);
        $sql = gzdecode(Storage::disk('local')->get($path));
        $this->assertIsString($sql);
        $this->assertStringContainsString('CREATE TABLE "site_settings"', $sql);
        $this->assertStringContainsString('pre-deployment-marker', $sql);
        $this->assertStringContainsString('Preserved SQL data', $sql);
        $this->assertStringNotContainsString('after_backup_column', $sql);
        $this->assertTrue(Schema::hasColumn('site_settings', 'after_backup_column'));
    }

    public function test_backup_failure_is_sanitized_and_does_not_apply_pending_migrations(): void
    {
        $disk = Storage::fake('local');
        file_put_contents($disk->path('backups'), 'not a directory');
        $this->migration('2099_01_01_000005_add_blocked_backup_column', <<<'PHP'
Schema::table('site_settings', function (Blueprint $table): void {
    $table->string('blocked_backup_column')->nullable();
});
PHP);

        try {
            $this->app->make(DeploymentDatabaseService::class)->backup('failed-backup');
            $this->fail('Backup failures must stop the deployment.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('No migration may proceed', $exception->getMessage());
            $this->assertStringNotContainsString($disk->path(''), $exception->getMessage());
        }

        $this->assertFalse(Schema::hasColumn('site_settings', 'blocked_backup_column'));
        $disk->assertMissing('git-deployment/backups/failed-backup.sql.gz');
    }

    public function test_deployment_backup_cannot_overwrite_an_existing_recovery_file(): void
    {
        $disk = Storage::fake('local');
        $disk->put('git-deployment/backups/existing-deployment.sql.gz', 'keep-original');

        try {
            $this->app->make(DeploymentDatabaseService::class)->backup('existing-deployment');
            $this->fail('Existing recovery backups must not be replaced.');
        } catch (RuntimeException) {
            $this->assertSame('keep-original', $disk->get('git-deployment/backups/existing-deployment.sql.gz'));
        }
    }

    private function isolateCacheDirectories(): void
    {
        $directory = $this->temporaryDirectory();
        $this->app->useBootstrapPath($directory);
        File::makeDirectory($directory.'/cache');
        $this->app->make(PackageManifest::class)->manifestPath = $this->app->getCachedPackagesPath();
        config(['view.compiled' => $directory.'/views']);
        File::makeDirectory($directory.'/views');
    }

    private function migration(string $name, string $body): void
    {
        $directory = $this->temporaryDirectory();
        $source = "<?php\n\nuse Illuminate\\Database\\Migrations\\Migration;\nuse Illuminate\\Database\\Schema\\Blueprint;\nuse Illuminate\\Support\\Facades\\Schema;\n\nreturn new class extends Migration {\npublic function up(): void {\n".$body."\n}\npublic function down(): void {}\n};\n";
        File::put($directory.'/'.$name.'.php', $source);
        $this->app->make('migrator')->path($directory);
    }

    private function temporaryDirectory(): string
    {
        $directory = sys_get_temp_dir().'/company-portfolio-deployment-test-'.Str::uuid();
        File::makeDirectory($directory);
        $this->temporaryDirectories[] = $directory;

        return $directory;
    }
}
