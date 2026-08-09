<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureTenantUser
{
    public function handle(
        Request $request,
        Closure $next
    ) {

        $user = $request->user();


        if (!$user) {

            return redirect()
                ->route('app.login');
        }


        /*
         * System Admin لا يدخل بوابة الشركة.
         */
        if ($user->is_system_admin) {

            return redirect()
                ->route('system.dashboard');
        }


        if (!$user->tenant_id || !$user->tenant) {

            Auth::logout();

            $request
                ->session()
                ->invalidate();

            $request
                ->session()
                ->regenerateToken();


            return redirect()
                ->route('app.login')
                ->withErrors([
                    'email' =>
                        'المستخدم غير مرتبط بشركة.',
                ]);
        }

        setPermissionsTeamId($user->tenant_id);

        return $next($request);
    }
}