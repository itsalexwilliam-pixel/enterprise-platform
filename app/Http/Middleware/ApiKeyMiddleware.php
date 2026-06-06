<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * ============================================================
 * API Key Authentication Middleware
 *
 * Supports two authentication methods:
 * 1. X-API-Key header: X-API-Key: your_api_key
 * 2. Bearer token: Authorization: Bearer your_api_key
 *
 * Also enforces:
 * - IP whitelist
 * - Key expiration
 * - Daily request limits
 * ============================================================
 */
class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Extract API key from request
        $apiKeyValue = $this->extractApiKey($request);

        if (! $apiKeyValue) {
            return response()->json([
                'success' => false,
                'error'   => 'API key required. Provide X-API-Key header or Bearer token.',
            ], 401);
        }

        // Lookup API key (cached for performance)
        $apiKey = $this->findApiKey($apiKeyValue);

        if (! $apiKey) {
            return response()->json([
                'success' => false,
                'error'   => 'Invalid API key.',
            ], 401);
        }

        // Check key status
        if ($apiKey->status !== 'active') {
            return response()->json([
                'success' => false,
                'error'   => 'API key is ' . $apiKey->status . '.',
            ], 401);
        }

        // Check expiration
        if ($apiKey->expires_at && $apiKey->expires_at->isPast()) {
            return response()->json([
                'success' => false,
                'error'   => 'API key has expired.',
            ], 401);
        }

        // Check IP whitelist
        if ($apiKey->allowed_ips && ! empty($apiKey->allowed_ips)) {
            $clientIp = $request->ip();
            if (! in_array($clientIp, $apiKey->allowed_ips, true)) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Request from unauthorized IP address.',
                ], 403);
            }
        }

        // Check daily limit
        if ($apiKey->rate_limit_per_day > 0) {
            $dailyKey   = "api_daily:{$apiKey->id}:" . now()->format('Y-m-d');
            $dailyCount = (int) Cache::get($dailyKey, 0);

            if ($dailyCount >= $apiKey->rate_limit_per_day) {
                return response()->json([
                    'success'  => false,
                    'error'    => 'Daily API limit exceeded.',
                    'limit'    => $apiKey->rate_limit_per_day,
                    'resets_at'=> now()->endOfDay()->toISOString(),
                ], 429);
            }

            Cache::increment($dailyKey, 1);
            Cache::put($dailyKey, Cache::get($dailyKey, 0), now()->endOfDay());
        }

        // Attach API key to request for later use
        $request->attributes->set('api_key', $apiKey);

        // Authenticate the user associated with this key
        Auth::login($apiKey->user);

        // Set rate limit headers
        $response = $next($request);

        return $response->withHeaders([
            'X-RateLimit-Limit'     => $apiKey->rate_limit_per_minute,
            'X-Credits-Remaining'   => $apiKey->user->credit_balance,
            'X-API-Key-Prefix'      => $apiKey->key_prefix,
        ]);
    }

    /**
     * Extract API key from request (header or bearer token)
     */
    private function extractApiKey(Request $request): ?string
    {
        // Method 1: X-API-Key header
        $key = $request->header('X-API-Key');
        if ($key) return $key;

        // Method 2: Authorization Bearer
        $bearer = $request->bearerToken();
        if ($bearer && str_starts_with($bearer, 'ev_')) {
            return $bearer;
        }

        // Method 3: Query parameter (not recommended but supported)
        $queryKey = $request->query('api_key');
        if ($queryKey) return $queryKey;

        return null;
    }

    /**
     * Find API key with caching for performance
     */
    private function findApiKey(string $key): ?ApiKey
    {
        $cacheKey = 'api_key:' . hash('sha256', $key);

        return Cache::remember($cacheKey, 300, function () use ($key) {
            return ApiKey::with('user')
                ->where('key', $key)
                ->where('status', 'active')
                ->first();
        });
    }
}
