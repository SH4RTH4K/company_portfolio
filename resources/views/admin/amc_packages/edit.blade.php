@extends('admin.layouts.admin')
@section('title', 'Edit Package')
@section('content')
<div class="card border-0 shadow-sm">
<div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
<h5 class="mb-0 fw-bold">Edit Package</h5>
<a href="{{ route('admin.amc-packages.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
</div>
<div class="card-body">
<form action="{{ route('admin.amc-packages.update', $amcPackage) }}" method="POST">
@csrf
@method('PUT')
<div class="row mb-3">
<div class="col-md-6">
<label class="form-label">Name (EN) *</label>
<input type="text" name="name_en" class="form-control" value="{{ $amcPackage->name_en }}" required>
</div>
<div class="col-md-6">
<label class="form-label">Name (BN)</label>
<input type="text" name="name_bn" class="form-control" value="{{ $amcPackage->name_bn }}">
</div>
</div>
<div class="row mb-3">
<div class="col-md-4">
<label class="form-label">Price</label>
<input type="number" name="price" step="0.01" class="form-control" value="{{ $amcPackage->price }}">
</div>
<div class="col-md-4">
<label class="form-label">Billing Cycle</label>
<input type="text" name="billing_cycle" class="form-control" value="{{ $amcPackage->billing_cycle }}">
</div>
<div class="col-md-4">
<label class="form-label">Order</label>
<input type="number" name="display_order" class="form-control" value="{{ $amcPackage->display_order }}">
</div>
</div>
<div class="row mb-3">
<div class="col-md-6 d-flex align-items-end">
<div class="form-check mb-2 me-3">
<input type="hidden" name="is_active" value="0">
<input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $amcPackage->is_active ? 'checked' : '' }}>
<label class="form-check-label">Active</label>
</div>
<div class="form-check mb-2">
<input type="hidden" name="is_recommended" value="0">
<input class="form-check-input" type="checkbox" name="is_recommended" value="1" {{ $amcPackage->is_recommended ? 'checked' : '' }}>
<label class="form-check-label">Recommended</label>
</div>
</div>
</div>
<button type="submit" class="btn btn-primary">Update</button>
</form>
</div>
</div>
@endsection