@extends('layouts.app')

@section('title', 'Bulk Validation')
@section('page-title', 'Bulk Email Validation')

@section('content')
<div class="row g-4">
    <!-- Upload Form -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header py-3 px-4 d-flex align-items-center gap-2">
                <i class="fas fa-upload" style="color:#00d4ff;"></i>
                <span class="fw-semibold">Upload Email List</span>
            </div>
            <div class="card-body p-4">
                @if(auth()->user()->credit_balance < 1)
                <div class="alert alert-warning">
                    <i class="fas fa-coins me-2"></i>
                    You have no credits. <a href="{{ route('user.billing') }}">Purchase credits</a> to use bulk validation.
                </div>
                @else
                <form method="POST" action="{{ route('user.bulk.upload') }}" enctype="multipart/form-data" id="uploadForm">
                    @csrf
                    <div id="dropZone" class="rounded-3 text-center p-5 mb-4" style="border:2px dashed rgba(255,255,255,0.15);cursor:pointer;transition:all 0.3s;">
                        <i class="fas fa-cloud-upload-alt mb-3" style="font-size:2.5rem;color:rgba(255,255,255,0.3);"></i>
                        <div class="fw-semibold mb-1">Drag &amp; drop your file here</div>
                        <div style="color:rgba(255,255,255,0.4);font-size:0.875rem;">or click to browse</div>
                        <div class="mt-2" style="color:rgba(255,255,255,0.3);font-size:0.78rem;">CSV, XLSX, or TXT — up to 100MB / 10M emails</div>
                        <input type="file" name="file" id="fileInput" accept=".csv,.xlsx,.txt" class="d-none" required>
                    </div>

                    <div id="fileInfo" class="d-none mb-4 p-3 rounded-2" style="background:rgba(0,212,255,0.08);border:1px solid rgba(0,212,255,0.2);">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-file-csv" style="color:#00d4ff;font-size:1.2rem;"></i>
                            <div>
                                <div class="fw-semibold" id="fileName">file.csv</div>
                                <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);" id="fileSize">0 KB</div>
                            </div>
                            <button type="button" class="btn btn-sm ms-auto" onclick="clearFile()"
                                    style="background:rgba(220,53,69,0.15);border:1px solid rgba(220,53,69,0.3);color:#ff8a9a;">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Job Name (optional)</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Newsletter Cleanup Jan 2025" value="{{ old('name') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Column (CSV only)</label>
                            <input type="text" name="email_column" class="form-control" placeholder="email" value="{{ old('email_column', 'email') }}">
                            <div class="form-text" style="color:rgba(255,255,255,0.3);font-size:0.78rem;">Column header name in your CSV</div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3">
                        <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold" id="submitBtn" disabled>
                            <i class="fas fa-rocket me-2"></i>Start Validation
                        </button>
                        <div style="font-size:0.82rem;color:rgba(255,255,255,0.4);">
                            <i class="fas fa-info-circle me-1"></i>
                            Credits will be deducted based on email count
                        </div>
                    </div>
                </form>
                @endif
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-3"><i class="fas fa-coins me-2" style="color:#ffd60a;"></i>Available Credits</h6>
                <div style="font-size:2rem;font-weight:800;background:linear-gradient(135deg,#00d4ff,#7b2ff7);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">
                    {{ number_format(auth()->user()->credit_balance) }}
                </div>
                <a href="{{ route('user.billing') }}" class="btn btn-sm btn-outline-light w-100 mt-3" style="font-size:0.8rem;">
                    <i class="fas fa-plus me-1"></i>Buy Credits
                </a>
            </div>
        </div>
        <div class="card">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-3"><i class="fas fa-circle-info me-2" style="color:#00d4ff;"></i>Supported Formats</h6>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge" style="background:rgba(25,135,84,0.2);color:#6feaaa;">CSV</span>
                    <span style="font-size:0.82rem;color:rgba(255,255,255,0.5);">Comma-separated, with header row</span>
                </div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge" style="background:rgba(13,202,240,0.2);color:#6ff0ff;">XLSX</span>
                    <span style="font-size:0.82rem;color:rgba(255,255,255,0.5);">Excel spreadsheet, first sheet</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge" style="background:rgba(255,193,7,0.2);color:#ffd60a;">TXT</span>
                    <span style="font-size:0.82rem;color:rgba(255,255,255,0.5);">One email per line</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Jobs Table -->
    <div class="col-12">
        <div class="card">
            <div class="card-header py-3 px-4 d-flex align-items-center justify-content-between">
                <span class="fw-semibold"><i class="fas fa-history me-2" style="color:#7b2ff7;"></i>Recent Jobs</span>
                <button onclick="location.reload()" class="btn btn-sm" style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);color:rgba(255,255,255,0.7);font-size:0.8rem;">
                    <i class="fas fa-refresh me-1"></i>Refresh
                </button>
            </div>
            <div class="card-body p-0">
                @if($jobs->isEmpty())
                <div class="text-center py-5" style="color:rgba(255,255,255,0.3);">
                    <i class="fas fa-inbox" style="font-size:2rem;margin-bottom:0.75rem;display:block;"></i>
                    No validation jobs yet. Upload a file to get started.
                </div>
                @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="px-4">Job Name</th>
                                <th>Status</th>
                                <th>Emails</th>
                                <th>Progress</th>
                                <th>Created</th>
                                <th class="px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($jobs as $job)
                            <tr>
                                <td class="px-4">
                                    <div class="fw-semibold" style="font-size:0.875rem;">{{ $job->name }}</div>
                                    <div style="font-size:0.75rem;color:rgba(255,255,255,0.3);">{{ substr($job->uuid, 0, 8) }}...</div>
                                </td>
                                <td>
                                    @php
                                        $statusMap = [
                                            'pending'    => ['class' => 'badge-unknown', 'icon' => 'fa-clock'],
                                            'processing' => ['class' => 'badge-risky',   'icon' => 'fa-spinner fa-spin'],
                                            'completed'  => ['class' => 'badge-valid',   'icon' => 'fa-check'],
                                            'failed'     => ['class' => 'badge-invalid', 'icon' => 'fa-times'],
                                            'cancelled'  => ['class' => 'badge-unknown', 'icon' => 'fa-ban'],
                                        ];
                                        $s = $statusMap[$job->status] ?? $statusMap['pending'];
                                    @endphp
                                    <span class="badge {{ $s['class'] }}">
                                        <i class="fas {{ $s['icon'] }} me-1"></i>{{ ucfirst($job->status) }}
                                    </span>
                                </td>
                                <td style="font-size:0.875rem;">{{ number_format($job->total_emails) }}</td>
                                <td style="min-width:120px;">
                                    <div class="progress mb-1" style="height:6px;">
                                        <div class="progress-bar" role="progressbar"
                                             style="width:{{ $job->progress_percentage }}%;background:linear-gradient(135deg,#7b2ff7,#00d4ff);"></div>
                                    </div>
                                    <div style="font-size:0.75rem;color:rgba(255,255,255,0.4);">{{ $job->progress_percentage }}%</div>
                                </td>
                                <td style="font-size:0.8rem;color:rgba(255,255,255,0.5);">{{ $job->created_at->diffForHumans() }}</td>
                                <td class="px-4">
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('user.bulk.show', $job) }}" class="btn btn-sm"
                                           style="background:rgba(0,212,255,0.1);border:1px solid rgba(0,212,255,0.2);color:#00d4ff;font-size:0.78rem;">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($job->status === 'completed' && $job->download_token)
                                        <a href="{{ route('user.bulk.download', $job) }}" class="btn btn-sm"
                                           style="background:rgba(25,135,84,0.1);border:1px solid rgba(25,135,84,0.2);color:#6feaaa;font-size:0.78rem;">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        @endif
                                        @if(in_array($job->status, ['pending', 'processing']))
                                        <form method="POST" action="{{ route('user.bulk.cancel', $job) }}" onsubmit="return confirm('Cancel this job?')">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn btn-sm"
                                                    style="background:rgba(220,53,69,0.1);border:1px solid rgba(220,53,69,0.2);color:#ff8a9a;font-size:0.78rem;">
                                                <i class="fas fa-stop"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
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
<script>
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const fileInfo = document.getElementById('fileInfo');
const submitBtn = document.getElementById('submitBtn');

if (dropZone) {
    dropZone.addEventListener('click', () => fileInput.click());
    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.style.borderColor = '#7b2ff7'; dropZone.style.background = 'rgba(123,47,247,0.05)'; });
    dropZone.addEventListener('dragleave', () => { dropZone.style.borderColor = 'rgba(255,255,255,0.15)'; dropZone.style.background = ''; });
    dropZone.addEventListener('drop', e => { e.preventDefault(); dropZone.style.borderColor = 'rgba(255,255,255,0.15)'; dropZone.style.background = ''; if (e.dataTransfer.files[0]) { fileInput.files = e.dataTransfer.files; showFile(e.dataTransfer.files[0]); } });
    fileInput.addEventListener('change', () => { if (fileInput.files[0]) showFile(fileInput.files[0]); });
}

function showFile(file) {
    document.getElementById('fileName').textContent = file.name;
    document.getElementById('fileSize').textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';
    if (fileInfo) fileInfo.classList.remove('d-none');
    if (dropZone) dropZone.classList.add('d-none');
    if (submitBtn) submitBtn.disabled = false;
}

function clearFile() {
    fileInput.value = '';
    if (fileInfo) fileInfo.classList.add('d-none');
    if (dropZone) dropZone.classList.remove('d-none');
    if (submitBtn) submitBtn.disabled = true;
}

// Auto-refresh if any job is processing
@if($jobs->where('status', 'processing')->count() > 0)
setTimeout(() => location.reload(), 5000);
@endif
</script>
@endpush
