<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\CreditPackage;
use App\Models\Plan;
use App\Models\Transaction;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function index()
    {
        $user          = auth()->user();
        $transactions  = Transaction::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        $plans         = Plan::where('is_active', true)
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->get();

        $packages      = CreditPackage::where('is_active', true)
            ->orderBy('credits')
            ->get();

        $currentPlan   = $user->currentPlan();

        return view('user.billing', compact('user', 'transactions', 'plans', 'packages', 'currentPlan'));
    }

    /**
     * Handle Stripe checkout (stub — wire up Stripe SDK in production).
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'package_id' => 'required|exists:credit_packages,id',
        ]);

        // TODO: Integrate Stripe Checkout
        return back()->with('error', 'Payment processing coming soon. Contact support to add credits.');
    }
}
