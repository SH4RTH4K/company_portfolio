<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Role extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_system',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * @return array<string, string>
     */
    public static function options(bool $activeOnly = true): array
    {
        if (! Schema::hasTable('roles')) {
            return self::defaultOptions();
        }

        $query = self::query()
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        $roles = $query->pluck('name', 'slug')->all();

        return $roles ?: self::defaultOptions();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role', 'slug');
    }

    /**
     * @return array<string, string>
     */
    private static function defaultOptions(): array
    {
        return [
            'super_admin' => 'Super Administrator',
            'admin' => 'Administrator',
            'content_manager' => 'Content Manager',
            'seo_manager' => 'SEO Manager',
            'lead_manager' => 'Lead CRM Manager',
            'viewer' => 'Read-only Viewer',
        ];
    }
}
