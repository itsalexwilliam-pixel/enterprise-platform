<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::withCount(['validationJobs', 'apiKeys'])
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->paginate(30)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        $user->loadCount(['validationJobs', 'apiKeys']);
        $user->load(['transactions' => fn($q) => $q->latest()->limit(10)]);
        return view('admin.users.show', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'   => 'required|string|max:100',
            'status' => 'required|in:active,inactive,suspended,banned',
            'role'   => 'required|in:admin,reseller,user',
            'credit_balance' => 'required|integer|min:0',
        ]);

        $user->update($request->only(['name', 'status', 'role', 'credit_balance']));

        return back()->with('success', 'User updated successfully.');
    }

    public function addCredits(Request $request, User $user)
    {
        $request->validate([
            'amount' => 'required|integer|min:1|max:10000000',
            'note'   => 'nullable|string|max:255',
        ]);

        $user->addCredits(
            $request->amount,
            'adjustment',
            $request->note ?? 'Admin credit adjustment'
        );

        return back()->with('success', "Added {$request->amount} credits to {$user->name}.");
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'You cannot delete your own account.']);
        }

        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }
}
