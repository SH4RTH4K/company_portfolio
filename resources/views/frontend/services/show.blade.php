@extends('layouts.app')

@php
    $isBn = app()->getLocale() === 'bn';
    $title = $isBn && $service->title_bn ? $service->title_bn : $service->title_en;
    $seo = $service->seoMeta;
@endphp

@section('meta_title', $seo && $seo->seo_title_en ? ($isBn && $seo->seo_title_bn ? $seo->seo_title_bn : $seo->seo_title_en) : $title)
@section('meta_description', $seo && $seo->seo_description_en ? ($isBn && $seo->seo_description_bn ? $seo->seo_description_bn : $seo->seo_description_en) : ($isBn ? $service->short_description_bn : $service->short_description_en))

@section('content')
<!-- Header Banner -->
<div class="bg-navy text-white py-5">
    <div class="container py-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home', ['locale' => app()->getLocale()]) }}" class="text-accent text-decoration-none">{{ $isBn ? 'হোম' : 'Home' }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('services.index', ['locale' => app()->getLocale()]) }}" class="text-accent text-decoration-none">{{ $isBn ? 'সেবাসমূহ' : 'Services' }}</a></li>
                <li class="breadcrumb-item active text-white-50" aria-current="page">{{ $title }}</li>
            </ol>
        </nav>
        <span class="badge bg-primary px-3 py-2 rounded-pill mb-2">{{ $service->businessDivision->name_en ?? 'IT Solutions' }}</span>
        <h1 class="display-5 fw-bold">{{ $title }}</h1>
        <p class="lead text-white-50 mb-0">{{ $isBn && $service->short_description_bn ? $service->short_description_bn : $service->short_description_en }}</p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-5">
        <!-- Main Content -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4 p-md-5 rounded-4 mb-5">
                <h3 class="fw-bold mb-4">{{ $isBn ? 'সেবার বিস্তারিত বিবরণ' : 'Overview & Capabilities' }}</h3>
                <div class="lh-lg text-muted fs-5">
                    {!! nl2br(e($isBn && $service->description_bn ? $service->description_bn : $service->description_en)) !!}
                </div>

                <hr class="my-5">

                <div class="bg-light p-4 rounded-4">
                    <h4 class="fw-bold mb-2">{{ $isBn ? 'এই সেবার জন্য কাস্টম প্রপোজাল চান?' : 'Need a Tailored Proposal?' }}</h4>
                    <p class="text-muted mb-4">{{ $isBn ? 'আমাদের টেকনিক্যাল আর্কিটেক্ট টিম আপনার বর্তমান সিস্টেম পর্যালোচনা করে উপযুক্ত সমাধান প্রস্তাব করবে।' : 'Our senior solution architects are ready to audit your requirements and provide an itemized SLA and cost proposal.' }}</p>
                    <a href="{{ route('contact', ['locale' => app()->getLocale(), 'service' => $service->title_en]) }}" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-send-fill me-1"></i> {{ $isBn ? 'কোটেশনের আবেদন করুন' : 'Request Technical Proposal' }}
                    </a>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4 rounded-4 mb-4 bg-navy text-white">
                <h5 class="fw-bold mb-3 text-accent"><i class="bi bi-telephone-inbound-fill me-2"></i> {{ $isBn ? 'সরাসরি কথা বলুন' : 'Speak to an Engineer' }}</h5>
                <p class="small text-white-50 mb-3">{{ $isBn ? 'যেকোনো আইটি জরুরি প্রয়োজনে আমাদের হেল্পডেস্কে কল করুন।' : 'Direct hotline for urgent enterprise queries and SLA consultations.' }}</p>
                <h4 class="fw-bold text-white mb-3">{{ $globalSettings['company_phone'] ?? '+880 1800-000000' }}</h4>
                <a href="{{ route('contact', ['locale' => app()->getLocale()]) }}" class="btn btn-accent rounded-pill w-100 fw-bold">
                    {{ $isBn ? 'যোগাযোগ পেজে যান' : 'Schedule Call' }}
                </a>
            </div>

            @if($relatedServices->count() > 0)
                <div class="card border-0 shadow-sm p-4 rounded-4">
                    <h5 class="fw-bold mb-3">{{ $isBn ? 'সম্পর্কিত অন্যান্য সেবা' : 'Related Capabilities' }}</h5>
                    <div class="list-group list-group-flush">
                        @foreach($relatedServices as $rel)
                            <a href="{{ route('services.show', ['locale' => app()->getLocale(), 'slug' => $isBn && $rel->slug_bn ? $rel->slug_bn : $rel->slug_en]) }}" class="list-group-item list-group-item-action border-0 px-0 py-2 d-flex justify-content-between align-items-center">
                                <span>{{ $isBn && $rel->title_bn ? $rel->title_bn : $rel->title_en }}</span>
                                <i class="bi bi-chevron-right small text-muted"></i>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

