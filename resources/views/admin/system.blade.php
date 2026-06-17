@extends('layouts.admin')

@section('title', 'System Health')
@section('page-title', 'System Health')

@push('styles')
<style>
    .sys-label { font-size:0.875rem; color:var(--text-muted); }
    .sys-value { font-size:0.875rem; color:var(--text); }
    .sys-row   { display:flex; justify-content:space-between; align-items:center; padding:0.55rem 0; border-bottom:1px solid var(--border); }
    .sys-row:last-child { border-bottom:none; }
    .section-sub { font-size:0.78rem; color:var(--text-muted); margin-bottom:8px; }
    .worker-name { font-size:0.85rem; font-weight:600; color:var(--text); }
    .worker-sub  { font-size:0.75rem; color:var(--text-muted); }
    .no-data { color:var(--text-muted); font-size:0.85rem; text-align:center; padding:1.5rem; }
</style>
@endpush

@section('content')
<div class="row g-4">

    {{-- Queue Sizes --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header py-3 px-4">
                <span class="fw-semibold"><i class="fas fa-layer-group me-2" style="color:var(--accent);opacity:0.8;"></i>Queue Sizes</span>
            </div>
            <div class="card-body p-4">
                @forelse($queues as $name => $size)
                <div class="sys-row">
                    <span class="sys-label" style="text-transform:capitalize;">{{ $name }}</span>
                    <span class="badge" style="
                        background:{{ $size > 100 ? 'rgba(220,53,69,0.2)' : ($size > 0 ? 'rgba(255,193,7,0.2)' : 'rgba(25,135,84,0.2)') }};
                        color:{{ $size > 100 ? '#ff8a9a' : ($size > 0 ? '#ffd60a' : '#6feaaa') }};
                        border:1px solid {{ $size > 100 ? 'rgba(220,53,69,0.3)' : ($size > 0 ? 'rgba(255,193,7,0.3)' : 'rgba(25,135,84,0.3)') }};">
                        {{ number_format($size) }} jobs
                    </span>
                </div>
                @empty
                <div class="no-data">Queue data unavailable</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Redis Info --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header py-3 px-4">
                <span class="fw-semibold"><i class="fas fa-database me-2" style="color:var(--accent);opacity:0.8;"></i>Redis Status</span>
            </div>
            <div class="card-body p-4">
                @if(!empty($redisInfo))
                    @foreach($redisInfo as $key => $val)
                    <div class="sys-row">
                        <span class="sys-label">{{ ucwords(str_replace('_', ' ', $key)) }}</span>
                        <span class="sys-value">{{ $val }}</span>
                    </div>
                    @endforeach
                @else
                <div class="no-data"><i class="fas fa-circle-xmark me-1"></i>Redis not connected</div>
                @endif
            </div>
        </div>
    </div>

    {{-- PHP Info --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header py-3 px-4">
                <span class="fw-semibold"><i class="fas fa-code me-2" style="color:var(--accent);opacity:0.8;"></i>PHP Environment</span>
            </div>
            <div class="card-body p-4">
                <div class="sys-row">
                    <span class="sys-label">PHP Version</span>
                    <span style="color:#6feaaa;font-size:0.875rem;font-weight:600;">{{ $phpInfo['version'] }}</span>
                </div>
                <div class="sys-row">
                    <span class="sys-label">Memory Limit</span>
                    <span class="sys-value">{{ $phpInfo['memory_limit'] }}</span>
                </div>
                <div class="sys-row">
                    <span class="sys-label">Max Execution</span>
                    <span class="sys-value">{{ $phpInfo['max_execution'] }}s</span>
                </div>
                <div class="sys-row">
                    <span class="sys-label">MySQL Connections</span>
                    <span class="sys-value">{{ $mysqlConnections }}</span>
                </div>
                <div class="pt-3">
                    <div class="section-sub">Extensions</div>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach($phpInfo['extensions'] as $ext)
                        <span class="badge" style="background:rgba(25,135,84,0.15);color:#6feaaa;border:1px solid rgba(25,135,84,0.25);font-size:0.72rem;">{{ $ext }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Workers --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header py-3 px-4">
                <span class="fw-semibold"><i class="fas fa-gears me-2" style="color:var(--accent);opacity:0.8;"></i>Queue Workers</span>
            </div>
            <div class="card-body p-0">
                @if($workers->isEmpty())
                <div class="no-data">
                    <i class="fas fa-circle-info me-1"></i>
                    No worker records. Run <code style="color:var(--accent);">php artisan queue:work</code> to start.
                </div>
                @else
                @foreach($workers as $w)
                <div class="d-flex align-items-center justify-content-between px-4 py-3 {{ !$loop->last ? 'border-bottom' : '' }}" style="border-color:var(--border);">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle" style="width:8px;height:8px;flex-shrink:0;background:{{ $w->isHealthy() ? '#6feaaa' : '#ff8a9a' }};"></div>
                        <div>
                            <div class="worker-name">{{ $w->worker_id ?? $w->hostname ?? 'Worker' }}</div>
                            <div class="worker-sub">Type: {{ $w->type ?? 'N/A' }} &bull; Status: {{ ucfirst($w->status ?? 'unknown') }}</div>
                        </div>
                    </div>
                    <div class="worker-sub">{{ $w->last_heartbeat_at?->diffForHumans() ?? 'Never' }}</div>
                </div>
                @endforeach
                @endif
            </div>
        </div>
    </div>

</div>
@endsection
