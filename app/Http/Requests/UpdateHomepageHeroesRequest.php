<?php

namespace App\Http\Requests;

use App\Models\HomepageHero;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHomepageHeroesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'slides' => ['required', 'array', 'list', 'min:1', 'max:10'],
            'slides.*' => ['required', 'array:id,'.implode(',', HomepageHero::CONTENT_KEYS)],
            'slides.*.id' => ['nullable', 'integer', Rule::exists('homepage_heroes', 'id')],
        ];

        $maximumLengths = [
            'hero_badge_label' => 60,
            'hero_badge_text' => 120,
            'hero_title_prefix' => 120,
            'hero_title_accent' => 120,
            'hero_intro' => 600,
            'hero_primary_button_label' => 60,
            'hero_secondary_button_label' => 60,
            'hero_card_title' => 120,
            'hero_card_subtitle' => 160,
            'hero_stat_1_value' => 30,
            'hero_stat_1_label' => 60,
            'hero_stat_2_value' => 30,
            'hero_stat_2_label' => 60,
            'hero_stat_3_value' => 30,
            'hero_stat_3_label' => 60,
        ];

        foreach ($maximumLengths as $key => $maximumLength) {
            $rules['slides.*.'.$key] = ['required', 'array:en,bn'];
            $rules['slides.*.'.$key.'.en'] = ['nullable', 'string', 'max:'.$maximumLength];
            $rules['slides.*.'.$key.'.bn'] = ['nullable', 'string', 'max:'.$maximumLength];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slides.required' => 'Add at least one homepage hero slide.',
            'slides.min' => 'Add at least one homepage hero slide.',
            'slides.max' => 'A maximum of 10 homepage hero slides is allowed.',
        ];
    }
}
