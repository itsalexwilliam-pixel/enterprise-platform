<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Novelio Technologies LLC</title>
    <link rel="icon" type="image/webp" href="{{ asset('images/novelio-logo.webp') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        /* ============================================================
           THEME TOKENS
           ============================================================ */
        [data-theme="dark"] {
            --bg: #0a0b1a; --sidebar-bg: #10102a; --card-bg: #13132a; --topbar-bg: #10102a;
            --border: rgba(255,255,255,0.10); --text: #e8eaf6; --text-muted: rgba(255,255,255,0.62);
            --primary: #7b2ff7; --accent: #00d4ff; --table-hover: rgba(123,47,247,0.07);
            --btn-grad: linear-gradient(135deg,#7b2ff7,#00d4ff);
            --nav-active-bg: rgba(123,47,247,0.15); --nav-active-color: #fff;
            --badge-bg: rgba(123,47,247,0.20); --badge-border: rgba(123,47,247,0.40); --badge-color: #c084fc;
        }
        [data-theme="light"] {
            --bg: #f0f2f8; --sidebar-bg: #ffffff; --card-bg: #ffffff; --topbar-bg: #ffffff;
            --border: rgba(0,0,0,0.10); --text: #1a1a2e; --text-muted: #5a6278;
            --primary: #7b2ff7; --accent: #00b4d8; --table-hover: rgba(123,47,247,0.04);
            --btn-grad: linear-gradient(135deg,#7b2ff7,#00b4d8);
            --nav-active-bg: rgba(123,47,247,0.10); --nav-active-color: #7b2ff7;
            --badge-bg: rgba(123,47,247,0.10); --badge-border: rgba(123,47,247,0.30); --badge-color: #7b2ff7;
        }
        [data-theme="pro-teal"] {
            --bg: #071a1a; --sidebar-bg: #0a2222; --card-bg: #0d2828; --topbar-bg: #0a2222;
            --border: rgba(0,212,180,0.14); --text: #d8f5f2; --text-muted: rgba(180,240,225,0.70);
            --primary: #00d4b4; --accent: #00ffe0; --table-hover: rgba(0,212,180,0.07);
            --btn-grad: linear-gradient(135deg,#00d4b4,#00ffe0);
            --nav-active-bg: rgba(0,212,180,0.12); --nav-active-color: #00ffe0;
            --badge-bg: rgba(0,212,180,0.15); --badge-border: rgba(0,212,180,0.35); --badge-color: #00ffe0;
        }
        [data-theme="midnight-navy"] {
            --bg: #060d1f; --sidebar-bg: #091530; --card-bg: #0d1c3a; --topbar-bg: #091530;
            --border: rgba(100,149,237,0.14); --text: #d8e8ff; --text-muted: rgba(180,210,255,0.70);
            --primary: #4a80f5; --accent: #90b4ff; --table-hover: rgba(74,128,245,0.07);
            --btn-grad: linear-gradient(135deg,#4a80f5,#90b4ff);
            --nav-active-bg: rgba(74,128,245,0.14); --nav-active-color: #90b4ff;
            --badge-bg: rgba(74,128,245,0.15); --badge-border: rgba(74,128,245,0.35); --badge-color: #90b4ff;
        }
        [data-theme="deep-emerald"] {
            --bg: #061a0e; --sidebar-bg: #082015; --card-bg: #0b261a; --topbar-bg: #082015;
            --border: rgba(0,200,100,0.14); --text: #d4f5e5; --text-muted: rgba(160,230,190,0.70);
            --primary: #00c864; --accent: #4dffaa; --table-hover: rgba(0,200,100,0.07);
            --btn-grad: linear-gradient(135deg,#00c864,#4dffaa);
            --nav-active-bg: rgba(0,200,100,0.12); --nav-active-color: #4dffaa;
            --badge-bg: rgba(0,200,100,0.15); --badge-border: rgba(0,200,100,0.35); --badge-color: #4dffaa;
        }
        [data-theme="royal-purple"] {
            --bg: #0f0520; --sidebar-bg: #180832; --card-bg: #1e0a3c; --topbar-bg: #180832;
            --border: rgba(160,80,255,0.16); --text: #ecdcff; --text-muted: rgba(210,170,255,0.70);
            --primary: #9040ff; --accent: #d080ff; --table-hover: rgba(160,80,255,0.08);
            --btn-grad: linear-gradient(135deg,#9040ff,#d080ff);
            --nav-active-bg: rgba(144,64,255,0.16); --nav-active-color: #d080ff;
            --badge-bg: rgba(144,64,255,0.18); --badge-border: rgba(144,64,255,0.40); --badge-color: #d080ff;
        }
        [data-theme="charcoal"] {
            --bg: #111111; --sidebar-bg: #1a1a1a; --card-bg: #222222; --topbar-bg: #1a1a1a;
            --border: rgba(255,255,255,0.09); --text: #e2e2e2; --text-muted: rgba(255,255,255,0.58);
            --primary: #999999; --accent: #bbbbbb; --table-hover: rgba(255,255,255,0.05);
            --btn-grad: linear-gradient(135deg,#666,#aaa);
            --nav-active-bg: rgba(255,255,255,0.09); --nav-active-color: #fff;
            --badge-bg: rgba(255,255,255,0.08); --badge-border: rgba(255,255,255,0.20); --badge-color: #ccc;
        }

        /* ============================================================
           BOOTSTRAP VARIABLE BRIDGE
           Forces Bootstrap 5 to respect the active theme on EVERY component
           ============================================================ */
        html {
            --bs-body-color:            var(--text);
            --bs-body-bg:               var(--bg);
            --bs-secondary-color:       var(--text-muted);
            --bs-tertiary-color:        var(--text-muted);
            --bs-emphasis-color:        var(--text);
            --bs-border-color:          var(--border);
            --bs-border-color-translucent: var(--border);
            /* Cards */
            --bs-card-bg:               var(--card-bg);
            --bs-card-color:            var(--text);
            --bs-card-border-color:     var(--border);
            --bs-card-cap-bg:           transparent;
            --bs-card-cap-color:        var(--text);
            /* Tables */
            --bs-table-color:           var(--text);
            --bs-table-bg:              transparent;
            --bs-table-border-color:    var(--border);
            --bs-table-striped-bg:      transparent;
            --bs-table-hover-bg:        var(--table-hover);
            --bs-table-hover-color:     var(--text);
            /* List group */
            --bs-list-group-bg:         var(--card-bg);
            --bs-list-group-color:      var(--text);
            --bs-list-group-border-color: var(--border);
            --bs-list-group-action-color: var(--text);
            --bs-list-group-action-hover-color: var(--text);
            --bs-list-group-hover-bg:   var(--table-hover);
            --bs-list-group-active-bg:  var(--primary);
            /* Modal */
            --bs-modal-bg:              var(--card-bg);
            --bs-modal-color:           var(--text);
            --bs-modal-border-color:    var(--border);
            --bs-modal-header-border-color: var(--border);
            --bs-modal-footer-border-color: var(--border);
            /* Dropdown */
            --bs-dropdown-bg:           var(--card-bg);
            --bs-dropdown-color:        var(--text);
            --bs-dropdown-border-color: var(--border);
            --bs-dropdown-link-color:   var(--text);
            --bs-dropdown-link-hover-color: var(--text);
            --bs-dropdown-link-hover-bg: var(--table-hover);
            --bs-dropdown-divider-bg:   var(--border);
            /* Inputs */
            --bs-form-control-bg:       rgba(255,255,255,0.05);
            --bs-form-control-border-color: var(--border);
            /* Nav */
            --bs-nav-link-color:        var(--text-muted);
            --bs-nav-link-hover-color:  var(--text);
            --bs-nav-tabs-border-color: var(--border);
            --bs-nav-tabs-link-hover-border-color: var(--border);
            --bs-nav-tabs-link-active-color: var(--text);
            --bs-nav-tabs-link-active-bg: var(--card-bg);
            --bs-nav-tabs-link-active-border-color: var(--border) var(--border) var(--card-bg);
        }

        /* ============================================================
           BASE STYLES
           ============================================================ */
        :root { --sidebar-width: 260px; }
        * { transition: background-color 0.25s, border-color 0.25s, color 0.15s; }
        body { background: var(--bg); color: var(--text); font-family: 'Segoe UI', sans-serif; }

        /* Headings & text */
        h1,h2,h3,h4,h5,h6 { color: var(--text); }
        p { color: var(--text); }
        small { color: var(--text-muted); }
        .card-title  { color: var(--text)       !important; }
        .card-text   { color: var(--text)       !important; }
        .card-subtitle { color: var(--text-muted) !important; }
        .lead        { color: var(--text); }
        label        { color: var(--text); }
        legend       { color: var(--text); }

        /* List group */
        .list-group-item { background: var(--card-bg) !important; border-color: var(--border) !important; color: var(--text) !important; }
        .list-group-item-action:hover { background: var(--table-hover) !important; color: var(--text) !important; }

        /* Modal */
        .modal-content { background: var(--card-bg); border-color: var(--border); color: var(--text); }
        .modal-header, .modal-footer { border-color: var(--border); }
        .modal-title { color: var(--text); }
        .btn-close { filter: invert(1) grayscale(100%) brightness(2); }
        [data-theme="light"] .btn-close { filter: none; }

        /* Dropdown */
        .dropdown-menu { background: var(--card-bg); border-color: var(--border); }
        .dropdown-item { color: var(--text); }
        .dropdown-item:hover, .dropdown-item:focus { background: var(--table-hover); color: var(--text); }
        .dropdown-header { color: var(--text-muted); }
        .dropdown-divider { border-color: var(--border); }

        /* Nav tabs */
        .nav-tabs { border-color: var(--border); }
        .nav-tabs .nav-link { color: var(--text-muted); border-color: transparent; }
        .nav-tabs .nav-link:hover { border-color: var(--border); color: var(--text); }
        .nav-tabs .nav-link.active { background: var(--card-bg); border-color: var(--border) var(--border) transparent; color: var(--text); }
        .nav-pills .nav-link { color: var(--text-muted); }
        .nav-pills .nav-link.active { background: var(--primary); color: #fff; }

        /* Input group */
        .input-group-text { background: rgba(255,255,255,0.06); border-color: var(--border); color: var(--text); }
        [data-theme="light"] .input-group-text { background: #f8f9fa; }

        /* Select option (native) */
        .form-select option { background: var(--card-bg); color: var(--text); }

        /* Pagination */
        .page-link { background: var(--card-bg); border-color: var(--border); color: var(--text); }
        .page-link:hover { background: var(--table-hover); border-color: var(--border); color: var(--text); }
        .page-item.disabled .page-link { background: var(--card-bg); border-color: var(--border); color: var(--text-muted); }
        .page-item.active .page-link { background: var(--primary); border-color: var(--primary); }

        /* Accordion */
        .accordion-item { background: var(--card-bg); border-color: var(--border); color: var(--text); }
        .accordion-button { background: var(--card-bg); color: var(--text); }
        .accordion-button:not(.collapsed) { background: var(--nav-active-bg); color: var(--nav-active-color); }
        .accordion-button::after { filter: invert(1); }
        [data-theme="light"] .accordion-button::after { filter: none; }

        /* Breadcrumb */
        .breadcrumb-item { color: var(--text-muted); }
        .breadcrumb-item.active { color: var(--text); }
        .breadcrumb-item a { color: var(--primary); }

        /* Sidebar */
        .sidebar { position:fixed;top:0;left:0;height:100vh;width:var(--sidebar-width);background:var(--sidebar-bg);border-right:1px solid var(--border);z-index:1000;overflow-y:auto;transition:transform 0.3s; }
        .sidebar-brand { padding:1rem 1.25rem;border-bottom:1px solid var(--border); }
        .brand-logo { font-size:0.75rem;font-weight:700;color:var(--text-muted);letter-spacing:0.04em;text-transform:uppercase;margin-top:4px; }
        .brand-logo img { max-width:150px;height:auto;filter:brightness(1.05); }
        .sidebar .nav-link { color:var(--text-muted);padding:0.6rem 1.25rem;border-radius:8px;margin:2px 0.5rem;display:flex;align-items:center;gap:0.75rem;font-size:0.9rem; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background:var(--nav-active-bg);color:var(--nav-active-color); }
        .sidebar .nav-link i { width:18px;text-align:center; }
        .sidebar-section { font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--text-muted);padding:1rem 1.5rem 0.25rem; }
        .sidebar-user { padding:1rem 1.25rem;border-top:1px solid var(--border);margin-top:auto; }

        /* Main content */
        .main-content { margin-left:var(--sidebar-width);padding:0;min-height:100vh; }

        /* Topbar */
        .topbar { background:var(--topbar-bg);border-bottom:1px solid var(--border);padding:0.75rem 1.5rem;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100; }
        .topbar .page-title { font-size:1.1rem;font-weight:600;margin:0;color:var(--text); }

        /* Credit badge */
        .credit-badge { background:var(--badge-bg);border:1px solid var(--badge-border);padding:0.35rem 0.8rem;border-radius:20px;font-size:0.8rem;color:var(--badge-color); }

        /* Cards */
        .card { background:var(--card-bg);border:1px solid var(--border);border-radius:12px; }
        .card-header { background:transparent;border-bottom:1px solid var(--border); }

        /* Stat cards */
        .stat-card { border-radius:12px;padding:1.25rem;background:var(--card-bg);border:1px solid var(--border);transition:transform 0.2s; }
        .stat-card:hover { transform:translateY(-2px); }
        .stat-icon { width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.25rem; }

        /* Tables — full Bootstrap override */
        .table {
            --bs-table-bg: transparent;
            --bs-table-striped-bg: transparent;
            --bs-table-hover-bg: var(--table-hover);
            --bs-table-color: var(--text);
            --bs-table-border-color: var(--border);
            --bs-table-hover-color: var(--text);
            color: var(--text);
        }
        .table thead th { background:transparent !important;border-color:var(--border);font-size:0.78rem;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-muted);font-weight:600; }
        .table tbody td { border-color:var(--border);vertical-align:middle; }
        .table tbody tr { background:transparent !important; }
        .table tbody tr:hover { background:var(--table-hover) !important; }
        .table-dark { --bs-table-bg:transparent;--bs-table-color:var(--text);--bs-table-border-color:var(--border);--bs-table-hover-bg:var(--table-hover); }

        /* text-muted override */
        .text-muted { color:var(--text-muted) !important; }

        /* Badges */
        .badge-valid   { background:rgba(25,135,84,0.2); color:#6feaaa;border:1px solid rgba(25,135,84,0.3); }
        .badge-invalid { background:rgba(220,53,69,0.2); color:#ff8a9a;border:1px solid rgba(220,53,69,0.3); }
        .badge-risky   { background:rgba(255,193,7,0.2); color:#ffd60a;border:1px solid rgba(255,193,7,0.3); }
        .badge-unknown { background:rgba(108,117,125,0.2);color:#adb5bd;border:1px solid rgba(108,117,125,0.3); }

        /* Buttons */
        .btn-primary { background:var(--btn-grad) !important;border:none !important;color:#fff !important; }
        .btn-primary:hover { opacity:0.88;box-shadow:0 4px 15px rgba(0,0,0,0.3); }

        /* Forms */
        .form-control, .form-select { background:rgba(255,255,255,0.05);border:1px solid var(--border);color:var(--text); }
        [data-theme="light"] .form-control,
        [data-theme="light"] .form-select { background:#fff;color:#1a1a2e; }
        .form-control:focus,.form-select:focus { background:rgba(255,255,255,0.09);border-color:var(--primary);color:var(--text);box-shadow:0 0 0 0.2rem rgba(123,47,247,0.20); }
        .form-control::placeholder { color:var(--text-muted); }
        .form-label { color:var(--text-muted);font-size:0.875rem; }

        /* Alerts */
        .alert-success { background:rgba(25,135,84,0.15);border-color:rgba(25,135,84,0.3);color:#6feaaa; }
        .alert-danger  { background:rgba(220,53,69,0.15);border-color:rgba(220,53,69,0.3);color:#ff8a9a; }
        .alert-warning { background:rgba(255,193,7,0.15);border-color:rgba(255,193,7,0.3);color:#ffd60a; }
        .alert-info    { background:rgba(13,202,240,0.15);border-color:rgba(13,202,240,0.3);color:#6ff0ff; }

        /* Progress */
        .progress { background:rgba(255,255,255,0.08); }

        /* ============================================================
           THEME SWITCHER
           ============================================================ */
        .theme-btn { background:rgba(255,255,255,0.06);border:1px solid var(--border);color:var(--text-muted);border-radius:8px;padding:0.35rem 0.7rem;font-size:0.82rem;cursor:pointer;display:flex;align-items:center;gap:0.4rem; }
        .theme-btn:hover { background:rgba(255,255,255,0.10);color:var(--text); }
        .theme-dropdown { position:absolute;top:calc(100% + 8px);right:0;background:var(--card-bg);border:1px solid var(--border);border-radius:12px;padding:0.4rem;min-width:185px;z-index:9999;box-shadow:0 8px 32px rgba(0,0,0,0.45);display:none; }
        .theme-dropdown.open { display:block; }
        .theme-item { display:flex;align-items:center;gap:0.6rem;padding:0.42rem 0.6rem;border-radius:8px;cursor:pointer;font-size:0.83rem;color:var(--text); }
        .theme-item:hover { background:rgba(255,255,255,0.07); }
        .theme-item.active { background:rgba(255,255,255,0.10);font-weight:600; }
        .theme-swatch { width:14px;height:14px;border-radius:50%;flex-shrink:0;border:2px solid rgba(255,255,255,0.18); }

        /* Mobile */
        @media (max-width:768px) {
            .sidebar { transform:translateX(-100%); }
            .sidebar.show { transform:translateX(0); }
            .main-content { margin-left:0; }
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width:5px; }
        ::-webkit-scrollbar-track { background:transparent; }
        ::-webkit-scrollbar-thumb { background:rgba(255,255,255,0.15);border-radius:3px; }

        /* Page content */
        .page-content { padding:1.5rem; }
    </style>
    @stack('styles')
</head>
<body>

<!-- Sidebar -->
<nav class="sidebar d-flex flex-column" id="sidebar">
    <div class="sidebar-brand text-center">
        <a href="{{ url('/') }}" class="text-decoration-none d-block">
            <img src="{{ asset('images/novelio-logo.webp') }}" alt="Novelio Technologies LLC" style="max-width:155px;height:auto;filter:brightness(1.1);">
        </a>
        <div class="brand-logo mt-1">Email Validator Pro</div>
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
            <div style="width:32px;height:32px;background:var(--btn-grad);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;font-weight:700;color:#fff;">
                {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}
            </div>
            <div style="min-width:0;">
                <div style="font-size:0.82rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--text);">{{ auth()->user()?->name }}</div>
                <div style="font-size:0.72rem;color:var(--text-muted);">{{ number_format(auth()->user()?->credit_balance ?? 0) }} credits</div>
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
            <button class="btn btn-sm d-md-none" style="background:rgba(255,255,255,0.08);border:none;color:var(--text);" onclick="document.getElementById('sidebar').classList.toggle('show')">
                <i class="fas fa-bars"></i>
            </button>
            <h6 class="page-title">@yield('page-title', 'Dashboard')</h6>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="credit-badge d-none d-sm-inline-flex">
                <i class="fas fa-coins me-1"></i>
                {{ number_format(auth()->user()?->credit_balance ?? 0) }} Credits
            </span>

            <!-- Theme Switcher -->
            <div class="position-relative" id="themeSwitcherWrap">
                <button class="theme-btn" onclick="toggleThemeDD()" title="Switch Theme">
                    <i class="fas fa-palette"></i>
                    <span class="d-none d-lg-inline" id="themeLabel">Theme</span>
                </button>
                <div class="theme-dropdown" id="themeDD">
                    <div class="theme-item" data-t="dark"          onclick="setTheme('dark')">
                        <span class="theme-swatch" style="background:linear-gradient(135deg,#0a0b1a,#7b2ff7)"></span> Dark
                    </div>
                    <div class="theme-item" data-t="light"         onclick="setTheme('light')">
                        <span class="theme-swatch" style="background:linear-gradient(135deg,#f0f2f8,#7b2ff7)"></span> Light
                    </div>
                    <div class="theme-item" data-t="pro-teal"      onclick="setTheme('pro-teal')">
                        <span class="theme-swatch" style="background:linear-gradient(135deg,#071a1a,#00d4b4)"></span> Pro Teal
                    </div>
                    <div class="theme-item" data-t="midnight-navy" onclick="setTheme('midnight-navy')">
                        <span class="theme-swatch" style="background:linear-gradient(135deg,#060d1f,#4a80f5)"></span> Midnight Navy
                    </div>
                    <div class="theme-item" data-t="deep-emerald"  onclick="setTheme('deep-emerald')">
                        <span class="theme-swatch" style="background:linear-gradient(135deg,#061a0e,#00c864)"></span> Deep Emerald
                    </div>
                    <div class="theme-item" data-t="royal-purple"  onclick="setTheme('royal-purple')">
                        <span class="theme-swatch" style="background:linear-gradient(135deg,#0f0520,#9040ff)"></span> Royal Purple
                    </div>
                    <div class="theme-item" data-t="charcoal"      onclick="setTheme('charcoal')">
                        <span class="theme-swatch" style="background:linear-gradient(135deg,#111,#aaa)"></span> Charcoal
                    </div>
                </div>
            </div>

            <a href="{{ route('user.account') }}" class="text-decoration-none">
                <div style="width:36px;height:36px;background:var(--btn-grad);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;">
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
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
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
<script>
    const THEME_NAMES = {
        'dark':'Dark','light':'Light','pro-teal':'Pro Teal',
        'midnight-navy':'Midnight Navy','deep-emerald':'Deep Emerald',
        'royal-purple':'Royal Purple','charcoal':'Charcoal'
    };
    function applyTheme(t) {
        document.documentElement.setAttribute('data-theme', t);
        const lbl = document.getElementById('themeLabel');
        if (lbl) lbl.textContent = THEME_NAMES[t] || 'Theme';
        document.querySelectorAll('.theme-item').forEach(el => {
            el.classList.toggle('active', el.dataset.t === t);
        });
    }
    function setTheme(t) { localStorage.setItem('ev_theme', t); applyTheme(t); closeThemeDD(); }
    function toggleThemeDD() { document.getElementById('themeDD').classList.toggle('open'); }
    function closeThemeDD() { document.getElementById('themeDD').classList.remove('open'); }
    document.addEventListener('click', e => {
        if (!document.getElementById('themeSwitcherWrap').contains(e.target)) closeThemeDD();
    });
    // Apply saved theme immediately (before page renders)
    (function(){ applyTheme(localStorage.getItem('ev_theme') || 'dark'); })();
</script>
@stack('scripts')
</body>
</html>
