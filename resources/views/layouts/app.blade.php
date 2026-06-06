<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Email Validator Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 260px;
            --primary: #7b2ff7;
            --accent: #00d4ff;
            --dark: #0f0f1a;
            --card-bg: #1a1a2e;
            --border: rgba(255,255,255,0.08);
        }
        body { background: #0a0a14; color: #e0e0e8; font-family: 'Segoe UI', sans-serif; }
        /* Sidebar */
        .sidebar { position: fixed; top: 0; left: 0; height: 100vh; width: var(--sidebar-width); background: var(--card-bg); border-right: 1px solid var(--border); z-index: 1000; overflow-y: auto; transition: transform 0.3s; }
        .sidebar-brand { padding: 1.5rem 1.25rem; border-bottom: 1px solid var(--border); }
        .brand-logo { font-size: 1.1rem; font-weight: 800; background: linear-gradient(135deg, var(--accent), var(--primary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .sidebar .nav-link { color: rgba(255,255,255,0.6); padding: 0.6rem 1.25rem; border-radius: 8px; margin: 2px 0.5rem; display: flex; align-items: center; gap: 0.75rem; transition: all 0.2s; font-size: 0.9rem; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(123,47,247,0.15); color: #fff; }
        .sidebar .nav-link i { width: 18px; text-align: center; }
        .sidebar-section { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: rgba(255,255,255,0.3); padding: 1rem 1.5rem 0.25rem; }
        /* Main content */
        .main-content { margin-left: var(--sidebar-width); padding: 0; min-height: 100vh; }
        /* Topbar */
        .topbar { background: var(--card-bg); border-bottom: 1px solid var(--border); padding: 0.75rem 1.5rem; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; }
        .topbar .page-title { font-size: 1.1rem; font-weight: 600; margin: 0; }
        .credit-badge { background: rgba(123,47,247,0.2); border: 1px solid rgba(123,47,247,0.4); padding: 0.35rem 0.8rem; border-radius: 20px; font-size: 0.8rem; color: #c084fc; }
        /* Cards */
        .card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 12px; }
        .card-header { background: transparent; border-bottom: 1px solid var(--border); }
        /* Stat cards */
        .stat-card { border-radius: 12px; padding: 1.25rem; background: var(--card-bg); border: 1px solid var(--border); transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
        /* Tables */
        .table { color: #e0e0e8; }
        .table thead th { background: rgba(255,255,255,0.05); border-color: var(--border); font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255,255,255,0.5); font-weight: 600; }
        .table tbody td { border-color: var(--border); vertical-align: middle; }
        .table tbody tr:hover { background: rgba(255,255,255,0.03); }
        /* Badges */
        .badge-valid { background: rgba(25,135,84,0.2); color: #6feaaa; border: 1px solid rgba(25,135,84,0.3); }
        .badge-invalid { background: rgba(220,53,69,0.2); color: #ff8a9a; border: 1px solid rgba(220,53,69,0.3); }
        .badge-risky { background: rgba(255,193,7,0.2); color: #ffd60a; border: 1px solid rgba(255,193,7,0.3); }
        .badge-unknown { background: rgba(108,117,125,0.2); color: #adb5bd; border: 1px solid rgba(108,117,125,0.3); }
        /* Buttons */
        .btn-primary { background: linear-gradient(135deg, #7b2ff7, #00d4ff); border: none; }
        .btn-primary:hover { opacity: 0.9; box-shadow: 0 4px 15px rgba(123,47,247,0.4); }
        /* Forms */
        .form-control, .form-select { background: rgba(255,255,255,0.05); border: 1px solid var(--border); color: #e0e0e8; }
        .form-control:focus, .form-select:focus { background: rgba(255,255,255,0.08); border-color: var(--primary); color: #fff; box-shadow: 0 0 0 0.2rem rgba(123,47,247,0.2); }
        .form-control::placeholder { color: rgba(255,255,255,0.3); }
        .form-label { color: rgba(255,255,255,0.7); font-size: 0.875rem; }
        /* Alert */
        .alert-success { background: rgba(25,135,84,0.15); border-color: rgba(25,135,84,0.3); color: #6feaaa; }
        .alert-danger { background: rgba(220,53,69,0.15); border-color: rgba(220,53,69,0.3); color: #ff8a9a; }
        .alert-warning { background: rgba(255,193,7,0.15); border-color: rgba(255,193,7,0.3); color: #ffd60a; }
        .alert-info { background: rgba(13,202,240,0.15); border-color: rgba(13,202,240,0.3); color: #6ff0ff; }
        /* Sidebar credits */
        .sidebar-user { padding: 1rem 1.25rem; border-top: 1px solid var(--border); margin-top: auto; }
        /* Mobile */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; }
        }
        /* Progress */
        .progress { background: rgba(255,255,255,0.08); }
        /* Scrollbar */
        ::-webkit-scrollbar { width: 5px; } ::-webkit-scrollbar-track { background: transparent; } ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 3px; }
        /* Page content */
        .page-content { padding: 1.5rem; }
    </style>
    @stack('styles')
</head>
<body>

<!-- Sidebar -->
<nav class="sidebar d-flex flex-column" id="sidebar">
    <div class="sidebar-brand">
        <span class="brand-logo">
            <i class="fas fa-envelope-circle-check me-2"></i>EV Pro
        </span>
    </div>

    <div class="mt-2 flex-grow-1">
        <div class="sidebar-section">Main</div>
        <a href="{{ route('user.dashboard') }}" class="nav-link {{ request()->routeIs('user.dashboard') ? 'active' : '' }}">
            <i class="fas fa-gauge-high"></i> Dashboard
        </a>
        <a href="{{ route('user.validate') }}" class="nav-link {{ request()->routeIs('user.validate') ? 'active' : '' }}">
            <i class="fas fa-magnifying-glass"></i> Validate Email
        </a>
        <a href="{{ route('user.bulk.index') }}" class="nav-link {{ request()->routeIs('user.bulk.*') ? 'active' : '' }}">
            <i class="fas fa-list-check"></i> Bulk Validation
        </a>

        <div class="sidebar-section">Account</div>
        <a href="{{ route('user.api-keys.index') }}" class="nav-link {{ request()->routeIs('user.api-keys.*') ? 'active' : '' }}">
            <i class="fas fa-key"></i> API Keys
        </a>
        <a href="{{ route('user.billing') }}" class="nav-link {{ request()->routeIs('user.billing') ? 'active' : '' }}">
            <i class="fas fa-credit-card"></i> Billing
        </a>
        <a href="{{ route('user.webhooks') }}" class="nav-link {{ request()->routeIs('user.webhooks') ? 'active' : '' }}">
            <i class="fas fa-bolt"></i> Webhooks
        </a>
        <a href="{{ route('user.account') }}" class="nav-link {{ request()->routeIs('user.account') ? 'active' : '' }}">
            <i class="fas fa-user-gear"></i> Account Settings
        </a>

        @if(auth()->user()?->isAdmin())
        <div class="sidebar-section">Admin</div>
        <a href="{{ route('admin.dashboard') }}" class="nav-link">
            <i class="fas fa-shield-halved"></i> Admin Panel
        </a>
        @endif
    </div>

    <div class="sidebar-user">
        <div class="d-flex align-items-center gap-2 mb-2">
            <div style="width:32px;height:32px;background:linear-gradient(135deg,#7b2ff7,#00d4ff);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;font-weight:700;">
                {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}
            </div>
            <div style="min-width:0;">
                <div style="font-size:0.82rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ auth()->user()?->name }}</div>
                <div style="font-size:0.72rem;color:rgba(255,255,255,0.4);">{{ number_format(auth()->user()?->credit_balance ?? 0) }} credits</div>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-sm w-100" style="background:rgba(220,53,69,0.15);border:1px solid rgba(220,53,69,0.3);color:#ff8a9a;">
                <i class="fas fa-right-from-bracket me-1"></i> Logout
            </button>
        </form>
    </div>
</nav>

<!-- Main content -->
<div class="main-content">
    <!-- Topbar -->
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm d-md-none" style="background:rgba(255,255,255,0.08);border:none;color:#fff;" onclick="document.getElementById('sidebar').classList.toggle('show')">
                <i class="fas fa-bars"></i>
            </button>
            <h6 class="page-title">@yield('page-title', 'Dashboard')</h6>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="credit-badge d-none d-sm-inline-flex">
                <i class="fas fa-coins me-1"></i>
                {{ number_format(auth()->user()?->credit_balance ?? 0) }} Credits
            </span>
            <a href="{{ route('user.account') }}" class="text-decoration-none">
                <div style="width:36px;height:36px;background:linear-gradient(135deg,#7b2ff7,#00d4ff);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;">
                    {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}
                </div>
            </a>
        </div>
    </div>

    <!-- Flash Messages -->
    <div class="px-4 pt-3">
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-circle-check me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-circle-exclamation me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-triangle-exclamation me-2"></i>
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
    </div>

    <!-- Page content -->
    <div class="page-content">
        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
@stack('scripts')
</body>
</html>
