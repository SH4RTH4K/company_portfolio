@extends('admin.layouts.admin')

@section('title', 'Edit Role')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">Edit Role: {{ $role->name }}</h5>
        <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.roles.update', $role) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Role Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $role->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Slug <span class="text-danger">*</span></label>
                    <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $role->slug) }}" required {{ $role->is_system ? 'readonly' : '' }}>
                    @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @if($role->is_system)
                        <div class="form-text">System role slugs are locked because permissions depend on them.</div>
                    @endif
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $role->description) }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $role->sort_order) }}" min="0" max="65535">
                    @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="form-check form-switch mb-2">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $role->is_active) ? 'checked' : '' }} {{ $role->slug === 'super_admin' ? 'disabled' : '' }}>
                        <label class="form-check-label fw-semibold" for="is_active">Role Active</label>
                        @if($role->slug === 'super_admin')
                            <div class="form-text">Super Administrator must stay active.</div>
                        @endif
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Update Role</button>
        </form>
    </div>
</div>
@endsection
