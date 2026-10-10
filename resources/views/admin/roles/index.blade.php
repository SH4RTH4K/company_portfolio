@extends('admin.layouts.admin')

@section('title', 'Roles')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold"><i class="bi bi-shield-lock-fill text-primary me-2"></i> Roles</h5>
        <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Add Role
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Description</th>
                        <th>Users</th>
                        <th>Status</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $role)
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $role->name }}</div>
                                @if($role->is_system)
                                    <span class="badge bg-light text-primary border">System</span>
                                @endif
                            </td>
                            <td><code>{{ $role->slug }}</code></td>
                            <td class="text-muted">{{ $role->description ?: 'No description added.' }}</td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $role->users_count }}</span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $role->is_active ? 'success' : 'secondary' }}">{{ $role->is_active ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td>
                                <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @if(! $role->is_system)
                                    <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this role?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No roles found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $roles->links() }}
        </div>
    </div>
</div>
@endsection
