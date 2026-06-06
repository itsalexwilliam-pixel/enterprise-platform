<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->status !== 'active') {
            $message = match ($user->status) {
                'suspended' => 'Your account has been suspended. Please contact support.',
                'banned'    => 'Your account has been banned.',
                default     => 'Your account is inactive.',
            };

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'error' => $message], 403);
            }

            auth()->logout();
            return redirect()->route('login')->withErrors(['email' => $message]);
        }

        return $next($request);
    }
}
