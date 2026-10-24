<!DOCTYPE html>
<html lang="{{ app()->getLocale() == 'bn' ? 'bn' : 'en' }}" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    @php
        $currentLocale = app()->getLocale();
        $altLocale = $currentLocale === 'en' ? 'bn' : 'en';
        $menuIsVisible = fn (string $item): bool => (string) ($globalSettings['menu_'.$item.'_visible'] ?? '1') === '1';
        $hasVisibleMenuItems = count(array_filter(array_keys(\App\Models\SiteSetting::NAVIGATION_ITEMS), $menuIsVisible)) > 0;
        $siteName = $globalSettings['site_name'] ?? 'Zero Gravities';
        $siteTagline = $currentLocale === 'en' ? ($globalSettings['tagline_en'] ?? 'Technology • Solutions • Commerce') : ($globalSettings['tagline_bn'] ?? 'প্রযুক্তি • সমাধান • বাণিজ্য');
        $contactPhone = $globalSettings['phone'] ?? $globalSettings['company_phone'] ?? '+880 1800-000000';
        $contactEmail = $globalSettings['email'] ?? $globalSettings['company_email'] ?? 'info@zerogravities.com';
        $contactAddress = $globalSettings['address'] ?? $globalSettings['company_address'] ?? 'Dhaka, Bangladesh';
        $storedAssetUrl = fn (?string $path, string $fallback): string => $path ? url(\Illuminate\Support\Facades\Storage::url($path)) : asset($fallback);
        $logoUrl = $storedAssetUrl($globalSettings['logo'] ?? null, 'images/logo.png');
        $faviconUrl = $storedAssetUrl($globalSettings['favicon'] ?? null, 'favicon.ico');
        $appleTouchIconUrl = $storedAssetUrl($globalSettings['apple_touch_icon'] ?? null, 'favicon.ico');
        $defaultOgImageUrl = $storedAssetUrl($globalSettings['default_og_image'] ?? null, 'images/og-default.jpg');
        $numericSetting = fn (string $key, int $default, int $min, int $max): int => max($min, min($max, (int) ($globalSettings[$key] ?? $default)));
        $logoWidth = $numericSetting('logo_width', 180, 80, 320);
        $logoHeight = $numericSetting('logo_height', 54, 24, 140);
        $logoPadding = $numericSetting('logo_padding', 0, 0, 40);
        $logoBorderRadius = $numericSetting('logo_border_radius', 0, 0, 40);
        $rawLogoBackground = $globalSettings['logo_background_color'] ?? 'transparent';
        $logoBackground = $rawLogoBackground === 'transparent' || preg_match('/^#[0-9A-Fa-f]{6}$/', $rawLogoBackground) ? $rawLogoBackground : 'transparent';
        $textLogoIconSize = $numericSetting('text_logo_icon_size', 32, 16, 96);
        $textLogoIconWidth = $numericSetting('text_logo_icon_width', $textLogoIconSize, 16, 160);
        $textLogoIconHeight = $numericSetting('text_logo_icon_height', $textLogoIconSize, 16, 160);
        $textLogoIconPadding = $numericSetting('text_logo_icon_padding', 0, 0, 40);
        $textLogoIconBorderRadius = $numericSetting('text_logo_icon_border_radius', 0, 0, 60);
        $rawTextLogoIconBackground = $globalSettings['text_logo_icon_background_color'] ?? 'transparent';
        $textLogoIconBackground = $rawTextLogoIconBackground === 'transparent' || preg_match('/^#[0-9A-Fa-f]{6}$/', $rawTextLogoIconBackground) ? $rawTextLogoIconBackground : 'transparent';
        $textLogoIconValue = $globalSettings['text_logo_icon_class'] ?? 'bi-rocket-takeoff-fill';
        $textLogoHasIcon = $textLogoIconValue !== 'none';
        $textLogoUsesFavicon = $textLogoHasIcon && $textLogoIconValue === 'uploaded_favicon' && !empty($globalSettings['favicon']);
        $textLogoIconClass = $textLogoHasIcon && ! $textLogoUsesFavicon && preg_match('/^bi-[a-z0-9-]+$/', $textLogoIconValue) ? $textLogoIconValue : 'bi-rocket-takeoff-fill';
        $validColor = fn (?string $color, string $fallback): string => $color && preg_match('/^#[0-9A-Fa-f]{6}$/', $color) ? $color : $fallback;
        $textLogoIconColor = $validColor($globalSettings['text_logo_icon_color'] ?? null, '#0d6efd');
        $textLogoPrimaryText = $globalSettings['text_logo_primary_text'] ?? 'ZERO';
        $textLogoPrimaryColor = $validColor($globalSettings['text_logo_primary_color'] ?? null, '#ffffff');
        $textLogoAccentText = $globalSettings['text_logo_accent_text'] ?? 'GRAVITIES';
        $textLogoAccentColor = $validColor($globalSettings['text_logo_accent_color'] ?? null, '#00b4d8');
        $textLogoTaglineText = trim((string) ($globalSettings['text_logo_tagline_text'] ?? 'Beyond Limits'));
        $textLogoTaglineColor = $validColor($globalSettings['text_logo_tagline_color'] ?? null, '#8fa6b8');
        $textLogoTaglineSize = $numericSetting('text_logo_tagline_size', 11, 8, 24);
        
        $metaTitle = View::hasSection('meta_title') ? View::getSection('meta_title') . ' | ' . $siteName : ($siteName . ' — ' . $siteTagline);
        $metaDescription = View::hasSection('meta_description') ? View::getSection('meta_description') : ($currentLocale === 'en' ? 'Zero Gravities provides enterprise-grade Managed IT, Annual Maintenance Contracts (AMC), Custom Software & Cloud Infrastructure in Bangladesh.' : 'জিরো গ্রাভিটিজ বাংলাদেশে এন্টারপ্রাইজ ম্যানেজড আইটি, সফটওয়্যার ও ক্লাউড সেবা প্রদানকারী শীর্ষস্থানীয় প্রতিষ্ঠান।');
        $metaKeywords = View::hasSection('meta_keywords') ? View::getSection('meta_keywords') : 'Zero Gravities, Managed IT BD, Software Development Bangladesh, AMC Services Dhaka, Cloud Security, Enterprise Servers';
        $ogImage = View::hasSection('og_image') ? View::getSection('og_image') : $defaultOgImageUrl;
        $canonicalUrl = View::hasSection('canonical_url') ? View::getSection('canonical_url') : url()->current();
        
        // Hreflang URLs
        $currentRoute = Route::current();
        $currentRouteName = $currentRoute ? $currentRoute->getName() : null;
        $routeParams = $currentRoute ? $currentRoute->parameters() : [];
        
        $paramsEn = array_merge($routeParams, ['locale' => 'en']);
        $paramsBn = array_merge($routeParams, ['locale' => 'bn']);
        
        $urlEn = $currentRouteName ? route($currentRouteName, $paramsEn) : url('/en');
        $urlBn = $currentRouteName ? route($currentRouteName, $paramsBn) : url('/bn');
    @endphp

    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="keywords" content="{{ $metaKeywords }}">
    <meta name="robots" content="@yield('meta_robots', 'index, follow')">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <link rel="icon" href="{{ $faviconUrl }}">
    <link rel="apple-touch-icon" href="{{ $appleTouchIconUrl }}">

    <!-- Hreflang Alternates -->
    <link rel="alternate" hreflang="en" href="{{ $urlEn }}">
    <link rel="alternate" hreflang="bn" href="{{ $urlBn }}">
    <link rel="alternate" hreflang="x-default" href="{{ $urlEn }}">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:locale" content="{{ $currentLocale === 'bn' ? 'bn_BD' : 'en_US' }}">

    <!-- Twitter / X -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">

    <!-- Structured Data JSON-LD -->
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "Organization",
      "name": "Zero Gravities",
      "url": "{{ url('/') }}",
      "logo": "{{ $logoUrl }}",
      "contactPoint": {
        "@@type": "ContactPoint",
        "telephone": "{{ $contactPhone }}",
        "contactType": "customer service",
        "areaServed": "BD",
        "availableLanguage": ["en", "bn"]
      }
    }
    </script>
    @yield('schema_jsonld')

    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --zg-navy: #0d1b2a;
            --zg-deep: #1b263b;
            --zg-blue: #0d6efd;
            --zg-accent: #00b4d8;
            --zg-light-bg: #f8f9fa;
            --zg-logo-width: {{ $logoWidth }}px;
            --zg-logo-height: {{ $logoHeight }}px;
            --zg-logo-padding: {{ $logoPadding }}px;
            --zg-logo-radius: {{ $logoBorderRadius }}px;
            --zg-logo-bg: {{ $logoBackground }};
            --zg-text-logo-icon-width: {{ $textLogoIconWidth }}px;
            --zg-text-logo-icon-height: {{ $textLogoIconHeight }}px;
            --zg-text-logo-icon-padding: {{ $textLogoIconPadding }}px;
            --zg-text-logo-icon-radius: {{ $textLogoIconBorderRadius }}px;
            --zg-text-logo-icon-bg: {{ $textLogoIconBackground }};
        }
        body {
            font-family: {{ $currentLocale === 'bn' ? "'Hind Siliguri', sans-serif" : "'Plus Jakarta Sans', sans-serif" }};
            color: #2b2d42;
            background-color: #fff;
            overflow-x: hidden;
        }
        .bg-navy { background-color: var(--zg-navy) !important; }
        .bg-deep { background-color: var(--zg-deep) !important; }
        .text-accent { color: var(--zg-accent) !important; }
        .btn-accent {
            background-color: var(--zg-accent);
            color: #fff;
            font-weight: 600;
            border: none;
        }
        .btn-accent:hover {
            background-color: #0096c7;
            color: #fff;
        }
        .navbar-nav .nav-link {
            font-weight: 500;
            padding: 0.5rem 0.9rem !important;
            color: rgba(255,255,255,0.85);
            transition: all 0.2s ease;
        }
        .navbar-nav .nav-link:hover, .navbar-nav .nav-link.active {
            color: var(--zg-accent);
        }
        .brand-logo-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: var(--zg-logo-bg);
            padding: var(--zg-logo-padding);
            border-radius: var(--zg-logo-radius);
            line-height: 1;
        }
        .brand-logo {
            width: min(var(--zg-logo-width), 48vw);
            height: var(--zg-logo-height);
            object-fit: contain;
        }
        .footer-logo {
            width: min(var(--zg-logo-width), 100%);
            height: var(--zg-logo-height);
            object-fit: contain;
        }
        .text-logo-icon {
            align-items: center;
            background-color: var(--zg-text-logo-icon-bg);
            border-radius: var(--zg-text-logo-icon-radius);
            box-sizing: content-box;
            color: {{ $textLogoIconColor }};
            display: inline-flex;
            flex: 0 0 auto;
            font-size: {{ $textLogoIconSize }}px;
            height: var(--zg-text-logo-icon-height);
            justify-content: center;
            line-height: 1;
            padding: var(--zg-text-logo-icon-padding);
            width: var(--zg-text-logo-icon-width);
        }
        .text-logo-favicon {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .text-logo-primary {
            color: {{ $textLogoPrimaryColor }};
        }
        .text-logo-accent {
            color: {{ $textLogoAccentColor }};
        }
        .text-logo-wordmark {
            display: inline-flex;
            flex-direction: column;
            justify-content: center;
            line-height: 1;
        }
        .text-logo-title {
            display: inline-flex;
            align-items: baseline;
            white-space: nowrap;
        }
        .text-logo-tagline {
            color: {{ $textLogoTaglineColor }};
            font-size: {{ $textLogoTaglineSize }}px;
            font-weight: 600;
            line-height: 1.1;
            margin-top: 0.18rem;
            letter-spacing: 0;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .hero-banner {
            background: linear-gradient(135deg, #0d1b2a 0%, #1b263b 60%, #0d3b66 100%);
            color: #fff;
            padding: 90px 0 80px;
        }
        .feature-card {
            border: 1px solid rgba(0,0,0,0.06);
            border-radius: 12px;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            background: #fff;
        }
        .feature-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 32px rgba(13, 27, 42, 0.08);
        }
        .client-logo-showcase {
            padding: 2rem;
            border: 1px solid rgba(13, 27, 42, 0.08);
            border-radius: 12px;
            background:
                linear-gradient(180deg, rgba(255,255,255,0.95), rgba(248,250,252,0.95)),
                radial-gradient(circle at top left, rgba(13, 110, 253, 0.08), transparent 34%);
            box-shadow: 0 18px 45px rgba(13, 27, 42, 0.07);
        }
        .client-logo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }
        .client-logo-card {
            min-height: 214px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            gap: 0.7rem;
            padding: 1.15rem 1rem;
            border: 1px solid rgba(13, 27, 42, 0.08);
            border-radius: 8px;
            background: #fff;
            color: inherit;
            text-decoration: none;
            transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .client-logo-card:hover {
            transform: translateY(-4px);
            border-color: rgba(13, 110, 253, 0.28);
            box-shadow: 0 14px 30px rgba(13, 27, 42, 0.08);
        }
        .client-logo-card:hover .client-logo-name {
            color: var(--zg-blue);
        }
        .client-logo-mark {
            width: 88px;
            height: 64px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .client-logo-mark img {
            max-width: 88px;
            max-height: 58px;
            object-fit: contain;
            filter: saturate(0.85);
            transition: filter 0.2s ease, transform 0.2s ease;
        }
        .client-logo-card:hover .client-logo-mark img {
            filter: saturate(1);
            transform: scale(1.03);
        }
        .client-logo-initial {
            width: 58px;
            height: 58px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--zg-navy), var(--zg-blue));
            color: #fff;
            font-size: 1.4rem;
            font-weight: 800;
        }
        .client-logo-name {
            color: #1b263b;
            font-size: 0.92rem;
            font-weight: 800;
            line-height: 1.25;
            text-align: center;
        }
        .client-logo-description {
            min-height: 2.8rem;
            margin: 0;
            color: #64748b;
            display: -webkit-box;
            font-size: 0.82rem;
            line-height: 1.4;
            overflow: hidden;
            text-align: justify;
            text-align-last: center;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
        }
        .client-logo-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.5rem;
            margin-top: auto;
        }
        .client-detail-logo {
            width: 86px;
            height: 64px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(13, 27, 42, 0.08);
            border-radius: 8px;
            background: #fff;
            overflow: hidden;
        }
        .client-detail-logo img {
            max-width: 76px;
            max-height: 54px;
            object-fit: contain;
        }
        .client-detail-description {
            color: #475569;
            line-height: 1.75;
            text-align: justify;
            white-space: pre-line;
        }
        .footer-link {
            color: #adb5bd;
            text-decoration: none;
            transition: color 0.2s;
            display: inline-block;
            margin-bottom: 0.45rem;
        }
        .footer-link:hover {
            color: var(--zg-accent);
        }
        .badge-pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { opacity: 0.8; }
            50% { opacity: 1; transform: scale(1.03); }
            100% { opacity: 0.8; }
        }
        .floating-contact-buttons {
            position: fixed;
            right: 20px;
            bottom: 24px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 12px;
        }
        .floating-contact-button {
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
            color: #fff;
            text-decoration: none;
            border-radius: 50px;
            padding: 10px 18px 10px 14px;
            font-weight: 600;
            font-size: 14px;
            min-width: 52px;
            min-height: 52px;
            overflow: hidden;
            will-change: transform;
            animation: floating-contact-idle 3.2s ease-in-out infinite;
            transition: width 0.28s ease, transform 0.24s ease, box-shadow 0.24s ease, filter 0.24s ease, padding 0.28s ease;
        }
        .floating-contact-button:hover,
        .floating-contact-button:focus {
            color: #fff;
            transform: translateY(-8px) scale(1.07);
            filter: brightness(1.04);
            animation-play-state: paused;
        }
        .floating-contact-button:focus-visible {
            outline: 3px solid rgba(255,255,255,0.7);
            outline-offset: 3px;
        }
        .floating-contact-label {
            max-width: 120px;
            overflow: hidden;
            white-space: nowrap;
            opacity: 1;
            transition: max-width 0.28s ease, opacity 0.2s ease;
        }
        .floating-contact-buttons.is-collapsed .floating-contact-button {
            width: 52px;
            height: 52px;
            gap: 0;
            padding: 0;
        }
        .floating-contact-buttons.is-collapsed .floating-contact-label {
            max-width: 0;
            opacity: 0;
        }
        .floating-contact-button--whatsapp {
            background-color: #25D366;
            box-shadow: 0 4px 16px rgba(37,211,102,0.45);
        }
        .floating-contact-button--whatsapp:hover,
        .floating-contact-button--whatsapp:focus {
            box-shadow: 0 14px 30px rgba(37,211,102,0.62);
        }
        .floating-contact-button--call {
            background-color: #0d6efd;
            box-shadow: 0 4px 16px rgba(13,110,253,0.4);
            animation-delay: 0.28s;
        }
        .floating-contact-button--call:hover,
        .floating-contact-button--call:focus {
            box-shadow: 0 14px 30px rgba(13,110,253,0.62);
        }
        @keyframes floating-contact-idle {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-4px); }
        }
        @media (prefers-reduced-motion: reduce) {
            .floating-contact-button {
                animation: none;
                transition: box-shadow 0.2s ease, filter 0.2s ease;
            }
            .floating-contact-button:hover,
            .floating-contact-button:focus {
                transform: none;
            }
        }
    </style>

    <!-- Injected Global Header Scripts -->
    {!! $globalSettings['header_scripts'] ?? '' !!}
</head>
<body class="d-flex flex-column min-vh-100">
    
    <!-- Top Bar -->
    <div class="bg-deep text-white py-1 px-3 small border-bottom border-secondary border-opacity-25">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <span class="me-3"><i class="bi bi-envelope me-1 text-accent"></i> {{ $contactEmail }}</span>
                <span class="d-none d-md-inline"><i class="bi bi-telephone me-1 text-accent"></i> {{ $contactPhone }}</span>
            </div>
            <div class="d-flex align-items-center">
                <!-- Language Switcher -->
                <div class="btn-group btn-group-sm me-3" role="group">
                    <a href="{{ $urlEn }}" class="btn btn-sm {{ $currentLocale === 'en' ? 'btn-primary active' : 'btn-outline-light' }} py-0 px-2 fw-bold">EN</a>
                    <a href="{{ $urlBn }}" class="btn btn-sm {{ $currentLocale === 'bn' ? 'btn-primary active' : 'btn-outline-light' }} py-0 px-2 fw-bold">বাংলা</a>
                </div>
                <a href="{{ route('admin.login') }}" class="text-white-50 text-decoration-none small"><i class="bi bi-shield-lock me-1"></i> Admin</a>
            </div>
        </div>
    </div>

    <!-- Main Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-navy sticky-top shadow-sm py-3">
        <div class="container">
            <a class="navbar-brand fw-bold fs-3 text-white d-flex align-items-center" href="{{ route('home', ['locale' => $currentLocale]) }}">
                @if(!empty($globalSettings['logo']))
                    <span class="brand-logo-wrap">
                        <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="brand-logo">
                    </span>
                @else
                    @if($textLogoHasIcon)
                        <span class="text-logo-icon me-2">
                            @if($textLogoUsesFavicon)
                                <img src="{{ $faviconUrl }}" alt="" class="text-logo-favicon">
                            @else
                                <i class="bi {{ $textLogoIconClass }}"></i>
                            @endif
                        </span>
                    @endif
                    <span class="text-logo-wordmark">
                        <span class="text-logo-title">
                            <span class="text-logo-primary">{{ $textLogoPrimaryText }}</span><span class="text-logo-accent">{{ $textLogoAccentText }}</span>
                        </span>
                        @if($textLogoTaglineText !== '')
                            <span class="text-logo-tagline">{{ $textLogoTaglineText }}</span>
                        @endif
                    </span>
                @endif
            </a>
            
            @if($hasVisibleMenuItems)
                <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
                    <span class="navbar-toggler-icon"></span>
                </button>
            
                <div class="collapse navbar-collapse" id="mainNavbar">
                    <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                        @if($menuIsVisible('home'))
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home', ['locale' => $currentLocale]) }}">
                                    {{ $currentLocale === 'en' ? 'Home' : 'হোম' }}
                                </a>
                            </li>
                        @endif
                        @if($menuIsVisible('about'))
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('about') ? 'active' : '' }}" href="{{ route('about', ['locale' => $currentLocale]) }}">
                                    {{ $currentLocale === 'en' ? 'About Us' : 'আমাদের সম্পর্কে' }}
                                </a>
                            </li>
                        @endif
                        @if($menuIsVisible('services'))
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('services.*') ? 'active' : '' }}" href="{{ route('services.index', ['locale' => $currentLocale]) }}">
                                    {{ $currentLocale === 'en' ? 'Services' : 'সেবাসমূহ' }}
                                </a>
                            </li>
                        @endif
                        @if($menuIsVisible('managed_it'))
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('managed-it') ? 'active' : '' }}" href="{{ route('managed-it', ['locale' => $currentLocale]) }}">
                                    <span class="badge bg-warning text-dark me-1">24/7</span> {{ $currentLocale === 'en' ? 'Managed IT / AMC' : 'আইটি এএমসি' }}
                                </a>
                            </li>
                        @endif
                        @if($menuIsVisible('products'))
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index', ['locale' => $currentLocale]) }}">
                                    {{ $currentLocale === 'en' ? 'Products' : 'পণ্যসমূহ' }}
                                </a>
                            </li>
                        @endif
                        @if($menuIsVisible('portfolio'))
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('portfolio.*') ? 'active' : '' }}" href="{{ route('portfolio.index', ['locale' => $currentLocale]) }}">
                                    {{ $currentLocale === 'en' ? 'Portfolio' : 'পোর্টফোলিও' }}
                                </a>
                            </li>
                        @endif
                        @if($menuIsVisible('industries'))
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('industries.*') ? 'active' : '' }}" href="{{ route('industries.index', ['locale' => $currentLocale]) }}">
                                    {{ $currentLocale === 'en' ? 'Industries' : 'ইন্ডাস্ট্রিজ' }}
                                </a>
                            </li>
                        @endif
                        @if($menuIsVisible('blog'))
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('blog.*') ? 'active' : '' }}" href="{{ route('blog.index', ['locale' => $currentLocale]) }}">
                                    {{ $currentLocale === 'en' ? 'Insights' : 'ব্লগ ও ইনসাইট' }}
                                </a>
                            </li>
                        @endif
                    </ul>
                
                    @if($menuIsVisible('contact'))
                        <div class="d-flex align-items-center">
                            <a href="{{ route('contact', ['locale' => $currentLocale]) }}" class="btn btn-accent rounded-pill px-4 shadow-sm">
                                <i class="bi bi-chat-dots-fill me-1"></i> {{ $currentLocale === 'en' ? 'Get a Quote' : 'যোগাযোগ করুন' }}
                            </a>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </nav>

    <!-- Main Dynamic Content -->
    <main class="flex-grow-1">
        @yield('content')
    </main>

    <!-- Full 5-Column Corporate Footer -->
    <footer class="bg-navy text-white pt-5 pb-4 mt-auto border-top border-secondary border-opacity-25">
        <div class="container">
            <div class="row g-4 pb-4">
                <!-- Col 1: About & Info -->
                <div class="col-lg-4 col-md-6">
                    <h5 class="fw-bold text-white mb-3 d-flex align-items-center">
                        @if(!empty($globalSettings['logo']))
                            <span class="brand-logo-wrap">
                                <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="footer-logo">
                            </span>
                        @else
                            @if($textLogoHasIcon)
                                <span class="text-logo-icon me-2">
                                    @if($textLogoUsesFavicon)
                                        <img src="{{ $faviconUrl }}" alt="" class="text-logo-favicon">
                                    @else
                                        <i class="bi {{ $textLogoIconClass }}"></i>
                                    @endif
                                </span>
                            @endif
                            <span class="text-logo-wordmark">
                                <span class="text-logo-title">
                                    <span class="text-logo-primary">{{ $textLogoPrimaryText }}</span><span class="text-logo-accent">{{ $textLogoAccentText }}</span>
                                </span>
                                @if($textLogoTaglineText !== '')
                                    <span class="text-logo-tagline">{{ $textLogoTaglineText }}</span>
                                @endif
                            </span>
                        @endif
                    </h5>
                    <p class="text-white-50 pe-lg-4">
                        {{ $currentLocale === 'en' ? 'Empowering businesses across Bangladesh and beyond with intelligent enterprise software, reliable 24/7 Managed IT support, cloud architecture, and corporate technology supplies.' : 'স্মার্ট সফটওয়্যার, সার্বক্ষণিক ম্যানেজড আইটি সাপোর্ট, ক্লাউড আর্কিটেকচার ও এন্টারপ্রাইজ প্রযুক্তির মাধ্যমে ব্যবসাকে এগিয়ে নিতে আমরা প্রতিশ্রুতিবদ্ধ।' }}
                    </p>
                    <div class="d-flex gap-3 mt-3">
                        <a href="https://facebook.com" class="text-white-50 fs-5"><i class="bi bi-facebook"></i></a>
                        <a href="https://linkedin.com" class="text-white-50 fs-5"><i class="bi bi-linkedin"></i></a>
                        <a href="https://twitter.com" class="text-white-50 fs-5"><i class="bi bi-twitter-x"></i></a>
                        <a href="https://youtube.com" class="text-white-50 fs-5"><i class="bi bi-youtube"></i></a>
                    </div>
                </div>

                <!-- Col 2: Solutions & Services -->
                @if($menuIsVisible('managed_it') || $menuIsVisible('services') || $menuIsVisible('products'))
                    <div class="col-lg-2 col-md-6">
                        <h6 class="text-uppercase fw-bold text-accent mb-3">{{ $currentLocale === 'en' ? 'Services' : 'সেবাসমূহ' }}</h6>
                        <ul class="list-unstyled">
                            @if($menuIsVisible('managed_it'))
                                <li><a href="{{ route('managed-it', ['locale' => $currentLocale]) }}" class="footer-link">{{ $currentLocale === 'en' ? 'Managed IT & AMC' : 'আইটি এএমসি' }}</a></li>
                            @endif
                            @if($menuIsVisible('services'))
                                <li><a href="{{ route('services.index', ['locale' => $currentLocale]) }}" class="footer-link">{{ $currentLocale === 'en' ? 'Custom Software' : 'কাস্টম সফটওয়্যার' }}</a></li>
                            @endif
                            @if($menuIsVisible('services'))
                                <li><a href="{{ route('services.index', ['locale' => $currentLocale]) }}" class="footer-link">{{ $currentLocale === 'en' ? 'Cloud & Security' : 'ক্লাউড ও নিরাপত্তা' }}</a></li>
                            @endif
                            @if($menuIsVisible('products'))
                                <li><a href="{{ route('products.index', ['locale' => $currentLocale]) }}" class="footer-link">{{ $currentLocale === 'en' ? 'Hardware Supply' : 'হার্ডওয়্যার সরবরাহ' }}</a></li>
                            @endif
                        </ul>
                    </div>
                @endif

                <!-- Col 3: Quick Links -->
                @if($menuIsVisible('about') || $menuIsVisible('portfolio') || $menuIsVisible('industries') || $menuIsVisible('blog') || $menuIsVisible('contact'))
                    <div class="col-lg-2 col-md-6">
                        <h6 class="text-uppercase fw-bold text-accent mb-3">{{ $currentLocale === 'en' ? 'Company' : 'কোম্পানি' }}</h6>
                        <ul class="list-unstyled">
                            @if($menuIsVisible('about'))
                                <li><a href="{{ route('about', ['locale' => $currentLocale]) }}" class="footer-link">{{ $currentLocale === 'en' ? 'About Us' : 'আমাদের সম্পর্কে' }}</a></li>
                            @endif
                            @if($menuIsVisible('portfolio'))
                                <li><a href="{{ route('portfolio.index', ['locale' => $currentLocale]) }}" class="footer-link">{{ $currentLocale === 'en' ? 'Case Studies' : 'কেস স্টাডি' }}</a></li>
                            @endif
                            @if($menuIsVisible('industries'))
                                <li><a href="{{ route('industries.index', ['locale' => $currentLocale]) }}" class="footer-link">{{ $currentLocale === 'en' ? 'Industries' : 'ইন্ডাস্ট্রিজ' }}</a></li>
                            @endif
                            @if($menuIsVisible('blog'))
                                <li><a href="{{ route('blog.index', ['locale' => $currentLocale]) }}" class="footer-link">{{ $currentLocale === 'en' ? 'Latest News' : 'ব্লগ ও খবর' }}</a></li>
                            @endif
                            @if($menuIsVisible('contact'))
                                <li><a href="{{ route('contact', ['locale' => $currentLocale]) }}" class="footer-link">{{ $currentLocale === 'en' ? 'Contact Us' : 'যোগাযোগ' }}</a></li>
                            @endif
                        </ul>
                    </div>
                @endif

                <!-- Col 4: Contact Information -->
                <div class="col-lg-4 col-md-6">
                    <h6 class="text-uppercase fw-bold text-accent mb-3">{{ $currentLocale === 'en' ? 'Headquarters' : 'হেড অফিস' }}</h6>
                    <p class="text-white-50 mb-2">
                        <i class="bi bi-geo-alt-fill text-accent me-2"></i>
                        {{ $contactAddress }}
                    </p>
                    <p class="text-white-50 mb-2">
                        <i class="bi bi-telephone-fill text-accent me-2"></i>
                        {{ $contactPhone }}
                    </p>
                    <p class="text-white-50 mb-3">
                        <i class="bi bi-envelope-fill text-accent me-2"></i>
                        {{ $contactEmail }}
                    </p>
                    <div class="bg-deep p-3 rounded border border-secondary border-opacity-25">
                        <span class="badge bg-success mb-1">{{ $currentLocale === 'en' ? '24/7 SLA Support' : '২৪/৭ সাপোর্ট' }}</span>
                        <p class="small text-white-50 mb-0">{{ $currentLocale === 'en' ? 'Enterprise helpline for contract clients active 24/7/365.' : 'চুক্তিভিত্তিক গ্রাহকদের জন্য সার্বক্ষণিক ডেডিকেটেড হেল্পলাইন।' }}</p>
                    </div>
                </div>
            </div>

            <hr class="border-secondary border-opacity-25 my-4">

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center small text-white-50">
                <p class="mb-2 mb-md-0">&copy; {{ date('Y') }} Zero Gravities Ltd. {{ $currentLocale === 'en' ? 'All rights reserved.' : 'সর্বস্বত্ব সংরক্ষিত।' }}</p>
                <div>
                    <a href="{{ url('/sitemap.xml') }}" class="text-white-50 text-decoration-none me-3"><i class="bi bi-diagram-3 me-1"></i> Sitemap</a>
                    <a href="{{ route('admin.login') }}" class="text-white-50 text-decoration-none"><i class="bi bi-lock me-1"></i> Admin Portal</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Injected Global Footer Scripts -->
    {!! $globalSettings['footer_scripts'] ?? '' !!}

    <!-- WhatsApp / Call Floating Buttons -->
    @php
        $waNumber = $globalSettings['whatsapp'] ?? '+8801400000000';
        $waNumber = preg_replace('/[^0-9+]/', '', $waNumber);
        $callNumber = $contactPhone ?: '+8801400000000';
        $waMessage = $currentLocale === 'bn' 
            ? 'হ্যালো! আমি Zero Gravities-এর সাথে কথা বলতে চাই।'
            : 'Hello! I would like to inquire about your services.';
    @endphp

    @if($waNumber)
    <div id="floating-buttons" class="floating-contact-buttons is-collapsed">
        {{-- WhatsApp --}}
        <a href="https://wa.me/{{ $waNumber }}?text={{ urlencode($waMessage) }}"
           target="_blank" rel="noopener"
           title="{{ $currentLocale === 'bn' ? 'WhatsApp-এ যোগাযোগ করুন' : 'Chat on WhatsApp' }}"
           class="floating-contact-button floating-contact-button--whatsapp"
           aria-expanded="false">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16">
                <path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/>
            </svg>
            <span class="floating-contact-label">{{ $currentLocale === 'bn' ? 'WhatsApp' : 'WhatsApp' }}</span>
        </a>

        {{-- Call --}}
        <a href="tel:{{ $callNumber }}"
           title="{{ $currentLocale === 'bn' ? 'ফোন করুন' : 'Call Us' }}"
           class="floating-contact-button floating-contact-button--call"
           aria-expanded="false">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                <path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.568 17.568 0 0 0 4.168 6.608 17.569 17.569 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.678.678 0 0 0-.58-.122l-2.19.547a1.745 1.745 0 0 1-1.657-.459L5.482 8.062a1.745 1.745 0 0 1-.46-1.657l.548-2.19a.678.678 0 0 0-.122-.58L3.654 1.328zM1.884.511a1.745 1.745 0 0 1 2.612.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.678.678 0 0 0 .178.643l2.457 2.457a.678.678 0 0 0 .644.178l2.189-.547a1.745 1.745 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.634 18.634 0 0 1-7.01-4.42 18.634 18.634 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877L1.885.511z"/>
            </svg>
            <span class="floating-contact-label">{{ $currentLocale === 'bn' ? 'কল করুন' : 'Call Us' }}</span>
        </a>
    </div>
    <script>
        (() => {
            const floatingContactButtons = document.getElementById('floating-buttons');

            if (! floatingContactButtons) {
                return;
            }

            const floatingContactLinks = floatingContactButtons.querySelectorAll('.floating-contact-button');

            const setFloatingContactExpanded = (expanded) => {
                floatingContactButtons.classList.toggle('is-collapsed', ! expanded);
                floatingContactLinks.forEach((link) => {
                    link.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                });
            };

            floatingContactLinks.forEach((link) => {
                link.addEventListener('click', (event) => {
                    if (floatingContactButtons.classList.contains('is-collapsed')) {
                        event.preventDefault();
                        setFloatingContactExpanded(true);
                    }
                });
            });

            document.addEventListener('click', (event) => {
                if (! floatingContactButtons.contains(event.target)) {
                    setFloatingContactExpanded(false);
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    setFloatingContactExpanded(false);
                }
            });
        })();
    </script>
    @endif

</body>
</html>
