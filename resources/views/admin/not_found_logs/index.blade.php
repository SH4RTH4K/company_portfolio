@extends('admin.layouts.admin')
@section('title', '404 Monitor')
@section('content')
<div class="card border-0 shadow-sm">
<div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
<h5 class="mb-0 fw-bold">404 Errors Monitor</h5>
<form action="{{ route('admin.not-found-logs.clear') }}" method="POST" onsubmit="return confirm('Clear all logs?');">
@csrf
<button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i> Clear All Logs</button>
</form>
</div>
<div class="card-body">
<div class="table-responsive">
<table class="table table-hover align-middle">
<thead>
<tr>
<th>URL Attempted</th><th>Hits</th><th>Last Hit</th><th width="100">Actions</th>
</tr>
</thead>
<tbody>
@forelse($logs as $log)
<tr>
<td><span class="text-break text-danger">{{ $log->url }}</span></td>
<td><span class="badge bg-warning text-dark">{{ $log->hits }}</span></td>
<td>{{ $log->last_hit_at->diffForHumans() }}</td>
<td>
<form action="{{ route('admin.not-found-logs.destroy', $log) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
@csrf
@method('DELETE')
<button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
</form>
</td>
</tr>
@empty
<tr><td colspan="4" class="text-center py-4">No 404 errors found. Good job!</td></tr>
@endforelse
</tbody>
</table>
</div>
<div class="mt-3">{{ $logs->links() }}</div>
</div>
</div>
@endsection