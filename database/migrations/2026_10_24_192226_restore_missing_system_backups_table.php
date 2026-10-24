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
        if (Schema::hasTable('system_backups')) {
            return;
        }

        Schema::create('system_backups', function (Blueprint $table): void {
            $table->id();
            $table->string('filename')->unique();
            $table->string('type', 20);
            $table->string('status', 20)->default('creating');
            $table->string('created_by')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * Backup history is retained when this repair migration is rolled back.
     */
    public function down(): void {}
};
