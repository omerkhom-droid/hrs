<?php

namespace App\Services\HR;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeSalaryStructure;
use App\Models\PayrollAdjustment;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Models\PayrollRunItemComponent;
use App\Models\EmployeeLoanInstallment;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use Throwable;

class PayrollRunService
{
    public function __construct(
        private readonly PayrollCalculationService $calculationService,
        private readonly EmployeeLoanService $loanService
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | إنشاء تشغيل الرواتب
    |--------------------------------------------------------------------------
    */

    public function create(
        array $data,
        User $actor
    ): PayrollRun {
        $tenantId =
            (int) $actor->tenant_id;

        $this->authorizeActor(
            $actor,
            $tenantId,
            'payroll.process'
        );

        return DB::transaction(
            function () use (
                $data,
                $actor,
                $tenantId
            ) {
                $period =
                    PayrollPeriod::withoutGlobalScopes()
                        ->where(
                            'tenant_id',
                            $tenantId
                        )
                        ->whereKey(
                            $data['payroll_period_id']
                        )
                        ->whereNull(
                            'deleted_at'
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $this->ensurePeriodAcceptsRun(
                    $period
                );

                $type =
                    $data['type']
                    ?? PayrollRun::TYPE_REGULAR;

                if (
                    $type ===
                    PayrollRun::TYPE_REGULAR
                ) {
                    $this->ensureNoRegularRunExists(
                        $period
                    );
                }

                $employeeIds =
                    $this->normalizeEmployeeIds(
                        $data['employee_ids']
                        ?? []
                    );

                $run =
                    PayrollRun::create([
                        'uuid' =>
                            (string) Str::uuid(),

                        'tenant_id' =>
                            $tenantId,

                        'payroll_period_id' =>
                            $period->id,

                        'run_number' =>
                            $this->generateRunNumber(
                                $period
                            ),

                        'type' =>
                            $type,

                        'status' =>
                            PayrollRun::STATUS_DRAFT,

                        'currency_code' =>
                            strtoupper(
                                $data['currency_code']
                            ),

                        'employee_count' =>
                            0,

                        'total_basic_salary' =>
                            0,

                        'total_earnings' =>
                            0,

                        'total_deductions' =>
                            0,

                        'total_net_salary' =>
                            0,

                        'created_by' =>
                            $actor->id,

                        'notes' =>
                            $data['notes']
                            ?? null,

                        'metadata' => [
                            'selection_mode' =>
                                empty($employeeIds)
                                    ? 'all_eligible'
                                    : 'selected_employees',

                            'selected_employee_ids' =>
                                empty($employeeIds)
                                    ? null
                                    : $employeeIds,

                            'created_from' =>
                                'web',

                            'created_at' =>
                                now()->toIso8601String(),
                        ],
                    ]);

                return $run
                    ->refresh()
                    ->load([
                        'period',
                        'createdBy',
                    ]);
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | حساب تشغيل الرواتب
    |--------------------------------------------------------------------------
    */

    public function calculate(
        PayrollRun $payrollRun,
        User $actor
    ): PayrollRun {
        $this->authorizeActor(
            $actor,
            (int) $payrollRun->tenant_id,
            'payroll.process'
        );

        return DB::transaction(
            function () use (
                $payrollRun,
                $actor
            ) {
                $run =
                    PayrollRun::withoutGlobalScopes()
                        ->where(
                            'tenant_id',
                            $payrollRun->tenant_id
                        )
                        ->whereKey(
                            $payrollRun->id
                        )
                        ->whereNull(
                            'deleted_at'
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $this->ensureRunCanBeCalculated(
                    $run
                );

                $period =
                    PayrollPeriod::withoutGlobalScopes()
                        ->where(
                            'tenant_id',
                            $run->tenant_id
                        )
                        ->whereKey(
                            $run->payroll_period_id
                        )
                        ->whereNull(
                            'deleted_at'
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $this->ensurePeriodAcceptsCalculation(
                    $period
                );

                /*
                 * تحرير التسويات المرتبطة بالحساب السابق
                 * قبل إعادة احتساب التشغيل.
                 */
                $this->deleteExistingRunItems(
                    $run
                );

                $employees =
                    $this->resolveEmployees(
                        $run,
                        $period
                    );

                if ($employees->isEmpty()) {
                    throw new LogicException(
                        'لا يوجد موظفون مؤهلون لتشغيل الرواتب.'
                    );
                }

                $run->forceFill([
                    'status' =>
                        PayrollRun::STATUS_CALCULATING,
                ])->save();

                $calculatedItems = 0;
                $exceptionItems = 0;

                foreach ($employees as $employee) {
                    $result =
                        $this->calculateEmployee(
                            $employee,
                            $period,
                            $run
                        );

                    $item =
                        $this->storePayrollItem(
                            $run,
                            $employee,
                            $result
                        );

                    if (
                        $item->status ===
                        PayrollRunItem::STATUS_EXCEPTION
                    ) {
                        $exceptionItems++;
                    } else {
                        $calculatedItems++;
                    }

                    $this->storeItemComponents(
                        $run,
                        $item,
                        $result['components']
                        ?? []
                    );

                    $this->applyAdjustments(
                        $run,
                        $item,
                        $result['adjustment_ids']
                        ?? []
                    );

                    $this->applyLoanInstallments(
                        $run,
                        $item,
                        $result['loan_installment_ids']
                        ?? []
                    );
                }

                $totals =
                    $this->calculateRunTotals(
                        $run
                    );

                $metadata =
                    $run->metadata
                    ?? [];

                $metadata['calculation'] = [
                    'calculated_at' =>
                        now()->toIso8601String(),

                    'calculated_by' =>
                        $actor->id,

                    'employee_count' =>
                        $employees->count(),

                    'calculated_items' =>
                        $calculatedItems,

                    'exception_items' =>
                        $exceptionItems,
                ];

                $run->forceFill([
                    'status' =>
                        PayrollRun::STATUS_CALCULATED,

                    'employee_count' =>
                        $totals['employee_count'],

                    'total_basic_salary' =>
                        $totals[
                            'total_basic_salary'
                        ],

                    'total_earnings' =>
                        $totals[
                            'total_earnings'
                        ],

                    'total_deductions' =>
                        $totals[
                            'total_deductions'
                        ],

                    'total_net_salary' =>
                        $totals[
                            'total_net_salary'
                        ],

                    'calculated_at' =>
                        now(),

                    'calculated_by' =>
                        $actor->id,

                    'approved_at' =>
                        null,

                    'approved_by' =>
                        null,

                    'paid_at' =>
                        null,

                    'paid_by' =>
                        null,

                    'metadata' =>
                        $metadata,
                ])->save();

                $period->forceFill([
                    'status' =>
                        PayrollPeriod::STATUS_PROCESSING,
                ])->save();

                return $run
                    ->refresh()
                    ->load([
                        'period',
                        'items.employee',
                        'items.components',
                    ]);
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | إرسال التشغيل للمراجعة
    |--------------------------------------------------------------------------
    */

    public function submitForReview(
        PayrollRun $payrollRun,
        User $actor
    ): PayrollRun {
        $this->authorizeActor(
            $actor,
            (int) $payrollRun->tenant_id,
            'payroll.process'
        );

        return DB::transaction(
            function () use (
                $payrollRun,
                $actor
            ) {
                $run =
                    $this->lockedRun(
                        $payrollRun
                    );

                if (
                    $run->status !==
                    PayrollRun::STATUS_CALCULATED
                ) {
                    throw new LogicException(
                        'يجب حساب تشغيل الرواتب قبل إرساله للمراجعة.'
                    );
                }

                $itemsCount =
                    PayrollRunItem::withoutGlobalScopes()
                        ->where(
                            'tenant_id',
                            $run->tenant_id
                        )
                        ->where(
                            'payroll_run_id',
                            $run->id
                        )
                        ->whereNull(
                            'deleted_at'
                        )
                        ->count();

                if ($itemsCount === 0) {
                    throw new LogicException(
                        'تشغيل الرواتب لا يحتوي على موظفين.'
                    );
                }

                $exceptionsExist =
                    PayrollRunItem::withoutGlobalScopes()
                        ->where(
                            'tenant_id',
                            $run->tenant_id
                        )
                        ->where(
                            'payroll_run_id',
                            $run->id
                        )
                        ->where(
                            'status',
                            PayrollRunItem::STATUS_EXCEPTION
                        )
                        ->whereNull(
                            'deleted_at'
                        )
                        ->exists();

                if ($exceptionsExist) {
                    throw new LogicException(
                        'يجب معالجة جميع أخطاء الموظفين قبل إرسال التشغيل للمراجعة.'
                    );
                }

                $metadata =
                    $run->metadata
                    ?? [];

                $metadata['review'] = [
                    'submitted_at' =>
                        now()->toIso8601String(),

                    'submitted_by' =>
                        $actor->id,
                ];

                $run->forceFill([
                    'status' =>
                        PayrollRun::STATUS_REVIEW,

                    'metadata' =>
                        $metadata,
                ])->save();

                $period =
                    PayrollPeriod::withoutGlobalScopes()
                        ->where(
                            'tenant_id',
                            $run->tenant_id
                        )
                        ->whereKey(
                            $run->payroll_period_id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $period->forceFill([
                    'status' =>
                        PayrollPeriod::STATUS_REVIEW,
                ])->save();

                return $run
                    ->refresh()
                    ->load([
                        'period',
                        'items.employee',
                    ]);
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | إعادة التشغيل من المراجعة
    |--------------------------------------------------------------------------
    */

    public function returnToCalculation(
        PayrollRun $payrollRun,
        User $actor,
        string $reason
    ): PayrollRun {
        $this->authorizeActor(
            $actor,
            (int) $payrollRun->tenant_id,
            'payroll.approve'
        );

        $reason = trim($reason);

        if ($reason === '') {
            throw new LogicException(
                'سبب إعادة التشغيل للحساب مطلوب.'
            );
        }

        return DB::transaction(
            function () use (
                $payrollRun,
                $actor,
                $reason
            ) {
                $run =
                    $this->lockedRun(
                        $payrollRun
                    );

                if (
                    $run->status !==
                    PayrollRun::STATUS_REVIEW
                ) {
                    throw new LogicException(
                        'يمكن إعادة التشغيل فقط عندما يكون قيد المراجعة.'
                    );
                }

                $metadata =
                    $run->metadata
                    ?? [];

                $metadata['review_return'] = [
                    'returned_at' =>
                        now()->toIso8601String(),

                    'returned_by' =>
                        $actor->id,

                    'reason' =>
                        $reason,
                ];

                $run->forceFill([
                    'status' =>
                        PayrollRun::STATUS_CALCULATED,

                    'metadata' =>
                        $metadata,
                ])->save();

                $period =
                    PayrollPeriod::withoutGlobalScopes()
                        ->where(
                            'tenant_id',
                            $run->tenant_id
                        )
                        ->whereKey(
                            $run->payroll_period_id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $period->forceFill([
                    'status' =>
                        PayrollPeriod::STATUS_PROCESSING,
                ])->save();

                return $run->refresh();
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | اعتماد تشغيل الرواتب
    |--------------------------------------------------------------------------
    */

    public function approve(
        PayrollRun $payrollRun,
        User $actor
    ): PayrollRun {
        $this->authorizeActor(
            $actor,
            (int) $payrollRun->tenant_id,
            'payroll.approve'
        );

        return DB::transaction(
            function () use (
                $payrollRun,
                $actor
            ) {
                $run =
                    $this->lockedRun(
                        $payrollRun
                    );

                if (
                    $run->status !==
                    PayrollRun::STATUS_REVIEW
                ) {
                    throw new LogicException(
                        'يجب إرسال تشغيل الرواتب للمراجعة قبل اعتماده.'
                    );
                }

                $exceptionsExist =
                    PayrollRunItem::withoutGlobalScopes()
                        ->where(
                            'tenant_id',
                            $run->tenant_id
                        )
                        ->where(
                            'payroll_run_id',
                            $run->id
                        )
                        ->where(
                            'status',
                            PayrollRunItem::STATUS_EXCEPTION
                        )
                        ->whereNull(
                            'deleted_at'
                        )
                        ->exists();

                if ($exceptionsExist) {
                    throw new LogicException(
                        'لا يمكن اعتماد تشغيل يحتوي على أخطاء.'
                    );
                }

                PayrollRunItem::withoutGlobalScopes()
                    ->where(
                        'tenant_id',
                        $run->tenant_id
                    )
                    ->where(
                        'payroll_run_id',
                        $run->id
                    )
                    ->where(
                        'status',
                        PayrollRunItem::STATUS_CALCULATED
                    )
                    ->whereNull(
                        'deleted_at'
                    )
                    ->update([
                        'status' =>
                            PayrollRunItem::STATUS_APPROVED,

                        'updated_at' =>
                            now(),
                    ]);

                $run->forceFill([
                    'status' =>
                        PayrollRun::STATUS_APPROVED,

                    'approved_at' =>
                        now(),

                    'approved_by' =>
                        $actor->id,
                ])->save();

                $period =
                    PayrollPeriod::withoutGlobalScopes()
                        ->where(
                            'tenant_id',
                            $run->tenant_id
                        )
                        ->whereKey(
                            $run->payroll_period_id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $period->forceFill([
                    'status' =>
                        PayrollPeriod::STATUS_APPROVED,

                    'is_locked' =>
                        true,

                    'locked_at' =>
                        now(),

                    'locked_by' =>
                        $actor->id,
                ])->save();

                return $run
                    ->refresh()
                    ->load([
                        'period',
                        'approvedBy',
                        'items.employee',
                    ]);
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | تسجيل الرواتب كمدفوعة
    |--------------------------------------------------------------------------
    */

    public function markPaid(
        PayrollRun $payrollRun,
        User $actor
    ): PayrollRun {
        $this->authorizeActor(
            $actor,
            (int) $payrollRun->tenant_id,
            'payroll.approve'
        );

        return DB::transaction(
            function () use (
                $payrollRun,
                $actor
            ) {
                $run =
                    $this->lockedRun(
                        $payrollRun
                    );

                if (
                    $run->status !==
                    PayrollRun::STATUS_APPROVED
                ) {
                    throw new LogicException(
                        'يجب اعتماد تشغيل الرواتب قبل تسجيله كمدفوع.'
                    );
                }

                PayrollRunItem::withoutGlobalScopes()
                    ->where(
                        'tenant_id',
                        $run->tenant_id
                    )
                    ->where(
                        'payroll_run_id',
                        $run->id
                    )
                    ->where(
                        'status',
                        PayrollRunItem::STATUS_APPROVED
                    )
                    ->whereNull(
                        'deleted_at'
                    )
                    ->update([
                        'status' =>
                            PayrollRunItem::STATUS_PAID,

                        'updated_at' =>
                            now(),
                    ]);

                $this->finalizeRunLoanInstallments(
                    $run
                );

                $run->forceFill([
                    'status' =>
                        PayrollRun::STATUS_PAID,

                    'paid_at' =>
                        now(),

                    'paid_by' =>
                        $actor->id,
                ])->save();

                $period =
                    PayrollPeriod::withoutGlobalScopes()
                        ->where(
                            'tenant_id',
                            $run->tenant_id
                        )
                        ->whereKey(
                            $run->payroll_period_id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $period->forceFill([
                    'status' =>
                        PayrollPeriod::STATUS_PAID,

                    'is_locked' =>
                        true,
                ])->save();

                return $run
                    ->refresh()
                    ->load([
                        'period',
                        'paidBy',
                        'items.employee',
                    ]);
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | إلغاء تشغيل الرواتب
    |--------------------------------------------------------------------------
    */

    public function cancel(
        PayrollRun $payrollRun,
        User $actor,
        string $reason
    ): PayrollRun {
        $requiredPermission =
            $payrollRun->status ===
                PayrollRun::STATUS_APPROVED
                ? 'payroll.approve'
                : 'payroll.manage';

        $this->authorizeActor(
            $actor,
            (int) $payrollRun->tenant_id,
            $requiredPermission
        );

        $reason = trim($reason);

        if ($reason === '') {
            throw new LogicException(
                'سبب إلغاء تشغيل الرواتب مطلوب.'
            );
        }

        return DB::transaction(
            function () use (
                $payrollRun,
                $actor,
                $reason
            ) {
                $run =
                    $this->lockedRun(
                        $payrollRun
                    );

                if (
                    $run->status ===
                    PayrollRun::STATUS_PAID
                ) {
                    throw new LogicException(
                        'لا يمكن إلغاء تشغيل رواتب تم دفعه.'
                    );
                }

                if (
                    $run->status ===
                    PayrollRun::STATUS_CANCELLED
                ) {
                    throw new LogicException(
                        'تشغيل الرواتب ملغى مسبقًا.'
                    );
                }

                $this->releaseRunAdjustments(
                    $run
                );

                $this->releaseRunLoanInstallments(
                    $run
                );

                $metadata =
                    $run->metadata
                    ?? [];

                $metadata['cancellation'] = [
                    'cancelled_at' =>
                        now()->toIso8601String(),

                    'cancelled_by' =>
                        $actor->id,

                    'reason' =>
                        $reason,
                ];

                $run->forceFill([
                    'status' =>
                        PayrollRun::STATUS_CANCELLED,

                    'metadata' =>
                        $metadata,
                ])->save();

                $period =
                    PayrollPeriod::withoutGlobalScopes()
                        ->where(
                            'tenant_id',
                            $run->tenant_id
                        )
                        ->whereKey(
                            $run->payroll_period_id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $this->refreshPeriodStatus(
                    $period
                );

                return $run
                    ->refresh()
                    ->load('period');
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | حذف تشغيل مسودة
    |--------------------------------------------------------------------------
    */

    public function delete(
        PayrollRun $payrollRun,
        User $actor
    ): void {
        $this->authorizeActor(
            $actor,
            (int) $payrollRun->tenant_id,
            'payroll.manage'
        );

        DB::transaction(
            function () use (
                $payrollRun
            ) {
                $run =
                    $this->lockedRun(
                        $payrollRun
                    );

                if (
                    $run->status !==
                    PayrollRun::STATUS_DRAFT
                ) {
                    throw new LogicException(
                        'يمكن حذف تشغيل الرواتب عندما يكون مسودة فقط.'
                    );
                }

                $this->deleteExistingRunItems(
                    $run
                );

                $period =
                    PayrollPeriod::withoutGlobalScopes()
                        ->where(
                            'tenant_id',
                            $run->tenant_id
                        )
                        ->whereKey(
                            $run->payroll_period_id
                        )
                        ->lockForUpdate()
                        ->first();

                $run->delete();

                if ($period) {
                    $this->refreshPeriodStatus(
                        $period
                    );
                }
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | حساب موظف واحد
    |--------------------------------------------------------------------------
    */

    private function calculateEmployee(
        Employee $employee,
        PayrollPeriod $period,
        PayrollRun $run
    ): array {
        try {
            $metrics =
                $this->resolveWorkMetrics(
                    $employee,
                    $period
                );

            return $this
                ->calculationService
                ->calculate(
                    $employee,
                    $period,
                    $run,
                    $metrics
                );
        } catch (LogicException $exception) {
            return [
                'employee_id' =>
                    $employee->id,

                'salary_structure_id' =>
                    null,

                'currency_code' =>
                    $run->currency_code,

                'basic_salary' =>
                    0,

                'gross_salary' =>
                    0,

                'total_earnings' =>
                    0,

                'total_deductions' =>
                    0,

                'net_salary' =>
                    0,

                'scheduled_work_days' =>
                    0,

                'actual_work_days' =>
                    0,

                'absent_days' =>
                    0,

                'paid_leave_days' =>
                    0,

                'unpaid_leave_days' =>
                    0,

                'overtime_minutes' =>
                    0,

                'components' =>
                    [],

                'adjustment_ids' =>
                    [],

                'loan_installment_ids' =>
                    [],

                'errors' => [
                    $exception->getMessage(),
                ],

                'warnings' =>
                    [],

                'calculation_snapshot' => [
                    'employee_id' =>
                        $employee->id,

                    'employee_number' =>
                        $employee->employee_number,

                    'failed_at' =>
                        now()->toIso8601String(),

                    'error' =>
                        $exception->getMessage(),
                ],
            ];
        }
    }


    /*
    |--------------------------------------------------------------------------
    | ملخص الحضور
    |--------------------------------------------------------------------------
    */

    private function resolveWorkMetrics(
        Employee $employee,
        PayrollPeriod $period
    ): array {
        $records =
            AttendanceRecord::withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $employee->tenant_id
                )
                ->where(
                    'employee_id',
                    $employee->id
                )
                ->whereDate(
                    'attendance_date',
                    '>=',
                    $period->start_date
                )
                ->whereDate(
                    'attendance_date',
                    '<=',
                    $period->end_date
                )
                ->where(
                    'approval_status',
                    'approved'
                )
                ->whereNull(
                    'deleted_at'
                )
                ->get([
                    'id',
                    'attendance_date',
                    'check_in_at',
                    'check_out_at',
                    'status',
                    'work_minutes',
                    'overtime_minutes',
                ]);

        /*
         * إذا لم توجد سجلات حضور معتمدة،
         * لا يتم تطبيق خصم تلقائي.
         */
        if ($records->isEmpty()) {
            return [
                'scheduled_work_days' =>
                    0,

                'actual_work_days' =>
                    0,

                'absent_days' =>
                    0,

                'paid_leave_days' =>
                    0,

                'unpaid_leave_days' =>
                    0,

                'overtime_minutes' =>
                    0,

                'proration_ratio' =>
                    1,
            ];
        }

        $scheduledWorkDays =
            $records
                ->unique(
                    fn ($record) =>
                        $record
                            ->attendance_date
                            ?->toDateString()
                        ?? (string) $record
                            ->attendance_date
                )
                ->count();

        $actualWorkDays =
            $records
                ->filter(
                    fn ($record) =>
                        $record->check_in_at !== null
                )
                ->unique(
                    fn ($record) =>
                        $record
                            ->attendance_date
                            ?->toDateString()
                        ?? (string) $record
                            ->attendance_date
                )
                ->count();

        $absentDays =
            $records
                ->where(
                    'status',
                    'absent'
                )
                ->unique(
                    fn ($record) =>
                        $record
                            ->attendance_date
                            ?->toDateString()
                        ?? (string) $record
                            ->attendance_date
                )
                ->count();

        $overtimeMinutes =
            (int) $records->sum(
                'overtime_minutes'
            );

        return [
            'scheduled_work_days' =>
                $scheduledWorkDays,

            'actual_work_days' =>
                $actualWorkDays,

            'absent_days' =>
                $absentDays,

            /*
             * سيتم ربط الإجازات المدفوعة وغير المدفوعة
             * في خدمة مستقلة لاحقًا.
             */
            'paid_leave_days' =>
                0,

            'unpaid_leave_days' =>
                0,

            'overtime_minutes' =>
                $overtimeMinutes,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | حفظ بند الموظف
    |--------------------------------------------------------------------------
    */

    private function storePayrollItem(
        PayrollRun $run,
        Employee $employee,
        array $result
    ): PayrollRunItem {
        $errors =
            $result['errors']
            ?? [];

        $status =
            empty($errors)
                ? PayrollRunItem::STATUS_CALCULATED
                : PayrollRunItem::STATUS_EXCEPTION;

        return PayrollRunItem::create([
            'uuid' =>
                (string) Str::uuid(),

            'tenant_id' =>
                $run->tenant_id,

            'payroll_run_id' =>
                $run->id,

            'employee_id' =>
                $employee->id,

            'salary_structure_id' =>
                $result['salary_structure_id']
                ?? null,

            'currency_code' =>
                $run->currency_code,

            'basic_salary' =>
                $result['basic_salary']
                ?? 0,

            'gross_salary' =>
                $result['gross_salary']
                ?? 0,

            'total_earnings' =>
                $result['total_earnings']
                ?? 0,

            'total_deductions' =>
                $result['total_deductions']
                ?? 0,

            'net_salary' =>
                $result['net_salary']
                ?? 0,

            'scheduled_work_days' =>
                $result['scheduled_work_days']
                ?? 0,

            'actual_work_days' =>
                $result['actual_work_days']
                ?? 0,

            'absent_days' =>
                $result['absent_days']
                ?? 0,

            'paid_leave_days' =>
                $result['paid_leave_days']
                ?? 0,

            'unpaid_leave_days' =>
                $result['unpaid_leave_days']
                ?? 0,

            'overtime_minutes' =>
                $result['overtime_minutes']
                ?? 0,

            'status' =>
                $status,

            'calculation_snapshot' =>
                $result['calculation_snapshot']
                ?? null,

            'errors' =>
                empty($errors)
                    ? null
                    : $errors,

            'metadata' => [
                'warnings' =>
                    $result['warnings']
                    ?? [],

                'calculated_at' =>
                    now()->toIso8601String(),
            ],
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | حفظ مكونات بند الراتب
    |--------------------------------------------------------------------------
    */

    private function storeItemComponents(
        PayrollRun $run,
        PayrollRunItem $item,
        array $components
    ): void {
        foreach ($components as $component) {
            PayrollRunItemComponent::create([
                'tenant_id' =>
                    $run->tenant_id,

                'payroll_item_id' =>
                    $item->id,

                'salary_component_id' =>
                    $component[
                        'salary_component_id'
                    ] ?? null,

                'component_code' =>
                    $component[
                        'component_code'
                    ],

                'component_name' =>
                    $component[
                        'component_name'
                    ],

                'type' =>
                    $component['type'],

                'category' =>
                    $component['category']
                    ?? null,

                'source' =>
                    $component['source'],

                'quantity' =>
                    $component['quantity']
                    ?? null,

                'rate' =>
                    $component['rate']
                    ?? null,

                'percentage' =>
                    $component['percentage']
                    ?? null,

                'amount' =>
                    $component['amount'],

                'is_taxable' =>
                    $component['is_taxable']
                    ?? false,

                'is_subject_to_insurance' =>
                    $component[
                        'is_subject_to_insurance'
                    ] ?? false,

                'reference_type' =>
                    $component['reference_type']
                    ?? null,

                'reference_id' =>
                    $component['reference_id']
                    ?? null,

                'metadata' =>
                    $component['metadata']
                    ?? null,
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | ربط التسويات ببند الراتب
    |--------------------------------------------------------------------------
    */

    private function applyAdjustments(
        PayrollRun $run,
        PayrollRunItem $item,
        array $adjustmentIds
    ): void {
        if (empty($adjustmentIds)) {
            return;
        }

        PayrollAdjustment::withoutGlobalScopes()
            ->where(
                'tenant_id',
                $run->tenant_id
            )
            ->whereIn(
                'id',
                $adjustmentIds
            )
            ->where(
                'status',
                PayrollAdjustment::STATUS_APPROVED
            )
            ->whereNull(
                'applied_payroll_item_id'
            )
            ->update([
                'status' =>
                    PayrollAdjustment::STATUS_APPLIED,

                'applied_payroll_item_id' =>
                    $item->id,

                'updated_at' =>
                    now(),
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ربط أقساط السلفة ببند الموظف
    |--------------------------------------------------------------------------
    */

    private function applyLoanInstallments(
        PayrollRun $run,
        PayrollRunItem $item,
        array $installmentIds
    ): void {
        $installmentIds = collect($installmentIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($installmentIds->isEmpty()) {
            return;
        }

        /*
         * لا يتم حجز الأقساط إذا فشل حساب راتب الموظف.
         */
        if (
            $item->status ===
            PayrollRunItem::STATUS_EXCEPTION
        ) {
            return;
        }

        $updatedCount =
            EmployeeLoanInstallment::withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $run->tenant_id
                )
                ->where(
                    'employee_id',
                    $item->employee_id
                )
                ->whereIn(
                    'id',
                    $installmentIds->all()
                )
                ->whereIn('status', [
                    'pending',
                    'partially_paid',
                ])
                ->whereNull('payroll_run_id')
                ->update([
                    'payroll_run_id' =>
                        $run->id,

                    'payroll_run_item_id' =>
                        $item->id,

                    'updated_at' =>
                        now(),
                ]);

        if (
            $updatedCount !==
            $installmentIds->count()
        ) {
            throw new LogicException(
                'تعذر حجز بعض أقساط السلفة؛ ربما أضيفت إلى تشغيل رواتب آخر.'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | تحديد الموظفين
    |--------------------------------------------------------------------------
    */

    private function resolveEmployees(
        PayrollRun $run,
        PayrollPeriod $period
    ): Collection {
        $metadata =
            $run->metadata
            ?? [];

        $employeeIds =
            $this->normalizeEmployeeIds(
                $metadata[
                    'selected_employee_ids'
                ] ?? []
            );

        $query =
            Employee::withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $run->tenant_id
                )
                ->whereNull(
                    'deleted_at'
                )
                ->whereNotIn(
                    'employment_status',
                    ['terminated']
                );

        if (!empty($employeeIds)) {
            $query->whereIn(
                'id',
                $employeeIds
            );
        } else {
            /*
             * عند اختيار جميع الموظفين،
             * يتم جلب الموظفين الذين لديهم هيكل راتب فعال.
             */
            $query->whereExists(
                function ($query) use (
                    $run,
                    $period
                ) {
                    $query
                        ->selectRaw('1')
                        ->from(
                            'employee_salary_structures'
                        )
                        ->whereColumn(
                            'employee_salary_structures.employee_id',
                            'employees.id'
                        )
                        ->where(
                            'employee_salary_structures.tenant_id',
                            $run->tenant_id
                        )
                        ->where(
                            'employee_salary_structures.status',
                            EmployeeSalaryStructure::STATUS_ACTIVE
                        )
                        ->whereDate(
                            'employee_salary_structures.effective_from',
                            '<=',
                            $period->end_date
                        )
                        ->where(function ($query) use ($period) {
                            $query
                                ->whereNull(
                                    'employee_salary_structures.effective_to'
                                )
                                ->orWhereDate(
                                    'employee_salary_structures.effective_to',
                                    '>=',
                                    $period->start_date
                                );
                        })
                        ->whereNull(
                            'employee_salary_structures.deleted_at'
                        );
                }
            );
        }

        return $query
            ->orderBy('id')
            ->get();
    }


    /*
    |--------------------------------------------------------------------------
    | إجماليات التشغيل
    |--------------------------------------------------------------------------
    */

    private function calculateRunTotals(
        PayrollRun $run
    ): array {
        $totals =
            PayrollRunItem::withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $run->tenant_id
                )
                ->where(
                    'payroll_run_id',
                    $run->id
                )
                ->whereNull(
                    'deleted_at'
                )
                ->selectRaw(
                    '
                        COUNT(*) AS employee_count,
                        COALESCE(SUM(basic_salary), 0) AS total_basic_salary,
                        COALESCE(SUM(total_earnings), 0) AS total_earnings,
                        COALESCE(SUM(total_deductions), 0) AS total_deductions,
                        COALESCE(SUM(net_salary), 0) AS total_net_salary
                    '
                )
                ->first();

        return [
            'employee_count' =>
                (int) (
                    $totals->employee_count
                    ?? 0
                ),

            'total_basic_salary' =>
                $this->money(
                    $totals->total_basic_salary
                    ?? 0
                ),

            'total_earnings' =>
                $this->money(
                    $totals->total_earnings
                    ?? 0
                ),

            'total_deductions' =>
                $this->money(
                    $totals->total_deductions
                    ?? 0
                ),

            'total_net_salary' =>
                $this->money(
                    $totals->total_net_salary
                    ?? 0
                ),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | حذف الحساب السابق
    |--------------------------------------------------------------------------
    */

    private function deleteExistingRunItems(
        PayrollRun $run
    ): void {
        
        $this->releaseRunLoanInstallments($run);

        $itemIds =
            PayrollRunItem::withoutGlobalScopes()
                ->withTrashed()
                ->where(
                    'tenant_id',
                    $run->tenant_id
                )
                ->where(
                    'payroll_run_id',
                    $run->id
                )
                ->pluck('id');

        if ($itemIds->isEmpty()) {
            return;
        }

        PayrollAdjustment::withoutGlobalScopes()
            ->where(
                'tenant_id',
                $run->tenant_id
            )
            ->whereIn(
                'applied_payroll_item_id',
                $itemIds
            )
            ->where(
                'status',
                PayrollAdjustment::STATUS_APPLIED
            )
            ->update([
                'status' =>
                    PayrollAdjustment::STATUS_APPROVED,

                'applied_payroll_item_id' =>
                    null,

                'updated_at' =>
                    now(),
            ]);

        PayrollRunItemComponent::withoutGlobalScopes()
            ->where(
                'tenant_id',
                $run->tenant_id
            )
            ->whereIn(
                'payroll_item_id',
                $itemIds
            )
            ->delete();

        PayrollRunItem::withoutGlobalScopes()
            ->withTrashed()
            ->where(
                'tenant_id',
                $run->tenant_id
            )
            ->where(
                'payroll_run_id',
                $run->id
            )
            ->forceDelete();
    }

    /*
    |--------------------------------------------------------------------------
    | تثبيت سداد أقساط السلف عند دفع المسير
    |--------------------------------------------------------------------------
    */

    private function finalizeRunLoanInstallments(
        PayrollRun $run
    ): void {
        $installments =
            EmployeeLoanInstallment::withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $run->tenant_id
                )
                ->where(
                    'payroll_run_id',
                    $run->id
                )
                ->whereIn('status', [
                    'pending',
                    'partially_paid',
                ])
                ->lockForUpdate()
                ->orderBy('id')
                ->get();

        foreach ($installments as $installment) {
            $amount =
                (float) $installment
                    ->remaining_amount;

            if ($amount <= 0) {
                continue;
            }

            if (!$installment->payroll_run_item_id) {
                throw new LogicException(
                    'قسط السلفة غير مرتبط ببند موظف داخل مسير الرواتب.'
                );
            }

            $this->loanService
                ->recordPayrollDeduction(
                    $installment,
                    $amount,
                    $run->id,
                    (int) $installment
                        ->payroll_run_item_id
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | تحرير أقساط السلفة المرتبطة بالمسير
    |--------------------------------------------------------------------------
    */

    private function releaseRunLoanInstallments(
        PayrollRun $run
    ): void {
        EmployeeLoanInstallment::withoutGlobalScopes()
            ->where(
                'tenant_id',
                $run->tenant_id
            )
            ->where(
                'payroll_run_id',
                $run->id
            )
            ->whereIn('status', [
                'pending',
                'partially_paid',
            ])
            ->update([
                'payroll_run_id' => null,

                'payroll_run_item_id' => null,

                'updated_at' => now(),
            ]);
    }

    private function releaseRunAdjustments(
        PayrollRun $run
    ): void {
        $itemIds =
            PayrollRunItem::withoutGlobalScopes()
                ->withTrashed()
                ->where(
                    'tenant_id',
                    $run->tenant_id
                )
                ->where(
                    'payroll_run_id',
                    $run->id
                )
                ->pluck('id');

        if ($itemIds->isEmpty()) {
            return;
        }

        PayrollAdjustment::withoutGlobalScopes()
            ->where(
                'tenant_id',
                $run->tenant_id
            )
            ->whereIn(
                'applied_payroll_item_id',
                $itemIds
            )
            ->where(
                'status',
                PayrollAdjustment::STATUS_APPLIED
            )
            ->update([
                'status' =>
                    PayrollAdjustment::STATUS_APPROVED,

                'applied_payroll_item_id' =>
                    null,

                'updated_at' =>
                    now(),
            ]);
    }


    /*
    |--------------------------------------------------------------------------
    | تحديث حالة الفترة
    |--------------------------------------------------------------------------
    */

    private function refreshPeriodStatus(
        PayrollPeriod $period
    ): void {
        $statuses =
            PayrollRun::withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $period->tenant_id
                )
                ->where(
                    'payroll_period_id',
                    $period->id
                )
                ->where(
                    'status',
                    '!=',
                    PayrollRun::STATUS_CANCELLED
                )
                ->whereNull(
                    'deleted_at'
                )
                ->pluck('status');

        if ($statuses->isEmpty()) {
            $period->forceFill([
                'status' =>
                    PayrollPeriod::STATUS_OPEN,

                'is_locked' =>
                    false,

                'locked_at' =>
                    null,

                'locked_by' =>
                    null,
            ])->save();

            return;
        }

        if (
            $statuses->contains(
                PayrollRun::STATUS_PAID
            )
        ) {
            $status =
                PayrollPeriod::STATUS_PAID;
        } elseif (
            $statuses->contains(
                PayrollRun::STATUS_APPROVED
            )
        ) {
            $status =
                PayrollPeriod::STATUS_APPROVED;
        } elseif (
            $statuses->contains(
                PayrollRun::STATUS_REVIEW
            )
        ) {
            $status =
                PayrollPeriod::STATUS_REVIEW;
        } elseif (
            $statuses->contains(
                PayrollRun::STATUS_CALCULATED
            )
            ||
            $statuses->contains(
                PayrollRun::STATUS_CALCULATING
            )
        ) {
            $status =
                PayrollPeriod::STATUS_PROCESSING;
        } else {
            $status =
                PayrollPeriod::STATUS_OPEN;
        }

        $period->forceFill([
            'status' =>
                $status,

            'is_locked' =>
                in_array(
                    $status,
                    [
                        PayrollPeriod::STATUS_APPROVED,
                        PayrollPeriod::STATUS_PAID,
                        PayrollPeriod::STATUS_CLOSED,
                    ],
                    true
                ),
        ])->save();
    }


    /*
    |--------------------------------------------------------------------------
    | التحقق
    |--------------------------------------------------------------------------
    */

    private function ensurePeriodAcceptsRun(
        PayrollPeriod $period
    ): void {
        if (
            $period->status !==
            PayrollPeriod::STATUS_OPEN
        ) {
            throw new LogicException(
                'يجب فتح فترة الرواتب أولًا.'
            );
        }

        if ($period->is_locked) {
            throw new LogicException(
                'فترة الرواتب مقفلة.'
            );
        }
    }


    private function ensurePeriodAcceptsCalculation(
        PayrollPeriod $period
    ): void {
        if ($period->is_locked) {
            throw new LogicException(
                'فترة الرواتب مقفلة ولا يمكن حساب التشغيل.'
            );
        }

        if (
            !in_array(
                $period->status,
                [
                    PayrollPeriod::STATUS_OPEN,
                    PayrollPeriod::STATUS_PROCESSING,
                    PayrollPeriod::STATUS_REVIEW,
                ],
                true
            )
        ) {
            throw new LogicException(
                'حالة فترة الرواتب لا تسمح بالحساب.'
            );
        }
    }


    private function ensureRunCanBeCalculated(
        PayrollRun $run
    ): void {
        if (
            !in_array(
                $run->status,
                [
                    PayrollRun::STATUS_DRAFT,
                    PayrollRun::STATUS_CALCULATED,
                    PayrollRun::STATUS_REVIEW,
                ],
                true
            )
        ) {
            throw new LogicException(
                'حالة تشغيل الرواتب لا تسمح بإعادة الحساب.'
            );
        }
    }


    private function ensureNoRegularRunExists(
        PayrollPeriod $period
    ): void {
        $exists =
            PayrollRun::withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $period->tenant_id
                )
                ->where(
                    'payroll_period_id',
                    $period->id
                )
                ->where(
                    'type',
                    PayrollRun::TYPE_REGULAR
                )
                ->where(
                    'status',
                    '!=',
                    PayrollRun::STATUS_CANCELLED
                )
                ->whereNull(
                    'deleted_at'
                )
                ->exists();

        if ($exists) {
            throw new LogicException(
                'يوجد تشغيل رواتب عادي لهذه الفترة مسبقًا.'
            );
        }
    }


    private function lockedRun(
        PayrollRun $payrollRun
    ): PayrollRun {
        return PayrollRun::withoutGlobalScopes()
            ->where(
                'tenant_id',
                $payrollRun->tenant_id
            )
            ->whereKey(
                $payrollRun->id
            )
            ->whereNull(
                'deleted_at'
            )
            ->lockForUpdate()
            ->firstOrFail();
    }


    private function authorizeActor(
        User $actor,
        int $tenantId,
        string $permission
    ): void {
        if (
            !$actor->tenant_id ||
            (int) $actor->tenant_id
                !== $tenantId
        ) {
            throw new AuthorizationException(
                'لا يمكنك إدارة بيانات شركة أخرى.'
            );
        }

        if (!$actor->can($permission)) {
            throw new AuthorizationException(
                'ليس لديك صلاحية لتنفيذ هذه العملية.'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function generateRunNumber(
        PayrollPeriod $period
    ): string {
        $prefix =
            $period->code
            . '-R';

        $lastNumber =
            PayrollRun::withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $period->tenant_id
                )
                ->where(
                    'payroll_period_id',
                    $period->id
                )
                ->lockForUpdate()
                ->count();

        $sequence =
            $lastNumber + 1;

        do {
            $runNumber =
                $prefix
                . str_pad(
                    (string) $sequence,
                    3,
                    '0',
                    STR_PAD_LEFT
                );

            $exists =
                PayrollRun::withoutGlobalScopes()
                    ->where(
                        'tenant_id',
                        $period->tenant_id
                    )
                    ->where(
                        'run_number',
                        $runNumber
                    )
                    ->withTrashed()
                    ->exists();

            $sequence++;
        } while ($exists);

        return $runNumber;
    }


    private function normalizeEmployeeIds(
        mixed $employeeIds
    ): array {
        if (!is_array($employeeIds)) {
            return [];
        }

        return array_values(
            array_unique(
                array_filter(
                    array_map(
                        'intval',
                        $employeeIds
                    )
                )
            )
        );
    }


    private function money(
        mixed $value
    ): float {
        return round(
            (float) $value,
            2
        );
    }
}