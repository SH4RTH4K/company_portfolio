@extends('layouts.app')

@php
    $isBn = app()->getLocale() === 'bn';
@endphp

@section('meta_title', $isBn ? 'আমাদের সম্পর্কে' : 'About Us')
@section('meta_description', $isBn ? 'জিরো গ্রাভিটিজ-এর মিশন, ভিশন, নেতৃত্ব এবং কর্পোরেট পরিচিতি।' : 'Learn about Zero Gravities: Mission, Vision, Core Values, and Leadership Team.')

@section('content')
<!-- Header Banner -->
<div class="bg-navy text-white py-5">
    <div class="container py-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home', ['locale' => app()->getLocale()]) }}" class="text-accent text-decoration-none">{{ $isBn ? 'হোম' : 'Home' }}</a></li>
                <li class="breadcrumb-item active text-white-50" aria-current="page">{{ $isBn ? 'আমাদের সম্পর্কে' : 'About Us' }}</li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold">{{ $isBn ? 'আমাদের পরিচিতি ও মিশন' : 'About Zero Gravities' }}</h1>
        <p class="lead text-white-50 mb-0">{{ $isBn ? 'প্রযুক্তি উদ্ভাবন ও নির্ভরযোগ্যতার সমন্বয়ে আগামীর সমাধান' : 'Driving Digital Transformation & Uncompromising Enterprise IT Reliability' }}</p>
    </div>
</div>

<!-- Mission / Vision / Values -->
<div class="container py-5">
    <div class="row g-5 align-items-center mb-5">
        <div class="col-lg-6">
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-semibold">{{ $isBn ? 'আমাদের গল্প' : 'Who We Are' }}</span>
            <h2 class="fw-bold mt-2 mb-3">{{ $isBn ? 'সীমাহীন প্রযুক্তি, আধুনিক ব্যবসায়িক সমাধান' : 'Technology Without Limits' }}</h2>
            <p class="text-muted">
                {{ $isBn ? 'জিরো গ্রাভিটিজ একটি আধুনিক প্রযুক্তি প্রতিষ্ঠান যা এন্টারপ্রাইজ সফটওয়্যার ডেভেলপমেন্ট, ক্লাউড আর্কিটেকচার এবং সার্বক্ষণিক ম্যানেজড আইটি সাপোর্ট (এএমসি) প্রদানে বিশেষজ্ঞ।' : 'Zero Gravities is a premier full-service technology company based in Dhaka, Bangladesh. We bridge the gap between complex engineering and real-world business results.' }}
            </p>
            <p class="text-muted">
                {{ $isBn ? 'আমাদের লক্ষ্য দেশের শীর্ষস্থানীয় প্রতিষ্ঠানগুলোকে আন্তর্জাতিক মানের সাইবার নিরাপত্তা ও স্থিতিশীল আইটি অবকাঠামো উপহার দেওয়া।' : 'From custom ERPs to mission-critical hardware deployments, we deliver solutions engineered for zero downtime and measurable return on investment.' }}
            </p>
        </div>
        <div class="col-lg-6">
            <div class="card bg-light border-0 p-4 rounded-4 shadow-sm">
                <div class="d-flex mb-4">
                    <span class="p-3 bg-primary text-white rounded-3 fs-4 me-3"><i class="bi bi-bullseye"></i></span>
                    <div>
                        <h5 class="fw-bold mb-1">{{ $isBn ? 'আমাদের মিশন' : 'Our Mission' }}</h5>
                        <p class="small text-muted mb-0">{{ $isBn ? 'প্রতিটি ক্লায়েন্টকে উচ্চমানের, নিরাপদ ও স্কেলেবল প্রযুক্তিগত সহায়তা দিয়ে তাদের ব্যবসায়িক লক্ষ্য অর্জন সহজ করা।' : 'To empower enterprises with flawless digital systems, unbreakable cybersecurity, and proactive support.' }}</p>
                    </div>
                </div>
                <div class="d-flex">
                    <span class="p-3 bg-accent text-white rounded-3 fs-4 me-3"><i class="bi bi-eye"></i></span>
                    <div>
                        <h5 class="fw-bold mb-1">{{ $isBn ? 'আমাদের ভিশন' : 'Our Vision' }}</h5>
                        <p class="small text-muted mb-0">{{ $isBn ? 'দক্ষিণ এশিয়ার সবচেয়ে বিশ্বস্ত ও নির্ভরযোগ্য আইটি ও এন্টারপ্রাইজ পার্টনার হিসেবে নিজেকে প্রতিষ্ঠা করা।' : 'To be recognized as the most trusted enterprise technology and managed services partner in the region.' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Leadership Team -->
    <div class="py-4">
        <div class="text-center mb-5">
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-semibold">{{ $isBn ? 'নেতৃত্ব' : 'Leadership' }}</span>
            <h2 class="fw-bold mt-2">{{ $isBn ? 'আমাদের এক্সপার্ট ম্যানেজমেন্ট টিম' : 'Meet Our Leadership' }}</h2>
        </div>

        <div class="row g-4 justify-content-center">
            @foreach($teams as $member)
                @php
                    $memberName = $isBn && $member->name_bn ? $member->name_bn : $member->name;
                    $hasSocialLinks = $member->email || $member->linkedin || $member->facebook || $member->twitter || $member->website || $member->github || $member->instagram || $member->youtube || $member->whatsapp;
                @endphp
                <div class="col-lg-4 col-md-6">
                    <div class="card feature-card h-100 text-center p-4">
                        @if($member->photo)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($member->photo) }}" alt="{{ $memberName }}" class="rounded-circle mx-auto mb-3" style="width: 100px; height: 100px; object-fit: cover;">
                        @else
                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary fw-bold fs-1 d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 100px; height: 100px;">
                                {{ mb_substr($memberName, 0, 1) }}
                            </div>
                        @endif
                        <h5 class="fw-bold mb-1">{{ $memberName }}</h5>
                        <p class="text-primary small fw-semibold mb-3">{{ $isBn && $member->designation_bn ? $member->designation_bn : $member->designation_en }}</p>
                        <p class="small text-muted mb-4">{{ $isBn && $member->bio_bn ? $member->bio_bn : $member->bio_en }}</p>
                        @if($hasSocialLinks)
                            <div class="mt-auto d-flex justify-content-center gap-2">
                                @if($member->linkedin)<a href="{{ $member->linkedin }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary rounded-circle" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>@endif
                                @if($member->facebook)<a href="{{ $member->facebook }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary rounded-circle" aria-label="Facebook"><i class="bi bi-facebook"></i></a>@endif
                                @if($member->twitter)<a href="{{ $member->twitter }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-dark rounded-circle" aria-label="X / Twitter"><i class="bi bi-twitter-x"></i></a>@endif
                                @if($member->website)<a href="{{ $member->website }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-info rounded-circle" aria-label="Website"><i class="bi bi-globe"></i></a>@endif
                                @if($member->github)<a href="{{ $member->github }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-dark rounded-circle" aria-label="GitHub"><i class="bi bi-github"></i></a>@endif
                                @if($member->instagram)<a href="{{ $member->instagram }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-danger rounded-circle" aria-label="Instagram"><i class="bi bi-instagram"></i></a>@endif
                                @if($member->youtube)<a href="{{ $member->youtube }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-danger rounded-circle" aria-label="YouTube"><i class="bi bi-youtube"></i></a>@endif
                                @if($member->whatsapp)<a href="{{ $member->whatsapp }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-success rounded-circle" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>@endif
                                @if($member->email)<a href="mailto:{{ $member->email }}" class="btn btn-sm btn-outline-secondary rounded-circle" aria-label="Email"><i class="bi bi-envelope"></i></a>@endif
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

