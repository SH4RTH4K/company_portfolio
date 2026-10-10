@extends('layouts.app')

@php
    $isBn = app()->getLocale() === 'bn';
@endphp

@section('meta_title', $isBn ? 'এন্টারপ্রাইজ হার্ডওয়্যার ও পণ্যসমূহ' : 'Enterprise Hardware & Products')
@section('meta_description', $isBn ? 'ব্র্যান্ডেড সার্ভার, নেটওয়ার্কিং ইকুইপমেন্ট ও ফায়ারওয়াল ক্যাটালগ।' : 'Authorized enterprise hardware catalog: Dell, Cisco, Fortinet servers, routers, and firewalls.')

@section('content')
<!-- Header Banner -->
<div class="bg-navy text-white py-5">
    <div class="container py-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home', ['locale' => app()->getLocale()]) }}" class="text-accent text-decoration-none">{{ $isBn ? 'হোম' : 'Home' }}</a></li>
                <li class="breadcrumb-item active text-white-50" aria-current="page">{{ $isBn ? 'পণ্যসমূহ' : 'Products' }}</li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold">{{ $isBn ? 'এন্টারপ্রাইজ হার্ডওয়্যার ক্যাটালগ' : 'Enterprise Hardware Catalog' }}</h1>
        <p class="lead text-white-50 mb-0">{{ $isBn ? 'আন্তর্জাতিক ব্র্যান্ডের অনুমোদিত সার্ভার, ফায়ারওয়াল ও নেটওয়ার্কিং গিয়ার' : 'Genuine corporate hardware supply with official warranty and deployment support' }}</p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4">
        <!-- Sidebar Filters -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm p-4 rounded-4 mb-4">
                <h5 class="fw-bold mb-3">{{ $isBn ? 'ক্যাটাগরি' : 'Categories' }}</h5>
                <div class="list-group list-group-flush">
                    <a href="{{ route('products.index', ['locale' => app()->getLocale()]) }}" class="list-group-item list-group-item-action border-0 px-0 {{ !request('category') ? 'fw-bold text-primary' : 'text-muted' }}">
                        {{ $isBn ? 'সকল ক্যাটাগরি' : 'All Categories' }}
                    </a>
                    @foreach($categories as $cat)
                        <a href="{{ route('products.index', ['locale' => app()->getLocale(), 'category' => $cat->id, 'brand' => request('brand')]) }}" class="list-group-item list-group-item-action border-0 px-0 {{ request('category') == $cat->id ? 'fw-bold text-primary' : 'text-muted' }}">
                            {{ $isBn && $cat->name_bn ? $cat->name_bn : $cat->name_en }}
                        </a>
                    @endforeach
                </div>

                <hr>

                <h5 class="fw-bold mb-3">{{ $isBn ? 'ব্র্যান্ডসমূহ' : 'Brands' }}</h5>
                <div class="list-group list-group-flush">
                    <a href="{{ route('products.index', ['locale' => app()->getLocale()]) }}" class="list-group-item list-group-item-action border-0 px-0 {{ !request('brand') ? 'fw-bold text-primary' : 'text-muted' }}">
                        {{ $isBn ? 'সকল ব্র্যান্ড' : 'All Brands' }}
                    </a>
                    @foreach($brands as $brand)
                        <a href="{{ route('products.index', ['locale' => app()->getLocale(), 'brand' => $brand->id, 'category' => request('category')]) }}" class="list-group-item list-group-item-action border-0 px-0 {{ request('brand') == $brand->id ? 'fw-bold text-primary' : 'text-muted' }}">
                            {{ $isBn && $brand->name_bn ? $brand->name_bn : $brand->name_en }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="col-lg-9">
            <div class="row g-4">
                @forelse($products as $prod)
                    <div class="col-md-6 col-lg-4">
                        <div class="card feature-card h-100 p-3 d-flex flex-column">
                            <div class="bg-light rounded-3 text-center p-4 mb-3 d-flex align-items-center justify-content-center" style="height: 160px;">
                                <i class="bi bi-hdd-network text-primary" style="font-size: 3.5rem;"></i>
                            </div>
                            <span class="badge bg-secondary align-self-start mb-2">{{ $prod->brand->name_en ?? 'Brand' }}</span>
                            <h6 class="fw-bold mb-2">
                                <a href="{{ route('products.show', ['locale' => app()->getLocale(), 'slug' => $isBn && $prod->slug_bn ? $prod->slug_bn : $prod->slug_en]) }}" class="text-dark text-decoration-none">
                                    {{ $isBn && $prod->product_name_bn ? $prod->product_name_bn : $prod->product_name_en }}
                                </a>
                            </h6>
                            <p class="small text-muted mb-3">{{ Str::limit($isBn && $prod->short_description_bn ? $prod->short_description_bn : $prod->short_description_en, 80) }}</p>
                            
                            <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                                @if($prod->show_price && $prod->price)
                                    <span class="fw-bold text-primary">BDT {{ number_format($prod->price) }}</span>
                                @else
                                    <span class="badge bg-info text-dark">{{ $isBn ? 'মূল্য জানতে চান' : 'Price on Request' }}</span>
                                @endif
                                <a href="{{ route('products.show', ['locale' => app()->getLocale(), 'slug' => $isBn && $prod->slug_bn ? $prod->slug_bn : $prod->slug_en]) }}" class="btn btn-sm btn-outline-primary rounded-pill">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-5 text-muted">
                        <p>{{ $isBn ? 'কোন পণ্য পাওয়া যায়নি।' : 'No products found.' }}</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-5 d-flex justify-content-center">
                {{ $products->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@endsection

