@extends('admin.layouts.admin')

@section('title', 'Audit & Activity Log')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history text-primary me-2"></i> System Audit & Activity Log</h5>
        <form action="{{ route('admin.audit-logs.clear') }}" method="POST" onsubmit="return confirm('Are you sure you want to clear all audit logs?');">
            @csrf
            <button type="submit" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-trash me-1"></i> Clear Audit History
            </button>
        </form>
    </div>
    <div class="card-body">
        <!-- Filter Bar -->
        <form action="{{ route('admin.audit-logs.index') }}" method="GET" class="row g-2 mb-4">
            <div class="col-md-4">
                <select name="module" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Modules</option>
                    @foreach(['Services', 'Products', 'Posts', 'Leads', 'Users', 'Settings', 'SEO'] as $m)
                        <option value="{{ $m }}" {{ request('module') == $m ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <select name="action" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Actions</option>
                    @foreach(['create', 'update', 'delete', 'restore', 'login', 'logout'] as $a)
                        <option value="{{ $a }}" {{ request('action') == $a ? 'selected' : '' }}>{{ ucfirst($a) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-sm btn-outline-secondary w-100">Reset Filter</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Module</th>
                        <th>IP Address</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <div class="fw-bold">{{ $log->user->name ?? 'System' }}</div>
                                <small class="text-muted">{{ $log->user->email ?? '' }}</small>
                            </td>
                            <td>
                                @php
                                    $badge = match(strtolower($log->action)) {
                                        'create' => 'bg-success',
                                        'update' => 'bg-primary',
                                        'delete' => 'bg-danger',
                                        'login' => 'bg-info text-dark',
                                        default => 'bg-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $badge }} text-uppercase">{{ $log->action }}</span>
                            </td>
                            <td><span class="fw-semibold">{{ $log->module }}</span></td>
                            <td><code>{{ $log->ip_address }}</code></td>
                            <td>{{ $log->created_at->format('M d, Y h:i A') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No activity records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection

