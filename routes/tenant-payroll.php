<?php

use App\Http\Controllers\Tenant\EmployeeBankAccountController;
use App\Http\Controllers\Tenant\PayrollSettingController;
use App\Http\Controllers\Tenant\PayrollPaymentBatchController;

use App\Http\Controllers\Tenant\PayrollReportController;
use App\Http\Controllers\Tenant\SelfServicePayslipController;
use App\Http\Controllers\Tenant\PayrollPayslipController;
use App\Http\Controllers\Tenant\PayrollAdjustmentController;
use App\Http\Controllers\Tenant\EmployeeSalaryStructureController;
use App\Http\Controllers\Tenant\PayrollPeriodController;
use App\Http\Controllers\Tenant\PayrollRunController;
use App\Http\Controllers\Tenant\SalaryComponentController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| مكونات الرواتب
|--------------------------------------------------------------------------
*/

Route::prefix('payroll/salary-components')
    ->name('payroll.salary-components.')
    ->controller(
        SalaryComponentController::class
    )
    ->group(function () {

        /*
         * المسارات الثابتة يجب أن تكون
         * قبل مسار {salaryComponent}.
         */

        Route::get('/data', 'data')
            ->name('data');

        Route::get('/options', 'options')
            ->name('options');

        Route::get('/', 'index')
            ->name('index');

        Route::post('/', 'store')
            ->name('store');

        Route::patch(
            '/{salaryComponent}/restore',
            [SalaryComponentController::class, 'restore']
        )
            ->withTrashed()
            ->name('restore');
            
        Route::get(
            '/{salaryComponent}',
            'show'
        )
            ->whereNumber(
                'salaryComponent'
            )
            ->name('show');

        Route::match(
            ['put', 'patch'],
            '/{salaryComponent}',
            'update'
        )
            ->whereNumber(
                'salaryComponent'
            )
            ->name('update');

        Route::delete(
            '/{salaryComponent}',
            'destroy'
        )
            ->whereNumber(
                'salaryComponent'
            )
            ->name('destroy');
    });


/*
|--------------------------------------------------------------------------
| هياكل رواتب الموظفين
|--------------------------------------------------------------------------
*/

Route::prefix('payroll/salary-structures')
    ->name('payroll.salary-structures.')
    ->controller(
        EmployeeSalaryStructureController::class
    )
    ->group(function () {

        /*
         * المسارات الثابتة قبل
         * {salaryStructure}.
         */

        Route::get('/data', 'data')
            ->name('data');

        Route::get('/options', 'options')
            ->name('options');

        Route::get('/', 'index')
            ->name('index');

        Route::post('/', 'store')
            ->name('store');


        /*
         * عمليات هيكل الراتب.
         */

        Route::post(
            '/{salaryStructure}/recalculate',
            'recalculate'
        )
            ->whereNumber(
                'salaryStructure'
            )
            ->name('recalculate');

        Route::post(
            '/{salaryStructure}/activate',
            'activate'
        )
            ->whereNumber(
                'salaryStructure'
            )
            ->name('activate');

        Route::post(
            '/{salaryStructure}/revision',
            'revision'
        )
            ->whereNumber(
                'salaryStructure'
            )
            ->name('revision');

        Route::post(
            '/{salaryStructure}/cancel',
            'cancel'
        )
            ->whereNumber(
                'salaryStructure'
            )
            ->name('cancel');


        /*
         * عرض وتعديل وحذف الهيكل.
         */

        Route::get(
            '/{salaryStructure}',
            'show'
        )
            ->whereNumber(
                'salaryStructure'
            )
            ->name('show');

        Route::match(
            ['put', 'patch'],
            '/{salaryStructure}',
            'update'
        )
            ->whereNumber(
                'salaryStructure'
            )
            ->name('update');

        Route::delete(
            '/{salaryStructure}',
            'destroy'
        )
            ->whereNumber(
                'salaryStructure'
            )
            ->name('destroy');
    });


/*
|--------------------------------------------------------------------------
| فترات الرواتب
|--------------------------------------------------------------------------
*/

Route::prefix('payroll/periods')
    ->name('payroll.periods.')
    ->controller(
        PayrollPeriodController::class
    )
    ->group(function () {

        /*
         * المسارات الثابتة قبل
         * {payrollPeriod}.
         */

        Route::get('/data', 'data')
            ->name('data');

        Route::get('/options', 'options')
            ->name('options');

        Route::get('/', 'index')
            ->name('index');

        Route::post('/', 'store')
            ->name('store');


        /*
         * عمليات فترة الرواتب.
         */

        Route::post(
            '/{payrollPeriod}/open',
            'open'
        )
            ->whereNumber(
                'payrollPeriod'
            )
            ->name('open');

        Route::post(
            '/{payrollPeriod}/lock',
            'lock'
        )
            ->whereNumber(
                'payrollPeriod'
            )
            ->name('lock');

        Route::post(
            '/{payrollPeriod}/unlock',
            'unlock'
        )
            ->whereNumber(
                'payrollPeriod'
            )
            ->name('unlock');

        Route::post(
            '/{payrollPeriod}/cancel',
            'cancel'
        )
            ->whereNumber(
                'payrollPeriod'
            )
            ->name('cancel');

        Route::post(
            '/{payrollPeriod}/close',
            'close'
        )
            ->whereNumber(
                'payrollPeriod'
            )
            ->name('close');


        /*
         * عرض وتعديل وحذف الفترة.
         */

        Route::get(
            '/{payrollPeriod}',
            'show'
        )
            ->whereNumber(
                'payrollPeriod'
            )
            ->name('show');

        Route::match(
            ['put', 'patch'],
            '/{payrollPeriod}',
            'update'
        )
            ->whereNumber(
                'payrollPeriod'
            )
            ->name('update');

        Route::delete(
            '/{payrollPeriod}',
            'destroy'
        )
            ->whereNumber(
                'payrollPeriod'
            )
            ->name('destroy');
    });



/*
|--------------------------------------------------------------------------
| تسويات الرواتب
|--------------------------------------------------------------------------
*/

Route::prefix('payroll/adjustments')
    ->name('payroll.adjustments.')
    ->controller(
        PayrollAdjustmentController::class
    )
    ->group(function () {

        /*
         * المسارات الثابتة قبل
         * {payrollAdjustment}.
         */

        Route::get('/data', 'data')
            ->name('data');

        Route::get('/options', 'options')
            ->name('options');

        Route::get('/', 'index')
            ->name('index');

        Route::post('/', 'store')
            ->name('store');


        /*
         * إرسال التسوية للاعتماد.
         */

        Route::post(
            '/{payrollAdjustment}/submit',
            'submit'
        )
            ->whereNumber(
                'payrollAdjustment'
            )
            ->name('submit');


        /*
         * اعتماد التسوية.
         */

        Route::post(
            '/{payrollAdjustment}/approve',
            'approve'
        )
            ->whereNumber(
                'payrollAdjustment'
            )
            ->name('approve');


        /*
         * رفض التسوية.
         */

        Route::post(
            '/{payrollAdjustment}/reject',
            'reject'
        )
            ->whereNumber(
                'payrollAdjustment'
            )
            ->name('reject');


        /*
         * إعادة التسوية المرفوضة إلى المسودة.
         */

        Route::post(
            '/{payrollAdjustment}/return-draft',
            'returnToDraft'
        )
            ->whereNumber(
                'payrollAdjustment'
            )
            ->name('return-draft');


        /*
         * إلغاء التسوية.
         */

        Route::post(
            '/{payrollAdjustment}/cancel',
            'cancel'
        )
            ->whereNumber(
                'payrollAdjustment'
            )
            ->name('cancel');


        /*
         * عرض وتعديل وحذف التسوية.
         */

        Route::get(
            '/{payrollAdjustment}',
            'show'
        )
            ->whereNumber(
                'payrollAdjustment'
            )
            ->name('show');

        Route::match(
            ['put', 'patch'],
            '/{payrollAdjustment}',
            'update'
        )
            ->whereNumber(
                'payrollAdjustment'
            )
            ->name('update');

        Route::delete(
            '/{payrollAdjustment}',
            'destroy'
        )
            ->whereNumber(
                'payrollAdjustment'
            )
            ->name('destroy');
    });

    
/*
|--------------------------------------------------------------------------
| تشغيلات الرواتب
|--------------------------------------------------------------------------
*/

Route::prefix('payroll/runs')
    ->name('payroll.runs.')
    ->controller(
        PayrollRunController::class
    )
    ->group(function () {

        /*
         * المسارات الثابتة قبل
         * {payrollRun}.
         */

        Route::get('/data', 'data')
            ->name('data');

        Route::get('/options', 'options')
            ->name('options');

        Route::get('/', 'index')
            ->name('index');

        Route::post('/', 'store')
            ->name('store');


        /*
         * بنود الموظفين داخل التشغيل.
         */

        Route::get(
            '/{payrollRun}/items/data',
            'itemsData'
        )
            ->whereNumber(
                'payrollRun'
            )
            ->name('items.data');

        Route::get(
            '/{payrollRun}/items/{payrollItem}',
            'showItem'
        )
            ->whereNumber(
                'payrollRun'
            )
            ->whereNumber(
                'payrollItem'
            )
            ->name('items.show');


        /*
         * حساب تشغيل الرواتب.
         */

        Route::post(
            '/{payrollRun}/calculate',
            'calculate'
        )
            ->whereNumber(
                'payrollRun'
            )
            ->name('calculate');


        /*
         * إرسال التشغيل للمراجعة.
         */

        Route::post(
            '/{payrollRun}/submit-review',
            'submitForReview'
        )
            ->whereNumber(
                'payrollRun'
            )
            ->name('submit-review');


        /*
         * إعادة التشغيل من المراجعة إلى الحساب.
         */

        Route::post(
            '/{payrollRun}/return-calculation',
            'returnToCalculation'
        )
            ->whereNumber(
                'payrollRun'
            )
            ->name('return-calculation');


        /*
         * اعتماد تشغيل الرواتب.
         */

        Route::post(
            '/{payrollRun}/approve',
            'approve'
        )
            ->whereNumber(
                'payrollRun'
            )
            ->name('approve');


        /*
         * تسجيل تشغيل الرواتب كمدفوع.
         */

        Route::post(
            '/{payrollRun}/mark-paid',
            'markPaid'
        )
            ->whereNumber(
                'payrollRun'
            )
            ->name('mark-paid');


        /*
         * إلغاء تشغيل الرواتب.
         */

        Route::post(
            '/{payrollRun}/cancel',
            'cancel'
        )
            ->whereNumber(
                'payrollRun'
            )
            ->name('cancel');


        /*
         * عرض وحذف تشغيل الرواتب.
         */

        Route::get(
            '/{payrollRun}',
            'show'
        )
            ->whereNumber(
                'payrollRun'
            )
            ->name('show');

        Route::delete(
            '/{payrollRun}',
            'destroy'
        )
            ->whereNumber(
                'payrollRun'
            )
            ->name('destroy');
    });



    /*
    |--------------------------------------------------------------------------
    | قسائم الرواتب
    |--------------------------------------------------------------------------
    */

    Route::prefix('payslips')
        ->name('payslips.')
        ->controller(PayrollPayslipController::class)
        ->group(function () {

            Route::get('/data', 'data')
                ->name('data');

            Route::get('/options', 'options')
                ->name('options');

            Route::get('/', 'index')
                ->name('index');

            Route::get('/{payrollItem}/print', 'print')
                ->whereNumber('payrollItem')
                ->name('print');

            Route::get('/{payrollItem}', 'show')
                ->whereNumber('payrollItem')
                ->name('show');
        });



        /*
        |--------------------------------------------------------------------------
        | الخدمة الذاتية - قسائم الرواتب
        |--------------------------------------------------------------------------
        */
        Route::prefix('self-service/payslips')
            ->name('self-service.payslips.')
            ->middleware(
                'permission:self_service.payslips'
            )
            ->controller(
                SelfServicePayslipController::class
            )
            ->group(function () {

                /*
                 * المسارات الثابتة قبل معرف القسيمة.
                 */

                Route::get('/data', 'data')
                    ->name('data');

                Route::get('/options', 'options')
                    ->name('options');

                Route::get('/', 'index')
                    ->name('index');

                Route::get(
                    '/{payrollItem}/print',
                    'print'
                )
                    ->whereNumber('payrollItem')
                    ->name('print');

                Route::get(
                    '/{payrollItem}',
                    'show'
                )
                    ->whereNumber('payrollItem')
                    ->name('show');
            });


            /*
|--------------------------------------------------------------------------
| تقارير الرواتب
|--------------------------------------------------------------------------
*/

Route::prefix('payroll/reports')
    ->name('payroll.reports.')
    ->middleware(
        'permission:reports.view'
    )
    ->controller(
        PayrollReportController::class
    )
    ->group(function () {

        /*
         * المسارات الثابتة أولاً.
         */

        Route::get('/export', 'export')
            ->middleware(
                'permission:reports.export'
            )
            ->name('export');
            
        Route::get('/options', 'options')
            ->name('options');

        Route::get('/data', 'data')
            ->name('data');

        Route::get('/', 'index')
            ->name('index');
    });

    /*
|--------------------------------------------------------------------------
| إعدادات الرواتب البنكية
|--------------------------------------------------------------------------
*/

Route::prefix('payroll/settings')
    ->name('payroll.settings.')
    ->controller(PayrollSettingController::class)
    ->group(function () {

        Route::get('/data', 'show')
            ->name('data');

        Route::get('/', 'index')
            ->name('index');

        Route::post('/', 'store')
            ->name('store');
    });


/*
|--------------------------------------------------------------------------
| الحسابات البنكية للموظفين
|--------------------------------------------------------------------------
*/

Route::prefix('payroll/bank-accounts')
    ->name('payroll.bank-accounts.')
    ->controller(EmployeeBankAccountController::class)
    ->group(function () {

        /*
         * يجب وضع المسارات الثابتة قبل {bankAccount}.
         */

        Route::get('/data', 'data')
            ->name('data');

        Route::get('/options', 'options')
            ->name('options');

        Route::get('/', 'index')
            ->name('index');

        Route::post('/', 'store')
            ->name('store');

        Route::post('/{bankAccount}/verify', 'verify')
            ->whereNumber('bankAccount')
            ->name('verify');

        Route::post('/{bankAccount}/unverify', 'unverify')
            ->whereNumber('bankAccount')
            ->name('unverify');

        Route::get('/{bankAccount}', 'show')
            ->whereNumber('bankAccount')
            ->name('show');

        Route::match(
            ['put', 'patch'],
            '/{bankAccount}',
            'update'
        )
            ->whereNumber('bankAccount')
            ->name('update');

        Route::delete('/{bankAccount}', 'destroy')
            ->whereNumber('bankAccount')
            ->name('destroy');
    });


    /*
|--------------------------------------------------------------------------
| Payroll Payment Batches
|--------------------------------------------------------------------------
*/

Route::prefix('payroll/payment-batches')
    ->name('payroll-payment-batches.')
    ->controller(PayrollPaymentBatchController::class)
    ->group(function () {
        Route::get('/', 'index')
            ->middleware('permission:payroll.view')
            ->name('index');

        Route::get('/create', 'create')
            ->middleware('permission:payroll.manage')
            ->name('create');

        Route::post('/', 'store')
            ->middleware('permission:payroll.manage')
            ->name('store');

        /*
         * يجب وضع المسارات الخاصة قبل مسار show العام.
         */
        Route::get(
            '/{payrollPaymentBatch}/summary',
            'summary'
        )
            ->whereNumber('payrollPaymentBatch')
            ->middleware('permission:payroll.view')
            ->name('summary');

        Route::post(
            '/{payrollPaymentBatch}/validate',
            'validateBatch'
        )
            ->whereNumber('payrollPaymentBatch')
            ->middleware('permission:payroll.manage')
            ->name('validate');

        Route::post(
            '/{payrollPaymentBatch}/cancel',
            'cancel'
        )
            ->whereNumber('payrollPaymentBatch')
            ->middleware('permission:payroll.manage')
            ->name('cancel');

        Route::post(
            '/{payrollPaymentBatch}/items/{item}/exclude',
            'excludeItem'
        )
            ->whereNumber([
                'payrollPaymentBatch',
                'item',
            ])
            ->middleware('permission:payroll.manage')
            ->name('items.exclude');

        Route::post(
            '/{payrollPaymentBatch}/items/{item}/restore',
            'restoreItem'
        )
            ->whereNumber([
                'payrollPaymentBatch',
                'item',
            ])
            ->middleware('permission:payroll.manage')
            ->name('items.restore');

        Route::post(
            '/{payrollPaymentBatch}/generate-file',
            'generateFile'
        )
            ->whereNumber('payrollPaymentBatch')
            ->middleware('permission:payroll.process')
            ->name('generate-file');

        Route::get(
            '/{payrollPaymentBatch}/download-file',
            'downloadFile'
        )
            ->whereNumber('payrollPaymentBatch')
            ->middleware('permission:payroll.process')
            ->name('download-file');

            
        /*
         * هذا المسار يكون في النهاية.
         */
        Route::get(
            '/{payrollPaymentBatch}',
            'show'
        )
            ->whereNumber('payrollPaymentBatch')
            ->middleware('permission:payroll.view')
            ->name('show');
    });