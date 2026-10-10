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
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->string('product_name_en');
            $table->string('product_name_bn')->nullable();
            $table->string('slug_en')->unique();
            $table->string('slug_bn')->unique()->nullable();
            $table->string('sku')->nullable();
            $table->foreignId('brand_id')->nullable()->constrained('product_brands')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->text('short_description_en')->nullable();
            $table->text('short_description_bn')->nullable();
            $table->longText('description_en')->nullable();
            $table->longText('description_bn')->nullable();
            $table->json('specifications')->nullable();
            $table->string('featured_image')->nullable();
            $table->json('gallery')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->boolean('show_price')->default(true);
            $table->string('availability')->nullable();
            $table->string('external_buy_url')->nullable();
            $table->boolean('request_quote')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
