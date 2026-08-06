<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSystemAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            !$user ||
            !$user->is_system_admin ||
            !$user->is_active
        ) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('system.login')
                ->withErrors([
                    'email' => 'غير مصرح لك بالدخول إلى إدارة النظام.',
                ]);
        }

        return $next($request);
    }
}