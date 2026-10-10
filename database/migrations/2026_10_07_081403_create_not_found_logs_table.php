<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('not_found_logs', function (Blueprint $table) {
            $table->id();
            $table->string('url', 768)->index();
            $table->integer('hits')->default(1);
            $table->timestamp('last_hit_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('not_found_logs');
    }
};
