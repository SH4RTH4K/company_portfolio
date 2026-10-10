<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomepageHeroController extends Controller
{
    public function index(): View
    {
        $settings = $this->defaultEnglishValues();
        $settingsBn = $this->defaultBengaliValues();

        SiteSetting::query()
            ->get(['key', 'value_en', 'value_bn'])
            ->each(function (SiteSetting $setting) use (&$settings, &$settingsBn): void {
                if (filled($setting->value_en)) {
                    $settings[$setting->key] = $setting->value_en;
                }

                if (filled($setting->value_bn)) {
                    $settingsBn[$setting->key] = $setting->value_bn;
                }
            });

        return view('admin.homepage_hero.index', compact('settings', 'settingsBn'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hero_badge_label.en' => ['nullable', 'string', 'max:60'],
            'hero_badge_label.bn' => ['nullable', 'string', 'max:60'],
            'hero_badge_text.en' => ['nullable', 'string', 'max:120'],
            'hero_badge_text.bn' => ['nullable', 'string', 'max:120'],
            'hero_title_prefix.en' => ['nullable', 'string', 'max:120'],
            'hero_title_prefix.bn' => ['nullable', 'string', 'max:120'],
            'hero_title_accent.en' => ['nullable', 'string', 'max:120'],
            'hero_title_accent.bn' => ['nullable', 'string', 'max:120'],
            'hero_intro.en' => ['nullable', 'string', 'max:600'],
            'hero_intro.bn' => ['nullable', 'string', 'max:600'],
            'hero_primary_button_label.en' => ['nullable', 'string', 'max:60'],
            'hero_primary_button_label.bn' => ['nullable', 'string', 'max:60'],
            'hero_secondary_button_label.en' => ['nullable', 'string', 'max:60'],
            'hero_secondary_button_label.bn' => ['nullable', 'string', 'max:60'],
            'hero_card_title.en' => ['nullable', 'string', 'max:120'],
            'hero_card_title.bn' => ['nullable', 'string', 'max:120'],
            'hero_card_subtitle.en' => ['nullable', 'string', 'max:160'],
            'hero_card_subtitle.bn' => ['nullable', 'string', 'max:160'],
            'hero_stat_1_value.en' => ['nullable', 'string', 'max:30'],
            'hero_stat_1_value.bn' => ['nullable', 'string', 'max:30'],
            'hero_stat_1_label.en' => ['nullable', 'string', 'max:60'],
            'hero_stat_1_label.bn' => ['nullable', 'string', 'max:60'],
            'hero_stat_2_value.en' => ['nullable', 'string', 'max:30'],
            'hero_stat_2_value.bn' => ['nullable', 'string', 'max:30'],
            'hero_stat_2_label.en' => ['nullable', 'string', 'max:60'],
            'hero_stat_2_label.bn' => ['nullable', 'string', 'max:60'],
            'hero_stat_3_value.en' => ['nullable', 'string', 'max:30'],
            'hero_stat_3_value.bn' => ['nullable', 'string', 'max:30'],
            'hero_stat_3_label.en' => ['nullable', 'string', 'max:60'],
            'hero_stat_3_label.bn' => ['nullable', 'string', 'max:60'],
        ]);

        $defaultEnglishValues = $this->defaultEnglishValues();
        $defaultBengaliValues = $this->defaultBengaliValues();

        foreach ($validated as $key => $values) {
            $valueEn = filled($values['en'] ?? null)
                ? (string) $values['en']
                : $defaultEnglishValues[$key];
            $valueBn = filled($values['bn'] ?? null)
                ? (string) $values['bn']
                : $defaultBengaliValues[$key];

            $this->saveSetting($key, $valueEn, $valueBn);
        }

        Cache::forget('site_settings');

        return redirect()->back()->with('success', 'Homepage hero updated successfully.');
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
