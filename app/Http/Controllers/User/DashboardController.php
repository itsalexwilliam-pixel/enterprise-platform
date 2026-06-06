<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ValidationJob;
use App\Models\ValidationResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $stats = [
            'total_validations' => ValidationResult::where('user_id', $user->id)->count(),
            'today_validations' => ValidationResult::where('user_id', $user->id)
                ->whereDate('created_at', today())->count(),
            'bulk_jobs'         => ValidationJob::where('user_id', $user->id)->count(),
            'credit_balance'    => $user->credit_balance,
        ];

        $recentJobs = ValidationJob::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('user.dashboard', compact('stats', 'recentJobs'));
    }

    public function validatePage()
    {
        $apiKey = auth()->user()->apiKeys()->where('status', 'active')->first();
        return view('user.validate', compact('apiKey'));
    }

    public function account()
    {
        return view('user.account', ['user' => auth()->user()]);
    }

    public function updateAccount(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:100',
            'company' => 'nullable|string|max:100',
            'phone'   => 'nullable|string|max:20',
            'country' => 'nullable|string|size:2',
            'timezone'=> 'nullable|string|max:50',
        ]);

        auth()->user()->update($validated);

        return back()->with('success', 'Account updated successfully!');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $user = auth()->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'Password changed successfully!');
    }
}
