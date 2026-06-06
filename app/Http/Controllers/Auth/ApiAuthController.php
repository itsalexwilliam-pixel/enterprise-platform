<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * ============================================================
 * API Authentication Controller
 * Handles: Register, Login, Logout, Password Reset, Email Verify
 * ============================================================
 */
class ApiAuthController extends Controller
{
    /**
     * POST /api/v1/auth/register
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email:filter|unique:users,email|max:255',
            'password' => ['required', Password::min(8)->letters()->numbers(), 'confirmed'],
            'company'  => 'nullable|string|max:100',
            'country'  => 'nullable|string|size:2',
        ]);

        $user = User::create([
            'name'                       => $validated['name'],
            'email'                      => strtolower($validated['email']),
            'password'                   => Hash::make($validated['password']),
            'company'                    => $validated['company'] ?? null,
            'country'                    => $validated['country'] ?? null,
            'status'                     => 'active',
            'role'                       => 'user',
            'credit_balance'             => 100, // Free credits on signup
            'email_verification_token'   => Str::random(64),
        ]);

        // Send verification email
        Mail::to($user->email)->queue(new \App\Mail\VerifyEmail($user));

        // Create default API key
        $apiKeyValue = ApiKey::generateKey();
        $apiKey = ApiKey::create([
            'user_id'               => $user->id,
            'name'                  => 'Default API Key',
            'key'                   => $apiKeyValue,
            'key_prefix'            => substr($apiKeyValue, 0, 8),
            'status'                => 'active',
            'rate_limit_per_minute' => 30,
            'rate_limit_per_day'    => 1000,
        ]);

        // Create Sanctum token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success'   => true,
            'message'   => 'Registration successful! Please verify your email.',
            'user'      => [
                'id'             => $user->id,
                'name'           => $user->name,
                'email'          => $user->email,
                'credit_balance' => $user->credit_balance,
                'role'           => $user->role,
            ],
            'token'     => $token,
            'api_key'   => $apiKeyValue,  // shown only once
        ], 201);
    }

    /**
     * POST /api/v1/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'    => 'required|email|max:255',
            'password' => 'required|string|min:8',
        ]);

        $user = User::where('email', strtolower($validated['email']))->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'error'   => 'Invalid email or password.',
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'error'   => "Account is {$user->status}. Please contact support.",
            ], 403);
        }

        // Update last login info
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        // Revoke old tokens
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'user'    => [
                'id'             => $user->id,
                'name'           => $user->name,
                'email'          => $user->email,
                'credit_balance' => $user->credit_balance,
                'role'           => $user->role,
                'two_factor'     => $user->two_factor_enabled,
            ],
            'token'   => $token,
        ]);
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * POST /api/v1/auth/forgot-password
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $user = User::where('email', strtolower($request->email))->first();

        if ($user) {
            $token = Str::random(64);
            $user->update([
                'password_reset_token'      => Hash::make($token),
                'password_reset_expires_at' => now()->addHour(),
            ]);

            Mail::to($user->email)->queue(new \App\Mail\PasswordReset($user, $token));
        }

        // Always return success (security: don't reveal if email exists)
        return response()->json([
            'success' => true,
            'message' => 'If this email exists, a reset link has been sent.',
        ]);
    }

    /**
     * POST /api/v1/auth/reset-password
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'    => 'required|email',
            'token'    => 'required|string',
            'password' => ['required', Password::min(8)->letters()->numbers(), 'confirmed'],
        ]);

        $user = User::where('email', strtolower($validated['email']))
            ->where('password_reset_expires_at', '>', now())
            ->first();

        if (! $user || ! Hash::check($validated['token'], $user->password_reset_token)) {
            return response()->json([
                'success' => false,
                'error'   => 'Invalid or expired reset token.',
            ], 422);
        }

        $user->update([
            'password'                  => Hash::make($validated['password']),
            'password_reset_token'      => null,
            'password_reset_expires_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully. You can now login.',
        ]);
    }

    /**
     * POST /api/v1/auth/verify-email
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $request->validate(['token' => 'required|string|size:64']);

        $user = User::where('email_verification_token', $request->token)->first();

        if (! $user) {
            return response()->json(['success' => false, 'error' => 'Invalid token.'], 422);
        }

        $user->update([
            'email_verified_at'          => now(),
            'email_verification_token'   => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully!',
        ]);
    }
}
