@extends('admin.layouts.admin')

@section('title', 'SEO Dashboard')

@section('content')
<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">SEO Overview</h5>
            </div>
            <div class="card-body">
                <p>Welcome to the SEO Dashboard. From here you can manage global SEO settings, meta defaults, scripts, and monitor site health.</p>
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="card bg-light border-0">
                            <div class="card-body text-center py-4">
                                <i class="bi bi-robot fs-1 text-primary mb-3"></i>
                                <h5>Robots.txt</h5>
                                <a href="{{ route('admin.seo.robots') }}" class="btn btn-sm btn-outline-primary mt-2">Manage</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light border-0">
                            <div class="card-body text-center py-4">
                                <i class="bi bi-diagram-3 fs-1 text-success mb-3"></i>
                                <h5>XML Sitemap</h5>
                                <a href="/sitemap.xml" target="_blank" class="btn btn-sm btn-outline-success mt-2">View Sitemap</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light border-0">
                            <div class="card-body text-center py-4">
                                <i class="bi bi-code-slash fs-1 text-warning mb-3"></i>
                                <h5>Global Scripts</h5>
                                <a href="{{ route('admin.seo.scripts') }}" class="btn btn-sm btn-outline-warning mt-2">Manage Scripts</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

