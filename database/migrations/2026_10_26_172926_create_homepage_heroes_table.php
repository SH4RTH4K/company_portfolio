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
        Schema::create('homepage_heroes', function (Blueprint $table) {
            $table->id();

            $table->string('hero_badge_label_en', 60);
            $table->string('hero_badge_label_bn', 60);
            $table->string('hero_badge_text_en', 120);
            $table->string('hero_badge_text_bn', 120);
            $table->string('hero_title_prefix_en', 120);
            $table->string('hero_title_prefix_bn', 120);
            $table->string('hero_title_accent_en', 120);
            $table->string('hero_title_accent_bn', 120);
            $table->text('hero_intro_en');
            $table->text('hero_intro_bn');
            $table->string('hero_primary_button_label_en', 60);
            $table->string('hero_primary_button_label_bn', 60);
            $table->string('hero_secondary_button_label_en', 60);
            $table->string('hero_secondary_button_label_bn', 60);
            $table->string('hero_card_title_en', 120);
            $table->string('hero_card_title_bn', 120);
            $table->string('hero_card_subtitle_en', 160);
            $table->string('hero_card_subtitle_bn', 160);
            $table->string('hero_stat_1_value_en', 30);
            $table->string('hero_stat_1_value_bn', 30);
            $table->string('hero_stat_1_label_en', 60);
            $table->string('hero_stat_1_label_bn', 60);
            $table->string('hero_stat_2_value_en', 30);
            $table->string('hero_stat_2_value_bn', 30);
            $table->string('hero_stat_2_label_en', 60);
            $table->string('hero_stat_2_label_bn', 60);
            $table->string('hero_stat_3_value_en', 30);
            $table->string('hero_stat_3_value_bn', 30);
            $table->string('hero_stat_3_label_en', 60);
            $table->string('hero_stat_3_label_bn', 60);
            $table->unsignedSmallInteger('display_order')->default(0)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homepage_heroes');
    }
};
