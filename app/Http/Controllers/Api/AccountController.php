<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        return response()->json([
            'success' => true,
            'data'    => [
                'id'             => $user->id,
                'name'           => $user->name,
                'email'          => $user->email,
                'credit_balance' => $user->credit_balance,
                'role'           => $user->role,
                'status'         => $user->status,
                'created_at'     => $user->created_at,
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'    => 'sometimes|string|max:100',
            'company' => 'sometimes|nullable|string|max:100',
            'phone'   => 'sometimes|nullable|string|max:20',
        ]);

        $request->user()->update($validated);

        return response()->json(['success' => true, 'message' => 'Account updated.']);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();
        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json(['success' => false, 'error' => 'Current password is incorrect.'], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);
        return response()->json(['success' => true, 'message' => 'Password changed.']);
    }

    public function listApiKeys(Request $request): JsonResponse
    {
        $keys = $request->user()->apiKeys()
            ->select('id', 'name', 'key', 'key_prefix', 'status', 'rate_limit_per_minute', 'rate_limit_per_day', 'requests_today', 'total_requests', 'last_used_at', 'expires_at', 'created_at')
            ->get()
            ->map(fn ($k) => array_merge($k->toArray(), ['key' => $k->key_prefix . str_repeat('*', 50)]));

        return response()->json(['success' => true, 'data' => $keys]);
    }

    public function createApiKey(Request $request): JsonResponse
    {
        $request->validate(['name' => 'required|string|max:100']);

        $rawKey = \App\Models\ApiKey::generateKey();
        $key = \App\Models\ApiKey::create([
            'user_id'    => $request->user()->id,
            'name'       => $request->name,
            'key'        => $rawKey,
            'key_prefix' => substr($rawKey, 0, 8),
            'status'     => 'active',
        ]);

        return response()->json(['success' => true, 'data' => $key], 201);
    }

    public function updateApiKey(Request $request, int $id): JsonResponse
    {
        $key = $request->user()->apiKeys()->findOrFail($id);
        $key->update($request->only(['name', 'status']));
        return response()->json(['success' => true]);
    }

    public function revokeApiKey(Request $request, int $id): JsonResponse
    {
        $request->user()->apiKeys()->findOrFail($id)->update(['status' => 'revoked']);
        return response()->json(['success' => true]);
    }

    public function creditBalance(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'balance' => $request->user()->credit_balance,
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $transactions = $request->user()->transactions()
            ->latest()
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $transactions]);
    }

    public function usage(Request $request): JsonResponse
    {
        $user  = $request->user();
        $today = now()->startOfDay();

        return response()->json([
            'success' => true,
            'data'    => [
                'total'   => \App\Models\ValidationResult::where('user_id', $user->id)->count(),
                'today'   => \App\Models\ValidationResult::where('user_id', $user->id)->where('created_at', '>=', $today)->count(),
                'credits' => $user->credit_balance,
            ],
        ]);
    }

    public function dailyUsage(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = \App\Models\ValidationResult::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json(['success' => true, 'data' => $data]);
    }
}
