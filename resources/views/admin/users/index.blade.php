@extends('layouts.admin')

@section('title', 'Users')
@section('page-title', 'User Management')

@section('content')
<div class="card">
    <div class="card-header py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span class="fw-semibold">All Users</span>
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name or email..." value="{{ request('search') }}" style="width:220px;">
            <select name="status" class="form-select form-select-sm" style="width:130px;">
                <option value="">All Status</option>
                @foreach(['active','inactive','suspended','banned'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-primary">Filter</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:0.85rem;">
                <thead>
                    <tr>
                        <th class="px-4">User</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Credits</th>
                        <th>Jobs</th>
                        <th>Joined</th>
                        <th class="px-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td class="px-4">
                            <div class="fw-semibold">{{ $user->name }}</div>
                            <div style="font-size:0.78rem;color:rgba(255,255,255,0.4);">{{ $user->email }}</div>
                        </td>
                        <td>
                            @php $roleColors = ['admin'=>'#ff8a9a','reseller'=>'#ffd60a','user'=>'#6ff0ff']; @endphp
                            <span style="color:{{ $roleColors[$user->role] ?? '#adb5bd' }};font-size:0.82rem;text-transform:capitalize;">{{ $user->role }}</span>
                        </td>
                        <td>
                            @php $stColors = ['active'=>'#6feaaa','inactive'=>'#adb5bd','suspended'=>'#ffd60a','banned'=>'#ff8a9a']; @endphp
                            <span class="badge" style="background:rgba(255,255,255,0.06);color:{{ $stColors[$user->status] ?? '#adb5bd' }};font-size:0.75rem;">{{ ucfirst($user->status) }}</span>
                        </td>
                        <td>{{ number_format($user->credit_balance) }}</td>
                        <td>{{ number_format($user->validation_jobs_count) }}</td>
                        <td style="color:rgba(255,255,255,0.4);">{{ $user->created_at->format('M d, Y') }}</td>
                        <td class="px-4">
                            <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm" style="background:rgba(0,212,255,0.1);border:1px solid rgba(0,212,255,0.2);color:#00d4ff;font-size:0.78rem;">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-4" style="color:rgba(255,255,255,0.3);">No users found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3">{{ $users->links() }}</div>
    </div>
</div>
@endsection
