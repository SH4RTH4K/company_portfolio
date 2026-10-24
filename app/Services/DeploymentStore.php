<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class DeploymentStore
{
    /**
     * @return array{
     *     repository: string,
     *     branch: string,
     *     remote_name: string,
     *     repository_type: string,
     *     authentication: string,
     *     credential: string,
     *     run_migrations: bool,
     *     clear_cache: bool,
     *     health_check: bool,
     *     token: string,
     *     php_version: string,
     *     delivery: string,
     *     enabled: bool
     * }
     */
    public function settings(): array
    {
        $runtimePhpVersion = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;
        $defaultPhpVersion = in_array($runtimePhpVersion, ['8.3', '8.4'], true) ? $runtimePhpVersion : '8.4';
        $defaults = [
            'repository' => '',
            'branch' => 'main',
            'remote_name' => 'origin',
            'repository_type' => 'public',
            'authentication' => 'none',
            'credential' => '',
            'run_migrations' => true,
            'clear_cache' => true,
            'health_check' => true,
            'token' => '',
            'php_version' => $defaultPhpVersion,
            'delivery' => 'git',
            'enabled' => false,
        ];
        $disk = Storage::disk('local');

        if (! $disk->exists('git-deployment/settings.enc')) {
            return $defaults;
        }

        try {
            $settings = json_decode(Crypt::decryptString($disk->get('git-deployment/settings.enc')), true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            throw new RuntimeException('Deployment settings could not be decrypted. Keep the original APP_KEY or configure the repository again.');
        }

        if (! is_array($settings)) {
            throw new RuntimeException('Deployment settings could not be decrypted. Keep the original APP_KEY or configure the repository again.');
        }

        return array_replace($defaults, array_intersect_key($settings, $defaults));
    }

    /** @param array<string, string|bool> $settings */
    public function saveSettings(array $settings): void
    {
        $this->write('settings.enc', Crypt::encryptString(json_encode(array_replace($this->settings(), $settings), JSON_THROW_ON_ERROR)));
        Storage::disk('local')->delete('git-deployment/preview.json');
        Storage::disk('local')->delete('git-deployment/git-status.json');
    }

    /** @param array<string, mixed> $data */
    public function writeData(string $name, array $data): void
    {
        $this->assertName($name);
        $this->write($name.'.json', json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
    }

    /** @return array<string, mixed> */
    public function readData(string $name): array
    {
        $this->assertName($name);
        $disk = Storage::disk('local');

        if (! $disk->exists('git-deployment/'.$name.'.json')) {
            return [];
        }

        $data = json_decode($disk->get('git-deployment/'.$name.'.json'), true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($data)) {
            throw new RuntimeException('The deployment record is invalid.');
        }

        return $data;
    }

    /** @param array<string, mixed> $release */
    public function saveRelease(array $release): void
    {
        $this->assertId($release['id']);
        $this->write('history/'.$release['id'].'.json', json_encode($release, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
    }

    /** @return array<string, mixed> */
    public function release(string $id): array
    {
        $this->assertId($id);
        $path = 'git-deployment/history/'.$id.'.json';

        if (! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return json_decode(Storage::disk('local')->get($path), true, flags: JSON_THROW_ON_ERROR);
    }

    /** @return list<array<string, mixed>> */
    public function history(): array
    {
        $records = [];

        foreach (Storage::disk('local')->files('git-deployment/history') as $path) {
            if (str_ends_with($path, '.json')) {
                $records[] = json_decode(Storage::disk('local')->get($path), true, flags: JSON_THROW_ON_ERROR);
            }
        }

        usort($records, fn (array $first, array $second): int => strcmp($second['created_at'], $first['created_at']));

        return $records;
    }

    public function hasActiveDeployment(): bool
    {
        foreach ($this->history() as $release) {
            if (in_array($release['status'], ['deploying', 'awaiting_verification', 'verification_failed'], true)) {
                return true;
            }
        }

        return false;
    }

    private function write(string $path, string $contents): void
    {
        $absolute = Storage::disk('local')->path('git-deployment/'.$path);
        File::ensureDirectoryExists(dirname($absolute), 0700);
        File::replace($absolute, $contents, 0600);
    }

    private function assertName(string $name): void
    {
        if (! preg_match('/^[a-z0-9-]+$/', $name)) {
            throw new RuntimeException('Invalid deployment record.');
        }
    }

    private function assertId(string $id): void
    {
        if (! Str::isUuid($id)) {
            abort(404);
        }
    }
}
