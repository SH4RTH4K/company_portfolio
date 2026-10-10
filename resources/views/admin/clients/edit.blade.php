@extends('admin.layouts.admin')

@section('title', 'Edit Client')

@section('content')
@php
    $clientLogoPreview = null;

    if (filled($client->logo)) {
        $clientLogoPreview = str($client->logo)->startsWith(['http://', 'https://', '/'])
            ? $client->logo
            : (str($client->logo)->startsWith('client-logos/') ? \Illuminate\Support\Facades\Storage::url($client->logo) : asset($client->logo));
    }
@endphp

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">Edit Client</h5>
        <a href="{{ route('admin.clients.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.clients.update', $client) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $client->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Website</label>
                    <input type="url" name="website" class="form-control @error('website') is-invalid @enderror" value="{{ old('website', $client->website) }}" placeholder="https://example.com">
                    @error('website')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Upload Logo</label>
                    @if($clientLogoPreview)
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <span class="client-logo-frame">
                                <img src="{{ $clientLogoPreview }}" alt="{{ $client->name }} logo">
                            </span>
                            <div class="form-check">
                                <input type="hidden" name="remove_logo" value="0">
                                <input type="checkbox" name="remove_logo" id="remove_logo" value="1" class="form-check-input">
                                <label class="form-check-label" for="remove_logo">Remove current logo</label>
                            </div>
                        </div>
                    @endif
                    <input type="file" name="logo_upload" class="form-control @error('logo_upload') is-invalid @enderror" accept="image/png,image/jpeg,image/webp,image/gif">
                    <div class="form-text">Use JPG, PNG, GIF, or WebP. Maximum 1 MB and 1200x600px. Uploading a new logo replaces the old uploaded file.</div>
                    @error('logo_upload')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Logo URL or path</label>
                    <input type="text" name="logo" class="form-control @error('logo') is-invalid @enderror" value="{{ old('logo', $client->logo) }}" placeholder="https://example.com/logo.png">
                    <div class="form-text">Optional. Uploading a file will override this value.</div>
                    @error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Description (EN)</label>
                    <textarea name="description_en" class="form-control @error('description_en') is-invalid @enderror" rows="5">{{ old('description_en', $client->description_en) }}</textarea>
                    <div class="form-text">Shown as a 2-line preview on client cards. Full text opens in Details.</div>
                    @error('description_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Description (BN)</label>
                    <textarea name="description_bn" class="form-control @error('description_bn') is-invalid @enderror" rows="5">{{ old('description_bn', $client->description_bn) }}</textarea>
                    <div class="form-text">Shown as a 2-line preview on client cards. Full text opens in Details.</div>
                    @error('description_bn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label">Display Order</label>
                    <input type="number" name="display_order" class="form-control @error('display_order') is-invalid @enderror" value="{{ old('display_order', $client->display_order) }}" min="0">
                    @error('display_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $client->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Update Client</button>
        </form>
    </div>
</div>
@endsection
