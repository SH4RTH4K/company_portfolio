@extends('admin.layouts.admin')

@section('title', 'Add Service')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">Add Service</h5>
        <a href="{{ route('admin.services.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.services.store') }}" method="POST">
            @csrf
            
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Business Division</label>
                    <select name="business_division_id" class="form-select @error('business_division_id') is-invalid @enderror">
                        <option value="">None</option>
                        @foreach($divisions as $division)
                            <option value="{{ $division->id }}" {{ old('business_division_id') == $division->id ? 'selected' : '' }}>
                                {{ $division->name_en }}
                            </option>
                        @endforeach
                    </select>
                    @error('business_division_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Title (EN) <span class="text-danger">*</span></label>
                    <input type="text" name="title_en" class="form-control @error('title_en') is-invalid @enderror" value="{{ old('title_en') }}" required>
                    @error('title_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Title (BN)</label>
                    <input type="text" name="title_bn" class="form-control @error('title_bn') is-invalid @enderror" value="{{ old('title_bn') }}">
                    @error('title_bn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Short Description (EN)</label>
                    <textarea name="short_description_en" class="form-control @error('short_description_en') is-invalid @enderror" rows="3">{{ old('short_description_en') }}</textarea>
                    @error('short_description_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Short Description (BN)</label>
                    <textarea name="short_description_bn" class="form-control @error('short_description_bn') is-invalid @enderror" rows="3">{{ old('short_description_bn') }}</textarea>
                    @error('short_description_bn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Description (EN)</label>
                    <textarea name="description_en" class="form-control @error('description_en') is-invalid @enderror" rows="5">{{ old('description_en') }}</textarea>
                    @error('description_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Description (BN)</label>
                    <textarea name="description_bn" class="form-control @error('description_bn') is-invalid @enderror" rows="5">{{ old('description_bn') }}</textarea>
                    @error('description_bn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror">
                        <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="published" {{ old('status', 'published') == 'published' ? 'selected' : '' }}>Published</option>
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Display Order</label>
                    <input type="number" name="display_order" class="form-control @error('display_order') is-invalid @enderror" value="{{ old('display_order', 0) }}">
                    @error('display_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input type="hidden" name="is_featured" value="0">
                        <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_featured">
                            Featured
                        </label>
                    </div>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">
                            Active
                        </label>
                    </div>
                </div>
            </div>

            <div class="card mt-4 mb-4 border border-light bg-light">
                <div class="card-body">
                    <h5 class="card-title text-muted mb-3"><i class="bi bi-search me-2"></i> SEO Settings</h5>
                    @include('admin.partials.seo-meta-fields')
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save Service</button>
        </form>
    </div>
</div>
@endsection

