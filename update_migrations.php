<?php

// Script to populate migrations with correct schemas.
$migrationsDir = __DIR__.'/database/migrations';
$files = scandir($migrationsDir);

function updateMigration($files, $tablePattern, $columnsCallback, $migrationsDir)
{
    $matchedFile = null;
    foreach ($files as $file) {
        if (strpos($file, $tablePattern) !== false) {
            $matchedFile = $file;
            break;
        }
    }
    if (! $matchedFile) {
        return;
    }

    $filePath = $migrationsDir.'/'.$matchedFile;
    $content = file_get_contents($filePath);

    // Replace the up method contents
    $replacement = "public function up(): void\n    {\n        Schema::create('$tablePattern', function (Blueprint \$table) {\n            \$table->id();\n$columnsCallback            \$table->timestamps();\n        });\n    }";

    $content = preg_replace('/public function up\(\): void\s*\{\s*Schema::create\(\'[^\']+\', function \(Blueprint \$table\) \{\s*\$table->id\(\);\s*\$table->timestamps\(\);\s*\}\);\s*\}/s', $replacement, $content);

    file_put_contents($filePath, $content);
}

// 1. business_divisions
updateMigration($files, 'business_divisions', "
            \$table->string('name_en');
            \$table->string('name_bn')->nullable();
            \$table->string('slug_en')->unique();
            \$table->string('slug_bn')->unique()->nullable();
            \$table->text('short_description_en')->nullable();
            \$table->text('short_description_bn')->nullable();
            \$table->longText('description_en')->nullable();
            \$table->longText('description_bn')->nullable();
            \$table->string('icon')->nullable();
            \$table->string('logo')->nullable();
            \$table->string('featured_image')->nullable();
            \$table->string('banner_image')->nullable();
            \$table->integer('display_order')->default(0);
            \$table->boolean('is_active')->default(true);
            \$table->softDeletes();
", $migrationsDir);

// 2. services
updateMigration($files, 'services', "
            \$table->foreignId('business_division_id')->nullable()->constrained('business_divisions')->nullOnDelete();
            \$table->string('title_en');
            \$table->string('title_bn')->nullable();
            \$table->string('slug_en')->unique();
            \$table->string('slug_bn')->unique()->nullable();
            \$table->text('short_description_en')->nullable();
            \$table->text('short_description_bn')->nullable();
            \$table->longText('description_en')->nullable();
            \$table->longText('description_bn')->nullable();
            \$table->string('icon')->nullable();
            \$table->string('featured_image')->nullable();
            \$table->string('banner_image')->nullable();
            \$table->json('features_en')->nullable();
            \$table->json('features_bn')->nullable();
            \$table->json('benefits_en')->nullable();
            \$table->json('benefits_bn')->nullable();
            \$table->json('process_en')->nullable();
            \$table->json('process_bn')->nullable();
            \$table->boolean('is_featured')->default(false);
            \$table->boolean('is_active')->default(true);
            \$table->integer('display_order')->default(0);
            \$table->string('status')->default('published');
            \$table->timestamp('published_at')->nullable();
            \$table->softDeletes();
", $migrationsDir);

// 3. project_categories
updateMigration($files, 'project_categories', "
            \$table->string('name_en');
            \$table->string('name_bn')->nullable();
            \$table->string('slug_en')->unique();
            \$table->string('slug_bn')->unique()->nullable();
            \$table->integer('display_order')->default(0);
            \$table->boolean('is_active')->default(true);
            \$table->softDeletes();
", $migrationsDir);

// 4. industries
updateMigration($files, 'industries', "
            \$table->string('title_en');
            \$table->string('title_bn')->nullable();
            \$table->string('slug_en')->unique();
            \$table->string('slug_bn')->unique()->nullable();
            \$table->text('short_description_en')->nullable();
            \$table->text('short_description_bn')->nullable();
            \$table->longText('description_en')->nullable();
            \$table->longText('description_bn')->nullable();
            \$table->string('icon')->nullable();
            \$table->string('featured_image')->nullable();
            \$table->string('banner_image')->nullable();
            \$table->integer('display_order')->default(0);
            \$table->boolean('is_active')->default(true);
            \$table->softDeletes();
", $migrationsDir);

// 5. portfolios
updateMigration($files, 'portfolios', "
            \$table->string('project_name_en');
            \$table->string('project_name_bn')->nullable();
            \$table->string('slug_en')->unique();
            \$table->string('slug_bn')->unique()->nullable();
            \$table->string('client_name')->nullable();
            \$table->string('client_logo')->nullable();
            \$table->foreignId('industry_id')->nullable()->constrained('industries')->nullOnDelete();
            \$table->foreignId('project_category_id')->nullable()->constrained('project_categories')->nullOnDelete();
            \$table->date('project_date')->nullable();
            \$table->string('project_url')->nullable();
            \$table->text('short_description_en')->nullable();
            \$table->text('short_description_bn')->nullable();
            \$table->longText('challenge_en')->nullable();
            \$table->longText('challenge_bn')->nullable();
            \$table->longText('solution_en')->nullable();
            \$table->longText('solution_bn')->nullable();
            \$table->longText('results_en')->nullable();
            \$table->longText('results_bn')->nullable();
            \$table->string('technology_used')->nullable();
            \$table->string('featured_image')->nullable();
            \$table->json('gallery')->nullable();
            \$table->text('testimonial_en')->nullable();
            \$table->text('testimonial_bn')->nullable();
            \$table->boolean('is_featured')->default(false);
            \$table->string('status')->default('published');
            \$table->integer('display_order')->default(0);
            \$table->timestamp('published_at')->nullable();
            \$table->softDeletes();
", $migrationsDir);

// 6. product_categories
updateMigration($files, 'product_categories', "
            \$table->string('name_en');
            \$table->string('name_bn')->nullable();
            \$table->string('slug_en')->unique();
            \$table->string('slug_bn')->unique()->nullable();
            \$table->integer('display_order')->default(0);
            \$table->boolean('is_active')->default(true);
            \$table->softDeletes();
", $migrationsDir);

// 7. product_brands
updateMigration($files, 'product_brands', "
            \$table->string('name_en');
            \$table->string('name_bn')->nullable();
            \$table->string('slug_en')->unique();
            \$table->string('slug_bn')->unique()->nullable();
            \$table->string('logo')->nullable();
            \$table->integer('display_order')->default(0);
            \$table->boolean('is_active')->default(true);
            \$table->softDeletes();
", $migrationsDir);

// 8. products
updateMigration($files, 'products', "
            \$table->string('product_name_en');
            \$table->string('product_name_bn')->nullable();
            \$table->string('slug_en')->unique();
            \$table->string('slug_bn')->unique()->nullable();
            \$table->string('sku')->nullable();
            \$table->foreignId('brand_id')->nullable()->constrained('product_brands')->nullOnDelete();
            \$table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            \$table->text('short_description_en')->nullable();
            \$table->text('short_description_bn')->nullable();
            \$table->longText('description_en')->nullable();
            \$table->longText('description_bn')->nullable();
            \$table->json('specifications')->nullable();
            \$table->string('featured_image')->nullable();
            \$table->json('gallery')->nullable();
            \$table->decimal('price', 10, 2)->nullable();
            \$table->boolean('show_price')->default(true);
            \$table->string('availability')->nullable();
            \$table->string('external_buy_url')->nullable();
            \$table->boolean('request_quote')->default(false);
            \$table->boolean('is_featured')->default(false);
            \$table->boolean('is_active')->default(true);
            \$table->integer('display_order')->default(0);
            \$table->softDeletes();
", $migrationsDir);

// 9. amc_packages
updateMigration($files, 'amc_packages', "
            \$table->string('name_en');
            \$table->string('name_bn')->nullable();
            \$table->text('description_en')->nullable();
            \$table->text('description_bn')->nullable();
            \$table->decimal('price', 10, 2)->nullable();
            \$table->string('price_text_en')->nullable();
            \$table->string('price_text_bn')->nullable();
            \$table->string('billing_cycle')->nullable();
            \$table->json('features_en')->nullable();
            \$table->json('features_bn')->nullable();
            \$table->boolean('is_recommended')->default(false);
            \$table->integer('display_order')->default(0);
            \$table->boolean('is_active')->default(true);
", $migrationsDir);

// 10. clients
updateMigration($files, 'clients', "
            \$table->string('name');
            \$table->string('logo')->nullable();
            \$table->string('website')->nullable();
            \$table->text('description_en')->nullable();
            \$table->text('description_bn')->nullable();
            \$table->integer('display_order')->default(0);
            \$table->boolean('is_active')->default(true);
", $migrationsDir);

// 11. testimonials
updateMigration($files, 'testimonials', "
            \$table->string('name');
            \$table->string('designation_en')->nullable();
            \$table->string('designation_bn')->nullable();
            \$table->string('company')->nullable();
            \$table->string('photo')->nullable();
            \$table->string('company_logo')->nullable();
            \$table->text('testimonial_en');
            \$table->text('testimonial_bn')->nullable();
            \$table->integer('rating')->nullable();
            \$table->integer('display_order')->default(0);
            \$table->boolean('is_active')->default(true);
", $migrationsDir);

// 12. partners
updateMigration($files, 'partners', "
            \$table->string('name');
            \$table->string('logo')->nullable();
            \$table->string('website')->nullable();
            \$table->string('type')->nullable(); // Technology, Brand, Partner
            \$table->boolean('official_partner')->default(false);
            \$table->integer('display_order')->default(0);
            \$table->boolean('is_active')->default(true);
", $migrationsDir);

// 13. teams
updateMigration($files, 'teams', "
            \$table->string('name');
            \$table->string('designation_en')->nullable();
            \$table->string('designation_bn')->nullable();
            \$table->text('bio_en')->nullable();
            \$table->text('bio_bn')->nullable();
            \$table->string('photo')->nullable();
            \$table->string('email')->nullable();
            \$table->string('linkedin')->nullable();
            \$table->integer('display_order')->default(0);
            \$table->boolean('is_active')->default(true);
", $migrationsDir);

// 14. post_categories
updateMigration($files, 'post_categories', "
            \$table->string('name_en');
            \$table->string('name_bn')->nullable();
            \$table->string('slug_en')->unique();
            \$table->string('slug_bn')->unique()->nullable();
            \$table->integer('display_order')->default(0);
            \$table->boolean('is_active')->default(true);
            \$table->softDeletes();
", $migrationsDir);

// 15. posts
updateMigration($files, 'posts', "
            \$table->string('title_en');
            \$table->string('title_bn')->nullable();
            \$table->string('slug_en')->unique();
            \$table->string('slug_bn')->unique()->nullable();
            \$table->text('excerpt_en')->nullable();
            \$table->text('excerpt_bn')->nullable();
            \$table->longText('content_en')->nullable();
            \$table->longText('content_bn')->nullable();
            \$table->string('featured_image')->nullable();
            \$table->foreignId('category_id')->nullable()->constrained('post_categories')->nullOnDelete();
            \$table->string('tags')->nullable();
            \$table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            \$table->string('status')->default('draft');
            \$table->timestamp('published_at')->nullable();
            \$table->softDeletes();
", $migrationsDir);

// 16. leads
updateMigration($files, 'leads', "
            \$table->string('full_name');
            \$table->string('company_name')->nullable();
            \$table->string('phone')->nullable();
            \$table->string('email')->nullable();
            \$table->string('interested_service')->nullable();
            \$table->string('budget_range')->nullable();
            \$table->string('subject')->nullable();
            \$table->text('message')->nullable();
            \$table->string('language')->nullable();
            \$table->string('lead_source')->nullable();
            \$table->string('landing_page')->nullable();
            \$table->string('referrer')->nullable();
            \$table->string('utm_source')->nullable();
            \$table->string('utm_medium')->nullable();
            \$table->string('utm_campaign')->nullable();
            \$table->string('utm_term')->nullable();
            \$table->string('utm_content')->nullable();
            \$table->string('status')->default('New');
", $migrationsDir);

// 17. site_settings
updateMigration($files, 'site_settings', "
            \$table->string('key')->unique();
            \$table->text('value_en')->nullable();
            \$table->text('value_bn')->nullable();
", $migrationsDir);

// 18. seo_metas
updateMigration($files, 'seo_metas', "
            \$table->morphs('seoable');
            \$table->string('seo_title_en')->nullable();
            \$table->string('seo_title_bn')->nullable();
            \$table->text('seo_description_en')->nullable();
            \$table->text('seo_description_bn')->nullable();
            \$table->text('seo_keywords_en')->nullable();
            \$table->text('seo_keywords_bn')->nullable();
            \$table->string('focus_keyword_en')->nullable();
            \$table->string('focus_keyword_bn')->nullable();
            \$table->string('canonical_url_en')->nullable();
            \$table->string('canonical_url_bn')->nullable();
            \$table->string('og_title_en')->nullable();
            \$table->string('og_title_bn')->nullable();
            \$table->text('og_description_en')->nullable();
            \$table->text('og_description_bn')->nullable();
            \$table->string('og_image')->nullable();
            \$table->string('twitter_title_en')->nullable();
            \$table->string('twitter_title_bn')->nullable();
            \$table->text('twitter_description_en')->nullable();
            \$table->text('twitter_description_bn')->nullable();
            \$table->string('twitter_image')->nullable();
            \$table->string('twitter_card')->nullable();
            \$table->boolean('robots_index')->default(true);
            \$table->boolean('robots_follow')->default(true);
            \$table->boolean('robots_noarchive')->default(false);
            \$table->boolean('robots_nosnippet')->default(false);
            \$table->boolean('robots_noimageindex')->default(false);
            \$table->string('schema_type')->nullable();
            \$table->json('custom_schema_json')->nullable();
            \$table->string('breadcrumb_title_en')->nullable();
            \$table->string('breadcrumb_title_bn')->nullable();
            \$table->boolean('sitemap_include')->default(true);
            \$table->string('sitemap_priority')->default('0.5');
            \$table->string('sitemap_change_frequency')->default('weekly');
            \$table->integer('seo_score_en')->nullable();
            \$table->integer('seo_score_bn')->nullable();
", $migrationsDir);

echo "Migrations populated successfully.\n";
