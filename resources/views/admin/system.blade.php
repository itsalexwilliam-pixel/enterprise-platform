@extends('layouts.admin')

@section('title', 'System Health')
@section('page-title', 'System Health')

@section('content')
<div class="row g-4">
    <!-- Queue Sizes -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header py-3 px-4"><span class="fw-semibold">Queue Sizes</span></div>
            <div class="card-body p-4">
                @foreach($queues as $name => $size)
                <div class="d-flex justify-content-between align-items-center py-2 {{ !$loop->last ? 'border-bottom' : '' }}" style="border-color:rgba(255,255,255,0.06);">
                    <span style="font-size:0.875rem;text-transform:capitalize;">{{ $name }}</span>
                    <span class="badge" style="background:{{ $size > 100 ? 'rgba(220,53,69,0.2)' : ($size > 0 ? 'rgba(255,193,7,0.2)' : 'rgba(25,135,84,0.2)') }};color:{{ $size > 100 ? '#ff8a9a' : ($size > 0 ? '#ffd60a' : '#6feaaa') }};">{{ number_format($size) }} jobs</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Redis Info -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header py-3 px-4"><span class="fw-semibold">Redis Status</span></div>
            <div class="card-body p-4">
                @if(!empty($redisInfo))
                @foreach($redisInfo as $key => $val)
                <div class="d-flex justify-content-between py-2 {{ !$loop->last ? 'border-bottom' : '' }}" style="border-color:rgba(255,255,255,0.06);font-size:0.875rem;">
                    <span style="color:rgba(255,255,255,0.5);text-transform:replace;">{{ str_replace('_', ' ', ucfirst($key)) }}</span>
                    <span>{{ $val }}</span>
                </div>
                @endforeach
                @else
                <div style="color:rgba(255,255,255,0.3);font-size:0.85rem;">Redis info unavailable.</div>
                @endif
            </div>
        </div>
    </div>

    <!-- PHP Info -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header py-3 px-4"><span class="fw-semibold">PHP Environment</span></div>
            <div class="card-body p-4">
                <div class="d-flex justify-content-between py-2 border-bottom" style="border-color:rgba(255,255,255,0.06);font-size:0.875rem;">
                    <span style="color:rgba(255,255,255,0.5);">PHP Version</span>
                    <span style="color:#6feaaa;">{{ $phpInfo['version'] }}</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom" style="border-color:rgba(255,255,255,0.06);font-size:0.875rem;">
                    <span style="color:rgba(255,255,255,0.5);">Memory Limit</span>
                    <span>{{ $phpInfo['memory_limit'] }}</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom" style="border-color:rgba(255,255,255,0.06);font-size:0.875rem;">
                    <span style="color:rgba(255,255,255,0.5);">Max Execution</span>
                    <span>{{ $phpInfo['max_execution'] }}s</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom" style="border-color:rgba(255,255,255,0.06);font-size:0.875rem;">
                    <span style="color:rgba(255,255,255,0.5);">MySQL Connections</span>
                    <span>{{ $mysqlConnections }}</span>
                </div>
                <div class="pt-2">
                    <div style="font-size:0.78rem;color:rgba(255,255,255,0.3);margin-bottom:8px;">Extensions</div>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach($phpInfo['extensions'] as $ext)
                        <span class="badge" style="background:rgba(25,135,84,0.15);color:#6feaaa;border:1px solid rgba(25,135,84,0.2);font-size:0.72rem;">{{ $ext }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Workers -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header py-3 px-4"><span class="fw-semibold">Queue Workers</span></div>
            <div class="card-body p-0">
                @if($workers->isEmpty())
                <div class="text-center py-4" style="color:rgba(255,255,255,0.3);font-size:0.85rem;">No worker records. Start queue workers to see status.</div>
                @else
                @foreach($workers as $w)
                <div class="d-flex align-items-center justify-content-between px-4 py-3 {{ !$loop->last ? 'border-bottom' : '' }}" style="border-color:rgba(255,255,255,0.06);">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle" style="width:8px;height:8px;background:{{ $w->isHealthy() ? '#6feaaa' : '#ff8a9a' }};"></div>
                        <div>
                            <div style="font-size:0.85rem;font-weight:600;">{{ $w->name }}</div>
                            <div style="font-size:0.75rem;color:rgba(255,255,255,0.3);">Queue: {{ $w->queue }}</div>
                        </div>
                    </div>
                    <div style="font-size:0.75rem;color:rgba(255,255,255,0.3);">{{ $w->last_heartbeat_at?->diffForHumans() }}</div>
                </div>
                @endforeach
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
