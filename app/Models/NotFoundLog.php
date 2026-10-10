<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotFoundLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'url',
        'hits',
        'last_hit_at',
    ];

    protected $casts = [
        'last_hit_at' => 'datetime',
    ];
}
