<?php

use App\Http\Controllers\Tenant\EmployeeContractController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Employee Contracts
|--------------------------------------------------------------------------
|
| يستدعى هذا الملف داخل مجموعة app المحمية بواسطة:
| auth, tenant.user, subscription.active
|
*/

Route::prefix('contracts')
    ->name('contracts.')
    ->controller(EmployeeContractController::class)
    ->group(function () {
        Route::get('/data', 'data')
            ->name('data');

        Route::get('/options', 'options')
            ->name('options');

        Route::get('/', 'index')
            ->name('index');

        Route::post('/', 'store')
            ->name('store');

        Route::post('/{contract}/activate', 'activate')
            ->whereNumber('contract')
            ->name('activate');

        Route::post('/{contract}/suspend', 'suspend')
            ->whereNumber('contract')
            ->name('suspend');

        Route::post('/{contract}/resume', 'resume')
            ->whereNumber('contract')
            ->name('resume');

        Route::post('/{contract}/terminate', 'terminate')
            ->whereNumber('contract')
            ->name('terminate');

        Route::post('/{contract}/cancel', 'cancel')
            ->whereNumber('contract')
            ->name('cancel');

        Route::post('/{contract}/restore', 'restore')
            ->whereNumber('contract')
            ->withTrashed()
            ->name('restore');

        Route::get('/{contract}', 'show')
            ->whereNumber('contract')
            ->withTrashed()
            ->name('show');

        Route::match(
            ['put', 'patch'],
            '/{contract}',
            'update'
        )
            ->whereNumber('contract')
            ->name('update');

        Route::delete('/{contract}', 'destroy')
            ->whereNumber('contract')
            ->name('destroy');
    });
