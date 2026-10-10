@extends('admin.layouts.admin')
@section('title', 'Add Post')
@section('content')
<div class="card border-0 shadow-sm">
<div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
<h5 class="mb-0 fw-bold">Add Post</h5>
<a href="{{ route('admin.posts.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
</div>
<div class="card-body">
<form action="{{ route('admin.posts.store') }}" method="POST">
@csrf
<div class="mb-3"><label>Title (EN) *</label><input type="text" name="title_en" class="form-control" required></div>
<div class="mb-3"><label>Category</label><select name="category_id" class="form-select"><option value="">None</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name_en }}</option>@endforeach</select></div>
<div class="mb-3"><label>Content (EN)</label><textarea name="content_en" class="form-control" rows="5"></textarea></div>
<div class="mb-3"><label>Status</label><select name="status" class="form-select"><option value="draft">Draft</option><option value="published">Published</option></select></div>
<div class="card mt-4 mb-4 border border-light bg-light">
    <div class="card-body">
        <h5 class="card-title text-muted mb-3"><i class="bi bi-search me-2"></i> SEO Settings</h5>
        @include('admin.partials.seo-meta-fields')
    </div>
</div>
<button type="submit" class="btn btn-primary">Save</button>
</form>
</div>
</div>
@endsection