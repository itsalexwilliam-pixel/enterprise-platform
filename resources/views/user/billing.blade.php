@extends('layouts.app')

@section('title', 'Billing')
@section('page-title', 'Billing & Credits')

@section('content')
<div class="row g-4">
    <!-- Credit Balance -->
    <div class="col-lg-4">
        <div class="card mb-4" style="background:linear-gradient(135deg,rgba(123,47,247,0.2),rgba(0,212,255,0.1));border-color:rgba(123,47,247,0.3);">
            <div class="card-body p-4 text-center">
                <i class="fas fa-coins mb-2" style="font-size:2rem;color:#ffd60a;"></i>
                <div style="font-size:2.5rem;font-weight:900;background:linear-gradient(135deg,#00d4ff,#7b2ff7);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">
                    {{ number_format($user->credit_balance) }}
                </div>
                <div style="color:rgba(255,255,255,0.5);">Credits Available</div>
                <div style="font-size:0.8rem;color:rgba(255,255,255,0.3);margin-top:4px;">1 credit = 1 email verification</div>
            </div>
        </div>

        <!-- Current Plan -->
        <div class="card">
            <div class="card-header py-3 px-4">
                <span class="fw-semibold" style="font-size:0.875rem;"><i class="fas fa-crown me-2" style="color:#ffd60a;"></i>Current Plan</span>
            </div>
            <div class="card-body p-4">
                @if($currentPlan)
                <div class="fw-bold mb-1" style="font-size:1.1rem;">{{ $currentPlan->name }}</div>
                <div style="color:rgba(255,255,255,0.4);font-size:0.85rem;">{{ number_format($currentPlan->monthly_credits) }} credits/month</div>
                <div class="mt-2" style="font-size:0.8rem;color:rgba(255,255,255,0.3);">${{ number_format($currentPlan->price_monthly, 2) }}/month</div>
                @else
                <div class="fw-bold mb-1">Free Plan</div>
                <div style="color:rgba(255,255,255,0.4);font-size:0.85rem;">100 signup credits</div>
                @endif
            </div>
        </div>
    </div>

    <!-- Credit Packages -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header py-3 px-4">
                <span class="fw-semibold"><i class="fas fa-shopping-cart me-2" style="color:#00d4ff;"></i>Buy Credits</span>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    @forelse($packages as $pkg)
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 h-100 {{ $pkg->popular ? '' : '' }}"
                             style="background:{{ $pkg->popular ? 'rgba(123,47,247,0.1)' : 'rgba(255,255,255,0.03)' }};border:1px solid {{ $pkg->popular ? 'rgba(123,47,247,0.4)' : 'rgba(255,255,255,0.08)' }};position:relative;">
                            @if($pkg->popular)
                            <div style="position:absolute;top:-10px;right:12px;background:linear-gradient(135deg,#7b2ff7,#00d4ff);color:#fff;font-size:0.7rem;font-weight:700;padding:2px 10px;border-radius:10px;">POPULAR</div>
                            @endif
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <div class="fw-bold">{{ $pkg->name }}</div>
                                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.4);">{{ number_format($pkg->credits) }} credits
                                        @if($pkg->bonus_credits > 0)
                                        <span style="color:#6feaaa;">+{{ number_format($pkg->bonus_credits) }} bonus</span>
                                        @endif
                                    </div>
                                </div>
                                <div style="font-size:1.4rem;font-weight:800;color:#fff;">${{ number_format($pkg->price, 0) }}</div>
                            </div>
                            <div style="font-size:0.75rem;color:rgba(255,255,255,0.3);margin-bottom:0.75rem;">
                                ${{ number_format($pkg->price / $pkg->credits * 1000, 2) }} per 1,000 verifications
                            </div>
                            <form method="POST" action="{{ route('user.billing.checkout') }}">
                                @csrf
                                <input type="hidden" name="package_id" value="{{ $pkg->id }}">
                                <button type="submit" class="btn btn-sm w-100 fw-semibold"
                                        style="background:{{ $pkg->popular ? 'linear-gradient(135deg,#7b2ff7,#00d4ff)' : 'rgba(255,255,255,0.06)' }};border:{{ $pkg->popular ? 'none' : '1px solid rgba(255,255,255,0.1)' }};color:#fff;font-size:0.82rem;">
                                    Buy {{ number_format($pkg->total_credits) }} Credits
                                </button>
                            </form>
                        </div>
                    </div>
                    @empty
                    <div class="col-12 text-center py-3" style="color:rgba(255,255,255,0.3);">No packages available.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction History -->
    <div class="col-12">
        <div class="card">
            <div class="card-header py-3 px-4">
                <span class="fw-semibold"><i class="fas fa-receipt me-2" style="color:#7b2ff7;"></i>Transaction History</span>
            </div>
            <div class="card-body p-0">
                @if($transactions->isEmpty())
                <div class="text-center py-5" style="color:rgba(255,255,255,0.3);">
                    <i class="fas fa-receipt mb-2" style="font-size:2rem;display:block;"></i>No transactions yet.
                </div>
                @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="font-size:0.85rem;">
                        <thead>
                            <tr>
                                <th class="px-4">Reference</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Credits</th>
                                <th>Amount</th>
                                <th class="px-4">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transactions as $tx)
                            <tr>
                                <td class="px-4"><code style="font-size:0.78rem;color:rgba(255,255,255,0.4);">{{ $tx->reference }}</code></td>
                                <td>
                                    @php $typeColors = ['purchase'=>'#6feaaa','subscription'=>'#6ff0ff','deduction'=>'#ff8a9a','refund'=>'#ffd60a','bonus'=>'#c084fc','adjustment'=>'#adb5bd']; @endphp
                                    <span style="color:{{ $typeColors[$tx->type] ?? '#adb5bd' }};font-size:0.8rem;text-transform:capitalize;">{{ $tx->type }}</span>
                                </td>
                                <td style="color:rgba(255,255,255,0.6);">{{ $tx->description }}</td>
                                <td>
                                    @if($tx->isCredit())
                                    <span style="color:#6feaaa;font-weight:600;">+{{ number_format($tx->credits) }}</span>
                                    @else
                                    <span style="color:#ff8a9a;font-weight:600;">-{{ number_format(abs($tx->credits)) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if(($tx->price_paid ?? 0) > 0)
                                    <span style="color:#6feaaa;">${{ number_format($tx->price_paid, 2) }}</span>
                                    @else
                                    <span style="color:rgba(255,255,255,0.3);">—</span>
                                    @endif
                                </td>
                                <td class="px-4" style="color:rgba(255,255,255,0.4);">{{ $tx->created_at->format('M d, Y H:i') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3">
                    {{ $transactions->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
