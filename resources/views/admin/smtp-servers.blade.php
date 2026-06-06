@extends('layouts.admin')
@section('title', 'SMTP Servers')
@section('page-title', 'SMTP Servers')
@section('content')
<div class="card">
    <div class="card-body p-5 text-center">
        <i class="fas fa-server mb-3" style="font-size:2.5rem;color:rgba(255,255,255,0.2);"></i>
        <h5 style="color:rgba(255,255,255,0.6);">SMTP Server Management</h5>
        <p style="color:rgba(255,255,255,0.3);font-size:0.875rem;">Configure the SMTP servers used for email validation probing.</p>
        <p style="color:rgba(255,255,255,0.3);font-size:0.85rem;">Coming soon — currently the validation engine uses automatic MX resolution.</p>
    </div>
</div>
@endsection
