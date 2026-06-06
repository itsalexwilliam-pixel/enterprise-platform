@extends('layouts.guest')

@section('title', 'Set New Password')

@section('content')
<h4 class="text-white fw-bold mb-1">Set new password</h4>
<p class="mb-4" style="color:rgba(255,255,255,0.5);font-size:0.9rem;">Choose a strong password for your account.</p>

@if($errors->any())
<div class="alert alert-danger">
    @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
</div>
@endif

<form method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div class="mb-3">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $email ?? '') }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">New Password</label>
        <input type="password" name="password" class="form-control" placeholder="Min. 8 characters" required>
    </div>
    <div class="mb-4">
        <label class="form-label">Confirm New Password</label>
        <input type="password" name="password_confirmation" class="form-control" placeholder="Repeat password" required>
    </div>
    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
        <i class="fas fa-lock me-2"></i>Reset Password
    </button>
</form>
@endsection
