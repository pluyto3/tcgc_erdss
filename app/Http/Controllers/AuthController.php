<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request)
    {
        $key = 'login:'.hash('sha256', $request->input('email').'|'.$request->ip());
        $ipKey = 'login-ip:'.hash('sha256', $request->ip());
        if (RateLimiter::tooManyAttempts($key, 5) || RateLimiter::tooManyAttempts($ipKey, 30)) {
            throw ValidationException::withMessages(['email' => 'Too many sign-in attempts. Please try again in a few minutes.']);
        }
        if (! Auth::attempt([...$request->validated(), 'status' => 'active'])) {
            RateLimiter::hit($key, 300);
            RateLimiter::hit($ipKey, 300);
            throw ValidationException::withMessages(['email' => 'The email or password is incorrect.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->session()->put('last_activity', time());
        $request->user()->forceFill(['last_login_at' => now()])->save();
        audit(db(), Auth::id(), 'login', 'user', Auth::id(), 'Successful sign in');

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request)
    {
        audit(db(), Auth::id(), 'logout', 'user', Auth::id(), 'Signed out');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
