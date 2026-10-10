@extends('layouts.app')

@php
    $isBn = app()->getLocale() === 'bn';
@endphp

@section('meta_title', $isBn ? 'কেস স্টাডি ও পোর্টফোলিও' : 'Case Studies & Portfolio')
@section('meta_description', $isBn ? 'জিরো গ্রাভিটিজ-এর সফল এন্টারপ্রাইজ প্রজেক্ট ও গ্রাহকদের সাফল্যের গল্প।' : 'Explore Zero Gravities successful enterprise case studies, infrastructure deployments, and software implementations.')

@section('content')
<!-- Header Banner -->
<div class="bg-navy text-white py-5">
    <div class="container py-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home', ['locale' => app()->getLocale()]) }}" class="text-accent text-decoration-none">{{ $isBn ? 'হোম' : 'Home' }}</a></li>
                <li class="breadcrumb-item active text-white-50" aria-current="page">{{ $isBn ? 'পোর্টফোলিও' : 'Portfolio' }}</li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold">{{ $isBn ? 'আমাদের সফল প্রকল্প ও কেস স্টাডি' : 'Enterprise Case Studies' }}</h1>
        <p class="lead text-white-50 mb-0">{{ $isBn ? 'প্রকৃত ব্যবসায়িক চ্যালেঞ্জ সমাধানে আমাদের কার্যকর প্রযুক্তির প্রয়োগ' : 'Real-world technical challenges transformed into measurable enterprise success' }}</p>
    </div>
</div>

<div class="container py-5">
    <!-- Industry Filter Pills -->
    <div class="d-flex flex-wrap gap-2 mb-5">
        <a href="{{ route('portfolio.index', ['locale' => app()->getLocale()]) }}" class="btn {{ !request('industry') ? 'btn-primary' : 'btn-outline-secondary' }} rounded-pill btn-sm px-3">
            {{ $isBn ? 'সকল প্রজেক্ট' : 'All Case Studies' }}
        </a>
        @foreach($industries as $ind)
            <a href="{{ route('portfolio.index', ['locale' => app()->getLocale(), 'industry' => $ind->id]) }}" class="btn {{ request('industry') == $ind->id ? 'btn-primary' : 'btn-outline-secondary' }} rounded-pill btn-sm px-3">
                {{ $isBn && $ind->title_bn ? $ind->title_bn : $ind->title_en }}
            </a>
        @endforeach
    </div>

    <!-- Portfolio Cards -->
    <div class="row g-4">
        @forelse($portfolios as $item)
            <div class="col-lg-6">
                <div class="card feature-card h-100 p-4 p-md-5">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-secondary">{{ $item->client_name }}</span>
                        <span class="badge bg-info text-dark">{{ $item->industry->title_en ?? 'Industry' }}</span>
                    </div>
                    
                    <h4 class="fw-bold mb-3">
                        <a href="{{ route('portfolio.show', ['locale' => app()->getLocale(), 'slug' => $isBn && $item->slug_bn ? $item->slug_bn : $item->slug_en]) }}" class="text-dark text-decoration-none">
                            {{ $isBn && $item->project_name_bn ? $item->project_name_bn : $item->project_name_en }}
                        </a>
                    </h4>

                    <p class="text-muted mb-4">{{ $isBn && $item->short_description_bn ? $item->short_description_bn : $item->short_description_en }}</p>

                    <div class="bg-light p-3 rounded-3 mb-4">
                        <strong class="text-success d-block mb-1"><i class="bi bi-trophy-fill me-1"></i> {{ $isBn ? 'ফলাফল ও অর্জন:' : 'Key Impact / ROI:' }}</strong>
                        <p class="small text-muted mb-0">{{ $isBn && $item->results_bn ? $item->results_bn : $item->results_en }}</p>
                    </div>

                    <a href="{{ route('portfolio.show', ['locale' => app()->getLocale(), 'slug' => $isBn && $item->slug_bn ? $item->slug_bn : $item->slug_en]) }}" class="btn btn-outline-primary rounded-pill btn-sm align-self-start mt-auto">
                        {{ $isBn ? 'সম্পূর্ণ কেস স্টাডি পড়ুন' : 'Read Full Case Study' }} <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5 text-muted">
                <p>{{ $isBn ? 'কোন কেস স্টাডি পাওয়া যায়নি।' : 'No case studies found.' }}</p>
            </div>
        @endforelse
    </div>

    <div class="mt-5 d-flex justify-content-center">
        {{ $portfolios->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection

