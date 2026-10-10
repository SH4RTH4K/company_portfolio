<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'designation_en',
        'designation_bn',
        'company',
        'photo',
        'company_logo',
        'testimonial_en',
        'testimonial_bn',
        'rating',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
        'rating' => 'integer',
    ];
}
