@extends('admin.layouts.admin')
@section('title', 'Add product')
@section('content')
<div class="card border-0 shadow-sm">
<div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
<h5 class="mb-0 fw-bold">Add product</h5>
<a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
</div>
<div class="card-body">
<form action="{{ route('admin.products.store') }}" method="POST">
@csrf
<div class="row mb-3">
<div class="col-md-6">
<label class="form-label">Product Name (EN) *</label>
<input type="text" name="product_name_en" class="form-control" required>
</div>
<div class="col-md-6">
<label class="form-label">Product Name (BN)</label>
<input type="text" name="product_name_bn" class="form-control">
</div>
</div>
<div class="row mb-3">
<div class="col-md-4">
<label class="form-label">Category</label>
<select name="category_id" class="form-select">
<option value="">None</option>
@foreach($categories as $category)
<option value="{{ $category->id }}">{{ $category->name_en }}</option>
@endforeach
</select>
</div>
<div class="col-md-4">
<label class="form-label">Brand</label>
<select name="brand_id" class="form-select">
<option value="">None</option>
@foreach($brands as $brand)
<option value="{{ $brand->id }}">{{ $brand->name_en }}</option>
@endforeach
</select>
</div>
<div class="col-md-4">
<label class="form-label">Price</label>
<input type="number" name="price" step="0.01" class="form-control">
</div>
</div>
<div class="row mb-3">
<div class="col-md-6">
<label class="form-label">Description (EN)</label>
<textarea name="description_en" class="form-control" rows="4"></textarea>
</div>
<div class="col-md-6">
<label class="form-label">Description (BN)</label>
<textarea name="description_bn" class="form-control" rows="4"></textarea>
</div>
</div>
<div class="row mb-3">
<div class="col-md-6">
<label class="form-label">Order</label>
<input type="number" name="display_order" class="form-control" value="0">
</div>
<div class="col-md-6 d-flex align-items-end">
<div class="form-check mb-2 me-3">
<input type="hidden" name="is_active" value="0">
<input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
<label class="form-check-label">Active</label>
</div>
<div class="form-check mb-2">
<input type="hidden" name="show_price" value="0">
<input class="form-check-input" type="checkbox" name="show_price" value="1" checked>
<label class="form-check-label">Show Price</label>
</div>
</div>
</div>

<div class="card mt-4 mb-4 border border-light bg-light">
    <div class="card-body">
        <h5 class="card-title text-muted mb-3"><i class="bi bi-search me-2"></i> SEO Settings</h5>
        @include('admin.partials.seo-meta-fields')
    </div>
</div>

<button type="submit" class="btn btn-primary">Save</button>
</form>
</div>
</div>
@endsection