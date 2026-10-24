<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ShowDevelopmentModePage
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldBypass($request)) {
            return $next($request);
        }

        $settings = $this->settings();

        if (($settings['development_mode_enabled'] ?? '0') !== '1') {
            return $next($request);
        }

        return response()
            ->view('development-mode', ['settings' => $settings], 503)
            ->header('Retry-After', '3600');
    }

    private function shouldBypass(Request $request): bool
    {
        return $request->is('admin')
            || $request->is('admin/*')
            || $request->is('admin/system/git-repository', 'admin/system/git-repository/*')
            || $request->is('system-health', 'system-health/*')
            || $request->is('build/*')
            || $request->is('storage/*')
            || $request->is('images/*')
            || $request->is('favicon.ico')
            || $request->is('up')
            || $request->expectsJson();
    }

    /**
     * @return array<string, string|null>
     */
    private function settings(): array
    {
        if (! $this->siteSettingsTableExists()) {
            return [];
        }

        $settings = Cache::rememberForever('site_settings', function (): array {
            $settingsEn = SiteSetting::pluck('value_en', 'key')->toArray();
            $settingsBn = SiteSetting::pluck('value_bn', 'key')->toArray();

            return ['en' => $settingsEn, 'bn' => $settingsBn];
        });

        return $settings['en'] ?? [];
    }

    private function siteSettingsTableExists(): bool
    {
        try {
            return Schema::hasTable('site_settings');
        } catch (Throwable) {
            return false;
        }
    }
}
