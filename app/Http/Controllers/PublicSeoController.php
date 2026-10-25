<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Models\Post;
use App\Models\Product;
use App\Models\Service;
use App\Models\SiteSetting;
use Symfony\Component\HttpFoundation\Response;

class PublicSeoController extends Controller
{
    public function robots(): Response
    {
        $setting = SiteSetting::where('key', 'robots_txt')->first();

        $content = $setting ? $setting->value_en : "User-agent: *\nDisallow: /admin\n\nSitemap: ".url('/sitemap.xml');

        return response($content)->header('Content-Type', 'text/plain');
    }

    public function sitemap(): Response
    {
        $services = Service::where('status', 'published')->get();
        $products = Product::where('is_active', true)->get();
        $portfolios = Portfolio::where('status', 'published')->get();
        $posts = Post::where('status', 'published')->get();

        $staticPages = [
            ['path' => '', 'changefreq' => 'daily', 'priority' => '1.0'],
            ['path' => 'about', 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['path' => 'services', 'changefreq' => 'weekly', 'priority' => '0.8'],
            ['path' => 'products', 'changefreq' => 'weekly', 'priority' => '0.8'],
            ['path' => 'portfolio', 'changefreq' => 'weekly', 'priority' => '0.8'],
            ['path' => 'industries', 'changefreq' => 'monthly', 'priority' => '0.7'],
            ['path' => 'managed-it', 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['path' => 'blog', 'changefreq' => 'weekly', 'priority' => '0.7'],
            ['path' => 'contact', 'changefreq' => 'monthly', 'priority' => '0.8'],
        ];

        return response()->view('sitemap', [
            'staticPages' => $staticPages,
            'services' => $services,
            'products' => $products,
            'portfolios' => $portfolios,
            'posts' => $posts,
        ])->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
