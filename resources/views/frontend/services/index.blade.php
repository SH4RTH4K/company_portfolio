@extends('layouts.app')

@php
    $isBn = app()->getLocale() === 'bn';
@endphp

@section('meta_title', $isBn ? 'আমাদের সেবাসমূহ' : 'Enterprise Services & Solutions')
@section('meta_description', $isBn ? 'জিরো গ্রাভিটিজ-এর এন্টারপ্রাইজ সফটওয়্যার, ক্লাউড, সাইবার নিরাপত্তা ও ম্যানেজড আইটি সেবা।' : 'Explore Zero Gravities full suite of enterprise software, managed IT, cloud migration, and infrastructure services.')

@section('content')
<!-- Header Banner -->
<div class="bg-navy text-white py-5">
    <div class="container py-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home', ['locale' => app()->getLocale()]) }}" class="text-accent text-decoration-none">{{ $isBn ? 'হোম' : 'Home' }}</a></li>
                <li class="breadcrumb-item active text-white-50" aria-current="page">{{ $isBn ? 'সেবাসমূহ' : 'Services' }}</li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold">{{ $isBn ? 'আমাদের এন্টারপ্রাইজ সেবাসমূহ' : 'Services & Capabilities' }}</h1>
        <p class="lead text-white-50 mb-0">{{ $isBn ? 'প্রতিটি ক্লায়েন্টের প্রয়োজন অনুযায়ী কাস্টমাইজড প্রযুক্তিগত সমাধান' : 'End-to-end technology solutions designed for performance, uptime, and ROI' }}</p>
    </div>
</div>

<div class="container py-5">
    <!-- Division Filter Pills -->
    <div class="d-flex flex-wrap gap-2 mb-5">
        <a href="{{ route('services.index', ['locale' => app()->getLocale()]) }}" class="btn {{ !request('division') ? 'btn-primary' : 'btn-outline-secondary' }} rounded-pill btn-sm px-3">
            {{ $isBn ? 'সকল সেবা' : 'All Capabilities' }}
        </a>
        @foreach($divisions as $div)
            <a href="{{ route('services.index', ['locale' => app()->getLocale(), 'division' => $div->code]) }}" class="btn {{ request('division') == $div->code ? 'btn-primary' : 'btn-outline-secondary' }} rounded-pill btn-sm px-3">
                {{ $isBn && $div->name_bn ? $div->name_bn : $div->name_en }}
            </a>
        @endforeach
    </div>

    <!-- Services Grid -->
    <div class="row g-4">
        @forelse($services as $service)
            <div class="col-lg-4 col-md-6">
                <div class="card feature-card h-100 p-4 border-top border-primary border-4">
                    <span class="badge bg-light text-primary border border-primary border-opacity-25 align-self-start mb-3">{{ $service->businessDivision->name_en ?? 'IT' }}</span>
                    <h5 class="fw-bold mb-3">
                        <a href="{{ route('services.show', ['locale' => app()->getLocale(), 'slug' => $isBn && $service->slug_bn ? $service->slug_bn : $service->slug_en]) }}" class="text-dark text-decoration-none">
                            {{ $isBn && $service->title_bn ? $service->title_bn : $service->title_en }}
                        </a>
                    </h5>
                    <p class="text-muted small mb-4">{{ $isBn && $service->short_description_bn ? $service->short_description_bn : $service->short_description_en }}</p>
                    <div class="mt-auto d-flex justify-content-between align-items-center">
                        <a href="{{ route('services.show', ['locale' => app()->getLocale(), 'slug' => $isBn && $service->slug_bn ? $service->slug_bn : $service->slug_en]) }}" class="fw-bold text-primary text-decoration-none small">
                            {{ $isBn ? 'বিস্তারিত দেখুন' : 'Explore Solution' }} <i class="bi bi-arrow-right"></i>
                        </a>
                        <a href="{{ route('contact', ['locale' => app()->getLocale(), 'service' => $service->title_en]) }}" class="btn btn-sm btn-outline-secondary rounded-pill">
                            {{ $isBn ? 'কোটেশন' : 'Quote' }}
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5 text-muted">
                <p>{{ $isBn ? 'কোন সেবা পাওয়া যায়নি।' : 'No services found in this category.' }}</p>
            </div>
        @endforelse
    </div>

    <div class="mt-5 d-flex justify-content-center">
        {{ $services->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection

