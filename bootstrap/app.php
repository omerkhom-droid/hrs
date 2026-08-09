<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsurePlatformAdmin;
use App\Http\Middleware\SetTenantContext;
use App\Http\Middleware\EnsureSystemAdmin;
use App\Http\Middleware\EnsureTenantUser;
use App\Http\Middleware\EnsureActiveSubscription;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->redirectGuestsTo(
            fn (\Illuminate\Http\Request $request) =>
                $request->is('system/*')
                    ? route('system.login')
                    : route('app.login')
        );
        
        $middleware->alias([
            'system.admin' => EnsureSystemAdmin::class,

            'tenant.user' => EnsureTenantUser::class,

            'subscription.active' => EnsureActiveSubscription::class,

            'role' =>\Spatie\Permission\Middleware\RoleMiddleware::class,
            
            'permission' =>\Spatie\Permission\Middleware\PermissionMiddleware::class,

            'role_or_permission' =>\Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
