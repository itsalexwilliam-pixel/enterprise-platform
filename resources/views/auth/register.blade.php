@extends('layouts.guest')

@section('title', 'Create Account')

@section('content')
<h4 class="text-white fw-bold mb-1">Create your account</h4>
<p class="mb-4" style="color:rgba(255,255,255,0.5);font-size:0.9rem;">100 free credits. No credit card required.</p>

@if($errors->any())
<div class="alert alert-danger">
    @foreach($errors->all() as $error)
        <div><i class="fas fa-exclamation-circle me-1"></i>{{ $error }}</div>
    @endforeach
</div>
@endif

<form method="POST" action="{{ route('register') }}">
    @csrf
    <div class="mb-3">
        <label class="form-label">Full Name</label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
               placeholder="John Smith" value="{{ old('name') }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
               placeholder="you@company.com" value="{{ old('email') }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
               placeholder="Min. 8 characters with letters & numbers" required>
    </div>
    <div class="mb-4">
        <label class="form-label">Confirm Password</label>
        <input type="password" name="password_confirmation" class="form-control"
               placeholder="Repeat password" required>
    </div>
    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
        <i class="fas fa-user-plus me-2"></i>Create Account
    </button>
    <p class="text-center mt-3" style="font-size:0.78rem;color:rgba(255,255,255,0.35);">
        By registering you agree to our Terms of Service and Privacy Policy.
    </p>
</form>
@endsection

@section('below-card')
<p class="text-center mt-3" style="color:rgba(255,255,255,0.5);font-size:0.875rem;">
    Already have an account?
    <a href="{{ route('login') }}" class="fw-semibold">Sign in</a>
</p>
@endsection
