@extends('admin.layouts.admin')

@section('title', 'Cache Management')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h4 class="mb-4"><i class="bi bi-hdd-network text-primary me-2"></i> Cache Management</h4>
        
        <div class="row g-4">
            <!-- Application Cache -->
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex p-3 mb-3">
                            <i class="bi bi-database fs-3 text-primary"></i>
                        </div>
                        <h5 class="fw-bold">Application Cache</h5>
                        <p class="text-muted small mb-4">Clears the application's cache containing cached database queries, cache tags, etc.</p>
                        <form action="{{ route('admin.cache.clear') }}" method="POST">
                            @csrf
                            <input type="hidden" name="type" value="application">
                            <button type="submit" class="btn btn-outline-primary w-100">Clear App Cache</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- View Cache -->
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex p-3 mb-3">
                            <i class="bi bi-window fs-3 text-success"></i>
                        </div>
                        <h5 class="fw-bold">View Cache</h5>
                        <p class="text-muted small mb-4">Clears the compiled view files. Use this if your recent UI changes are not showing up.</p>
                        <form action="{{ route('admin.cache.clear') }}" method="POST">
                            @csrf
                            <input type="hidden" name="type" value="views">
                            <button type="submit" class="btn btn-outline-success w-100">Clear View Cache</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Route Cache -->
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <div class="bg-info bg-opacity-10 rounded-circle d-inline-flex p-3 mb-3">
                            <i class="bi bi-signpost-split fs-3 text-info"></i>
                        </div>
                        <h5 class="fw-bold">Route Cache</h5>
                        <p class="text-muted small mb-4">Clears the route cache. Use this if you have recently added or modified routes.</p>
                        <form action="{{ route('admin.cache.clear') }}" method="POST">
                            @csrf
                            <input type="hidden" name="type" value="routes">
                            <button type="submit" class="btn btn-outline-info w-100">Clear Route Cache</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Config Cache -->
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex p-3 mb-3">
                            <i class="bi bi-gear fs-3 text-warning"></i>
                        </div>
                        <h5 class="fw-bold">Config Cache</h5>
                        <p class="text-muted small mb-4">Clears the configuration cache. Use this if you have modified your .env or config files.</p>
                        <form action="{{ route('admin.cache.clear') }}" method="POST">
                            @csrf
                            <input type="hidden" name="type" value="config">
                            <button type="submit" class="btn btn-outline-warning w-100">Clear Config Cache</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Clear All -->
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100 border-danger">
                    <div class="card-body text-center p-4">
                        <div class="bg-danger bg-opacity-10 rounded-circle d-inline-flex p-3 mb-3">
                            <i class="bi bi-exclamation-triangle fs-3 text-danger"></i>
                        </div>
                        <h5 class="fw-bold text-danger">Clear All Caches</h5>
                        <p class="text-muted small mb-4">Runs optimize:clear to clear all Laravel caches (app, views, routes, config) at once.</p>
                        <form action="{{ route('admin.cache.clear') }}" method="POST" onsubmit="return confirm('Are you sure you want to clear all caches?');">
                            @csrf
                            <input type="hidden" name="type" value="all">
                            <button type="submit" class="btn btn-danger w-100">Clear All</button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

