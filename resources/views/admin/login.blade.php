<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login - Zero Gravities</title>
    @php
        $adminStoredAssetUrl = fn (?string $path, string $fallback): string => $path ? url(\Illuminate\Support\Facades\Storage::url($path)) : asset($fallback);
        $adminFaviconUrl = $adminStoredAssetUrl($globalSettings['favicon'] ?? null, 'favicon.ico');
        $adminAppleTouchIconUrl = $adminStoredAssetUrl($globalSettings['apple_touch_icon'] ?? ($globalSettings['favicon'] ?? null), 'favicon.ico');
    @endphp
    <link rel="icon" href="{{ $adminFaviconUrl }}">
    <link rel="apple-touch-icon" href="{{ $adminAppleTouchIconUrl }}">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body {
            background-color: #0d1b2a; /* Deep Navy */
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center vh-100">

    <div class="card p-4 shadow-lg" style="width: 400px;">
        <div class="text-center mb-4">
            <h2 class="fw-bold">Zero Gravities</h2>
            <p class="text-muted">Admin Panel Login</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger p-2 mb-3">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.login') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="username" class="form-label">User Name</label>
                <input type="text" class="form-control" id="username" name="username" value="{{ old('username') }}" required autofocus autocomplete="username">
            </div>
            
            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-2">Login</button>
        </form>
    </div>

</body>
</html>
