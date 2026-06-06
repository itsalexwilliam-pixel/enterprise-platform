@extends('layouts.admin')
@section('title', 'Bulk Jobs')
@section('page-title', 'Bulk Validation Jobs')
@section('content')
<div class="card">
    <div class="card-header py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span class="fw-semibold">All Jobs</span>
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search job name..." value="{{ request('search') }}" style="width:200px;">
            <select name="status" class="form-select form-select-sm" style="width:140px;">
                <option value="">All Status</option>
                @foreach(['pending','processing','completed','failed','cancelled'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-primary">Filter</button>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:0.85rem;">
                <thead>
                    <tr>
                        <th class="px-4">Job</th>
                        <th>User</th>
                        <th>Status</th>
                        <th>Emails</th>
                        <th>Progress</th>
                        <th>Credits</th>
                        <th>Created</th>
                        <th class="px-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jobs as $job)
                    <tr>
                        <td class="px-4">
                            <div class="fw-semibold">{{ Str::limit($job->name, 30) }}</div>
                            <div style="font-size:0.75rem;color:rgba(255,255,255,0.3);">{{ substr($job->uuid, 0, 8) }}...</div>
                        </td>
                        <td>
                            <div style="font-size:0.82rem;">{{ $job->user?->name }}</div>
                            <div style="font-size:0.75rem;color:rgba(255,255,255,0.3);">{{ $job->user?->email }}</div>
                        </td>
                        <td>
                            @php $stColors = ['pending'=>'#adb5bd','processing'=>'#00d4ff','completed'=>'#6feaaa','failed'=>'#ff8a9a','cancelled'=>'#adb5bd']; @endphp
                            <span class="badge" style="background:rgba(255,255,255,0.06);color:{{ $stColors[$job->status] ?? '#adb5bd' }};">{{ ucfirst($job->status) }}</span>
                        </td>
                        <td>{{ number_format($job->total_emails) }}</td>
                        <td>
                            <div class="progress mb-1" style="height:4px;width:80px;">
                                <div class="progress-bar" style="width:{{ $job->progress_percentage }}%;background:linear-gradient(135deg,#7b2ff7,#00d4ff);"></div>
                            </div>
                            <div style="font-size:0.72rem;color:rgba(255,255,255,0.4);">{{ $job->progress_percentage }}%</div>
                        </td>
                        <td>{{ number_format($job->credits_used) }}</td>
                        <td style="color:rgba(255,255,255,0.4);">{{ $job->created_at->format('M d, Y') }}</td>
                        <td class="px-4">
                            <div class="d-flex gap-1">
                                <a href="{{ route('admin.jobs.show', $job) }}" class="btn btn-sm" style="background:rgba(0,212,255,0.1);border:1px solid rgba(0,212,255,0.2);color:#00d4ff;font-size:0.75rem;"><i class="fas fa-eye"></i></a>
                                <form method="POST" action="{{ route('admin.jobs.destroy', $job) }}" onsubmit="return confirm('Delete this job and all results?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm" style="background:rgba(220,53,69,0.1);border:1px solid rgba(220,53,69,0.2);color:#ff8a9a;font-size:0.75rem;"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-4" style="color:rgba(255,255,255,0.3);">No jobs found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3">{{ $jobs->links() }}</div>
    </div>
</div>
@endsection
