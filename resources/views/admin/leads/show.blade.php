@extends('admin.layouts.admin')

@section('title', 'Contact Message Details')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Contact Message Information</h5>
                <a href="{{ route('admin.leads.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4 text-muted fw-bold">Date Received</div>
                    <div class="col-md-8">{{ $lead->created_at->format('F d, Y h:i A') }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 text-muted fw-bold">Full Name</div>
                    <div class="col-md-8">{{ $lead->full_name }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 text-muted fw-bold">Company Name</div>
                    <div class="col-md-8">{{ $lead->company_name ?? '-' }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 text-muted fw-bold">Email</div>
                    <div class="col-md-8"><a href="mailto:{{ $lead->email }}">{{ $lead->email }}</a></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 text-muted fw-bold">Phone</div>
                    <div class="col-md-8"><a href="tel:{{ $lead->phone }}">{{ $lead->phone }}</a></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 text-muted fw-bold">Interested Service</div>
                    <div class="col-md-8">{{ $lead->interested_service ?? '-' }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 text-muted fw-bold">Budget Range</div>
                    <div class="col-md-8">{{ $lead->budget_range ?? '-' }}</div>
                </div>
                <hr>
                <div class="row mb-3">
                    <div class="col-md-12 text-muted fw-bold mb-2">Subject</div>
                    <div class="col-md-12 border p-3 bg-light rounded">{{ $lead->subject ?? '-' }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12 text-muted fw-bold mb-2">Message</div>
                    <div class="col-md-12 border p-3 bg-light rounded">{!! nl2br(e($lead->message)) !!}</div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Reply to Customer</h5>
                @if($lead->replied_at)
                    <span class="badge bg-success">Last replied {{ $lead->replied_at->format('M d, Y h:i A') }}</span>
                @endif
            </div>
            <div class="card-body">
                <div class="alert alert-info small">
                    Replies are sent from <strong>{{ config('mail.from.address') }}</strong> as <strong>{{ config('mail.from.name') }}</strong>.
                    Current responder: <strong>{{ auth()->user()?->name }}</strong>.
                </div>

                @if($lead->replied_at)
                    <div class="border rounded p-3 bg-light mb-4">
                        <div class="small text-muted mb-1">
                            Last reply by {{ $lead->repliedBy?->name ?? 'Admin' }}
                        </div>
                        <div class="fw-bold mb-2">{{ $lead->reply_subject }}</div>
                        <div class="small">{!! nl2br(e($lead->reply_message)) !!}</div>
                    </div>
                @endif

                <form action="{{ route('admin.leads.reply', $lead) }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Reply Subject</label>
                        <input type="text" name="reply_subject" class="form-control @error('reply_subject') is-invalid @enderror" value="{{ old('reply_subject', 'Re: '.($lead->subject ?: 'Your inquiry')) }}" maxlength="255" required>
                        @error('reply_subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reply Message</label>
                        <textarea name="reply_message" rows="8" class="form-control @error('reply_message') is-invalid @enderror" required>{{ old('reply_message') }}</textarea>
                        @error('reply_message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <button type="submit" class="btn btn-primary" {{ $lead->email ? '' : 'disabled' }}>
                        <i class="bi bi-send me-1"></i> Send Reply Email
                    </button>
                    @if(! $lead->email)
                        <div class="form-text text-danger">This lead has no customer email address.</div>
                    @endif
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Message Status</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.leads.update', $lead) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label class="form-label">Current Status</label>
                        <select name="status" class="form-select">
                            @foreach(\App\Models\Lead::STATUSES as $status)
                                <option value="{{ $status }}" {{ $lead->status === $status ? 'selected' : '' }}>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Update Status</button>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Tracking Data</h5>
            </div>
            <div class="card-body">
                <div class="mb-2"><span class="text-muted">Language:</span> {{ $lead->language ?? '-' }}</div>
                <div class="mb-2"><span class="text-muted">Source:</span> {{ $lead->lead_source ?? '-' }}</div>
                <div class="mb-2"><span class="text-muted">Landing Page:</span> <br><small class="text-break">{{ $lead->landing_page ?? '-' }}</small></div>
                <div class="mb-2"><span class="text-muted">Referrer:</span> <br><small class="text-break">{{ $lead->referrer ?? '-' }}</small></div>
                
                @if($lead->utm_source || $lead->utm_medium || $lead->utm_campaign)
                    <hr>
                    <h6 class="fw-bold mt-3 mb-2">UTM Parameters</h6>
                    <div class="mb-1"><span class="text-muted">Source:</span> {{ $lead->utm_source ?? '-' }}</div>
                    <div class="mb-1"><span class="text-muted">Medium:</span> {{ $lead->utm_medium ?? '-' }}</div>
                    <div class="mb-1"><span class="text-muted">Campaign:</span> {{ $lead->utm_campaign ?? '-' }}</div>
                    <div class="mb-1"><span class="text-muted">Term:</span> {{ $lead->utm_term ?? '-' }}</div>
                    <div class="mb-1"><span class="text-muted">Content:</span> {{ $lead->utm_content ?? '-' }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

