@extends('layouts.admin')

@section('title', 'Admin Dashboard')
@section('page-title', 'Dashboard')

@push('styles')
<style>
    .stat-label { font-size:0.8rem;font-weight:500;color:var(--text-muted); }
    .stat-value { font-size:1.75rem;font-weight:700;color:var(--text); }
    .stat-change { font-size:0.75rem; }
    .stat-change.up   { color:#22c55e; }
    .stat-change.down { color:#ef4444; }
    .stat-icon { width:48px;height:48px;border-radius:0.75rem;display:flex;align-items:center;justify-content:center;font-size:1.4rem; }
    .worker-dot { width:8px;height:8px;border-radius:50%;display:inline-block;margin-right:6px; }
    .worker-dot.running  { background:#22c55e;animation:pulse 1.5s infinite; }
    .worker-dot.idle     { background:#f59e0b; }
    .worker-dot.stopped  { background:#94a3b8; }
    .worker-dot.crashed  { background:#ef4444; }
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.4} }
    .progress-sm { height:6px;border-radius:999px; }
    .chart-container { position:relative; }
    .badge-valid      { background:rgba(34,197,94,0.2);color:#22c55e; }
    .badge-invalid    { background:rgba(239,68,68,0.2);color:#ef4444; }
    .badge-risky      { background:rgba(245,158,11,0.2);color:#f59e0b; }
    .badge-unknown    { background:rgba(148,163,184,0.2);color:#94a3b8; }
    .badge-catch_all  { background:rgba(14,165,233,0.2);color:#0ea5e9; }
    .badge-disposable { background:rgba(236,72,153,0.2);color:#ec4899; }
    .badge-spam_trap  { background:rgba(30,27,75,0.6);color:#a5b4fc; }
    .badge-processing { background:rgba(123,47,247,0.2);color:#c084fc; }
    .badge-completed  { background:rgba(34,197,94,0.2);color:#22c55e; }
    .badge-failed     { background:rgba(239,68,68,0.2);color:#ef4444; }
    .badge-cancelled  { background:rgba(148,163,184,0.2);color:#94a3b8; }
    .badge-pending    { background:rgba(245,158,11,0.2);color:#f59e0b; }
</style>
@endpush

@section('content')

{{-- ================================================================
     KEY METRICS ROW
     ================================================================ --}}
<div class="row g-3 mb-4">

    <!-- Total Users -->
    <div class="col-xl-3 col-md-6">
        <div class="stat-card card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">Total Users</div>
                        <div class="stat-value" id="stat-total-users">{{ number_format($stats['users']['total']) }}</div>
                        <div class="stat-change up mt-1">
                            <i class="fas fa-arrow-up fa-xs me-1"></i>+{{ $stats['users']['today'] }} today
                        </div>
                    </div>
                    <div class="stat-icon" style="background:rgba(99,102,241,0.15);color:#6366f1;">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Revenue -->
    <div class="col-xl-3 col-md-6">
        <div class="stat-card card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">Revenue (Month)</div>
                        <div class="stat-value">${{ number_format($stats['revenue']['revenue_this_month'], 0) }}</div>
                        <div class="stat-change up mt-1">
                            <i class="fas fa-arrow-up fa-xs me-1"></i>${{ number_format($stats['revenue']['revenue_today'], 2) }} today
                        </div>
                    </div>
                    <div class="stat-icon" style="background:rgba(34,197,94,0.15);color:#22c55e;">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Validations Today -->
    <div class="col-xl-3 col-md-6">
        <div class="stat-card card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">Validations Today</div>
                        <div class="stat-value" id="stat-validations-today">{{ number_format($stats['validations']['today']) }}</div>
                        <div class="stat-change up mt-1">
                            <i class="fas fa-circle-check fa-xs me-1"></i>{{ number_format($stats['validations']['valid_today']) }} valid
                        </div>
                    </div>
                    <div class="stat-icon" style="background:rgba(14,165,233,0.15);color:#0ea5e9;">
                        <i class="fas fa-envelope-circle-check"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Workers -->
    <div class="col-xl-3 col-md-6">
        <div class="stat-card card">
            <div class="card-body">
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
                    <div class="stat-icon" style="background:rgba(245,158,11,0.15);color:#f59e0b;">
                        <i class="fas fa-microchip"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ================================================================
     CHARTS ROW
     ================================================================ --}}
<div class="row g-3 mb-4">

    <!-- Hourly Validation Chart -->
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">Validations (Last 24 Hours)</h6>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-success" id="live-indicator">● LIVE</span>
                    <span class="text-muted small">Updated: <span id="last-updated">Just now</span></span>
                </div>
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
        <div class="card h-100">
            <div class="card-header">
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

{{-- ================================================================
     QUEUE & WORKERS & SYSTEM ROW
     ================================================================ --}}
<div class="row g-3 mb-4">

    <!-- Queue Sizes -->
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold">
                    <i class="fas fa-layer-group me-2" style="color:var(--primary);"></i>Queue Monitor
                </h6>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <tbody>
                        @foreach($stats['system']['queue_sizes'] as $queue => $size)
                        <tr>
                            <td class="ps-3 fw-medium">{{ ucwords(str_replace('_', ' ', $queue)) }}</td>
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
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold">
                    <i class="fas fa-microchip me-2" style="color:#f59e0b;"></i>Worker Status
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
                            <td class="ps-3 font-monospace small">{{ $worker['id'] }}</td>
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
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold">
                    <i class="fas fa-chart-bar me-2" style="color:#0ea5e9;"></i>System Health
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
                        <div class="fw-bold" style="color:var(--primary);">{{ $stats['validations']['bulk_jobs_processing'] }}</div>
                        <div class="text-muted" style="font-size:0.7rem;">Processing</div>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold text-success">{{ $stats['users']['active'] }}</div>
                        <div class="text-muted" style="font-size:0.7rem;">Active Users</div>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold text-info">{{ number_format($stats['validations']['total']) }}</div>
                        <div class="text-muted" style="font-size:0.7rem;">Total Valid.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ================================================================
     RECENT JOBS TABLE
     ================================================================ --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold">Recent Validation Jobs</h6>
        <a href="{{ route('admin.jobs.index') }}" class="btn btn-sm btn-primary">View All</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 small">
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
                            <a href="{{ route('admin.jobs.show', $job['id']) }}" class="text-decoration-none fw-medium" style="color:var(--text);">
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
                            <div class="progress progress-sm mb-1">
                                <div class="progress-bar bg-primary" style="width:{{ $job['progress'] }}%"></div>
                            </div>
                            <small class="text-muted">{{ number_format($job['progress'], 1) }}%</small>
                        </td>
                        <td class="text-muted">{{ \Carbon\Carbon::parse($job['created_at'])->diffForHumans() }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Chart.js — Hourly Validation Chart
const hourlyData  = @json($stats['hourly_chart']);
const hourlyCtx   = document.getElementById('hourly-chart').getContext('2d');
const hourlyChart = new Chart(hourlyCtx, {
    type: 'line',
    data: {
        labels: hourlyData.map(h => h.hour),
        datasets: [{
            label: 'Validations',
            data: hourlyData.map(h => h.count),
            fill: true,
            backgroundColor: 'rgba(123,47,247,0.12)',
            borderColor: '#7b2ff7',
            borderWidth: 2,
            tension: 0.4,
            pointBackgroundColor: '#7b2ff7',
            pointRadius: 3,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, ticks: { color: 'rgba(150,150,200,0.7)' } },
            y: { beginAtZero: true, grid: { color: 'rgba(150,150,200,0.12)' }, ticks: { color: 'rgba(150,150,200,0.7)' } }
        }
    }
});

// Chart.js — Status Distribution Donut
const statusData = @json($stats['status_distribution']);
const statusCtx  = document.getElementById('status-chart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: Object.keys(statusData),
        datasets: [{
            data: Object.values(statusData),
            backgroundColor: ['#22c55e','#ef4444','#f59e0b','#94a3b8','#0ea5e9','#ec4899'],
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
                labels: { boxWidth: 10, font: { size: 11 }, color: 'rgba(150,150,200,0.8)' }
            }
        }
    }
});

// Real-time Auto-Refresh every 30 seconds
function refreshDashboard() {
    fetch('/api/v1/admin/dashboard', {
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const d = data.data;
            document.getElementById('stat-total-users').textContent = d.users.total.toLocaleString();
            document.getElementById('stat-validations-today').textContent = d.validations.today.toLocaleString();
            document.getElementById('last-updated').textContent = 'Just now';
            hourlyChart.data.datasets[0].data = d.hourly_chart.map(h => h.count);
            hourlyChart.update('none');
        }
    })
    .catch(console.error);
}
setInterval(refreshDashboard, 30000);
</script>
@endpush
