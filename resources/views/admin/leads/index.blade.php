@extends('admin.layouts.admin')

@section('title', 'Contact Messages')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">Contact Messages</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Subject</th>
                        <th>Reply</th>
                        <th>Status</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leads as $lead)
                    <tr>
                        <td>{{ $lead->created_at->format('M d, Y H:i') }}</td>
                        <td>{{ $lead->full_name }}</td>
                        <td>{{ $lead->email }}</td>
                        <td>{{ $lead->phone }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($lead->subject, 30) }}</td>
                        <td>
                            @if($lead->replied_at)
                                <span class="badge bg-success">Replied</span>
                                <div class="small text-muted">{{ $lead->replied_at->format('M d, Y H:i') }}</div>
                            @else
                                <span class="badge bg-light text-dark border">Pending</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $lead->status === 'New' ? 'danger' : ($lead->status === 'Won' ? 'success' : 'secondary') }}">
                                {{ $lead->status }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.leads.show', $lead) }}" class="btn btn-sm btn-outline-info">
                                <i class="bi bi-eye"></i>
                            </a>
                            <form action="{{ route('admin.leads.destroy', $lead) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
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
                        <td colspan="8" class="text-center py-4">No contact messages found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $leads->links() }}
        </div>
    </div>
</div>
@endsection

