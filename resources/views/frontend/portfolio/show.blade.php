@extends('layouts.app')

@php
    $isBn = app()->getLocale() === 'bn';
    $title = $isBn && $portfolio->project_name_bn ? $portfolio->project_name_bn : $portfolio->project_name_en;
    $seo = $portfolio->seoMeta;
@endphp

@section('meta_title', $seo && $seo->seo_title_en ? ($isBn && $seo->seo_title_bn ? $seo->seo_title_bn : $seo->seo_title_en) : $title)
@section('meta_description', $seo && $seo->seo_description_en ? ($isBn && $seo->seo_description_bn ? $seo->seo_description_bn : $seo->seo_description_en) : ($isBn ? $portfolio->short_description_bn : $portfolio->short_description_en))

@section('content')
<!-- Header Banner -->
<div class="bg-navy text-white py-5">
    <div class="container py-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home', ['locale' => app()->getLocale()]) }}" class="text-accent text-decoration-none">{{ $isBn ? 'হোম' : 'Home' }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('portfolio.index', ['locale' => app()->getLocale()]) }}" class="text-accent text-decoration-none">{{ $isBn ? 'পোর্টফোলিও' : 'Portfolio' }}</a></li>
                <li class="breadcrumb-item active text-white-50" aria-current="page">{{ $title }}</li>
            </ol>
        </nav>
        <span class="badge bg-primary px-3 py-2 rounded-pill mb-2">{{ $portfolio->client_name }}</span>
        <h1 class="display-5 fw-bold">{{ $title }}</h1>
        <p class="lead text-white-50 mb-0">{{ $portfolio->industry->title_en ?? 'Industry' }}</p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-5">
        <!-- Main Case Study Breakdown -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4 p-md-5 rounded-4 mb-5">
                
                <!-- Overview -->
                <h4 class="fw-bold mb-3 text-primary">{{ $isBn ? 'প্রজেক্টের সারসংক্ষেপ' : 'Project Overview' }}</h4>
                <p class="fs-5 text-muted lh-lg mb-5">{{ $isBn && $portfolio->short_description_bn ? $portfolio->short_description_bn : $portfolio->short_description_en }}</p>

                <!-- Challenge -->
                <div class="card bg-light border-0 p-4 rounded-4 mb-4 border-start border-danger border-4">
                    <h5 class="fw-bold text-danger mb-2"><i class="bi bi-exclamation-octagon-fill me-2"></i> {{ $isBn ? 'ব্যবসায়িক ও প্রযুক্তিগত চ্যালেঞ্জ' : 'The Challenge' }}</h5>
                    <p class="text-muted mb-0 lh-lg">{{ $isBn && $portfolio->challenge_bn ? $portfolio->challenge_bn : $portfolio->challenge_en }}</p>
                </div>

                <!-- Solution -->
                <div class="card bg-light border-0 p-4 rounded-4 mb-4 border-start border-primary border-4">
                    <h5 class="fw-bold text-primary mb-2"><i class="bi bi-lightbulb-fill me-2"></i> {{ $isBn ? 'প্রদত্ত সমাধান ও আর্কিটেকচার' : 'The Engineered Solution' }}</h5>
                    <p class="text-muted mb-0 lh-lg">{{ $isBn && $portfolio->solution_en ? $portfolio->solution_en : $portfolio->solution_en }}</p>
                </div>

                <!-- Results -->
                <div class="card bg-light border-0 p-4 rounded-4 mb-4 border-start border-success border-4">
                    <h5 class="fw-bold text-success mb-2"><i class="bi bi-check-circle-fill me-2"></i> {{ $isBn ? 'পরিমাপযোগ্য ফলাফল ও অর্জন' : 'Business Impact & Key Results' }}</h5>
                    <p class="text-muted mb-0 lh-lg">{{ $isBn && $portfolio->results_bn ? $portfolio->results_bn : $portfolio->results_en }}</p>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4 rounded-4 mb-4">
                <h5 class="fw-bold mb-3">{{ $isBn ? 'প্রজেক্ট বিবরণ' : 'Project Details' }}</h5>
                <ul class="list-unstyled mb-0">
                    <li class="mb-3">
                        <strong class="d-block text-muted small">{{ $isBn ? 'ক্লায়েন্ট:' : 'Client Organization:' }}</strong>
                        <span class="fw-bold">{{ $portfolio->client_name }}</span>
                    </li>
                    <li class="mb-3">
                        <strong class="d-block text-muted small">{{ $isBn ? 'শিল্প খাত:' : 'Industry Sector:' }}</strong>
                        <span>{{ $portfolio->industry->title_en ?? 'N/A' }}</span>
                    </li>
                </ul>
            </div>

            <div class="card border-0 shadow-sm p-4 rounded-4 bg-navy text-white text-center">
                <h5 class="fw-bold text-accent mb-2">{{ $isBn ? 'অনুরূপ প্রযুক্তি সমাধান চান?' : 'Need Similar Scale & Architecture?' }}</h5>
                <p class="small text-white-50 mb-4">{{ $isBn ? 'আমাদের টিম আপনার প্রতিষ্ঠানের জন্য কাস্টমাইজড প্ল্যান তৈরি করে দেবে।' : 'Let us architect a battle-tested solution tailored to your operational scale.' }}</p>
                <a href="{{ route('contact', ['locale' => app()->getLocale(), 'service' => 'Case Study Inquiry: ' . $portfolio->project_name_en]) }}" class="btn btn-accent rounded-pill px-4 fw-bold">
                    {{ $isBn ? 'পরামর্শের জন্য যোগাযোগ' : 'Schedule Strategy Session' }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

