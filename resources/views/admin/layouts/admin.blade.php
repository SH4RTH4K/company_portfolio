<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Panel - Zero Gravities</title>
    @php
        $adminStoredAssetUrl = fn (?string $path, string $fallback): string => $path ? url(\Illuminate\Support\Facades\Storage::url($path)) : asset($fallback);
        $adminFaviconUrl = $adminStoredAssetUrl($globalSettings['favicon'] ?? null, 'favicon.ico');
        $adminAppleTouchIconUrl = $adminStoredAssetUrl($globalSettings['apple_touch_icon'] ?? ($globalSettings['favicon'] ?? null), 'favicon.ico');
        $adminSiteName = $globalSettings['site_name'] ?? 'Zero Gravities';
    @endphp
    <link rel="icon" href="{{ $adminFaviconUrl }}">
    <link rel="apple-touch-icon" href="{{ $adminAppleTouchIconUrl }}">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        body {
            background-color: #f4f6f9;
        }
        .sidebar {
            width: 260px;
            flex: 0 0 260px;
            min-height: 100vh;
            background-color: #0d1b2a; /* Deep Navy */
        }
        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-height: 48px;
            padding: 0.35rem 0.4rem;
            margin-bottom: 1.5rem;
            color: #fff;
            text-decoration: none;
        }
        .sidebar-brand:hover {
            color: #fff;
        }
        .sidebar-brand-mark {
            width: 38px;
            height: 38px;
            flex: 0 0 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background-color: #fff;
            box-shadow: 0 10px 24px rgba(0, 180, 216, 0.18);
            overflow: hidden;
        }
        .sidebar-brand-mark img {
            width: 30px;
            height: 30px;
            object-fit: contain;
        }
        .sidebar-brand-name {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 1.08rem;
            line-height: 1.1;
            font-weight: 800;
        }
        .sidebar .nav-link {
            display: flex;
            align-items: flex-start;
            gap: 0.65rem;
            color: #e0e1dd;
            padding: 0.75rem 1rem;
            line-height: 1.35;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255,255,255,0.1);
        }
        .sidebar .nav-link i {
            width: 1.25rem;
            flex: 0 0 1.25rem;
            margin-right: 0 !important;
            text-align: center;
        }
        .sidebar-subnav {
            display: grid;
            gap: 0.15rem;
            margin: 0.25rem 0 0.35rem 1.1rem;
        }
        .sidebar-subnav-link {
            align-items: flex-start;
            border-radius: 8px;
            color: #e0e1dd;
            display: flex;
            font-size: 1rem;
            gap: 0.65rem;
            line-height: 1.35;
            padding: 0.64rem 1rem;
            text-decoration: none;
        }
        .sidebar-subnav-link:hover,
        .sidebar-subnav-link:focus {
            background-color: rgba(255,255,255,0.08);
            color: #fff;
        }
        .sidebar-subnav-link i {
            color: #e0e1dd;
            flex: 0 0 1.25rem;
            font-size: 1rem;
            text-align: center;
            width: 1.25rem;
        }
        .content-area {
            flex-grow: 1;
            min-width: 0;
            padding: 20px;
        }
        .admin-page-kicker {
            color: #0d6efd;
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0;
            text-transform: uppercase;
        }
        .admin-stat-card {
            border: 0;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 10px 30px rgba(13, 27, 42, 0.06);
        }
        .client-logo-frame {
            width: 58px;
            height: 58px;
            flex: 0 0 58px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background: #fff;
            overflow: hidden;
        }
        .client-logo-frame img {
            max-width: 46px;
            max-height: 40px;
            object-fit: contain;
        }
        .client-logo-placeholder {
            background: linear-gradient(135deg, #0d1b2a, #0d6efd);
            color: #fff;
            font-size: 1.15rem;
            font-weight: 800;
        }
        .client-name-cell {
            min-width: 220px;
        }
        .client-website-link {
            max-width: 260px;
            display: inline-block;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: bottom;
            white-space: nowrap;
        }
        .admin-account-toggle {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            border: 1px solid #dee2e6;
            border-radius: 999px;
            padding: 0.35rem 0.65rem 0.35rem 0.4rem;
            background-color: #fff;
            color: #0d1b2a;
        }
        .admin-account-toggle:hover,
        .admin-account-toggle:focus {
            border-color: #b8c2cc;
            color: #0d1b2a;
            background-color: #f8fafc;
        }
        .admin-avatar {
            width: 34px;
            height: 34px;
            flex: 0 0 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: #0d1b2a;
            color: #fff;
            font-weight: 800;
            line-height: 1;
        }
        .admin-account-meta {
            min-width: 0;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            line-height: 1.1;
        }
        .admin-account-name {
            max-width: 160px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-weight: 700;
        }
        .admin-account-role {
            color: #6c757d;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .admin-account-dropdown .dropdown-menu {
            min-width: 220px;
        }
        @media (max-width: 575.98px) {
            .admin-account-meta {
                display: none;
            }
        }
    </style>
</head>
<body class="d-flex">
    
    <!-- Sidebar -->
    <div class="sidebar p-3 d-flex flex-column">
        <a href="{{ route('admin.dashboard') }}" class="sidebar-brand" title="{{ $adminSiteName }}">
            <span class="sidebar-brand-mark">
                <img src="{{ $adminFaviconUrl }}" alt="{{ $adminSiteName }}">
            </span>
            <span class="sidebar-brand-name">{{ $adminSiteName }}</span>
        </a>
        @php
            $settingsSections = [
                ['id' => 'company-settings', 'icon' => 'bi-building', 'label' => 'Company & Contact'],
                ['id' => 'email-settings', 'icon' => 'bi-envelope-at', 'label' => 'Email Integration'],
                ['id' => 'language-settings', 'icon' => 'bi-translate', 'label' => 'Language & Theme'],
                ['id' => 'development-settings', 'icon' => 'bi-code-square', 'label' => 'Development Mode'],
                ['id' => 'brand-settings', 'icon' => 'bi-image', 'label' => 'Brand & Logo'],
                ['id' => 'social-settings', 'icon' => 'bi-share', 'label' => 'Social Links'],
            ];
            $isSuperAdmin = auth()->user()?->isSuperAdmin() ?? false;
        @endphp
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.leads.index') }}" class="nav-link {{ request()->routeIs('admin.leads.*') ? 'active' : '' }}">
                    <i class="bi bi-envelope-paper me-2"></i> Contact Messages
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.business-divisions.index') }}" class="nav-link {{ request()->routeIs('admin.business-divisions.*') ? 'active' : '' }}">
                    <i class="bi bi-building me-2"></i> Business Divisions
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.services.index') }}" class="nav-link {{ request()->routeIs('admin.services.*') ? 'active' : '' }}">
                    <i class="bi bi-tools me-2"></i> Services
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.homepage-hero.index') }}" class="nav-link {{ request()->routeIs('admin.homepage-hero.*') ? 'active' : '' }}">
                    <i class="bi bi-stars me-2"></i> Homepage Hero
                </a>
            </li>
            
            <li class="nav-item mt-3 mb-1 text-white-50 small text-uppercase fw-bold px-3">Products</li>
            <li class="nav-item">
                <a href="{{ route('admin.products.index') }}" class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                    <i class="bi bi-box me-2"></i> All Products
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.product-categories.index') }}" class="nav-link {{ request()->routeIs('admin.product-categories.*') ? 'active' : '' }}">
                    <i class="bi bi-tags me-2"></i> Categories
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.amc-packages.index') }}" class="nav-link {{ request()->routeIs('admin.amc-packages.*') ? 'active' : '' }}">
                    <i class="bi bi-shield-check me-2"></i> AMC Packages
                </a>
            </li>

            <li class="nav-item mt-3 mb-1 text-white-50 small text-uppercase fw-bold px-3">Company</li>
            <li class="nav-item">
                <a href="{{ route('admin.industries.index') }}" class="nav-link {{ request()->routeIs('admin.industries.*') ? 'active' : '' }}">
                    <i class="bi bi-building-gear me-2"></i> Industries
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.portfolios.index') }}" class="nav-link {{ request()->routeIs('admin.portfolios.*') ? 'active' : '' }}">
                    <i class="bi bi-briefcase me-2"></i> Portfolios
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.clients.index') }}" class="nav-link {{ request()->routeIs('admin.clients.*') ? 'active' : '' }}">
                    <i class="bi bi-people me-2"></i> Clients
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.partners.index') }}" class="nav-link {{ request()->routeIs('admin.partners.*') ? 'active' : '' }}">
                    <i class="bi bi-patch-check me-2"></i> Partners
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.teams.index') }}" class="nav-link {{ request()->routeIs('admin.teams.*') ? 'active' : '' }}">
                    <i class="bi bi-person-badge me-2"></i> Team
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.testimonials.index') }}" class="nav-link {{ request()->routeIs('admin.testimonials.*') ? 'active' : '' }}">
                    <i class="bi bi-chat-quote me-2"></i> Testimonials
                </a>
            </li>

            <li class="nav-item mt-3 mb-1 text-white-50 small text-uppercase fw-bold px-3">Blog</li>
            <li class="nav-item">
                <a href="{{ route('admin.posts.index') }}" class="nav-link {{ request()->routeIs('admin.posts.*') ? 'active' : '' }}">
                    <i class="bi bi-file-post me-2"></i> Posts
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.post-categories.index') }}" class="nav-link {{ request()->routeIs('admin.post-categories.*') ? 'active' : '' }}">
                    <i class="bi bi-folder me-2"></i> Post Categories
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.faqs.index') }}" class="nav-link {{ request()->routeIs('admin.faqs.*') ? 'active' : '' }}">
                    <i class="bi bi-question-circle me-2"></i> FAQs
                </a>
            </li>

            <li class="nav-item mt-3 mb-1 text-white-50 small text-uppercase fw-bold px-3">SEO</li>
            <li class="nav-item">
                <a href="{{ route('admin.seo.dashboard') }}" class="nav-link {{ request()->routeIs('admin.seo.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-graph-up-arrow me-2"></i> SEO Dashboard
                </a>
            </li>
            @if($isSuperAdmin)
                <li class="nav-item">
                    <a href="{{ route('admin.seo.robots') }}" class="nav-link {{ request()->routeIs('admin.seo.robots') ? 'active' : '' }}">
                        <i class="bi bi-robot me-2"></i> Robots.txt
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.seo.scripts') }}" class="nav-link {{ request()->routeIs('admin.seo.scripts') ? 'active' : '' }}">
                        <i class="bi bi-code-slash me-2"></i> Global Scripts
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.redirects.index') }}" class="nav-link {{ request()->routeIs('admin.redirects.*') ? 'active' : '' }}">
                        <i class="bi bi-sign-turn-right me-2"></i> Redirects
                    </a>
                </li>
            @endif
            <li class="nav-item">
                <a href="{{ route('admin.not-found-logs.index') }}" class="nav-link {{ request()->routeIs('admin.not-found-logs.*') ? 'active' : '' }}">
                    <i class="bi bi-exclamation-triangle me-2"></i> 404 Monitor
                </a>
            </li>

            @if($isSuperAdmin)
                <li class="nav-item mt-3 mb-1 text-white-50 small text-uppercase fw-bold px-3">System</li>
                <li class="nav-item">
                    <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <i class="bi bi-person-badge-fill me-2"></i> Users
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.roles.index') }}" class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                        <i class="bi bi-shield-lock me-2"></i> Roles
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.audit-logs.index') }}" class="nav-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}">
                        <i class="bi bi-clock-history me-2"></i> Audit Logs
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.recycle-bin.index') }}" class="nav-link {{ request()->routeIs('admin.recycle-bin.*') ? 'active' : '' }}">
                        <i class="bi bi-trash3 me-2"></i> Recycle Bin
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.cache.index') }}" class="nav-link {{ request()->routeIs('admin.cache.*') ? 'active' : '' }}">
                        <i class="bi bi-hdd-network me-2"></i> Cache Management
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.system-health.index') }}" class="nav-link {{ request()->routeIs('admin.system-health.*') ? 'active' : '' }}">
                        <i class="bi bi-heart-pulse me-2"></i> System Health &amp; Backups
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.settings.index') }}" class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                        <i class="bi bi-gear me-2"></i> Settings
                    </a>
                    @if(request()->routeIs('admin.settings.*'))
                        <div class="sidebar-subnav" aria-label="Settings sections">
                            @foreach($settingsSections as $settingsSection)
                                <a href="{{ route('admin.settings.index') }}#{{ $settingsSection['id'] }}" class="sidebar-subnav-link">
                                    <i class="bi {{ $settingsSection['icon'] }}"></i>
                                    <span>{{ $settingsSection['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </li>
            @endif
            <!-- Add other menu items here as needed -->
        </ul>
        
        <hr class="text-white">
        
        <form action="{{ route('admin.logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-outline-light w-100">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
            </button>
        </form>
    </div>

    <!-- Main Content -->
    <div class="content-area">
        @php
            $adminUser = Auth::user();
            $adminName = $adminUser?->name ?? 'Admin';
            $adminInitial = str($adminName)->substr(0, 1)->upper();
            $adminRole = $adminUser?->role ? str($adminUser->role)->replace('_', ' ')->title() : 'Admin';
        @endphp

        <nav class="navbar navbar-expand-lg navbar-light bg-white rounded shadow-sm mb-4 p-3">
            <div class="container-fluid">
                <span class="navbar-brand mb-0 h1">@yield('title', 'Dashboard')</span>
                
                <div class="d-flex align-items-center">
                    <div class="dropdown admin-account-dropdown">
                        <button class="btn admin-account-toggle dropdown-toggle" type="button" id="adminAccountMenuButton" aria-expanded="false" aria-controls="adminAccountMenu">
                            <span class="admin-avatar">{{ $adminInitial }}</span>
                            <span class="admin-account-meta">
                                <span class="admin-account-name">{{ $adminName }}</span>
                                <span class="admin-account-role">{{ $adminRole }}</span>
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" id="adminAccountMenu" aria-labelledby="adminAccountMenuButton">
                            @if($adminUser)
                                <li>
                                    <a class="dropdown-item" href="{{ route('admin.users.edit', $adminUser) }}">
                                        <i class="bi bi-person-circle me-2"></i> Profile
                                    </a>
                                </li>
                            @endif
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <form action="{{ route('admin.logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmxc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        const adminAccountButton = document.getElementById('adminAccountMenuButton');
        const adminAccountMenu = document.getElementById('adminAccountMenu');

        if (adminAccountButton && adminAccountMenu) {
            const closeAdminAccountMenu = () => {
                adminAccountButton.setAttribute('aria-expanded', 'false');
                adminAccountMenu.classList.remove('show');
            };

            adminAccountButton.addEventListener('click', (event) => {
                event.stopPropagation();

                const isOpen = adminAccountMenu.classList.toggle('show');
                adminAccountButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });

            document.addEventListener('click', (event) => {
                if (! adminAccountMenu.contains(event.target)) {
                    closeAdminAccountMenu();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeAdminAccountMenu();
                }
            });
        }
    </script>
</body>
</html>
