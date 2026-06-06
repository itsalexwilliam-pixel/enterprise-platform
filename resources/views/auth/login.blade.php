@extends('layouts.guest')

@section('title', 'Sign In')

@section('content')
<h4 class="text-white fw-bold mb-1">Welcome back</h4>
<p class="mb-4" style="color:rgba(255,255,255,0.5);font-size:0.9rem;">Sign in to your account to continue</p>

@if($errors->any())
<div class="alert alert-danger">
    @foreach($errors->all() as $error)
        <div><i class="fas fa-exclamation-circle me-1"></i>{{ $error }}</div>
    @endforeach
</div>
@endif

@if(session('status'))
<div class="alert alert-success"><i class="fas fa-check me-1"></i>{{ session('status') }}</div>
@endif

<form method="POST" action="{{ route('login') }}">
    @csrf
    <div class="mb-3">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
               placeholder="you@example.com" value="{{ old('email') }}" required autofocus>
    </div>
    <div class="mb-3">
        <label class="form-label d-flex justify-content-between">
            <span>Password</span>
            <a href="{{ route('password.request') }}" style="font-size:0.8rem;">Forgot password?</a>
        </label>
        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
               placeholder="••••••••" required>
    </div>
    <div class="mb-4 d-flex align-items-center gap-2">
        <input type="checkbox" name="remember" id="remember" class="form-check-input" style="width:16px;height:16px;background:rgba(255,255,255,0.1);border-color:rgba(255,255,255,0.3);">
        <label for="remember" style="font-size:0.875rem;color:rgba(255,255,255,0.6);">Remember me</label>
    </div>
    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
        <i class="fas fa-right-to-bracket me-2"></i>Sign In
    </button>
</form>
@endsection

@section('below-card')
<p class="text-center mt-3" style="color:rgba(255,255,255,0.5);font-size:0.875rem;">
    Don't have an account?
    <a href="{{ route('register') }}" class="fw-semibold">Create one free</a>
</p>
@endsection
