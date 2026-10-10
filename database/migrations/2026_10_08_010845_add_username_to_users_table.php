<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable()->unique()->after('name');
        });

        $usedUsernames = [];

        DB::table('users')
            ->select(['id', 'name', 'email'])
            ->orderBy('id')
            ->each(function (object $user) use (&$usedUsernames): void {
                $source = Str::before((string) $user->email, '@') ?: (string) $user->name;
                $baseUsername = Str::of($source)
                    ->lower()
                    ->replaceMatches('/[^a-z0-9_]+/', '_')
                    ->trim('_')
                    ->limit(40, '')
                    ->toString();

                if ($baseUsername === '') {
                    $baseUsername = 'user';
                }

                $username = $baseUsername;
                $suffix = 2;

                while (in_array($username, $usedUsernames, true) || DB::table('users')->where('username', $username)->exists()) {
                    $username = Str::limit($baseUsername, 44, '').'_'.$suffix;
                    $suffix++;
                }

                $usedUsernames[] = $username;

                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['username' => $username]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
