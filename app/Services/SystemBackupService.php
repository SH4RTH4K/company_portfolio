<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use stdClass;
use Throwable;

class SystemBackupService
{
    public const TYPES = ['database', 'media', 'full'];

    private const BACKUP_TABLE = 'system_backups';

    private const FORMAT = 'company-portfolio-system-backup';

    private const FORMAT_VERSION = 1;

    public function create(string $type, ?string $createdBy = null): stdClass
    {
        if (! in_array($type, self::TYPES, true)) {
            throw new RuntimeException('Unsupported backup type.');
        }

        if (! Schema::hasTable(self::BACKUP_TABLE)) {
            throw new RuntimeException('Run the pending migrations before creating backups.');
        }

        $filename = 'company-portfolio-'.now()->format('Ymd-His').'-'.str()->random(8).'.'.$type.'.gz';
        $relativePath = 'backups/'.$filename;
        $backupId = DB::table(self::BACKUP_TABLE)->insertGetId([
            'filename' => $filename,
            'type' => $type,
            'status' => 'creating',
            'created_by' => $createdBy,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $temporaryPath = null;
        $gzipHandle = false;

        try {
            $temporaryPath = tempnam(sys_get_temp_dir(), 'portfolio-backup-');
            if ($temporaryPath === false) {
                throw new RuntimeException('A temporary backup file could not be created.');
            }

            $gzipHandle = gzopen($temporaryPath, 'wb9');
            if ($gzipHandle === false) {
                throw new RuntimeException('The compressed backup file could not be opened.');
            }

            $this->writeRecord($gzipHandle, [
                'kind' => 'metadata',
                'format' => self::FORMAT,
                'version' => self::FORMAT_VERSION,
                'type' => $type,
                'database' => config('database.default'),
                'created_at' => now()->toIso8601String(),
            ]);

            if (in_array($type, ['database', 'full'], true)) {
                $this->writeDatabase($gzipHandle);
            }

            if (in_array($type, ['media', 'full'], true)) {
                $this->writeMedia($gzipHandle);
            }

            if (! gzclose($gzipHandle)) {
                throw new RuntimeException('The compressed backup file could not be finalized.');
            }
            $gzipHandle = false;

            $disk = Storage::disk('local');
            $disk->makeDirectory('backups');
            $stream = fopen($temporaryPath, 'rb');
            if ($stream === false) {
                throw new RuntimeException('The completed backup file could not be read.');
            }

            try {
                if (! $disk->put($relativePath, $stream)) {
                    throw new RuntimeException('The backup could not be saved to local storage.');
                }
            } finally {
                fclose($stream);
            }

            $sizeBytes = filesize($temporaryPath);
            if ($sizeBytes === false) {
                throw new RuntimeException('The completed backup file size could not be read.');
            }

            DB::table(self::BACKUP_TABLE)->where('id', $backupId)->update([
                'status' => 'completed',
                'size_bytes' => $sizeBytes,
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

            return DB::table(self::BACKUP_TABLE)->where('id', $backupId)->firstOrFail();
        } catch (Throwable $exception) {
            if (is_resource($gzipHandle)) {
                gzclose($gzipHandle);
            }

            Storage::disk('local')->delete($relativePath);
            DB::table(self::BACKUP_TABLE)->where('id', $backupId)->update([
                'status' => 'failed',
                'error' => Str::limit($exception->getMessage(), 2000),
                'updated_at' => now(),
            ]);

            throw $exception;
        } finally {
            if (is_string($temporaryPath) && is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }

    public function writeDatabaseArchive(string $path): void
    {
        $gzipHandle = gzopen($path, 'wb9');

        if ($gzipHandle === false) {
            throw new RuntimeException('The database snapshot could not be opened.');
        }

        try {
            $this->writeRecord($gzipHandle, [
                'kind' => 'metadata',
                'format' => self::FORMAT,
                'version' => self::FORMAT_VERSION,
                'type' => 'database',
                'database' => config('database.default'),
                'created_at' => now()->toIso8601String(),
            ]);
            $this->writeDatabase($gzipHandle);
        } catch (Throwable $exception) {
            gzclose($gzipHandle);

            throw $exception;
        }

        if (! gzclose($gzipHandle)) {
            throw new RuntimeException('The database snapshot could not be finalized.');
        }
    }

    public function restore(string $temporaryPath, string $expectedType, ?string $createdBy = null): stdClass
    {
        if (! in_array($expectedType, self::TYPES, true)) {
            throw new RuntimeException('Unsupported restore type.');
        }

        $this->validateArchive($temporaryPath, $expectedType);

        $safetyBackup = $this->create($expectedType, 'Before restore by '.($createdBy ?: 'administrator'));

        if (in_array($expectedType, ['media', 'full'], true)) {
            $this->restoreMedia($temporaryPath);
        }

        if (in_array($expectedType, ['database', 'full'], true)) {
            $this->restoreDatabase($temporaryPath);
        }

        return $safetyBackup;
    }

    /**
     * @param  resource  $gzipHandle
     */
    public function writePhpMyAdminSql(string $backupPath, mixed $gzipHandle): void
    {
        $this->validateArchive($backupPath, 'database');
        $this->writeSqlLine($gzipHandle, "-- Database data export for phpMyAdmin.\n");
        $this->writeSqlLine($gzipHandle, "-- The application tables are dropped, recreated, and populated from this backup.\n");
        $this->writeSqlLine($gzipHandle, "SET FOREIGN_KEY_CHECKS=0;\n");

        $activeTable = null;
        $columns = [];

        $this->eachRecord($backupPath, function (array $record) use ($gzipHandle, &$activeTable, &$columns): void {
            if (($record['kind'] ?? null) === 'table') {
                $activeTable = $record['name'];
                $columns = $record['columns'];
                $quotedTable = $this->quoteSqlIdentifier($activeTable);
                $this->writeSqlLine($gzipHandle, 'DROP TABLE IF EXISTS '.$quotedTable.";\n");
                $this->writeSqlLine($gzipHandle, $this->createTableStatement($activeTable).";\n");

                return;
            }

            if (($record['kind'] ?? null) !== 'row') {
                return;
            }

            $quotedColumns = array_map($this->quoteSqlIdentifier(...), $columns);
            $values = array_map(
                fn (mixed $value): string => $this->formatSqlValue($value),
                $record['values'],
            );
            $statement = 'INSERT INTO '.$this->quoteSqlIdentifier($activeTable)
                .' ('.implode(', ', $quotedColumns).') VALUES ('.implode(', ', $values).");\n";

            $this->writeSqlLine($gzipHandle, $statement);
        });

        $this->writeSqlLine($gzipHandle, "SET FOREIGN_KEY_CHECKS=1;\n");
    }

    public function prune(int $retentionDays): int
    {
        if ($retentionDays < 1) {
            throw new RuntimeException('Backup retention must be at least one day.');
        }

        $expiredBackups = DB::table(self::BACKUP_TABLE)
            ->where('created_at', '<', now()->subDays($retentionDays))
            ->where('status', '!=', 'creating')
            ->get();
        $disk = Storage::disk('local');

        foreach ($expiredBackups as $backup) {
            $path = 'backups/'.$backup->filename;
            if ($disk->exists($path) && ! $disk->delete($path)) {
                throw new RuntimeException('An expired backup file could not be removed from local storage.');
            }

            DB::table(self::BACKUP_TABLE)->where('id', $backup->id)->delete();
        }

        return $expiredBackups->count();
    }

    private function writeDatabase(mixed $gzipHandle): void
    {
        foreach ($this->databaseTableNames() as $table) {
            $columns = Schema::getColumnListing($table);
            $this->writeRecord($gzipHandle, [
                'kind' => 'table',
                'name' => $table,
                'columns' => $columns,
            ]);

            foreach (DB::table($table)->cursor() as $row) {
                $values = [];
                foreach ($columns as $column) {
                    $values[] = $this->encodeDatabaseValue($row->{$column});
                }

                $this->writeRecord($gzipHandle, [
                    'kind' => 'row',
                    'values' => $values,
                ]);
            }
        }
    }

    private function writeMedia(mixed $gzipHandle): void
    {
        $disk = Storage::disk('public');
        $paths = $disk->allFiles();
        sort($paths);

        foreach ($paths as $path) {
            $this->assertSafeMediaPath($path);
            $stream = $disk->readStream($path);
            if (! is_resource($stream)) {
                throw new RuntimeException('A media file could not be read for backup.');
            }

            try {
                $this->writeRecord($gzipHandle, [
                    'kind' => 'file-start',
                    'path' => $path,
                ]);

                while (! feof($stream)) {
                    $chunk = fread($stream, 786432);
                    if ($chunk === false || ($chunk === '' && ! feof($stream))) {
                        throw new RuntimeException('A media file could not be read for backup.');
                    }

                    if ($chunk !== '') {
                        $this->writeRecord($gzipHandle, [
                            'kind' => 'file-chunk',
                            'contents' => base64_encode($chunk),
                        ]);
                    }
                }

                $this->writeRecord($gzipHandle, ['kind' => 'file-end']);
            } finally {
                fclose($stream);
            }
        }
    }

    private function validateArchive(string $path, string $expectedType): void
    {
        if (! is_file($path)) {
            throw new RuntimeException('The uploaded backup file could not be read.');
        }

        $state = [
            'metadata' => null,
            'active_table' => null,
            'active_file' => null,
            'tables' => [],
            'file_paths' => [],
            'files_started' => false,
        ];
        $knownTables = array_fill_keys($this->databaseTableNames(), true);

        $this->eachRecord($path, function (array $record) use (&$state, $expectedType, $knownTables): void {
            if ($state['metadata'] === null) {
                if (($record['kind'] ?? null) !== 'metadata'
                    || ($record['format'] ?? null) !== self::FORMAT
                    || ($record['version'] ?? null) !== self::FORMAT_VERSION
                    || ($record['type'] ?? null) !== $expectedType
                    || ($record['database'] ?? null) !== config('database.default')) {
                    throw new RuntimeException('The backup file is not compatible with this application or restore type.');
                }

                $state['metadata'] = $record;

                return;
            }

            switch ($record['kind'] ?? null) {
                case 'table':
                    if (! in_array($expectedType, ['database', 'full'], true) || $state['files_started']) {
                        throw new RuntimeException('The backup file contains unexpected database data.');
                    }

                    $table = $record['name'] ?? null;
                    $columns = $record['columns'] ?? null;
                    if (! is_string($table)
                        || ! isset($knownTables[$table])
                        || isset($state['tables'][$table])
                        || ! is_array($columns)
                        || ! array_is_list($columns)
                        || $columns !== Schema::getColumnListing($table)) {
                        throw new RuntimeException('The backup database schema does not match this application.');
                    }

                    $state['tables'][$table] = $columns;
                    $state['active_table'] = $table;

                    break;

                case 'row':
                    $table = $state['active_table'];
                    $values = $record['values'] ?? null;
                    if (! is_string($table)
                        || ! is_array($values)
                        || ! array_is_list($values)
                        || count($values) !== count($state['tables'][$table])) {
                        throw new RuntimeException('The backup contains an invalid database row.');
                    }

                    foreach ($values as $value) {
                        if (is_array($value)
                            && array_keys($value) === ['__system_backup_binary']
                            && is_string($value['__system_backup_binary'])
                            && base64_decode($value['__system_backup_binary'], true) !== false) {
                            continue;
                        }

                        if (! is_scalar($value) && $value !== null) {
                            throw new RuntimeException('The backup contains an unsupported database value.');
                        }
                    }

                    break;

                case 'file-start':
                    if (! in_array($expectedType, ['media', 'full'], true)) {
                        throw new RuntimeException('The backup file contains unexpected media data.');
                    }

                    $state['files_started'] = true;
                    $filePath = $record['path'] ?? null;
                    if (! is_string($filePath)
                        || $state['active_file'] !== null
                        || isset($state['file_paths'][$filePath])) {
                        throw new RuntimeException('The backup contains an invalid media file.');
                    }

                    $this->assertSafeMediaPath($filePath);
                    $state['file_paths'][$filePath] = true;
                    $state['active_file'] = $filePath;
                    $state['active_table'] = null;

                    break;

                case 'file-chunk':
                    $contents = $record['contents'] ?? null;
                    if (! is_string($state['active_file'])
                        || ! is_string($contents)
                        || base64_decode($contents, true) === false) {
                        throw new RuntimeException('The backup contains an invalid media file chunk.');
                    }

                    break;

                case 'file-end':
                    if (! is_string($state['active_file'])) {
                        throw new RuntimeException('The backup contains an invalid media file sequence.');
                    }

                    $state['active_file'] = null;

                    break;

                default:
                    throw new RuntimeException('The backup contains an unknown record type.');
            }
        });

        if ($state['metadata'] === null) {
            throw new RuntimeException('The uploaded file is not a valid system backup.');
        }

        if ($state['active_file'] !== null) {
            throw new RuntimeException('The backup contains an incomplete media file.');
        }

        if (in_array($expectedType, ['database', 'full'], true)
            && array_keys($state['tables']) !== $this->databaseTableNames()) {
            throw new RuntimeException('The backup database schema does not match this application.');
        }

        if (in_array($expectedType, ['media', 'full'], true) && ! $state['files_started']) {
            $state['files_started'] = true;
        }
    }

    private function restoreDatabase(string $path): void
    {
        $tables = $this->databaseTableNames();

        Schema::withoutForeignKeyConstraints(function () use ($path, $tables): void {
            DB::transaction(function () use ($path, $tables): void {
                foreach (array_reverse($tables) as $table) {
                    DB::table($table)->delete();
                }

                $activeTable = null;
                $columns = [];
                $batch = [];

                $this->eachRecord($path, function (array $record) use (&$activeTable, &$columns, &$batch): void {
                    if (($record['kind'] ?? null) === 'table') {
                        if ($activeTable !== null && $batch !== []) {
                            DB::table($activeTable)->insert($batch);
                        }

                        $activeTable = $record['name'];
                        $columns = $record['columns'];
                        $batch = [];

                        return;
                    }

                    if (($record['kind'] ?? null) !== 'row') {
                        return;
                    }

                    $values = array_map(
                        fn (mixed $value): mixed => $this->decodeDatabaseValue($value),
                        $record['values'],
                    );
                    $batch[] = array_combine($columns, $values);
                    if (count($batch) >= 250) {
                        DB::table($activeTable)->insert($batch);
                        $batch = [];
                    }
                });

                if ($activeTable !== null && $batch !== []) {
                    DB::table($activeTable)->insert($batch);
                }
            });
        });
    }

    private function restoreMedia(string $path): void
    {
        $disk = Storage::disk('public');
        $activeFilePath = null;
        $temporaryStream = null;

        try {
            $this->eachRecord($path, function (array $record) use ($disk, &$activeFilePath, &$temporaryStream): void {
                switch ($record['kind'] ?? null) {
                    case 'file-start':
                        $activeFilePath = $record['path'];
                        $temporaryStream = tmpfile();
                        if ($temporaryStream === false) {
                            throw new RuntimeException('A temporary media file could not be created.');
                        }

                        break;

                    case 'file-chunk':
                        $contents = base64_decode($record['contents'], true);
                        if ($contents === false || fwrite($temporaryStream, $contents) !== strlen($contents)) {
                            throw new RuntimeException('A media file could not be restored.');
                        }

                        break;

                    case 'file-end':
                        rewind($temporaryStream);
                        if (! $disk->writeStream($activeFilePath, $temporaryStream)) {
                            throw new RuntimeException('A media file could not be restored.');
                        }

                        fclose($temporaryStream);
                        $temporaryStream = null;
                        $activeFilePath = null;

                        break;
                }
            });
        } finally {
            if (is_resource($temporaryStream)) {
                fclose($temporaryStream);
            }
        }
    }

    /**
     * @return list<string>
     */
    private function databaseTableNames(): array
    {
        $tables = array_column(Schema::getTables(Schema::getCurrentSchemaListing()), 'name');
        $tables = array_values(array_filter(
            $tables,
            static fn (string $table): bool => $table !== self::BACKUP_TABLE,
        ));

        $dependencies = [];
        foreach ($tables as $table) {
            foreach (Schema::getForeignKeys($table) as $foreignKey) {
                if (in_array($foreignKey['foreign_table'], $tables, true)
                    && $foreignKey['foreign_table'] !== $table) {
                    $dependencies[$table][] = $foreignKey['foreign_table'];
                }
            }
        }

        $ordered = [];
        $visiting = [];
        $visited = [];
        $visit = function (string $table) use (&$visit, &$ordered, &$visiting, &$visited, $dependencies): void {
            if (isset($visited[$table]) || isset($visiting[$table])) {
                return;
            }

            $visiting[$table] = true;
            foreach ($dependencies[$table] ?? [] as $parent) {
                $visit($parent);
            }
            unset($visiting[$table]);
            $visited[$table] = true;
            $ordered[] = $table;
        };

        foreach ($tables as $table) {
            $visit($table);
        }

        return $ordered;
    }

    private function assertSafeMediaPath(string $path): void
    {
        $normalizedPath = str_replace('\\', '/', $path);
        if ($normalizedPath === ''
            || str_starts_with($normalizedPath, '/')
            || str_contains($normalizedPath, "\0")
            || preg_match('/[\x01-\x1F\x7F]/', $normalizedPath)
            || collect(explode('/', $normalizedPath))->contains(fn (string $segment): bool => in_array($segment, ['', '.', '..'], true))) {
            throw new RuntimeException('The backup contains an unsafe media path.');
        }

        if (config('filesystems.disks.public.driver') === 'local') {
            $diskRoot = realpath(Storage::disk('public')->path(''));
            if ($diskRoot !== false) {
                $candidate = $diskRoot;
                foreach (explode('/', $normalizedPath) as $segment) {
                    $candidate .= DIRECTORY_SEPARATOR.$segment;
                    if (is_link($candidate)) {
                        throw new RuntimeException('The backup contains a media path that uses a symbolic link.');
                    }
                }
            }
        }
    }

    private function encodeDatabaseValue(mixed $value): mixed
    {
        if (is_string($value) && preg_match('//u', $value) !== 1) {
            return ['__system_backup_binary' => base64_encode($value)];
        }

        return $value;
    }

    private function decodeDatabaseValue(mixed $value): mixed
    {
        if (is_array($value) && array_keys($value) === ['__system_backup_binary']) {
            $decoded = base64_decode($value['__system_backup_binary'], true);
            if ($decoded === false) {
                throw new RuntimeException('The backup contains an invalid binary database value.');
            }

            return $decoded;
        }

        return $value;
    }

    private function quoteSqlIdentifier(string $identifier): string
    {
        return '`'.str_replace('`', '``', $identifier).'`';
    }

    private function createTableStatement(string $table): string
    {
        $quotedTable = $this->quoteSqlIdentifier($table);

        return match (DB::connection()->getDriverName()) {
            'mysql' => $this->mysqlCreateTableStatement($quotedTable),
            'sqlite' => $this->sqliteCreateTableStatement($table),
            default => throw new RuntimeException('SQL export is not supported for this database driver.'),
        };
    }

    private function mysqlCreateTableStatement(string $quotedTable): string
    {
        $definition = DB::select('SHOW CREATE TABLE '.$quotedTable)[0] ?? null;
        if ($definition === null) {
            throw new RuntimeException('The database table definition could not be read.');
        }

        $createSql = array_values((array) $definition)[1] ?? null;
        if (! is_string($createSql) || $createSql === '') {
            throw new RuntimeException('The database table definition could not be read.');
        }

        return $createSql;
    }

    private function sqliteCreateTableStatement(string $table): string
    {
        $createSql = DB::selectOne(
            "SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?",
            [$table],
        )?->sql;

        if (! is_string($createSql) || $createSql === '') {
            throw new RuntimeException('The database table definition could not be read.');
        }

        return $createSql;
    }

    private function formatSqlValue(mixed $value): string
    {
        $value = $this->decodeDatabaseValue($value);

        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_string($value)) {
            if (preg_match('//u', $value) !== 1) {
                return "X'".bin2hex($value)."'";
            }

            $quoted = DB::connection()->getPdo()->quote($value);
            if ($quoted === false) {
                throw new RuntimeException('A database value could not be escaped for the SQL export.');
            }

            return $quoted;
        }

        throw new RuntimeException('The backup contains a value that cannot be exported as SQL.');
    }

    /**
     * @param  resource  $gzipHandle
     */
    private function writeSqlLine(mixed $gzipHandle, string $line): void
    {
        if (gzwrite($gzipHandle, $line) !== strlen($line)) {
            throw new RuntimeException('The phpMyAdmin SQL export could not be written.');
        }
    }

    /**
     * @param  resource  $gzipHandle
     * @param  array<string, mixed>  $record
     */
    private function writeRecord(mixed $gzipHandle, array $record): void
    {
        $line = json_encode($record, JSON_THROW_ON_ERROR)."\n";
        if (gzwrite($gzipHandle, $line) !== strlen($line)) {
            throw new RuntimeException('The backup file could not be written.');
        }
    }

    /**
     * @param  callable(array<string, mixed>): void  $callback
     */
    private function eachRecord(string $path, callable $callback): void
    {
        $gzipHandle = gzopen($path, 'rb');
        if ($gzipHandle === false) {
            throw new RuntimeException('The uploaded backup is not a readable compressed file.');
        }

        try {
            $recordCount = 0;
            while (($line = gzgets($gzipHandle)) !== false) {
                $record = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                if (! is_array($record)) {
                    throw new RuntimeException('The backup contains an invalid record.');
                }

                $recordCount++;
                $callback($record);
            }

            if ($recordCount === 0) {
                throw new RuntimeException('The uploaded file is empty.');
            }
        } finally {
            gzclose($gzipHandle);
        }
    }
}
