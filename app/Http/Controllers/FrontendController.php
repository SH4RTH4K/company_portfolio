<?php

namespace App\Http\Controllers;

use App\Models\AmcPackage;
use App\Models\BusinessDivision;
use App\Models\Client;
use App\Models\Faq;
use App\Models\HomepageHero;
use App\Models\Industry;
use App\Models\Partner;
use App\Models\Portfolio;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\Service;
use App\Models\Team;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class FrontendController extends Controller
{
    protected function setLocale($locale)
    {
        if (! in_array($locale, ['en', 'bn'])) {
            abort(404);
        }
        App::setLocale($locale);

        return $locale;
    }

    public function home($locale = 'en')
    {
        $locale = $this->setLocale($locale);

        $homepageHeroes = HomepageHero::query()->ordered()->get();
        $divisions = BusinessDivision::where('is_active', true)->orderBy('display_order')->get();
        $featuredServices = Service::where('status', 'published')->where('is_active', true)->where('is_featured', true)->orderBy('display_order')->take(6)->get();
        $featuredProducts = Product::where('is_active', true)->where('is_featured', true)->with(['category', 'brand'])->orderBy('display_order')->take(4)->get();
        $featuredPortfolios = Portfolio::where('status', 'published')->where('is_featured', true)->with('industry')->orderBy('display_order')->take(3)->get();
        $amcPackages = AmcPackage::where('is_active', true)->orderBy('display_order')->get();
        $clients = Client::where('is_active', true)->orderBy('display_order')->take(12)->get();
        $testimonials = Testimonial::where('is_active', true)->orderBy('display_order')->take(6)->get();
        $partners = Partner::where('is_active', true)->orderBy('display_order')->get();
        $latestPosts = Post::where('status', 'published')->with('category')->orderBy('published_at', 'desc')->take(3)->get();

        return view('frontend.home', compact(
            'locale', 'homepageHeroes', 'divisions', 'featuredServices', 'featuredProducts',
            'featuredPortfolios', 'amcPackages', 'clients', 'testimonials',
            'partners', 'latestPosts'
        ));
    }

    public function about($locale = 'en')
    {
        $locale = $this->setLocale($locale);
        $teams = Team::where('is_active', true)->orderBy('display_order')->get();
        $divisions = BusinessDivision::where('is_active', true)->orderBy('display_order')->get();
        $partners = Partner::where('is_active', true)->orderBy('display_order')->get();

        return view('frontend.about', compact('locale', 'teams', 'divisions', 'partners'));
    }

    public function services($locale, Request $request)
    {
        $locale = $this->setLocale($locale);
        $divisions = BusinessDivision::where('is_active', true)->with(['services' => function ($q) {
            $q->where('status', 'published')->where('is_active', true)->orderBy('display_order');
        }])->orderBy('display_order')->get();

        $query = Service::where('status', 'published')->where('is_active', true)->with('businessDivision');
        if ($request->has('division')) {
            $query->whereHas('businessDivision', function ($q) use ($request) {
                $q->where('code', $request->division)->orWhere('id', $request->division);
            });
        }
        $services = $query->orderBy('display_order')->paginate(9);

        return view('frontend.services.index', compact('locale', 'divisions', 'services'));
    }

    public function serviceDetail($locale, $slug)
    {
        $locale = $this->setLocale($locale);
        $service = Service::where('slug_en', $slug)
            ->orWhere('slug_bn', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        $relatedServices = Service::where('business_division_id', $service->business_division_id)
            ->where('id', '!=', $service->id)
            ->where('status', 'published')
            ->take(3)
            ->get();

        // FAQs: service-specific first, then category-matched, then General
        $faqs = Faq::where('is_active', true)
            ->where(function ($q) use ($service) {
                $q->where('service_id', $service->id)
                    ->orWhereNull('service_id');
            })
            ->orderByRaw("CASE WHEN service_id = {$service->id} THEN 0 ELSE 1 END")
            ->orderBy('display_order')
            ->take(8)
            ->get();

        return view('frontend.services.show', compact('locale', 'service', 'relatedServices', 'faqs'));
    }

    public function products($locale, Request $request)
    {
        $locale = $this->setLocale($locale);
        $categories = ProductCategory::where('is_active', true)->orderBy('display_order')->get();
        $brands = ProductBrand::where('is_active', true)->orderBy('display_order')->get();

        $query = Product::where('is_active', true)->with(['category', 'brand']);
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }
        if ($request->filled('brand')) {
            $query->where('brand_id', $request->brand);
        }
        $products = $query->orderBy('display_order')->paginate(12);

        return view('frontend.products.index', compact('locale', 'categories', 'brands', 'products'));
    }

    public function productDetail($locale, $slug)
    {
        $locale = $this->setLocale($locale);
        $product = Product::where('slug_en', $slug)
            ->orWhere('slug_bn', $slug)
            ->where('is_active', true)
            ->with(['category', 'brand'])
            ->firstOrFail();

        $relatedProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->take(4)
            ->get();

        return view('frontend.products.show', compact('locale', 'product', 'relatedProducts'));
    }

    public function portfolio($locale, Request $request)
    {
        $locale = $this->setLocale($locale);
        $industries = Industry::where('is_active', true)->orderBy('display_order')->get();

        $query = Portfolio::where('status', 'published')->with('industry');
        if ($request->filled('industry')) {
            $query->where('industry_id', $request->industry);
        }
        $portfolios = $query->orderBy('display_order')->paginate(9);

        return view('frontend.portfolio.index', compact('locale', 'industries', 'portfolios'));
    }

    public function portfolioDetail($locale, $slug)
    {
        $locale = $this->setLocale($locale);
        $portfolio = Portfolio::where('slug_en', $slug)
            ->orWhere('slug_bn', $slug)
            ->where('status', 'published')
            ->with('industry')
            ->firstOrFail();

        return view('frontend.portfolio.show', compact('locale', 'portfolio'));
    }

    public function industries($locale = 'en')
    {
        $locale = $this->setLocale($locale);
        $industries = Industry::where('is_active', true)->orderBy('display_order')->get();

        return view('frontend.industries.index', compact('locale', 'industries'));
    }

    public function managedIt($locale = 'en')
    {
        $locale = $this->setLocale($locale);
        $amcPackages = AmcPackage::where('is_active', true)->orderBy('display_order')->get();
        $testimonials = Testimonial::where('is_active', true)->take(3)->get();
        $faqs = Faq::where('is_active', true)
            ->where(function ($q) {
                $q->whereIn('category', ['AMC', 'Managed IT', 'General'])
                    ->orWhereNull('category');
            })
            ->orderBy('display_order')
            ->take(8)
            ->get();

        return view('frontend.managed_it', compact('locale', 'amcPackages', 'testimonials', 'faqs'));
    }

    public function blog($locale, Request $request)
    {
        $locale = $this->setLocale($locale);
        $categories = PostCategory::where('is_active', true)->orderBy('display_order')->get();

        $query = Post::where('status', 'published')->with(['category', 'author']);
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('title_en', 'like', "%{$search}%")
                    ->orWhere('title_bn', 'like', "%{$search}%")
                    ->orWhere('content_en', 'like', "%{$search}%")
                    ->orWhere('content_bn', 'like', "%{$search}%");
            });
        }
        $posts = $query->orderBy('published_at', 'desc')->paginate(6);

        return view('frontend.blog.index', compact('locale', 'categories', 'posts'));
    }

    public function blogDetail($locale, $slug)
    {
        $locale = $this->setLocale($locale);
        $post = Post::where('slug_en', $slug)
            ->orWhere('slug_bn', $slug)
            ->where('status', 'published')
            ->with(['category', 'author'])
            ->firstOrFail();

        $recentPosts = Post::where('status', 'published')
            ->where('id', '!=', $post->id)
            ->orderBy('published_at', 'desc')
            ->take(4)
            ->get();

        return view('frontend.blog.show', compact('locale', 'post', 'recentPosts'));
    }
}
