@extends('layouts.app')

@php
    $isBn = app()->getLocale() === 'bn';
@endphp

@section('meta_title', $isBn ? 'শিল্প ও খাতভিত্তিক সমাধান' : 'Industries & Sectors We Serve')
@section('meta_description', $isBn ? 'গার্মেন্টস, ব্যাংকিং, স্বাস্থ্যসেবা ও কর্পোরেট খাতের জন্য বিশেষায়িত প্রযুক্তি সমাধান।' : 'Specialized IT, networking, and software solutions for RMG, Banking, Healthcare, and Corporate enterprises.')

@section('content')
<!-- Header Banner -->
<div class="bg-navy text-white py-5">
    <div class="container py-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home', ['locale' => app()->getLocale()]) }}" class="text-accent text-decoration-none">{{ $isBn ? 'হোম' : 'Home' }}</a></li>
                <li class="breadcrumb-item active text-white-50" aria-current="page">{{ $isBn ? 'ইন্ডাস্ট্রিজ' : 'Industries' }}</li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold">{{ $isBn ? 'খাতভিত্তিক এন্টারপ্রাইজ সমাধান' : 'Industry-Specific Solutions' }}</h1>
        <p class="lead text-white-50 mb-0">{{ $isBn ? 'প্রতিটি খাতের অনন্য প্রযুক্তিগত চ্যালেঞ্জ সমাধানে আমাদের বিশেষায়িত আর্কিটেকচার' : 'Tailored enterprise architectures addressing the compliance, uptime, and scale needs of key industries' }}</p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4">
        @foreach($industries as $ind)
            <div class="col-lg-6">
                <div class="card feature-card h-100 p-4 p-md-5">
                    <div class="d-flex align-items-center mb-4">
                        <span class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 fs-3 me-3"><i class="bi bi-buildings-fill"></i></span>
                        <div>
                            <h4 class="fw-bold mb-0">{{ $isBn && $ind->title_bn ? $ind->title_bn : $ind->title_en }}</h4>
                            <small class="text-muted">{{ $isBn ? 'কর্পোরেট সমাধান' : 'Enterprise Technology Focus' }}</small>
                        </div>
                    </div>
                    <p class="text-muted lh-lg mb-4">{{ $isBn && $ind->description_bn ? $ind->description_bn : $ind->description_en }}</p>
                    <div class="mt-auto">
                        <a href="{{ route('portfolio.index', ['locale' => app()->getLocale(), 'industry' => $ind->id]) }}" class="btn btn-outline-primary rounded-pill btn-sm">
                            {{ $isBn ? 'কেস স্টাডি দেখুন' : 'View Relevant Case Studies' }} <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection

