<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiKeyController extends Controller
{
    public function index()
    {
        $apiKeys = ApiKey::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->get();

        return view('user.api-keys', compact('apiKeys'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $maxKeys = $user->currentPlan()?->max_api_keys ?? 3;
        if (ApiKey::where('user_id', $user->id)->where('status', 'active')->count() >= $maxKeys) {
            return back()->withErrors(['name' => "Maximum {$maxKeys} API keys allowed on your plan."]);
        }

        $request->validate(['name' => 'required|string|max:100']);

        $keyValue = 'ev_' . Str::random(56);
        ApiKey::create([
            'user_id'               => $user->id,
            'name'                  => $request->name,
            'key'                   => $keyValue,
            'key_prefix'            => substr($keyValue, 0, 8),
            'status'                => 'active',
            'rate_limit_per_minute' => $user->getApiRateLimit(),
            'rate_limit_per_day'    => 10000,
        ]);

        return back()->with([
            'success'     => 'API key created successfully!',
            'new_api_key' => $keyValue,  // shown only once
        ]);
    }

    public function update(Request $request, int $id)
    {
        $request->validate(['name' => 'required|string|max:100']);

        ApiKey::where('id', $id)->where('user_id', auth()->id())->firstOrFail()
            ->update(['name' => $request->name]);

        return back()->with('success', 'API key updated.');
    }

    public function destroy(int $id)
    {
        ApiKey::where('id', $id)->where('user_id', auth()->id())->firstOrFail()
            ->update(['status' => 'revoked']);

        return back()->with('success', 'API key revoked.');
    }
}
