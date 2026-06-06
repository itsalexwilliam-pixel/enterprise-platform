@extends('layouts.app')

@section('title', $job->name)
@section('page-title', 'Job: ' . $job->name)

@section('content')
<div class="row g-4">
    <!-- Progress Card -->
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h6 class="fw-semibold mb-0">{{ $job->name }}</h6>
                    @php
                        $statusColors = ['pending' => '#adb5bd','processing' => '#00d4ff','completed' => '#6feaaa','failed' => '#ff8a9a','cancelled' => '#adb5bd'];
                        $color = $statusColors[$job->status] ?? '#adb5bd';
                    @endphp
                    <span class="badge" style="background:rgba(255,255,255,0.08);color:{{ $color }};border:1px solid {{ $color }}40;">
                        {{ ucfirst($job->status) }}
                    </span>
                </div>

                <!-- Progress Ring (CSS) -->
                <div class="text-center my-4">
                    <div style="position:relative;display:inline-block;">
                        <svg width="120" height="120" viewBox="0 0 120 120">
                            <circle cx="60" cy="60" r="50" fill="none" stroke="rgba(255,255,255,0.08)" stroke-width="8"/>
                            <circle cx="60" cy="60" r="50" fill="none" stroke="url(#grad)" stroke-width="8"
                                    stroke-linecap="round" stroke-dasharray="314"
                                    stroke-dashoffset="{{ 314 - (314 * $job->progress_percentage / 100) }}"
                                    transform="rotate(-90 60 60)"/>
                            <defs><linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" style="stop-color:#7b2ff7"/>
                                <stop offset="100%" style="stop-color:#00d4ff"/>
                            </linearGradient></defs>
                        </svg>
                        <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;">
                            <div style="font-size:1.5rem;font-weight:800;">{{ $job->progress_percentage }}%</div>
                            <div style="font-size:0.7rem;color:rgba(255,255,255,0.4);">Complete</div>
                        </div>
                    </div>
                </div>

                <div class="row g-2 text-center mb-3">
                    <div class="col-6">
                        <div style="font-size:1.2rem;font-weight:700;">{{ number_format($job->processed_emails) }}</div>
                        <div style="font-size:0.72rem;color:rgba(255,255,255,0.4);">Processed</div>
                    </div>
                    <div class="col-6">
                        <div style="font-size:1.2rem;font-weight:700;">{{ number_format($job->total_emails) }}</div>
                        <div style="font-size:0.72rem;color:rgba(255,255,255,0.4);">Total</div>
                    </div>
                    @if($job->processing_speed)
                    <div class="col-6">
                        <div style="font-size:1.2rem;font-weight:700;">{{ number_format($job->processing_speed) }}/s</div>
                        <div style="font-size:0.72rem;color:rgba(255,255,255,0.4);">Speed</div>
                    </div>
                    @endif
                    @if($job->eta_seconds && $job->status === 'processing')
                    <div class="col-6">
                        <div style="font-size:1.2rem;font-weight:700;">{{ gmdate('H:i:s', $job->eta_seconds) }}</div>
                        <div style="font-size:0.72rem;color:rgba(255,255,255,0.4);">ETA</div>
                    </div>
                    @endif
                </div>

                @if($job->status === 'completed' && $job->download_token)
                <a href="{{ route('user.bulk.download', $job) }}" class="btn btn-primary w-100 fw-semibold">
                    <i class="fas fa-download me-2"></i>Download Results (CSV)
                </a>
                @elseif(in_array($job->status, ['pending', 'processing']))
                <form method="POST" action="{{ route('user.bulk.cancel', $job) }}" onsubmit="return confirm('Cancel this job? Remaining credits will be refunded.')">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn w-100" style="background:rgba(220,53,69,0.15);border:1px solid rgba(220,53,69,0.3);color:#ff8a9a;">
                        <i class="fas fa-stop me-2"></i>Cancel Job
                    </button>
                </form>
                @endif
            </div>
        </div>

        <!-- Job Details -->
        <div class="card">
            <div class="card-header py-3 px-4"><span class="fw-semibold" style="font-size:0.875rem;">Job Details</span></div>
            <div class="card-body p-4">
                <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid rgba(255,255,255,0.06);font-size:0.82rem;">
                    <span style="color:rgba(255,255,255,0.4);">Job ID</span>
                    <span class="font-monospace" style="font-size:0.75rem;">{{ substr($job->uuid, 0, 8) }}...</span>
                </div>
                <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid rgba(255,255,255,0.06);font-size:0.82rem;">
                    <span style="color:rgba(255,255,255,0.4);">Format</span>
                    <span class="text-uppercase">{{ $job->file_type }}</span>
                </div>
                <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid rgba(255,255,255,0.06);font-size:0.82rem;">
                    <span style="color:rgba(255,255,255,0.4);">Credits Used</span>
                    <span>{{ number_format($job->credits_used) }}</span>
                </div>
                <div class="d-flex justify-content-between py-2" style="font-size:0.82rem;">
                    <span style="color:rgba(255,255,255,0.4);">Started</span>
                    <span>{{ $job->started_at?->format('M d, H:i') ?? 'Not started' }}</span>
                </div>
                @if($job->completed_at)
                <div class="d-flex justify-content-between py-2" style="border-top:1px solid rgba(255,255,255,0.06);font-size:0.82rem;">
                    <span style="color:rgba(255,255,255,0.4);">Completed</span>
                    <span>{{ $job->completed_at->format('M d, H:i') }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Results Summary -->
    <div class="col-lg-8">
        @if($job->summary)
        <div class="row g-3 mb-4">
            @php $s = $job->summary; @endphp
            <div class="col-6 col-md-3">
                <div class="stat-card text-center">
                    <div style="font-size:1.6rem;font-weight:800;color:#6feaaa;">{{ number_format($s['valid'] ?? 0) }}</div>
                    <div style="font-size:0.78rem;color:rgba(255,255,255,0.4);">Valid</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card text-center">
                    <div style="font-size:1.6rem;font-weight:800;color:#ff8a9a;">{{ number_format($s['invalid'] ?? 0) }}</div>
                    <div style="font-size:0.78rem;color:rgba(255,255,255,0.4);">Invalid</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card text-center">
                    <div style="font-size:1.6rem;font-weight:800;color:#ffd60a;">{{ number_format($s['risky'] ?? 0) }}</div>
                    <div style="font-size:0.78rem;color:rgba(255,255,255,0.4);">Risky</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card text-center">
                    <div style="font-size:1.6rem;font-weight:800;color:#adb5bd;">{{ number_format($s['unknown'] ?? 0) }}</div>
                    <div style="font-size:0.78rem;color:rgba(255,255,255,0.4);">Unknown</div>
                </div>
            </div>
        </div>
        @endif

        <!-- Results Table -->
        <div class="card">
            <div class="card-header py-3 px-4 d-flex align-items-center justify-content-between">
                <span class="fw-semibold">Results Preview</span>
                <span style="font-size:0.8rem;color:rgba(255,255,255,0.4);">Showing first {{ $results->count() }} results</span>
            </div>
            <div class="card-body p-0">
                @if($results->isEmpty())
                <div class="text-center py-5" style="color:rgba(255,255,255,0.3);">
                    <i class="fas fa-hourglass-half mb-2" style="font-size:1.5rem;display:block;"></i>
                    No results yet. Processing in progress...
                </div>
                @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="font-size:0.82rem;">
                        <thead>
                            <tr>
                                <th class="px-4">Email</th>
                                <th>Status</th>
                                <th>Score</th>
                                <th>Disposable</th>
                                <th>SMTP</th>
                                <th>Catch-All</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results as $r)
                            <tr>
                                <td class="px-4" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $r->email }}</td>
                                <td>
                                    @php $classes = ['valid'=>'badge-valid','invalid'=>'badge-invalid','risky'=>'badge-risky','unknown'=>'badge-unknown']; @endphp
                                    <span class="badge {{ $classes[$r->status] ?? 'badge-unknown' }}">{{ ucfirst($r->status) }}</span>
                                </td>
                                <td><span style="font-weight:700;color:{{ $r->score >= 70 ? '#6feaaa' : ($r->score >= 40 ? '#ffd60a' : '#ff8a9a') }}">{{ $r->score }}</span></td>
                                <td><i class="fas {{ $r->is_disposable ? 'fa-circle-check text-danger' : 'fa-circle-xmark' }}" style="color:{{ $r->is_disposable ? '#ff8a9a' : 'rgba(255,255,255,0.2)' }};"></i></td>
                                <td><i class="fas {{ $r->smtp_valid ? 'fa-circle-check' : 'fa-circle-xmark' }}" style="color:{{ $r->smtp_valid ? '#6feaaa' : 'rgba(255,255,255,0.2)' }};"></i></td>
                                <td><i class="fas {{ $r->is_catch_all ? 'fa-circle-exclamation' : 'fa-circle-xmark' }}" style="color:{{ $r->is_catch_all ? '#ffd60a' : 'rgba(255,255,255,0.2)' }};"></i></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@if(in_array($job->status, ['pending', 'processing']))
<script>
setTimeout(() => location.reload(), 4000);
</script>
@endif
@endpush
