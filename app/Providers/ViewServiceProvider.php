<?php

namespace App\Providers;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view) {
            $settings = Cache::rememberForever('site_settings', function () {
                $settingsEn = SiteSetting::pluck('value_en', 'key')->toArray();
                $settingsBn = SiteSetting::pluck('value_bn', 'key')->toArray();

                return ['en' => $settingsEn, 'bn' => $settingsBn];
            });

            $locale = app()->getLocale();
            $localeSettings = [];

            // Merge fallbacks (if specific locale value is null, use English)
            foreach ($settings['en'] as $key => $value) {
                if ($locale === 'bn' && ! empty($settings['bn'][$key])) {
                    $localeSettings[$key] = $settings['bn'][$key];
                } else {
                    $localeSettings[$key] = $value;
                }
            }

            $view->with('globalSettings', $localeSettings);
        });
    }
}
