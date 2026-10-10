<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmcPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_en',
        'name_bn',
        'description_en',
        'description_bn',
        'price',
        'price_text_en',
        'price_text_bn',
        'billing_cycle',
        'features_en',
        'features_bn',
        'is_recommended',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'features_en' => 'array',
        'features_bn' => 'array',
        'is_recommended' => 'boolean',
        'is_active' => 'boolean',
    ];
}
