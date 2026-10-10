@extends('admin.layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card bg-primary text-white h-100 shadow-sm border-0">
            <div class="card-body">
                <h6 class="card-title text-uppercase text-white-50">Total Divisions</h6>
                <h2 class="display-5 fw-bold mb-0">4</h2>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card bg-success text-white h-100 shadow-sm border-0">
            <div class="card-body">
                <h6 class="card-title text-uppercase text-white-50">Total Services</h6>
                <h2 class="display-5 fw-bold mb-0">0</h2>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card bg-info text-white h-100 shadow-sm border-0">
            <div class="card-body">
                <h6 class="card-title text-uppercase text-white-50">Contact Messages</h6>
                <h2 class="display-5 fw-bold mb-0">0</h2>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card bg-warning text-white h-100 shadow-sm border-0">
            <div class="card-body">
                <h6 class="card-title text-uppercase text-white-50">Total Visitors</h6>
                <h2 class="display-5 fw-bold mb-0">0</h2>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mt-2">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold">Recent Activity</h5>
    </div>
    <div class="card-body text-center py-5 text-muted">
        <p>No recent activity.</p>
    </div>
</div>
@endsection

