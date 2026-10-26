<?php

namespace App\Services;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DeploymentDatabaseService
{
    public function __construct(
        private SystemBackupService $backups,
        private DatabaseManager $database,
        private Migrator $migrator,
    ) {}

    /**
     * @param  array<string, array{columns: list<string>, indexes?: list<list<string>>}>  $requiredSchema
     * @return array{ok: bool, checks: list<array{name: string, ok: bool, message: string}>, pending: list<string>, repairable: bool}
     */
    public function check(array $requiredSchema = []): array
    {
        $checks = [];
        $pending = [];

        try {
            $connection = $this->database->connection();
            $connection->getPdo();
            $connection->select('SELECT 1');
            $driver = $connection->getDriverName();
            $checks[] = $this->result('connection', true, 'Database connection and read access succeeded ('.$driver.').');
        } catch (Throwable) {
            return [
                'ok' => false,
                'checks' => [$this->result('connection', false, 'Database connection failed. In cPanel, verify the database host, prefixed database name, username and password in .env; assign the database user to this database with the required privileges.')],
                'pending' => [],
                'repairable' => false,
            ];
        }

        $canMigrate = false;

        try {
            if (! $this->migrator->repositoryExists()) {
                $checks[] = $this->result('migrations', false, 'The migration history table is missing. Restore the migration history from a trusted backup before deploying; existing tables must not be recreated blindly.');
            } else {
                $ran = $this->migrator->getRepository()->getRan();
                $pending = array_values(array_diff(array_keys($this->migrationFiles()), $ran));
                $canMigrate = true;
                $checks[] = $this->result('migrations', $pending === [], $pending === []
                    ? 'All known migrations have been applied.'
                    : count($pending).' pending migration(s) can be applied after the database backup succeeds.');
            }
        } catch (Throwable) {
            $checks[] = $this->result('migrations', false, 'Migration history could not be read. Check the database user privileges in cPanel and restore damaged migration history from a trusted backup.');
        }

        $schema = $connection->getSchemaBuilder();

        foreach ($requiredSchema ?: $this->essentialSchema() as $table => $requirements) {
            try {
                if (! $schema->hasTable($table)) {
                    $checks[] = $this->result('schema:'.$table, false, 'Required table '.$table.' is missing. Apply the release migrations, or restore the table from a trusted backup if no migration is pending.');

                    continue;
                }

                $missingColumns = array_values(array_diff($requirements['columns'], $schema->getColumnListing($table)));
                $missingIndexes = [];

                foreach ($requirements['indexes'] ?? [] as $columns) {
                    if (! $schema->hasIndex($table, $columns)) {
                        $missingIndexes[] = implode(', ', $columns);
                    }
                }

                $problems = [];

                if ($missingColumns !== []) {
                    $problems[] = 'missing columns: '.implode(', ', $missingColumns);
                }

                if ($missingIndexes !== []) {
                    $problems[] = 'missing indexes: '.implode('; ', $missingIndexes);
                }

                $checks[] = $this->result('schema:'.$table, $problems === [], $problems === []
                    ? 'Required columns and indexes are present in '.$table.'.'
                    : $table.' has '.implode('; ', $problems).'. Apply known release migrations; unexpected schema drift requires a reviewed migration or trusted backup.');
            } catch (Throwable) {
                $checks[] = $this->result('schema:'.$table, false, 'The schema for '.$table.' could not be inspected. Check database metadata and table privileges in cPanel.');
            }
        }

        $writeAccess = $this->checkWriteAccess($connection);
        $checks[] = $writeAccess['check'];

        return [
            'ok' => ! in_array(false, array_column($checks, 'ok'), true),
            'checks' => $checks,
            'pending' => $pending,
            'repairable' => $canMigrate && $pending !== [] && ! $writeAccess['blocks_repair'],
        ];
    }

    public function backup(string $deploymentId): string
    {
        $safeId = preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9_-]{0,99}\z/', $deploymentId) === 1
            ? $deploymentId
            : (string) Str::uuid();
        $path = 'git-deployment/backups/'.$safeId.'.sql.gz';
        $temporaryPath = null;
        $temporaryArchivePath = null;
        $archivePath = null;
        $gzipHandle = false;
        $disk = Storage::disk('local');

        try {
            if ($disk->exists($path)) {
                throw new RuntimeException('The deployment backup already exists.');
            }

            if (Schema::hasTable('system_backups')) {
                $archive = $this->backups->create('database', 'Before Git deployment '.$safeId);
                $archivePath = $disk->path('backups/'.$archive->filename);
            } else {
                $temporaryArchivePath = tempnam(sys_get_temp_dir(), 'git-deployment-database-');

                if ($temporaryArchivePath === false) {
                    throw new RuntimeException('The temporary database snapshot could not be created.');
                }

                $this->backups->writeDatabaseArchive($temporaryArchivePath);
                $archivePath = $temporaryArchivePath;
            }

            $temporaryPath = tempnam(sys_get_temp_dir(), 'git-deployment-sql-');

            if ($temporaryPath === false) {
                throw new RuntimeException('The deployment backup temporary file could not be created.');
            }

            $gzipHandle = gzopen($temporaryPath, 'wb9');

            if ($gzipHandle === false) {
                throw new RuntimeException('The deployment backup could not be opened.');
            }

            $this->backups->writePhpMyAdminSql($archivePath, $gzipHandle);

            if (! gzclose($gzipHandle)) {
                throw new RuntimeException('The deployment backup could not be finalized.');
            }

            $gzipHandle = false;
            $stream = fopen($temporaryPath, 'rb');

            if ($stream === false) {
                throw new RuntimeException('The deployment backup could not be read.');
            }

            try {
                if (! $disk->put($path, $stream)) {
                    throw new RuntimeException('The deployment backup could not be stored.');
                }
            } finally {
                fclose($stream);
            }

            if ($disk->size($path) === 0) {
                throw new RuntimeException('The deployment backup is empty.');
            }

            return $path;
        } catch (Throwable) {
            throw new RuntimeException('The pre-deployment database backup failed. No migration may proceed. Check private storage space and permissions, database read privileges, and the existing System Backups setup in cPanel.');
        } finally {
            if (is_resource($gzipHandle)) {
                gzclose($gzipHandle);
            }

            if (is_string($temporaryPath) && is_file($temporaryPath)) {
                unlink($temporaryPath);
            }

            if (is_string($temporaryArchivePath) && is_file($temporaryArchivePath)) {
                unlink($temporaryArchivePath);
            }
        }
    }

    /**
     * @param  array<string, array{columns: list<string>, indexes?: list<list<string>>}>  $requiredSchema
     * @return array{ok: bool, checks: list<array{name: string, ok: bool, message: string}>, pending: list<string>, repairable: bool}
     */
    public function repair(array $requiredSchema = [], bool $clearCache = true): array
    {
        $before = $this->check($requiredSchema);

        if (! $before['ok'] && ! $before['repairable']) {
            return $before;
        }

        try {
            if ($before['pending'] !== []) {
                $files = $this->migrationFiles();
                $paths = array_map(static fn (string $name): string => $files[$name], $before['pending']);

                if (Artisan::call('migrate', [
                    '--path' => $paths,
                    '--realpath' => true,
                    '--force' => true,
                    '--no-interaction' => true,
                ]) !== 0) {
                    throw new RuntimeException('The migration command failed.');
                }
            }

            if ($clearCache) {
                foreach (['route:clear', 'event:clear', 'clear-compiled', 'package:discover', 'config:clear', 'view:clear'] as $command) {
                    if (Artisan::call($command, ['--no-interaction' => true]) !== 0) {
                        throw new RuntimeException('The cache refresh failed.');
                    }
                }
            }
        } catch (Throwable) {
            $after = $this->check($requiredSchema);
            $after['ok'] = false;
            $after['checks'][] = $this->result('repair', false, 'The deployment database repair did not finish. Completed migrations remain recorded. Check cPanel database ALTER, CREATE, INDEX and data privileges, available storage, and PHP cache-directory permissions; review the release migration before retrying. No automatic schema reset or rollback was performed.');

            return $after;
        }

        return $this->check($requiredSchema);
    }

    /**
     * @return array<string, string>
     */
    private function migrationFiles(): array
    {
        return $this->migrator->getMigrationFiles(array_merge($this->migrator->paths(), [database_path('migrations')]));
    }

    /**
     * @return array{check: array{name: string, ok: bool, message: string}, blocks_repair: bool}
     */
    private function checkWriteAccess(Connection $connection): array
    {
        try {
            $schema = $connection->getSchemaBuilder();

            if (! $schema->hasTable('cache') || ! $schema->hasColumns('cache', ['key', 'value', 'expiration'])) {
                return [
                    'check' => $this->result('permissions', false, 'A safe write-permission check is unavailable because the application cache table is missing or incomplete. Apply its known migration and retry; verify the database user privileges in cPanel.'),
                    'blocks_repair' => false,
                ];
            }

            if (in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)) {
                $cacheTable = collect($schema->getTables($schema->getCurrentSchemaListing()))->firstWhere('name', $connection->getTablePrefix().'cache');

                if (strtolower($cacheTable['engine'] ?? '') !== 'innodb') {
                    return [
                        'check' => $this->result('permissions', false, 'A safe rolled-back write check requires an InnoDB cache table. Verify database privileges and ask the host to review the cache table storage engine.'),
                        'blocks_repair' => true,
                    ];
                }
            } elseif (! in_array($connection->getDriverName(), ['sqlite', 'pgsql', 'sqlsrv'], true)) {
                return [
                    'check' => $this->result('permissions', false, 'This database driver does not support the deployment write check. Verify database privileges with the hosting provider.'),
                    'blocks_repair' => true,
                ];
            }

            $level = $connection->transactionLevel();
            $key = 'git-deployment-check:'.Str::uuid();
            $connection->beginTransaction();

            try {
                $connection->table('cache')->insert(['key' => $key, 'value' => 'check', 'expiration' => time() + 60]);

                if ($connection->table('cache')->where('key', $key)->value('value') !== 'check'
                    || $connection->table('cache')->where('key', $key)->update(['value' => 'verified']) !== 1
                    || $connection->table('cache')->where('key', $key)->delete() !== 1) {
                    throw new RuntimeException('The write check did not complete.');
                }
            } finally {
                $connection->rollBack($level);
            }

            return [
                'check' => $this->result('permissions', true, 'Cache-table insert, read, update and delete succeeded and were rolled back. Migration schema privileges are verified when the known migrations run.'),
                'blocks_repair' => false,
            ];
        } catch (Throwable) {
            return [
                'check' => $this->result('permissions', false, 'The rolled-back database write check failed. In cPanel, assign this database user to the database and grant SELECT, INSERT, UPDATE and DELETE; release migrations may also require CREATE, ALTER and INDEX privileges.'),
                'blocks_repair' => true,
            ];
        }
    }

    /**
     * @return array<string, array{columns: list<string>, indexes?: list<list<string>>}>
     */
    private function essentialSchema(): array
    {
        return [
            'users' => ['columns' => ['id', 'name', 'email', 'username', 'password', 'role', 'is_active'], 'indexes' => [['email'], ['username']]],
            'roles' => ['columns' => ['id', 'name', 'slug', 'is_active'], 'indexes' => [['slug']]],
            'site_settings' => ['columns' => ['id', 'key', 'value_en', 'value_bn'], 'indexes' => [['key']]],
            'homepage_heroes' => ['columns' => ['id', 'hero_title_prefix_en', 'hero_title_prefix_bn', 'display_order'], 'indexes' => [['display_order']]],
            'cache' => ['columns' => ['key', 'value', 'expiration'], 'indexes' => [['key']]],
            'cache_locks' => ['columns' => ['key', 'owner', 'expiration'], 'indexes' => [['key']]],
            'system_backups' => ['columns' => ['id', 'filename', 'type', 'status', 'created_by', 'size_bytes', 'error', 'completed_at']],
        ];
    }

    /**
     * @return array{name: string, ok: bool, message: string}
     */
    private function result(string $name, bool $ok, string $message): array
    {
        return ['name' => $name, 'ok' => $ok, 'message' => $message];
    }
}
