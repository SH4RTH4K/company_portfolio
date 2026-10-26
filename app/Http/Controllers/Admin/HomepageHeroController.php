<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateHomepageHeroesRequest;
use App\Models\HomepageHero;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomepageHeroController extends Controller
{
    public function index(): View
    {
        $slides = HomepageHero::query()
            ->ordered()
            ->get()
            ->map(fn (HomepageHero $hero): array => $hero->toEditorArray());

        if ($slides->isEmpty()) {
            $slides->push($this->legacySlide());
        }

        $sliderIntervalSeconds = (int) (SiteSetting::query()
            ->where('key', 'hero_slider_interval_seconds')
            ->value('value_en') ?: 6);
        $sliderIntervalSeconds = min(30, max(3, $sliderIntervalSeconds));

        return view('admin.homepage_hero.index', [
            'slides' => $slides->all(),
            'sliderIntervalSeconds' => $sliderIntervalSeconds,
        ]);
    }

    public function update(UpdateHomepageHeroesRequest $request): RedirectResponse
    {
        /** @var list<array<string, mixed>> $slides */
        $slides = $request->validated('slides');
        $sliderIntervalSeconds = (int) $request->validated('slider_interval_seconds');

        DB::transaction(function () use ($slides, $sliderIntervalSeconds): void {
            $retainedHeroIds = [];

            foreach ($slides as $displayOrder => $slide) {
                $hero = filled($slide['id'] ?? null)
                    ? HomepageHero::query()->findOrFail((int) $slide['id'])
                    : new HomepageHero;

                $hero->fill($this->heroAttributes($slide, $displayOrder));
                $hero->save();
                $retainedHeroIds[] = $hero->getKey();
            }

            HomepageHero::query()->whereNotIn('id', $retainedHeroIds)->delete();
            $this->syncLegacySettings($slides[0]);
            SiteSetting::query()->updateOrCreate(
                ['key' => 'hero_slider_interval_seconds'],
                ['value_en' => (string) $sliderIntervalSeconds, 'value_bn' => null],
            );
        });

        Cache::forget('site_settings');

        return back()->with('success', 'Homepage hero slides updated successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function legacySlide(): array
    {
        $settings = SiteSetting::query()
            ->whereIn('key', HomepageHero::CONTENT_KEYS)
            ->get(['key', 'value_en', 'value_bn'])
            ->keyBy('key');

        $slide = ['id' => null];
        $defaultEnglishValues = $this->defaultEnglishValues();
        $defaultBengaliValues = $this->defaultBengaliValues();

        foreach (HomepageHero::CONTENT_KEYS as $key) {
            $setting = $settings->get($key);
            $slide[$key] = [
                'en' => filled($setting?->value_en) ? $setting->value_en : $defaultEnglishValues[$key],
                'bn' => filled($setting?->value_bn) ? $setting->value_bn : $defaultBengaliValues[$key],
            ];
        }

        return $slide;
    }

    /**
     * @param  array<string, mixed>  $slide
     * @return array<string, mixed>
     */
    private function heroAttributes(array $slide, int $displayOrder): array
    {
        $attributes = ['display_order' => $displayOrder];
        $defaultEnglishValues = $this->defaultEnglishValues();
        $defaultBengaliValues = $this->defaultBengaliValues();

        foreach (HomepageHero::CONTENT_KEYS as $key) {
            /** @var array{en?: mixed, bn?: mixed} $translations */
            $translations = $slide[$key];
            $attributes[$key.'_en'] = filled($translations['en'] ?? null)
                ? (string) $translations['en']
                : $defaultEnglishValues[$key];
            $attributes[$key.'_bn'] = filled($translations['bn'] ?? null)
                ? (string) $translations['bn']
                : $defaultBengaliValues[$key];
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $slide
     */
    private function syncLegacySettings(array $slide): void
    {
        $attributes = $this->heroAttributes($slide, 0);

        foreach (HomepageHero::CONTENT_KEYS as $key) {
            SiteSetting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'value_en' => $attributes[$key.'_en'],
                    'value_bn' => $attributes[$key.'_bn'],
                ],
            );
        }
    }

    /**
     * @return array<string, string>
     */
    private function defaultEnglishValues(): array
    {
        return [
            'hero_badge_label' => 'Zero Gravities',
            'hero_badge_text' => 'Technology • Solutions • Commerce',
            'hero_title_prefix' => 'Technology Without Limits,',
            'hero_title_accent' => 'Built For Growth',
            'hero_intro' => 'We empower progressive enterprises with intelligent custom software, 24/7 proactive Managed IT & AMC, robust cloud cybersecurity, and enterprise-grade hardware infrastructure.',
            'hero_primary_button_label' => 'Explore Solutions',
            'hero_secondary_button_label' => 'Request Consultation',
            'hero_card_title' => 'Enterprise IT Reliability',
            'hero_card_subtitle' => '99.9% Uptime Guarantee',
            'hero_stat_1_value' => '15+',
            'hero_stat_1_label' => 'Years Exp',
            'hero_stat_2_value' => '200+',
            'hero_stat_2_label' => 'Clients',
            'hero_stat_3_value' => '24/7',
            'hero_stat_3_label' => 'NOC Support',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function defaultBengaliValues(): array
    {
        return [
            'hero_badge_label' => 'জিরো গ্র্যাভিটিস',
            'hero_badge_text' => 'প্রযুক্তি • সমাধান • বাণিজ্য',
            'hero_title_prefix' => 'সীমাহীন প্রযুক্তি,',
            'hero_title_accent' => 'আধুনিক ব্যবসায়িক সমাধান',
            'hero_intro' => 'স্মার্ট সফটওয়্যার, নির্ভরযোগ্য আইটি অবকাঠামো, ক্লাউড আর্কিটেকচার এবং ২৪/৭ এন্টারপ্রাইজ সাপোর্ট সার্ভিসের মাধ্যমে আমরা আপনার ব্যবসাকে সামনের দিকে এগিয়ে নিতে কাজ করি।',
            'hero_primary_button_label' => 'আমাদের সমাধান দেখুন',
            'hero_secondary_button_label' => 'সমাধানের জন্য যোগাযোগ',
            'hero_card_title' => '২৪/৭ এন্টারপ্রাইজ সাপোর্ট',
            'hero_card_subtitle' => '১০০% নিশ্চিত আপটাইম এসএলএ',
            'hero_stat_1_value' => '১৫+',
            'hero_stat_1_label' => 'বছরের অভিজ্ঞতা',
            'hero_stat_2_value' => '২০০+',
            'hero_stat_2_label' => 'কর্পোরেট ক্লায়েন্ট',
            'hero_stat_3_value' => '২৪/৭',
            'hero_stat_3_label' => 'সাপোর্ট টিম',
        ];
    }
}
