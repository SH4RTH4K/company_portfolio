@extends('admin.layouts.admin')
@section('title', 'Add Industry')
@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">Add Industry</h5>
        <a href="{{ route('admin.industries.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.industries.store') }}" method="POST">
            @csrf
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Title (EN) <span class="text-danger">*</span></label>
                    <input type="text" name="title_en" class="form-control @error('title_en') is-invalid @enderror" value="{{ old('title_en') }}" required>
                    @error('title_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Title (BN)</label>
                    <input type="text" name="title_bn" class="form-control" value="{{ old('title_bn') }}">
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Short Description (EN)</label>
                    <textarea name="short_description_en" class="form-control" rows="3">{{ old('short_description_en') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Short Description (BN)</label>
                    <textarea name="short_description_bn" class="form-control" rows="3">{{ old('short_description_bn') }}</textarea>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label">Display Order</label>
                    <input type="number" name="display_order" class="form-control" value="{{ old('display_order', 0) }}">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="form-check-label">Active</label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Save Industry</button>
        </form>
    </div>
</div>
@endsection

