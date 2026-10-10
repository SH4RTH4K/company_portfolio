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
        Schema::create('services', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_division_id')->nullable()->constrained('business_divisions')->nullOnDelete();
            $table->string('title_en');
            $table->string('title_bn')->nullable();
            $table->string('slug_en')->unique();
            $table->string('slug_bn')->unique()->nullable();
            $table->text('short_description_en')->nullable();
            $table->text('short_description_bn')->nullable();
            $table->longText('description_en')->nullable();
            $table->longText('description_bn')->nullable();
            $table->string('icon')->nullable();
            $table->string('featured_image')->nullable();
            $table->string('banner_image')->nullable();
            $table->json('features_en')->nullable();
            $table->json('features_bn')->nullable();
            $table->json('benefits_en')->nullable();
            $table->json('benefits_bn')->nullable();
            $table->json('process_en')->nullable();
            $table->json('process_bn')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->string('status')->default('published');
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
        Schema::dropIfExists('services');
    }
};
