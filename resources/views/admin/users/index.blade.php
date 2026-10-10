@extends('admin.layouts.admin')

@section('title', 'Users')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold"><i class="bi bi-people-fill text-primary me-2"></i> Users</h5>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
            <i class="bi bi-person-plus-fill me-1"></i> Add New User
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>User Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $u)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <div class="fw-bold">{{ $u->name }}</div>
                                @if($u->id === auth()->id())
                                    <span class="badge bg-light text-primary border">You (Current)</span>
                                @endif
                            </td>
                            <td><code>{{ $u->username }}</code></td>
                            <td>{{ $u->email }}</td>
                            <td>
                                @php
                                    $roleBadge = match($u->role) {
                                        'super_admin' => 'bg-danger',
                                        'admin' => 'bg-primary',
                                        'content_manager' => 'bg-info text-dark',
                                        'seo_manager' => 'bg-warning text-dark',
                                        'lead_manager' => 'bg-success',
                                        default => 'bg-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $roleBadge }}">{{ $roles[$u->role] ?? str($u->role)->replace('_', ' ')->title() }}</span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $u->is_active ? 'success' : 'secondary' }}">{{ $u->is_active ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td>{{ $u->created_at->format('M d, Y') }}</td>
                            <td>
                                <a href="{{ route('admin.users.edit', $u) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @if($u->id !== auth()->id())
                                    <form action="{{ route('admin.users.destroy', $u) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user account?');">
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
                            <td colspan="8" class="text-center py-4 text-muted">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $users->links() }}
        </div>
    </div>
</div>
@endsection

