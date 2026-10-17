@extends('admin.layouts.admin')

@section('title', 'System Health & Backups')

@section('content')
@php
    $systemIsHealthy = collect($health)->every(fn (array $check): bool => $check['ok']);
@endphp

@if($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>The request could not be completed.</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <div class="admin-page-kicker">System</div>
        <h2 class="h4 fw-bold mb-1">System Health</h2>
        <p class="text-muted mb-0">Live operational checks and recovery tools.</p>
    </div>
    <span class="badge rounded-pill {{ $systemIsHealthy ? 'text-bg-success' : 'text-bg-warning' }} px-3 py-2">
        <i class="bi {{ $systemIsHealthy ? 'bi-check-circle' : 'bi-exclamation-triangle' }} me-1"></i>
        {{ $systemIsHealthy ? 'All checks passed' : 'Review system checks' }}
    </span>
</div>

<div class="row g-3 mb-4">
    @foreach($health as $label => $check)
        <div class="col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex gap-3 align-items-start">
                    <span class="rounded-circle {{ $check['ok'] ? 'bg-success' : 'bg-warning' }} bg-opacity-10 p-2">
                        <i class="bi {{ $check['ok'] ? 'bi-check-lg text-success' : 'bi-exclamation-triangle text-warning' }}"></i>
                    </span>
                    <div class="min-w-0">
                        <h3 class="h6 fw-bold mb-1">{{ $label }}</h3>
                        <p class="text-muted small mb-0">{{ $check['message'] }}</p>
                    </div>
                    <span class="badge ms-auto {{ $check['ok'] ? 'text-bg-success' : 'text-bg-warning' }}">
                        {{ $check['ok'] ? 'OK' : 'Check' }}
                    </span>
                </div>
            </div>
        </div>
    @endforeach
</div>

<section class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h2 class="h5 fw-bold mb-1">Database migrations</h2>
            <p class="text-muted small mb-0">{{ $migrations['message'] }}</p>
        </div>
        @if(count($migrations['pending']) > 0)
            <form action="{{ route('admin.system-health.migrations.run') }}" method="POST" onsubmit="return confirm('Run all pending database migrations now? Create a backup first if this is a production system.')">
                @csrf
                <button type="submit" class="btn btn-danger btn-sm">
                    <i class="bi bi-arrow-repeat me-1"></i> Run pending migrations
                </button>
            </form>
        @else
            <span class="badge text-bg-success">Up to date</span>
        @endif
    </div>
    @if(count($migrations['pending']) > 0)
        <ul class="list-group list-group-flush">
            @foreach($migrations['pending'] as $migration)
                <li class="list-group-item d-flex align-items-center gap-2">
                    <i class="bi bi-hourglass-split text-warning"></i>
                    <code>{{ $migration }}</code>
                </li>
            @endforeach
        </ul>
    @endif
</section>

<section class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h2 class="h5 fw-bold mb-1">Backups</h2>
        <p class="text-muted small mb-0">Create a database snapshot, a media snapshot, or both together.</p>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-4">
            @foreach([
                ['type' => 'database', 'title' => 'Database backup', 'description' => 'Save database tables and records.', 'icon' => 'bi-database', 'color' => 'primary'],
                ['type' => 'media', 'title' => 'Media backup', 'description' => 'Save files from public storage.', 'icon' => 'bi-images', 'color' => 'info'],
                ['type' => 'full', 'title' => 'Full backup', 'description' => 'Save both database and public media.', 'icon' => 'bi-shield-check', 'color' => 'success'],
            ] as $backupOption)
                <div class="col-md-4">
                    <div class="border rounded-3 p-3 h-100 d-flex flex-column">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi {{ $backupOption['icon'] }} text-{{ $backupOption['color'] }} fs-5"></i>
                            <h3 class="h6 fw-bold mb-0">{{ $backupOption['title'] }}</h3>
                        </div>
                        <p class="text-muted small flex-grow-1">{{ $backupOption['description'] }}</p>
                        <form action="{{ route('admin.system-health.backups.create', ['type' => $backupOption['type']]) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-{{ $backupOption['color'] }} w-100">
                                Create backup
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="alert alert-warning d-flex gap-2 align-items-start mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
            <div><strong>Restore replaces data.</strong> Each restore creates a safety backup first. Database restores require a backup generated by this application with a matching database schema.</div>
        </div>

        <div class="row g-3">
            @foreach([
                ['type' => 'database', 'title' => 'Restore database', 'description' => 'Replace the current database records from a database backup.'],
                ['type' => 'media', 'title' => 'Restore media', 'description' => 'Overwrite matching files in public storage from a media backup.'],
                ['type' => 'full', 'title' => 'Restore full backup', 'description' => 'Restore database records and matching public media files.'],
            ] as $restoreOption)
                <div class="col-lg-4">
                    <form action="{{ route('admin.system-health.restore', ['type' => $restoreOption['type']]) }}" method="POST" enctype="multipart/form-data" class="border rounded-3 p-3 h-100" onsubmit="return confirm('This restore can replace current application data. Continue?')">
                        @csrf
                        <h3 class="h6 fw-bold">{{ $restoreOption['title'] }}</h3>
                        <p class="text-muted small">{{ $restoreOption['description'] }}</p>
                        <label for="backup-{{ $restoreOption['type'] }}" class="form-label small">Generated compressed backup</label>
                        <input id="backup-{{ $restoreOption['type'] }}" type="file" name="backup" class="form-control mb-3" accept=".gz,application/gzip" required>
                        <button type="submit" class="btn btn-warning w-100">Restore</button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h2 class="h5 fw-bold mb-1">Backup history</h2>
            <p class="text-muted small mb-0">
                The most recent 30 backup jobs. The app backup is for in-app restores; use the SQL download for phpMyAdmin.
                SQL exports recreate the app tables and replace their data in the selected database.
            </p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th scope="col">Backup</th>
                    <th scope="col">Type</th>
                    <th scope="col">Size</th>
                    <th scope="col">Status</th>
                    <th scope="col">Created</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($backups as $backup)
                    <tr>
                        <td class="text-break">
                            <span class="fw-semibold">{{ $backup->filename }}</span>
                            @if($backup->created_by)
                                <span class="d-block text-muted small">By {{ $backup->created_by }}</span>
                            @endif
                            @if($backup->error)
                                <span class="d-block text-danger small">{{ $backup->error }}</span>
                            @endif
                        </td>
                        <td>{{ ucfirst($backup->type) }}</td>
                        <td>{{ $backup->size_bytes ? number_format($backup->size_bytes / 1024, 1).' KB' : '—' }}</td>
                        <td>
                            <span class="badge {{ $backup->status === 'completed' ? 'text-bg-success' : ($backup->status === 'failed' ? 'text-bg-danger' : 'text-bg-secondary') }}">
                                {{ ucfirst($backup->status) }}
                            </span>
                        </td>
                        <td>{{ $backup->created_at ? \Illuminate\Support\Carbon::parse($backup->created_at)->format('Y-m-d H:i') : '—' }}</td>
                        <td>
                            <div class="d-flex gap-2">
                                @if($backup->status === 'completed')
                                    @if($backup->type === 'database')
                                        <a href="{{ route('admin.system-health.backups.phpmyadmin', ['backupId' => $backup->id]) }}" class="btn btn-sm btn-outline-primary" title="Download SQL for phpMyAdmin">
                                            <i class="bi bi-database-down"></i> SQL for phpMyAdmin
                                        </a>
                                        <a href="{{ route('admin.system-health.backups.download', ['backupId' => $backup->id]) }}" class="btn btn-sm btn-outline-secondary" title="Download app restore archive">
                                            <i class="bi bi-download"></i> App archive
                                        </a>
                                    @else
                                        <a href="{{ route('admin.system-health.backups.download', ['backupId' => $backup->id]) }}" class="btn btn-sm btn-outline-primary" title="Download app restore archive">
                                            <i class="bi bi-download"></i> App archive
                                        </a>
                                    @endif
                                @endif
                                <form action="{{ route('admin.system-health.backups.delete', ['backupId' => $backup->id]) }}" method="POST" onsubmit="return confirm('Permanently delete this backup file?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i><span class="visually-hidden">Delete</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No backups exist yet. Create the first backup above.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="card border-0 shadow-sm">
    <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h2 class="h5 fw-bold mb-1">Maintenance tools</h2>
            <p class="text-muted small mb-0">Clear application caches and rebuild compiled views after an update.</p>
        </div>
        <form action="{{ route('admin.system-health.cache.clear') }}" method="POST" onsubmit="return confirm('Clear application caches and rebuild compiled views?')">
            @csrf
            <button type="submit" class="btn btn-outline-warning">
                <i class="bi bi-arrow-repeat me-1"></i> Clear &amp; rebuild caches
            </button>
        </form>
    </div>
</section>

<section class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex gap-3 align-items-start">
            <i class="bi bi-clock-history fs-4 text-primary"></i>
            <div>
                <h2 class="h5 fw-bold mb-1">Automatic schedule</h2>
                <p class="text-muted small mb-1">A database backup runs daily at 2:00 AM. Backups older than 14 days are removed after a successful scheduled backup.</p>
                <p class="text-muted small mb-0">The server must run <code>php artisan schedule:run</code> every minute. On Windows, configure this in Task Scheduler.</p>
            </div>
        </div>
    </div>
</section>
@endsection
