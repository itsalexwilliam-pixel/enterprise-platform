@extends('layouts.app')

@section('title', 'Account Settings')
@section('page-title', 'Account Settings')

@section('content')
<div class="row g-4">
    <div class="col-lg-7">
        <!-- Profile -->
        <div class="card mb-4">
            <div class="card-header py-3 px-4">
                <span class="fw-semibold"><i class="fas fa-user me-2" style="color:#00d4ff;"></i>Profile Information</span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('user.account.update') }}">
                    @csrf @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', auth()->user()->name) }}" required maxlength="100">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" value="{{ auth()->user()->email }}" disabled
                                   style="color:rgba(255,255,255,0.4);">
                            <div class="form-text" style="color:rgba(255,255,255,0.3);font-size:0.75rem;">Email cannot be changed.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Company</label>
                            <input type="text" name="company" class="form-control"
                                   value="{{ old('company', auth()->user()->company) }}" maxlength="100" placeholder="Optional">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Country</label>
                            <input type="text" name="country" class="form-control"
                                   value="{{ old('country', auth()->user()->country) }}" maxlength="2" placeholder="US">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control"
                                   value="{{ old('phone', auth()->user()->phone) }}" placeholder="+1 555 000 0000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Timezone</label>
                            <select name="timezone" class="form-select">
                                @foreach(timezone_identifiers_list() as $tz)
                                <option value="{{ $tz }}" {{ (old('timezone', auth()->user()->timezone) === $tz) ? 'selected' : '' }}>{{ $tz }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary px-4 fw-semibold">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Change Password -->
        <div class="card">
            <div class="card-header py-3 px-4">
                <span class="fw-semibold"><i class="fas fa-lock me-2" style="color:#7b2ff7;"></i>Change Password</span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('user.account.password') }}">
                    @csrf @method('PUT')
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required>
                            @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">New Password</label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                                   placeholder="Min. 8 characters" required>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn px-4 fw-semibold" style="background:rgba(123,47,247,0.2);border:1px solid rgba(123,47,247,0.4);color:#c084fc;">
                                <i class="fas fa-key me-2"></i>Update Password
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Panel -->
    <div class="col-lg-5">
        <!-- Plan Info -->
        <div class="card mb-4">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-3"><i class="fas fa-crown me-2" style="color:#ffd60a;"></i>Current Plan</h6>
                @php $plan = auth()->user()->currentPlan(); @endphp
                @if($plan)
                <div style="font-size:1.2rem;font-weight:700;margin-bottom:0.25rem;">{{ $plan->name }}</div>
                <div style="font-size:0.85rem;color:rgba(255,255,255,0.5);">{{ number_format($plan->monthly_credits) }} credits/month</div>
                @else
                <div style="font-size:1.2rem;font-weight:700;margin-bottom:0.25rem;">Free Plan</div>
                <div style="font-size:0.85rem;color:rgba(255,255,255,0.5);">100 signup credits</div>
                @endif
                <a href="{{ route('user.billing') }}" class="btn btn-sm btn-outline-light w-100 mt-3" style="font-size:0.8rem;">
                    <i class="fas fa-arrow-up me-1"></i>Upgrade Plan
                </a>
            </div>
        </div>

        <!-- Account Info -->
        <div class="card mb-4">
            <div class="card-header py-3 px-4"><span class="fw-semibold" style="font-size:0.875rem;">Account Info</span></div>
            <div class="card-body p-4">
                <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid rgba(255,255,255,0.06);font-size:0.82rem;">
                    <span style="color:rgba(255,255,255,0.4);">Member Since</span>
                    <span>{{ auth()->user()->created_at->format('M Y') }}</span>
                </div>
                <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid rgba(255,255,255,0.06);font-size:0.82rem;">
                    <span style="color:rgba(255,255,255,0.4);">Role</span>
                    <span class="text-capitalize">{{ auth()->user()->role }}</span>
                </div>
                <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid rgba(255,255,255,0.06);font-size:0.82rem;">
                    <span style="color:rgba(255,255,255,0.4);">Email Verified</span>
                    @if(auth()->user()->email_verified_at)
                    <span style="color:#6feaaa;"><i class="fas fa-check me-1"></i>Yes</span>
                    @else
                    <span style="color:#ff8a9a;"><i class="fas fa-times me-1"></i>No</span>
                    @endif
                </div>
                <div class="d-flex justify-content-between py-2" style="font-size:0.82rem;">
                    <span style="color:rgba(255,255,255,0.4);">Last Login</span>
                    <span>{{ auth()->user()->last_login_at?->diffForHumans() ?? 'N/A' }}</span>
                </div>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="card" style="border-color:rgba(220,53,69,0.25);">
            <div class="card-header py-3 px-4" style="border-bottom:1px solid rgba(220,53,69,0.15);">
                <span class="fw-semibold" style="color:#ff8a9a;font-size:0.875rem;"><i class="fas fa-triangle-exclamation me-2"></i>Danger Zone</span>
            </div>
            <div class="card-body p-4">
                <p style="font-size:0.82rem;color:rgba(255,255,255,0.5);">Once you delete your account, all data will be permanently removed.</p>
                <button class="btn btn-sm" style="background:rgba(220,53,69,0.15);border:1px solid rgba(220,53,69,0.3);color:#ff8a9a;"
                        onclick="if(confirm('Are you absolutely sure? This cannot be undone.')) document.getElementById('deleteForm').submit()">
                    <i class="fas fa-trash me-2"></i>Delete My Account
                </button>
                <form id="deleteForm" method="POST" action="/user/account" style="display:none;">
                    @csrf @method('DELETE')
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
