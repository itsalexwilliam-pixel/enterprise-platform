@extends('layouts.guest')

@section('title', 'Reset Password')

@section('content')
<h4 class="text-white fw-bold mb-1">Forgot your password?</h4>
<p class="mb-4" style="color:rgba(255,255,255,0.5);font-size:0.9rem;">Enter your email and we'll send a reset link.</p>

@if(session('status'))
<div class="alert alert-success"><i class="fas fa-check me-1"></i>{{ session('status') }}</div>
@endif

@if($errors->any())
<div class="alert alert-danger">
    @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
</div>
@endif

<form method="POST" action="{{ route('password.email') }}">
    @csrf
    <div class="mb-4">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" placeholder="you@example.com" value="{{ old('email') }}" required>
    </div>
    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
        <i class="fas fa-paper-plane me-2"></i>Send Reset Link
    </button>
</form>
@endsection

@section('below-card')
<p class="text-center mt-3" style="color:rgba(255,255,255,0.5);font-size:0.875rem;">
    <a href="{{ route('login') }}" class="fw-semibold"><i class="fas fa-arrow-left me-1"></i>Back to Sign In</a>
</p>
@endsection
