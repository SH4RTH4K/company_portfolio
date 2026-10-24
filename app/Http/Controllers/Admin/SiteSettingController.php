<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\MailConfiguration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SiteSettingController extends Controller
{
    public function index(): View
    {
        $settings = SiteSetting::pluck('value_en', 'key')->toArray();
        $settingsBn = SiteSetting::pluck('value_bn', 'key')->toArray();
        $mailPasswordConfigured = app(MailConfiguration::class)->passwordIsConfigured();
        $navigationItems = SiteSetting::NAVIGATION_ITEMS;

        return view('admin.settings.index', compact('mailPasswordConfigured', 'navigationItems', 'settings', 'settingsBn'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name.en' => ['nullable', 'string', 'max:255'],
            'company_name.bn' => ['nullable', 'string', 'max:255'],
            'tagline.en' => ['nullable', 'string', 'max:255'],
            'tagline.bn' => ['nullable', 'string', 'max:255'],
            'phone.en' => ['nullable', 'string', 'max:50'],
            'whatsapp.en' => ['nullable', 'string', 'max:50'],
            'email.en' => ['nullable', 'email', 'max:255'],
            'address.en' => ['nullable', 'string', 'max:1000'],
            'address.bn' => ['nullable', 'string', 'max:1000'],
            'default_language' => ['nullable', 'in:en,bn'],
            'menu_visibility' => ['sometimes', 'array:'.implode(',', array_keys(SiteSetting::NAVIGATION_ITEMS))],
            'menu_visibility.*' => ['required', 'boolean'],
            'development_mode_enabled' => ['nullable', 'boolean'],
            'development_mode_badge' => ['nullable', 'string', 'max:50'],
            'development_mode_title' => ['nullable', 'string', 'max:100'],
            'development_mode_message' => ['nullable', 'string', 'max:500'],
            'development_mode_eta' => ['nullable', 'string', 'max:120'],
            'development_mode_cta_label' => ['nullable', 'string', 'max:60'],
            'development_mode_cta_url' => [
                'nullable',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $value || preg_match('/^(https?:\/\/|mailto:|tel:|\/(?!\/))/', (string) $value)) {
                        return;
                    }

                    $fail('The development mode button URL must start with http://, https://, mailto:, tel:, or a local / path.');
                },
            ],
            'development_mode_design' => ['nullable', 'in:orbit,focus,light'],
            'development_mode_background_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'development_mode_accent_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'development_mode_text_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'facebook.en' => ['nullable', 'url', 'max:255'],
            'linkedin.en' => ['nullable', 'url', 'max:255'],
            'youtube.en' => ['nullable', 'url', 'max:255'],
            'twitter.en' => ['nullable', 'url', 'max:255'],
            'mail_mailer' => ['nullable', 'in:log,smtp'],
            'mail_host' => ['nullable', 'string', 'max:255'],
            'mail_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:1000'],
            'remove_mail_password' => ['nullable', 'boolean'],
            'mail_scheme' => ['nullable', 'in:auto,smtp,smtps'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:255'],
            'mail_admin_notification_address' => ['nullable', 'email', 'max:255'],
            'logo_width' => ['nullable', 'integer', 'min:80', 'max:320'],
            'logo_height' => ['nullable', 'integer', 'min:24', 'max:140'],
            'logo_padding' => ['nullable', 'integer', 'min:0', 'max:40'],
            'logo_border_radius' => ['nullable', 'integer', 'min:0', 'max:40'],
            'logo_background_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo_background_transparent' => ['nullable', 'boolean'],
            'text_logo_icon_class' => [
                'nullable',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (in_array($value, ['none', 'uploaded_favicon'], true) || preg_match('/^bi-[a-z0-9-]+$/', (string) $value)) {
                        return;
                    }

                    $fail('The selected text logo icon is invalid.');
                },
            ],
            'text_logo_icon_size' => ['nullable', 'integer', 'min:16', 'max:96'],
            'text_logo_icon_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text_logo_icon_width' => ['nullable', 'integer', 'min:16', 'max:160'],
            'text_logo_icon_height' => ['nullable', 'integer', 'min:16', 'max:160'],
            'text_logo_icon_padding' => ['nullable', 'integer', 'min:0', 'max:40'],
            'text_logo_icon_border_radius' => ['nullable', 'integer', 'min:0', 'max:60'],
            'text_logo_icon_background_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text_logo_icon_background_transparent' => ['nullable', 'boolean'],
            'text_logo_primary_text' => ['nullable', 'string', 'max:40'],
            'text_logo_primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text_logo_accent_text' => ['nullable', 'string', 'max:40'],
            'text_logo_accent_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text_logo_tagline_text' => ['nullable', 'string', 'max:80'],
            'text_logo_tagline_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text_logo_tagline_size' => ['nullable', 'integer', 'min:8', 'max:24'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:ico,png,jpg,jpeg,webp', 'max:512'],
            'apple_touch_icon' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
            'default_og_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $data = collect($validated)->except(
            'logo',
            'favicon',
            'apple_touch_icon',
            'default_og_image',
            'remove_logo',
            'remove_favicon',
            'remove_apple_touch_icon',
            'remove_default_og_image',
            'logo_background_transparent',
            'text_logo_icon_background_transparent',
            'development_mode_enabled',
            'menu_visibility',
            'mail_password',
            'remove_mail_password'
        )->all();

        $data['development_mode_enabled'] = $request->boolean('development_mode_enabled') ? '1' : '0';

        foreach ($validated['menu_visibility'] ?? [] as $item => $isVisible) {
            $data['menu_'.$item.'_visible'] = $isVisible ? '1' : '0';
        }

        if ($request->boolean('logo_background_transparent')) {
            $data['logo_background_color'] = 'transparent';
        }

        if ($request->boolean('text_logo_icon_background_transparent')) {
            $data['text_logo_icon_background_color'] = 'transparent';
        }

        foreach ($data as $key => $values) {
            if (is_array($values)) {
                $this->saveSetting($key, $values['en'] ?? null, $values['bn'] ?? null);
            } else {
                $this->saveSetting($key, $values);
            }
        }

        $this->updateMailPassword($request);

        foreach (['logo', 'favicon', 'apple_touch_icon', 'default_og_image'] as $field) {
            $this->updateMediaSetting($request, $field);
        }

        Cache::forget('site_settings');

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }

    private function updateMailPassword(Request $request): void
    {
        $mailConfiguration = app(MailConfiguration::class);

        if ($request->filled('mail_password')) {
            $this->saveSetting('mail_password', $mailConfiguration->encryptPassword((string) $request->input('mail_password')));

            return;
        }

        if ($request->boolean('remove_mail_password')) {
            $this->saveSetting('mail_password', null);
        }
    }

    private function updateMediaSetting(Request $request, string $field): void
    {
        $setting = SiteSetting::firstOrNew(['key' => $field]);
        $currentPath = $setting->value_en;

        if ($request->hasFile($field)) {
            $this->deleteStoredAsset($currentPath);

            $setting->value_en = $request->file($field)->store('brand-assets', 'public');
            $setting->value_bn = null;
            $setting->save();

            return;
        }

        if ($request->boolean('remove_'.$field)) {
            $this->deleteStoredAsset($currentPath);
            $setting->value_en = null;
            $setting->value_bn = null;
            $setting->save();
        }
    }

    private function saveSetting(string $key, ?string $valueEn, ?string $valueBn = null): void
    {
        SiteSetting::updateOrCreate(
            ['key' => $key],
            [
                'value_en' => $valueEn,
                'value_bn' => $valueBn,
            ]
        );
    }

    private function deleteStoredAsset(?string $path): void
    {
        if (! $path || ! str_starts_with($path, 'brand-assets/')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
