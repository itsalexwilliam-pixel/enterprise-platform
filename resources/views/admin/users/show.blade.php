@extends('layouts.admin')

@section('title', $user->name)
@section('page-title', 'User: ' . $user->name)

@section('content')
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <div style="width:64px;height:64px;background:linear-gradient(135deg,#7b2ff7,#00d4ff);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:800;color:#fff;margin:0 auto 12px;">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div class="fw-bold" style="font-size:1.1rem;">{{ $user->name }}</div>
                    <div style="color:rgba(255,255,255,0.4);font-size:0.85rem;">{{ $user->email }}</div>
                </div>
                <form method="POST" action="{{ route('admin.users.update', $user) }}">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.8rem;">Name</label>
                        <input type="text" name="name" class="form-control form-control-sm" value="{{ $user->name }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.8rem;">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            @foreach(['active','inactive','suspended','banned'] as $s)
                            <option value="{{ $s }}" {{ $user->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.8rem;">Role</label>
                        <select name="role" class="form-select form-select-sm">
                            @foreach(['user','reseller','admin'] as $r)
                            <option value="{{ $r }}" {{ $user->role === $r ? 'selected' : '' }}>{{ ucfirst($r) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" style="font-size:0.8rem;">Credit Balance</label>
                        <input type="number" name="credit_balance" class="form-control form-control-sm" value="{{ $user->credit_balance }}" min="0">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">Save Changes</button>
                </form>
            </div>
        </div>

        <!-- Add Credits -->
        <div class="card">
            <div class="card-header py-3 px-4"><span style="font-size:0.875rem;font-weight:600;">Add Credits</span></div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.users.add-credits', $user) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.8rem;">Amount</label>
                        <input type="number" name="amount" class="form-control form-control-sm" min="1" max="10000000" placeholder="1000" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.8rem;">Note</label>
                        <input type="text" name="note" class="form-control form-control-sm" placeholder="Reason...">
                    </div>
                    <button type="submit" class="btn btn-sm w-100" style="background:rgba(25,135,84,0.2);border:1px solid rgba(25,135,84,0.3);color:#6feaaa;">
                        <i class="fas fa-plus me-1"></i>Add Credits
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="stat-card text-center"><div style="font-size:1.5rem;font-weight:800;">{{ number_format($user->credit_balance) }}</div><div style="font-size:0.75rem;color:rgba(255,255,255,0.4);">Credits</div></div></div>
            <div class="col-md-3"><div class="stat-card text-center"><div style="font-size:1.5rem;font-weight:800;">{{ $user->validation_jobs_count }}</div><div style="font-size:0.75rem;color:rgba(255,255,255,0.4);">Bulk Jobs</div></div></div>
            <div class="col-md-3"><div class="stat-card text-center"><div style="font-size:1.5rem;font-weight:800;">{{ $user->api_keys_count }}</div><div style="font-size:0.75rem;color:rgba(255,255,255,0.4);">API Keys</div></div></div>
            <div class="col-md-3"><div class="stat-card text-center"><div style="font-size:1.5rem;font-weight:800;">{{ $user->created_at->diffForHumans() }}</div><div style="font-size:0.75rem;color:rgba(255,255,255,0.4);">Joined</div></div></div>
        </div>

        <!-- Recent Transactions -->
        <div class="card">
            <div class="card-header py-3 px-4"><span class="fw-semibold" style="font-size:0.875rem;">Recent Transactions</span></div>
            <div class="card-body p-0">
                @if($user->transactions->isEmpty())
                <div class="text-center py-4" style="color:rgba(255,255,255,0.3);font-size:0.85rem;">No transactions.</div>
                @else
                <table class="table table-hover mb-0" style="font-size:0.82rem;">
                    <thead><tr><th class="px-4">Type</th><th>Credits</th><th>Description</th><th class="px-4">Date</th></tr></thead>
                    <tbody>
                        @foreach($user->transactions as $tx)
                        <tr>
                            <td class="px-4" style="text-transform:capitalize;">{{ $tx->type }}</td>
                            <td style="color:{{ $tx->credits > 0 ? '#6feaaa' : '#ff8a9a' }};">{{ $tx->credits > 0 ? '+' : '' }}{{ number_format($tx->credits) }}</td>
                            <td style="color:rgba(255,255,255,0.5);">{{ $tx->description }}</td>
                            <td class="px-4" style="color:rgba(255,255,255,0.4);">{{ $tx->created_at->format('M d, H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
