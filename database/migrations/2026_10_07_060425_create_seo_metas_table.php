<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('seo_metas', function (Blueprint $table) {
            $table->id();

            $table->morphs('seoable');
            $table->string('seo_title_en')->nullable();
            $table->string('seo_title_bn')->nullable();
            $table->text('seo_description_en')->nullable();
            $table->text('seo_description_bn')->nullable();
            $table->text('seo_keywords_en')->nullable();
            $table->text('seo_keywords_bn')->nullable();
            $table->string('focus_keyword_en')->nullable();
            $table->string('focus_keyword_bn')->nullable();
            $table->string('canonical_url_en')->nullable();
            $table->string('canonical_url_bn')->nullable();
            $table->string('og_title_en')->nullable();
            $table->string('og_title_bn')->nullable();
            $table->text('og_description_en')->nullable();
            $table->text('og_description_bn')->nullable();
            $table->string('og_image')->nullable();
            $table->string('twitter_title_en')->nullable();
            $table->string('twitter_title_bn')->nullable();
            $table->text('twitter_description_en')->nullable();
            $table->text('twitter_description_bn')->nullable();
            $table->string('twitter_image')->nullable();
            $table->string('twitter_card')->nullable();
            $table->boolean('robots_index')->default(true);
            $table->boolean('robots_follow')->default(true);
            $table->boolean('robots_noarchive')->default(false);
            $table->boolean('robots_nosnippet')->default(false);
            $table->boolean('robots_noimageindex')->default(false);
            $table->string('schema_type')->nullable();
            $table->json('custom_schema_json')->nullable();
            $table->string('breadcrumb_title_en')->nullable();
            $table->string('breadcrumb_title_bn')->nullable();
            $table->boolean('sitemap_include')->default(true);
            $table->string('sitemap_priority')->default('0.5');
            $table->string('sitemap_change_frequency')->default('weekly');
            $table->integer('seo_score_en')->nullable();
            $table->integer('seo_score_bn')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_metas');
    }
};
