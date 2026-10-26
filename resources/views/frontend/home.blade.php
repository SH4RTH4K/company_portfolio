@extends('layouts.app')

@php
    $isBn = app()->getLocale() === 'bn';
    $heroSlides = $homepageHeroes->isNotEmpty() ? $homepageHeroes : collect([null]);
    $hasHeroSlider = $heroSlides->count() > 1;
@endphp

@section('content')
<!-- 1. HERO SECTION -->
<section class="hero-banner position-relative">
    @if($hasHeroSlider)
        <div id="homepageHeroCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="6500">
            <div class="carousel-indicators mb-3">
                @foreach($heroSlides as $hero)
                    <button type="button" data-bs-target="#homepageHeroCarousel" data-bs-slide-to="{{ $loop->index }}" class="{{ $loop->first ? 'active' : '' }}" aria-current="{{ $loop->first ? 'true' : 'false' }}" aria-label="Hero slide {{ $loop->iteration }}"></button>
                @endforeach
            </div>
            <div class="carousel-inner">
                @foreach($heroSlides as $hero)
                    <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                        @include('frontend.partials.homepage-hero-slide', ['hero' => $hero])
                    </div>
                @endforeach
            </div>
            <button class="carousel-control-prev homepage-hero-control" type="button" data-bs-target="#homepageHeroCarousel" data-bs-slide="prev" aria-label="Previous hero slide">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            </button>
            <button class="carousel-control-next homepage-hero-control" type="button" data-bs-target="#homepageHeroCarousel" data-bs-slide="next" aria-label="Next hero slide">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
            </button>
        </div>
    @else
        @include('frontend.partials.homepage-hero-slide', ['hero' => $heroSlides->first()])
    @endif
</section>

<!-- 2. BUSINESS DIVISIONS -->
<section class="py-5 bg-light">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-semibold">{{ $isBn ? 'আমাদের বিভাগসমূহ' : 'Our Business Divisions' }}</span>
            <h2 class="fw-bold mt-2">{{ $isBn ? 'বিশেষায়িত প্রযুক্তি ও ব্যবসায়িক ডোমেন' : 'Specialized Enterprise Divisions' }}</h2>
            <p class="text-muted">{{ $isBn ? 'প্রতিটি ডোমেইনের জন্য ডেডিকেটেড এক্সপার্ট টিম এবং আধুনিক প্রযুক্তিগত সহায়তা' : 'Dedicated engineering teams focused on end-to-end technology transformation' }}</p>
        </div>

        <div class="row g-4">
            @foreach($divisions as $div)
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100 feature-card p-4 text-center">
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-3 d-inline-block mx-auto mb-3" style="width: 70px; height: 70px;">
                            <i class="bi bi-layers-fill fs-3"></i>
                        </div>
                        <h5 class="fw-bold mb-2">{{ $isBn && $div->name_bn ? $div->name_bn : $div->name_en }}</h5>
                        <p class="small text-muted mb-3">{{ $isBn && $div->description_bn ? $div->description_bn : $div->description_en }}</p>
                        <a href="{{ route('services.index', ['locale' => app()->getLocale(), 'division' => $div->code]) }}" class="mt-auto text-primary fw-semibold text-decoration-none small">
                            {{ $isBn ? 'সেবাসমূহ দেখুন' : 'Explore Division' }} <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- 3. FEATURED SERVICES -->
<section class="py-5">
    <div class="container py-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-5">
            <div>
                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-semibold">{{ $isBn ? 'আমাদের মূল সেবাসমূহ' : 'Core Capabilities' }}</span>
                <h2 class="fw-bold mt-2">{{ $isBn ? 'ব্যবসার প্রবৃদ্ধিতে আধুনিক আইটি সলিউশন' : 'Engineered for Scale & Security' }}</h2>
            </div>
            <a href="{{ route('services.index', ['locale' => app()->getLocale()]) }}" class="btn btn-outline-primary rounded-pill mt-3 mt-md-0">
                {{ $isBn ? 'সকল সেবা দেখুন' : 'View All Services' }} <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-4">
            @foreach($featuredServices as $service)
                <div class="col-lg-6">
                    <div class="card feature-card h-100 p-4 border-start border-primary border-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h4 class="fw-bold text-navy mb-0">
                                <a href="{{ route('services.show', ['locale' => app()->getLocale(), 'slug' => $isBn && $service->slug_bn ? $service->slug_bn : $service->slug_en]) }}" class="text-decoration-none text-dark">
                                    {{ $isBn && $service->title_bn ? $service->title_bn : $service->title_en }}
                                </a>
                            </h4>
                            <span class="badge bg-light text-primary border border-primary border-opacity-25">{{ $service->businessDivision->name_en ?? 'IT' }}</span>
                        </div>
                        <p class="text-muted mb-4">{{ $isBn && $service->short_description_bn ? $service->short_description_bn : $service->short_description_en }}</p>
                        <div class="mt-auto d-flex justify-content-between align-items-center">
                            <a href="{{ route('services.show', ['locale' => app()->getLocale(), 'slug' => $isBn && $service->slug_bn ? $service->slug_bn : $service->slug_en]) }}" class="fw-bold text-primary text-decoration-none">
                                {{ $isBn ? 'বিস্তারিত পড়ুন' : 'Learn More' }} <i class="bi bi-chevron-right"></i>
                            </a>
                            <a href="{{ route('contact', ['locale' => app()->getLocale(), 'service' => $service->title_en]) }}" class="btn btn-sm btn-outline-secondary rounded-pill">
                                {{ $isBn ? 'কোটেশন চান' : 'Get Quote' }}
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- 4. MANAGED IT / AMC PROMO SECTION -->
<section class="py-5 bg-navy text-white">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-3">24/7/365 SLA GUARANTEED</span>
                <h2 class="fw-bold text-white mb-3">
                    {{ $isBn ? 'সার্বক্ষণিক আইটি এএমসি ও অবকাঠামো রক্ষণাবেক্ষণ' : 'Never Worry About System Downtime Again' }}
                </h2>
                <p class="text-white-50 mb-4">
                    {{ $isBn ? 'জরুরি অন-সাইট সাপোর্ট, সার্ভার মনিটরিং, ব্যাকআপ যাচাইকরণ এবং নেটওয়ার্ক সুরক্ষার সমন্বয়ে আমাদের বার্ষিক রক্ষণাবেক্ষণ চুক্তি (এএমসি)।' : 'Our SLA-driven Annual Maintenance Contracts (AMC) protect your factory, bank, or corporate headquarters with fast on-site engineers, proactive patching, and automated backup testing.' }}
                </p>
                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle-fill text-accent fs-4 me-2"></i>
                            <span>{{ $isBn ? '১-ঘণ্টার জরুরি রেসপন্স' : '1-Hour Rapid Response' }}</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle-fill text-accent fs-4 me-2"></i>
                            <span>{{ $isBn ? 'নিয়মিত হার্ডওয়্যার অডিট' : 'Quarterly System Audits' }}</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle-fill text-accent fs-4 me-2"></i>
                            <span>{{ $isBn ? 'স্বয়ংক্রিয় ব্যাকআপ মনিটরিং' : 'Automated Cloud Backups' }}</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle-fill text-accent fs-4 me-2"></i>
                            <span>{{ $isBn ? 'ডেডিকেটেড আইটি ইঞ্জিনিয়ার' : 'Dedicated NOC Engineer' }}</span>
                        </div>
                    </div>
                </div>
                <a href="{{ route('managed-it', ['locale' => app()->getLocale()]) }}" class="btn btn-accent btn-lg rounded-pill px-4">
                    {{ $isBn ? 'এএমসি প্যাকেজসমূহ দেখুন' : 'Compare AMC Packages' }} <i class="bi bi-arrow-right ms-2"></i>
                </a>
            </div>

            <div class="col-lg-6">
                <div class="row g-3">
                    @foreach($amcPackages as $pkg)
                        <div class="col-md-6">
                            <div class="card bg-deep border {{ $pkg->is_recommended ? 'border-primary border-2' : 'border-secondary border-opacity-25' }} p-4 rounded-4 h-100 text-white position-relative">
                                @if($pkg->is_recommended)
                                    <span class="position-absolute top-0 end-0 translate-middle-y badge bg-primary me-3">{{ $isBn ? 'জনপ্রিয়' : 'Most Popular' }}</span>
                                @endif
                                <h5 class="fw-bold mb-2">{{ $isBn && $pkg->name_bn ? $pkg->name_bn : $pkg->name_en }}</h5>
                                <h3 class="text-accent fw-bold my-2">{{ $isBn && $pkg->price_text_bn ? $pkg->price_text_bn : $pkg->price_text_en }}</h3>
                                <p class="small text-white-50 mb-3">{{ $isBn && $pkg->description_bn ? $pkg->description_bn : $pkg->description_en }}</p>
                                <ul class="list-unstyled small text-white-50 mb-4">
                                    @php $features = $isBn && !empty($pkg->features_bn) ? $pkg->features_bn : ($pkg->features_en ?? []); @endphp
                                    @foreach(array_slice($features, 0, 4) as $feat)
                                        <li class="mb-1"><i class="bi bi-check2 text-accent me-1"></i> {{ $feat }}</li>
                                    @endforeach
                                </ul>
                                <a href="{{ route('contact', ['locale' => app()->getLocale(), 'service' => 'AMC - ' . $pkg->name_en]) }}" class="btn {{ $pkg->is_recommended ? 'btn-primary' : 'btn-outline-light' }} btn-sm rounded-pill mt-auto w-100">
                                    {{ $isBn ? 'প্যাকেজটি বেছে নিন' : 'Inquire Now' }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 5. FEATURED PORTFOLIO / CASE STUDY -->
<section class="py-5 bg-light">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-semibold">{{ $isBn ? 'কেস স্টাডি' : 'Proven Track Record' }}</span>
            <h2 class="fw-bold mt-2">{{ $isBn ? 'আমাদের সফল প্রকল্পসমূহ' : 'Featured Case Studies' }}</h2>
        </div>

        <div class="row g-4">
            @foreach($featuredPortfolios as $item)
                <div class="col-lg-6 mx-auto">
                    <div class="card feature-card h-100 p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge bg-secondary">{{ $item->client_name }}</span>
                            <span class="badge bg-info text-dark">{{ $item->industry->title_en ?? 'Industry' }}</span>
                        </div>
                        <h4 class="fw-bold mb-3">
                            <a href="{{ route('portfolio.show', ['locale' => app()->getLocale(), 'slug' => $isBn && $item->slug_bn ? $item->slug_bn : $item->slug_en]) }}" class="text-dark text-decoration-none">
                                {{ $isBn && $item->project_name_bn ? $item->project_name_bn : $item->project_name_en }}
                            </a>
                        </h4>
                        <div class="bg-light p-3 rounded mb-3">
                            <strong class="text-success d-block mb-1"><i class="bi bi-trophy me-1"></i> {{ $isBn ? 'ফলাফল:' : 'Impact & Result:' }}</strong>
                            <p class="small text-muted mb-0">{{ $isBn && $item->results_bn ? $item->results_bn : $item->results_en }}</p>
                        </div>
                        <a href="{{ route('portfolio.show', ['locale' => app()->getLocale(), 'slug' => $isBn && $item->slug_bn ? $item->slug_bn : $item->slug_en]) }}" class="btn btn-outline-primary rounded-pill btn-sm align-self-start">
                            {{ $isBn ? 'সম্পূর্ণ কেস স্টাডি পড়ুন' : 'Read Case Study' }} <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- 6. TESTIMONIALS & CLIENTS -->
<section class="py-5">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-semibold">{{ $isBn ? 'গ্রাহকদের মতামত' : 'Client Feedback' }}</span>
            <h2 class="fw-bold mt-2">{{ $isBn ? 'ক্লায়েন্টরা আমাদের সম্পর্কে যা বলছেন' : 'Trusted by Leading Organizations' }}</h2>
        </div>

        <div class="row g-4 mb-5">
            @foreach($testimonials as $t)
                <div class="col-md-6">
                    <div class="card feature-card p-4 h-100 bg-white">
                        <div class="text-warning mb-3">
                            @for($i=0; $i<$t->rating; $i++)
                                <i class="bi bi-star-fill"></i>
                            @endfor
                        </div>
                        <p class="fst-italic text-muted mb-4">"{{ $isBn && $t->testimonial_bn ? $t->testimonial_bn : $t->testimonial_en }}"</p>
                        <div class="d-flex align-items-center mt-auto">
                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary fw-bold d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                                {{ substr($t->name, 0, 1) }}
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0">{{ $t->name }}</h6>
                                <small class="text-muted">{{ $isBn && $t->designation_bn ? $t->designation_bn : $t->designation_en }} • {{ $t->company }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Clients Logo Strip -->
        <div class="client-logo-showcase text-center pt-3">
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-semibold mb-3">{{ $isBn ? 'Corporate Trust' : 'Corporate Trust' }}</span>
            <h6 class="text-uppercase text-muted small fw-bold mb-4">{{ $isBn ? 'শীর্ষস্থানীয় প্রতিষ্ঠানসমূহ আমাদের উপর আস্থা রাখছে' : 'Valued Corporate Clients' }}</h6>
            <div class="client-logo-grid">
                @foreach($clients as $c)
                    @php
                        $clientLogoUrl = null;

                        if (filled($c->logo)) {
                            $clientLogoUrl = str($c->logo)->startsWith(['http://', 'https://', '/'])
                                ? $c->logo
                                : (str($c->logo)->startsWith('client-logos/') ? \Illuminate\Support\Facades\Storage::url($c->logo) : asset($c->logo));
                        }

                        $clientDescription = $isBn && filled($c->description_bn) ? $c->description_bn : $c->description_en;
                        $hasClientDescription = filled($clientDescription);
                        $clientModalId = 'clientDetails' . $c->id;
                    @endphp
                    <div class="client-logo-card" aria-label="{{ $c->name }}">
                        <span class="client-logo-mark">
                            @if($clientLogoUrl)
                                <img src="{{ $clientLogoUrl }}" alt="{{ $c->name }} logo">
                            @else
                                <span class="client-logo-initial">{{ str($c->name)->substr(0, 1)->upper() }}</span>
                            @endif
                        </span>
                        <span class="client-logo-name">{{ $c->name }}</span>
                        @if($hasClientDescription)
                            <p class="client-logo-description">{{ $clientDescription }}</p>
                        @else
                            <p class="client-logo-description">&nbsp;</p>
                        @endif
                        <span class="client-logo-actions">
                            @if($hasClientDescription)
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#{{ $clientModalId }}">
                                    Details
                                </button>
                            @endif
                            @if($c->website)
                                <a href="{{ $c->website }}" class="btn btn-sm btn-light border rounded-pill" target="_blank" rel="noopener">
                                    Website
                                </a>
                            @endif
                        </span>
                    </div>

                    @if($hasClientDescription)
                        <div class="modal fade" id="{{ $clientModalId }}" tabindex="-1" aria-labelledby="{{ $clientModalId }}Label" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow">
                                    <div class="modal-header">
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="client-detail-logo">
                                                @if($clientLogoUrl)
                                                    <img src="{{ $clientLogoUrl }}" alt="{{ $c->name }} logo">
                                                @else
                                                    <span class="client-logo-initial">{{ str($c->name)->substr(0, 1)->upper() }}</span>
                                                @endif
                                            </span>
                                            <div>
                                                <h5 class="modal-title fw-bold mb-0" id="{{ $clientModalId }}Label">{{ $c->name }}</h5>
                                                @if($c->website)
                                                    <a href="{{ $c->website }}" class="small text-decoration-none" target="_blank" rel="noopener">{{ $c->website }}</a>
                                                @endif
                                            </div>
                                        </div>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body text-start">
                                        <p class="client-detail-description mb-0">{{ $clientDescription }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</section>

<!-- 7. LATEST INSIGHTS / BLOG -->
<section class="py-5 bg-light">
    <div class="container py-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-5">
            <div>
                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-semibold">{{ $isBn ? 'সর্বশেষ খবর ও ইনসাইট' : 'Knowledge & Insights' }}</span>
                <h2 class="fw-bold mt-2">{{ $isBn ? 'প্রযুক্তি ও এন্টারপ্রাইজ ট্রেন্ডস' : 'Latest Tech Articles & Analysis' }}</h2>
            </div>
            <a href="{{ route('blog.index', ['locale' => app()->getLocale()]) }}" class="btn btn-outline-primary rounded-pill mt-3 mt-md-0">
                {{ $isBn ? 'সকল আর্টিকেল দেখুন' : 'View All Articles' }} <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-4">
            @foreach($latestPosts as $post)
                <div class="col-lg-4 col-md-6">
                    <div class="card feature-card h-100">
                        <div class="card-body p-4 d-flex flex-column">
                            <span class="badge bg-primary bg-opacity-10 text-primary mb-2 align-self-start">{{ $post->category->name_en ?? 'Tech' }}</span>
                            <h5 class="fw-bold mb-3">
                                <a href="{{ route('blog.show', ['locale' => app()->getLocale(), 'slug' => $isBn && $post->slug_bn ? $post->slug_bn : $post->slug_en]) }}" class="text-dark text-decoration-none">
                                    {{ $isBn && $post->title_bn ? $post->title_bn : $post->title_en }}
                                </a>
                            </h5>
                            <p class="text-muted small mb-4">{{ $isBn && $post->excerpt_bn ? $post->excerpt_bn : $post->excerpt_en }}</p>
                            <div class="mt-auto d-flex justify-content-between align-items-center small text-muted border-top pt-3">
                                <span><i class="bi bi-calendar3 me-1"></i> {{ $post->published_at ? $post->published_at->format('M d, Y') : '' }}</span>
                                <a href="{{ route('blog.show', ['locale' => app()->getLocale(), 'slug' => $isBn && $post->slug_bn ? $post->slug_bn : $post->slug_en]) }}" class="text-primary fw-bold text-decoration-none">
                                    {{ $isBn ? 'পড়ুন' : 'Read' }} <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- 8. FINAL CTA BANNER -->
<section class="py-5 bg-navy text-white text-center">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h2 class="display-6 fw-bold mb-3">{{ $isBn ? 'আপনার আইটি অবকাঠামোকে পরবর্তী স্তরে নিয়ে যেতে প্রস্তুত?' : 'Ready to Transform Your Enterprise Technology?' }}</h2>
                <p class="lead text-white-50 mb-4">
                    {{ $isBn ? 'আজই আমাদের বিশেষজ্ঞ টিমের সাথে যোগাযোগ করে বিনামূল্যে কনসালটেশন নিন।' : 'Schedule a free consultation with our senior solution architects today.' }}
                </p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="{{ route('contact', ['locale' => app()->getLocale()]) }}" class="btn btn-accent btn-lg rounded-pill px-5 shadow">
                        <i class="bi bi-telephone-fill me-2"></i> {{ $isBn ? 'যোগাযোগ করুন' : 'Contact Us Today' }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

