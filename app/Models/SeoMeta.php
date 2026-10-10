<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeoMeta extends Model
{
    use HasFactory;

    protected $fillable = [
        'seoable_type',
        'seoable_id',
        'seo_title_en',
        'seo_title_bn',
        'seo_description_en',
        'seo_description_bn',
        'seo_keywords_en',
        'seo_keywords_bn',
        'focus_keyword_en',
        'focus_keyword_bn',
        'canonical_url_en',
        'canonical_url_bn',
        'og_title_en',
        'og_title_bn',
        'og_description_en',
        'og_description_bn',
        'og_image',
        'twitter_title_en',
        'twitter_title_bn',
        'twitter_description_en',
        'twitter_description_bn',
        'twitter_image',
        'twitter_card',
        'robots_index',
        'robots_follow',
        'robots_noarchive',
        'robots_nosnippet',
        'robots_noimageindex',
        'schema_type',
        'custom_schema_json',
        'breadcrumb_title_en',
        'breadcrumb_title_bn',
        'sitemap_include',
        'sitemap_priority',
        'sitemap_change_frequency',
        'seo_score_en',
        'seo_score_bn',
    ];

    protected $casts = [
        'custom_schema_json' => 'array',
        'robots_index' => 'boolean',
        'robots_follow' => 'boolean',
        'robots_noarchive' => 'boolean',
        'robots_nosnippet' => 'boolean',
        'robots_noimageindex' => 'boolean',
        'sitemap_include' => 'boolean',
    ];

    public function seoable()
    {
        return $this->morphTo();
    }
}
