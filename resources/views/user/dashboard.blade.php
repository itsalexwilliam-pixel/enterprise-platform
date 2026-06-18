@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

{{-- ================================================================
     STAT CARDS
     ================================================================ --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small mb-1">Total Validations</div>
                    <div class="h4 mb-0 fw-bold">{{ number_format($stats['total_validations'] ?? 0) }}</div>
                </div>
                <div class="stat-icon" style="background:rgba(123,47,247,0.15);color:#c084fc">
                    <i class="fas fa-envelope-circle-check"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small mb-1">Today</div>
                    <div class="h4 mb-0 fw-bold" style="color:#6feaaa">{{ number_format($stats['today_validations'] ?? 0) }}</div>
                </div>
                <div class="stat-icon" style="background:rgba(25,135,84,0.15);color:#6feaaa">
                    <i class="fas fa-calendar-day"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small mb-1">Bulk Jobs</div>
                    <div class="h4 mb-0 fw-bold" style="color:#6ff0ff">{{ number_format($stats['bulk_jobs'] ?? 0) }}</div>
                </div>
                <div class="stat-icon" style="background:rgba(0,212,255,0.12);color:#6ff0ff">
                    <i class="fas fa-list-check"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small mb-1">Credits</div>
                    <div class="h4 mb-0 fw-bold" style="color:#ffd60a">{{ number_format(auth()->user()->credit_balance) }}</div>
                </div>
                <div class="stat-icon" style="background:rgba(255,193,7,0.12);color:#ffd60a">
                    <i class="fas fa-coins"></i>
                </div>
            </div>
            <div class="mt-2">
                <a href="{{ route('user.billing') }}" class="btn btn-sm py-0" style="background:rgba(123,47,247,0.2);border:1px solid rgba(123,47,247,0.4);color:#c084fc;font-size:0.75rem;">
                    <i class="fas fa-plus me-1"></i> Buy Credits
                </a>
            </div>
        </div>
    </div>
</div>

{{-- ================================================================
     QUICK VALIDATOR
     ================================================================ --}}
<div class="card mb-4">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="fas fa-bolt" style="color:#ffd60a"></i>
        <span class="fw-semibold">Quick Email Validation</span>
        <span class="badge ms-auto" style="background:rgba(123,47,247,0.2);color:#c084fc;border:1px solid rgba(123,47,247,0.3);">
            1 Credit Per Check
        </span>
    </div>
    <div class="card-body" id="app">
        <quick-validator
            api-key="{{ auth()->user()->apiKeys()->where('status','active')->first()?->key ?? '' }}"
        ></quick-validator>
    </div>
</div>

{{-- ================================================================
     RECENT BULK JOBS
     ================================================================ --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <i class="fas fa-clock-rotate-left" style="color:#6ff0ff"></i>
            <span class="fw-semibold">Recent Bulk Jobs</span>
        </div>
        <a href="{{ route('user.bulk.index') }}" class="btn btn-sm" style="background:rgba(0,212,255,0.12);border:1px solid rgba(0,212,255,0.2);color:#6ff0ff;">
            <i class="fas fa-plus me-1"></i> New Job
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Name</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Valid</th>
                        <th>Progress</th>
                        <th>Date</th>
                        <th class="pe-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentJobs as $job)
                    <tr>
                        <td class="ps-3 fw-medium">{{ $job->name }}</td>
                        <td>
                            <span class="badge rounded-pill
                                @if($job->status === 'completed') bg-success
                                @elseif($job->status === 'processing') bg-primary
                                @elseif($job->status === 'failed') bg-danger
                                @elseif($job->status === 'cancelled') bg-secondary
                                @else badge-unknown @endif">
                                {{ ucfirst($job->status) }}
                            </span>
                        </td>
                        <td>{{ number_format($job->total_emails) }}</td>
                        <td class="text-success">{{ number_format($job->valid_emails) }}</td>
                        <td style="min-width:110px">
                            <div class="progress mb-1" style="height:5px">
                                <div class="progress-bar bg-primary" style="width:{{ $job->progress_percentage }}%"></div>
                            </div>
                            <small class="text-muted">{{ $job->progress_percentage }}%</small>
                        </td>
                        <td class="text-muted small">{{ $job->created_at->diffForHumans() }}</td>
                        <td class="pe-3 text-end">
                            @if($job->isCompleted())
                                <a href="{{ route('user.bulk.download', $job) }}?token={{ $job->download_token }}"
                                   class="btn btn-sm"
                                   style="background:rgba(25,135,84,0.15);border:1px solid rgba(25,135,84,0.3);color:#6feaaa;">
                                    <i class="fas fa-download"></i>
                                </a>
                            @else
                                <a href="{{ route('user.bulk.show', $job) }}"
                                   class="btn btn-sm"
                                   style="background:rgba(0,212,255,0.1);border:1px solid rgba(0,212,255,0.2);color:#6ff0ff;">
                                    <i class="fas fa-eye"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-2x mb-2 d-block" style="opacity:0.3"></i>
                            No bulk jobs yet.
                            <a href="{{ route('user.bulk.index') }}" style="color:#c084fc;">Upload your first list</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/app.js') }}"></script>
@endpush
