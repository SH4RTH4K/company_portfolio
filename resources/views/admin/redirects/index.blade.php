@extends('admin.layouts.admin')
@section('title', 'Redirect Manager')
@section('content')
<div class="card border-0 shadow-sm">
<div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
<h5 class="mb-0 fw-bold">Redirects</h5>
<a href="{{ route('admin.redirects.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add New</a>
</div>
<div class="card-body">
<div class="table-responsive">
<table class="table table-hover align-middle">
<thead>
<tr>
<th>#</th><th>Old URL</th><th>New URL</th><th>Status Code</th><th>Hits</th><th>Status</th><th width="150">Actions</th>
</tr>
</thead>
<tbody>
@forelse($redirects as $redirect)
<tr>
<td>{{ $loop->iteration }}</td>
<td>{{ $redirect->old_url }}</td>
<td>{{ $redirect->new_url }}</td>
<td><span class="badge bg-secondary">{{ $redirect->status_code }}</span></td>
<td>{{ $redirect->hits }}</td>
<td><span class="badge bg-{{ $redirect->is_active ? 'success' : 'danger' }}">{{ $redirect->is_active ? 'Active' : 'Inactive' }}</span></td>
<td>
<a href="{{ route('admin.redirects.edit', $redirect) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
<form action="{{ route('admin.redirects.destroy', $redirect) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
@csrf
@method('DELETE')
<button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
</form>
</td>
</tr>
@empty
<tr><td colspan="7" class="text-center py-4">No redirects found.</td></tr>
@endforelse
</tbody>
</table>
</div>
</div>
</div>
@endsection