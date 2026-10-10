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
        Schema::create('portfolios', function (Blueprint $table) {
            $table->id();

            $table->string('project_name_en');
            $table->string('project_name_bn')->nullable();
            $table->string('slug_en')->unique();
            $table->string('slug_bn')->unique()->nullable();
            $table->string('client_name')->nullable();
            $table->string('client_logo')->nullable();
            $table->foreignId('industry_id')->nullable()->constrained('industries')->nullOnDelete();
            $table->foreignId('project_category_id')->nullable()->constrained('project_categories')->nullOnDelete();
            $table->date('project_date')->nullable();
            $table->string('project_url')->nullable();
            $table->text('short_description_en')->nullable();
            $table->text('short_description_bn')->nullable();
            $table->longText('challenge_en')->nullable();
            $table->longText('challenge_bn')->nullable();
            $table->longText('solution_en')->nullable();
            $table->longText('solution_bn')->nullable();
            $table->longText('results_en')->nullable();
            $table->longText('results_bn')->nullable();
            $table->string('technology_used')->nullable();
            $table->string('featured_image')->nullable();
            $table->json('gallery')->nullable();
            $table->text('testimonial_en')->nullable();
            $table->text('testimonial_bn')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->string('status')->default('published');
            $table->integer('display_order')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portfolios');
    }
};
