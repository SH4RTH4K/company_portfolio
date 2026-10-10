@extends('admin.layouts.admin')

@section('title', 'Create Post Category')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Create Post Category</h4>
    <a href="{{ route('admin.post-categories.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('admin.post-categories.store') }}" method="POST">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Name (English) <span class="text-danger">*</span></label>
                    <input type="text" name="name_en" class="form-control @error('name_en') is-invalid @enderror"
                           value="{{ old('name_en') }}" required>
                    @error('name_en')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Name (Bangla)</label>
                    <input type="text" name="name_bn" class="form-control @error('name_bn') is-invalid @enderror"
                           value="{{ old('name_bn') }}">
                    @error('name_bn')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Display Order</label>
                    <input type="number" name="display_order" class="form-control @error('display_order') is-invalid @enderror"
                           value="{{ old('display_order', 0) }}" min="0">
                    @error('display_order')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                               {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Save Category
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
