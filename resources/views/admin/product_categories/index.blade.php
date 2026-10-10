@extends('admin.layouts.admin')
@section('title', 'Product Categories')
@section('content')
<div class="card border-0 shadow-sm">
<div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
<h5 class="mb-0 fw-bold">Product Categories</h5>
<a href="{{ route('admin.product-categories.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add New</a>
</div>
<div class="card-body">
<div class="table-responsive">
<table class="table table-hover align-middle">
<thead>
<tr>
<th>#</th>
<th>Name (EN)</th>
<th>Order</th>
<th>Status</th>
<th width="150">Actions</th>
</tr>
</thead>
<tbody>
@forelse($categories as $category)
<tr>
<td>{{ $loop->iteration }}</td>
<td>{{ $category->name_en }}</td>
<td>{{ $category->display_order }}</td>
<td><span class="badge bg-{{ $category->is_active ? 'success' : 'danger' }}">{{ $category->is_active ? 'Active' : 'Inactive' }}</span></td>
<td>
<a href="{{ route('admin.product-categories.edit', $category) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
<form action="{{ route('admin.product-categories.destroy', $category) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
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