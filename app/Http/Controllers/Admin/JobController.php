<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ValidationJob;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $query = ValidationJob::with('user')->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $jobs = $query->paginate(30)->withQueryString();

        return view('admin.jobs.index', compact('jobs'));
    }

    public function show(ValidationJob $job)
    {
        $job->load('user');
        $results = $job->results()->limit(100)->get();
        return view('admin.jobs.show', compact('job', 'results'));
    }

    public function destroy(ValidationJob $job)
    {
        $job->results()->delete();
        $job->delete();
        return redirect()->route('admin.jobs.index')->with('success', 'Job deleted.');
    }
}
