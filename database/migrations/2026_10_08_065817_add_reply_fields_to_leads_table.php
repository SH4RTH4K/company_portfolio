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
        Schema::table('leads', function (Blueprint $table) {
            $table->string('reply_subject')->nullable()->after('status');
            $table->text('reply_message')->nullable()->after('reply_subject');
            $table->foreignId('replied_by')->nullable()->after('reply_message')->constrained('users')->nullOnDelete();
            $table->timestamp('replied_at')->nullable()->after('replied_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['replied_by']);
            $table->dropColumn(['reply_subject', 'reply_message', 'replied_by', 'replied_at']);
        });
    }
};
