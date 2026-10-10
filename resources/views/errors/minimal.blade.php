@extends('layouts.app')

@section('title', $title ?? 'Error')

@section('content')
@php
    $homeLocale = app()->getLocale() === 'bn' ? 'bn' : 'en';
@endphp

<div class="container py-5 my-5 text-center">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <h1 class="display-1 fw-bold text-primary mb-3">@yield('code')</h1>
            <h2 class="h3 fw-bold mb-4">@yield('message')</h2>
            
            @if(app()->getLocale() == 'bn')
                <p class="text-muted mb-5">দুঃখিত, আপনি যে পৃষ্ঠাটি খুঁজছেন তা পাওয়া যাচ্ছে না বা একটি ত্রুটি ঘটেছে।</p>
                <a href="{{ route('home', ['locale' => $homeLocale]) }}" class="btn btn-primary btn-lg rounded-pill px-5">হোম পেজে ফিরে যান</a>
            @else
                <p class="text-muted mb-5">Sorry, the page you are looking for could not be found or an error occurred.</p>
                <a href="{{ route('home', ['locale' => $homeLocale]) }}" class="btn btn-primary btn-lg rounded-pill px-5">Back to Homepage</a>
            @endif
        </div>
    </div>
</div>
@endsection
