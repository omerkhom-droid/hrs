<?php

use App\Http\Controllers\Tenant\EmployeeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Employee Management
|--------------------------------------------------------------------------
|
| يجب استدعاء هذا الملف من داخل مجموعة مسارات الشركة المحمية بواسطة:
| auth, tenant.user, subscription.active
|
*/

Route::prefix('employees')
    ->name('employees.')
    ->controller(EmployeeController::class)
    ->group(function () {

        /*
         * توضع المسارات الثابتة قبل {employee}
         * حتى لا يعتبر Laravel كلمة data أو options رقم موظف.
         */
        Route::get('/data', 'data')
            ->name('data');

        Route::get('/options', 'options')
            ->name('options');

        Route::get('/', 'index')
            ->name('index');

        Route::post('/', 'store')
            ->name('store');

        Route::post('/{employee}/restore', 'restore')
            ->whereNumber('employee')
            ->name('restore');

        Route::get('/{employee}/photo', 'photo')
            ->whereNumber('employee')
            ->withTrashed()
            ->name('photo');
            
        Route::get('/{employee}', 'show')
            ->whereNumber('employee')
            ->name('show');

        Route::match(
            ['put', 'patch'],
            '/{employee}',
            'update'
        )
            ->whereNumber('employee')
            ->name('update');

        Route::delete('/{employee}', 'destroy')
            ->whereNumber('employee')
            ->name('destroy');
    });