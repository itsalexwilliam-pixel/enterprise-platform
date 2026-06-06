<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::orderBy('sort_order')->get();
        return view('admin.plans.index', compact('plans'));
    }

    public function update(Request $request, Plan $plan)
    {
        $request->validate([
            'name'            => 'required|string|max:100',
            'monthly_credits' => 'required|integer|min:0',
            'price_monthly'   => 'required|numeric|min:0',
            'max_api_keys'    => 'nullable|integer|min:1',
            'max_team_members'=> 'nullable|integer|min:1',
            'status'          => 'required|in:active,inactive',
        ]);

        // Map form field names → actual DB column names
        $plan->update([
            'name'             => $request->name,
            'credits_included' => (int) $request->monthly_credits,
            'price'            => (float) $request->price_monthly,
            'max_api_keys'     => $request->max_api_keys     ? (int) $request->max_api_keys     : $plan->max_api_keys,
            'max_team_members' => $request->max_team_members ? (int) $request->max_team_members : $plan->max_team_members,
            'is_active'        => $request->status === 'active',
        ]);

        return back()->with('success', 'Plan updated successfully.');
    }
}
