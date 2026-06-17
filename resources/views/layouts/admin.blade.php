<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') - Novelio Technologies LLC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --sidebar-width: 260px; }
        body { background: #111827; color: #e8eaf6; font-family: 'Segoe UI', sans-serif; }
        .sidebar { position: fixed; top: 0; left: 0; height: 100vh; width: var(--sidebar-width); background: #1a2235; border-right: 1px solid rgba(255,255,255,0.12); z-index: 1000; overflow-y: auto; }
        .sidebar-brand { padding: 1rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.08); }
        .brand-logo { font-size: 0.7rem; font-weight: 700; color: rgba(255,255,255,0.5); letter-spacing: 0.04em; text-transform: uppercase; margin-top: 3px; }
        .brand-logo img { max-width: 150px; height: auto; }
        .sidebar .nav-link { color: rgba(255,255,255,0.6); padding: 0.6rem 1rem; border-radius: 8px; margin: 2px 0.5rem; display: flex; align-items: center; gap: 0.75rem; font-size: 0.875rem; transition: all 0.2s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,107,107,0.1); color: #ff6b6b; }
        .sidebar .nav-link i { width: 18px; text-align: center; }
        .sidebar-section { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: rgba(255,255,255,0.25); padding: 1rem 1.5rem 0.25rem; }
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; }
        .topbar { background: #1a2235; border-bottom: 1px solid rgba(255,255,255,0.12); padding: 0.75rem 1.5rem; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; }
        .admin-badge { background: rgba(255,107,107,0.2); border: 1px solid rgba(255,107,107,0.4); padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.7rem; color: #ff6b6b; font-weight: 700; text-transform: uppercase; }
        .card { background: #1e2538; border: 1px solid rgba(255,255,255,0.12); border-radius: 12px; }
        .card-header { background: transparent; border-bottom: 1px solid rgba(255,255,255,0.08); }
        .table { color: #e0e0e8; }
        .table thead th { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.08); font-size: 0.78rem; text-transform: uppercase; color: rgba(255,255,255,0.5); font-weight: 600; }
        .table tbody td { border-color: rgba(255,255,255,0.06); vertical-align: middle; }
        .btn-primary { background: #ff6b6b; border: none; }
        .form-control, .form-select { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #e0e0e8; }
        .form-control:focus, .form-select:focus { background: rgba(255,255,255,0.08); border-color: #ff6b6b; color: #fff; box-shadow: none; }
        .alert-success { background: rgba(25,135,84,0.15); border-color: rgba(25,135,84,0.3); color: #6feaaa; }
        .alert-danger { background: rgba(220,53,69,0.15); border-color: rgba(220,53,69,0.3); color: #ff8a9a; }
        .page-content { padding: 1.5rem; }
        ::-webkit-scrollbar { width: 5px; } ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 3px; }
    </style>
    @stack('styles')
</head>
<body>
<nav class="sidebar">
    <div class="sidebar-brand text-center">
        <a href="{{ url('/') }}" class="text-decoration-none d-block">
            <img src="{{ asset('images/novelio-logo.webp') }}" alt="Novelio Technologies LLC" style="max-width:155px;height:auto;filter:brightness(1.1);">
        </a>
        <div class="brand-logo mt-1"><i class="fas fa-shield-halved me-1" style="color:#ff6b6b;"></i>Admin Panel</div>
    </div>
    <div class="mt-2">
        <div class="sidebar-section">Overview</div>
        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><i class="fas fa-gauge-high"></i> Dashboard</a>

        <div class="sidebar-section">Users</div>
        <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"><i class="fas fa-users"></i> All Users</a>

        <div class="sidebar-section">Validation</div>
        <a href="{{ route('admin.jobs.index') }}" class="nav-link {{ request()->routeIs('admin.jobs.*') ? 'active' : '' }}"><i class="fas fa-list-check"></i> Bulk Jobs</a>
        <a href="{{ route('admin.smtp-servers.index') }}" class="nav-link {{ request()->routeIs('admin.smtp-servers.*') ? 'active' : '' }}"><i class="fas fa-server"></i> SMTP Servers</a>

        <div class="sidebar-section">Finance</div>
        <a href="{{ route('admin.plans.index') }}" class="nav-link {{ request()->routeIs('admin.plans.*') ? 'active' : '' }}"><i class="fas fa-tags"></i> Plans</a>
        <a href="{{ route('admin.transactions.index') }}" class="nav-link {{ request()->routeIs('admin.transactions.*') ? 'active' : '' }}"><i class="fas fa-receipt"></i> Transactions</a>

        <div class="sidebar-section">System</div>
        <a href="{{ route('admin.system') }}" class="nav-link {{ request()->routeIs('admin.system') ? 'active' : '' }}"><i class="fas fa-server"></i> System Health</a>
        <a href="{{ route('admin.audit') }}" class="nav-link {{ request()->routeIs('admin.audit') ? 'active' : '' }}"><i class="fas fa-clipboard-list"></i> Audit Log</a>
        <a href="{{ route('admin.settings') }}" class="nav-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}"><i class="fas fa-gear"></i> Settings</a>

        <div class="sidebar-section mt-3">
        <a href="{{ route('user.dashboard') }}" class="nav-link"><i class="fas fa-arrow-left"></i> User Panel</a>
        </div>
    </div>
</nav>

<div class="main-content">
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <h6 class="mb-0" style="font-size:1rem;font-weight:600;">@yield('page-title', 'Dashboard')</h6>
            <span class="admin-badge">Admin</span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span style="font-size:0.85rem;color:rgba(255,255,255,0.5);">{{ auth()->user()?->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-sm" style="background:rgba(220,53,69,0.15);border:1px solid rgba(220,53,69,0.3);color:#ff8a9a;font-size:0.8rem;">
                    <i class="fas fa-right-from-bracket"></i>
                </button>
            </form>
        </div>
    </div>

    <div class="px-4 pt-3">
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
    </div>

    <div class="page-content">
        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@stack('scripts')
</body>
</html>
