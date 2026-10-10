@extends('admin.layouts.admin')

@section('title', 'Edit Team Member')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">Edit Team Member</h5>
        <a href="{{ route('admin.teams.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.teams.update', $team) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Name (EN) <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $team->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Name (BN)</label>
                    <input type="text" name="name_bn" class="form-control @error('name_bn') is-invalid @enderror" value="{{ old('name_bn', $team->name_bn) }}">
                    @error('name_bn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Designation (EN)</label>
                    <input type="text" name="designation_en" class="form-control @error('designation_en') is-invalid @enderror" value="{{ old('designation_en', $team->designation_en) }}">
                    @error('designation_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Designation (BN)</label>
                    <input type="text" name="designation_bn" class="form-control @error('designation_bn') is-invalid @enderror" value="{{ old('designation_bn', $team->designation_bn) }}">
                    @error('designation_bn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Bio (EN)</label>
                    <textarea name="bio_en" rows="4" class="form-control @error('bio_en') is-invalid @enderror">{{ old('bio_en', $team->bio_en) }}</textarea>
                    @error('bio_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Bio (BN)</label>
                    <textarea name="bio_bn" rows="4" class="form-control @error('bio_bn') is-invalid @enderror">{{ old('bio_bn', $team->bio_bn) }}</textarea>
                    @error('bio_bn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Picture</label>
                @if($team->photo)
                    <div class="mb-2">
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($team->photo) }}" alt="{{ $team->name }}" style="width: 96px; height: 96px; object-fit: cover;" class="rounded border">
                    </div>
                    <div class="form-check mb-2">
                        <input type="hidden" name="remove_photo" value="0">
                        <input type="checkbox" name="remove_photo" id="remove_photo" value="1" class="form-check-input">
                        <label class="form-check-label" for="remove_photo">Remove current picture</label>
                    </div>
                @endif
                <input type="file" name="photo" class="form-control @error('photo') is-invalid @enderror" accept="image/png,image/jpeg,image/webp">
                <div class="form-text">Use JPG, PNG, or WebP. Maximum 2 MB. Uploading a new picture replaces the old one.</div>
                @error('photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $team->email) }}">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">LinkedIn URL</label>
                    <input type="url" name="linkedin" class="form-control @error('linkedin') is-invalid @enderror" value="{{ old('linkedin', $team->linkedin) }}">
                    @error('linkedin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Facebook URL</label>
                    <input type="url" name="facebook" class="form-control @error('facebook') is-invalid @enderror" value="{{ old('facebook', $team->facebook) }}">
                    @error('facebook')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">X / Twitter URL</label>
                    <input type="url" name="twitter" class="form-control @error('twitter') is-invalid @enderror" value="{{ old('twitter', $team->twitter) }}">
                    @error('twitter')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Personal Website URL</label>
                    <input type="url" name="website" class="form-control @error('website') is-invalid @enderror" value="{{ old('website', $team->website) }}">
                    @error('website')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">GitHub URL</label>
                    <input type="url" name="github" class="form-control @error('github') is-invalid @enderror" value="{{ old('github', $team->github) }}">
                    @error('github')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Instagram URL</label>
                    <input type="url" name="instagram" class="form-control @error('instagram') is-invalid @enderror" value="{{ old('instagram', $team->instagram) }}">
                    @error('instagram')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">YouTube URL</label>
                    <input type="url" name="youtube" class="form-control @error('youtube') is-invalid @enderror" value="{{ old('youtube', $team->youtube) }}">
                    @error('youtube')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">WhatsApp URL</label>
                    <input type="url" name="whatsapp" class="form-control @error('whatsapp') is-invalid @enderror" value="{{ old('whatsapp', $team->whatsapp) }}">
                    @error('whatsapp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Example: https://wa.me/8801400000000</div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Display Order</label>
                    <input type="number" name="display_order" class="form-control @error('display_order') is-invalid @enderror" value="{{ old('display_order', $team->display_order) }}">
                    @error('display_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="form-check form-switch mb-2">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" id="is_active" value="1" class="form-check-input" {{ old('is_active', $team->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="is_active">Active</label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Update</button>
        </form>
    </div>
</div>
@endsection
