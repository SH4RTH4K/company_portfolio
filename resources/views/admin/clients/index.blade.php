@extends('admin.layouts.admin')

@section('title', 'Clients')

@section('content')
@php
    $totalClients = $clients->count();
    $activeClients = $clients->where('is_active', true)->count();
    $clientsWithLogo = $clients->filter(fn ($client) => filled($client->logo))->count();
    $clientLogoUrl = function (?string $logo): ?string {
        if (! filled($logo)) {
            return null;
        }

        if (str($logo)->startsWith(['http://', 'https://', '/'])) {
            return $logo;
        }

        return str($logo)->startsWith('client-logos/') ? \Illuminate\Support\Facades\Storage::url($logo) : asset($logo);
    };
@endphp

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
    <div>
        <div class="admin-page-kicker mb-1">Valued Corporate Clients</div>
        <h3 class="fw-bold mb-1">Client Logo & Name Directory</h3>
        <p class="text-muted mb-0">Manage the brands shown in the public corporate client logo section.</p>
    </div>
    <a href="{{ route('admin.clients.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Add New Client
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="admin-stat-card p-3 h-100">
            <div class="d-flex align-items-center gap-3">
                <span class="btn btn-primary rounded-circle disabled"><i class="bi bi-buildings"></i></span>
                <div>
                    <div class="text-muted small">Total Clients</div>
                    <div class="h4 fw-bold mb-0">{{ $totalClients }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="admin-stat-card p-3 h-100">
            <div class="d-flex align-items-center gap-3">
                <span class="btn btn-success rounded-circle disabled"><i class="bi bi-check2-circle"></i></span>
                <div>
                    <div class="text-muted small">Active on Frontend</div>
                    <div class="h4 fw-bold mb-0">{{ $activeClients }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="admin-stat-card p-3 h-100">
            <div class="d-flex align-items-center gap-3">
                <span class="btn btn-info rounded-circle disabled"><i class="bi bi-image"></i></span>
                <div>
                    <div class="text-muted small">Logos Added</div>
                    <div class="h4 fw-bold mb-0">{{ $clientsWithLogo }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <div>
            <h5 class="mb-0 fw-bold">Corporate Client Brands</h5>
            <small class="text-muted">Logo, name, website, status, and display order.</small>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th width="50">#</th>
                        <th>Logo & Name</th>
                        <th>Website</th>
                        <th>Order</th>
                        <th>Status</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clients as $client)
                    @php
                        $logoUrl = $clientLogoUrl($client->logo);
                    @endphp
                    <tr>
                        <td class="text-muted fw-semibold">{{ $loop->iteration }}</td>
                        <td class="client-name-cell">
                            <div class="d-flex align-items-center gap-3">
                                @if($logoUrl)
                                    <span class="client-logo-frame">
                                        <img src="{{ $logoUrl }}" alt="{{ $client->name }} logo">
                                    </span>
                                @else
                                    <span class="client-logo-frame client-logo-placeholder">
                                        {{ str($client->name)->substr(0, 1)->upper() }}
                                    </span>
                                @endif
                                <div>
                                    <div class="fw-bold">{{ $client->name }}</div>
                                    <div class="text-muted small">{{ $client->logo ? 'Logo path saved' : 'Using initials fallback' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($client->website)
                                <a href="{{ $client->website }}" target="_blank" class="client-website-link text-decoration-none" title="{{ $client->website }}">
                                    {{ $client->website }}
                                </a>
                            @else
                                <span class="text-muted">&mdash;</span>
                            @endif
                        </td>
                        <td><span class="badge bg-light text-dark border">{{ $client->display_order }}</span></td>
                        <td>
                            @if($client->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.clients.edit', $client) }}" class="btn btn-sm btn-outline-secondary" title="Edit client">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('admin.clients.destroy', $client) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this client?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete client">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="text-muted mb-2"><i class="bi bi-buildings fs-2"></i></div>
                            <div class="fw-bold">No clients found.</div>
                            <a href="{{ route('admin.clients.create') }}" class="btn btn-primary btn-sm mt-3">Add the first client</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
