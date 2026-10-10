<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Product;
use App\Models\Service;
use App\Models\SiteSetting;

class PublicSeoController extends Controller
{
    public function robots()
    {
        $setting = SiteSetting::where('key', 'robots_txt')->first();

        $content = $setting ? $setting->value_en : "User-agent: *\nDisallow: /admin\n\nSitemap: ".url('/sitemap.xml');

        return response($content)->header('Content-Type', 'text/plain');
    }

    public function sitemap()
    {
        $services = Service::where('status', 'published')->get();
        $products = Product::where('is_active', true)->get();
        $posts = Post::where('status', 'published')->get();

        return response()->view('sitemap', [
            'services' => $services,
            'products' => $products,
            'posts' => $posts,
        ])->header('Content-Type', 'text/xml');
    }
}
