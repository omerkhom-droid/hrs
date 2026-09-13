<?php

use App\Http\Controllers\Api\V1\Mobile\MobileAuthController;
use App\Http\Controllers\Api\V1\Mobile\MobileAttendanceController;
use App\Http\Controllers\Api\V1\Mobile\MobileLeaveController;
use App\Http\Controllers\Api\V1\Mobile\MobileEmployeeLoanController;
use App\Http\Controllers\Api\V1\Mobile\MobilePayslipController;
use App\Http\Controllers\Api\V1\Mobile\MobileNotificationController;

use Illuminate\Support\Facades\Route;

Route::prefix('v1/mobile')
    ->name('api.v1.mobile.')
    ->group(function () {
        Route::post(
            '/login',
            [MobileAuthController::class, 'login']
        )
            ->middleware('throttle:5,1')
            ->name('login');

        Route::middleware('auth:sanctum','permission.tenant',)
            ->group(function () {
                Route::get(
                    '/me',
                    [MobileAuthController::class, 'me']
                )->name('me');

                Route::prefix('attendance')
                    ->name('attendance.')
                    ->group(function () {
                        Route::get(
                            '/today',
                            [
                                MobileAttendanceController::class,
                                'today',
                            ]
                        )->name('today');
                        
                        Route::get(
                            '/history',
                            [MobileAttendanceController::class, 'history']
                        )->name('history');

                        Route::post(
                            '/check-in',
                            [
                                MobileAttendanceController::class,
                                'checkIn',
                            ]
                        )
                            ->middleware('throttle:10,1')
                            ->name('check-in');

                        Route::post(
                            '/check-out',
                            [
                                MobileAttendanceController::class,
                                'checkOut',
                            ]
                        )
                            ->middleware('throttle:10,1')
                            ->name('check-out');
                    });

                Route::prefix('leaves')
                    ->name('leaves.')
                    ->controller(MobileLeaveController::class)
                    ->group(function () {
                        /*
                         * يجب وضع المسارات الثابتة قبل {leaveRequest}.
                         */

                        Route::get(
                            '/options',
                            'options'
                        )->name('options');

                        Route::get(
                            '/balances',
                            'balances'
                        )->name('balances');

                        Route::get(
                            '/',
                            'index'
                        )->name('index');

                        Route::post(
                            '/',
                            'store'
                        )->name('store');

                        Route::get(
                            '/{leaveRequest:uuid}',
                            'show'
                        )->name('show');

                        Route::put(
                            '/{leaveRequest:uuid}',
                            'update'
                        )->name('update');

                        Route::delete(
                            '/{leaveRequest:uuid}',
                            'destroy'
                        )->name('destroy');

                        Route::post(
                            '/{leaveRequest:uuid}/submit',
                            'submit'
                        )->name('submit');

                        Route::post(
                            '/{leaveRequest:uuid}/cancel',
                            'cancel'
                        )->name('cancel');

                        Route::get(
                            '/{leaveRequest:uuid}/attachment',
                            'attachment'
                        )->name('attachment');
                    });
                    
                Route::prefix('loans')
                    ->name('loans.')
                    ->controller(
                        MobileEmployeeLoanController::class
                    )
                    ->group(function () {
                        /*
                         * المسارات الثابتة قبل مسار UUID.
                         */
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
                    
                Route::prefix('payslips')
                    ->name('payslips.')
                    ->controller(
                        MobilePayslipController::class
                    )
                    ->group(function () {
                        Route::get(
                            '/',
                            'index'
                        )->name('index');

                        Route::get(
                            '/{payrollRunItem:uuid}',
                            'show'
                        )->name('show');
                    });


                Route::prefix('notifications')
                    ->name('notifications.')
                    ->controller(
                        MobileNotificationController::class
                    )
                    ->group(function () {
                        Route::get(
                            '/unread-count',
                            'unreadCount'
                        )->name('unread-count');

                        Route::post(
                            '/mark-all-read',
                            'markAllAsRead'
                        )->name('mark-all-read');

                        Route::get(
                            '/',
                            'index'
                        )->name('index');

                        Route::post(
                            '/{notification}/read',
                            'markAsRead'
                        )->name('read');

                        Route::delete(
                            '/{notification}',
                            'destroy'
                        )->name('destroy');
                    });


                Route::post(
                    '/logout',
                    [MobileAuthController::class, 'logout']
                )->name('logout');
            });
    });