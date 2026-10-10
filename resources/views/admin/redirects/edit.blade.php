@extends('admin.layouts.admin')
@section('title', 'Edit Redirect')
@section('content')
<div class="card border-0 shadow-sm">
<div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
<h5 class="mb-0 fw-bold">Edit Redirect</h5>
<a href="{{ route('admin.redirects.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
</div>
<div class="card-body">
<form action="{{ route('admin.redirects.update', $redirect) }}" method="POST">
@csrf
@method('PUT')
<div class="mb-3"><label>Old URL *</label><input type="text" name="old_url" class="form-control" value="{{ $redirect->old_url }}" required></div>
<div class="mb-3"><label>New URL *</label><input type="text" name="new_url" class="form-control" value="{{ $redirect->new_url }}" required></div>
<div class="mb-3"><label>Redirect Type</label><select name="status_code" class="form-select"><option value="301" {{ $redirect->status_code == 301 ? 'selected' : '' }}>301 Moved Permanently</option><option value="302" {{ $redirect->status_code == 302 ? 'selected' : '' }}>302 Found (Temporary)</option></select></div>
<div class="form-check mb-3"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" class="form-check-input" {{ $redirect->is_active ? 'checked' : '' }}><label class="form-check-label">Active</label></div>
<button type="submit" class="btn btn-primary">Update</button>
</form>
</div>
</div>
@endsection