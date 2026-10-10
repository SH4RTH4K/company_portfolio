@extends('admin.layouts.admin')
@section('title', 'Posts')
@section('content')
<div class="card border-0 shadow-sm">
<div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
<h5 class="mb-0 fw-bold">Posts</h5>
<a href="{{ route('admin.posts.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add New</a>
</div>
<div class="card-body">
<div class="table-responsive">
<table class="table table-hover align-middle">
<thead>
<tr>
<th>#</th><th>Title</th><th>Category</th><th>Status</th><th>Published</th><th width="150">Actions</th>
</tr>
</thead>
<tbody>
@forelse($posts as $post)
<tr>
<td>{{ $loop->iteration }}</td>
<td>{{ $post->title_en }}</td>
<td>{{ $post->category->name_en ?? '-' }}</td>
<td><span class="badge bg-{{ $post->status == 'published' ? 'success' : 'secondary' }}">{{ ucfirst($post->status) }}</span></td>
<td>{{ $post->published_at ? $post->published_at->format('Y-m-d') : '-' }}</td>
<td>
<a href="{{ route('admin.posts.edit', $post) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
<form action="{{ route('admin.posts.destroy', $post) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
@csrf
@method('DELETE')
<button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
</form>
</td>
</tr>
@empty
<tr><td colspan="6" class="text-center py-4">No records found.</td></tr>
@endforelse
</tbody>
</table>
</div>
</div>
</div>
@endsection