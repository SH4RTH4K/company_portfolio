<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_bn',
        'designation_en',
        'designation_bn',
        'bio_en',
        'bio_bn',
        'photo',
        'email',
        'linkedin',
        'facebook',
        'twitter',
        'website',
        'github',
        'instagram',
        'youtube',
        'whatsapp',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
