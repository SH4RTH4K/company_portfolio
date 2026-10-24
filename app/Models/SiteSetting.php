<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    use HasFactory;

    public const array NAVIGATION_ITEMS = [
        'home' => 'Home',
        'about' => 'About Us',
        'services' => 'Services',
        'managed_it' => 'Managed IT / AMC',
        'products' => 'Products',
        'portfolio' => 'Portfolio',
        'industries' => 'Industries',
        'blog' => 'Insights',
        'contact' => 'Contact / Get a Quote',
    ];

    protected $fillable = [
        'key',
        'value_en',
        'value_bn',
    ];
}
