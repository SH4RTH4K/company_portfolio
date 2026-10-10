@extends('admin.layouts.admin')
@section('title', 'Edit brand')
@section('content')
<div class="card border-0 shadow-sm">
<div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
<h5 class="mb-0 fw-bold">Edit brand</h5>
<a href="{{ route('admin.product-brands.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
</div>
<div class="card-body">
<form action="{{ route('admin.product-brands.update', $productBrand) }}" method="POST">
@csrf
@method('PUT')
<div class="row mb-3">
<div class="col-md-6">
<label class="form-label">Name (EN) *</label>
<input type="text" name="name_en" class="form-control" value="{{ $productBrand->name_en }}" required>
</div>
<div class="col-md-6">
<label class="form-label">Name (BN)</label>
<input type="text" name="name_bn" class="form-control" value="{{ $productBrand->name_bn }}">
</div>
</div>
<div class="row mb-3">
<div class="col-md-6">
<label class="form-label">Order</label>
<input type="number" name="display_order" class="form-control" value="{{ $productBrand->display_order }}">
</div>
<div class="col-md-6 d-flex align-items-end">
<div class="form-check mb-2">
<input type="hidden" name="is_active" value="0">
<input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $productBrand->is_active ? 'checked' : '' }}>
<label class="form-check-label">Active</label>
</div>
</div>
</div>
<button type="submit" class="btn btn-primary">Update</button>
</form>
</div>
</div>
@endsection
