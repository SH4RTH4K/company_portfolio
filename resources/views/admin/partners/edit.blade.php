@extends('admin.layouts.admin')
@section('title', 'Edit partner')
@section('content')
<div class="card border-0 shadow-sm">
<div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
<h5 class="mb-0 fw-bold">Edit partner</h5>
<a href="{{ route('admin.partners.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
</div>
<div class="card-body">
<form action="{{ route('admin.partners.update', $partner) }}" method="POST">
@csrf
@method('PUT')
<div class="mb-3"><label>Name *</label><input type="text" name="name" class="form-control" value="{{ $partner->name }}" required></div>
<div class="mb-3"><label>Type *</label><select name="type" class="form-select" required><option value="Technology" {{ $partner->type=='Technology' ? 'selected' : '' }}>Technology</option><option value="Brand" {{ $partner->type=='Brand' ? 'selected' : '' }}>Brand</option><option value="Partner" {{ $partner->type=='Partner' ? 'selected' : '' }}>Partner</option></select></div>
<div class="mb-3"><label>Website</label><input type="text" name="website" class="form-control" value="{{ $partner->website }}"></div>
<div class="form-check mb-2"><input type="hidden" name="official_partner" value="0"><input type="checkbox" name="official_partner" value="1" class="form-check-input" {{ $partner->official_partner ? 'checked' : '' }}><label class="form-check-label">Official Partner</label></div>
<div class="form-check mb-3"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" class="form-check-input" {{ $partner->is_active ? 'checked' : '' }}><label class="form-check-label">Active</label></div>
<button type="submit" class="btn btn-primary">Update</button>
</form>
</div>
</div>
@endsection