@extends('layouts.app')

@php
    $isBn = app()->getLocale() === 'bn';
@endphp

@section('meta_title', $isBn ? 'প্রযুক্তি ইনসাইটস ও ব্লগ' : 'Tech Insights & Articles')
@section('meta_description', $isBn ? 'এন্টারপ্রাইজ আইটি, ক্লাউড কম্পিউটিং ও সাইবার সিকিউরিটি সংক্রান্ত বিশ্লেষণ।' : 'Latest articles, guides, and engineering analysis on enterprise IT infrastructure, AMC, and cybersecurity.')

@section('content')
<!-- Header Banner -->
<div class="bg-navy text-white py-5">
    <div class="container py-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home', ['locale' => app()->getLocale()]) }}" class="text-accent text-decoration-none">{{ $isBn ? 'হোম' : 'Home' }}</a></li>
                <li class="breadcrumb-item active text-white-50" aria-current="page">{{ $isBn ? 'ব্লগ ও ইনসাইটস' : 'Insights' }}</li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold">{{ $isBn ? 'প্রযুক্তি ও ব্যবসায়িক ইনসাইটস' : 'Zero Gravities Insights' }}</h1>
        <p class="lead text-white-50 mb-0">{{ $isBn ? 'আইটি অবকাঠামো, এএমসি ও ডিজিটাল রূপান্তর সংক্রান্ত বিশ্লেষণ' : 'Actionable technical guides, market trends, and architecture deep-dives' }}</p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4">
        <!-- Main Blog Posts List -->
        <div class="col-lg-8">
            <div class="row g-4">
                @forelse($posts as $post)
                    <div class="col-md-6">
                        <div class="card feature-card h-100 d-flex flex-column">
                            <div class="card-body p-4 d-flex flex-column">
                                <span class="badge bg-primary bg-opacity-10 text-primary mb-2 align-self-start">{{ $post->category->name_en ?? 'Article' }}</span>
                                <h5 class="fw-bold mb-3">
                                    <a href="{{ route('blog.show', ['locale' => app()->getLocale(), 'slug' => $isBn && $post->slug_bn ? $post->slug_bn : $post->slug_en]) }}" class="text-dark text-decoration-none">
                                        {{ $isBn && $post->title_bn ? $post->title_bn : $post->title_en }}
                                    </a>
                                </h5>
                                <p class="text-muted small mb-4">{{ Str::limit($isBn && $post->excerpt_bn ? $post->excerpt_bn : $post->excerpt_en, 120) }}</p>
                                <div class="mt-auto d-flex justify-content-between align-items-center small text-muted border-top pt-3">
                                    <span><i class="bi bi-calendar3 me-1"></i> {{ $post->published_at ? $post->published_at->format('M d, Y') : '' }}</span>
                                    <a href="{{ route('blog.show', ['locale' => app()->getLocale(), 'slug' => $isBn && $post->slug_bn ? $post->slug_bn : $post->slug_en]) }}" class="text-primary fw-bold text-decoration-none">
                                        {{ $isBn ? 'পড়ুন' : 'Read' }} <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-5 text-muted">
                        <p>{{ $isBn ? 'কোন আর্টিকেল পাওয়া যায়নি।' : 'No articles found matching your query.' }}</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-5 d-flex justify-content-center">
                {{ $posts->links('pagination::bootstrap-5') }}
            </div>
        </div>

        <!-- Blog Sidebar -->
        <div class="col-lg-4">
            <!-- Search -->
            <div class="card border-0 shadow-sm p-4 rounded-4 mb-4">
                <h5 class="fw-bold mb-3">{{ $isBn ? 'অনুসন্ধান করুন' : 'Search Articles' }}</h5>
                <form action="{{ route('blog.index', ['locale' => app()->getLocale()]) }}" method="GET">
                    <div class="input-group">
                        <input type="text" name="q" class="form-control" placeholder="{{ $isBn ? 'কীওয়ার্ড লিখুন...' : 'Search topics...' }}" value="{{ request('q') }}">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                </form>
            </div>

            <!-- Categories -->
            <div class="card border-0 shadow-sm p-4 rounded-4 mb-4">
                <h5 class="fw-bold mb-3">{{ $isBn ? 'ক্যাটাগরি' : 'Categories' }}</h5>
                <div class="list-group list-group-flush">
                    <a href="{{ route('blog.index', ['locale' => app()->getLocale()]) }}" class="list-group-item list-group-item-action border-0 px-0 {{ !request('category') ? 'fw-bold text-primary' : 'text-muted' }}">
                        {{ $isBn ? 'সকল বিষয়' : 'All Categories' }}
                    </a>
                    @foreach($categories as $cat)
                        <a href="{{ route('blog.index', ['locale' => app()->getLocale(), 'category' => $cat->id]) }}" class="list-group-item list-group-item-action border-0 px-0 {{ request('category') == $cat->id ? 'fw-bold text-primary' : 'text-muted' }}">
                            {{ $isBn && $cat->name_bn ? $cat->name_bn : $cat->name_en }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

