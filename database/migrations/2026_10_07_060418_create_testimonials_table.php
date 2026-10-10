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
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('designation_en')->nullable();
            $table->string('designation_bn')->nullable();
            $table->string('company')->nullable();
            $table->string('photo')->nullable();
            $table->string('company_logo')->nullable();
            $table->text('testimonial_en');
            $table->text('testimonial_bn')->nullable();
            $table->integer('rating')->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
