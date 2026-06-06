@extends('layouts.app')

@section('title', 'Webhooks')
@section('page-title', 'Webhook Endpoints')

@section('content')
<div class="row g-4">
    <!-- Create Webhook -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header py-3 px-4">
                <span class="fw-semibold"><i class="fas fa-plus me-2" style="color:#00d4ff;"></i>Add Webhook</span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('user.webhooks.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Webhook Name</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               placeholder="e.g. My CRM" value="{{ old('name') }}" required maxlength="100">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Endpoint URL</label>
                        <input type="url" name="url" class="form-control @error('url') is-invalid @enderror"
                               placeholder="https://yourapp.com/webhook" value="{{ old('url') }}" required>
                        @error('url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Events to Subscribe</label>
                        <div class="d-flex flex-column gap-2">
                            @php $selectedEvents = old('events', []); @endphp
                            @foreach(['job.completed' => 'Bulk Job Completed', 'job.failed' => 'Bulk Job Failed', 'email.validated' => 'Email Validated (API)'] as $event => $label)
                            <label style="display:flex;align-items:center;gap:0.6rem;font-size:0.875rem;color:rgba(255,255,255,0.7);cursor:pointer;">
                                <input type="checkbox" name="events[]" value="{{ $event }}"
                                       {{ in_array($event, $selectedEvents) ? 'checked' : '' }}
                                       style="width:16px;height:16px;accent-color:#7b2ff7;">
                                {{ $label }}
                            </label>
                            @endforeach
                        </div>
                        @error('events')<div class="text-danger" style="font-size:0.82rem;margin-top:4px;">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-semibold">
                        <i class="fas fa-bolt me-2"></i>Create Webhook
                    </button>
                </form>

                <div class="mt-4 p-3 rounded-2" style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);">
                    <h6 style="font-size:0.78rem;font-weight:600;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.5rem;">Payload Format</h6>
                    <pre style="font-size:0.72rem;color:#00d4ff;margin:0;background:rgba(0,0,0,0.3);padding:0.75rem;border-radius:6px;">POST https://yourapp.com/webhook
X-Webhook-Signature: sha256=...
Content-Type: application/json

{
  "event": "job.completed",
  "timestamp": "2025-01-01T00:00:00Z",
  "data": { ... }
}</pre>
                </div>
            </div>
        </div>
    </div>

    <!-- Webhooks List -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header py-3 px-4 d-flex align-items-center justify-content-between">
                <span class="fw-semibold"><i class="fas fa-list me-2" style="color:#7b2ff7;"></i>Your Webhooks</span>
                <span style="font-size:0.8rem;color:rgba(255,255,255,0.4);">{{ $webhooks->count() }} / 10</span>
            </div>
            <div class="card-body p-0">
                @if($webhooks->isEmpty())
                <div class="text-center py-5" style="color:rgba(255,255,255,0.3);">
                    <i class="fas fa-bolt mb-2" style="font-size:2rem;display:block;"></i>No webhooks configured yet.
                </div>
                @else
                @foreach($webhooks as $wh)
                <div class="p-4" style="{{ !$loop->last ? 'border-bottom:1px solid rgba(255,255,255,0.06);' : '' }}">
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <div class="rounded-circle" style="width:8px;height:8px;flex-shrink:0;background:{{ $wh->status === 'active' ? '#6feaaa' : '#ff8a9a' }};"></div>
                                <span class="fw-semibold">{{ $wh->name }}</span>
                            </div>
                            <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);word-break:break-all;margin-bottom:0.5rem;">{{ $wh->url }}</div>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($wh->events as $event)
                                <span class="badge" style="background:rgba(0,212,255,0.1);color:#00d4ff;border:1px solid rgba(0,212,255,0.2);font-size:0.7rem;">{{ $event }}</span>
                                @endforeach
                            </div>
                            <div class="mt-1" style="font-size:0.75rem;color:rgba(255,255,255,0.3);">
                                {{ $wh->deliveries_count }} deliveries · Created {{ $wh->created_at->diffForHumans() }}
                            </div>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <!-- Toggle -->
                            <form method="POST" action="{{ route('user.webhooks.toggle', $wh) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm"
                                        style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);color:rgba(255,255,255,0.5);font-size:0.78rem;"
                                        title="{{ $wh->status === 'active' ? 'Disable' : 'Enable' }}">
                                    <i class="fas {{ $wh->status === 'active' ? 'fa-pause' : 'fa-play' }}"></i>
                                </button>
                            </form>
                            <!-- Delete -->
                            <form method="POST" action="{{ route('user.webhooks.destroy', $wh) }}" onsubmit="return confirm('Delete this webhook?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm"
                                        style="background:rgba(220,53,69,0.1);border:1px solid rgba(220,53,69,0.2);color:#ff8a9a;font-size:0.78rem;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @endforeach
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
