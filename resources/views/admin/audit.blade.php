@extends('layouts.admin')
@section('title', 'Audit Log')
@section('page-title', 'Audit Log')
@section('content')
<div class="card">
    <div class="card-header py-3 px-4"><span class="fw-semibold">Recent Activity</span></div>
    <div class="card-body p-0">
        @php
        $logs = \App\Models\AuditLog::with('user')->orderByDesc('created_at')->paginate(50);
        @endphp
        @if($logs->isEmpty())
        <div class="text-center py-5" style="color:rgba(255,255,255,0.3);">
            <i class="fas fa-clipboard-list mb-2" style="font-size:2rem;display:block;"></i>No audit logs yet.
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:0.82rem;">
                <thead><tr><th class="px-4">User</th><th>Action</th><th>Model</th><th>IP</th><th class="px-4">Time</th></tr></thead>
                <tbody>
                    @foreach($logs as $log)
                    <tr>
                        <td class="px-4">
                            <div>{{ $log->user?->name ?? 'System' }}</div>
                            <div style="font-size:0.75rem;color:rgba(255,255,255,0.3);">{{ $log->user?->email }}</div>
                        </td>
                        <td style="color:#00d4ff;font-family:monospace;font-size:0.8rem;">{{ $log->action }}</td>
                        <td style="color:rgba(255,255,255,0.5);">
                            @if($log->model_type)
                            {{ class_basename($log->model_type) }}
                            @if($log->model_id) <span style="color:rgba(255,255,255,0.3);">#{{ $log->model_id }}</span> @endif
                            @else —
                            @endif
                        </td>
                        <td style="color:rgba(255,255,255,0.4);font-family:monospace;font-size:0.78rem;">{{ $log->ip_address }}</td>
                        <td class="px-4" style="color:rgba(255,255,255,0.4);">{{ $log->created_at->format('M d, H:i:s') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3">{{ $logs->links() }}</div>
        @endif
    </div>
</div>
@endsection
