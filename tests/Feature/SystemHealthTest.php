<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SystemBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SystemHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get(route('admin.system-health.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_non_super_admin_cannot_access_system_health(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.system-health.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.system-health.backups.create', ['type' => 'database']))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.system-health.backups.phpmyadmin', ['backupId' => 1]))
            ->assertForbidden();

        $this->assertDatabaseCount('system_backups', 0);
    }

    public function test_super_admin_sees_health_migrations_and_backup_actions(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('admin.system-health.index'))
            ->assertOk()
            ->assertSee('System Health')
            ->assertSee('Database migrations')
            ->assertSee('Database backup')
            ->assertSee('Restore full backup');
    }

    public function test_database_backup_is_saved_and_listed_for_super_admin(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->post(route('admin.system-health.backups.create', ['type' => 'database']))
            ->assertRedirect()
            ->assertSessionHas('success');

        $backup = DB::table('system_backups')->first();

        $this->assertSame('database', $backup->type);
        $this->assertSame('completed', $backup->status);
        Storage::disk('local')->assertExists('backups/'.$backup->filename);

        $this->actingAs($superAdmin)
            ->get(route('admin.system-health.index'))
            ->assertSee($backup->filename)
            ->assertSee(route('admin.system-health.backups.phpmyadmin', ['backupId' => $backup->id]))
            ->assertSee('SQL for phpMyAdmin')
            ->assertSee('App archive');
    }

    public function test_super_admin_can_download_a_phpmyadmin_compatible_sql_export(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $superAdmin = User::factory()->create([
            'name' => 'PhpMyAdmin Export User',
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $backup = $this->app->make(SystemBackupService::class)->create('database');
        $archivePath = Storage::disk('local')->path('backups/'.$backup->filename);
        $exportPath = tempnam(sys_get_temp_dir(), 'portfolio-sql-export-test-');
        $gzipHandle = gzopen($exportPath, 'wb9');
        if ($gzipHandle === false) {
            unlink($exportPath);

            $this->fail('The SQL export test file could not be opened.');
        }

        try {
            $this->app->make(SystemBackupService::class)->writePhpMyAdminSql($archivePath, $gzipHandle);
        } finally {
            gzclose($gzipHandle);
        }

        try {
            $sql = gzdecode(file_get_contents($exportPath));
        } finally {
            unlink($exportPath);
        }

        $this->assertIsString($sql);
        $this->assertStringContainsString('SET FOREIGN_KEY_CHECKS=0;', $sql);
        $this->assertStringContainsString('DROP TABLE IF EXISTS `users`;', $sql);
        $this->assertStringContainsString('DROP TABLE IF EXISTS `amc_packages`;', $sql);
        $tableQuote = DB::connection()->getDriverName() === 'sqlite' ? '"' : '`';
        $this->assertStringContainsString('CREATE TABLE '.$tableQuote.'users'.$tableQuote, $sql);
        $this->assertStringContainsString('CREATE TABLE '.$tableQuote.'amc_packages'.$tableQuote, $sql);
        $this->assertStringContainsString('INSERT INTO `users`', $sql);
        $this->assertStringContainsString('PhpMyAdmin Export User', $sql);
        $this->assertStringNotContainsString('"kind":"metadata"', $sql);

        $download = $this->actingAs($superAdmin)
            ->get(route('admin.system-health.backups.phpmyadmin', ['backupId' => $backup->id]))
            ->assertDownload(pathinfo($backup->filename, PATHINFO_FILENAME).'.sql.gz');

        $downloadedExportPath = $download->baseResponse->getFile()->getPathname();
        if (is_file($downloadedExportPath)) {
            unlink($downloadedExportPath);
        }
    }

    public function test_super_admin_can_download_a_completed_backup(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $backup = $this->app->make(SystemBackupService::class)->create('database');

        $this->actingAs($superAdmin)
            ->get(route('admin.system-health.backups.download', ['backupId' => $backup->id]))
            ->assertOk()
            ->assertDownload($backup->filename);
    }

    public function test_database_backup_excludes_tables_from_other_schemas(): void
    {
        Storage::fake('local');
        DB::statement("ATTACH DATABASE ':memory:' AS clinic_app");
        DB::statement('CREATE TABLE clinic_app.activity_log (id INTEGER PRIMARY KEY, description TEXT)');

        $this->app->make(SystemBackupService::class)->create('database');
        $backup = DB::table('system_backups')->first();
        $archiveContents = gzdecode(Storage::disk('local')->get('backups/'.$backup->filename));
        $records = array_map(
            static fn (string $line): array => json_decode($line, true, flags: JSON_THROW_ON_ERROR),
            array_filter(explode("\n", $archiveContents ?: ''), static fn (string $line): bool => $line !== ''),
        );
        $tableNames = array_column(
            array_filter($records, static fn (array $record): bool => ($record['kind'] ?? null) === 'table'),
            'name',
        );

        $this->assertNotContains('activity_log', $tableNames);
    }

    public function test_database_and_full_backup_failures_display_the_actual_error(): void
    {
        $localDisk = Storage::fake('local');
        Storage::fake('public');
        file_put_contents($localDisk->path('backups'), 'not a directory');
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        foreach (['database', 'full'] as $type) {
            $response = $this->actingAs($superAdmin)
                ->post(route('admin.system-health.backups.create', ['type' => $type]))
                ->assertRedirect()
                ->assertSessionHas('error');

            $this->assertStringContainsString('Backup failed:', $response->getSession()->get('error'));
            $this->assertDatabaseHas('system_backups', [
                'type' => $type,
                'status' => 'failed',
            ]);
        }

        $failedBackup = DB::table('system_backups')->where('type', 'database')->first();
        $this->assertNotSame('Backup creation failed. Check the application logs.', $failedBackup->error);

        $this->actingAs($superAdmin)
            ->get(route('admin.system-health.index'))
            ->assertSee($failedBackup->error);
    }

    public function test_database_backup_preserves_binary_column_values(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Schema::create('system_backup_binary_values', function ($table): void {
            $table->id();
            $table->binary('payload');
        });
        DB::table('system_backup_binary_values')->insert([
            'id' => 1,
            'payload' => hex2bin('00fffe'),
        ]);

        $this->app->make(SystemBackupService::class)->create('database');
        $backup = DB::table('system_backups')->first();
        $backupPath = Storage::disk('local')->path('backups/'.$backup->filename);
        DB::table('system_backup_binary_values')->where('id', 1)->update([
            'payload' => 'changed',
        ]);

        $this->app->make(SystemBackupService::class)->restore($backupPath, 'database');

        $this->assertSame(
            hex2bin('00fffe'),
            DB::table('system_backup_binary_values')->where('id', 1)->value('payload'),
        );
    }

    public function test_database_restore_restores_data_and_creates_a_safety_backup(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $superAdmin = User::factory()->create([
            'name' => 'Original Administrator',
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->post(route('admin.system-health.backups.create', ['type' => 'database']))
            ->assertRedirect();

        $backup = DB::table('system_backups')->where('type', 'database')->first();
        $backupPath = Storage::disk('local')->path('backups/'.$backup->filename);
        User::query()->whereKey($superAdmin->id)->update(['name' => 'Changed Administrator']);

        $this->actingAs($superAdmin)
            ->post(route('admin.system-health.restore', ['type' => 'database']), [
                'backup' => new UploadedFile($backupPath, $backup->filename, 'application/gzip', null, true),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('Original Administrator', User::query()->findOrFail($superAdmin->id)->name);
        $this->assertSame(2, DB::table('system_backups')->where('status', 'completed')->count());
        $this->assertSame(
            'Before restore by Original Administrator',
            DB::table('system_backups')->where('id', '!=', $backup->id)->value('created_by'),
        );
    }

    public function test_restore_rejects_an_uploaded_file_that_is_not_a_generated_backup(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->post(route('admin.system-health.restore', ['type' => 'database']), [
                'backup' => UploadedFile::fake()->createWithContent('invalid.gz', 'not a backup'),
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(0, DB::table('system_backups')->count());
    }

    public function test_full_restore_restores_database_and_public_media(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Storage::disk('public')->put('branding/logo.txt', 'Original media');
        $superAdmin = User::factory()->create([
            'name' => 'Original Administrator',
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->post(route('admin.system-health.backups.create', ['type' => 'full']))
            ->assertRedirect();

        $backup = DB::table('system_backups')->where('type', 'full')->first();
        $backupPath = Storage::disk('local')->path('backups/'.$backup->filename);
        User::query()->whereKey($superAdmin->id)->update(['name' => 'Changed Administrator']);
        Storage::disk('public')->put('branding/logo.txt', 'Changed media');

        $this->actingAs($superAdmin)
            ->post(route('admin.system-health.restore', ['type' => 'full']), [
                'backup' => new UploadedFile($backupPath, $backup->filename, 'application/gzip', null, true),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('Original Administrator', User::query()->findOrFail($superAdmin->id)->name);
        Storage::disk('public')->assertExists('branding/logo.txt');
        $this->assertSame('Original media', Storage::disk('public')->get('branding/logo.txt'));
    }

    public function test_media_restore_rejects_paths_outside_public_storage(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $metadata = json_encode([
            'kind' => 'metadata',
            'format' => 'company-portfolio-system-backup',
            'version' => 1,
            'type' => 'media',
            'database' => config('database.default'),
        ], JSON_THROW_ON_ERROR);
        $fileRecord = json_encode([
            'kind' => 'file-start',
            'path' => '../outside.txt',
        ], JSON_THROW_ON_ERROR);
        $archive = gzencode($metadata."\n".$fileRecord."\n");

        $this->actingAs($superAdmin)
            ->post(route('admin.system-health.restore', ['type' => 'media']), [
                'backup' => UploadedFile::fake()->createWithContent('unsafe.gz', $archive),
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        Storage::disk('public')->assertMissing('outside.txt');
        $this->assertSame(0, DB::table('system_backups')->count());
    }

    public function test_scheduled_backup_command_creates_a_backup_and_prunes_expired_files(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Storage::disk('local')->put('backups/expired.gz', 'expired');
        DB::table('system_backups')->insert([
            'filename' => 'expired.gz',
            'type' => 'database',
            'status' => 'completed',
            'created_by' => 'scheduler',
            'size_bytes' => 7,
            'error' => null,
            'completed_at' => now()->subDays(15),
            'created_at' => now()->subDays(15),
            'updated_at' => now()->subDays(15),
        ]);

        $this->artisan('system:backup-database')
            ->expectsOutputToContain('removed 1 expired backup(s)')
            ->assertSuccessful();

        Storage::disk('local')->assertMissing('backups/expired.gz');
        $this->assertSame(1, DB::table('system_backups')->where('status', 'completed')->count());
    }
}
