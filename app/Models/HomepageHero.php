<?php

namespace App\Models;

use Database\Factories\HomepageHeroFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomepageHero extends Model
{
    /** @use HasFactory<HomepageHeroFactory> */
    use HasFactory;

    public const array CONTENT_KEYS = [
        'hero_badge_label',
        'hero_badge_text',
        'hero_title_prefix',
        'hero_title_accent',
        'hero_intro',
        'hero_primary_button_label',
        'hero_secondary_button_label',
        'hero_card_title',
        'hero_card_subtitle',
        'hero_stat_1_value',
        'hero_stat_1_label',
        'hero_stat_2_value',
        'hero_stat_2_label',
        'hero_stat_3_value',
        'hero_stat_3_label',
    ];

    protected $fillable = [
        'hero_badge_label_en',
        'hero_badge_label_bn',
        'hero_badge_text_en',
        'hero_badge_text_bn',
        'hero_title_prefix_en',
        'hero_title_prefix_bn',
        'hero_title_accent_en',
        'hero_title_accent_bn',
        'hero_intro_en',
        'hero_intro_bn',
        'hero_primary_button_label_en',
        'hero_primary_button_label_bn',
        'hero_secondary_button_label_en',
        'hero_secondary_button_label_bn',
        'hero_card_title_en',
        'hero_card_title_bn',
        'hero_card_subtitle_en',
        'hero_card_subtitle_bn',
        'hero_stat_1_value_en',
        'hero_stat_1_value_bn',
        'hero_stat_1_label_en',
        'hero_stat_1_label_bn',
        'hero_stat_2_value_en',
        'hero_stat_2_value_bn',
        'hero_stat_2_label_en',
        'hero_stat_2_label_bn',
        'hero_stat_3_value_en',
        'hero_stat_3_value_bn',
        'hero_stat_3_label_en',
        'hero_stat_3_label_bn',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
        ];
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('id');
    }

    public function text(string $key, string $locale): ?string
    {
        $localizedValue = $this->getAttribute($key.'_'.$locale);

        if (filled($localizedValue)) {
            return (string) $localizedValue;
        }

        $englishValue = $this->getAttribute($key.'_en');

        return filled($englishValue) ? (string) $englishValue : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toEditorArray(): array
    {
        $slide = ['id' => $this->getKey()];

        foreach (self::CONTENT_KEYS as $key) {
            $slide[$key] = [
                'en' => $this->getAttribute($key.'_en'),
                'bn' => $this->getAttribute($key.'_bn'),
            ];
        }

        return $slide;
    }
}
