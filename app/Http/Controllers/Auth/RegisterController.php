<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function showForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email:filter|unique:users,email|max:255',
            'password' => ['required', Password::min(8)->letters()->numbers(), 'confirmed'],
            'company'  => 'nullable|string|max:100',
        ]);

        $user = User::create([
            'name'                     => $validated['name'],
            'email'                    => strtolower($validated['email']),
            'password'                 => Hash::make($validated['password']),
            'company'                  => $validated['company'] ?? null,
            'status'                   => 'active',
            'role'                     => 'user',
            'credit_balance'           => 100,
            'email_verification_token' => Str::random(64),
        ]);

        // Create default API key
        $apiKeyValue = 'ev_' . Str::random(56);
        ApiKey::create([
            'user_id'               => $user->id,
            'name'                  => 'Default API Key',
            'key'                   => $apiKeyValue,
            'key_prefix'            => substr($apiKeyValue, 0, 8),
            'status'                => 'active',
            'rate_limit_per_minute' => 60,
            'rate_limit_per_day'    => 1000,
        ]);

        // Log in immediately
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('user.dashboard')
            ->with('success', 'Welcome! You have 100 free credits to get started.');
    }
}
