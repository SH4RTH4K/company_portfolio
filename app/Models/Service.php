<?php

namespace App\Models;

use App\Traits\HasSeoMeta;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, HasSeoMeta, SoftDeletes;

    protected $fillable = [
        'business_division_id',
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
        'features_en',
        'features_bn',
        'benefits_en',
        'benefits_bn',
        'process_en',
        'process_bn',
        'is_featured',
        'is_active',
        'display_order',
        'status',
        'published_at',
    ];

    protected $casts = [
        'features_en' => 'array',
        'features_bn' => 'array',
        'benefits_en' => 'array',
        'benefits_bn' => 'array',
        'process_en' => 'array',
        'process_bn' => 'array',
        'published_at' => 'datetime',
    ];

    public function businessDivision()
    {
        return $this->belongsTo(BusinessDivision::class);
    }
}
