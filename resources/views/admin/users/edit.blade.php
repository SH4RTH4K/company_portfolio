@extends('admin.layouts.admin')

@section('title', 'Edit User')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">Edit User Account: {{ $user->name }}</h5>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.users.update', $user) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">User Name <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username', $user->username) }}" required>
                    <div class="form-text">Used to login. Use letters, numbers, dashes, or underscores.</div>
                    @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Role & Access Level <span class="text-danger">*</span></label>
                    <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                        @foreach($roles as $val => $label)
                            <option value="{{ $val }}" {{ old('role', $user->role) == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="form-check form-switch mb-2">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="is_active">Account Active</label>
                    </div>
                </div>
            </div>

            <div class="card bg-light border-0 p-3 mb-4 rounded-3">
                <h6 class="fw-bold mb-2">Change Password (leave blank to keep current password)</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small">New Password</label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" minlength="8">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="form-control" minlength="8">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Update User Account</button>
        </form>
    </div>
</div>
@endsection

