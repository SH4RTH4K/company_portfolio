<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class EnsureNavigationItemIsEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $item): Response
    {
        $settings = Cache::rememberForever('site_settings', function (): array {
            $settingsEn = SiteSetting::pluck('value_en', 'key')->toArray();
            $settingsBn = SiteSetting::pluck('value_bn', 'key')->toArray();

            return ['en' => $settingsEn, 'bn' => $settingsBn];
        });

        if ((string) ($settings['en']['menu_'.$item.'_visible'] ?? '1') !== '1') {
            app()->setLocale((string) $request->route('locale'));

            abort(404);
        }

        return $next($request);
    }
}
