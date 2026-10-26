<?php

namespace Tests\Feature;

use App\Models\HomepageHero;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HomepageHeroSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_multiple_homepage_hero_slides_in_display_order(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.homepage-hero.update'), [
                'slider_interval_seconds' => 8,
                'slides' => [
                    $this->slidePayload('First Hero', 'প্রথম হিরো'),
                    $this->slidePayload('Second Hero', 'দ্বিতীয় হিরো'),
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Homepage hero slides updated successfully.');

        $heroes = HomepageHero::query()->ordered()->get();

        $this->assertCount(2, $heroes);
        $this->assertSame('First Hero', $heroes[0]->hero_title_prefix_en);
        $this->assertSame(0, $heroes[0]->display_order);
        $this->assertSame('Second Hero', $heroes[1]->hero_title_prefix_en);
        $this->assertSame(1, $heroes[1]->display_order);
        $this->assertSame('First Hero', SiteSetting::where('key', 'hero_title_prefix')->value('value_en'));
        $this->assertSame('প্রথম হিরো', SiteSetting::where('key', 'hero_title_prefix')->value('value_bn'));
        $this->assertSame('8', SiteSetting::where('key', 'hero_slider_interval_seconds')->value('value_en'));
    }

    public function test_blank_homepage_hero_fields_save_default_copy_for_both_languages(): void
    {
        $admin = User::factory()->create();
        $slide = $this->slidePayload('Technology Without Limits,', 'সীমাহীন প্রযুক্তি,');
        $slide['hero_badge_text'] = ['en' => '', 'bn' => ''];
        $slide['hero_primary_button_label'] = ['en' => '', 'bn' => ''];

        $this->actingAs($admin)
            ->post(route('admin.homepage-hero.update'), [
                'slider_interval_seconds' => 6,
                'slides' => [$slide],
            ])
            ->assertRedirect();

        $hero = HomepageHero::query()->sole();

        $this->assertSame('Technology • Solutions • Commerce', $hero->hero_badge_text_en);
        $this->assertSame('প্রযুক্তি • সমাধান • বাণিজ্য', $hero->hero_badge_text_bn);
        $this->assertSame('Explore Solutions', $hero->hero_primary_button_label_en);
        $this->assertSame('আমাদের সমাধান দেখুন', $hero->hero_primary_button_label_bn);
    }

    public function test_admin_editor_starts_with_existing_legacy_hero_and_slider_controls(): void
    {
        $admin = User::factory()->create();
        SiteSetting::create([
            'key' => 'hero_title_prefix',
            'value_en' => 'Existing Hero',
            'value_bn' => 'পুরোনো হিরো',
        ]);
        SiteSetting::create([
            'key' => 'hero_slider_interval_seconds',
            'value_en' => '9',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.homepage-hero.index'))
            ->assertSee('Homepage Hero Slides')
            ->assertSee('Add Slide')
            ->assertSee('Transition time (seconds)')
            ->assertSee('value="9"', false)
            ->assertSee('Hero Slide')
            ->assertSee('Existing Hero')
            ->assertSee('পুরোনো হিরো')
            ->assertSee(route('admin.homepage-hero.update'), false);
    }

    public function test_update_rejects_an_empty_slide_list(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.homepage-hero.index'))
            ->post(route('admin.homepage-hero.update'), [
                'slider_interval_seconds' => 6,
                'slides' => [],
            ])
            ->assertRedirect(route('admin.homepage-hero.index'))
            ->assertInvalid(['slides' => 'Add at least one homepage hero slide.']);

        $this->assertSame(0, HomepageHero::query()->count());
    }

    public function test_update_rejects_transition_time_below_three_seconds(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.homepage-hero.update'), [
                'slider_interval_seconds' => 2,
                'slides' => [$this->slidePayload('First Hero', 'প্রথম হিরো')],
            ])
            ->assertInvalid([
                'slider_interval_seconds' => 'Set the slide transition time to at least 3 seconds.',
            ]);

        $this->assertSame(0, HomepageHero::query()->count());
        $this->assertNull(SiteSetting::where('key', 'hero_slider_interval_seconds')->value('value_en'));
    }

    public function test_homepage_uses_static_markup_when_only_one_hero_exists(): void
    {
        HomepageHero::factory()->create([
            'hero_title_prefix_en' => 'Only Hero',
        ]);

        $this->get('/en')
            ->assertSee('Only Hero')
            ->assertDontSee('id="homepageHeroCarousel"', false)
            ->assertDontSee('data-bs-ride="carousel"', false);
    }

    public function test_homepage_uses_carousel_when_multiple_heroes_exist(): void
    {
        HomepageHero::factory()->create([
            'hero_title_prefix_en' => 'First Hero',
            'display_order' => 0,
        ]);
        HomepageHero::factory()->create([
            'hero_title_prefix_en' => 'Second Hero',
            'display_order' => 1,
        ]);
        SiteSetting::create([
            'key' => 'hero_slider_interval_seconds',
            'value_en' => '9',
        ]);

        Cache::forget('site_settings');

        $this->get('/en')
            ->assertSee('First Hero')
            ->assertSee('Second Hero')
            ->assertSee('id="homepageHeroCarousel"', false)
            ->assertSee('data-bs-ride="carousel"', false)
            ->assertSee('data-bs-interval="9000"', false)
            ->assertSee('data-bs-slide="prev"', false)
            ->assertSee('data-bs-slide="next"', false);
    }

    public function test_homepage_uses_legacy_settings_until_slides_are_saved(): void
    {
        SiteSetting::create([
            'key' => 'hero_badge_text',
            'value_en' => 'Beyond Limits',
        ]);

        Cache::forget('site_settings');

        $this->get('/en')
            ->assertSee('<span class="small text-white-50">Beyond Limits</span>', false)
            ->assertDontSee('data-bs-ride="carousel"', false);
    }

    public function test_homepage_escapes_saved_hero_content(): void
    {
        HomepageHero::factory()->create([
            'hero_title_prefix_en' => '<script>alert("hero")</script>',
        ]);

        $this->get('/en')
            ->assertSee('&lt;script&gt;alert(&quot;hero&quot;)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert("hero")</script>', false);
    }

    /**
     * @return array<string, mixed>
     */
    private function slidePayload(string $titleEnglish, string $titleBengali): array
    {
        return [
            'hero_badge_label' => ['en' => 'Zero Gravities', 'bn' => 'জিরো গ্র্যাভিটিস'],
            'hero_badge_text' => ['en' => 'Technology • Solutions • Commerce', 'bn' => 'প্রযুক্তি • সমাধান • বাণিজ্য'],
            'hero_title_prefix' => ['en' => $titleEnglish, 'bn' => $titleBengali],
            'hero_title_accent' => ['en' => 'Built For Growth', 'bn' => 'আধুনিক ব্যবসায়িক সমাধান'],
            'hero_intro' => ['en' => 'Custom software, managed IT, cloud security, and hardware infrastructure.', 'bn' => 'আধুনিক প্রযুক্তি সমাধান।'],
            'hero_primary_button_label' => ['en' => 'Explore Solutions', 'bn' => 'আমাদের সমাধান দেখুন'],
            'hero_secondary_button_label' => ['en' => 'Request Consultation', 'bn' => 'সমাধানের জন্য যোগাযোগ'],
            'hero_card_title' => ['en' => 'Enterprise IT Reliability', 'bn' => '২৪/৭ এন্টারপ্রাইজ সাপোর্ট'],
            'hero_card_subtitle' => ['en' => '99.9% Uptime Guarantee', 'bn' => '১০০% নিশ্চিত আপটাইম এসএলএ'],
            'hero_stat_1_value' => ['en' => '15+', 'bn' => '১৫+'],
            'hero_stat_1_label' => ['en' => 'Years Exp', 'bn' => 'বছরের অভিজ্ঞতা'],
            'hero_stat_2_value' => ['en' => '200+', 'bn' => '২০০+'],
            'hero_stat_2_label' => ['en' => 'Clients', 'bn' => 'কর্পোরেট ক্লায়েন্ট'],
            'hero_stat_3_value' => ['en' => '24/7', 'bn' => '২৪/৭'],
            'hero_stat_3_label' => ['en' => 'NOC Support', 'bn' => 'সাপোর্ট টিম'],
        ];
    }
}
