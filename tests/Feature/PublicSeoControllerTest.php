<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use App\Models\Post;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSeoControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_returns_valid_xml_for_public_pages_and_published_content(): void
    {
        Service::withoutTimestamps(function (): void {
            Service::create([
                'title_en' => 'Public service',
                'slug_en' => 'public-service',
                'status' => 'published',
            ]);
        });
        Product::create([
            'product_name_en' => 'Public product',
            'slug_en' => 'public-product',
            'is_active' => true,
        ]);
        Portfolio::create([
            'project_name_en' => 'Public project',
            'slug_en' => 'public-project',
            'status' => 'published',
        ]);
        Post::create([
            'title_en' => 'Public post',
            'slug_en' => 'public-post',
            'status' => 'published',
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=utf-8')
            ->assertSee('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"', false)
            ->assertSee(url('/en/about'), false)
            ->assertSee(url('/bn/about'), false)
            ->assertSee(url('/en/services/public-service'), false)
            ->assertSee(url('/en/products/public-product'), false)
            ->assertSee(url('/en/portfolio/public-project'), false)
            ->assertSee(url('/en/blog/public-post'), false);

        $this->assertInstanceOf(\SimpleXMLElement::class, simplexml_load_string($response->getContent()));
    }
}
