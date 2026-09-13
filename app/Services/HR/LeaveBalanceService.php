<?php

namespace App\Services\HR;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveBalanceTransaction;
use App\Models\LeaveType;
use App\Models\Tenant;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;

class LeaveBalanceService
{
    /*
    |--------------------------------------------------------------------------
    | Get Or Create
    |--------------------------------------------------------------------------
    */

    public function getOrCreate(
        Employee $employee,
        LeaveType $leaveType,
        ?int $year = null
    ): LeaveBalance {
        $this->ensureSameTenant(
            $employee,
            $leaveType
        );

        $year ??= (int) now()->format('Y');

        return DB::transaction(
            function () use (
                $employee,
                $leaveType,
                $year
            ) {
                $balance = LeaveBalance::query()
                    ->where(
                        'tenant_id',
                        $employee->tenant_id
                    )
                    ->where(
                        'employee_id',
                        $employee->id
                    )
                    ->where(
                        'leave_type_id',
                        $leaveType->id
                    )
                    ->where(
                        'year',
                        $year
                    )
                    ->lockForUpdate()
                    ->first();

                if (!$balance) {
                    $balance = LeaveBalance::create([
                        'tenant_id' =>
                            $employee->tenant_id,

                        'employee_id' =>
                            $employee->id,

                        'leave_type_id' =>
                            $leaveType->id,

                        'year' =>
                            $year,

                        'opening_balance' =>
                            0,

                        'accrued_balance' =>
                            0,

                        'carried_forward' =>
                            0,

                        'adjustment_balance' =>
                            0,

                        'used_balance' =>
                            0,

                        'pending_balance' =>
                            0,

                        'available_balance' =>
                            0,

                        'calculated_at' =>
                            now(),
                    ]);
                }

                return $this->synchronizeAccrual(
                    $balance
                );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Accrual
    |--------------------------------------------------------------------------
    */

    public function synchronizeAccrual(
        LeaveBalance $balance
    ): LeaveBalance {
        /*
         * الاستحقاق التلقائي معطل حاليًا.
         *
         * تتم إضافة أرصدة الإجازات يدويًا
         * من إدارة الموارد البشرية.
         */
        return $this->recalculate(
            $balance
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Reservation
    |--------------------------------------------------------------------------
    */

    public function reserve(
        LeaveBalance $balance,
        float $amount
    ): LeaveBalance {
        $amount = $this->normalizeAmount(
            $amount
        );

        return DB::transaction(
            function () use (
                $balance,
                $amount
            ) {
                $balance = LeaveBalance::query()
                    ->with('leaveType')
                    ->lockForUpdate()
                    ->findOrFail(
                        $balance->id
                    );

                if (
                    !$balance->leaveType
                        ?->requires_balance
                ) {
                    return $balance;
                }

                if (!$balance->canCover($amount)) {
                    throw new DomainException(
                        'رصيد الإجازة المتاح غير كافٍ.'
                    );
                }

                $balance->pending_balance = round(
                    (float) $balance->pending_balance +
                    $amount,
                    2
                );

                $this->setCalculatedBalance(
                    $balance
                );

                $balance->save();

                return $balance->refresh();
            }
        );
    }


    public function releaseReservation(
        LeaveBalance $balance,
        float $amount
    ): LeaveBalance {
        $amount = $this->normalizeAmount(
            $amount
        );

        return DB::transaction(
            function () use (
                $balance,
                $amount
            ) {
                $balance = LeaveBalance::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $balance->id
                    );

                $balance->pending_balance = max(
                    0,
                    round(
                        (float) $balance->pending_balance -
                        $amount,
                        2
                    )
                );

                $this->setCalculatedBalance(
                    $balance
                );

                $balance->save();

                return $balance->refresh();
            }
        );
    }


    public function consumeReservation(
        LeaveBalance $balance,
        float $amount,
        ?int $leaveRequestId = null
    ): LeaveBalance {
        $amount = $this->normalizeAmount(
            $amount
        );

        return DB::transaction(
            function () use (
                $balance,
                $amount,
                $leaveRequestId
            ) {
                $balance = LeaveBalance::query()
                    ->with('leaveType')
                    ->lockForUpdate()
                    ->findOrFail(
                        $balance->id
                    );

                if (
                    !$balance->leaveType
                        ?->requires_balance
                ) {
                    return $balance;
                }

                if (
                    (float) $balance->pending_balance <
                    $amount
                ) {
                    throw new DomainException(
                        'الرصيد المعلّق أقل من قيمة الإجازة المطلوبة.'
                    );
                }

                $balance->pending_balance = round(
                    (float) $balance->pending_balance -
                    $amount,
                    2
                );

                $balance->used_balance = round(
                    (float) $balance->used_balance +
                    $amount,
                    2
                );

                $this->setCalculatedBalance(
                    $balance
                );

                $balance->save();

                $this->recordTransaction(
                    balance: $balance,
                    type:
                        LeaveBalanceTransaction::TYPE_USAGE,
                    amount:
                        -$amount,
                    leaveRequestId:
                        $leaveRequestId,
                    notes:
                        'خصم رصيد طلب إجازة معتمد'
                );

                return $balance->refresh();
            }
        );
    }


    public function reverseUsage(
        LeaveBalance $balance,
        float $amount,
        ?int $leaveRequestId = null
    ): LeaveBalance {
        $amount = $this->normalizeAmount(
            $amount
        );

        return DB::transaction(
            function () use (
                $balance,
                $amount,
                $leaveRequestId
            ) {
                $balance = LeaveBalance::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $balance->id
                    );

                if (
                    (float) $balance->used_balance <
                    $amount
                ) {
                    throw new DomainException(
                        'لا يمكن إعادة مبلغ أكبر من الرصيد المستخدم.'
                    );
                }

                $balance->used_balance = round(
                    (float) $balance->used_balance -
                    $amount,
                    2
                );

                $this->setCalculatedBalance(
                    $balance
                );

                $balance->save();

                $this->recordTransaction(
                    balance: $balance,
                    type:
                        LeaveBalanceTransaction::TYPE_REVERSAL,
                    amount:
                        $amount,
                    leaveRequestId:
                        $leaveRequestId,
                    notes:
                        'إعادة رصيد طلب إجازة'
                );

                return $balance->refresh();
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Direct Adjustment
    |--------------------------------------------------------------------------
    */

    public function adjust(
        LeaveBalance $balance,
        float $amount,
        ?string $notes = null,
        ?int $createdBy = null
    ): LeaveBalance {
        $amount = round(
            $amount,
            2
        );

        if ($amount == 0) {
            throw new DomainException(
                'قيمة التسوية يجب ألا تساوي صفرًا.'
            );
        }

        return DB::transaction(
            function () use (
                $balance,
                $amount,
                $notes,
                $createdBy
            ) {
                $balance = LeaveBalance::query()
                    ->with('leaveType')
                    ->lockForUpdate()
                    ->findOrFail(
                        $balance->id
                    );

                $balance->adjustment_balance = round(
                    (float) $balance->adjustment_balance +
                    $amount,
                    2
                );

                $this->setCalculatedBalance(
                    $balance
                );

                if (
                    (float) $balance->available_balance < 0 &&
                    !$balance->leaveType
                        ?->allow_negative_balance
                ) {
                    throw new DomainException(
                        'التسوية ستجعل الرصيد المتاح سالبًا.'
                    );
                }

                $balance->save();

                $this->recordTransaction(
                    balance: $balance,
                    type:
                        LeaveBalanceTransaction::TYPE_ADJUSTMENT,
                    amount:
                        $amount,
                    notes:
                        $notes ?: 'تسوية يدوية للرصيد',
                    createdBy:
                        $createdBy
                );

                return $balance->refresh();
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Opening Balance
    |--------------------------------------------------------------------------
    */

    public function addOpeningBalance(
        LeaveBalance $balance,
        float $amount,
        ?string $notes = null,
        ?int $createdBy = null
    ): LeaveBalance {
        $amount = round(
            $amount,
            2
        );

        if ($amount == 0) {
            throw new DomainException(
                'قيمة الرصيد الافتتاحي يجب ألا تساوي صفرًا.'
            );
        }

        return DB::transaction(
            function () use (
                $balance,
                $amount,
                $notes,
                $createdBy
            ) {
                $balance = LeaveBalance::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $balance->id
                    );

                $balance->opening_balance = round(
                    (float) $balance->opening_balance +
                    $amount,
                    2
                );

                $this->setCalculatedBalance(
                    $balance
                );

                $balance->save();

                $this->recordTransaction(
                    balance: $balance,
                    type:
                        LeaveBalanceTransaction::TYPE_OPENING,
                    amount:
                        $amount,
                    notes:
                        $notes ?: 'إضافة رصيد افتتاحي',
                    createdBy:
                        $createdBy
                );

                return $balance->refresh();
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Carry Forward
    |--------------------------------------------------------------------------
    */

    public function carryForward(
        LeaveBalance $sourceBalance,
        LeaveBalance $targetBalance,
        ?int $createdBy = null,
        ?string $batchUuid = null,
        ?string $reason = null
    ): LeaveBalance {
        return DB::transaction(
            function () use (
                $sourceBalance,
                $targetBalance,
                $createdBy,
                $batchUuid,
                $reason
            ) {
                /*
                 * قفل رصيد السنة المصدر.
                 */
                $sourceBalance = LeaveBalance::query()
                    ->with('leaveType')
                    ->lockForUpdate()
                    ->findOrFail(
                        $sourceBalance->id
                    );


                /*
                 * قفل رصيد السنة المستهدفة.
                 */
                $targetBalance = LeaveBalance::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $targetBalance->id
                    );


                /*
                 * التحقق من تطابق الشركة.
                 */
                if (
                    (int) $sourceBalance->tenant_id !==
                    (int) $targetBalance->tenant_id
                ) {
                    throw new DomainException(
                        'لا يمكن ترحيل الرصيد بين شركتين مختلفتين.'
                    );
                }


                /*
                 * التحقق من تطابق الموظف.
                 */
                if (
                    (int) $sourceBalance->employee_id !==
                    (int) $targetBalance->employee_id
                ) {
                    throw new DomainException(
                        'لا يمكن ترحيل الرصيد بين موظفين مختلفين.'
                    );
                }


                /*
                 * التحقق من تطابق نوع الإجازة.
                 */
                if (
                    (int) $sourceBalance->leave_type_id !==
                    (int) $targetBalance->leave_type_id
                ) {
                    throw new DomainException(
                        'نوع الإجازة غير متطابق.'
                    );
                }


                /*
                 * لا يسمح بالترحيل إلا إلى السنة التالية.
                 */
                if (
                    (int) $targetBalance->year !==
                    (int) $sourceBalance->year + 1
                ) {
                    throw new DomainException(
                        'يجب أن تكون السنة المستهدفة هي السنة التالية مباشرة.'
                    );
                }


                $leaveType =
                    $sourceBalance->leaveType;


                if (
                    !$leaveType ||
                    !$leaveType->allow_carry_forward
                ) {
                    throw new DomainException(
                        'نوع الإجازة لا يسمح بترحيل الرصيد.'
                    );
                }


                /*
                 * منع إقفال الرصيد إذا توجد طلبات
                 * إجازة معلقة على السنة المصدر.
                 */
                if (
                    (float) $sourceBalance->pending_balance >
                    0
                ) {
                    throw ValidationException::withMessages([
                        'employee_ids' =>
                            'لا يمكن ترحيل الرصيد لوجود طلبات إجازة معلقة للموظف.',
                    ]);
                }


                $sourceAvailable = round(
                    max(
                        0,
                        (float) $sourceBalance
                            ->available_balance
                    ),
                    2
                );


                if ($sourceAvailable <= 0) {
                    throw ValidationException::withMessages([
                        'employee_ids' =>
                            'لا يوجد رصيد متاح للترحيل.',
                    ]);
                }


                /*
                 * تحديد الحد الأعلى المسموح بترحيله.
                 */
                $carryAmount =
                    $sourceAvailable;


                if (
                    $leaveType->maximum_carry_forward !==
                    null
                ) {
                    $carryAmount = min(
                        $carryAmount,
                        (float) $leaveType
                            ->maximum_carry_forward
                    );
                }


                $carryAmount = round(
                    max(
                        0,
                        $carryAmount
                    ),
                    2
                );


                /*
                 * الجزء غير القابل للترحيل ينتهي
                 * مع إقفال السنة المصدر.
                 */
                $expiredAmount = round(
                    $sourceAvailable -
                    $carryAmount,
                    2
                );


                /*
                 * إقفال الرصيد المتاح في السنة المصدر.
                 *
                 * نخصم كامل الرصيد المتاح:
                 * الجزء المرحل + الجزء المنتهي.
                 */
                $sourceBalance->adjustment_balance = round(
                    (float) $sourceBalance
                        ->adjustment_balance -
                    $sourceAvailable,
                    2
                );


                $this->setCalculatedBalance(
                    $sourceBalance
                );


                $sourceBalance->save();


                /*
                 * تسجيل الجزء الخارج للترحيل.
                 */
                if ($carryAmount > 0) {
                    $this->recordTransaction(
                        balance:
                            $sourceBalance,

                        type:
                            'carry_forward',

                        amount:
                            -$carryAmount,

                        notes:
                            'ترحيل رصيد إلى سنة ' .
                            $targetBalance->year,

                        createdBy:
                            $createdBy,

                        metadata: [
                            'source' =>
                                'manual_year_closing',

                            'direction' =>
                                'out',

                            'batch_uuid' =>
                                $batchUuid,
                            
                            'reason' =>
                                $reason,
                            
                            'source_year' =>
                                $sourceBalance->year,

                            'target_year' =>
                                $targetBalance->year,

                            'carry_amount' =>
                                $carryAmount,
                        ]
                    );
                }


                /*
                 * تسجيل الجزء المنتهي وغير القابل للترحيل.
                 */
                if ($expiredAmount > 0) {
                    $this->recordTransaction(
                        balance:
                            $sourceBalance,

                        type:
                            'expiry',

                        amount:
                            -$expiredAmount,

                        notes:
                            'انتهاء الرصيد غير القابل للترحيل عند إقفال السنة',

                        createdBy:
                            $createdBy,

                        metadata: [
                            'source' =>
                                'manual_year_closing',

                            'batch_uuid' =>
                                $batchUuid,
                            
                            'reason' =>
                                $reason,
                            
                            'source_year' =>
                                $sourceBalance->year,

                            'target_year' =>
                                $targetBalance->year,

                            'expired_amount' =>
                                $expiredAmount,
                        ]
                    );
                }


                /*
                 * إضافة الجزء المسموح إلى رصيد
                 * السنة الجديدة.
                 */
                if ($carryAmount > 0) {
                    $targetBalance->carried_forward = round(
                        (float) $targetBalance
                            ->carried_forward +
                        $carryAmount,
                        2
                    );


                    $this->setCalculatedBalance(
                        $targetBalance
                    );


                    $targetBalance->save();


                    $this->recordTransaction(
                        balance:
                            $targetBalance,

                        type:
                            'carry_forward',

                        amount:
                            $carryAmount,

                        notes:
                            'رصيد مرحّل من سنة ' .
                            $sourceBalance->year,

                        createdBy:
                            $createdBy,

                        metadata: [
                            'source' =>
                                'manual_year_closing',

                            'direction' =>
                                'in',

                            'batch_uuid' =>
                                $batchUuid,
                            
                            'reason' =>
                                $reason,
                            
                            'source_year' =>
                                $sourceBalance->year,

                            'target_year' =>
                                $targetBalance->year,

                            'carry_amount' =>
                                $carryAmount,
                        ]
                    );
                }


                return $targetBalance->refresh();
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Manual Adjustment
    |--------------------------------------------------------------------------
    */

    public function applyManualAdjustment(
        Tenant $tenant,
        Employee $employee,
        LeaveType $leaveType,
        int $year,
        string $mode,
        float $days,
        string $reason,
        User $actor,
        ?string $batchUuid = null
    ): LeaveBalance {
        $this->validateManualAdjustment(
            tenant: $tenant,
            employee: $employee,
            leaveType: $leaveType,
            year: $year,
            mode: $mode,
            days: $days,
            reason: $reason,
            actor: $actor
        );

        $days = round(
            $days,
            2
        );

        $reason = trim(
            $reason
        );

        return DB::transaction(
            function () use (
                $tenant,
                $employee,
                $leaveType,
                $year,
                $mode,
                $days,
                $reason,
                $actor,
                $batchUuid
            ) {
                $currentBalance =
                    $this->getOrCreate(
                        $employee,
                        $leaveType,
                        $year
                    );

                $balance = LeaveBalance::query()
                    ->with('leaveType')
                    ->where(
                        'tenant_id',
                        $tenant->id
                    )
                    ->whereKey(
                        $currentBalance->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $balanceBefore = round(
                    (float) $balance
                        ->available_balance,
                    2
                );

                $adjustmentBefore = round(
                    (float) $balance
                        ->adjustment_balance,
                    2
                );

                $adjustmentAmount = match ($mode) {
                    'add' =>
                        $days,

                    'deduct' =>
                        -$days,

                    'set' =>
                        round(
                            $days - $balanceBefore,
                            2
                        ),
                };

                /*
                 * إذا كان التعيين مساويًا للرصيد الحالي،
                 * لا ننشئ حركة بقيمة صفر.
                 */
                if (
                    abs($adjustmentAmount) <
                    0.01
                ) {
                    return $balance->refresh();
                }

                $expectedBalance = round(
                    $balanceBefore +
                    $adjustmentAmount,
                    2
                );

                if (
                    $expectedBalance < 0 &&
                    !$leaveType->allow_negative_balance
                ) {
                    throw ValidationException::withMessages([
                        'days' =>
                            'لا يمكن خصم هذا العدد من الأيام؛ الرصيد المتاح للموظف هو ' .
                            number_format(
                                $balanceBefore,
                                2
                            ) .
                            ' يوم.',
                    ]);
                }

                $newAdjustmentBalance = round(
                    $adjustmentBefore +
                    $adjustmentAmount,
                    2
                );

                $balance->adjustment_balance =
                    $newAdjustmentBalance;

                $this->setCalculatedBalance(
                    $balance
                );

                $balance->save();

                $balanceAfter = round(
                    (float) $balance
                        ->available_balance,
                    2
                );

                $this->recordTransaction(
                    balance: $balance,
                    type:
                        LeaveBalanceTransaction::TYPE_ADJUSTMENT,
                    amount:
                        $adjustmentAmount,
                    notes:
                        $reason,
                    createdBy:
                        $actor->id,
                    metadata: [
                        'source' =>
                            'manual_admin_adjustment',

                        'batch_uuid' =>
                            $batchUuid,

                        'mode' =>
                            $mode,

                        'balance_before' =>
                            $balanceBefore,

                        'balance_after' =>
                            $balanceAfter,

                        'adjustment_before' =>
                            $adjustmentBefore,

                        'adjustment_after' =>
                            $newAdjustmentBalance,

                        'employee_id' =>
                            $employee->id,

                        'leave_type_id' =>
                            $leaveType->id,

                        'year' =>
                            $year,
                    ],
                    effectiveDate:
                        now(
                            $tenant->timezone ??
                            config(
                                'app.timezone',
                                'UTC'
                            )
                        )->toDateString()
                );

                return $balance->fresh([
                    'leaveType',
                ]);
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Bulk Manual Adjustment
    |--------------------------------------------------------------------------
    */

    public function applyBulkManualAdjustment(
        Tenant $tenant,
        LeaveType $leaveType,
        array $employeeIds,
        int $year,
        string $mode,
        float $days,
        string $reason,
        User $actor
    ): array {
        if (
            (int) $leaveType->tenant_id !==
            (int) $tenant->id
        ) {
            throw new LogicException(
                'نوع الإجازة لا يتبع الشركة المحددة.'
            );
        }

        if (!$leaveType->requires_balance) {
            throw ValidationException::withMessages([
                'leave_type_id' =>
                    'نوع الإجازة المحدد لا يعتمد على رصيد.',
            ]);
        }

        if (
            (int) $actor->tenant_id !==
            (int) $tenant->id
        ) {
            throw new LogicException(
                'المستخدم لا يتبع الشركة المحددة.'
            );
        }

        $employeeIds = array_values(
            array_unique(
                array_map(
                    'intval',
                    $employeeIds
                )
            )
        );

        if (empty($employeeIds)) {
            throw ValidationException::withMessages([
                'employee_ids' =>
                    'يجب اختيار موظف واحد على الأقل.',
            ]);
        }

        if (count($employeeIds) > 500) {
            throw ValidationException::withMessages([
                'employee_ids' =>
                    'لا يمكن تنفيذ العملية على أكثر من 500 موظف دفعة واحدة.',
            ]);
        }

        return DB::transaction(
            function () use (
                $tenant,
                $leaveType,
                $employeeIds,
                $year,
                $mode,
                $days,
                $reason,
                $actor
            ) {
                /*
                 * الترتيب حسب ID يقلل احتمالية
                 * تعارض قفل السجلات عند التنفيذ المتزامن.
                 */
                $employees = Employee::query()
                    ->where(
                        'tenant_id',
                        $tenant->id
                    )
                    ->whereIn(
                        'id',
                        $employeeIds
                    )
                    ->orderBy('id')
                    ->get();

                if (
                    $employees->count() !==
                    count($employeeIds)
                ) {
                    throw ValidationException::withMessages([
                        'employee_ids' =>
                            'بعض الموظفين غير موجودين أو لا يتبعون الشركة.',
                    ]);
                }

                $batchUuid =
                    (string) Str::uuid();

                $processedEmployeeIds = [];

                foreach ($employees as $employee) {
                    $this->applyManualAdjustment(
                        tenant: $tenant,
                        employee: $employee,
                        leaveType: $leaveType,
                        year: $year,
                        mode: $mode,
                        days: $days,
                        reason: $reason,
                        actor: $actor,
                        batchUuid: $batchUuid
                    );

                    $processedEmployeeIds[] =
                        $employee->id;
                }

                return [
                    'batch_uuid' =>
                        $batchUuid,

                    'processed_count' =>
                        count(
                            $processedEmployeeIds
                        ),

                    'employee_ids' =>
                        $processedEmployeeIds,
                ];
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Bulk Carry Forward
    |--------------------------------------------------------------------------
    */

    public function bulkCarryForward(
        Tenant $tenant,
        LeaveType $leaveType,
        array $employeeIds,
        int $sourceYear,
        int $targetYear,
        string $reason,
        User $actor
    ): array {
        if (
            (int) $leaveType->tenant_id !==
            (int) $tenant->id
        ) {
            throw new LogicException(
                'نوع الإجازة لا يتبع الشركة المحددة.'
            );
        }


        if (!$leaveType->requires_balance) {
            throw ValidationException::withMessages([
                'leave_type_id' =>
                    'نوع الإجازة المحدد لا يعتمد على رصيد.',
            ]);
        }


        if (!$leaveType->allow_carry_forward) {
            throw ValidationException::withMessages([
                'leave_type_id' =>
                    'نوع الإجازة المحدد لا يسمح بترحيل الرصيد.',
            ]);
        }


        if (
            (int) $actor->tenant_id !==
            (int) $tenant->id
        ) {
            throw new LogicException(
                'المستخدم لا يتبع الشركة المحددة.'
            );
        }


        if (
            $targetYear !==
            $sourceYear + 1
        ) {
            throw ValidationException::withMessages([
                'target_year' =>
                    'يجب أن تكون السنة المستهدفة هي السنة التالية مباشرة.',
            ]);
        }


        $reason = trim(
            $reason
        );


        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' =>
                    'سبب الترحيل مطلوب.',
            ]);
        }


        $employeeIds = array_values(
            array_unique(
                array_map(
                    'intval',
                    $employeeIds
                )
            )
        );


        if (empty($employeeIds)) {
            throw ValidationException::withMessages([
                'employee_ids' =>
                    'يجب اختيار موظف واحد على الأقل.',
            ]);
        }


        if (count($employeeIds) > 500) {
            throw ValidationException::withMessages([
                'employee_ids' =>
                    'لا يمكن معالجة أكثر من 500 موظف دفعة واحدة.',
            ]);
        }


        return DB::transaction(
            function () use (
                $tenant,
                $leaveType,
                $employeeIds,
                $sourceYear,
                $targetYear,
                $reason,
                $actor
            ) {
                $employees = Employee::query()
                    ->where(
                        'tenant_id',
                        $tenant->id
                    )
                    ->whereIn(
                        'id',
                        $employeeIds
                    )
                    ->orderBy('id')
                    ->get();


                if (
                    $employees->count() !==
                    count($employeeIds)
                ) {
                    throw ValidationException::withMessages([
                        'employee_ids' =>
                            'بعض الموظفين غير موجودين أو لا يتبعون الشركة.',
                    ]);
                }


                $batchUuid =
                    (string) Str::uuid();


                $processedEmployeeIds = [];

                $skippedEmployeeIds = [];


                foreach ($employees as $employee) {
                    /*
                     * البحث عن رصيد السنة المصدر.
                     */
                    $sourceBalance = LeaveBalance::query()
                        ->where(
                            'tenant_id',
                            $tenant->id
                        )
                        ->where(
                            'employee_id',
                            $employee->id
                        )
                        ->where(
                            'leave_type_id',
                            $leaveType->id
                        )
                        ->where(
                            'year',
                            $sourceYear
                        )
                        ->first();


                    /*
                     * عدم وجود رصيد أو وجود رصيد غير موجب
                     * لا يعتبر خطأ؛ يتم تجاوز الموظف.
                     */
                    if (
                        !$sourceBalance ||
                        (float) $sourceBalance
                            ->available_balance <= 0
                    ) {
                        $skippedEmployeeIds[] =
                            $employee->id;

                        continue;
                    }


                    /*
                     * وجود طلبات معلقة يمنع إقفال العملية
                     * كاملة حتى تتم معالجة الطلبات.
                     */
                    if (
                        (float) $sourceBalance
                            ->pending_balance > 0
                    ) {
                        throw ValidationException::withMessages([
                            'employee_ids' =>
                                'لا يمكن تنفيذ الترحيل لأن الموظف ' .
                                $this->employeeDisplayName(
                                    $employee
                                ) .
                                ' لديه طلبات إجازة معلقة.',
                        ]);
                    }


                    /*
                     * إنشاء رصيد السنة المستهدفة إذا
                     * لم يكن موجودًا.
                     */
                    $targetBalance = $this->getOrCreate(
                        employee:
                            $employee,

                        leaveType:
                            $leaveType,

                        year:
                            $targetYear
                    );


                    /*
                     * تنفيذ إقفال وترحيل الرصيد.
                     */
                    $this->carryForward(
                        sourceBalance:
                            $sourceBalance,

                        targetBalance:
                            $targetBalance,

                        createdBy:
                            $actor->id,

                        batchUuid:
                            $batchUuid,

                        reason:
                            $reason
                    );


                    $processedEmployeeIds[] =
                        $employee->id;
                }


                /*
                 * لا توجد أي أرصدة قابلة للترحيل.
                 */
                if (empty($processedEmployeeIds)) {
                    throw ValidationException::withMessages([
                        'employee_ids' =>
                            'لا يوجد رصيد متاح قابل للترحيل للموظفين المحددين.',
                    ]);
                }


                return [
                    'batch_uuid' =>
                        $batchUuid,

                    'processed_count' =>
                        count(
                            $processedEmployeeIds
                        ),

                    'skipped_count' =>
                        count(
                            $skippedEmployeeIds
                        ),

                    'processed_employee_ids' =>
                        $processedEmployeeIds,

                    'skipped_employee_ids' =>
                        $skippedEmployeeIds,
                ];
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Recalculate
    |--------------------------------------------------------------------------
    */

    public function recalculate(
        LeaveBalance $balance
    ): LeaveBalance {
        $this->setCalculatedBalance(
            $balance
        );

        $balance->save();

        return $balance->refresh();
    }


    /*
    |--------------------------------------------------------------------------
    | Private Helpers
    |--------------------------------------------------------------------------
    */

    private function employeeDisplayName(
        Employee $employee
    ): string {
        return collect([
            $employee->first_name,
            $employee->father_name,
            $employee->grandfather_name,
            $employee->family_name,
        ])
            ->filter()
            ->implode(' ');
    }
    
    private function setCalculatedBalance(
        LeaveBalance $balance
    ): void {
        $balance->available_balance =
            $balance->calculateAvailableBalance();

        $balance->calculated_at =
            now();
    }


    private function recordTransaction(
        LeaveBalance $balance,
        string $type,
        float $amount,
        ?int $leaveRequestId = null,
        ?string $notes = null,
        ?int $createdBy = null,
        ?array $metadata = null,
        ?string $effectiveDate = null
    ): LeaveBalanceTransaction {
        return LeaveBalanceTransaction::create([
            'uuid' =>
                (string) Str::uuid(),

            'tenant_id' =>
                $balance->tenant_id,

            'leave_balance_id' =>
                $balance->id,

            'leave_request_id' =>
                $leaveRequestId,

            'type' =>
                $type,

            'amount' =>
                round(
                    $amount,
                    2
                ),

            'balance_after' =>
                round(
                    (float) $balance
                        ->available_balance,
                    2
                ),

            'effective_date' =>
                $effectiveDate ??
                now()->toDateString(),

            'notes' =>
                $notes,

            'created_by' =>
                $createdBy ??
                auth()->id(),

            'metadata' =>
                $metadata,
        ]);
    }


    private function normalizeAmount(
        float $amount
    ): float {
        $amount = round(
            $amount,
            2
        );

        if ($amount <= 0) {
            throw new DomainException(
                'القيمة المطلوبة يجب أن تكون أكبر من صفر.'
            );
        }

        return $amount;
    }


    private function ensureSameTenant(
        Employee $employee,
        LeaveType $leaveType
    ): void {
        if (
            (int) $employee->tenant_id !==
            (int) $leaveType->tenant_id
        ) {
            throw new DomainException(
                'الموظف ونوع الإجازة لا يتبعان الشركة نفسها.'
            );
        }
    }


    private function validateManualAdjustment(
        Tenant $tenant,
        Employee $employee,
        LeaveType $leaveType,
        int $year,
        string $mode,
        float $days,
        string $reason,
        User $actor
    ): void {
        if (
            (int) $employee->tenant_id !==
            (int) $tenant->id
        ) {
            throw new LogicException(
                'الموظف لا يتبع الشركة المحددة.'
            );
        }

        if (
            (int) $leaveType->tenant_id !==
            (int) $tenant->id
        ) {
            throw new LogicException(
                'نوع الإجازة لا يتبع الشركة المحددة.'
            );
        }

        if (
            (int) $actor->tenant_id !==
            (int) $tenant->id
        ) {
            throw new LogicException(
                'المستخدم لا يتبع الشركة المحددة.'
            );
        }

        if (!$leaveType->requires_balance) {
            throw ValidationException::withMessages([
                'leave_type_id' =>
                    'نوع الإجازة المحدد لا يعتمد على رصيد.',
            ]);
        }

        if (
            $year < 2020 ||
            $year > 2100
        ) {
            throw ValidationException::withMessages([
                'year' =>
                    'سنة الرصيد غير صحيحة.',
            ]);
        }

        if (
            !in_array(
                $mode,
                [
                    'add',
                    'deduct',
                    'set',
                ],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'mode' =>
                    'نوع تسوية الرصيد غير صحيح.',
            ]);
        }

        $days = round(
            $days,
            2
        );

        if ($days < 0) {
            throw ValidationException::withMessages([
                'days' =>
                    'عدد الأيام لا يمكن أن يكون سالبًا.',
            ]);
        }

        if (
            in_array(
                $mode,
                [
                    'add',
                    'deduct',
                ],
                true
            ) &&
            $days <= 0
        ) {
            throw ValidationException::withMessages([
                'days' =>
                    'عدد الأيام يجب أن يكون أكبر من صفر.',
            ]);
        }

        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'reason' =>
                    'سبب التسوية مطلوب.',
            ]);
        }
    }
}