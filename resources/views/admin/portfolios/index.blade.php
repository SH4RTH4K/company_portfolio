@extends('admin.layouts.admin')
@section('title', 'Portfolio')
@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">Portfolio / Case Studies</h5>
        <a href="{{ route('admin.portfolios.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add New</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr><th>#</th><th>Project Name (EN)</th><th>Client</th><th>Industry</th><th>Status</th><th width="150">Actions</th></tr>
                </thead>
                <tbody>
                    @forelse($portfolios as $portfolio)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $portfolio->project_name_en }}</td>
                        <td>{{ $portfolio->client_name ?: '-' }}</td>
                        <td>{{ $portfolio->industry ? $portfolio->industry->title_en : '-' }}</td>
                        <td>
                            @if($portfolio->is_featured)<span class="badge bg-warning text-dark me-1">Featured</span>@endif
                            <span class="badge bg-{{ $portfolio->status === 'published' ? 'success' : 'secondary' }}">{{ ucfirst($portfolio->status) }}</span>
                        </td>
                        <td>
                            <a href="{{ route('admin.portfolios.edit', $portfolio) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('admin.portfolios.destroy', $portfolio) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-4">No portfolio items found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

