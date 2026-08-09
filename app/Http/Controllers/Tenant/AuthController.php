<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        if (Auth::check()) {

            if ($request->user()->is_system_admin) {

                return redirect()
                    ->route('system.dashboard');
            }


            if ($request->user()->tenant_id) {

                return redirect()
                    ->route('app.dashboard');
            }


            Auth::logout();
        }


        return view(
            'tenant.auth.login'
        );
    }


    public function login(Request $request)
    {
        $credentials =
            $request->validate([
                'email' => [
                    'required',
                    'email',
                ],

                'password' => [
                    'required',
                    'string',
                ],

                'remember' => [
                    'nullable',
                    'boolean',
                ],
            ]);


        $email =
            strtolower(
                trim($credentials['email'])
            );


        $key =
            'tenant-login|'
            . $email
            . '|'
            . $request->ip();


        if (
            RateLimiter::tooManyAttempts(
                $key,
                5
            )
        ) {

            throw ValidationException::withMessages([
                'email' =>
                    'محاولات كثيرة. حاول مرة أخرى بعد دقيقة.',
            ]);
        }


        $success = Auth::attempt(
            [
                'email' => $email,
                'password' =>
                    $credentials['password'],

                'is_active' => true,

                'is_system_admin' => false,
            ],
            (bool) ($credentials['remember'] ?? false)
        );


        if (!$success) {

            RateLimiter::hit(
                $key,
                60
            );


            throw ValidationException::withMessages([
                'email' =>
                    'البريد الإلكتروني أو كلمة المرور غير صحيحة.',
            ]);
        }


        /*
         * حماية إضافية:
         * المستخدم يجب أن ينتمي إلى Tenant.
         */
        if (!$request->user()->tenant_id) {

            Auth::logout();

            throw ValidationException::withMessages([
                'email' =>
                    'المستخدم غير مرتبط بشركة.',
            ]);
        }


        RateLimiter::clear($key);


        $request
            ->session()
            ->regenerate();


        $request
            ->user()
            ->forceFill([
                'last_login_at' => now(),
            ])
            ->save();


        return redirect()
            ->intended(
                route('app.dashboard')
            );
    }


    public function logout(Request $request)
    {
        Auth::logout();

        $request
            ->session()
            ->invalidate();

        $request
            ->session()
            ->regenerateToken();


        return redirect()
            ->route('app.login');
    }
}