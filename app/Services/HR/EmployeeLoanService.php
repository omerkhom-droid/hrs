<?php

namespace App\Services\HR;

use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanInstallment;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\EmployeeLoanStatusNotification;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

class EmployeeLoanService
{
    /*
    |--------------------------------------------------------------------------
    | إنشاء مسودة
    |--------------------------------------------------------------------------
    */

    public function createDraft(
        Tenant $tenant,
        Employee $employee,
        User $user,
        array $data
    ): EmployeeLoan {
        return DB::transaction(function () use (
            $tenant,
            $employee,
            $user,
            $data
        ) {
            $this->ensureEmployeeBelongsToTenant(
                $tenant,
                $employee
            );

            $requestedAmount = $this->money(
                $data['requested_amount']
            );

            $installmentsCount = max(
                1,
                (int) ($data['installments_count'] ?? 1)
            );

            if ($requestedAmount <= 0) {
                throw new DomainException(
                    'مبلغ السلفة يجب أن يكون أكبر من صفر.'
                );
            }

            $loan = EmployeeLoan::query()->create([
                'tenant_id' => $tenant->id,
                'employee_id' => $employee->id,

                'loan_type' =>
                    $data['loan_type']
                    ?? 'salary_advance',

                'requested_amount' => $requestedAmount,
                'installments_count' => $installmentsCount,

                'first_installment_date' =>
                    $data['first_installment_date']
                    ?? null,

                'status' => 'draft',
                'reason' => trim(
                    (string) $data['reason']
                ),

                'employee_notes' =>
                    $this->nullableString(
                        $data['employee_notes'] ?? null
                    ),

                'created_by' => $user->id,
                'remaining_amount' => 0,
            ]);

            return $loan->fresh([
                'employee',
                'installments',
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | تعديل المسودة
    |--------------------------------------------------------------------------
    */

    public function updateDraft(
        EmployeeLoan $loan,
        array $data
    ): EmployeeLoan {
        return DB::transaction(function () use (
            $loan,
            $data
        ) {
            $loan = $this->lockLoan($loan);

            if (!$loan->isDraft()) {
                throw new DomainException(
                    'لا يمكن تعديل السلفة بعد إرسالها للاعتماد.'
                );
            }

            $requestedAmount = $this->money(
                $data['requested_amount']
                ?? $loan->requested_amount
            );

            if ($requestedAmount <= 0) {
                throw new DomainException(
                    'مبلغ السلفة يجب أن يكون أكبر من صفر.'
                );
            }

            $loan->fill([
                'loan_type' =>
                    $data['loan_type']
                    ?? $loan->loan_type,

                'requested_amount' => $requestedAmount,

                'installments_count' => max(
                    1,
                    (int) (
                        $data['installments_count']
                        ?? $loan->installments_count
                    )
                ),

                'first_installment_date' =>
                    $data['first_installment_date']
                    ?? $loan->first_installment_date,

                'reason' =>
                    array_key_exists('reason', $data)
                        ? trim((string) $data['reason'])
                        : $loan->reason,

                'employee_notes' =>
                    array_key_exists(
                        'employee_notes',
                        $data
                    )
                        ? $this->nullableString(
                            $data['employee_notes']
                        )
                        : $loan->employee_notes,
            ])->save();

            return $loan->fresh([
                'employee',
                'installments',
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | إرسال الطلب للاعتماد
    |--------------------------------------------------------------------------
    */

    public function submit(
        EmployeeLoan $loan
    ): EmployeeLoan {
        return DB::transaction(function () use ($loan) {
            $loan = $this->lockLoan($loan);

            if (!$loan->canBeSubmitted()) {
                throw new DomainException(
                    'لا يمكن إرسال هذه السلفة للاعتماد.'
                );
            }

            if ((float) $loan->requested_amount <= 0) {
                throw new DomainException(
                    'مبلغ السلفة غير صحيح.'
                );
            }

            if (blank($loan->reason)) {
                throw new DomainException(
                    'سبب طلب السلفة مطلوب.'
                );
            }

            $loan->forceFill([
                'status' => 'submitted',
                'submitted_at' => now(),
            ])->save();
            
            DB::afterCommit(function () use ($loan) {
                $this->notifyEmployee(
                    $loan,
                    'submitted'
                );
            });
            
            return $loan->refresh();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | اعتماد السلفة وإنشاء الأقساط
    |--------------------------------------------------------------------------
    */

    public function approve(
        EmployeeLoan $loan,
        User $approver,
        array $data = []
    ): EmployeeLoan {
        return DB::transaction(function () use (
            $loan,
            $approver,
            $data
        ) {
            $loan = $this->lockLoan($loan);

            if (!$loan->isSubmitted()) {
                throw new DomainException(
                    'يمكن اعتماد طلبات السلف المرسلة فقط.'
                );
            }

            $approvedAmount = $this->money(
                $data['approved_amount']
                ?? $loan->requested_amount
            );

            $installmentsCount = max(
                1,
                (int) (
                    $data['installments_count']
                    ?? $loan->installments_count
                )
            );

            $firstInstallmentDate =
                $data['first_installment_date']
                ?? $loan->first_installment_date;

            if ($approvedAmount <= 0) {
                throw new DomainException(
                    'المبلغ المعتمد يجب أن يكون أكبر من صفر.'
                );
            }

            if (!$firstInstallmentDate) {
                throw new DomainException(
                    'تاريخ أول قسط مطلوب لاعتماد السلفة.'
                );
            }

            $firstInstallmentDate =
                CarbonImmutable::parse(
                    $firstInstallmentDate
                )->startOfDay();

            $loan->forceFill([
                'approved_amount' => $approvedAmount,
                'installments_count' => $installmentsCount,

                'installment_amount' =>
                    $this->money(
                        $approvedAmount /
                        $installmentsCount
                    ),

                'paid_amount' => 0,
                'remaining_amount' => $approvedAmount,

                'first_installment_date' =>
                    $firstInstallmentDate->toDateString(),

                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $approver->id,

                'approval_notes' =>
                    $this->nullableString(
                        $data['approval_notes'] ?? null
                    ),

                'rejected_at' => null,
                'rejected_by' => null,
                'rejection_reason' => null,
            ])->save();

            $this->generateInstallments(
                $loan,
                $approvedAmount,
                $installmentsCount,
                $firstInstallmentDate
            );

            DB::afterCommit(function () use ($loan) {
                $this->notifyEmployee(
                    $loan,
                    'approved'
                );
            });

            return $loan->fresh([
                'employee',
                'installments',
                'approver',
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | رفض السلفة
    |--------------------------------------------------------------------------
    */

    public function reject(
        EmployeeLoan $loan,
        User $rejecter,
        string $reason
    ): EmployeeLoan {
        return DB::transaction(function () use (
            $loan,
            $rejecter,
            $reason
        ) {
            $loan = $this->lockLoan($loan);

            if (!$loan->isSubmitted()) {
                throw new DomainException(
                    'يمكن رفض طلبات السلف المرسلة فقط.'
                );
            }

            $reason = trim($reason);

            if ($reason === '') {
                throw new DomainException(
                    'سبب رفض السلفة مطلوب.'
                );
            }

            $loan->forceFill([
                'status' => 'rejected',
                'rejected_at' => now(),
                'rejected_by' => $rejecter->id,
                'rejection_reason' => $reason,
            ])->save();

            DB::afterCommit(function () use ($loan) {
                $this->notifyEmployee(
                    $loan,
                    'rejected'
                );
            });

            return $loan->refresh();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | إلغاء السلفة
    |--------------------------------------------------------------------------
    */

    public function cancel(
        EmployeeLoan $loan,
        User $user,
        ?string $reason = null
    ): EmployeeLoan {
        return DB::transaction(function () use (
            $loan,
            $user,
            $reason
        ) {
            $loan = $this->lockLoan($loan);

            if (!$loan->canBeCancelled()) {
                throw new DomainException(
                    'لا يمكن إلغاء هذه السلفة في حالتها الحالية.'
                );
            }

            $hasPayments = $loan->installments()
                ->where('paid_amount', '>', 0)
                ->exists();

            if ($hasPayments) {
                throw new DomainException(
                    'لا يمكن إلغاء السلفة بعد بدء سداد الأقساط.'
                );
            }

            $loan->installments()
                ->whereIn('status', [
                    'pending',
                    'partially_paid',
                    'postponed',
                ])
                ->update([
                    'status' => 'cancelled',
                    'updated_at' => now(),
                ]);

            $loan->forceFill([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $user->id,

                'cancellation_reason' =>
                    $this->nullableString($reason),

                'remaining_amount' => 0,
            ])->save();

            DB::afterCommit(function () use ($loan) {
                $this->notifyEmployee(
                    $loan,
                    'cancelled'
                );
            });

            return $loan->fresh('installments');
        });
    }

    /*
    |--------------------------------------------------------------------------
    | حذف المسودة
    |--------------------------------------------------------------------------
    */

    public function deleteDraft(
        EmployeeLoan $loan
    ): void {
        DB::transaction(function () use ($loan) {
            $loan = $this->lockLoan($loan);

            if (!$loan->isDraft()) {
                throw new DomainException(
                    'يمكن حذف مسودة السلفة فقط.'
                );
            }

            $loan->installments()->delete();
            $loan->delete();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | تسجيل خصم قسط من الراتب
    |--------------------------------------------------------------------------
    */

    public function recordPayrollDeduction(
        EmployeeLoanInstallment $installment,
        float $amount,
        int $payrollRunId,
        ?int $payrollEmployeeId = null
    ): EmployeeLoanInstallment {
        return DB::transaction(function () use (
            $installment,
            $amount,
            $payrollRunId,
            $payrollEmployeeId
        ) {
            $installment =
                EmployeeLoanInstallment::query()
                    ->lockForUpdate()
                    ->findOrFail($installment->id);

            $loan = EmployeeLoan::query()
                ->lockForUpdate()
                ->findOrFail(
                    $installment->employee_loan_id
                );

            if (
                !in_array(
                    $loan->status,
                    ['approved', 'active'],
                    true
                )
            ) {
                throw new DomainException(
                    'السلفة ليست متاحة للخصم.'
                );
            }

            if (!$installment->isPending()) {
                throw new DomainException(
                    'هذا القسط غير متاح للخصم.'
                );
            }

            $amount = $this->money($amount);

            $remainingBefore =
                (float) $installment->remaining_amount;

            if (
                $amount <= 0 ||
                $amount > $remainingBefore
            ) {
                throw new DomainException(
                    'مبلغ الخصم غير صحيح.'
                );
            }

            $paidAmount = $this->money(
                (float) $installment->paid_amount +
                $amount
            );

            $remainingAmount = $this->money(
                (float) $installment->amount -
                $paidAmount
            );

            $isFullyPaid = $remainingAmount <= 0;

            $installment->forceFill([
                'paid_amount' => $paidAmount,

                'remaining_amount' =>
                    max(0, $remainingAmount),

                'status' =>
                    $isFullyPaid
                        ? 'deducted'
                        : 'partially_paid',

                'payroll_run_id' => $payrollRunId,

                'payroll_run_item_id' =>
                    $payrollEmployeeId,

                'deducted_at' => now()->toDateString(),

                'paid_at' =>
                    $isFullyPaid
                        ? now()
                        : null,
            ])->save();

            $this->refreshLoanTotals($loan);

            return $installment->refresh();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | إلغاء خصم قسط عند التراجع عن مسير الرواتب
    |--------------------------------------------------------------------------
    */

    public function reversePayrollDeduction(
        EmployeeLoanInstallment $installment,
        int $payrollRunId
    ): EmployeeLoanInstallment {
        return DB::transaction(function () use (
            $installment,
            $payrollRunId
        ) {
            $installment =
                EmployeeLoanInstallment::query()
                    ->lockForUpdate()
                    ->findOrFail($installment->id);

            if (
                (int) $installment->payroll_run_id !==
                $payrollRunId
            ) {
                throw new DomainException(
                    'القسط غير مرتبط بمسير الرواتب المحدد.'
                );
            }

            $loan = EmployeeLoan::query()
                ->lockForUpdate()
                ->findOrFail(
                    $installment->employee_loan_id
                );

            $installment->forceFill([
                'paid_amount' => 0,
                'remaining_amount' =>
                    $installment->amount,
                'status' => 'pending',
                'payroll_run_id' => null,
                'payroll_run_item_id' => null,
                'deducted_at' => null,
                'paid_at' => null,
            ])->save();

            $this->refreshLoanTotals($loan);

            return $installment->refresh();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | إنشاء الأقساط
    |--------------------------------------------------------------------------
    */

    private function generateInstallments(
        EmployeeLoan $loan,
        float $approvedAmount,
        int $installmentsCount,
        CarbonImmutable $firstInstallmentDate
    ): void {
        $loan->installments()->delete();

        /*
         * الحساب بالهللات لمنع فروقات الكسور.
         */
        $totalCents = (int) round(
            $approvedAmount * 100
        );

        $baseCents = intdiv(
            $totalCents,
            $installmentsCount
        );

        $remainderCents =
            $totalCents % $installmentsCount;

        for (
            $number = 1;
            $number <= $installmentsCount;
            $number++
        ) {
            $installmentCents = $baseCents;

            /*
             * توزيع الهللات الزائدة على الأقساط الأولى.
             */
            if ($number <= $remainderCents) {
                $installmentCents++;
            }

            $amount = $installmentCents / 100;

            EmployeeLoanInstallment::query()->create([
                'tenant_id' => $loan->tenant_id,
                'employee_loan_id' => $loan->id,
                'employee_id' => $loan->employee_id,

                'installment_number' => $number,

                'due_date' =>
                    $firstInstallmentDate
                        ->addMonthsNoOverflow(
                            $number - 1
                        )
                        ->toDateString(),

                'amount' => $amount,
                'paid_amount' => 0,
                'remaining_amount' => $amount,
                'status' => 'pending',
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | تحديث إجماليات السلفة
    |--------------------------------------------------------------------------
    */

    private function refreshLoanTotals(
        EmployeeLoan $loan
    ): void {
        $wasCompleted =
            $loan->status === 'completed';

        $paidAmount = $this->money(
            $loan->installments()->sum(
                'paid_amount'
            )
        );

        $approvedAmount =
            (float) $loan->approved_amount;

        $remainingAmount = max(
            0,
            $this->money(
                $approvedAmount - $paidAmount
            )
        );

        $completed =
            $remainingAmount <= 0;

        $loan->forceFill([
            'paid_amount' =>
                $paidAmount,

            'remaining_amount' =>
                $remainingAmount,

            'status' =>
                $completed
                    ? 'completed'
                    : (
                        $paidAmount > 0
                            ? 'active'
                            : 'approved'
                    ),

            'completed_at' =>
                $completed
                    ? (
                        $loan->completed_at
                        ?? now()
                    )
                    : null,
        ])->save();

        if (
            $completed &&
            !$wasCompleted
        ) {
            DB::afterCommit(
                function () use ($loan) {
                    $this->notifyEmployee(
                        $loan,
                        'completed'
                    );
                }
            );
        }
    }

    private function notifyEmployee(
        EmployeeLoan $loan,
        string $event
    ): void {
        $employee = Employee::withoutGlobalScopes()
            ->where(
                'tenant_id',
                $loan->tenant_id
            )
            ->whereKey(
                $loan->employee_id
            )
            ->whereNull('deleted_at')
            ->first();

        if (
            !$employee ||
            !$employee->user_id
        ) {
            return;
        }

        $user = User::withoutGlobalScopes()
            ->where(
                'tenant_id',
                $loan->tenant_id
            )
            ->whereKey(
                $employee->user_id
            )
            ->whereNull('deleted_at')
            ->first();

        if (!$user) {
            return;
        }

        $freshLoan =
            EmployeeLoan::withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $loan->tenant_id
                )
                ->whereKey($loan->id)
                ->first();

        if (!$freshLoan) {
            return;
        }

        $user->notify(
            new EmployeeLoanStatusNotification(
                $freshLoan,
                $event
            )
        );
    }

    private function lockLoan(
        EmployeeLoan $loan
    ): EmployeeLoan {
        return EmployeeLoan::query()
            ->lockForUpdate()
            ->findOrFail($loan->id);
    }

    private function ensureEmployeeBelongsToTenant(
        Tenant $tenant,
        Employee $employee
    ): void {
        if (
            (int) $employee->tenant_id !==
            (int) $tenant->id
        ) {
            throw new DomainException(
                'الموظف لا يتبع الشركة الحالية.'
            );
        }
    }

    private function money(
        mixed $amount
    ): float {
        return round((float) $amount, 2);
    }

    private function nullableString(
        mixed $value
    ): ?string {
        $value = trim((string) $value);

        return $value !== ''
            ? $value
            : null;
    }
}