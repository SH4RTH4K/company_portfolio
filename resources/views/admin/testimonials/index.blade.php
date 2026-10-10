@extends('admin.layouts.admin')

@section('title', 'Testimonials')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">Testimonials</h5>
        <a href="{{ route('admin.testimonials.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add New
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th width="50">#</th>
                        <th>Name</th>
                        <th>Designation (EN)</th>
                        <th>Rating</th>
                        <th>Status</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($testimonials as $testimonial)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $testimonial->name }}</td>
                        <td>{{ $testimonial->designation_en ?: '—' }}</td>
                        <td>
                            @if($testimonial->rating)
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="bi bi-star{{ $i <= $testimonial->rating ? '-fill text-warning' : ' text-muted' }}"></i>
                                @endfor
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($testimonial->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.testimonials.edit', $testimonial) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('admin.testimonials.destroy', $testimonial) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this testimonial?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4">No testimonials found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
