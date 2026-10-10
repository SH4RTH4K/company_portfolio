@extends('admin.layouts.admin')
@section('title', 'Add partner')
@section('content')
<div class="card border-0 shadow-sm">
<div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
<h5 class="mb-0 fw-bold">Add partner</h5>
<a href="{{ route('admin.partners.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
</div>
<div class="card-body">
<form action="{{ route('admin.partners.store') }}" method="POST">
@csrf
<div class="mb-3"><label>Name *</label><input type="text" name="name" class="form-control" required></div>
<div class="mb-3"><label>Type *</label><select name="type" class="form-select" required><option value="Technology">Technology</option><option value="Brand">Brand</option><option value="Partner">Partner</option></select></div>
<div class="mb-3"><label>Website</label><input type="text" name="website" class="form-control"></div>
<div class="form-check mb-2"><input type="hidden" name="official_partner" value="0"><input type="checkbox" name="official_partner" value="1" class="form-check-input"><label class="form-check-label">Official Partner</label></div>
<div class="form-check mb-3"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" class="form-check-input" checked><label class="form-check-label">Active</label></div>
<button type="submit" class="btn btn-primary">Save</button>
</form>
</div>
</div>
@endsection