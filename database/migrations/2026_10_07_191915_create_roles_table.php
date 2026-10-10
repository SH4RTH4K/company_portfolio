<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('roles')->insert([
            [
                'name' => 'Super Administrator',
                'slug' => 'super_admin',
                'description' => 'Full access to every admin feature.',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Administrator',
                'slug' => 'admin',
                'description' => 'General administration access.',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 20,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Content Manager',
                'slug' => 'content_manager',
                'description' => 'Manages services, products, pages, and blog content.',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'SEO Manager',
                'slug' => 'seo_manager',
                'description' => 'Manages SEO tools, robots, sitemap, scripts, and redirects.',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 40,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Lead CRM Manager',
                'slug' => 'lead_manager',
                'description' => 'Manages leads and customer inquiries.',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 50,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Read-only Viewer',
                'slug' => 'viewer',
                'description' => 'Read-only admin access.',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 60,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
