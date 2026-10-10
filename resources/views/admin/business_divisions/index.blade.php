@extends('admin.layouts.admin')

@section('title', 'Business Divisions')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">Business Divisions</h5>
        <a href="{{ route('admin.business-divisions.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add New
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th width="50">#</th>
                        <th>Name (EN)</th>
                        <th>Name (BN)</th>
                        <th>Order</th>
                        <th>Status</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($divisions as $division)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $division->name_en }}</td>
                        <td>{{ $division->name_bn ?: '-' }}</td>
                        <td>{{ $division->display_order }}</td>
                        <td>
                            @if($division->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.business-divisions.edit', $division) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('admin.business-divisions.destroy', $division) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this?');">
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
                        <td colspan="6" class="text-center py-4">No business divisions found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

