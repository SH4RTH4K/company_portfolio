@extends('admin.layouts.admin')
@section('title', 'Edit Post')
@section('content')
<div class="card border-0 shadow-sm">
<div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
<h5 class="mb-0 fw-bold">Edit Post</h5>
<a href="{{ route('admin.posts.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
</div>
<div class="card-body">
<form action="{{ route('admin.posts.update', $post) }}" method="POST">
@csrf
@method('PUT')
<div class="mb-3"><label>Title (EN) *</label><input type="text" name="title_en" class="form-control" value="{{ $post->title_en }}" required></div>
<div class="mb-3"><label>Category</label><select name="category_id" class="form-select"><option value="">None</option>@foreach($categories as $category)<option value="{{ $category->id }}" {{ $post->category_id == $category->id ? 'selected' : '' }}>{{ $category->name_en }}</option>@endforeach</select></div>
<div class="mb-3"><label>Content (EN)</label><textarea name="content_en" class="form-control" rows="5">{{ $post->content_en }}</textarea></div>
<div class="mb-3"><label>Status</label><select name="status" class="form-select"><option value="draft" {{ $post->status == 'draft' ? 'selected' : '' }}>Draft</option><option value="published" {{ $post->status == 'published' ? 'selected' : '' }}>Published</option></select></div>
<div class="card mt-4 mb-4 border border-light bg-light">
    <div class="card-body">
        <h5 class="card-title text-muted mb-3"><i class="bi bi-search me-2"></i> SEO Settings</h5>
        @include('admin.partials.seo-meta-fields', ['model' => $post])
    </div>
</div>
<button type="submit" class="btn btn-primary">Update</button>
</form>
</div>
</div>
@endsection