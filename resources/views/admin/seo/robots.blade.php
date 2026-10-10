@extends('admin.layouts.admin')

@section('title', 'Robots.txt Manager')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold">Manage Robots.txt</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.seo.robots.update') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label">Content of robots.txt</label>
                <textarea name="content" class="form-control font-monospace" rows="10">{{ $robotsTxt->value_en ?? "User-agent: *\nDisallow: /admin\n\nSitemap: " . url('/sitemap.xml') }}</textarea>
                <div class="form-text">This file tells search engine crawlers which pages or files they can or can't request from your site.</div>
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
</div>
@endsection

