<?php

namespace App\Models;

use App\Traits\HasSeoMeta;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessDivision extends Model
{
    use HasFactory, HasSeoMeta, SoftDeletes;

    protected $fillable = [
        'name_en',
        'name_bn',
        'slug_en',
        'slug_bn',
        'short_description_en',
        'short_description_bn',
        'description_en',
        'description_bn',
        'icon',
        'logo',
        'featured_image',
        'banner_image',
        'display_order',
        'is_active',
    ];

    public function services()
    {
        return $this->hasMany(Service::class, 'business_division_id');
    }
}
