<?php

namespace Database\Factories;

use App\Models\HomepageHero;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HomepageHero>
 */
class HomepageHeroFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hero_badge_label_en' => 'Zero Gravities',
            'hero_badge_label_bn' => 'জিরো গ্র্যাভিটিস',
            'hero_badge_text_en' => 'Technology • Solutions • Commerce',
            'hero_badge_text_bn' => 'প্রযুক্তি • সমাধান • বাণিজ্য',
            'hero_title_prefix_en' => 'Technology Without Limits,',
            'hero_title_prefix_bn' => 'সীমাহীন প্রযুক্তি,',
            'hero_title_accent_en' => 'Built For Growth',
            'hero_title_accent_bn' => 'আধুনিক ব্যবসায়িক সমাধান',
            'hero_intro_en' => fake()->sentence(14),
            'hero_intro_bn' => 'আপনার ব্যবসার জন্য আধুনিক ও নির্ভরযোগ্য প্রযুক্তি সমাধান।',
            'hero_primary_button_label_en' => 'Explore Solutions',
            'hero_primary_button_label_bn' => 'আমাদের সমাধান দেখুন',
            'hero_secondary_button_label_en' => 'Request Consultation',
            'hero_secondary_button_label_bn' => 'সমাধানের জন্য যোগাযোগ',
            'hero_card_title_en' => 'Enterprise IT Reliability',
            'hero_card_title_bn' => '২৪/৭ এন্টারপ্রাইজ সাপোর্ট',
            'hero_card_subtitle_en' => '99.9% Uptime Guarantee',
            'hero_card_subtitle_bn' => '১০০% নিশ্চিত আপটাইম এসএলএ',
            'hero_stat_1_value_en' => '15+',
            'hero_stat_1_value_bn' => '১৫+',
            'hero_stat_1_label_en' => 'Years Exp',
            'hero_stat_1_label_bn' => 'বছরের অভিজ্ঞতা',
            'hero_stat_2_value_en' => '200+',
            'hero_stat_2_value_bn' => '২০০+',
            'hero_stat_2_label_en' => 'Clients',
            'hero_stat_2_label_bn' => 'কর্পোরেট ক্লায়েন্ট',
            'hero_stat_3_value_en' => '24/7',
            'hero_stat_3_value_bn' => '২৪/৭',
            'hero_stat_3_label_en' => 'NOC Support',
            'hero_stat_3_label_bn' => 'সাপোর্ট টিম',
            'display_order' => 0,
        ];
    }
}
