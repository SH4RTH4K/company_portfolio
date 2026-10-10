@extends('admin.layouts.admin')
@section('title', 'Partners')
@section('content')
<div class="card border-0 shadow-sm">
<div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
<h5 class="mb-0 fw-bold">Partners</h5>
<a href="{{ route('admin.partners.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add New</a>
</div>
<div class="card-body">
<div class="table-responsive">
<table class="table table-hover align-middle">
<thead>
<tr>
<th>#</th>
<th>Name</th>
<th>Type</th>
<th>Official</th>
<th>Status</th>
<th width="150">Actions</th>
</tr>
</thead>
<tbody>
@forelse($partners as $partner)
<tr>
<td>{{ $loop->iteration }}</td>
<td>{{ $partner->name }}</td>
<td>{{ $partner->type }}</td>
<td><span class="badge bg-{{ $partner->official_partner ? 'info' : 'secondary' }}">{{ $partner->official_partner ? 'Yes' : 'No' }}</span></td>
<td><span class="badge bg-{{ $partner->is_active ? 'success' : 'danger' }}">{{ $partner->is_active ? 'Active' : 'Inactive' }}</span></td>
<td>
<a href="{{ route('admin.partners.edit', $partner) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
<form action="{{ route('admin.partners.destroy', $partner) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
@csrf
@method('DELETE')
<button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
</form>
</td>
</tr>
@empty
<tr><td colspan="10" class="text-center py-4">No records found.</td></tr>
@endforelse
</tbody>
</table>
</div>
</div>
</div>
@endsection