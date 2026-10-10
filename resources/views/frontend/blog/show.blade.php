@extends('layouts.app')

@php
    $isBn = app()->getLocale() === 'bn';
    $title = $isBn && $post->title_bn ? $post->title_bn : $post->title_en;
    $seo = $post->seoMeta;
@endphp

@section('meta_title', $seo && $seo->seo_title_en ? ($isBn && $seo->seo_title_bn ? $seo->seo_title_bn : $seo->seo_title_en) : $title)
@section('meta_description', $seo && $seo->seo_description_en ? ($isBn && $seo->seo_description_bn ? $seo->seo_description_bn : $seo->seo_description_en) : ($isBn ? $post->excerpt_bn : $post->excerpt_en))
@section('og_type', 'article')

@section('content')
<!-- Header Banner -->
<div class="bg-navy text-white py-5">
    <div class="container py-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home', ['locale' => app()->getLocale()]) }}" class="text-accent text-decoration-none">{{ $isBn ? 'হোম' : 'Home' }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('blog.index', ['locale' => app()->getLocale()]) }}" class="text-accent text-decoration-none">{{ $isBn ? 'ব্লগ' : 'Insights' }}</a></li>
                <li class="breadcrumb-item active text-white-50" aria-current="page">{{ Str::limit($title, 35) }}</li>
            </ol>
        </nav>
        <span class="badge bg-primary px-3 py-2 rounded-pill mb-2">{{ $post->category->name_en ?? 'Article' }}</span>
        <h1 class="display-5 fw-bold">{{ $title }}</h1>
        <div class="d-flex align-items-center text-white-50 mt-3 small">
            <span class="me-3"><i class="bi bi-person-circle me-1 text-accent"></i> {{ $post->author->name ?? 'Zero Gravities Engineering' }}</span>
            <span><i class="bi bi-calendar3 me-1 text-accent"></i> {{ $post->published_at ? $post->published_at->format('F d, Y') : '' }}</span>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="row g-5">
        <!-- Article Content -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4 p-md-5 rounded-4 mb-5">
                @if($post->excerpt_en || $post->excerpt_bn)
                    <div class="lead text-dark fst-italic border-start border-primary border-4 ps-3 py-1 mb-4">
                        {{ $isBn && $post->excerpt_bn ? $post->excerpt_bn : $post->excerpt_en }}
                    </div>
                @endif

                <div class="article-content lh-lg text-muted fs-5">
                    {!! $isBn && $post->content_bn ? $post->content_bn : $post->content_en !!}
                </div>

                @if($post->tags)
                    <div class="mt-5 pt-4 border-top">
                        <strong class="me-2 text-dark">{{ $isBn ? 'ট্যাগস:' : 'Tags:' }}</strong>
                        @foreach(explode(',', $post->tags) as $tag)
                            <span class="badge bg-light text-dark border me-1">{{ trim($tag) }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            @if($recentPosts->count() > 0)
                <div class="card border-0 shadow-sm p-4 rounded-4 mb-4">
                    <h5 class="fw-bold mb-3">{{ $isBn ? 'সম্পর্কিত অন্যান্য আর্টিকেল' : 'Recent Insights' }}</h5>
                    <div class="list-group list-group-flush">
                        @foreach($recentPosts as $rec)
                            <a href="{{ route('blog.show', ['locale' => app()->getLocale(), 'slug' => $isBn && $rec->slug_bn ? $rec->slug_bn : $rec->slug_en]) }}" class="list-group-item list-group-item-action border-0 px-0 py-2">
                                <h6 class="fw-bold mb-1">{{ $isBn && $rec->title_bn ? $rec->title_bn : $rec->title_en }}</h6>
                                <small class="text-muted">{{ $rec->published_at ? $rec->published_at->format('M d, Y') : '' }}</small>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="card border-0 shadow-sm p-4 rounded-4 bg-navy text-white text-center">
                <h5 class="fw-bold text-accent mb-2">{{ $isBn ? 'আইটি কনসালটেশন প্রয়োজন?' : 'Need Engineering Help?' }}</h5>
                <p class="small text-white-50 mb-4">{{ $isBn ? 'আমাদের অভিজ্ঞ সলিউশন আর্কিটেক্টদের সাথে এখনই কথা বলুন।' : 'Talk to our senior architects regarding custom software and infrastructure solutions.' }}</p>
                <a href="{{ route('contact', ['locale' => app()->getLocale()]) }}" class="btn btn-accent rounded-pill px-4 fw-bold">
                    {{ $isBn ? 'যোগাযোগ করুন' : 'Contact Us' }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

