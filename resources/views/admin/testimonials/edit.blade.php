@extends('admin.layouts.admin')

@section('title', 'Edit Testimonial')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">Edit Testimonial</h5>
        <a href="{{ route('admin.testimonials.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.testimonials.update', $testimonial) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $testimonial->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Company</label>
                    <input type="text" name="company" class="form-control @error('company') is-invalid @enderror" value="{{ old('company', $testimonial->company) }}">
                    @error('company')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Designation (EN)</label>
                    <input type="text" name="designation_en" class="form-control @error('designation_en') is-invalid @enderror" value="{{ old('designation_en', $testimonial->designation_en) }}">
                    @error('designation_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Designation (BN)</label>
                    <input type="text" name="designation_bn" class="form-control @error('designation_bn') is-invalid @enderror" value="{{ old('designation_bn', $testimonial->designation_bn) }}">
                    @error('designation_bn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Photo (URL or path)</label>
                    <input type="text" name="photo" class="form-control @error('photo') is-invalid @enderror" value="{{ old('photo', $testimonial->photo) }}">
                    @error('photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Company Logo (URL or path)</label>
                    <input type="text" name="company_logo" class="form-control @error('company_logo') is-invalid @enderror" value="{{ old('company_logo', $testimonial->company_logo) }}">
                    @error('company_logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Testimonial (EN) <span class="text-danger">*</span></label>
                    <textarea name="testimonial_en" class="form-control @error('testimonial_en') is-invalid @enderror" rows="5" required>{{ old('testimonial_en', $testimonial->testimonial_en) }}</textarea>
                    @error('testimonial_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Testimonial (BN)</label>
                    <textarea name="testimonial_bn" class="form-control @error('testimonial_bn') is-invalid @enderror" rows="5">{{ old('testimonial_bn', $testimonial->testimonial_bn) }}</textarea>
                    @error('testimonial_bn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-3">
                    <label class="form-label">Rating (1–5)</label>
                    <input type="number" name="rating" class="form-control @error('rating') is-invalid @enderror" value="{{ old('rating', $testimonial->rating) }}" min="1" max="5">
                    @error('rating')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Display Order</label>
                    <input type="number" name="display_order" class="form-control @error('display_order') is-invalid @enderror" value="{{ old('display_order', $testimonial->display_order) }}" min="0">
                    @error('display_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $testimonial->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Update Testimonial</button>
        </form>
    </div>
</div>
@endsection
