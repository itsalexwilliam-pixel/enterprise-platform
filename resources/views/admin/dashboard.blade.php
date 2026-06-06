<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Dashboard - Email Validator Pro</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- Vue 3 -->
    <script src="https://cdn.jsdelivr.net/npm/vue@3.3.4/dist/vue.global.prod.js"></script>

    <style>
        :root {
            --ev-primary: #6366f1;
            --ev-secondary: #8b5cf6;
            --ev-success: #22c55e;
            --ev-danger: #ef4444;
            --ev-warning: #f59e0b;
            --ev-info: #0ea5e9;
            --ev-dark: #0f172a;
            --ev-sidebar-width: 260px;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #f1f5f9;
            color: #334155;
        }

        /* ---- SIDEBAR ---- */
        .sidebar {
            position: fixed;
            top: 0; left: 0;
            width: var(--ev-sidebar-width);
            height: 100vh;
            background: var(--ev-dark);
            color: white;
            z-index: 1000;
            overflow-y: auto;
            transition: transform 0.3s ease;
        }

        .sidebar-brand {
            padding: 1.5rem 1.25rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-brand h1 {
            font-size: 1.1rem;
            font-weight: 700;
            color: white;
            margin: 0;
        }

        .sidebar-brand span { color: var(--ev-primary); }

        .sidebar-nav .nav-link {
            color: rgba(255,255,255,0.65);
            padding: 0.6rem 1.25rem;
            border-radius: 0.375rem;
            margin: 0.1rem 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .sidebar-nav .nav-link:hover,
        .sidebar-nav .nav-link.active {
            color: white;
            background: rgba(99,102,241,0.3);
        }

        .sidebar-nav .nav-section {
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.35);
            padding: 1rem 1.75rem 0.25rem;
        }

        /* ---- MAIN CONTENT ---- */
        .main-content {
            margin-left: var(--ev-sidebar-width);
            min-height: 100vh;
        }

        .topbar {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* ---- STAT CARDS ---- */
        .stat-card {
            background: white;
            border-radius: 0.75rem;
            padding: 1.25rem;
            border: 1px solid #e2e8f0;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .stat-icon {
            width: 48px; height: 48px;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }

        .stat-label { font-size: 0.8rem; font-weight: 500; color: #64748b; }
        .stat-value { font-size: 1.75rem; font-weight: 700; color: #0f172a; }
        .stat-change { font-size: 0.75rem; }
        .stat-change.up   { color: var(--ev-success); }
        .stat-change.down { color: var(--ev-danger); }

        /* ---- TABLES ---- */
        .data-table { border-radius: 0.75rem; overflow: hidden; }
        .table thead th {
            background: #f8fafc;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            border-bottom: 1px solid #e2e8f0;
        }

        /* ---- STATUS BADGES ---- */
        .badge-valid       { background: #dcfce7; color: #16a34a; }
        .badge-invalid     { background: #fee2e2; color: #dc2626; }
        .badge-risky       { background: #fef3c7; color: #d97706; }
        .badge-unknown     { background: #f1f5f9; color: #64748b; }
        .badge-catch_all   { background: #e0f2fe; color: #0284c7; }
        .badge-disposable  { background: #fce7f3; color: #be185d; }
        .badge-spam_trap   { background: #1e1b4b; color: white; }

        /* ---- WORKER STATUS ---- */
        .worker-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }
        .worker-dot.running  { background: var(--ev-success); animation: pulse 1.5s infinite; }
        .worker-dot.idle     { background: var(--ev-warning); }
        .worker-dot.stopped  { background: #94a3b8; }
        .worker-dot.crashed  { background: var(--ev-danger); }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.4; }
        }

        /* ---- PROGRESS ---- */
        .progress-sm { height: 6px; border-radius: 999px; }

        /* ---- CHARTS ---- */
        .chart-container { position: relative; }

        /* ---- QUEUE INDICATOR ---- */
        .queue-size {
            font-size: 1.2rem;
            font-weight: 700;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>

<!-- ============================================================ -->
<!-- SIDEBAR                                                       -->
<!-- ============================================================ -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <h1><i class="bi bi-envelope-check"></i> Email<span>Validator</span></h1>
        <small class="text-muted" style="font-size:0.7rem;">Admin Panel v1.0</small>
    </div>

    <nav class="sidebar-nav mt-3">
        <span class="nav-section">Main</span>
        <a href="{{ route('admin.dashboard') }}" class="nav-link active">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="{{ route('admin.dashboard') }}" class="nav-link">
            <i class="bi bi-graph-up"></i> Analytics
        </a>

        <span class="nav-section">Management</span>
        <a href="{{ route('admin.users.index') }}" class="nav-link">
            <i class="bi bi-people"></i> Users
        </a>
        <a href="{{ route('admin.jobs.index') }}" class="nav-link">
            <i class="bi bi-list-task"></i> Validation Jobs
        </a>
        <a href="{{ route('admin.transactions.index') }}" class="nav-link">
            <i class="bi bi-credit-card"></i> Transactions
        </a>
        <a href="{{ route('admin.plans.index') }}" class="nav-link">
            <i class="bi bi-box"></i> Plans & Pricing
        </a>

        <span class="nav-section">Infrastructure</span>
        <a href="{{ route('admin.workers') }}" class="nav-link">
            <i class="bi bi-cpu"></i> Workers
        </a>
        <a href="{{ route('admin.smtp-servers.index') }}" class="nav-link">
            <i class="bi bi-server"></i> SMTP Servers
        </a>
        <a href="{{ route('admin.domains.index') }}" class="nav-link">
            <i class="bi bi-globe"></i> Domain Lists
        </a>
        <a href="{{ route('admin.queues') }}" class="nav-link">
            <i class="bi bi-stack"></i> Queue Monitor
        </a>

        <span class="nav-section">System</span>
        <a href="{{ route('admin.audit') }}" class="nav-link">
            <i class="bi bi-journal-text"></i> Audit Logs
        </a>
        <a href="{{ route('admin.settings') }}" class="nav-link">
            <i class="bi bi-gear"></i> Settings
        </a>

        <div class="mt-auto pt-3 pb-3 px-3" style="border-top:1px solid rgba(255,255,255,0.1);margin-top:2rem">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center"
                     style="width:32px;height:32px;font-size:0.8rem;font-weight:700;">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
                <div>
                    <div style="font-size:0.8rem;font-weight:600;">{{ auth()->user()->name }}</div>
                    <div style="font-size:0.7rem;color:rgba(255,255,255,0.5);">Administrator</div>
                </div>
            </div>
        </div>
    </nav>
</aside>

<!-- ============================================================ -->
<!-- MAIN CONTENT                                                  -->
<!-- ============================================================ -->
<main class="main-content" id="app">
    <!-- Topbar -->
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm btn-light d-md-none" onclick="document.querySelector('.sidebar').classList.toggle('show')">
                <i class="bi bi-list"></i>
            </button>
            <h5 class="mb-0 fw-semibold">Dashboard</h5>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-success" id="live-indicator">● LIVE</span>
            <span class="text-muted small">Last updated: <span id="last-updated">Just now</span></span>
            <a href="{{ route('home') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-box-arrow-up-right"></i> View Site
            </a>
        </div>
    </div>

    <div class="p-4">

        <!-- ======================================================== -->
        <!-- KEY METRICS ROW                                           -->
        <!-- ======================================================== -->
        <div class="row g-3 mb-4">

            <!-- Total Users -->
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">Total Users</div>
                            <div class="stat-value" id="stat-total-users">{{ number_format($stats['users']['total']) }}</div>
                            <div class="stat-change up mt-1">
                                <i class="bi bi-arrow-up-short"></i>
                                +{{ $stats['users']['today'] }} today
                            </div>
                        </div>
                        <div class="stat-icon" style="background:#ede9fe;color:var(--ev-primary)">
                            <i class="bi bi-people-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Revenue -->
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">Revenue (Month)</div>
                            <div class="stat-value">${{ number_format($stats['revenue']['revenue_this_month'], 0) }}</div>
                            <div class="stat-change up mt-1">
                                <i class="bi bi-arrow-up-short"></i>
                                ${{ number_format($stats['revenue']['revenue_today'], 2) }} today
                            </div>
                        </div>
                        <div class="stat-icon" style="background:#dcfce7;color:var(--ev-success)">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Validations Today -->
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">Validations Today</div>
                            <div class="stat-value" id="stat-validations-today">{{ number_format($stats['validations']['today']) }}</div>
                            <div class="stat-change up mt-1">
                                <i class="bi bi-check-circle"></i>
                                {{ number_format($stats['validations']['valid_today']) }} valid
                            </div>
                        </div>
                        <div class="stat-icon" style="background:#e0f2fe;color:var(--ev-info)">
                            <i class="bi bi-envelope-check-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Workers -->
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">Active Workers</div>
                            <div class="stat-value">{{ $stats['system']['workers']['running'] }}/{{ $stats['system']['workers']['total'] }}</div>
                            <div class="stat-change mt-1" id="worker-status-text">
                                @if($stats['system']['workers']['crashed'] > 0)
                                    <span class="text-danger">{{ $stats['system']['workers']['crashed'] }} crashed</span>
                                @else
                                    <span class="text-success">All healthy</span>
                                @endif
                            </div>
                        </div>
                        <div class="stat-icon" style="background:#fef3c7;color:var(--ev-warning)">
                            <i class="bi bi-cpu-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- CHARTS ROW                                                -->
        <!-- ======================================================== -->
        <div class="row g-3 mb-4">

            <!-- Hourly Validation Chart -->
            <div class="col-xl-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-semibold">Validations (Last 24 Hours)</h6>
                        <span class="badge bg-primary">Hourly</span>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height:280px">
                            <canvas id="hourly-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Distribution -->
            <div class="col-xl-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom">
                        <h6 class="mb-0 fw-semibold">Today's Status Distribution</h6>
                    </div>
                    <div class="card-body d-flex align-items-center justify-content-center">
                        <div class="chart-container" style="height:240px;width:240px">
                            <canvas id="status-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- QUEUE & WORKERS ROW                                       -->
        <!-- ======================================================== -->
        <div class="row g-3 mb-4">

            <!-- Queue Sizes -->
            <div class="col-xl-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h6 class="mb-0 fw-semibold">
                            <i class="bi bi-stack text-primary me-2"></i>Queue Monitor
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <table class="table mb-0">
                            <tbody>
                                @foreach($stats['system']['queue_sizes'] as $queue => $size)
                                <tr>
                                    <td class="ps-3">
                                        <span class="fw-medium">{{ ucwords(str_replace('_', ' ', $queue)) }}</span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <span class="badge {{ $size > 1000 ? 'bg-danger' : ($size > 100 ? 'bg-warning' : 'bg-success') }}">
                                            {{ number_format($size) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Worker Status -->
            <div class="col-xl-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h6 class="mb-0 fw-semibold">
                            <i class="bi bi-cpu text-warning me-2"></i>Worker Status
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <table class="table mb-0 small">
                            <thead>
                                <tr>
                                    <th class="ps-3">Worker</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th class="text-end pe-3">Jobs</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stats['system']['workers']['details'] as $worker)
                                <tr>
                                    <td class="ps-3 font-monospace">{{ $worker['id'] }}</td>
                                    <td><span class="badge bg-secondary">{{ $worker['type'] }}</span></td>
                                    <td>
                                        <span class="worker-dot {{ $worker['status'] }}"></span>
                                        {{ $worker['status'] }}
                                    </td>
                                    <td class="text-end pe-3">{{ number_format($worker['jobs_processed']) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- System Stats -->
            <div class="col-xl-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h6 class="mb-0 fw-semibold">
                            <i class="bi bi-bar-chart text-info me-2"></i>System Health
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Redis Memory</span>
                                <span>{{ $stats['system']['redis_memory']['used_mb'] }}MB / {{ $stats['system']['redis_memory']['max_mb'] ?: '∞' }}MB</span>
                            </div>
                            @php
                                $redisPercent = $stats['system']['redis_memory']['max_mb'] > 0
                                    ? ($stats['system']['redis_memory']['used_mb'] / $stats['system']['redis_memory']['max_mb'] * 100)
                                    : 30;
                            @endphp
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-info" style="width:{{ min($redisPercent, 100) }}%"></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>MySQL Connections</span>
                                <span>{{ $stats['system']['db_connections'] }}</span>
                            </div>
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-success" style="width:{{ min($stats['system']['db_connections'] / 200 * 100, 100) }}%"></div>
                            </div>
                        </div>

                        <div class="row text-center mt-3">
                            <div class="col-4">
                                <div class="fw-bold text-primary">{{ $stats['validations']['bulk_jobs_processing'] }}</div>
                                <div class="text-muted" style="font-size:0.7rem">Processing</div>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold text-success">{{ $stats['users']['active'] }}</div>
                                <div class="text-muted" style="font-size:0.7rem">Active Users</div>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold text-info">{{ number_format($stats['validations']['total']) }}</div>
                                <div class="text-muted" style="font-size:0.7rem">Total Valid.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- RECENT JOBS TABLE                                         -->
        <!-- ======================================================== -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">Recent Validation Jobs</h6>
                <a href="{{ route('admin.jobs.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 small data-table">
                        <thead>
                            <tr>
                                <th class="ps-3">Job Name</th>
                                <th>User</th>
                                <th>Status</th>
                                <th>Total</th>
                                <th>Progress</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stats['recent_jobs'] as $job)
                            <tr>
                                <td class="ps-3">
                                    <a href="{{ route('admin.jobs.show', $job['id']) }}" class="text-decoration-none fw-medium">
                                        {{ $job['name'] }}
                                    </a>
                                </td>
                                <td>{{ $job['user'] }}</td>
                                <td>
                                    <span class="badge badge-{{ $job['status'] }} rounded-pill px-2">
                                        {{ $job['status'] }}
                                    </span>
                                </td>
                                <td>{{ number_format($job['total_emails']) }}</td>
                                <td style="min-width:120px">
                                    <div class="progress progress-sm">
                                        <div class="progress-bar bg-primary" style="width:{{ $job['progress'] }}%"></div>
                                    </div>
                                    <small>{{ number_format($job['progress'], 1) }}%</small>
                                </td>
                                <td>{{ \Carbon\Carbon::parse($job['created_at'])->diffForHumans() }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div><!-- /p-4 -->
</main>

<!-- ============================================================ -->
<!-- SCRIPTS                                                       -->
<!-- ============================================================ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ============================================================
// Dashboard JavaScript
// Real-time updates using polling
// ============================================================

// Hourly Chart
const hourlyData = @json($stats['hourly_chart']);
const hourlyCtx  = document.getElementById('hourly-chart').getContext('2d');
const hourlyChart = new Chart(hourlyCtx, {
    type: 'line',
    data: {
        labels: hourlyData.map(h => h.hour),
        datasets: [{
            label: 'Validations',
            data: hourlyData.map(h => h.count),
            fill: true,
            backgroundColor: 'rgba(99,102,241,0.1)',
            borderColor: '#6366f1',
            borderWidth: 2,
            tension: 0.4,
            pointBackgroundColor: '#6366f1',
            pointRadius: 3,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false } },
            y: { beginAtZero: true, grid: { color: '#f1f5f9' } }
        }
    }
});

// Status Distribution Chart
const statusData = @json($stats['status_distribution']);
const statusCtx  = document.getElementById('status-chart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: Object.keys(statusData),
        datasets: [{
            data: Object.values(statusData),
            backgroundColor: [
                '#22c55e', // valid
                '#ef4444', // invalid
                '#f59e0b', // risky
                '#94a3b8', // unknown
                '#0ea5e9', // catch_all
                '#ec4899', // disposable
            ],
            borderWidth: 0,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '70%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: { boxWidth: 10, font: { size: 11 } }
            }
        }
    }
});

// ============================================================
// Real-time Auto-Refresh (every 30 seconds)
// ============================================================
function refreshDashboard() {
    fetch('/api/v1/admin/dashboard', {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const d = data.data;
            document.getElementById('stat-total-users').textContent =
                d.users.total.toLocaleString();
            document.getElementById('stat-validations-today').textContent =
                d.validations.today.toLocaleString();
            document.getElementById('last-updated').textContent = 'Just now';

            // Update hourly chart
            hourlyChart.data.datasets[0].data = d.hourly_chart.map(h => h.count);
            hourlyChart.update('none');
        }
    })
    .catch(console.error);
}

// Auto refresh every 30 seconds
setInterval(refreshDashboard, 30000);

// Update "last updated" counter
setInterval(() => {
    // Could show elapsed time
}, 1000);
</script>

</body>
</html>
