<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_brand_assets_from_settings(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.settings.update'), [
                'company_name' => ['en' => 'Zero Gravities', 'bn' => 'জিরো গ্রাভিটিজ'],
                'default_language' => 'en',
                'logo' => UploadedFile::fake()->image('logo.png', 400, 120),
                'favicon' => UploadedFile::fake()->image('favicon.png', 64, 64),
                'apple_touch_icon' => UploadedFile::fake()->image('apple.png', 180, 180),
                'default_og_image' => UploadedFile::fake()->image('og.png', 1200, 630),
            ])
            ->assertRedirect();

        foreach (['logo', 'favicon', 'apple_touch_icon', 'default_og_image'] as $key) {
            $path = SiteSetting::where('key', $key)->value('value_en');

            $this->assertNotEmpty($path);
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_admin_can_remove_existing_brand_asset(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();
        $path = UploadedFile::fake()->image('logo.png', 400, 120)->store('brand-assets', 'public');

        SiteSetting::create([
            'key' => 'logo',
            'value_en' => $path,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.settings.update'), [
                'company_name' => ['en' => 'Zero Gravities'],
                'default_language' => 'en',
                'remove_logo' => '1',
            ])
            ->assertRedirect();

        $this->assertNull(SiteSetting::where('key', 'logo')->value('value_en'));
        Storage::disk('public')->assertMissing($path);
    }

    public function test_admin_can_update_logo_display_customization(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.settings.update'), [
                'company_name' => ['en' => 'Zero Gravities'],
                'default_language' => 'en',
                'logo_width' => '220',
                'logo_height' => '72',
                'logo_padding' => '10',
                'logo_border_radius' => '8',
                'logo_background_color' => '#0d1b2a',
                'text_logo_icon_class' => 'none',
                'text_logo_icon_size' => '44',
                'text_logo_icon_color' => '#1f8cff',
                'text_logo_icon_width' => '64',
                'text_logo_icon_height' => '48',
                'text_logo_icon_padding' => '6',
                'text_logo_icon_border_radius' => '14',
                'text_logo_icon_background_color' => '#0d1b2a',
                'text_logo_primary_text' => 'ZERO',
                'text_logo_primary_color' => '#ffffff',
                'text_logo_accent_text' => 'GRAVITIES',
                'text_logo_accent_color' => '#00b4d8',
                'text_logo_tagline_text' => 'Beyond Limits',
                'text_logo_tagline_color' => '#8fa6b8',
                'text_logo_tagline_size' => '12',
            ])
            ->assertRedirect();

        $this->assertSame('220', SiteSetting::where('key', 'logo_width')->value('value_en'));
        $this->assertSame('72', SiteSetting::where('key', 'logo_height')->value('value_en'));
        $this->assertSame('10', SiteSetting::where('key', 'logo_padding')->value('value_en'));
        $this->assertSame('8', SiteSetting::where('key', 'logo_border_radius')->value('value_en'));
        $this->assertSame('#0d1b2a', SiteSetting::where('key', 'logo_background_color')->value('value_en'));
        $this->assertSame('none', SiteSetting::where('key', 'text_logo_icon_class')->value('value_en'));
        $this->assertSame('44', SiteSetting::where('key', 'text_logo_icon_size')->value('value_en'));
        $this->assertSame('#1f8cff', SiteSetting::where('key', 'text_logo_icon_color')->value('value_en'));
        $this->assertSame('64', SiteSetting::where('key', 'text_logo_icon_width')->value('value_en'));
        $this->assertSame('48', SiteSetting::where('key', 'text_logo_icon_height')->value('value_en'));
        $this->assertSame('6', SiteSetting::where('key', 'text_logo_icon_padding')->value('value_en'));
        $this->assertSame('14', SiteSetting::where('key', 'text_logo_icon_border_radius')->value('value_en'));
        $this->assertSame('#0d1b2a', SiteSetting::where('key', 'text_logo_icon_background_color')->value('value_en'));
        $this->assertSame('ZERO', SiteSetting::where('key', 'text_logo_primary_text')->value('value_en'));
        $this->assertSame('#ffffff', SiteSetting::where('key', 'text_logo_primary_color')->value('value_en'));
        $this->assertSame('GRAVITIES', SiteSetting::where('key', 'text_logo_accent_text')->value('value_en'));
        $this->assertSame('#00b4d8', SiteSetting::where('key', 'text_logo_accent_color')->value('value_en'));
        $this->assertSame('Beyond Limits', SiteSetting::where('key', 'text_logo_tagline_text')->value('value_en'));
        $this->assertSame('#8fa6b8', SiteSetting::where('key', 'text_logo_tagline_color')->value('value_en'));
        $this->assertSame('12', SiteSetting::where('key', 'text_logo_tagline_size')->value('value_en'));
    }

    public function test_transparent_logo_background_overrides_color_picker_value(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.settings.update'), [
                'company_name' => ['en' => 'Zero Gravities'],
                'default_language' => 'en',
                'logo_background_color' => '#ffffff',
                'logo_background_transparent' => '1',
            ])
            ->assertRedirect();

        $this->assertSame('transparent', SiteSetting::where('key', 'logo_background_color')->value('value_en'));
    }

    public function test_transparent_text_logo_icon_background_overrides_color_picker_value(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.settings.update'), [
                'company_name' => ['en' => 'Zero Gravities'],
                'default_language' => 'en',
                'text_logo_icon_background_color' => '#ffffff',
                'text_logo_icon_background_transparent' => '1',
            ])
            ->assertRedirect();

        $this->assertSame('transparent', SiteSetting::where('key', 'text_logo_icon_background_color')->value('value_en'));
    }

    public function test_admin_can_update_development_mode_page_settings(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.settings.update'), [
                'company_name' => ['en' => 'Zero Gravities'],
                'default_language' => 'en',
                'development_mode_enabled' => '1',
                'development_mode_badge' => 'Building',
                'development_mode_title' => 'Launching a better experience',
                'development_mode_message' => 'We are polishing the new Zero Gravities website.',
                'development_mode_eta' => 'Back online soon',
                'development_mode_cta_label' => 'Email Us',
                'development_mode_cta_url' => 'mailto:info@zerogravities.com',
                'development_mode_design' => 'focus',
                'development_mode_background_color' => '#0d1b2a',
                'development_mode_accent_color' => '#00b4d8',
                'development_mode_text_color' => '#ffffff',
            ])
            ->assertRedirect();

        $this->assertSame('1', SiteSetting::where('key', 'development_mode_enabled')->value('value_en'));
        $this->assertSame('Building', SiteSetting::where('key', 'development_mode_badge')->value('value_en'));
        $this->assertSame('Launching a better experience', SiteSetting::where('key', 'development_mode_title')->value('value_en'));
        $this->assertSame('We are polishing the new Zero Gravities website.', SiteSetting::where('key', 'development_mode_message')->value('value_en'));
        $this->assertSame('Back online soon', SiteSetting::where('key', 'development_mode_eta')->value('value_en'));
        $this->assertSame('Email Us', SiteSetting::where('key', 'development_mode_cta_label')->value('value_en'));
        $this->assertSame('mailto:info@zerogravities.com', SiteSetting::where('key', 'development_mode_cta_url')->value('value_en'));
        $this->assertSame('focus', SiteSetting::where('key', 'development_mode_design')->value('value_en'));
        $this->assertSame('#0d1b2a', SiteSetting::where('key', 'development_mode_background_color')->value('value_en'));
        $this->assertSame('#00b4d8', SiteSetting::where('key', 'development_mode_accent_color')->value('value_en'));
        $this->assertSame('#ffffff', SiteSetting::where('key', 'development_mode_text_color')->value('value_en'));
    }

    public function test_admin_can_manage_email_integration_from_settings(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.settings.update'), [
                'company_name' => ['en' => 'Zero Gravities'],
                'default_language' => 'en',
                'mail_mailer' => 'smtp',
                'mail_host' => 'smtp.example.com',
                'mail_port' => '587',
                'mail_username' => 'info@zerogravities.com',
                'mail_password' => 'secret-app-password',
                'mail_scheme' => 'smtp',
                'mail_from_address' => 'info@zerogravities.com',
                'mail_from_name' => 'Zero Gravities',
                'mail_admin_notification_address' => 'support@zerogravities.com',
            ])
            ->assertRedirect();

        $storedPassword = SiteSetting::where('key', 'mail_password')->value('value_en');

        $this->assertSame('smtp', SiteSetting::where('key', 'mail_mailer')->value('value_en'));
        $this->assertSame('smtp.example.com', SiteSetting::where('key', 'mail_host')->value('value_en'));
        $this->assertSame('587', SiteSetting::where('key', 'mail_port')->value('value_en'));
        $this->assertSame('info@zerogravities.com', SiteSetting::where('key', 'mail_username')->value('value_en'));
        $this->assertNotSame('secret-app-password', $storedPassword);
        $this->assertSame('secret-app-password', Crypt::decryptString($storedPassword));
        $this->assertSame('smtp', SiteSetting::where('key', 'mail_scheme')->value('value_en'));
        $this->assertSame('info@zerogravities.com', SiteSetting::where('key', 'mail_from_address')->value('value_en'));
        $this->assertSame('Zero Gravities', SiteSetting::where('key', 'mail_from_name')->value('value_en'));
        $this->assertSame('support@zerogravities.com', SiteSetting::where('key', 'mail_admin_notification_address')->value('value_en'));
    }

    public function test_blank_email_integration_password_keeps_existing_saved_password(): void
    {
        $admin = User::factory()->create();
        $storedPassword = Crypt::encryptString('existing-password');

        SiteSetting::create([
            'key' => 'mail_password',
            'value_en' => $storedPassword,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.settings.update'), [
                'company_name' => ['en' => 'Zero Gravities'],
                'default_language' => 'en',
                'mail_mailer' => 'smtp',
                'mail_host' => 'smtp.example.com',
                'mail_port' => '587',
                'mail_username' => 'info@zerogravities.com',
                'mail_scheme' => 'smtp',
                'mail_from_address' => 'info@zerogravities.com',
                'mail_from_name' => 'Zero Gravities',
                'mail_admin_notification_address' => 'support@zerogravities.com',
            ])
            ->assertRedirect();

        $this->assertSame($storedPassword, SiteSetting::where('key', 'mail_password')->value('value_en'));
    }

    public function test_admin_can_clear_saved_email_integration_password(): void
    {
        $admin = User::factory()->create();

        SiteSetting::create([
            'key' => 'mail_password',
            'value_en' => Crypt::encryptString('existing-password'),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.settings.update'), [
                'company_name' => ['en' => 'Zero Gravities'],
                'default_language' => 'en',
                'mail_mailer' => 'log',
                'remove_mail_password' => '1',
            ])
            ->assertRedirect();

        $this->assertNull(SiteSetting::where('key', 'mail_password')->value('value_en'));
    }

    public function test_development_mode_shows_custom_page_for_public_visitors(): void
    {
        foreach ([
            'development_mode_enabled' => '1',
            'development_mode_badge' => 'Building',
            'development_mode_title' => 'Launching a better experience',
            'development_mode_message' => 'We are polishing the new Zero Gravities website.',
            'development_mode_eta' => 'Back online soon',
            'development_mode_cta_label' => 'Email Us',
            'development_mode_cta_url' => 'mailto:info@zerogravities.com',
            'development_mode_design' => 'orbit',
            'development_mode_background_color' => '#0d1b2a',
            'development_mode_accent_color' => '#00b4d8',
            'development_mode_text_color' => '#ffffff',
        ] as $key => $value) {
            SiteSetting::create([
                'key' => $key,
                'value_en' => $value,
            ]);
        }

        Cache::forget('site_settings');

        $this->get('/en')
            ->assertServiceUnavailable()
            ->assertHeader('Retry-After', '3600')
            ->assertSee('Launching a better experience')
            ->assertSee('We are polishing the new Zero Gravities website.');
    }

    public function test_development_mode_keeps_admin_access_available(): void
    {
        SiteSetting::create([
            'key' => 'development_mode_enabled',
            'value_en' => '1',
        ]);

        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Site Settings');
    }

    public function test_settings_page_groups_controls_into_clear_sections(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Company & Contact', false)
            ->assertSee('Email Integration')
            ->assertSee('Language & Theme', false)
            ->assertSee('Development Mode')
            ->assertSee('Brand & Logo', false)
            ->assertSee('Social Links')
            ->assertDontSee('hero_badge_label', false)
            ->assertSee('Ready to apply changes?');
    }

    public function test_admin_pages_use_configured_favicon(): void
    {
        SiteSetting::create([
            'key' => 'favicon',
            'value_en' => 'brand-assets/admin-favicon.png',
        ]);

        Cache::forget('site_settings');

        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('/storage/brand-assets/admin-favicon.png', false);

        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('/storage/brand-assets/admin-favicon.png', false);
    }

    public function test_admin_layout_shows_profile_and_logout_options(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'role' => 'super_admin',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Admin User')
            ->assertSee('Super Admin')
            ->assertSee('Profile')
            ->assertSee(route('admin.users.edit', $admin), false)
            ->assertSee(route('admin.settings.index').'#brand-settings', false)
            ->assertSee(route('admin.homepage-hero.index'), false)
            ->assertSee('Language & Theme')
            ->assertSee('Logout');
    }
}
