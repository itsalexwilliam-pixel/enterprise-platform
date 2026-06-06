@extends('layouts.admin')
@section('title', 'Transactions')
@section('page-title', 'Transactions')
@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div style="font-size:0.75rem;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Total Revenue</div>
            <div style="font-size:1.8rem;font-weight:800;color:#6feaaa;">${{ number_format($totalRevenue, 2) }}</div>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span class="fw-semibold">All Transactions</span>
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="User email..." value="{{ request('search') }}" style="width:200px;">
            <select name="type" class="form-select form-select-sm" style="width:130px;">
                <option value="">All Types</option>
                @foreach(['purchase','subscription','deduction','refund','bonus','adjustment'] as $t)
                <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-primary">Filter</button>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:0.85rem;">
                <thead><tr><th class="px-4">Reference</th><th>User</th><th>Type</th><th>Credits</th><th>Amount</th><th>Description</th><th class="px-4">Date</th></tr></thead>
                <tbody>
                    @forelse($transactions as $tx)
                    <tr>
                        <td class="px-4"><code style="font-size:0.75rem;color:rgba(255,255,255,0.4);">{{ $tx->reference }}</code></td>
                        <td>
                            <div style="font-size:0.82rem;">{{ $tx->user?->name }}</div>
                            <div style="font-size:0.75rem;color:rgba(255,255,255,0.3);">{{ $tx->user?->email }}</div>
                        </td>
                        <td style="text-transform:capitalize;font-size:0.82rem;">{{ $tx->type }}</td>
                        <td style="font-weight:600;color:{{ $tx->credits > 0 ? '#6feaaa' : '#ff8a9a' }}">{{ $tx->credits > 0 ? '+' : '' }}{{ number_format($tx->credits) }}</td>
                        <td style="color:{{ ($tx->price_paid ?? 0) > 0 ? '#6feaaa' : 'rgba(255,255,255,0.3)' }}">{{ ($tx->price_paid ?? 0) > 0 ? '$'.number_format($tx->price_paid,2) : '—' }}</td>
                        <td style="color:rgba(255,255,255,0.5);font-size:0.8rem;">{{ Str::limit($tx->description, 40) }}</td>
                        <td class="px-4" style="color:rgba(255,255,255,0.4);">{{ $tx->created_at->format('M d, Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-4" style="color:rgba(255,255,255,0.3);">No transactions found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3">{{ $transactions->links() }}</div>
    </div>
</div>
@endsection
