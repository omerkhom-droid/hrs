<?php

use App\Http\Controllers\Tenant\EmployeeController;
use App\Http\Controllers\Tenant\EmployeeLoanApprovalController;
use App\Http\Controllers\Tenant\EmployeeLoanController;
use App\Http\Controllers\Tenant\SelfServiceEmployeeLoanController;
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

    /*
    |--------------------------------------------------------------------------
    | إدارة السلف
    |--------------------------------------------------------------------------
    */

    Route::prefix('payroll/employee-loans')
        ->name('payroll.employee-loans.')
        ->controller(EmployeeLoanController::class)
        ->group(function () {
            /*
             * المسارات الثابتة تسبق مسار السلفة.
             */
            Route::get('/data', 'data')
                ->name('data');

            Route::get('/options', 'options')
                ->name('options');

            Route::get('/', 'index')
                ->name('index');

            Route::post('/', 'store')
                ->name('store');

            Route::get('/{employeeLoan:uuid}', 'show')
                ->name('show');

            Route::put('/{employeeLoan:uuid}', 'update')
                ->name('update');

            Route::delete('/{employeeLoan:uuid}', 'destroy')
                ->name('destroy');

            Route::post(
                '/{employeeLoan:uuid}/submit',
                'submit'
            )->name('submit');

            Route::post(
                '/{employeeLoan:uuid}/cancel',
                'cancel'
            )->name('cancel');
        });


    /*
    |--------------------------------------------------------------------------
    | اعتماد السلف
    |--------------------------------------------------------------------------
    */

    Route::prefix('payroll/employee-loan-approvals')
        ->name('payroll.employee-loan-approvals.')
        ->controller(EmployeeLoanApprovalController::class)
        ->group(function () {
            Route::get('/data', 'data')
                ->name('data');

            Route::get('/', 'index')
                ->name('index');

            Route::get(
                '/{employeeLoan:uuid}',
                'show'
            )->name('show');

            Route::post(
                '/{employeeLoan:uuid}/approve',
                'approve'
            )->name('approve');

            Route::post(
                '/{employeeLoan:uuid}/reject',
                'reject'
            )->name('reject');
        });


    /*
    |--------------------------------------------------------------------------
    | الخدمة الذاتية للسلف
    |--------------------------------------------------------------------------
    */

    Route::prefix('self-service/loans')
        ->name('self-service.loans.')
        ->controller(SelfServiceEmployeeLoanController::class)
        ->group(function () {
            /*
             * يجب وضع data وoptions قبل {employeeLoan}.
             */
            Route::get('/data', 'data')
                ->name('data');

            Route::get('/options', 'options')
                ->name('options');

            Route::get('/', 'index')
                ->name('index');

            Route::post('/', 'store')
                ->name('store');

            Route::get(
                '/{employeeLoan:uuid}',
                'show'
            )->name('show');

            Route::put(
                '/{employeeLoan:uuid}',
                'update'
            )->name('update');

            Route::delete(
                '/{employeeLoan:uuid}',
                'destroy'
            )->name('destroy');

            Route::post(
                '/{employeeLoan:uuid}/submit',
                'submit'
            )->name('submit');

            Route::post(
                '/{employeeLoan:uuid}/cancel',
                'cancel'
            )->name('cancel');
        });