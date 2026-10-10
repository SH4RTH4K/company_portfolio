<?php

namespace App\Models;

use App\Traits\HasSeoMeta;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, HasSeoMeta, SoftDeletes;

    protected $fillable = [
        'product_name_en',
        'product_name_bn',
        'slug_en',
        'slug_bn',
        'sku',
        'brand_id',
        'category_id',
        'short_description_en',
        'short_description_bn',
        'description_en',
        'description_bn',
        'specifications',
        'featured_image',
        'gallery',
        'price',
        'show_price',
        'availability',
        'external_buy_url',
        'request_quote',
        'is_featured',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'specifications' => 'array',
        'gallery' => 'array',
        'show_price' => 'boolean',
        'request_quote' => 'boolean',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function brand()
    {
        return $this->belongsTo(ProductBrand::class, 'brand_id');
    }
}
