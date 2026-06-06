@extends('layouts.admin')

@section('title', 'Settings')
@section('page-title', 'System Settings')

@section('content')
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header py-3 px-4"><span class="fw-semibold">Current Configuration</span></div>
            <div class="card-body p-4">
                @foreach($settings as $key => $value)
                <div class="d-flex justify-content-between py-2 {{ !$loop->last ? 'border-bottom' : '' }}" style="border-color:rgba(255,255,255,0.06);font-size:0.875rem;">
                    <span style="color:rgba(255,255,255,0.5);">{{ str_replace('_', ' ', ucwords($key)) }}</span>
                    <span class="font-monospace" style="color:#00d4ff;font-size:0.8rem;">{{ $value }}</span>
                </div>
                @endforeach
                <div class="mt-3 p-2 rounded" style="background:rgba(255,193,7,0.08);border:1px solid rgba(255,193,7,0.2);font-size:0.78rem;color:#ffd60a;">
                    <i class="fas fa-circle-info me-1"></i>To change settings, edit the <code>.env</code> file and rebuild config cache.
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header py-3 px-4"><span class="fw-semibold">Cache Management</span></div>
            <div class="card-body p-4">
                <p style="font-size:0.85rem;color:rgba(255,255,255,0.5);">Clear application caches or rebuild optimised config/route/view caches.</p>
                <div class="d-flex gap-3">
                    <form method="POST" action="{{ route('admin.settings.clear-cache') }}">
                        @csrf
                        <button type="submit" class="btn btn-sm px-4" style="background:rgba(220,53,69,0.15);border:1px solid rgba(220,53,69,0.3);color:#ff8a9a;" onclick="return confirm('Clear all caches?')">
                            <i class="fas fa-broom me-2"></i>Clear Caches
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.settings.optimise') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <i class="fas fa-bolt me-2"></i>Optimise App
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header py-3 px-4"><span class="fw-semibold">Application Info</span></div>
            <div class="card-body p-4">
                @foreach([
                    'Laravel Version' => app()->version(),
                    'PHP Version' => PHP_VERSION,
                    'Environment' => app()->environment(),
                    'Debug Mode' => config('app.debug') ? 'ON (disable in production)' : 'OFF',
                    'Timezone' => config('app.timezone'),
                    'Queue Driver' => config('queue.default'),
                    'Cache Driver' => config('cache.default'),
                ] as $label => $value)
                <div class="d-flex justify-content-between py-2 {{ !$loop->last ? 'border-bottom' : '' }}" style="border-color:rgba(255,255,255,0.06);font-size:0.85rem;">
                    <span style="color:rgba(255,255,255,0.5);">{{ $label }}</span>
                    <span style="color:{{ $label === 'Debug Mode' && config('app.debug') ? '#ffd60a' : '#e0e0e8' }};">{{ $value }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
