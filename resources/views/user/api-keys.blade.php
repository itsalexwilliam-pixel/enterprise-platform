@extends('layouts.app')

@section('title', 'API Keys')
@section('page-title', 'API Keys')

@section('content')

@if(session('new_api_key'))
<div class="alert alert-success d-flex align-items-start gap-3 mb-4" style="border:1px solid rgba(25,135,84,0.4);">
    <i class="fas fa-key mt-1" style="color:#6feaaa;font-size:1.2rem;"></i>
    <div>
        <div class="fw-semibold mb-1">Your new API key (shown once — save it now!)</div>
        <div class="d-flex align-items-center gap-2">
            <code id="newKey" style="background:rgba(0,0,0,0.3);padding:0.4rem 0.8rem;border-radius:6px;font-size:0.85rem;word-break:break-all;color:#6feaaa;">{{ session('new_api_key') }}</code>
            <button onclick="copyKey('newKey')" class="btn btn-sm" style="background:rgba(25,135,84,0.15);border:1px solid rgba(25,135,84,0.3);color:#6feaaa;white-space:nowrap;">
                <i class="fas fa-copy me-1"></i>Copy
            </button>
        </div>
    </div>
</div>
@endif

<div class="row g-4">
    <!-- Create New Key -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header py-3 px-4">
                <span class="fw-semibold"><i class="fas fa-plus me-2" style="color:#00d4ff;"></i>Create API Key</span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('user.api-keys.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Key Name</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               placeholder="e.g. Production App" value="{{ old('name') }}" required maxlength="100">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-semibold">
                        <i class="fas fa-key me-2"></i>Generate API Key
                    </button>
                </form>

                <div class="mt-4 p-3 rounded-2" style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);">
                    <h6 style="font-size:0.8rem;font-weight:600;color:rgba(255,255,255,0.6);margin-bottom:0.75rem;">How to use your API key</h6>
                    <pre style="font-size:0.75rem;color:#00d4ff;margin:0;white-space:pre-wrap;word-break:break-all;background:rgba(0,0,0,0.3);padding:0.75rem;border-radius:6px;">curl -X POST {{ url('/api/v1/validate') }} \
  -H "X-API-Key: ev_your_key_here" \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com"}'</pre>
                </div>
            </div>
        </div>
    </div>

    <!-- Keys List -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header py-3 px-4 d-flex align-items-center justify-content-between">
                <span class="fw-semibold"><i class="fas fa-list me-2" style="color:#7b2ff7;"></i>Your API Keys</span>
                <span style="font-size:0.8rem;color:rgba(255,255,255,0.4);">{{ $apiKeys->where('status','active')->count() }} active</span>
            </div>
            <div class="card-body p-0">
                @if($apiKeys->isEmpty())
                <div class="text-center py-5" style="color:rgba(255,255,255,0.3);">
                    <i class="fas fa-key mb-2" style="font-size:2rem;display:block;"></i>
                    No API keys yet. Create one to get started.
                </div>
                @else
                @foreach($apiKeys as $key)
                <div class="p-4" style="{{ !$loop->last ? 'border-bottom:1px solid rgba(255,255,255,0.06);' : '' }}">
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="fw-semibold">{{ $key->name }}</span>
                                @if($key->status === 'active')
                                <span class="badge" style="background:rgba(25,135,84,0.2);color:#6feaaa;border:1px solid rgba(25,135,84,0.3);font-size:0.7rem;">Active</span>
                                @else
                                <span class="badge" style="background:rgba(220,53,69,0.2);color:#ff8a9a;border:1px solid rgba(220,53,69,0.3);font-size:0.7rem;">Revoked</span>
                                @endif
                            </div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <code style="font-size:0.8rem;color:rgba(255,255,255,0.5);background:rgba(0,0,0,0.2);padding:0.2rem 0.5rem;border-radius:4px;">
                                    {{ $key->key_prefix }}••••••••••••••••••••••••
                                </code>
                            </div>
                            <div style="font-size:0.75rem;color:rgba(255,255,255,0.3);">
                                Created {{ $key->created_at->diffForHumans() }}
                                @if($key->last_used_at) · Last used {{ $key->last_used_at->diffForHumans() }} @endif
                            </div>
                        </div>
                        @if($key->status === 'active')
                        <div class="d-flex gap-2">
                            <!-- Edit Modal Trigger -->
                            <button class="btn btn-sm" onclick="openEdit({{ $key->id }}, '{{ addslashes($key->name) }}')"
                                    style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);color:rgba(255,255,255,0.6);font-size:0.78rem;">
                                <i class="fas fa-pen"></i>
                            </button>
                            <!-- Revoke -->
                            <form method="POST" action="{{ route('user.api-keys.destroy', $key) }}" onsubmit="return confirm('Revoke this API key? This cannot be undone.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm" style="background:rgba(220,53,69,0.1);border:1px solid rgba(220,53,69,0.2);color:#ff8a9a;font-size:0.78rem;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>

                    <!-- Rate Limits -->
                    <div class="d-flex gap-3 mt-2">
                        <span style="font-size:0.75rem;color:rgba(255,255,255,0.3);">
                            <i class="fas fa-gauge me-1"></i>{{ number_format($key->rate_limit_per_minute) }}/min
                        </span>
                        <span style="font-size:0.75rem;color:rgba(255,255,255,0.3);">
                            <i class="fas fa-calendar-day me-1"></i>{{ number_format($key->rate_limit_per_day) }}/day
                        </span>
                        @if($key->daily_count > 0)
                        <span style="font-size:0.75rem;color:rgba(255,193,7,0.6);">
                            <i class="fas fa-chart-bar me-1"></i>{{ number_format($key->daily_count) }} used today
                        </span>
                        @endif
                    </div>
                </div>
                @endforeach
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:#1a1a2e;border:1px solid rgba(255,255,255,0.1);">
            <form method="POST" id="editForm">
                @csrf @method('PUT')
                <div class="modal-header" style="border-bottom:1px solid rgba(255,255,255,0.08);">
                    <h5 class="modal-title text-white">Rename API Key</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <label class="form-label">Key Name</label>
                    <input type="text" name="name" id="editName" class="form-control" required maxlength="100">
                </div>
                <div class="modal-footer" style="border-top:1px solid rgba(255,255,255,0.08);">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function copyKey(id) {
    const text = document.getElementById(id).textContent.trim();
    navigator.clipboard.writeText(text).then(() => {
        const btn = event.target.closest('button');
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check me-1"></i>Copied!';
        setTimeout(() => btn.innerHTML = orig, 2000);
    });
}
function openEdit(id, name) {
    document.getElementById('editForm').action = `/user/api-keys/${id}`;
    document.getElementById('editName').value = name;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
@endpush
