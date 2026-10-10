@extends('admin.layouts.admin')
@section('title', 'Team')
@section('content')
<div class="card border-0 shadow-sm">
<div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
<h5 class="mb-0 fw-bold">Team</h5>
<a href="{{ route('admin.teams.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add New</a>
</div>
<div class="card-body">
<div class="table-responsive">
<table class="table table-hover align-middle">
<thead>
<tr>
<th>#</th>
<th>Picture</th>
<th>Name (EN)</th>
<th>Name (BN)</th>
<th>Designation (EN)</th>
<th>Social</th>
<th>Status</th>
<th width="150">Actions</th>
</tr>
</thead>
<tbody>
@forelse($teams as $team)
<tr>
<td>{{ $loop->iteration }}</td>
<td>
@if($team->photo)
<img src="{{ \Illuminate\Support\Facades\Storage::url($team->photo) }}" alt="{{ $team->name }}" style="width: 48px; height: 48px; object-fit: cover;" class="rounded-circle border">
@else
<span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light text-muted border" style="width: 48px; height: 48px;">{{ mb_substr($team->name, 0, 1) }}</span>
@endif
</td>
<td>{{ $team->name }}</td>
<td>{{ $team->name_bn ?: '—' }}</td>
<td>{{ $team->designation_en }}</td>
<td>
@if($team->email)<i class="bi bi-envelope me-1" title="Email"></i>@endif
@if($team->linkedin)<i class="bi bi-linkedin me-1" title="LinkedIn"></i>@endif
@if($team->facebook)<i class="bi bi-facebook me-1" title="Facebook"></i>@endif
@if($team->twitter)<i class="bi bi-twitter-x me-1" title="X / Twitter"></i>@endif
@if($team->website)<i class="bi bi-globe me-1" title="Website"></i>@endif
@if($team->github)<i class="bi bi-github me-1" title="GitHub"></i>@endif
@if($team->instagram)<i class="bi bi-instagram me-1" title="Instagram"></i>@endif
@if($team->youtube)<i class="bi bi-youtube me-1" title="YouTube"></i>@endif
@if($team->whatsapp)<i class="bi bi-whatsapp me-1" title="WhatsApp"></i>@endif
</td>
<td><span class="badge bg-{{ $team->is_active ? 'success' : 'danger' }}">{{ $team->is_active ? 'Active' : 'Inactive' }}</span></td>
<td>
<a href="{{ route('admin.teams.edit', $team) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
<form action="{{ route('admin.teams.destroy', $team) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
@csrf
@method('DELETE')
<button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
</form>
</td>
</tr>
@empty
<tr><td colspan="10" class="text-center py-4">No records found.</td></tr>
@endforelse
</tbody>
</table>
</div>
</div>
</div>
@endsection
