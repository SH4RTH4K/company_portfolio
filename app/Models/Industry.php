<?php

namespace App\Models;

use App\Traits\HasSeoMeta;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Industry extends Model
{
    use HasFactory, HasSeoMeta, SoftDeletes;

    protected $fillable = [
        'title_en',
        'title_bn',
        'slug_en',
        'slug_bn',
        'short_description_en',
        'short_description_bn',
        'description_en',
        'description_bn',
        'icon',
        'featured_image',
        'banner_image',
        'display_order',
        'is_active',
    ];
}
