<?php

use App\Http\Controllers\Tenant\LeaveApprovalController;
use App\Http\Controllers\Tenant\LeaveRequestController;
use App\Http\Controllers\Tenant\LeaveTypeController;
use App\Http\Controllers\Tenant\SelfServiceLeaveController;
use App\Http\Controllers\Tenant\LeaveBalanceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| إعدادات أنواع الإجازات
|--------------------------------------------------------------------------
*/

Route::prefix('leave-types')
->name('leave-types.')
->group(function () {

    Route::get(
        '/',
        [
            LeaveTypeController::class,
            'index',
        ]
    )->name('index');

    /*
     * يجب أن يكون data قبل {leaveType}.
     */
    Route::get(
        '/data',
        [
            LeaveTypeController::class,
            'data',
        ]
    )->name('data');

    Route::post(
        '/',
        [
            LeaveTypeController::class,
            'store',
        ]
    )->name('store');

    Route::get(
        '/{leaveType}',
        [
            LeaveTypeController::class,
            'show',
        ]
    )->name('show');

    Route::put(
        '/{leaveType}',
        [
            LeaveTypeController::class,
            'update',
        ]
    )->name('update');

    Route::delete(
        '/{leaveType}',
        [
            LeaveTypeController::class,
            'destroy',
        ]
    )->name('destroy');
});

/*
|--------------------------------------------------------------------------
| إدارة طلبات الإجازات
|--------------------------------------------------------------------------
*/

Route::prefix('leaves/requests')
->name('leaves.requests.')
->group(function () {

    Route::get(
        '/',
        [
            LeaveRequestController::class,
            'index',
        ]
    )->name('index');

    /*
     * المسارات الثابتة يجب أن تسبق {leaveRequest}.
     */
    Route::get(
        '/data',
        [
            LeaveRequestController::class,
            'data',
        ]
    )->name('data');

    Route::get(
        '/options',
        [
            LeaveRequestController::class,
            'options',
        ]
    )->name('options');

    Route::post(
        '/',
        [
            LeaveRequestController::class,
            'store',
        ]
    )->name('store');

    Route::get(
        '/{leaveRequest}',
        [
            LeaveRequestController::class,
            'show',
        ]
    )->name('show');

    Route::put(
        '/{leaveRequest}',
        [
            LeaveRequestController::class,
            'update',
        ]
    )->name('update');

    Route::delete(
        '/{leaveRequest}',
        [
            LeaveRequestController::class,
            'destroy',
        ]
    )->name('destroy');

    Route::post(
        '/{leaveRequest}/submit',
        [
            LeaveRequestController::class,
            'submit',
        ]
    )->name('submit');

    Route::post(
        '/{leaveRequest}/cancel',
        [
            LeaveRequestController::class,
            'cancel',
        ]
    )->name('cancel');

    Route::get(
        '/{leaveRequest}/attachment',
        [
            LeaveRequestController::class,
            'attachment',
        ]
    )->name('attachment');
});

/*
|--------------------------------------------------------------------------
| اعتماد الإجازات
|--------------------------------------------------------------------------
*/

Route::prefix('leaves/approvals')
->name('leaves.approvals.')
->group(function () {

    Route::get(
        '/',
        [
            LeaveApprovalController::class,
            'index',
        ]
    )->name('index');

    Route::get(
        '/data',
        [
            LeaveApprovalController::class,
            'data',
        ]
    )->name('data');

    /*
     * المسارات الجماعية قبل مسار رقم الطلب.
     */
    Route::post(
        '/bulk-approve',
        [
            LeaveApprovalController::class,
            'bulkApprove',
        ]
    )->name('bulk-approve');

    Route::post(
        '/bulk-reject',
        [
            LeaveApprovalController::class,
            'bulkReject',
        ]
    )->name('bulk-reject');

    Route::post(
        '/{leaveRequest}/approve',
        [
            LeaveApprovalController::class,
            'approve',
        ]
    )->name('approve');

    Route::post(
        '/{leaveRequest}/reject',
        [
            LeaveApprovalController::class,
            'reject',
        ]
    )->name('reject');
});

/*
|--------------------------------------------------------------------------
| الخدمة الذاتية للموظف
|--------------------------------------------------------------------------
*/

Route::prefix('self-service/leave')
->name('self-service.leave.')
->group(function () {

    Route::get(
        '/',
        [
            SelfServiceLeaveController::class,
            'index',
        ]
    )->name('index');

    Route::get(
        '/data',
        [
            SelfServiceLeaveController::class,
            'data',
        ]
    )->name('data');

    Route::get(
        '/options',
        [
            SelfServiceLeaveController::class,
            'options',
        ]
    )->name('options');

    Route::post(
        '/',
        [
            SelfServiceLeaveController::class,
            'store',
        ]
    )->name('store');

    Route::get(
        '/{leaveRequest}',
        [
            SelfServiceLeaveController::class,
            'show',
        ]
    )->name('show');

    Route::put(
        '/{leaveRequest}',
        [
            SelfServiceLeaveController::class,
            'update',
        ]
    )->name('update');

    Route::delete(
        '/{leaveRequest}',
        [
            SelfServiceLeaveController::class,
            'destroy',
        ]
    )->name('destroy');

    Route::post(
        '/{leaveRequest}/submit',
        [
            SelfServiceLeaveController::class,
            'submit',
        ]
    )->name('submit');

    Route::post(
        '/{leaveRequest}/cancel',
        [
            SelfServiceLeaveController::class,
            'cancel',
        ]
    )->name('cancel');

    Route::get(
        '/{leaveRequest}/attachment',
        [
            SelfServiceLeaveController::class,
            'attachment',
        ]
    )->name('attachment');
});


/*
|--------------------------------------------------------------------------
| Leave Balances
|--------------------------------------------------------------------------
*/

Route::prefix('leave-balances')
    ->name('leave-balances.')
    ->controller(
        LeaveBalanceController::class
    )
    ->group(function () {

        /*
         * المسارات الثابتة يجب أن تكون
         * قبل مسار {employee}.
         */

        Route::get(
            '/data',
            'data'
        )->name('data');


        Route::get(
            '/options',
            'options'
        )->name('options');


        /*
         * تسوية أرصدة عدة موظفين.
         */
        Route::post(
            '/bulk-adjustment',
            'bulkStore'
        )->name('bulk-store');


        /*
         * إقفال وترحيل أرصدة الإجازات.
         */
        Route::post(
            '/carry-forward',
            'carryForwardStore'
        )->name('carry-forward');
        
        /*
         * صفحة إدارة الأرصدة.
         */
        Route::get(
            '/',
            'index'
        )->name('index');


        /*
         * تسوية رصيد موظف واحد.
         */
        Route::post(
            '/',
            'store'
        )->name('store');


        /*
         * سجل حركات رصيد الموظف.
         */
        Route::get(
            '/employees/{employee}/history',
            'history'
        )
            ->whereNumber('employee')
            ->name('history');


        /*
         * عرض أرصدة موظف.
         */
        Route::get(
            '/{employee}',
            'show'
        )
            ->whereNumber('employee')
            ->name('show');
    });