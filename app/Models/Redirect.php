<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    use HasFactory;

    protected $fillable = [
        'old_url',
        'new_url',
        'status_code',
        'hits',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
