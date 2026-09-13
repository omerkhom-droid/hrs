<?php

namespace App\Services\HR;

use App\Models\Employee;
use App\Models\PayrollAdjustment;
use App\Models\PayrollPeriod;
use App\Models\SalaryComponent;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

class PayrollAdjustmentService
{
    /*
    |--------------------------------------------------------------------------
    | إنشاء التسوية
    |--------------------------------------------------------------------------
    */

    public function create(
        array $data,
        User $actor
    ): PayrollAdjustment {
        $tenantId =
            (int) $actor->tenant_id;

        $this->authorizeActor(
            $actor,
            $tenantId,
            'payroll.manage'
        );

        return DB::transaction(
            function () use (
                $data,
                $actor,
                $tenantId
            ) {
                $this->validateReferences(
                    $data,
                    $tenantId
                );

                $effectiveDate =
                    Carbon::parse(
                        $data['effective_date']
                    )->toDateString();

                $metadata =
                    $this->appendHistory(
                        [],
                        'created',
                        $actor,
                        [
                            'source' =>
                                'web',
                        ]
                    );

                $adjustment =
                    PayrollAdjustment::create([
                        'uuid' =>
                            (string) Str::uuid(),

                        'tenant_id' =>
                            $tenantId,

                        'employee_id' =>
                            $data['employee_id'],

                        'payroll_period_id' =>
                            $data[
                                'payroll_period_id'
                            ] ?? null,

                        'salary_component_id' =>
                            $data[
                                'salary_component_id'
                            ] ?? null,

                        'applied_payroll_item_id' =>
                            null,

                        'adjustment_number' =>
                            $this->generateAdjustmentNumber(
                                $tenantId,
                                $effectiveDate
                            ),

                        'type' =>
                            $data['type'],

                        'amount' =>
                            $this->money(
                                $data['amount']
                            ),

                        'currency_code' =>
                            strtoupper(
                                $data['currency_code']
                            ),

                        'effective_date' =>
                            $effectiveDate,

                        'status' =>
                            PayrollAdjustment::STATUS_DRAFT,

                        'reason' =>
                            $data['reason'],

                        'notes' =>
                            $data['notes']
                            ?? null,

                        'approved_by' =>
                            null,

                        'approved_at' =>
                            null,

                        'created_by' =>
                            $actor->id,

                        'metadata' =>
                            $metadata,
                    ]);

                return $this->loadRelations(
                    $adjustment->refresh()
                );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | تعديل التسوية
    |--------------------------------------------------------------------------
    */

    public function update(
        PayrollAdjustment $payrollAdjustment,
        array $data,
        User $actor
    ): PayrollAdjustment {
        $this->authorizeActor(
            $actor,
            (int) $payrollAdjustment->tenant_id,
            'payroll.manage'
        );

        return DB::transaction(
            function () use (
                $payrollAdjustment,
                $data,
                $actor
            ) {
                $adjustment =
                    $this->lockedAdjustment(
                        $payrollAdjustment
                    );

                $this->ensureDraft(
                    $adjustment
                );

                $this->ensureNotApplied(
                    $adjustment
                );

                $this->validateReferences(
                    $data,
                    (int) $adjustment->tenant_id
                );

                $metadata =
                    $this->appendHistory(
                        $adjustment->metadata
                        ?? [],
                        'updated',
                        $actor
                    );

                $adjustment->forceFill([
                    'employee_id' =>
                        $data['employee_id'],

                    'payroll_period_id' =>
                        $data[
                            'payroll_period_id'
                        ] ?? null,

                    'salary_component_id' =>
                        $data[
                            'salary_component_id'
                        ] ?? null,

                    'type' =>
                        $data['type'],

                    'amount' =>
                        $this->money(
                            $data['amount']
                        ),

                    'currency_code' =>
                        strtoupper(
                            $data['currency_code']
                        ),

                    'effective_date' =>
                        Carbon::parse(
                            $data['effective_date']
                        )->toDateString(),

                    'reason' =>
                        $data['reason'],

                    'notes' =>
                        $data['notes']
                        ?? null,

                    'metadata' =>
                        $metadata,
                ])->save();

                return $this->loadRelations(
                    $adjustment->refresh()
                );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | إرسال التسوية للاعتماد
    |--------------------------------------------------------------------------
    */

    public function submit(
        PayrollAdjustment $payrollAdjustment,
        User $actor
    ): PayrollAdjustment {
        $this->authorizeActor(
            $actor,
            (int) $payrollAdjustment->tenant_id,
            'payroll.manage'
        );

        return DB::transaction(
            function () use (
                $payrollAdjustment,
                $actor
            ) {
                $adjustment =
                    $this->lockedAdjustment(
                        $payrollAdjustment
                    );

                $this->ensureDraft(
                    $adjustment
                );

                $this->ensureNotApplied(
                    $adjustment
                );

                $this->validatePeriodStillAvailable(
                    $adjustment
                );

                $metadata =
                    $this->appendHistory(
                        $adjustment->metadata
                        ?? [],
                        'submitted',
                        $actor
                    );

                $metadata['submission'] = [
                    'submitted_at' =>
                        now()->toIso8601String(),

                    'submitted_by' =>
                        $actor->id,
                ];

                $adjustment->forceFill([
                    'status' =>
                        PayrollAdjustment::STATUS_PENDING,

                    'metadata' =>
                        $metadata,
                ])->save();

                return $this->loadRelations(
                    $adjustment->refresh()
                );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | اعتماد التسوية
    |--------------------------------------------------------------------------
    */

    public function approve(
        PayrollAdjustment $payrollAdjustment,
        User $actor
    ): PayrollAdjustment {
        $this->authorizeActor(
            $actor,
            (int) $payrollAdjustment->tenant_id,
            'payroll.approve'
        );

        return DB::transaction(
            function () use (
                $payrollAdjustment,
                $actor
            ) {
                $adjustment =
                    $this->lockedAdjustment(
                        $payrollAdjustment
                    );

                if (
                    $adjustment->status !==
                    PayrollAdjustment::STATUS_PENDING
                ) {
                    throw new LogicException(
                        'يمكن اعتماد التسوية عندما تكون بانتظار الاعتماد فقط.'
                    );
                }

                $this->ensureNotApplied(
                    $adjustment
                );

                $this->validatePeriodStillAvailable(
                    $adjustment
                );

                $metadata =
                    $this->appendHistory(
                        $adjustment->metadata
                        ?? [],
                        'approved',
                        $actor
                    );

                $adjustment->forceFill([
                    'status' =>
                        PayrollAdjustment::STATUS_APPROVED,

                    'approved_by' =>
                        $actor->id,

                    'approved_at' =>
                        now(),

                    'metadata' =>
                        $metadata,
                ])->save();

                return $this->loadRelations(
                    $adjustment->refresh()
                );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | رفض التسوية
    |--------------------------------------------------------------------------
    */

    public function reject(
        PayrollAdjustment $payrollAdjustment,
        User $actor,
        string $reason
    ): PayrollAdjustment {
        $this->authorizeActor(
            $actor,
            (int) $payrollAdjustment->tenant_id,
            'payroll.approve'
        );

        $reason =
            trim($reason);

        if ($reason === '') {
            throw new LogicException(
                'سبب رفض التسوية مطلوب.'
            );
        }

        return DB::transaction(
            function () use (
                $payrollAdjustment,
                $actor,
                $reason
            ) {
                $adjustment =
                    $this->lockedAdjustment(
                        $payrollAdjustment
                    );

                if (
                    $adjustment->status !==
                    PayrollAdjustment::STATUS_PENDING
                ) {
                    throw new LogicException(
                        'يمكن رفض التسوية عندما تكون بانتظار الاعتماد فقط.'
                    );
                }

                $this->ensureNotApplied(
                    $adjustment
                );

                $metadata =
                    $this->appendHistory(
                        $adjustment->metadata
                        ?? [],
                        'rejected',
                        $actor,
                        [
                            'reason' =>
                                $reason,
                        ]
                    );

                $metadata['rejection'] = [
                    'rejected_at' =>
                        now()->toIso8601String(),

                    'rejected_by' =>
                        $actor->id,

                    'reason' =>
                        $reason,
                ];

                $adjustment->forceFill([
                    'status' =>
                        PayrollAdjustment::STATUS_REJECTED,

                    'approved_by' =>
                        null,

                    'approved_at' =>
                        null,

                    'metadata' =>
                        $metadata,
                ])->save();

                return $this->loadRelations(
                    $adjustment->refresh()
                );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | إعادة التسوية المرفوضة إلى مسودة
    |--------------------------------------------------------------------------
    */

    public function returnToDraft(
        PayrollAdjustment $payrollAdjustment,
        User $actor
    ): PayrollAdjustment {
        $this->authorizeActor(
            $actor,
            (int) $payrollAdjustment->tenant_id,
            'payroll.manage'
        );

        return DB::transaction(
            function () use (
                $payrollAdjustment,
                $actor
            ) {
                $adjustment =
                    $this->lockedAdjustment(
                        $payrollAdjustment
                    );

                if (
                    $adjustment->status !==
                    PayrollAdjustment::STATUS_REJECTED
                ) {
                    throw new LogicException(
                        'يمكن إعادة التسوية إلى المسودة بعد رفضها فقط.'
                    );
                }

                $this->ensureNotApplied(
                    $adjustment
                );

                $metadata =
                    $this->appendHistory(
                        $adjustment->metadata
                        ?? [],
                        'returned_to_draft',
                        $actor
                    );

                $adjustment->forceFill([
                    'status' =>
                        PayrollAdjustment::STATUS_DRAFT,

                    'approved_by' =>
                        null,

                    'approved_at' =>
                        null,

                    'metadata' =>
                        $metadata,
                ])->save();

                return $this->loadRelations(
                    $adjustment->refresh()
                );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | إلغاء التسوية
    |--------------------------------------------------------------------------
    */

    public function cancel(
        PayrollAdjustment $payrollAdjustment,
        User $actor,
        string $reason
    ): PayrollAdjustment {
        $this->authorizeActor(
            $actor,
            (int) $payrollAdjustment->tenant_id,
            'payroll.manage'
        );

        $reason =
            trim($reason);

        if ($reason === '') {
            throw new LogicException(
                'سبب إلغاء التسوية مطلوب.'
            );
        }

        return DB::transaction(
            function () use (
                $payrollAdjustment,
                $actor,
                $reason
            ) {
                $adjustment =
                    $this->lockedAdjustment(
                        $payrollAdjustment
                    );

                $this->ensureNotApplied(
                    $adjustment
                );

                if (
                    in_array(
                        $adjustment->status,
                        [
                            PayrollAdjustment::STATUS_CANCELLED,
                            PayrollAdjustment::STATUS_APPLIED,
                        ],
                        true
                    )
                ) {
                    throw new LogicException(
                        'حالة التسوية لا تسمح بالإلغاء.'
                    );
                }

                $metadata =
                    $this->appendHistory(
                        $adjustment->metadata
                        ?? [],
                        'cancelled',
                        $actor,
                        [
                            'reason' =>
                                $reason,
                        ]
                    );

                $metadata['cancellation'] = [
                    'cancelled_at' =>
                        now()->toIso8601String(),

                    'cancelled_by' =>
                        $actor->id,

                    'reason' =>
                        $reason,
                ];

                $adjustment->forceFill([
                    'status' =>
                        PayrollAdjustment::STATUS_CANCELLED,

                    'metadata' =>
                        $metadata,
                ])->save();

                return $this->loadRelations(
                    $adjustment->refresh()
                );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | حذف التسوية
    |--------------------------------------------------------------------------
    */

    public function delete(
        PayrollAdjustment $payrollAdjustment,
        User $actor
    ): void {
        $this->authorizeActor(
            $actor,
            (int) $payrollAdjustment->tenant_id,
            'payroll.manage'
        );

        DB::transaction(
            function () use (
                $payrollAdjustment
            ) {
                $adjustment =
                    $this->lockedAdjustment(
                        $payrollAdjustment
                    );

                $this->ensureDraft(
                    $adjustment
                );

                $this->ensureNotApplied(
                    $adjustment
                );

                $adjustment->delete();
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | التحقق من العلاقات
    |--------------------------------------------------------------------------
    */

    private function validateReferences(
        array $data,
        int $tenantId
    ): void {
        $employee =
            Employee::withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $tenantId
                )
                ->whereKey(
                    $data['employee_id']
                )
                ->whereNull(
                    'deleted_at'
                )
                ->first();

        if (!$employee) {
            throw new LogicException(
                'الموظف المحدد غير موجود.'
            );
        }

        $period = null;

        if (
            !empty(
                $data['payroll_period_id']
            )
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
                    ->first();

            if (!$period) {
                throw new LogicException(
                    'فترة الرواتب المحددة غير موجودة.'
                );
            }

            $this->ensurePeriodAvailable(
                $period
            );

            $effectiveDate =
                Carbon::parse(
                    $data['effective_date']
                )->startOfDay();

            if (
                $effectiveDate->lt(
                    $period
                        ->start_date
                        ->copy()
                        ->startOfDay()
                )
                ||
                $effectiveDate->gt(
                    $period
                        ->end_date
                        ->copy()
                        ->endOfDay()
                )
            ) {
                throw new LogicException(
                    'تاريخ التسوية يجب أن يكون داخل فترة الرواتب.'
                );
            }
        }

        if (
            !empty(
                $data['salary_component_id']
            )
        ) {
            $component =
                SalaryComponent::withoutGlobalScopes()
                    ->where(
                        'tenant_id',
                        $tenantId
                    )
                    ->whereKey(
                        $data['salary_component_id']
                    )
                    ->whereNull(
                        'deleted_at'
                    )
                    ->first();

            if (!$component) {
                throw new LogicException(
                    'مكون الراتب المحدد غير موجود.'
                );
            }

            if (!$component->is_active) {
                throw new LogicException(
                    'مكون الراتب المحدد غير نشط.'
                );
            }

            if (
                $component->type !==
                $data['type']
            ) {
                throw new LogicException(
                    'نوع مكون الراتب لا يطابق نوع التسوية.'
                );
            }

            if (
                !$component
                    ->affects_net_salary
            ) {
                throw new LogicException(
                    'مكون الراتب المحدد لا يؤثر على صافي الراتب.'
                );
            }
        }
    }


    private function validatePeriodStillAvailable(
        PayrollAdjustment $adjustment
    ): void {
        if (
            !$adjustment
                ->payroll_period_id
        ) {
            return;
        }

        $period =
            PayrollPeriod::withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $adjustment->tenant_id
                )
                ->whereKey(
                    $adjustment
                        ->payroll_period_id
                )
                ->whereNull(
                    'deleted_at'
                )
                ->first();

        if (!$period) {
            throw new LogicException(
                'فترة الرواتب المرتبطة بالتسوية غير موجودة.'
            );
        }

        $this->ensurePeriodAvailable(
            $period
        );
    }


    private function ensurePeriodAvailable(
        PayrollPeriod $period
    ): void {
        if ($period->is_locked) {
            throw new LogicException(
                'فترة الرواتب مقفلة.'
            );
        }

        if (
            in_array(
                $period->status,
                [
                    PayrollPeriod::STATUS_PAID,
                    PayrollPeriod::STATUS_CLOSED,
                    PayrollPeriod::STATUS_CANCELLED,
                ],
                true
            )
        ) {
            throw new LogicException(
                'حالة فترة الرواتب لا تسمح بإضافة أو اعتماد التسوية.'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | التحقق من الحالة
    |--------------------------------------------------------------------------
    */

    private function ensureDraft(
        PayrollAdjustment $adjustment
    ): void {
        if (
            $adjustment->status !==
            PayrollAdjustment::STATUS_DRAFT
        ) {
            throw new LogicException(
                'يمكن تنفيذ العملية على التسوية عندما تكون مسودة فقط.'
            );
        }
    }


    private function ensureNotApplied(
        PayrollAdjustment $adjustment
    ): void {
        if (
            $adjustment->status ===
                PayrollAdjustment::STATUS_APPLIED
            ||
            $adjustment
                ->applied_payroll_item_id
                !== null
        ) {
            throw new LogicException(
                'لا يمكن تعديل تسوية تم تطبيقها على تشغيل الرواتب.'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | صلاحيات الشركة
    |--------------------------------------------------------------------------
    */

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
                'لا يمكنك إدارة تسويات شركة أخرى.'
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
    | جلب التسوية مع القفل
    |--------------------------------------------------------------------------
    */

    private function lockedAdjustment(
        PayrollAdjustment $payrollAdjustment
    ): PayrollAdjustment {
        return PayrollAdjustment::withoutGlobalScopes()
            ->where(
                'tenant_id',
                $payrollAdjustment->tenant_id
            )
            ->whereKey(
                $payrollAdjustment->id
            )
            ->whereNull(
                'deleted_at'
            )
            ->lockForUpdate()
            ->firstOrFail();
    }


    /*
    |--------------------------------------------------------------------------
    | توليد رقم التسوية
    |--------------------------------------------------------------------------
    */

    private function generateAdjustmentNumber(
        int $tenantId,
        string $effectiveDate
    ): string {
        $year =
            Carbon::parse(
                $effectiveDate
            )->year;

        $prefix =
            'ADJ-'
            . $year
            . '-';

        $sequence =
            PayrollAdjustment::withoutGlobalScopes()
                ->withTrashed()
                ->where(
                    'tenant_id',
                    $tenantId
                )
                ->where(
                    'adjustment_number',
                    'like',
                    $prefix . '%'
                )
                ->lockForUpdate()
                ->count() + 1;

        do {
            $number =
                $prefix
                . str_pad(
                    (string) $sequence,
                    6,
                    '0',
                    STR_PAD_LEFT
                );

            $exists =
                PayrollAdjustment::withoutGlobalScopes()
                    ->withTrashed()
                    ->where(
                        'tenant_id',
                        $tenantId
                    )
                    ->where(
                        'adjustment_number',
                        $number
                    )
                    ->exists();

            $sequence++;
        } while ($exists);

        return $number;
    }


    /*
    |--------------------------------------------------------------------------
    | سجل العمليات
    |--------------------------------------------------------------------------
    */

    private function appendHistory(
        array $metadata,
        string $event,
        User $actor,
        array $details = []
    ): array {
        $history =
            $metadata['history']
            ?? [];

        $history[] = array_merge(
            [
                'event' =>
                    $event,

                'performed_at' =>
                    now()->toIso8601String(),

                'performed_by' =>
                    $actor->id,

                'performed_by_name' =>
                    $actor->name,
            ],
            $details
        );

        $metadata['history'] =
            $history;

        return $metadata;
    }


    /*
    |--------------------------------------------------------------------------
    | تحميل العلاقات
    |--------------------------------------------------------------------------
    */

    private function loadRelations(
        PayrollAdjustment $adjustment
    ): PayrollAdjustment {
        return $adjustment->load([
            'employee',
            'payrollPeriod',
            'salaryComponent',
            'approvedBy',
            'createdBy',
            'appliedPayrollItem',
        ]);
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