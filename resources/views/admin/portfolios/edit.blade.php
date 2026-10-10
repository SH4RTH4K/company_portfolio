@extends('admin.layouts.admin')
@section('title', 'Edit Portfolio')
@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">Edit Portfolio Project</h5>
        <a href="{{ route('admin.portfolios.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.portfolios.update', $portfolio) }}" method="POST">
            @csrf @method('PUT')
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Project Name (EN) <span class="text-danger">*</span></label>
                    <input type="text" name="project_name_en" class="form-control @error('project_name_en') is-invalid @enderror" value="{{ old('project_name_en', $portfolio->project_name_en) }}" required>
                    @error('project_name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Project Name (BN)</label>
                    <input type="text" name="project_name_bn" class="form-control" value="{{ old('project_name_bn', $portfolio->project_name_bn) }}">
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Client Name</label>
                    <input type="text" name="client_name" class="form-control" value="{{ old('client_name', $portfolio->client_name) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Industry</label>
                    <select name="industry_id" class="form-select">
                        <option value="">None</option>
                        @foreach($industries as $industry)
                            <option value="{{ $industry->id }}" {{ old('industry_id', $portfolio->industry_id) == $industry->id ? 'selected' : '' }}>{{ $industry->title_en }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Project Category</label>
                    <select name="project_category_id" class="form-select">
                        <option value="">None</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('project_category_id', $portfolio->project_category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name_en }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Project Date</label>
                    <input type="date" name="project_date" class="form-control" value="{{ old('project_date', $portfolio->project_date ? $portfolio->project_date->format('Y-m-d') : '') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Project URL</label>
                    <input type="url" name="project_url" class="form-control" value="{{ old('project_url', $portfolio->project_url) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Technology Used</label>
                    <input type="text" name="technology_used" class="form-control" value="{{ old('technology_used', $portfolio->technology_used) }}" placeholder="e.g. Laravel, React">
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Short Description (EN)</label>
                    <textarea name="short_description_en" class="form-control" rows="3">{{ old('short_description_en', $portfolio->short_description_en) }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Short Description (BN)</label>
                    <textarea name="short_description_bn" class="form-control" rows="3">{{ old('short_description_bn', $portfolio->short_description_bn) }}</textarea>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Challenge (EN)</label>
                    <textarea name="challenge_en" class="form-control" rows="4">{{ old('challenge_en', $portfolio->challenge_en) }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Solution (EN)</label>
                    <textarea name="solution_en" class="form-control" rows="4">{{ old('solution_en', $portfolio->solution_en) }}</textarea>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Results (EN)</label>
                    <textarea name="results_en" class="form-control" rows="4">{{ old('results_en', $portfolio->results_en) }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Testimonial (EN)</label>
                    <textarea name="testimonial_en" class="form-control" rows="4">{{ old('testimonial_en', $portfolio->testimonial_en) }}</textarea>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="draft" {{ old('status', $portfolio->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="published" {{ old('status', $portfolio->status) === 'published' ? 'selected' : '' }}>Published</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Display Order</label>
                    <input type="number" name="display_order" class="form-control" value="{{ old('display_order', $portfolio->display_order) }}">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input type="hidden" name="is_featured" value="0">
                        <input class="form-check-input" type="checkbox" name="is_featured" value="1" {{ old('is_featured', $portfolio->is_featured) ? 'checked' : '' }}>
                        <label class="form-check-label">Featured</label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Update Portfolio</button>
        </form>
    </div>
</div>
@endsection

