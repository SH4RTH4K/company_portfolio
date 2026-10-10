<?php

namespace App\Models;

use App\Traits\HasSeoMeta;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Portfolio extends Model
{
    use HasFactory, HasSeoMeta, SoftDeletes;

    protected $fillable = [
        'project_name_en',
        'project_name_bn',
        'slug_en',
        'slug_bn',
        'client_name',
        'client_logo',
        'industry_id',
        'project_category_id',
        'project_date',
        'project_url',
        'short_description_en',
        'short_description_bn',
        'challenge_en',
        'challenge_bn',
        'solution_en',
        'solution_bn',
        'results_en',
        'results_bn',
        'technology_used',
        'featured_image',
        'gallery',
        'testimonial_en',
        'testimonial_bn',
        'is_featured',
        'status',
        'display_order',
        'published_at',
    ];

    protected $casts = [
        'gallery' => 'array',
        'project_date' => 'date',
        'published_at' => 'datetime',
        'is_featured' => 'boolean',
    ];

    public function industry()
    {
        return $this->belongsTo(Industry::class);
    }

    public function projectCategory()
    {
        return $this->belongsTo(ProjectCategory::class, 'project_category_id');
    }
}
