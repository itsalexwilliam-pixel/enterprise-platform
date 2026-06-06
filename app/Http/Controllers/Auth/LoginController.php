<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\User;

class LoginController extends Controller
{
    public function showForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $user = Auth::user();
            $user->update(['last_login_at' => now(), 'last_login_ip' => $request->ip()]);

            return redirect()->intended(route('user.dashboard'))
                ->with('success', 'Welcome back, ' . $user->name . '!');
        }

        return back()->withErrors(['email' => 'Invalid email or password.'])->withInput();
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('success', 'Logged out successfully.');
    }

    public function showForgotForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', strtolower($request->email))->first();
        if ($user) {
            $token = Str::random(64);
            $user->update([
                'password_reset_token'      => Hash::make($token),
                'password_reset_expires_at' => now()->addHour(),
            ]);
            Mail::to($user->email)->queue(new \App\Mail\PasswordReset($user, $token));
        }
        return back()->with('success', 'If that email exists, a reset link was sent.');
    }

    public function showResetForm(string $token)
    {
        return view('auth.reset-password', ['token' => $token]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'token'    => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);
        $user = User::where('email', strtolower($request->email))
            ->where('password_reset_expires_at', '>', now())->first();
        if (! $user || ! Hash::check($request->token, $user->password_reset_token)) {
            return back()->withErrors(['email' => 'Invalid or expired token.']);
        }
        $user->update([
            'password'                  => Hash::make($request->password),
            'password_reset_token'      => null,
            'password_reset_expires_at' => null,
        ]);
        return redirect()->route('login')->with('success', 'Password reset! Please login.');
    }
}
