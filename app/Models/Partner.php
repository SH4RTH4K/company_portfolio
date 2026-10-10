<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'logo',
        'website',
        'type',
        'official_partner',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'official_partner' => 'boolean',
        'is_active' => 'boolean',
    ];
}
