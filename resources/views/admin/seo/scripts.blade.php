@extends('admin.layouts.admin')

@section('title', 'Global Scripts')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold">Global Tracking & Scripts</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.seo.scripts.update') }}" method="POST">
            @csrf
            
            <div class="mb-4">
                <label class="form-label fw-bold">Header Scripts (&lt;head&gt;)</label>
                <textarea name="header_scripts" class="form-control font-monospace" rows="6" placeholder="<!-- Google Analytics, Meta Pixel, etc -->">{{ $headerScripts->value_en ?? '' }}</textarea>
                <div class="form-text">These scripts will be injected just before the closing <code>&lt;/head&gt;</code> tag on all public pages.</div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Footer Scripts (&lt;body&gt;)</label>
                <textarea name="footer_scripts" class="form-control font-monospace" rows="6" placeholder="<!-- Live Chat, Additional Tracking -->">{{ $footerScripts->value_en ?? '' }}</textarea>
                <div class="form-text">These scripts will be injected just before the closing <code>&lt;/body&gt;</code> tag on all public pages.</div>
            </div>

            <button type="submit" class="btn btn-primary">Save Scripts</button>
        </form>
    </div>
</div>
@endsection

