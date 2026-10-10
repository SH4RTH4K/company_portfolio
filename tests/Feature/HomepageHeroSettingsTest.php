<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HomepageHeroSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_homepage_hero_content(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.homepage-hero.update'), [
                'hero_badge_label' => ['en' => 'Zero Gravities', 'bn' => 'ZG'],
                'hero_badge_text' => ['en' => 'Technology • Solutions • Commerce', 'bn' => 'Tech'],
                'hero_title_prefix' => ['en' => 'Technology Without Limits,', 'bn' => 'Title BN'],
                'hero_title_accent' => ['en' => 'Built For Growth', 'bn' => 'Accent BN'],
                'hero_intro' => ['en' => 'Custom software, managed IT, cloud security, and hardware infrastructure.', 'bn' => 'Intro BN'],
                'hero_primary_button_label' => ['en' => 'Explore Solutions', 'bn' => 'Primary BN'],
                'hero_secondary_button_label' => ['en' => 'Request Consultation', 'bn' => 'Secondary BN'],
                'hero_card_title' => ['en' => 'Enterprise IT Reliability', 'bn' => 'Card BN'],
                'hero_card_subtitle' => ['en' => '99.9% Uptime Guarantee', 'bn' => 'Subtitle BN'],
                'hero_stat_1_value' => ['en' => '15+', 'bn' => '15'],
                'hero_stat_1_label' => ['en' => 'Years Exp', 'bn' => 'Years BN'],
                'hero_stat_2_value' => ['en' => '200+', 'bn' => '200'],
                'hero_stat_2_label' => ['en' => 'Clients', 'bn' => 'Clients BN'],
                'hero_stat_3_value' => ['en' => '24/7', 'bn' => '24'],
                'hero_stat_3_label' => ['en' => 'NOC Support', 'bn' => 'Support BN'],
            ])
            ->assertRedirect();

        $this->assertSame('Technology Without Limits,', SiteSetting::where('key', 'hero_title_prefix')->value('value_en'));
        $this->assertSame('Title BN', SiteSetting::where('key', 'hero_title_prefix')->value('value_bn'));
        $this->assertSame('Built For Growth', SiteSetting::where('key', 'hero_title_accent')->value('value_en'));
        $this->assertSame('99.9% Uptime Guarantee', SiteSetting::where('key', 'hero_card_subtitle')->value('value_en'));
        $this->assertSame('24/7', SiteSetting::where('key', 'hero_stat_3_value')->value('value_en'));
    }

    public function test_blank_homepage_hero_fields_save_default_copy_for_both_languages(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.homepage-hero.update'), [
                'hero_badge_text' => ['en' => '', 'bn' => ''],
                'hero_primary_button_label' => ['en' => '', 'bn' => ''],
            ])
            ->assertRedirect();

        $this->assertSame('Technology • Solutions • Commerce', SiteSetting::where('key', 'hero_badge_text')->value('value_en'));
        $this->assertSame('প্রযুক্তি • সমাধান • বাণিজ্য', SiteSetting::where('key', 'hero_badge_text')->value('value_bn'));
        $this->assertSame('Explore Solutions', SiteSetting::where('key', 'hero_primary_button_label')->value('value_en'));
        $this->assertSame('আমাদের সমাধান দেখুন', SiteSetting::where('key', 'hero_primary_button_label')->value('value_bn'));
    }

    public function test_admin_can_open_homepage_hero_editor_from_its_own_page(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.homepage-hero.index'))
            ->assertOk()
            ->assertSee('Homepage Hero')
            ->assertSee('Hero Content')
            ->assertSee('Metric Counters')
            ->assertSee('প্রযুক্তি • সমাধান • বাণিজ্য')
            ->assertSee('আমাদের সমাধান দেখুন')
            ->assertSee(route('admin.homepage-hero.update'), false);
    }

    public function test_homepage_renders_configured_hero_badge_text(): void
    {
        SiteSetting::create([
            'key' => 'hero_badge_text',
            'value_en' => 'Beyond Limits',
        ]);

        Cache::forget('site_settings');

        $this->get('/en')
            ->assertOk()
            ->assertSee('<span class="small text-white-50">Beyond Limits</span>', false);
    }
}
