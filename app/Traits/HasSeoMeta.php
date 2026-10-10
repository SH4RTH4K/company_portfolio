<?php

namespace App\Traits;

use App\Models\SeoMeta;

trait HasSeoMeta
{
    /**
     * Get the SEO meta data for the model.
     */
    public function seoMeta()
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    /**
     * Sync SEO metadata from request data.
     */
    public function syncSeoMeta(array $data)
    {
        if (empty($data)) {
            return;
        }

        $seoData = [];
        $seoFields = [
            'seo_title_en', 'seo_title_bn', 'seo_description_en', 'seo_description_bn',
            'seo_keywords_en', 'seo_keywords_bn', 'focus_keyword_en', 'focus_keyword_bn',
            'canonical_url_en', 'canonical_url_bn', 'og_title_en', 'og_title_bn',
            'og_description_en', 'og_description_bn', 'og_image',
            'twitter_title_en', 'twitter_title_bn', 'twitter_description_en', 'twitter_description_bn',
            'twitter_image', 'twitter_card', 'robots_index', 'robots_follow',
            'robots_noarchive', 'robots_nosnippet', 'robots_noimageindex',
            'schema_type', 'breadcrumb_title_en', 'breadcrumb_title_bn',
            'sitemap_include', 'sitemap_priority', 'sitemap_change_frequency',
        ];

        foreach ($seoFields as $field) {
            if (array_key_exists($field, $data)) {
                $seoData[$field] = $data[$field];
            }
        }

        if (! empty($seoData)) {
            if ($this->seoMeta) {
                $this->seoMeta->update($seoData);
            } else {
                $this->seoMeta()->create($seoData);
            }
        }
    }
}
