<?php

use App\Http\Controllers\Tenant\HolidayController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Holidays
|--------------------------------------------------------------------------
|
| هذا الملف يتم استدعاؤه من داخل مجموعة مسارات /app المحمية.
| لذلك لا نضيف prefix('app') هنا.
|
*/

Route::prefix('holidays')
    ->name('holidays.')
    ->controller(
        HolidayController::class
    )
    ->group(function () {

        /*
         * يجب وضع المسارات الثابتة قبل {holiday}.
         */

        Route::get(
            '/data',
            'data'
        )->name('data');


        Route::get(
            '/options',
            'options'
        )->name('options');


        Route::get(
            '/',
            'index'
        )->name('index');


        Route::post(
            '/',
            'store'
        )->name('store');


        Route::get(
            '/{holiday}',
            'show'
        )
            ->whereNumber('holiday')
            ->name('show');


        Route::match(
            [
                'put',
                'patch',
            ],
            '/{holiday}',
            'update'
        )
            ->whereNumber('holiday')
            ->name('update');


        Route::delete(
            '/{holiday}',
            'destroy'
        )
            ->whereNumber('holiday')
            ->name('destroy');
    });