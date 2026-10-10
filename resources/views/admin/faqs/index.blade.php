@extends('admin.layouts.admin')
@section('title', 'FAQs')
@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold"><i class="bi bi-question-circle me-2 text-primary"></i> FAQs</h5>
        <a href="{{ route('admin.faqs.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i> Add FAQ
        </a>
    </div>
    <div class="card-body p-0">
        @if(session('success'))
            <div class="alert alert-success m-3">{{ session('success') }}</div>
        @endif

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Question (EN)</th>
                        <th>Category</th>
                        <th>Service</th>
                        <th>Order</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($faqs as $faq)
                    <tr>
                        <td>{{ $faq->id }}</td>
                        <td class="fw-medium" style="max-width:350px">{{ Str::limit($faq->question_en, 80) }}</td>
                        <td>
                            @if($faq->category)
                                <span class="badge bg-secondary">{{ $faq->category }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>{{ $faq->service?->title_en ?? '—' }}</td>
                        <td>{{ $faq->display_order }}</td>
                        <td>
                            @if($faq->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.faqs.edit', $faq) }}" class="btn btn-sm btn-outline-primary me-1">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('admin.faqs.destroy', $faq) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this FAQ?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No FAQs yet. <a href="{{ route('admin.faqs.create') }}">Add the first one.</a></td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

