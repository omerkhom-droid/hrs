<?php

use App\Http\Controllers\System\AuthController;
use App\Http\Controllers\System\DashboardController;
use App\Http\Controllers\System\TenantController;
use App\Http\Controllers\System\PlanController;
use App\Http\Controllers\System\PlanFeatureController;
use App\Http\Controllers\System\SubscriptionController;
use App\Http\Controllers\System\SubscriptionLifecycleController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Home
|--------------------------------------------------------------------------
*/

Route::get('/', function () {

    if (
        auth()->check() &&
        auth()->user()->is_system_admin
    ) {
        return redirect()->route('system.dashboard');
    }

    return redirect()->route('system.login');
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
        | Protected System Admin Routes
        |--------------------------------------------------------------------------
        */

        Route::middleware([
            'auth',
            'system.admin'
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

            // مهم: data قبل resource
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
            | features
            |--------------------------------------------------------------------------
            */
            Route::get('/plans/{plan}/features', [
                PlanFeatureController::class,
                'edit'
            ])->name('plans.features.edit');

            Route::put('/plans/{plan}/features', [
                PlanFeatureController::class,
                'update'
            ])->name('plans.features.update');


            /*
            |--------------------------------------------------------------------------
            | plans
            |--------------------------------------------------------------------------
            */
            Route::get('/plans/data', [
                PlanController::class,
                'data'
            ])->name('plans.data');

            Route::resource(
                'plans',
                PlanController::class
            )->except([
                'create',
                'edit',
            ]);


            /*
            |--------------------------------------------------------------------------
            | subscriptions
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
            | subscriptions changes
            |--------------------------------------------------------------------------
            */
            Route::post(
                '/subscriptions/{subscription}/convert-trial',
                [SubscriptionLifecycleController::class, 'convertTrial']
            )->name('subscriptions.convert-trial');


            Route::post(
                '/subscriptions/{subscription}/renew',
                [SubscriptionLifecycleController::class, 'renew']
            )->name('subscriptions.renew');


            Route::post(
                '/subscriptions/{subscription}/change-plan',
                [SubscriptionLifecycleController::class, 'changePlan']
            )->name('subscriptions.change-plan');


            Route::post(
                '/subscriptions/{subscription}/suspend',
                [SubscriptionLifecycleController::class, 'suspend']
            )->name('subscriptions.suspend');


            Route::post(
                '/subscriptions/{subscription}/resume',
                [SubscriptionLifecycleController::class, 'resume']
            )->name('subscriptions.resume');


            Route::post(
                '/subscriptions/{subscription}/cancel',
                [SubscriptionLifecycleController::class, 'cancel']
            )->name('subscriptions.cancel');
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