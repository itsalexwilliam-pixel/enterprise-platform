@extends('layouts.app')

@section('title', $job->name)
@section('page-title', 'Job: ' . $job->name)

@push('styles')
<style>
    /* Pulsing dot for "currently processing" indicator */
    .pulse-dot {
        width: 9px; height: 9px; border-radius: 50%;
        background: #00d4ff; flex-shrink: 0;
        animation: pulse-glow 1.4s ease-in-out infinite;
    }
    @keyframes pulse-glow {
        0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(0,212,255,0.45); }
        50%       { opacity: 0.65; box-shadow: 0 0 0 7px rgba(0,212,255,0); }
    }

    /* Fade-in animation for new result rows */
    @keyframes rowFadeIn {
        from { opacity: 0; transform: translateY(-4px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .result-row-new { animation: rowFadeIn 0.3s ease forwards; }
</style>
@endpush

@section('content')
<div class="row g-4">
    {{-- ══════════════════════════════════════════════════
         LEFT COLUMN — Progress ring + Job details
    ══════════════════════════════════════════════════ --}}
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h6 class="fw-semibold mb-0">{{ $job->name }}</h6>
                    @php
                        $statusColors = [
                            'pending'    => '#adb5bd',
                            'processing' => '#00d4ff',
                            'completed'  => '#6feaaa',
                            'failed'     => '#ff8a9a',
                            'cancelled'  => '#adb5bd',
                        ];
                        $color = $statusColors[$job->status] ?? '#adb5bd';
                    @endphp
                    <span id="statusBadge" class="badge"
                          style="background:rgba(255,255,255,0.08);color:{{ $color }};border:1px solid {{ $color }}40;">
                        {{ ucfirst($job->status) }}
                    </span>
                </div>

                {{-- Progress Ring --}}
                <div class="text-center my-4">
                    <div style="position:relative;display:inline-block;">
                        <svg width="120" height="120" viewBox="0 0 120 120">
                            <circle cx="60" cy="60" r="50" fill="none" stroke="rgba(255,255,255,0.08)" stroke-width="8"/>
                            <circle id="progressRing" cx="60" cy="60" r="50" fill="none" stroke="url(#grad)" stroke-width="8"
                                    stroke-linecap="round" stroke-dasharray="314"
                                    stroke-dashoffset="{{ 314 - (314 * $job->progress_percentage / 100) }}"
                                    transform="rotate(-90 60 60)"
                                    style="transition:stroke-dashoffset 0.5s ease;"/>
                            <defs>
                                <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%"   style="stop-color:#7b2ff7"/>
                                    <stop offset="100%" style="stop-color:#00d4ff"/>
                                </linearGradient>
                            </defs>
                        </svg>
                        <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;">
                            <div id="progressPct" style="font-size:1.5rem;font-weight:800;">{{ $job->progress_percentage }}%</div>
                            <div style="font-size:0.7rem;color:rgba(255,255,255,0.4);">Complete</div>
                        </div>
                    </div>
                </div>

                {{-- Counts grid --}}
                <div class="row g-2 text-center mb-3">
                    <div class="col-6">
                        <div id="processedCount" style="font-size:1.2rem;font-weight:700;">{{ number_format($job->processed_emails) }}</div>
                        <div style="font-size:0.72rem;color:rgba(255,255,255,0.4);">Processed</div>
                    </div>
                    <div class="col-6">
                        <div id="totalCount" style="font-size:1.2rem;font-weight:700;">{{ number_format($job->total_emails) }}</div>
                        <div style="font-size:0.72rem;color:rgba(255,255,255,0.4);">Total</div>
                    </div>
                    <div class="col-6" id="speedWrap" style="{{ $job->processing_speed ? '' : 'display:none;' }}">
                        <div id="processingSpeed" style="font-size:1.2rem;font-weight:700;">{{ number_format($job->processing_speed ?? 0) }}/s</div>
                        <div style="font-size:0.72rem;color:rgba(255,255,255,0.4);">Speed</div>
                    </div>
                    <div class="col-6" id="etaWrap" style="{{ ($job->estimated_seconds && $job->status === 'processing') ? '' : 'display:none;' }}">
                        <div id="etaSeconds" style="font-size:1.2rem;font-weight:700;">
                            {{ $job->estimated_seconds ? gmdate('H:i:s', $job->estimated_seconds) : '—' }}
                        </div>
                        <div style="font-size:0.72rem;color:rgba(255,255,255,0.4);">ETA</div>
                    </div>
                </div>

                @if($job->status === 'completed' && $job->download_token)
                <a href="{{ route('user.bulk.download', $job) }}" class="btn btn-primary w-100 fw-semibold">
                    <i class="fas fa-download me-2"></i>Download Results (CSV)
                </a>
                @elseif(in_array($job->status, ['pending', 'processing']))
                <form method="POST" action="{{ route('user.bulk.cancel', $job) }}"
                      onsubmit="return confirm('Cancel this job? Remaining credits will be refunded.')">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn w-100"
                            style="background:rgba(220,53,69,0.15);border:1px solid rgba(220,53,69,0.3);color:#ff8a9a;">
                        <i class="fas fa-stop me-2"></i>Cancel Job
                    </button>
                </form>
                @endif
            </div>
        </div>

        {{-- Job Details --}}
        <div class="card">
            <div class="card-header py-3 px-4">
                <span class="fw-semibold" style="font-size:0.875rem;">Job Details</span>
            </div>
            <div class="card-body p-4">
                <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid rgba(255,255,255,0.06);font-size:0.82rem;">
                    <span style="color:rgba(255,255,255,0.4);">Job ID</span>
                    <span class="font-monospace" style="font-size:0.75rem;">{{ substr($job->uuid, 0, 8) }}…</span>
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

    {{-- ══════════════════════════════════════════════════
         RIGHT COLUMN — Live stats + ticker + results
    ══════════════════════════════════════════════════ --}}
    <div class="col-lg-8">

        {{-- Live Stats Row — always visible, AJAX-updated during processing --}}
        <div class="row g-3 mb-4">
            @php
                $s = $job->summary;
                $dispValid   = number_format($s['valid']   ?? $job->valid_emails   ?? 0);
                $dispInvalid = number_format($s['invalid'] ?? $job->invalid_emails ?? 0);
                $dispRisky   = number_format($s['risky']   ?? $job->risky_emails   ?? 0);
                $dispUnknown = number_format($s['unknown'] ?? $job->unknown_emails ?? 0);
            @endphp
            <div class="col-6 col-md-3">
                <div class="stat-card text-center">
                    <div id="liveValid" style="font-size:1.6rem;font-weight:800;color:#6feaaa;">{{ $dispValid }}</div>
                    <div style="font-size:0.78rem;color:rgba(255,255,255,0.4);">Valid</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card text-center">
                    <div id="liveInvalid" style="font-size:1.6rem;font-weight:800;color:#ff8a9a;">{{ $dispInvalid }}</div>
                    <div style="font-size:0.78rem;color:rgba(255,255,255,0.4);">Invalid</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card text-center">
                    <div id="liveRisky" style="font-size:1.6rem;font-weight:800;color:#ffd60a;">{{ $dispRisky }}</div>
                    <div style="font-size:0.78rem;color:rgba(255,255,255,0.4);">Risky</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card text-center">
                    <div id="liveUnknown" style="font-size:1.6rem;font-weight:800;color:#adb5bd;">{{ $dispUnknown }}</div>
                    <div style="font-size:0.78rem;color:rgba(255,255,255,0.4);">Unknown</div>
                </div>
            </div>
        </div>

        {{-- Currently-Processing Ticker (hidden unless status = processing) --}}
        <div id="processingTickerWrap" class="card mb-4"
             style="{{ $job->status === 'processing' ? '' : 'display:none;' }}">
            <div class="card-body py-3 px-4 d-flex align-items-center gap-3">
                <div class="pulse-dot"></div>
                <div style="font-size:0.78rem;color:rgba(255,255,255,0.45);white-space:nowrap;">Checking:</div>
                <div id="currentlyProcessing"
                     style="font-size:0.82rem;font-weight:600;font-family:monospace;color:#00d4ff;
                            overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    …waiting for first result…
                </div>
            </div>
        </div>

        {{-- Results Table --}}
        <div class="card">
            <div class="card-header py-3 px-4 d-flex align-items-center justify-content-between">
                <span class="fw-semibold">Results Preview</span>
                <span id="resultsCountLabel" style="font-size:0.8rem;color:rgba(255,255,255,0.4);">
                    Showing first {{ $results->count() }} results
                </span>
            </div>
            <div class="card-body p-0">
                {{-- Empty state (hidden once results appear) --}}
                <div id="resultsEmpty"
                     style="{{ $results->isEmpty() ? '' : 'display:none;' }}"
                     class="text-center py-5" style="color:rgba(255,255,255,0.3);">
                    <i class="fas fa-hourglass-half mb-2" style="font-size:1.5rem;display:block;"></i>
                    No results yet. Processing in progress…
                </div>

                <div class="table-responsive" id="resultsTableWrap"
                     style="{{ $results->isEmpty() ? 'display:none;' : '' }}">
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
                        <tbody id="liveResultsTbody">
                            @foreach($results as $r)
                            <tr>
                                <td class="px-4"
                                    style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                    {{ $r->email }}
                                </td>
                                <td>
                                    @php
                                        $cls = ['valid'=>'badge-valid','invalid'=>'badge-invalid',
                                                'risky'=>'badge-risky','unknown'=>'badge-unknown'];
                                    @endphp
                                    <span class="badge {{ $cls[$r->status] ?? 'badge-unknown' }}">
                                        {{ ucfirst($r->status) }}
                                    </span>
                                </td>
                                <td>
                                    <span style="font-weight:700;
                                          color:{{ $r->score >= 70 ? '#6feaaa' : ($r->score >= 40 ? '#ffd60a' : '#ff8a9a') }}">
                                        {{ $r->score }}
                                    </span>
                                </td>
                                <td>
                                    <i class="fas {{ $r->is_disposable ? 'fa-circle-check' : 'fa-circle-xmark' }}"
                                       style="color:{{ $r->is_disposable ? '#ff8a9a' : 'rgba(255,255,255,0.2)' }};"></i>
                                </td>
                                <td>
                                    <i class="fas {{ $r->smtp_valid ? 'fa-circle-check' : 'fa-circle-xmark' }}"
                                       style="color:{{ $r->smtp_valid ? '#6feaaa' : 'rgba(255,255,255,0.2)' }};"></i>
                                </td>
                                <td>
                                    <i class="fas {{ $r->is_catch_all ? 'fa-circle-exclamation' : 'fa-circle-xmark' }}"
                                       style="color:{{ $r->is_catch_all ? '#ffd60a' : 'rgba(255,255,255,0.2)' }};"></i>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const PROGRESS_URL   = '{{ route('user.bulk.progress', $job) }}';
    const INITIAL_STATUS = '{{ $job->status }}';

    const STATUS_COLORS = {
        pending    : '#adb5bd',
        processing : '#00d4ff',
        completed  : '#6feaaa',
        failed     : '#ff8a9a',
        cancelled  : '#adb5bd',
    };

    // ── Helpers ──────────────────────────────────────────────────────
    function fmt(n)    { return Number(n || 0).toLocaleString(); }
    function fmtTime(s) {
        if (!s) return '—';
        const h   = Math.floor(s / 3600);
        const m   = Math.floor((s % 3600) / 60);
        const sec = s % 60;
        return [h, m, sec].map(v => String(v).padStart(2, '0')).join(':');
    }
    function setEl(id, val) {
        const el = document.getElementById(id);
        if (el) el.textContent = val;
    }
    function show(id) { const el = document.getElementById(id); if (el) el.style.display = ''; }
    function hide(id) { const el = document.getElementById(id); if (el) el.style.display = 'none'; }

    // ── Row renderer ─────────────────────────────────────────────────
    const STATUS_BADGE = {
        valid    : 'badge-valid',
        invalid  : 'badge-invalid',
        risky    : 'badge-risky',
        unknown  : 'badge-unknown',
        disposable: 'badge-invalid',
        spam_trap : 'badge-invalid',
        catch_all : 'badge-risky',
    };
    function iconCell(bool, trueColor, falseColor) {
        const icon  = bool ? 'fa-circle-check' : 'fa-circle-xmark';
        const color = bool ? trueColor : (falseColor || 'rgba(255,255,255,0.2)');
        return `<i class="fas ${icon}" style="color:${color};"></i>`;
    }
    function scoreColor(s) {
        return s >= 70 ? '#6feaaa' : (s >= 40 ? '#ffd60a' : '#ff8a9a');
    }
    function renderRow(r) {
        const badgeCls  = STATUS_BADGE[r.status] || 'badge-unknown';
        const label     = r.status.charAt(0).toUpperCase() + r.status.slice(1);
        return `<tr class="result-row-new">
            <td class="px-4" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                title="${r.email}">${r.email}</td>
            <td><span class="badge ${badgeCls}">${label}</span></td>
            <td><span style="font-weight:700;color:${scoreColor(r.score)}">${r.score}</span></td>
            <td>${iconCell(r.is_disposable, '#ff8a9a')}</td>
            <td>${iconCell(r.smtp_valid,    '#6feaaa')}</td>
            <td>${iconCell(r.is_catch_all,  '#ffd60a')}</td>
        </tr>`;
    }

    // ── Update results table ─────────────────────────────────────────
    function updateResultsTable(results) {
        if (!results || results.length === 0) return;

        const tbody    = document.getElementById('liveResultsTbody');
        const emptyMsg = document.getElementById('resultsEmpty');
        const tableWrap= document.getElementById('resultsTableWrap');
        const countLbl = document.getElementById('resultsCountLabel');

        if (!tbody) return;

        // Hide empty state, show table
        if (emptyMsg) emptyMsg.style.display = 'none';
        if (tableWrap) tableWrap.style.display = '';

        // Re-render all rows (newest first from API, so just replace)
        tbody.innerHTML = results.map(renderRow).join('');

        if (countLbl) {
            countLbl.textContent = `Showing latest ${results.length} results`;
        }
    }

    // ── Main poll ────────────────────────────────────────────────────
    function poll() {
        fetch(PROGRESS_URL, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(d => {

            // ── Progress ring ───────────────────────────────────────
            const pct    = d.progress || 0;
            const offset = 314 - (314 * pct / 100);
            const ring   = document.getElementById('progressRing');
            if (ring) ring.setAttribute('stroke-dashoffset', offset);
            setEl('progressPct', pct + '%');

            // ── Status badge ────────────────────────────────────────
            const badge = document.getElementById('statusBadge');
            if (badge) {
                const c = STATUS_COLORS[d.status] || '#adb5bd';
                badge.textContent = d.status.charAt(0).toUpperCase() + d.status.slice(1);
                badge.style.color        = c;
                badge.style.borderColor  = c + '66';
            }

            // ── Counts ──────────────────────────────────────────────
            setEl('processedCount', fmt(d.processed_emails));
            setEl('totalCount',     fmt(d.total_emails));

            // ── Speed ───────────────────────────────────────────────
            if (d.processing_speed > 0) {
                setEl('processingSpeed', fmt(d.processing_speed) + '/s');
                show('speedWrap');
            } else {
                hide('speedWrap');
            }

            // ── ETA ─────────────────────────────────────────────────
            if (d.eta_seconds > 0 && d.status === 'processing') {
                setEl('etaSeconds', fmtTime(d.eta_seconds));
                show('etaWrap');
            } else {
                hide('etaWrap');
            }

            // ── Live stats cards ────────────────────────────────────
            setEl('liveValid',   fmt(d.valid_emails));
            setEl('liveInvalid', fmt(d.invalid_emails));
            setEl('liveRisky',   fmt(d.risky_emails));
            setEl('liveUnknown', fmt(d.unknown_emails));

            // ── Currently-processing ticker ─────────────────────────
            const tickerWrap = document.getElementById('processingTickerWrap');
            if (d.status === 'processing') {
                if (tickerWrap) tickerWrap.style.display = '';
                if (d.latest_results && d.latest_results.length > 0) {
                    // latest_results is newest-first, so [0] = most recently finished
                    setEl('currentlyProcessing', d.latest_results[0].email);
                }
            } else {
                if (tickerWrap) tickerWrap.style.display = 'none';
            }

            // ── Live results table ──────────────────────────────────
            updateResultsTable(d.latest_results);

            // ── Continue or finish ──────────────────────────────────
            if (d.status === 'pending' || d.status === 'processing') {
                setTimeout(poll, 2500);
            } else {
                // Job finished — reload after 1.5 s to show full results + download button
                setTimeout(() => location.reload(), 1500);
            }
        })
        .catch(() => {
            // Network error — retry slower
            setTimeout(poll, 5000);
        });
    }

    // Only start polling when the job is active
    if (INITIAL_STATUS === 'pending' || INITIAL_STATUS === 'processing') {
        setTimeout(poll, 2500);
    }
})();
</script>
@endpush
