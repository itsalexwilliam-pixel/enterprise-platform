@extends('layouts.admin')
@section('title', 'Domain Cache')
@section('page-title', 'Domain DNS Cache')
@section('content')
<div class="card">
    <div class="card-header py-3 px-4"><span class="fw-semibold">Cached Domains</span></div>
    <div class="card-body p-0">
        @php
        $domains = \App\Models\Domain::orderByDesc('updated_at')->paginate(50);
        @endphp
        @if($domains->isEmpty())
        <div class="text-center py-5" style="color:rgba(255,255,255,0.3);">
            <i class="fas fa-globe mb-2" style="font-size:2rem;display:block;"></i>No domains cached yet.
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:0.82rem;">
                <thead><tr><th class="px-4">Domain</th><th>MX Valid</th><th>SPF</th><th>DMARC</th><th>Catch-All</th><th class="px-4">Updated</th></tr></thead>
                <tbody>
                    @foreach($domains as $d)
                    <tr>
                        <td class="px-4 fw-semibold">{{ $d->domain }}</td>
                        <td><i class="fas {{ $d->mx_found ? 'fa-check' : 'fa-times' }}" style="color:{{ $d->mx_found ? '#6feaaa' : '#ff8a9a' }}"></i></td>
                        <td><i class="fas {{ $d->spf_found ? 'fa-check' : 'fa-times' }}" style="color:{{ $d->spf_found ? '#6feaaa' : 'rgba(255,255,255,0.2)' }}"></i></td>
                        <td><i class="fas {{ $d->dmarc_found ? 'fa-check' : 'fa-times' }}" style="color:{{ $d->dmarc_found ? '#6feaaa' : 'rgba(255,255,255,0.2)' }}"></i></td>
                        <td><i class="fas {{ $d->is_catch_all ? 'fa-exclamation-triangle' : 'fa-times' }}" style="color:{{ $d->is_catch_all ? '#ffd60a' : 'rgba(255,255,255,0.2)' }}"></i></td>
                        <td class="px-4" style="color:rgba(255,255,255,0.4);">{{ $d->updated_at->diffForHumans() }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3">{{ $domains->links() }}</div>
        @endif
    </div>
</div>
@endsection
