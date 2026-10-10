@extends('admin.layouts.admin')
@section('title', 'Add FAQ')
@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">Add FAQ</h5>
        <a href="{{ route('admin.faqs.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif
        <form action="{{ route('admin.faqs.store') }}" method="POST">
            @csrf
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-medium">Question (EN) <span class="text-danger">*</span></label>
                    <input type="text" name="question_en" class="form-control" value="{{ old('question_en') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium">Question (BN)</label>
                    <input type="text" name="question_bn" class="form-control" value="{{ old('question_bn') }}">
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-medium">Answer (EN) <span class="text-danger">*</span></label>
                    <textarea name="answer_en" class="form-control" rows="5" required>{{ old('answer_en') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium">Answer (BN)</label>
                    <textarea name="answer_bn" class="form-control" rows="5">{{ old('answer_bn') }}</textarea>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-medium">Category</label>
                    <select name="category" class="form-select">
                        <option value="">— Global / No Category —</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-medium">Link to Service</label>
                    <select name="service_id" class="form-select">
                        <option value="">— None —</option>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}" {{ old('service_id') == $service->id ? 'selected' : '' }}>{{ $service->title_en }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-medium">Display Order</label>
                    <input type="number" name="display_order" class="form-control" value="{{ old('display_order', 0) }}" min="0">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-medium" for="is_active">Active</label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary px-4">Save FAQ</button>
        </form>
    </div>
</div>
@endsection

