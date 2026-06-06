@extends('layouts.admin')
@section('title', $job->name)
@section('page-title', 'Job: ' . Str::limit($job->name, 40))
@section('content')
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-3">Job Details</h6>
                @php $details = [
                    'Status' => ucfirst($job->status),
                    'User' => $job->user?->email,
                    'Total Emails' => number_format($job->total_emails),
                    'Processed' => number_format($job->processed_emails),
                    'Credits Used' => number_format($job->credits_used),
                    'File Type' => strtoupper($job->file_type ?? 'N/A'),
                    'Progress' => $job->progress_percentage . '%',
                    'Created' => $job->created_at->format('M d, Y H:i'),
                    'Started' => $job->started_at?->format('M d, Y H:i') ?? '—',
                    'Completed' => $job->completed_at?->format('M d, Y H:i') ?? '—',
                ]; @endphp
                @foreach($details as $label => $value)
                <div class="d-flex justify-content-between py-2 {{ !$loop->last ? 'border-bottom' : '' }}" style="border-color:rgba(255,255,255,0.06);font-size:0.82rem;">
                    <span style="color:rgba(255,255,255,0.4);">{{ $label }}</span>
                    <span>{{ $value }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        @if($job->summary)
        <div class="row g-3 mb-4">
            @php $s = $job->summary; @endphp
            @foreach(['valid'=>['#6feaaa','Valid'],'invalid'=>['#ff8a9a','Invalid'],'risky'=>['#ffd60a','Risky'],'unknown'=>['#adb5bd','Unknown']] as $key=>[$color,$label])
            <div class="col-6 col-md-3">
                <div class="stat-card text-center">
                    <div style="font-size:1.5rem;font-weight:800;color:{{ $color }};">{{ number_format($s[$key] ?? 0) }}</div>
                    <div style="font-size:0.75rem;color:rgba(255,255,255,0.4);">{{ $label }}</div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
        <div class="card">
            <div class="card-header py-3 px-4"><span class="fw-semibold" style="font-size:0.875rem;">Results (first 100)</span></div>
            <div class="card-body p-0">
                @if($results->isEmpty())
                <div class="text-center py-4" style="color:rgba(255,255,255,0.3);font-size:0.85rem;">No results yet.</div>
                @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="font-size:0.8rem;">
                        <thead><tr><th class="px-4">Email</th><th>Status</th><th>Score</th><th>Disposable</th><th>SMTP</th></tr></thead>
                        <tbody>
                            @foreach($results as $r)
                            <tr>
                                <td class="px-4" style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $r->email }}</td>
                                <td>
                                    @php $c=['valid'=>'badge-valid','invalid'=>'badge-invalid','risky'=>'badge-risky','unknown'=>'badge-unknown']; @endphp
                                    <span class="badge {{ $c[$r->status] ?? 'badge-unknown' }}">{{ ucfirst($r->status) }}</span>
                                </td>
                                <td style="font-weight:700;color:{{ $r->score >= 70 ? '#6feaaa' : ($r->score >= 40 ? '#ffd60a' : '#ff8a9a') }}">{{ $r->score }}</td>
                                <td><i class="fas {{ $r->is_disposable ? 'fa-check' : 'fa-times' }}" style="color:{{ $r->is_disposable ? '#ff8a9a' : 'rgba(255,255,255,0.2)' }}"></i></td>
                                <td><i class="fas {{ $r->smtp_valid ? 'fa-check' : 'fa-times' }}" style="color:{{ $r->smtp_valid ? '#6feaaa' : 'rgba(255,255,255,0.2)' }}"></i></td>
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
