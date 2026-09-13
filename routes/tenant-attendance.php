<?php

use App\Http\Controllers\Tenant\AttendanceController;
use App\Http\Controllers\Tenant\OvertimeController;
use App\Http\Controllers\Tenant\SelfServiceAttendanceController;
use App\Http\Controllers\Tenant\WorkShiftController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Attendance Management
|--------------------------------------------------------------------------
|
| يستدعى داخل مجموعة app المحمية بواسطة:
| auth, tenant.user, subscription.active
|
*/

Route::prefix('attendance')
    ->name('attendance.')
    ->group(function () {
        Route::prefix('self-service')
            ->name('self-service.')
            ->group(function () {
                Route::get('/', [
                    SelfServiceAttendanceController::class,
                    'index',
                ])->name('index');

                Route::get('/today', [
                    SelfServiceAttendanceController::class,
                    'today',
                ])->name('today');

                Route::get('/history', [
                    SelfServiceAttendanceController::class,
                    'history',
                ])->name('history');

                Route::post('/check-in', [
                    SelfServiceAttendanceController::class,
                    'checkIn',
                ])->middleware('throttle:10,1')
                    ->name('check-in');

                Route::post('/check-out', [
                    SelfServiceAttendanceController::class,
                    'checkOut',
                ])->middleware('throttle:10,1')
                    ->name('check-out');
            });

        Route::prefix('overtime')
            ->name('overtime.')
            ->group(function () {
                Route::get('/', [
                    OvertimeController::class,
                    'index',
                ])->name('index');

                Route::get('/data', [
                    OvertimeController::class,
                    'data',
                ])->name('data');

                Route::get('/options', [
                    OvertimeController::class,
                    'options',
                ])->name('options');

                Route::post('/', [
                    OvertimeController::class,
                    'store',
                ])->name('store');

                Route::post('/bulk-decision', [
                    OvertimeController::class,
                    'bulkDecision',
                ])->name('bulk-decision');

                Route::get('/{overtimeRequest}', [
                    OvertimeController::class,
                    'show',
                ])
                    ->whereNumber('overtimeRequest')
                    ->name('show');

                Route::post('/{overtimeRequest}/approve', [
                    OvertimeController::class,
                    'approve',
                ])
                    ->whereNumber('overtimeRequest')
                    ->name('approve');

                Route::post('/{overtimeRequest}/reject', [
                    OvertimeController::class,
                    'reject',
                ])
                    ->whereNumber('overtimeRequest')
                    ->name('reject');

                Route::post('/{overtimeRequest}/cancel', [
                    OvertimeController::class,
                    'cancel',
                ])
                    ->whereNumber('overtimeRequest')
                    ->name('cancel');
            });

        Route::get('/shifts/data', [WorkShiftController::class, 'data'])
            ->name('shifts.data');

        Route::get('/shifts/options', [WorkShiftController::class, 'options'])
            ->name('shifts.options');

        Route::get(
            '/shifts/assignments/data',
            [WorkShiftController::class, 'assignmentsData']
        )->name('shifts.assignments.data');

        Route::post(
            '/shifts/assignments',
            [WorkShiftController::class, 'assign']
        )->name('shifts.assignments.store');

        Route::post(
            '/shifts/assignments/{assignment}/end',
            [WorkShiftController::class, 'endAssignment']
        )
            ->whereNumber('assignment')
            ->name('shifts.assignments.end');

        Route::put(
            '/policy',
            [WorkShiftController::class, 'updatePolicy']
        )->name('policy.update');

        Route::get('/shifts', [WorkShiftController::class, 'index'])
            ->name('shifts.index');

        Route::post('/shifts', [WorkShiftController::class, 'store'])
            ->name('shifts.store');

        Route::get('/shifts/{shift}', [WorkShiftController::class, 'show'])
            ->whereNumber('shift')
            ->name('shifts.show');

        Route::match(
            ['put', 'patch'],
            '/shifts/{shift}',
            [WorkShiftController::class, 'update']
        )
            ->whereNumber('shift')
            ->name('shifts.update');

        Route::delete(
            '/shifts/{shift}',
            [WorkShiftController::class, 'destroy']
        )
            ->whereNumber('shift')
            ->name('shifts.destroy');

        Route::get('/data', [AttendanceController::class, 'data'])
            ->name('data');

        Route::get('/options', [AttendanceController::class, 'options'])
            ->name('options');

        Route::get('/', [AttendanceController::class, 'index'])
            ->name('index');

        Route::post('/', [AttendanceController::class, 'store'])
            ->name('store');

        Route::post(
            '/bulk-approve',
            [AttendanceController::class, 'bulkApprove']
        )->name('bulk-approve');

        Route::post(
            '/{record}/approve',
            [AttendanceController::class, 'approve']
        )
            ->whereNumber('record')
            ->name('approve');

        Route::post(
            '/{record}/reopen',
            [AttendanceController::class, 'reopen']
        )
            ->whereNumber('record')
            ->name('reopen');

        Route::get('/{record}', [AttendanceController::class, 'show'])
            ->whereNumber('record')
            ->name('show');

        Route::match(
            ['put', 'patch'],
            '/{record}',
            [AttendanceController::class, 'update']
        )
            ->whereNumber('record')
            ->name('update');

        Route::delete(
            '/{record}',
            [AttendanceController::class, 'destroy']
        )
            ->whereNumber('record')
            ->name('destroy');
    });
