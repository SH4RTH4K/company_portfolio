<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SystemBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SystemHealthController extends Controller
{
    public function index(): View
    {
        $health = $this->healthChecks();
        $migrations = $this->migrationStatus();
        $backups = collect();

        try {
            if (Schema::hasTable('system_backups')) {
                $backups = DB::table('system_backups')
                    ->latest('created_at')
                    ->limit(30)
                    ->get();
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        return view('admin.system_health.index', compact('health', 'migrations', 'backups'));
    }

    public function createBackup(Request $request, string $type, SystemBackupService $backupService): RedirectResponse
    {
        try {
            $backup = $backupService->create($type, $request->user()?->name);

            return back()->with('success', ucfirst($type).' backup completed successfully. Reference '.$backup->id.'.');
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Backup failed: '.$exception->getMessage());
        }
    }

    public function restore(Request $request, string $type, SystemBackupService $backupService): RedirectResponse
    {
        $request->validate([
            'backup' => ['required', 'file', 'max:512000'],
        ]);

        $uploadedBackup = $request->file('backup');
        if ($uploadedBackup === null) {
            throw new RuntimeException('The uploaded backup file is required.');
        }

        try {
            $safetyBackup = $backupService->restore(
                $uploadedBackup->getRealPath(),
                $type,
                $request->user()?->name,
            );

            return back()->with(
                'success',
                ucfirst($type).' restore completed. A pre-restore safety backup was saved as reference '.$safetyBackup->id.'.',
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'The restore failed. Any pre-restore safety backup remains in the backup list. Check the application logs for details.');
        }
    }

    public function download(int $backupId): StreamedResponse
    {
        $backup = DB::table('system_backups')
            ->where('id', $backupId)
            ->where('status', 'completed')
            ->first();

        abort_unless($backup && Storage::disk('local')->exists('backups/'.$backup->filename), 404);

        return Storage::disk('local')->download('backups/'.$backup->filename, $backup->filename);
    }

    public function downloadPhpMyAdminSql(int $backupId, SystemBackupService $backupService): BinaryFileResponse
    {
        $backup = DB::table('system_backups')
            ->where('id', $backupId)
            ->where('type', 'database')
            ->where('status', 'completed')
            ->first();

        abort_unless($backup && Storage::disk('local')->exists('backups/'.$backup->filename), 404);

        $archivePath = Storage::disk('local')->path('backups/'.$backup->filename);
        $temporaryPath = tempnam(sys_get_temp_dir(), 'portfolio-sql-export-');
        if ($temporaryPath === false) {
            throw new RuntimeException('A temporary SQL export file could not be created.');
        }

        $gzipHandle = gzopen($temporaryPath, 'wb9');
        if ($gzipHandle === false) {
            unlink($temporaryPath);

            throw new RuntimeException('The compressed SQL export file could not be opened.');
        }

        try {
            $backupService->writePhpMyAdminSql($archivePath, $gzipHandle);
        } catch (Throwable $exception) {
            gzclose($gzipHandle);
            unlink($temporaryPath);

            throw $exception;
        }

        if (! gzclose($gzipHandle)) {
            unlink($temporaryPath);

            throw new RuntimeException('The compressed SQL export file could not be finalized.');
        }

        $filename = pathinfo($backup->filename, PATHINFO_FILENAME).'.sql.gz';

        return response()->download($temporaryPath, $filename, [
            'Content-Type' => 'application/gzip',
        ])->deleteFileAfterSend(true);
    }

    public function delete(int $backupId): RedirectResponse
    {
        $backup = DB::table('system_backups')->where('id', $backupId)->first();
        abort_unless($backup, 404);

        try {
            if (! Storage::disk('local')->delete('backups/'.$backup->filename)) {
                throw new RuntimeException('The backup file could not be removed from local storage.');
            }

            DB::table('system_backups')->where('id', $backupId)->delete();

            return back()->with('success', 'Backup deleted.');
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'The backup could not be deleted. Check the application logs for details.');
        }
    }

    public function clearCache(): RedirectResponse
    {
        try {
            foreach (['optimize:clear', 'view:cache'] as $command) {
                if (Artisan::call($command) !== 0) {
                    throw new RuntimeException('The '.$command.' command failed.');
                }
            }

            return back()->with('success', 'Application caches were cleared and compiled views were rebuilt.');
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Cache maintenance failed. Check the application logs for details.');
        }
    }

    public function runMigrations(): RedirectResponse
    {
        try {
            if (Artisan::call('migrate', ['--force' => true]) !== 0) {
                throw new RuntimeException('The migration command failed.');
            }

            $output = trim(Artisan::output());

            return back()->with('success', $output !== '' ? $output : 'Database migrations completed successfully.');
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Database migrations failed. Check the application logs for details.');
        }
    }

    /**
     * @return array<string, array{ok: bool, message: string}>
     */
    private function healthChecks(): array
    {
        try {
            DB::select('SELECT 1');
            $database = ['ok' => true, 'message' => 'Connected'];
        } catch (Throwable $exception) {
            report($exception);
            $database = ['ok' => false, 'message' => 'Connection unavailable'];
        }

        $storageWritable = collect([
            storage_path('app'),
            storage_path('framework'),
            storage_path('logs'),
        ])->every(fn (string $path): bool => is_dir($path) && is_writable($path));

        $freeBytes = disk_free_space(base_path());
        $totalBytes = disk_total_space(base_path());
        $diskAvailable = $freeBytes !== false && $totalBytes !== false && $totalBytes > 0;
        $freePercent = $diskAvailable ? ($freeBytes / $totalBytes) * 100 : 0;
        $diskMessage = $diskAvailable
            ? number_format($freePercent, 1).'% free ('.number_format($freeBytes / 1048576, 1).' MB of '.number_format($totalBytes / 1048576, 1).' MB)'
            : 'Filesystem capacity unavailable';

        return [
            'Database' => $database,
            'Storage' => [
                'ok' => $storageWritable,
                'message' => $storageWritable ? 'Required storage directories are writable' : 'One or more storage directories are not writable',
            ],
            'PHP' => [
                'ok' => version_compare(PHP_VERSION, '8.3.0', '>='),
                'message' => PHP_VERSION,
            ],
            'Laravel' => [
                'ok' => true,
                'message' => app()->version(),
            ],
            'Compression' => [
                'ok' => extension_loaded('zlib'),
                'message' => extension_loaded('zlib') ? 'Gzip support is available' : 'Gzip support is unavailable',
            ],
            'Disk space' => [
                'ok' => $diskAvailable && $freePercent >= 5,
                'message' => $diskMessage,
            ],
            'Environment' => [
                'ok' => app()->environment('production') && ! config('app.debug'),
                'message' => app()->environment().' · debug '.(config('app.debug') ? 'ON' : 'off'),
            ],
        ];
    }

    /**
     * @return array{ok: bool, pending: array<int, string>, message: string}
     */
    private function migrationStatus(): array
    {
        $migrationFiles = collect(File::files(database_path('migrations')))
            ->map(fn (\SplFileInfo $file): string => $file->getBasename('.php'))
            ->sort()
            ->values()
            ->all();
        $migrationTable = config('database.migrations.table', 'migrations');

        try {
            if (! Schema::hasTable($migrationTable)) {
                return [
                    'ok' => false,
                    'pending' => $migrationFiles,
                    'message' => 'Migration repository is not initialized',
                ];
            }

            $ran = DB::table($migrationTable)->pluck('migration')->all();
            $pending = array_values(array_diff($migrationFiles, $ran));

            return [
                'ok' => $pending === [],
                'pending' => $pending,
                'message' => $pending === [] ? 'All migrations have been applied' : count($pending).' pending migration(s)',
            ];
        } catch (Throwable $exception) {
            report($exception);

            return [
                'ok' => false,
                'pending' => [],
                'message' => 'Migration status could not be read',
            ];
        }
    }
}
