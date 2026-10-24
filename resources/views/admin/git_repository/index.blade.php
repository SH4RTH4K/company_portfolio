@extends('admin.layouts.admin')

@section('title', 'Git Repository')

@section('content')
@php
    $serverGit = $settings['delivery'] === 'git';
    $gitCheckedAt = $gitStatus['checked_at'] ?? null;
    $gitCheckFresh = is_string($gitCheckedAt) && strtotime($gitCheckedAt) !== false && strtotime($gitCheckedAt) >= now()->subMinutes(15)->timestamp;
    $statusLabel = $serverGit
        ? ($gitStatus['status'] ?? ($settings['enabled'] ? 'Connection unavailable' : 'Disabled'))
        : ($preview
            ? (isset($installed['commit']) ? ($preview['commit'] === $installed['commit'] ? 'Up to date' : 'Update available') : 'First release ready')
            : (isset($installed['commit']) ? 'Check for updates' : 'Not deployed yet'));
@endphp

<section class="card border-0 shadow-sm text-white mb-3" style="background:linear-gradient(115deg,#101827 0%,#171b2b 70%,#71311f 100%);border-radius:14px;">
    <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-end gap-3">
        <div>
            <div class="small text-uppercase fw-bold mb-1" style="color:#ff9b55;letter-spacing:.12em;">System / Deployment</div>
            <h2 class="h3 fw-bold mb-1">Git Repository</h2>
            <p class="small mb-0 text-white-50">Connect this cPanel installation to GitHub, review incoming commits, and deploy only after the server state is safe.</p>
        </div>
        <div class="border rounded-3 px-3 py-2 bg-dark bg-opacity-25">
            <div class="small text-uppercase text-white-50">Current status</div>
            <span class="badge rounded-pill {{ $statusLabel === 'Up to date' ? 'text-bg-success' : ($statusLabel === 'Update available' ? 'text-bg-warning' : 'text-bg-light text-dark') }}">{{ $statusLabel }}</span>
        </div>
    </div>
</section>

@if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif
@if($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Review the following fields.</strong>
        <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
@if($manifestError)
    <div class="alert alert-warning" role="alert">{{ $manifestError }} Complete or recover the upload through cPanel File Manager. Deployment history and backups remain available below.</div>
@endif

<section class="card border-0 shadow-sm mb-3">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <div class="small text-uppercase fw-bold text-danger">Connection</div>
                <h3 class="h5 fw-bold mb-0">Repository settings</h3>
            </div>
            <span class="badge rounded-pill {{ $settings['enabled'] ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $settings['enabled'] ? 'Integration enabled' : 'Integration disabled' }}</span>
        </div>
        <p class="small text-muted">Saving enabled settings connects an uploaded cPanel installation to the configured branch without overwriting its files. Public and private repositories are supported.</p>
        <form action="{{ route('admin.git-repository.settings') }}" method="POST" autocomplete="off">
            @csrf
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label" for="deployment-enabled">Integration</label>
                    <select class="form-select" name="enabled" id="deployment-enabled" required>
                        <option value="1" @selected((bool) old('enabled', $settings['enabled']))>On</option>
                        <option value="0" @selected(!(bool) old('enabled', $settings['enabled']))>Off</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="deployment-repository-type">Repository type</label>
                    <select class="form-select" name="repository_type" id="deployment-repository-type" required>
                        <option value="public" @selected(old('repository_type', $settings['repository_type']) === 'public')>Public</option>
                        <option value="private" @selected(old('repository_type', $settings['repository_type']) === 'private')>Private</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="deployment-branch">Branch</label>
                    <input class="form-control" id="deployment-branch" name="branch" value="{{ old('branch', $settings['branch']) }}" required maxlength="200">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="deployment-remote-name">Remote name</label>
                    <input class="form-control" id="deployment-remote-name" name="remote_name" value="{{ old('remote_name', $settings['remote_name']) }}" required maxlength="50">
                </div>
                <div class="col-12">
                    <label class="form-label" for="deployment-repository">GitHub repository URL</label>
                    <input class="form-control" id="deployment-repository" name="repository" value="{{ old('repository', $settings['repository'] !== '' ? 'https://github.com/'.$settings['repository'].'.git' : '') }}" placeholder="https://github.com/owner/repository.git" required maxlength="200">
                    <div class="form-text">Use the repository URL configured for the deployed checkout. This page never pushes changes to GitHub.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="deployment-authentication">Authentication</label>
                    <select class="form-select" name="authentication" id="deployment-authentication" required>
                        <option value="none" @selected(old('authentication', $settings['authentication']) === 'none')>None (public repository)</option>
                        <option value="ssh" @selected(old('authentication', $settings['authentication']) === 'ssh')>SSH deploy key</option>
                        <option value="pat" @selected(old('authentication', $settings['authentication']) === 'pat')>Personal access token</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="deployment-credential">Replace credential</label>
                    <input class="form-control" type="password" id="deployment-credential" name="credential" value="" autocomplete="new-password" placeholder="{{ $credentialConfigured ? 'Saved credential retained when blank' : 'Only required for private repositories' }}" maxlength="10000">
                    <div class="form-text">Encrypted at rest and never displayed after saving.</div>
                </div>
                <div class="col-12 border-top pt-3">
                    <div class="d-flex flex-wrap gap-2">
                        @foreach([['run_migrations', 'Run migrations after deployment'], ['clear_cache', 'Clear and rebuild application cache'], ['health_check', 'Run a database health check']] as [$key, $label])
                            <label class="form-check rounded-3 bg-light px-3 py-2 mb-0">
                                <input class="form-check-input" type="checkbox" name="{{ $key }}" value="1" @checked((bool) old($key, $settings[$key]))>
                                <span class="form-check-label">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="col-12 d-flex flex-wrap gap-2">
                    <button class="btn btn-primary" type="submit">Save settings</button>
                    <button class="btn btn-outline-secondary" type="submit" formaction="{{ route('admin.git-repository.inspect') }}" formmethod="POST" formnovalidate @disabled(!$settings['enabled'] || $settings['repository'] === '')>Test connection</button>
                </div>
                <div class="col-12">
                    <details>
                        <summary class="small">cPanel delivery and GitHub Actions fallback</summary>
                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label" for="deployment-delivery">Deployment method</label>
                                <select class="form-select" name="delivery" id="deployment-delivery">
                                    <option value="git" @selected(old('delivery', $settings['delivery']) === 'git')>Server Git (no cPanel SSH login required)</option>
                                    <option value="zip" @selected(old('delivery', $settings['delivery']) === 'zip')>cPanel ZIP download and upload</option>
                                    <option value="ftps" @selected(old('delivery', $settings['delivery']) === 'ftps')>Automatic FTPS</option>
                                </select>
                                <div class="form-text">Server Git needs Git installed and callable by PHP. If the host disables it, choose ZIP or FTPS.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="deployment-php">Build PHP version</label>
                                <select class="form-select" id="deployment-php" name="php_version">
                                    @foreach(['8.3', '8.4'] as $phpVersion)
                                        <option value="{{ $phpVersion }}" @selected(old('php_version', $settings['php_version']) === $phpVersion)>PHP {{ $phpVersion }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Choose the PHP version configured for this cPanel domain. Detected here: {{ PHP_VERSION }}.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="deployment-token">GitHub Actions API token (ZIP/FTPS only)</label>
                                <input class="form-control" type="password" id="deployment-token" name="token" value="" autocomplete="new-password" placeholder="{{ $tokenConfigured ? 'Saved token retained when blank' : 'Fine-grained token with Contents read and Actions access' }}" maxlength="500">
                                <div class="form-text">Encrypted at rest. Used only to request/test GitHub Actions builds; it is not required for public Server Git repositories.</div>
                            </div>
                            <div class="col-12">
                                <ol class="small text-muted ps-3 mb-0">
                                    <li>Server Git runs the repository commands from this Laravel process; you do not need an SSH login to cPanel.</li>
                                    <li>For ZIP/FTPS, enable GitHub Actions and include <code>.github/workflows/deploy-release.yml</code>. FTPS requires the <code>production</code> environment with its host, user, password and directory settings.</li>
                                    <li>Keep <code>.env</code>, <code>storage/</code> and uploaded files on the host when using package delivery.</li>
                                </ol>
                            </div>
                        </div>
                    </details>
                </div>
            </div>
        </form>
    </div>
</section>

<section class="card border-0 shadow-sm mb-3">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
            <div>
                <div class="small text-uppercase fw-bold text-danger">Safe workflow</div>
                <h3 class="h5 fw-bold mb-0">Review before deployment</h3>
                <p class="small text-muted mb-0">Check incoming commits, review server files and database readiness, then deploy the reviewed update.</p>
            </div>
            <div class="small text-end text-muted">Installed commit: <code>{{ $installed['commit'] ?? 'Not recorded' }}</code><br>Branch: {{ $settings['branch'] }}</div>
        </div>
        <div class="row g-3">
            <div class="col-lg-4">
                <div class="border rounded-3 p-3 h-100 bg-light">
                    <span class="badge text-bg-dark mb-2">01</span>
                    <h4 class="h6 fw-bold">Check for updates</h4>
                    <p class="small text-muted">Fetch GitHub and review incoming commits without changing application files.</p>
                    <form action="{{ route('admin.git-repository.inspect') }}" method="POST">@csrf<button class="btn btn-primary btn-sm" type="submit" @disabled(!$settings['enabled'] || $settings['repository'] === '')>Check GitHub</button></form>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="border rounded-3 p-3 h-100 bg-light">
                    <span class="badge text-bg-dark mb-2">02</span>
                    <h4 class="h6 fw-bold">Review server status</h4>
                    <p class="small text-muted">Check tracked server changes and database readiness. Untracked uploads and storage files are kept.</p>
                    <form action="{{ route('admin.git-repository.server.review') }}" method="POST">@csrf<button class="btn btn-outline-primary btn-sm" type="submit">Review server status</button></form>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="border rounded-3 p-3 h-100 bg-light">
                    <span class="badge text-bg-dark mb-2">03</span>
                    <h4 class="h6 fw-bold">Deploy reviewed updates</h4>
                    @if($serverGit)
                        <p class="small text-muted">Fast-forward the configured branch only after Git status and the database checks pass.</p>
                        @if(($gitStatus['status'] ?? null) === 'Update Available' && $gitCheckFresh)
                            <form action="{{ route('admin.git-repository.deploy') }}" method="POST">
                                @csrf
                                <input type="hidden" name="commit" value="{{ $gitStatus['remote'] }}">
                                <div class="form-check small mb-2"><input class="form-check-input" type="checkbox" name="confirmation" value="1" id="confirm-server-git-deploy" required><label class="form-check-label" for="confirm-server-git-deploy">Confirm this reviewed update and its database backup.</label></div>
                                <button class="btn btn-primary btn-sm" type="submit">Deploy reviewed update</button>
                            </form>
                        @elseif(($gitStatus['status'] ?? null) === 'Update Available')
                            <button class="btn btn-primary btn-sm" type="button" disabled>Check GitHub again to review</button>
                        @elseif(($gitStatus['status'] ?? null) === 'Local Changes Detected' && $gitCheckFresh)
                            @if(!empty($gitStatus['commits']))
                                <details><summary class="small">Discard tracked edits and deploy</summary>
                                    <p class="small text-muted mt-2">This discards tracked server edits. Untracked files and uploaded assets remain.</p>
                                    <form action="{{ route('admin.git-repository.discard-and-deploy') }}" method="POST" class="d-flex flex-wrap gap-2 align-items-center">@csrf<input type="hidden" name="commit" value="{{ $gitStatus['remote'] }}"><input class="form-control form-control-sm" name="confirmation" placeholder="Type DISCARD LOCAL CHANGES" pattern="DISCARD LOCAL CHANGES" required style="max-width:260px;"><button class="btn btn-outline-danger btn-sm" type="submit">Discard tracked edits &amp; deploy</button></form>
                                </details>
                            @else
                                <p class="small text-muted mb-0">Tracked server changes block deployment, and no incoming update is available to deploy.</p>
                            @endif
                        @elseif(($gitStatus['status'] ?? null) === 'Local Changes Detected')
                            <button class="btn btn-outline-danger btn-sm" type="button" disabled>Check GitHub again before deploy</button>
                        @elseif(($gitStatus['status'] ?? null) === 'Diverged Branch')
                            <form action="{{ route('admin.git-repository.reset-to-remote') }}" method="POST" class="d-flex flex-wrap gap-2 align-items-center">@csrf<input class="form-control form-control-sm" name="confirmation" placeholder="Type RESET TO GITHUB" pattern="RESET TO GITHUB" required style="max-width:220px;"><button class="btn btn-outline-danger btn-sm" type="submit">Preserve history &amp; recover</button></form>
                        @else
                            <button class="btn btn-primary btn-sm" type="button" disabled>{{ ($gitStatus['status'] ?? null) === 'Up to date' ? 'No update available' : 'Check GitHub first' }}</button>
                        @endif
                    @elseif($preview)
                        <p class="small text-muted">GitHub tests and packages the selected commit. Confirm only after reviewing its result.</p>
                        <form action="{{ route('admin.git-repository.prepare') }}" method="POST">
                            @csrf
                            <input type="hidden" name="commit" value="{{ $preview['commit'] }}">
                            <button class="btn btn-primary btn-sm" type="submit">Build reviewed update</button>
                        </form>
                    @else
                        <p class="small text-muted">Build and review the package before sending files to cPanel.</p>
                        <button class="btn btn-primary btn-sm" type="button" disabled>Check GitHub first</button>
                    @endif
                </div>
            </div>
        </div>

        @if($serverGit && $gitError)
            <div class="alert alert-warning mt-3 mb-0">{{ $gitError }} For a host where Git is unavailable to PHP, select cPanel ZIP or Automatic FTPS in the delivery settings.</div>
        @endif

        @if($serverGit && $gitStatus)
            <div class="alert {{ in_array($gitStatus['status'] ?? '', ['Local Changes Detected', 'Diverged Branch', 'Branch Not Found'], true) ? 'alert-warning' : 'alert-info' }} mt-3 mb-0">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <div><strong>{{ $gitStatus['status'] ?? 'Git status unavailable' }}</strong><div class="small mt-1">{{ $gitStatus['message'] ?? '' }}</div></div>
                    <div class="small text-end">Server: <code>{{ isset($gitStatus['local']) ? substr($gitStatus['local'], 0, 12) : '—' }}</code><br>GitHub: <code>{{ isset($gitStatus['remote']) ? substr($gitStatus['remote'], 0, 12) : '—' }}</code></div>
                </div>
                @if(!empty($gitStatus['commits']))
                    <div class="small fw-bold mt-2">Incoming commits</div>
                    <ul class="small mb-0">@foreach($gitStatus['commits'] as $commit)<li><code>{{ $commit['short'] }}</code> {{ $commit['subject'] }}</li>@endforeach</ul>
                @endif
                @if(!empty($gitStatus['local_changes']))
                    <div class="small fw-bold mt-2">Tracked server changes</div>
                    <ul class="small mb-0">@foreach($gitStatus['local_changes'] as $change)<li><code>{{ $change }}</code></li>@endforeach</ul>
                @endif
                @if(!empty($gitStatus['untracked_files']))
                    <details class="small mt-2"><summary>Untracked files kept ({{ count($gitStatus['untracked_files']) }})</summary><ul class="mt-1 mb-0">@foreach($gitStatus['untracked_files'] as $file)<li><code>{{ $file }}</code></li>@endforeach</ul></details>
                @endif
            </div>
        @endif

        @if($preview)
            <div class="alert alert-primary mt-3 mb-0 text-break">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <div><strong>{{ $preview['repository'] }}</strong> <span class="badge text-bg-secondary">{{ $preview['private'] ? 'Private' : 'Public' }}</span> / {{ $preview['branch'] }}<br><code>{{ $preview['commit'] }}</code><div class="small mt-1">{{ $preview['message'] }}</div></div>
                    <a href="{{ $preview['url'] }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm align-self-start">View commit</a>
                </div>
            </div>
        @endif

        @if($serverReview)
            <div class="alert {{ $serverReview['status'] === 'attention' ? 'alert-warning' : 'alert-info' }} mt-3 mb-0">
                <strong>{{ $serverGit
                    ? ($serverReview['status'] === 'ready' ? 'No tracked server changes; database checks passed.' : 'Server or database review found changes that need attention.')
                    : ($serverReview['status'] === 'baseline_missing' ? 'No installed release baseline yet.' : ($serverReview['status'] === 'ready' ? 'Tracked application files match the installed release.' : 'Server review found changes that need attention.')) }}</strong>
                @if($serverReview['status'] === 'baseline_missing')<div class="small mt-1">This is expected before the first managed deployment. Database readiness is shown below.</div>@endif
                @if($serverReview['changed_files'])
                    <ul class="small mt-2 mb-0">@foreach($serverReview['changed_files'] as $changedFile)<li><code>{{ $changedFile['path'] }}</code> — {{ $changedFile['status'] }}</li>@endforeach</ul>
                    <div class="small mt-2">Restore or review these tracked files before deploying. Untracked uploads and storage files are preserved.</div>
                @endif
            </div>
        @endif

        @if($databaseReport)
            <div class="border-top mt-3 pt-3" id="deployment-database">
                <div class="d-flex justify-content-between align-items-center mb-2"><strong>Server and database checks</strong><span class="badge {{ $databaseReport['ok'] ? 'text-bg-success' : 'text-bg-warning' }}">{{ $databaseReport['ok'] ? 'Ready' : 'Review required' }}</span></div>
                <div class="row g-2">
                    @foreach($databaseReport['checks'] as $check)
                        <div class="col-lg-6"><div class="small border rounded p-2 h-100"><span class="badge {{ $check['ok'] ? 'text-bg-success' : 'text-bg-warning' }} me-1">{{ $check['ok'] ? 'Passed' : 'Attention' }}</span><strong>{{ ucfirst($check['name']) }}</strong><div class="text-muted mt-1">{{ $check['message'] }}</div></div></div>
                    @endforeach
                </div>
                @if($databaseReport['pending'])
                    <details class="mt-2"><summary class="small">Pending migrations ({{ count($databaseReport['pending']) }})</summary><ul class="small mt-2 mb-0">@foreach($databaseReport['pending'] as $migration)<li><code>{{ $migration }}</code></li>@endforeach</ul></details>
                @endif
            </div>
        @endif
    </div>
</section>

<section class="card border-0 shadow-sm" id="deployment-history">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div><div class="small text-uppercase fw-bold text-danger">Audit trail</div><h3 class="h5 fw-bold mb-0">Deployment history</h3></div>
            <div class="d-flex align-items-center gap-2">
                <span class="small text-muted">Latest 30 releases</span>
                @if($serverGit && $settings['enabled'] && $gitCheckFresh && ($gitStatus['status'] ?? null) === 'Update Available')
                    <details>
                        <summary class="btn btn-outline-secondary btn-sm">Pull latest</summary>
                        <form action="{{ route('admin.git-repository.pull') }}" method="POST" class="position-absolute bg-white border rounded shadow-sm p-3 mt-1" style="z-index:2;max-width:320px;">
                            @csrf<input type="hidden" name="commit" value="{{ $gitStatus['remote'] }}">
                            <div class="form-check small mb-2"><input class="form-check-input" type="checkbox" name="confirmation" value="1" id="confirm-history-pull" required><label class="form-check-label" for="confirm-history-pull">Back up the database, deploy this checked commit, and run the selected migration, cache and health checks.</label></div>
                            <button class="btn btn-outline-primary btn-sm" type="submit">Pull latest with checks</button>
                        </form>
                    </details>
                @endif
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>Date</th><th>Changes</th><th>Commit</th><th>Checks</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                @forelse($history as $release)
                    @php
                        $releaseChecks = $release['database_report']['checks'] ?? [];
                        $migrationCheck = collect($releaseChecks)->firstWhere('name', 'migrations');
                        $connectionCheck = collect($releaseChecks)->firstWhere('name', 'connection');
                    @endphp
                    <tr>
                        <td class="small text-nowrap">{{ \Illuminate\Support\Carbon::parse($release['created_at'])->format('m/d/Y, g:i A') }}</td>
                        <td class="small" style="min-width:190px;">{{ ($release['message'] ?? '') ?: (!empty($release['rollback_of']) ? 'Rollback of release '.substr($release['rollback_of'], 0, 8) : 'Deployment release') }}</td>
                        <td><code class="small">{{ substr($release['commit'], 0, 12) }}</code></td>
                        <td class="small" style="min-width:150px;">
                            @if($release['status'] === 'completed')
                                @if(($release['transport'] ?? null) === 'git')
                                    <span class="d-block">Migrations: {{ str_replace('_', ' ', $release['migration_status'] ?? 'not_run') }}</span>
                                    <span class="d-block">Health: {{ ucfirst($release['health_status'] ?? 'not_run') }}</span>
                                @else
                                    <span class="d-block">Migrations: {{ $migrationCheck ? ($migrationCheck['ok'] ? 'Passed' : 'Attention') : 'Passed' }}</span>
                                    <span class="d-block">Health: {{ $connectionCheck ? ($connectionCheck['ok'] ? 'Passed' : 'Attention') : 'Passed' }}</span>
                                @endif
                            @elseif($release['status'] === 'ready')
                                Tests and package passed
                            @else
                                {{ strtoupper($release['delivery']) }} delivery
                            @endif
                        </td>
                        <td><span class="badge rounded-pill {{ $release['status'] === 'completed' ? 'text-bg-success' : (in_array($release['status'], ['failed', 'verification_failed']) ? 'text-bg-danger' : 'text-bg-primary') }}">{{ ucwords(str_replace('_', ' ', $release['status'])) }}</span></td>
                        <td style="min-width:230px;">
                            @if(($release['transport'] ?? null) === 'git')
                                @if($release['backup'])<a href="{{ route('admin.git-repository.backup', $release['id']) }}" class="btn btn-outline-secondary btn-sm mb-2">SQL backup</a>@endif
                                @if($release['can_rollback'] && empty($release['rollback_of']))
                                    <details><summary class="small">Rollback source</summary>
                                        <p class="small text-muted mt-2">Creates a fresh database backup and resets source files to the previous commit. Database migrations are not reversed.</p>
                                        <form action="{{ route('admin.git-repository.rollback', $release['id']) }}" method="POST">@csrf<div class="form-check small mb-2"><input class="form-check-input" type="checkbox" name="confirmation" value="1" id="rollback-{{ $release['id'] }}" required><label class="form-check-label" for="rollback-{{ $release['id'] }}">Confirm source rollback</label></div><button class="btn btn-outline-danger btn-sm" type="submit">Rollback</button></form>
                                    </details>
                                @endif
                                <details class="mt-1"><summary class="small">Deployment log</summary><ul class="small text-muted mt-2 mb-0">@foreach($release['logs'] ?? [] as $line)<li>{{ $line }}</li>@endforeach</ul>@if(!empty($release['error_summary']))<div class="small text-danger mt-2">{{ $release['error_summary'] }}</div>@endif</details>
                            @elseif(in_array($release['status'], ['building', 'deploying']))
                                <form action="{{ route('admin.git-repository.refresh', $release['id']) }}" method="POST" class="d-flex flex-wrap gap-1 align-items-center">
                                    @csrf
                                    @if(!$release['run_id'])<input class="form-control form-control-sm" style="max-width:125px;" type="number" min="1" name="run_id" placeholder="Run ID" required>@endif
                                    <button class="btn btn-outline-primary btn-sm" type="submit">Refresh</button>
                                    @if(!empty($release['run_url']))<a href="{{ $release['run_url'] }}" target="_blank" rel="noopener noreferrer" class="small">Workflow</a>@endif
                                </form>
                            @elseif($release['status'] === 'ready')
                                <details><summary class="small">Review and confirm</summary>
                                    <form action="{{ route('admin.git-repository.approve', $release['id']) }}" method="POST" class="mt-2">
                                        @csrf<input type="hidden" name="commit" value="{{ $release['commit'] }}">
                                        <div class="form-check small mb-2"><input class="form-check-input" type="checkbox" name="confirmation" value="1" id="approve-{{ $release['id'] }}" required><label class="form-check-label" for="approve-{{ $release['id'] }}">Confirm this commit, hosting layout, and maintenance window. Back up the database before delivery.</label></div>
                                        <button class="btn btn-danger btn-sm" type="submit">Confirm deployment</button>
                                    </form>
                                </details>
                            @elseif($release['approved_at'])
                                <div class="d-flex flex-wrap gap-1 mb-2">
                                    <a href="{{ route('admin.git-repository.download', $release['id']) }}" class="btn btn-outline-primary btn-sm">Release ZIP</a>
                                    @if($release['backup'])<a href="{{ route('admin.git-repository.backup', $release['id']) }}" class="btn btn-outline-secondary btn-sm">SQL backup</a>@endif
                                </div>
                                @if(in_array($release['status'], ['awaiting_verification', 'verification_failed']))
                                    <form action="{{ route('admin.git-repository.finalize', $release['id']) }}" method="POST" class="mb-2">
                                        @csrf<div class="form-check small mb-2"><input class="form-check-input" type="checkbox" name="confirmation" value="1" id="finalize-{{ $release['id'] }}" required><label class="form-check-label" for="finalize-{{ $release['id'] }}">Upload is complete; verify files, apply known migrations, refresh caches, and check database.</label></div>
                                        <button class="btn btn-primary btn-sm" type="submit">Finalize and check DB</button>
                                    </form>
                                    <details><summary class="small">Manual recovery</summary><p class="small text-muted mt-2">Restore the previous files and, if needed, its SQL backup using cPanel. Restore its deployment manifest too; if none existed, remove the new manifest. Then verify recovery here.</p>
                                        <form action="{{ route('admin.git-repository.recover', $release['id']) }}" method="POST" class="d-flex gap-1 align-items-end">@csrf<input class="form-control form-control-sm" name="confirmation" placeholder="Type RECOVERED" required pattern="RECOVERED"><button class="btn btn-outline-danger btn-sm" type="submit">Verify recovery</button></form>
                                    </details>
                                @endif
                                @if($release['can_rollback'])
                                    <details><summary class="small">Rollback</summary>
                                        <p class="small text-muted mt-2">Prepare this previously deployed source package, create a fresh database backup, then upload it. Database migrations will not be reversed automatically.</p>
                                        <form action="{{ route('admin.git-repository.rollback', $release['id']) }}" method="POST">@csrf<div class="form-check small mb-2"><input class="form-check-input" type="checkbox" name="confirmation" value="1" id="rollback-{{ $release['id'] }}" required><label class="form-check-label" for="rollback-{{ $release['id'] }}">Confirm rollback to this commit</label></div><button class="btn btn-outline-danger btn-sm" type="submit">Prepare rollback</button></form>
                                    </details>
                                @endif
                            @else
                                <details><summary class="small">Release log</summary><ul class="small text-muted mt-2 mb-0">@foreach($release['logs'] as $line)<li>{{ $line }}</li>@endforeach</ul></details>
                            @endif
                        </td>
                    </tr>
                    @if(in_array($release['status'], ['building', 'deploying', 'awaiting_verification', 'verification_failed', 'recovered', 'failed']) || !empty($release['rollback_of']))
                        <tr><td colspan="6" class="border-0 pt-0"><details><summary class="small text-muted">{{ !empty($release['rollback_of']) ? 'Rollback record' : 'Deployment log' }}</summary><ul class="small text-muted mt-2 mb-0">@foreach($release['logs'] as $line)<li>{{ $line }}</li>@endforeach</ul></details></td></tr>
                    @endif
                @empty
                    <tr><td colspan="6" class="text-muted py-3">No releases yet. Connect the repository and check GitHub to prepare the first release.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
