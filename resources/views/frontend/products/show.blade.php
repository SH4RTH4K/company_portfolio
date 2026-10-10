@extends('layouts.app')

@php
    $isBn = app()->getLocale() === 'bn';
    $name = $isBn && $product->product_name_bn ? $product->product_name_bn : $product->product_name_en;
    $seo = $product->seoMeta;
@endphp

@section('meta_title', $seo && $seo->seo_title_en ? ($isBn && $seo->seo_title_bn ? $seo->seo_title_bn : $seo->seo_title_en) : $name)
@section('meta_description', $seo && $seo->seo_description_en ? ($isBn && $seo->seo_description_bn ? $seo->seo_description_bn : $seo->seo_description_en) : ($isBn ? $product->short_description_bn : $product->short_description_en))

@section('content')
<!-- Header Banner -->
<div class="bg-navy text-white py-5">
    <div class="container py-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home', ['locale' => app()->getLocale()]) }}" class="text-accent text-decoration-none">{{ $isBn ? 'হোম' : 'Home' }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('products.index', ['locale' => app()->getLocale()]) }}" class="text-accent text-decoration-none">{{ $isBn ? 'পণ্যসমূহ' : 'Products' }}</a></li>
                <li class="breadcrumb-item active text-white-50" aria-current="page">{{ $name }}</li>
            </ol>
        </nav>
        <span class="badge bg-primary px-3 py-2 rounded-pill mb-2">{{ $product->brand->name_en ?? 'Brand' }}</span>
        <h1 class="display-5 fw-bold">{{ $name }}</h1>
        <p class="lead text-white-50 mb-0">SKU: {{ $product->sku ?? 'N/A' }}</p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-5">
        <!-- Main Details -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm p-4 p-md-5 rounded-4 mb-4">
                <div class="bg-light rounded-4 text-center p-5 mb-4 d-flex align-items-center justify-content-center" style="min-height: 260px;">
                    <i class="bi bi-hdd-network text-primary" style="font-size: 6rem;"></i>
                </div>

                <h4 class="fw-bold mb-3">{{ $isBn ? 'পণ্যের বিবরণ' : 'Product Description' }}</h4>
                <div class="lh-lg text-muted fs-5 mb-4">
                    {!! nl2br(e($isBn && $product->description_bn ? $product->description_bn : $product->description_en)) !!}
                </div>
            </div>
        </div>

        <!-- Sidebar / Buy Quote -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm p-4 rounded-4 mb-4">
                <div class="mb-3">
                    <span class="badge bg-success mb-2">{{ $isBn ? 'স্টকে আছে' : 'In Stock & Ready to Deploy' }}</span>
                    @if($product->show_price && $product->price)
                        <h2 class="fw-bold text-primary mb-0">BDT {{ number_format($product->price) }}</h2>
                        <small class="text-muted">{{ $isBn ? 'ভ্যাট ও ট্যাক্স প্রযোজ্য' : 'Excluding VAT/Tax where applicable' }}</small>
                    @else
                        <h3 class="fw-bold text-primary mb-0">{{ $isBn ? 'মূল্য জানতে কোটেশন চান' : 'Custom Corporate Pricing' }}</h3>
                    @endif
                </div>

                <hr>

                <div class="mb-4">
                    <h6 class="fw-bold mb-2">{{ $isBn ? 'সংক্ষিপ্ত তথ্য:' : 'Key Highlights:' }}</h6>
                    <p class="text-muted small">{{ $isBn && $product->short_description_bn ? $product->short_description_bn : $product->short_description_en }}</p>
                </div>

                <div class="bg-light p-4 rounded-4 text-center mb-4">
                    <h5 class="fw-bold mb-2">{{ $isBn ? 'অফিসিয়াল কোটেশন প্রয়োজন?' : 'Need an Official Price Quote?' }}</h5>
                    <p class="small text-muted mb-3">{{ $isBn ? 'আমাদের টিম আপনার কর্পোরেট ডিসকাউন্ট ও ইনস্টলেশন সাপোর্ট সহ প্রপোজাল পাঠাবে।' : 'Receive official enterprise quotes with installation warranty within 2 business hours.' }}</p>
                    <a href="{{ route('contact', ['locale' => app()->getLocale(), 'service' => 'Product: ' . $product->product_name_en . ' (SKU: ' . $product->sku . ')']) }}" class="btn btn-primary rounded-pill w-100 py-2 fw-bold">
                        <i class="bi bi-file-earmark-text-fill me-1"></i> {{ $isBn ? 'কোটেশন রিকোয়েস্ট করুন' : 'Request Official RFQ' }}
                    </a>
                </div>

                <div class="small text-muted">
                    <p class="mb-1"><i class="bi bi-shield-check text-success me-2"></i> {{ $isBn ? '১০০% অরিজিনাল ব্র্যান্ড ওয়ারেন্টি' : '100% Genuine Brand Warranty' }}</p>
                    <p class="mb-1"><i class="bi bi-truck text-primary me-2"></i> {{ $isBn ? 'ঢাকা ও সারাদেশে দ্রুত ডেলিভারি' : 'Nationwide Enterprise Delivery' }}</p>
                    <p class="mb-0"><i class="bi bi-tools text-warning me-2"></i> {{ $isBn ? 'সার্টিফাইড ইঞ্জিনিয়ার দ্বারা ইনস্টলেশন' : 'Certified Engineer On-site Deployment' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

