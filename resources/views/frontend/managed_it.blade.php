@extends('layouts.app')

@php
    $isBn = app()->getLocale() === 'bn';
@endphp

@section('meta_title', $isBn ? 'ম্যানেজড আইটি ও বার্ষিক রক্ষণাবেক্ষণ চুক্তি (এএমসি)' : 'Managed IT & AMC Packages')
@section('meta_description', $isBn ? '২৪/৭ সার্বক্ষণিক আইটি সাপোর্ট, সার্ভার মনিটরিং ও নিশ্চিত এসএলএ এএমসি প্যাকেজ।' : 'Enterprise 24/7 Managed IT Services & Annual Maintenance Contracts (AMC) in Bangladesh.')

@section('content')
<!-- Header Banner -->
<div class="bg-navy text-white py-5">
    <div class="container py-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home', ['locale' => app()->getLocale()]) }}" class="text-accent text-decoration-none">{{ $isBn ? 'হোম' : 'Home' }}</a></li>
                <li class="breadcrumb-item active text-white-50" aria-current="page">{{ $isBn ? 'ম্যানেজড আইটি / এএমসি' : 'Managed IT / AMC' }}</li>
            </ol>
        </nav>
        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill mb-2 fw-bold">24/7/365 SLA GUARANTEED</span>
        <h1 class="display-5 fw-bold">{{ $isBn ? 'বার্ষিক আইটি রক্ষণাবেক্ষণ চুক্তি (এএমসি)' : 'Enterprise Managed IT & AMC Packages' }}</h1>
        <p class="lead text-white-50 mb-0">{{ $isBn ? 'আপনার ব্যবসার আইটি অবকাঠামোর সার্বিক দায়িত্ব নিন আমাদের অভিজ্ঞ প্রকৌশলীদের হাতে' : 'Proactive infrastructure maintenance, rapid on-site emergency response, and zero downtime.' }}</p>
    </div>
</div>

<div class="container py-5">
    <div class="text-center mb-5">
        <h2 class="fw-bold">{{ $isBn ? 'আপনার প্রতিষ্ঠানের উপযুক্ত এএমসি প্যাকেজ বেছে নিন' : 'Flexible SLA-Driven AMC Contracts' }}</h2>
        <p class="text-muted">{{ $isBn ? 'স্বচ্ছ মূল্য এবং নিশ্চিত রেসপন্স টাইমের সাথে কাস্টমাইজড সাপোর্ট প্ল্যান' : 'Transparent pricing with guaranteed response SLAs tailored to your infrastructure scale' }}</p>
    </div>

    <!-- Pricing Cards -->
    <div class="row g-4 mb-5 justify-content-center">
        @foreach($amcPackages as $pkg)
            <div class="col-lg-5 col-md-6">
                <div class="card h-100 p-4 p-md-5 rounded-4 {{ $pkg->is_recommended ? 'border-primary border-3 shadow-lg' : 'border-0 shadow-sm' }} position-relative">
                    @if($pkg->is_recommended)
                        <span class="position-absolute top-0 end-0 translate-middle-y badge bg-primary px-3 py-2 me-4 rounded-pill fs-6">{{ $isBn ? 'সর্বাধিক জনপ্রিয়' : 'Most Popular' }}</span>
                    @endif
                    <h4 class="fw-bold mb-2">{{ $isBn && $pkg->name_bn ? $pkg->name_bn : $pkg->name_en }}</h4>
                    <p class="text-muted small mb-4">{{ $isBn && $pkg->description_bn ? $pkg->description_bn : $pkg->description_en }}</p>
                    
                    <div class="bg-light p-3 rounded-3 mb-4">
                        <h2 class="fw-bold text-primary mb-0">{{ $isBn && $pkg->price_text_bn ? $pkg->price_text_bn : $pkg->price_text_en }}</h2>
                        <small class="text-muted">{{ $isBn ? 'মাসিক চুক্তিভিত্তিক বিলিং' : 'Billed Monthly / Annually with SLA contract' }}</small>
                    </div>

                    <h6 class="fw-bold mb-3">{{ $isBn ? 'প্যাকেজের অন্তর্ভুক্ত সেবাসমূহ:' : 'Included In This Plan:' }}</h6>
                    <ul class="list-unstyled mb-5">
                        @php $features = $isBn && !empty($pkg->features_bn) ? $pkg->features_bn : ($pkg->features_en ?? []); @endphp
                        @foreach($features as $feat)
                            <li class="mb-2 d-flex align-items-center">
                                <i class="bi bi-check-circle-fill text-success me-2 fs-5"></i>
                                <span>{{ $feat }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <a href="{{ route('contact', ['locale' => app()->getLocale(), 'service' => 'AMC Plan: ' . $pkg->name_en]) }}" class="btn {{ $pkg->is_recommended ? 'btn-primary' : 'btn-outline-primary' }} btn-lg rounded-pill w-100 mt-auto fw-bold">
                        {{ $isBn ? 'এই প্যাকেজটি নির্বাচন করুন' : 'Choose This Plan' }}
                    </a>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Why Managed IT Table / Highlights -->
    <div class="card bg-navy text-white p-5 rounded-4 border-0">
        <div class="row g-4 align-items-center">
            <div class="col-lg-6">
                <h3 class="fw-bold text-white mb-3">{{ $isBn ? 'কেন জিরো গ্রাভিটিজ এএমসি বেছে নেবেন?' : 'Why Partner With Zero Gravities for AMC?' }}</h3>
                <p class="text-white-50 mb-4">{{ $isBn ? 'আমরা শুধু আইটি সমস্যা সমাধান করি না, বরং সমস্যা তৈরি হওয়ার আগেই প্রো-অ্যাক্টিভ ব্যবস্থা গ্রহণ করি।' : 'Unlike reactive freelance support, our SLA contracts provide institutional stability and accountability for your critical systems.' }}</p>
                
                <div class="d-flex mb-3">
                    <i class="bi bi-clock-history text-accent fs-3 me-3"></i>
                    <div>
                        <h6 class="fw-bold mb-1">{{ $isBn ? 'অন-সাইট ও রিমোট দ্রুত রেসপন্স' : 'Guaranteed Fast Response Time' }}</h6>
                        <p class="small text-white-50 mb-0">{{ $isBn ? '১ থেকে ৪ ঘণ্টার মধ্যে জরুরি টেকনিক্যাল সহায়তা।' : 'Engineers dispatched on-site or connected remotely within strict timeframes.' }}</p>
                    </div>
                </div>

                <div class="d-flex mb-3">
                    <i class="bi bi-shield-lock-fill text-accent fs-3 me-3"></i>
                    <div>
                        <h6 class="fw-bold mb-1">{{ $isBn ? 'সার্ভার ও সাইবার সিকিউরিটি অডিট' : 'Proactive Security & Patching' }}</h6>
                        <p class="small text-white-50 mb-0">{{ $isBn ? 'নিয়মিত নিরাপত্তা পরীক্ষা ও অপারেটিং সিস্টেম আপডেট।' : 'Periodic vulnerability scans and routine backup restoration audits.' }}</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 text-center">
                <div class="bg-deep p-4 rounded-4 border border-secondary border-opacity-25 text-start">
                    <h5 class="fw-bold text-accent mb-3">{{ $isBn ? 'কাস্টম এন্টারপ্রাইজ এএমসি প্রয়োজন?' : 'Need a Custom Infrastructure Scope?' }}</h5>
                    <p class="text-white-50 small mb-4">{{ $isBn ? 'আপনার অফিসের কম্পিউটার ও সার্ভারের সংখ্যা অনুযায়ী বিশেষায়িত এএমসি প্রস্তাবনা প্রস্তুত করে দেব।' : 'Have 50+ workstations, multiple remote factory campuses, or specialized hybrid cloud requirements? We design customized SLA tier contracts.' }}</p>
                    <a href="{{ route('contact', ['locale' => app()->getLocale(), 'service' => 'Custom AMC Scope']) }}" class="btn btn-accent rounded-pill px-4">
                        {{ $isBn ? 'কাস্টম কোটেশনের জন্য যোগাযোগ' : 'Request Custom AMC Proposal' }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

