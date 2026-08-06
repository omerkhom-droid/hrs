<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AuthController extends Controller
{
    public function showLogin(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            if (Auth::user()->is_system_admin && Auth::user()->is_active) {
                return redirect()->route('system.dashboard');
            }

            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return view('system.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $email = Str::lower(trim($credentials['email']));

        $throttleKey = Str::lower(
            $email.'|'.$request->ip()
        );

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => "محاولات كثيرة. حاول مرة أخرى بعد {$seconds} ثانية.",
            ]);
        }

        $success = Auth::attempt([
            'email' => $email,
            'password' => $credentials['password'],
            'is_system_admin' => true,
            'is_active' => true,
        ], $request->boolean('remember'));

        if (!$success) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'email' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        $request->session()->regenerate();

        Auth::user()->forceFill([
            'last_login_at' => now(),
        ])->save();

        return redirect()->intended(
            route('system.dashboard')
        );
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('system.login');
    }
}