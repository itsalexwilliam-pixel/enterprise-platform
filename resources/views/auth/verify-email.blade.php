@extends('layouts.guest')
@section('title', 'Verify Email')
@section('content')
<div class="text-center">
    <i class="fas fa-envelope-circle-check mb-3" style="font-size:3rem;color:#00d4ff;"></i>
    <h4 class="text-white fw-bold mb-2">Check your email</h4>
    <p style="color:rgba(255,255,255,0.5);font-size:0.9rem;">
        We've sent a verification link to your email address. Please check your inbox and click the link to activate your account.
    </p>
    <a href="{{ route('user.dashboard') }}" class="btn btn-primary w-100 py-2 mt-3 fw-semibold">
        <i class="fas fa-gauge-high me-2"></i>Go to Dashboard
    </a>
</div>
@endsection
