<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Throwable;
use ZipArchive;

#[Signature('deployment:package {commit : Full lowercase Git commit SHA} {--output= : Private directory for the release files}')]
#[Description('Package production dependencies, assets, and the migrated build database schema for cPanel')]
class BuildDeploymentRelease extends Command
{
    public function handle(Filesystem $files): int
    {
        $commit = (string) $this->argument('commit');

        if (! preg_match('/\A[a-f0-9]{40}\z/', $commit)) {
            $this->error('The commit must be a full lowercase 40-character Git SHA.');

            return self::FAILURE;
        }

        $source = $this->sourcePath();
        $output = (string) ($this->option('output') ?: storage_path('app/private/deployment-releases/'.$commit));

        try {
            foreach (['vendor/autoload.php', 'public/build/manifest.json', 'public/index.php', 'bootstrap/app.php', 'artisan', 'composer.json', 'composer.lock'] as $requiredFile) {
                if (! is_file($source.'/'.$requiredFile) || is_link($source.'/'.$requiredFile)) {
                    throw new RuntimeException('Missing regular release file: '.$requiredFile.'. Install production dependencies and build assets first.');
                }
            }

            $this->validateOutputPath($output, $source);
            $files->ensureDirectoryExists($output, 0700);

            foreach (['release.zip', 'release-manifest.json', 'release.sha256'] as $filename) {
                if (file_exists($output.'/'.$filename) || is_link($output.'/'.$filename)) {
                    throw new RuntimeException('The release output already exists. Choose an empty output directory.');
                }
            }

            $manifest = [
                'version' => 1,
                'commit' => $commit,
                'php_version' => PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION,
                'document_root' => 'public',
                'required_schema' => $this->requiredSchema(),
                'files' => [],
            ];
            $releaseFiles = $this->releaseFiles($source);

            foreach ($releaseFiles as $relativePath => $absolutePath) {
                $hash = hash_file('sha256', $absolutePath);

                if ($hash === false) {
                    throw new RuntimeException('A release file could not be read: '.$relativePath);
                }

                $manifest['files'][$relativePath] = $hash;
            }

            $encodedManifest = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
            $archive = new ZipArchive;

            if ($archive->open($output.'/release.zip', ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
                throw new RuntimeException('The release ZIP could not be created.');
            }

            try {
                foreach ($releaseFiles as $relativePath => $absolutePath) {
                    if (! $archive->addFile($absolutePath, $relativePath)) {
                        throw new RuntimeException('A release file could not be archived: '.$relativePath);
                    }
                }

                if (! $archive->addEmptyDir('bootstrap/cache') || ! $archive->addFromString('deployment-manifest.json', $encodedManifest)) {
                    throw new RuntimeException('The deployment manifest could not be archived.');
                }
            } finally {
                if (! $archive->close()) {
                    throw new RuntimeException('The release ZIP could not be finalized.');
                }
            }

            if ($files->put($output.'/release-manifest.json', $encodedManifest) === false
                || $files->put($output.'/release.sha256', hash_file('sha256', $output.'/release.zip').'  release.zip'.PHP_EOL) === false) {
                throw new RuntimeException('The release manifest and checksum could not be written.');
            }
            $this->info('Release packaged for '.$commit.'. Set the hosting document root to the application public directory.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    protected function sourcePath(): string
    {
        return base_path();
    }

    private function validateOutputPath(string $output, string $source): void
    {
        $normalizedOutput = str_replace('\\', '/', $output);
        $normalizedSource = rtrim(str_replace('\\', '/', $source), '/');

        if (preg_match('/[\x00-\x1f]/', $output) || in_array('..', explode('/', $normalizedOutput), true)) {
            throw new RuntimeException('The release output path must not contain traversal or control characters.');
        }

        if (! str_starts_with($normalizedOutput, '/') && ! preg_match('/\A[A-Za-z]:\//', $normalizedOutput)) {
            throw new RuntimeException('The release output directory must be an absolute path.');
        }

        foreach (['app', 'bootstrap', 'config', 'database', 'public', 'resources', 'routes', 'vendor'] as $root) {
            $includedPath = $normalizedSource.'/'.$root;

            if (strcasecmp($normalizedOutput, $includedPath) === 0 || str_starts_with(strtolower($normalizedOutput), strtolower($includedPath.'/'))) {
                throw new RuntimeException('The release output must be outside packaged application directories.');
            }
        }

        $ancestor = $output;

        while ($ancestor !== dirname($ancestor)) {
            if (is_link($ancestor)) {
                throw new RuntimeException('The release output must not follow symbolic links.');
            }

            $ancestor = dirname($ancestor);
        }
    }

    /**
     * @return array<string, array{columns: list<string>, indexes: list<list<string>>}>
     */
    private function requiredSchema(): array
    {
        $schema = [];

        foreach (Schema::getTables() as $table) {
            $name = $table['name'];
            $columns = Schema::getColumnListing($name);
            sort($columns);
            $indexes = array_map(fn (array $index): array => $index['columns'], Schema::getIndexes($name));
            $schema[$name] = ['columns' => $columns, 'indexes' => $indexes];
        }

        if ($schema === []) {
            throw new RuntimeException('The build database has no tables. Run migrations against the disposable build database first.');
        }

        ksort($schema);

        return $schema;
    }

    /**
     * @return array<string, string>
     */
    private function releaseFiles(string $source): array
    {
        $releaseFiles = [];

        foreach (['app', 'bootstrap', 'config', 'database/migrations', 'database/seeders', 'database/factories', 'public', 'resources/views', 'lang', 'routes', 'vendor', 'artisan', 'composer.json', 'composer.lock'] as $path) {
            $absolutePath = $source.'/'.$path;

            if (is_link($absolutePath)) {
                throw new RuntimeException('A release source is a symbolic link: '.$path);
            }

            if (is_file($absolutePath)) {
                $releaseFiles[$path] = $absolutePath;

                continue;
            }

            if (! is_dir($absolutePath)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($absolutePath, RecursiveDirectoryIterator::SKIP_DOTS));

            foreach ($iterator as $file) {
                $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($source) + 1));

                if ($this->excludedPath($relativePath)) {
                    continue;
                }

                if ($file->isLink()) {
                    throw new RuntimeException('A release source is a symbolic link: '.$relativePath);
                }

                if ($file->isFile()) {
                    $releaseFiles[$relativePath] = $file->getPathname();
                }
            }
        }

        ksort($releaseFiles);

        return $releaseFiles;
    }

    private function excludedPath(string $path): bool
    {
        foreach (['bootstrap/cache', 'public/storage', 'public/uploads', 'public/hot', 'public/fonts-manifest.dev.json'] as $excludedPath) {
            if ($path === $excludedPath || str_starts_with($path, $excludedPath.'/')) {
                return true;
            }
        }

        foreach (explode('/', $path) as $segment) {
            if (in_array($segment, ['.git', '.env', 'node_modules'], true) || str_starts_with($segment, '.env.')) {
                return true;
            }
        }

        return false;
    }
}
