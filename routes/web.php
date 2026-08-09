<?php

use App\Http\Controllers\System\AuthController;
use App\Http\Controllers\System\DashboardController;
use App\Http\Controllers\System\TenantController;
use App\Http\Controllers\System\PlanController;
use App\Http\Controllers\System\PlanFeatureController;
use App\Http\Controllers\System\SubscriptionController;
use App\Http\Controllers\System\SubscriptionLifecycleController;

use App\Http\Controllers\Tenant\AuthController as TenantAuthController;
use App\Http\Controllers\Tenant\DashboardController as TenantDashboardController;
use App\Http\Controllers\Tenant\UserController as TenantUserController;
use App\Http\Controllers\Tenant\RoleController as TenantRoleController;

use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Home
|--------------------------------------------------------------------------
*/

Route::get('/', function () {

    if (auth()->check()) {

        if (auth()->user()->is_system_admin) {
            return redirect()
                ->route('system.dashboard');
        }

        if (auth()->user()->tenant_id) {
            return redirect()
                ->route('app.dashboard');
        }
    }

    return redirect()
        ->route('system.login');
});


/*
|--------------------------------------------------------------------------
| System Admin
|--------------------------------------------------------------------------
*/

Route::prefix('system')
    ->name('system.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Authentication
        |--------------------------------------------------------------------------
        */

        Route::get('/login', [
            AuthController::class,
            'showLogin'
        ])->name('login');


        Route::post('/login', [
            AuthController::class,
            'login'
        ])->name('login.submit');


        /*
        |--------------------------------------------------------------------------
        | Protected System Routes
        |--------------------------------------------------------------------------
        */

        Route::middleware([
            'auth',
            'system.admin',
        ])->group(function () {

            /*
            |--------------------------------------------------------------------------
            | Dashboard
            |--------------------------------------------------------------------------
            */

            Route::get('/dashboard', [
                DashboardController::class,
                'index'
            ])->name('dashboard');


            /*
            |--------------------------------------------------------------------------
            | Tenants
            |--------------------------------------------------------------------------
            */

            Route::get('/tenants/data', [
                TenantController::class,
                'data'
            ])->name('tenants.data');


            Route::resource(
                'tenants',
                TenantController::class
            )->except([
                'create',
                'edit',
            ]);


            /*
            |--------------------------------------------------------------------------
            | Plans
            |--------------------------------------------------------------------------
            */

            Route::get('/plans/data', [
                PlanController::class,
                'data'
            ])->name('plans.data');


            /*
             * يجب أن تكون مسارات Features
             * قبل resource plans.
             */

            Route::get('/plans/{plan}/features', [
                PlanFeatureController::class,
                'edit'
            ])->name('plans.features.edit');


            Route::put('/plans/{plan}/features', [
                PlanFeatureController::class,
                'update'
            ])->name('plans.features.update');


            Route::resource(
                'plans',
                PlanController::class
            )->except([
                'create',
                'edit',
            ]);


            /*
            |--------------------------------------------------------------------------
            | Subscriptions
            |--------------------------------------------------------------------------
            */

            Route::get('/subscriptions/data', [
                SubscriptionController::class,
                'data'
            ])->name('subscriptions.data');


            Route::get('/subscriptions', [
                SubscriptionController::class,
                'index'
            ])->name('subscriptions.index');


            Route::post('/subscriptions', [
                SubscriptionController::class,
                'store'
            ])->name('subscriptions.store');


            Route::get('/subscriptions/{subscription}', [
                SubscriptionController::class,
                'show'
            ])->name('subscriptions.show');


            /*
            |--------------------------------------------------------------------------
            | Subscription Lifecycle
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/subscriptions/{subscription}/convert-trial',
                [
                    SubscriptionLifecycleController::class,
                    'convertTrial'
                ]
            )->name(
                'subscriptions.convert-trial'
            );


            Route::post(
                '/subscriptions/{subscription}/renew',
                [
                    SubscriptionLifecycleController::class,
                    'renew'
                ]
            )->name(
                'subscriptions.renew'
            );


            Route::post(
                '/subscriptions/{subscription}/change-plan',
                [
                    SubscriptionLifecycleController::class,
                    'changePlan'
                ]
            )->name(
                'subscriptions.change-plan'
            );


            Route::post(
                '/subscriptions/{subscription}/suspend',
                [
                    SubscriptionLifecycleController::class,
                    'suspend'
                ]
            )->name(
                'subscriptions.suspend'
            );


            Route::post(
                '/subscriptions/{subscription}/resume',
                [
                    SubscriptionLifecycleController::class,
                    'resume'
                ]
            )->name(
                'subscriptions.resume'
            );


            Route::post(
                '/subscriptions/{subscription}/cancel',
                [
                    SubscriptionLifecycleController::class,
                    'cancel'
                ]
            )->name(
                'subscriptions.cancel'
            );


            /*
            |--------------------------------------------------------------------------
            | Logout
            |--------------------------------------------------------------------------
            */

            Route::post('/logout', [
                AuthController::class,
                'logout'
            ])->name('logout');
        });
    });


/*
|--------------------------------------------------------------------------
| Tenant Application
|--------------------------------------------------------------------------
*/

Route::prefix('app')
    ->name('app.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Authentication
        |--------------------------------------------------------------------------
        */

        Route::get('/login', [
            TenantAuthController::class,
            'showLogin'
        ])->name('login');


        Route::post('/login', [
            TenantAuthController::class,
            'login'
        ])->name('login.submit');


        /*
        |--------------------------------------------------------------------------
        | Authenticated Tenant User
        |--------------------------------------------------------------------------
        */

        Route::middleware([
            'auth',
            'tenant.user',
        ])->group(function () {

            /*
             * Logout يجب أن يبقى متاحًا
             * حتى لو انتهى الاشتراك.
             */

            Route::post('/logout', [
                TenantAuthController::class,
                'logout'
            ])->name('logout');


            /*
            |--------------------------------------------------------------------------
            | Active Subscription
            |--------------------------------------------------------------------------
            */

            Route::middleware([
                'subscription.active',
            ])->group(function () {

                /*
                |--------------------------------------------------------------------------
                | Dashboard
                |--------------------------------------------------------------------------
                */

                Route::get('/dashboard', [
                    TenantDashboardController::class,
                    'index'
                ])->name('dashboard');


                /*
                |--------------------------------------------------------------------------
                | Tenant Users
                |--------------------------------------------------------------------------
                */

                Route::prefix('users')
                    ->name('users.')
                    ->group(function () {

                        Route::get('/', [
                            TenantUserController::class,
                            'index'
                        ])
                            ->middleware(
                                'permission:users.view'
                            )
                            ->name('index');


                        Route::post('/', [
                            TenantUserController::class,
                            'store'
                        ])
                            ->middleware(
                                'permission:users.create'
                            )
                            ->name('store');


                        Route::get('/{user}', [
                            TenantUserController::class,
                            'show'
                        ])
                            ->whereNumber('user')
                            ->middleware(
                                'permission:users.view'
                            )
                            ->name('show');


                        Route::put('/{user}', [
                            TenantUserController::class,
                            'update'
                        ])
                            ->whereNumber('user')
                            ->middleware(
                                'permission:users.update'
                            )
                            ->name('update');


                        Route::patch('/{user}/status', [
                            TenantUserController::class,
                            'status'
                        ])
                            ->whereNumber('user')
                            ->middleware(
                                'permission:users.deactivate'
                            )
                            ->name('status');
                    });


                    Route::prefix('roles')
                        ->name('roles.')
                        ->group(function () {

                            Route::get('/', [
                                TenantRoleController::class,
                                'index'
                            ])
                                ->middleware('permission:roles.view')
                                ->name('index');


                            Route::get('/{role}', [
                                TenantRoleController::class,
                                'show'
                            ])
                                ->whereNumber('role')
                                ->middleware('permission:roles.view')
                                ->name('show');


                            Route::post('/', [
                                TenantRoleController::class,
                                'store'
                            ])
                                ->middleware('permission:roles.manage')
                                ->name('store');


                            Route::put('/{role}', [
                                TenantRoleController::class,
                                'update'
                            ])
                                ->whereNumber('role')
                                ->middleware('permission:roles.manage')
                                ->name('update');


                            Route::delete('/{role}', [
                                TenantRoleController::class,
                                'destroy'
                            ])
                                ->whereNumber('role')
                                ->middleware('permission:roles.manage')
                                ->name('destroy');
                        });
                        
            });
        });
    });