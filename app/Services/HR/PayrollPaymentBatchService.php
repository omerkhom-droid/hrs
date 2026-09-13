<?php

namespace App\Services\HR;

use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use App\Models\PayrollPaymentBatch;
use App\Models\PayrollPaymentBatchItem;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Models\PayrollSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PayrollPaymentBatchService
{
    /*
    |--------------------------------------------------------------------------
    | إنشاء دفعة من مسير رواتب معتمد
    |--------------------------------------------------------------------------
    */

    public function createFromPayrollRun(
        PayrollRun $payrollRun,
        User $user,
        array $data
    ): PayrollPaymentBatch {
        $tenantId = (int) $user->tenant_id;

        $this->ensureSameTenant(
            $payrollRun,
            $tenantId
        );

        $this->ensurePayrollRunCanBeTransferred(
            $payrollRun
        );

        $settings = PayrollSetting::query()
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$settings) {
            throw ValidationException::withMessages([
                'payroll_settings' =>
                    'يجب إعداد بيانات بنك الرواتب قبل إنشاء دفعة التحويل.',
            ]);
        }

        $this->validateCompanyBankSettings(
            $settings
        );

        return DB::transaction(function () use (
            $payrollRun,
            $settings,
            $user,
            $data,
            $tenantId
        ) {
            /*
             * إعادة تحميل المسير مع قفل السجل لمنع إنشاء
             * دفعتين في نفس اللحظة.
             */
            $payrollRun = PayrollRun::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($payrollRun->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensurePayrollRunCanBeTransferred(
                $payrollRun
            );

            $existingBatch = PayrollPaymentBatch::query()
                ->where('tenant_id', $tenantId)
                ->where('payroll_run_id', $payrollRun->id)
                ->whereNotIn('status', [
                    'cancelled',
                    'failed',
                ])
                ->exists();

            if ($existingBatch) {
                throw ValidationException::withMessages([
                    'payroll_run_id' =>
                        'يوجد بالفعل دفعة تحويل فعالة مرتبطة بهذا المسير.',
                ]);
            }

            $runItems = $this->loadPayrollRunItems(
                $payrollRun,
                $tenantId
            );

            if ($runItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'payroll_run_id' =>
                        'لا يحتوي مسير الرواتب على موظفين مؤهلين للتحويل.',
                ]);
            }

            $batchNumber = $this->generateBatchNumber(
                $tenantId
            );

            $paymentReference =
                $this->generatePaymentReference(
                    $settings,
                    $batchNumber
                );

            $batch = PayrollPaymentBatch::create([
                'tenant_id' =>
                    $tenantId,

                'payroll_run_id' =>
                    $payrollRun->id,

                'batch_number' =>
                    $batchNumber,

                'payment_reference' =>
                    $paymentReference,

                'name' =>
                    $data['name']
                    ?? $this->defaultBatchName($payrollRun),

                'payment_date' =>
                    $data['payment_date'],

                'currency_code' =>
                    $data['currency_code']
                    ?? $payrollRun->currency_code
                    ?? $settings->currency_code
                    ?? 'SAR',

                'file_format' =>
                    $data['file_format']
                    ?? $settings->default_file_format
                    ?? 'bank_csv',

                'status' =>
                    'draft',

                /*
                 * لقطة من بيانات حساب الشركة.
                 */
                'source_bank_name' =>
                    $settings->payroll_bank_name,

                'source_bank_code' =>
                    $settings->payroll_bank_code,

                'source_account_number' =>
                    $settings->payroll_account_number,

                'source_account_last_four' =>
                    $this->lastFour(
                        $settings->payroll_account_number
                    ),

                'source_iban' =>
                    $settings->payroll_iban,

                'source_iban_last_four' =>
                    $this->lastFour(
                        $settings->payroll_iban
                    ),

                'source_swift_code' =>
                    $settings->payroll_bank_swift,

                'notes' =>
                    $data['notes'] ?? null,

                'created_by' =>
                    $user->id,

                'updated_by' =>
                    $user->id,

                'metadata' => [
                    'source' =>
                        'payroll_run',

                    'payroll_run_status' =>
                        $payrollRun->status,

                    'created_manually' =>
                        true,
                ],
            ]);

            foreach ($runItems as $runItem) {
                $this->createBatchItem(
                    $batch,
                    $runItem,
                    $settings
                );
            }

            $batch->recalculateTotals();

            return $this->loadBatch(
                $batch
            );
        });
    }


    /*
    |--------------------------------------------------------------------------
    | إعادة فحص دفعة التحويل
    |--------------------------------------------------------------------------
    */

    public function validateBatch(
        PayrollPaymentBatch $batch,
        User $user
    ): PayrollPaymentBatch {
        $tenantId = (int) $user->tenant_id;

        $this->ensureSameTenant(
            $batch,
            $tenantId
        );

        if (!in_array(
            $batch->status,
            [
                'draft',
                'validated',
            ],
            true
        )) {
            throw ValidationException::withMessages([
                'status' =>
                    'لا يمكن فحص الدفعة في حالتها الحالية.',
            ]);
        }

        $settings = PayrollSetting::query()
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$settings) {
            throw ValidationException::withMessages([
                'payroll_settings' =>
                    'إعدادات الرواتب البنكية غير موجودة.',
            ]);
        }

        $this->validateCompanyBankSettings(
            $settings
        );

        return DB::transaction(function () use (
            $batch,
            $settings,
            $user,
            $tenantId
        ) {
            $batch = PayrollPaymentBatch::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!in_array(
                $batch->status,
                [
                    'draft',
                    'validated',
                ],
                true
            )) {
                throw ValidationException::withMessages([
                    'status' =>
                        'تغيرت حالة الدفعة ولا يمكن فحصها الآن.',
                ]);
            }

            $items = $batch->items()
                ->with([
                    'employee.primaryBankAccount',
                    'payrollRunItem',
                ])
                ->lockForUpdate()
                ->get();

            foreach ($items as $item) {
                $this->refreshBatchItem(
                    $item,
                    $settings
                );
            }

            $batch->recalculateTotals();
            $batch->refresh();

            /*
             * تبقى الدفعة مسودة إذا وُجدت استثناءات.
             */
            $status = $batch->exception_employees_count > 0
                ? 'draft'
                : 'validated';

            $metadata = $batch->metadata ?? [];

            $metadata['validation'] = [
                'evaluated_at' =>
                    now()->toIso8601String(),

                'evaluated_by' =>
                    $user->id,

                'ready_items' =>
                    $batch->ready_employees_count,

                'exception_items' =>
                    $batch->exception_employees_count,
            ];

            $batch->forceFill([
                'status' =>
                    $status,

                'validated_at' =>
                    now(),

                'validated_by' =>
                    $user->id,

                'updated_by' =>
                    $user->id,

                'metadata' =>
                    $metadata,
            ])->save();

            return $this->loadBatch(
                $batch
            );
        });
    }


    /*
    |--------------------------------------------------------------------------
    | استبعاد موظف من دفعة مسودة
    |--------------------------------------------------------------------------
    */

    public function excludeItem(
        PayrollPaymentBatchItem $item,
        User $user,
        string $reason
    ): PayrollPaymentBatchItem {
        $tenantId = (int) $user->tenant_id;

        $this->ensureSameTenant(
            $item,
            $tenantId
        );

        $item->loadMissing('batch');

        if (!$item->batch?->isEditable()) {
            throw ValidationException::withMessages([
                'status' =>
                    'لا يمكن استبعاد الموظف بعد اعتماد الدفعة.',
            ]);
        }

        return DB::transaction(function () use (
            $item,
            $user,
            $reason,
            $tenantId
        ) {
            $item = PayrollPaymentBatchItem::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($item->id)
                ->lockForUpdate()
                ->firstOrFail();

            $item->forceFill([
                'status' =>
                    'excluded',

                'exception_code' =>
                    'manually_excluded',

                'exception_message' =>
                    $reason,

                'metadata' => array_merge(
                    $item->metadata ?? [],
                    [
                        'excluded_by' =>
                            $user->id,

                        'excluded_at' =>
                            now()->toIso8601String(),
                    ]
                ),
            ])->save();

            $item->batch->recalculateTotals();

            return $item->refresh();
        });
    }


    /*
    |--------------------------------------------------------------------------
    | إعادة موظف مستبعد إلى الدفعة
    |--------------------------------------------------------------------------
    */

    public function restoreItem(
        PayrollPaymentBatchItem $item,
        User $user
    ): PayrollPaymentBatchItem {
        $tenantId = (int) $user->tenant_id;

        $this->ensureSameTenant(
            $item,
            $tenantId
        );

        $item->loadMissing('batch');

        if (!$item->batch?->isEditable()) {
            throw ValidationException::withMessages([
                'status' =>
                    'لا يمكن إعادة الموظف بعد اعتماد الدفعة.',
            ]);
        }

        $settings = PayrollSetting::query()
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        return DB::transaction(function () use (
            $item,
            $settings,
            $tenantId
        ) {
            $item = PayrollPaymentBatchItem::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($item->id)
                ->with([
                    'employee.primaryBankAccount',
                ])
                ->lockForUpdate()
                ->firstOrFail();

            $this->refreshBatchItem(
                $item,
                $settings
            );

            $item->batch->recalculateTotals();

            return $item->refresh();
        });
    }


    /*
    |--------------------------------------------------------------------------
    | إلغاء دفعة
    |--------------------------------------------------------------------------
    */

    public function cancelBatch(
        PayrollPaymentBatch $batch,
        User $user,
        string $reason
    ): PayrollPaymentBatch {
        $tenantId = (int) $user->tenant_id;

        $this->ensureSameTenant(
            $batch,
            $tenantId
        );

        if (!$batch->can_cancel) {
            throw ValidationException::withMessages([
                'status' =>
                    'لا يمكن إلغاء الدفعة في حالتها الحالية.',
            ]);
        }

        return DB::transaction(function () use (
            $batch,
            $user,
            $reason,
            $tenantId
        ) {
            $batch = PayrollPaymentBatch::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$batch->can_cancel) {
                throw ValidationException::withMessages([
                    'status' =>
                        'تغيرت حالة الدفعة ولا يمكن إلغاؤها.',
                ]);
            }

            $batch->items()
                ->whereNotIn('status', [
                    'paid',
                    'failed',
                ])
                ->update([
                    'status' =>
                        'cancelled',

                    'exception_code' =>
                        'batch_cancelled',

                    'exception_message' =>
                        $reason,

                    'updated_at' =>
                        now(),
                ]);

            $batch->forceFill([
                'status' =>
                    'cancelled',

                'cancelled_at' =>
                    now(),

                'cancelled_by' =>
                    $user->id,

                'cancellation_reason' =>
                    $reason,

                'updated_by' =>
                    $user->id,
            ])->save();

            $batch->recalculateTotals();

            return $this->loadBatch(
                $batch
            );
        });
    }


    /*
    |--------------------------------------------------------------------------
    | إنشاء عنصر موظف
    |--------------------------------------------------------------------------
    */

    private function createBatchItem(
        PayrollPaymentBatch $batch,
        PayrollRunItem $runItem,
        PayrollSetting $settings
    ): PayrollPaymentBatchItem {
        $employee = $runItem->employee;

        if (!$employee) {
            throw new RuntimeException(
                'أحد عناصر مسير الرواتب غير مرتبط بموظف.'
            );
        }

        $bankAccount = $employee->primaryBankAccount;

        $validation = $this->validateEmployeeBankAccount(
            $employee,
            $bankAccount,
            $settings,
            $runItem
        );

        return PayrollPaymentBatchItem::create([
            'tenant_id' =>
                $batch->tenant_id,

            'payroll_payment_batch_id' =>
                $batch->id,

            'payroll_run_item_id' =>
                $runItem->id,

            'employee_id' =>
                $employee->id,

            'employee_bank_account_id' =>
                $bankAccount?->id,

            'employee_number' =>
                $employee->employee_number,

            'employee_name' =>
                $this->employeeName($employee),

            'amount' =>
                $runItem->net_salary,

            'currency_code' =>
                $runItem->currency_code
                ?? $batch->currency_code,

            'payment_method' =>
                $bankAccount?->payment_method
                ?? 'bank_transfer',

            'bank_name' =>
                $bankAccount?->bank_name,

            'bank_code' =>
                $bankAccount?->bank_code,

            'bank_branch_code' =>
                $bankAccount?->bank_branch_code,

            'account_holder_name' =>
                $bankAccount?->account_holder_name,

            'account_number' =>
                $bankAccount?->account_number,

            'account_number_last_four' =>
                $this->lastFour(
                    $bankAccount?->account_number
                ),

            'iban' =>
                $bankAccount?->iban,

            'iban_hash' =>
                $bankAccount?->iban_hash,

            'iban_last_four' =>
                $this->lastFour(
                    $bankAccount?->iban
                ),

            'swift_code' =>
                $bankAccount?->swift_code,

            'status' =>
                $validation['ready']
                    ? 'ready'
                    : 'exception',

            'exception_code' =>
                $validation['code'],

            'exception_message' =>
                $validation['message'],

            'metadata' => [
                'bank_account_verified' =>
                    (bool) $bankAccount?->is_verified,

                'bank_account_primary' =>
                    (bool) $bankAccount?->is_primary,

                'salary_snapshot' => [
                    'basic_salary' =>
                        $runItem->basic_salary,

                    'total_earnings' =>
                        $runItem->total_earnings,

                    'total_deductions' =>
                        $runItem->total_deductions,

                    'net_salary' =>
                        $runItem->net_salary,
                ],
            ],
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | تحديث بيانات موظف أثناء إعادة الفحص
    |--------------------------------------------------------------------------
    */

    private function refreshBatchItem(
        PayrollPaymentBatchItem $item,
        PayrollSetting $settings
    ): void {
        if (in_array(
            $item->status,
            [
                'submitted',
                'paid',
                'failed',
                'cancelled',
            ],
            true
        )) {
            return;
        }

        $employee = $item->employee;

        $bankAccount =
            $employee?->primaryBankAccount;

        $validation = $this->validateEmployeeBankAccount(
            $employee,
            $bankAccount,
            $settings,
            $item->payrollRunItem
        );

        $item->forceFill([
            'employee_bank_account_id' =>
                $bankAccount?->id,

            'employee_name' =>
                $employee
                    ? $this->employeeName($employee)
                    : $item->employee_name,

            'payment_method' =>
                $bankAccount?->payment_method
                ?? 'bank_transfer',

            'bank_name' =>
                $bankAccount?->bank_name,

            'bank_code' =>
                $bankAccount?->bank_code,

            'bank_branch_code' =>
                $bankAccount?->bank_branch_code,

            'account_holder_name' =>
                $bankAccount?->account_holder_name,

            'account_number' =>
                $bankAccount?->account_number,

            'account_number_last_four' =>
                $this->lastFour(
                    $bankAccount?->account_number
                ),

            'iban' =>
                $bankAccount?->iban,

            'iban_hash' =>
                $bankAccount?->iban_hash,

            'iban_last_four' =>
                $this->lastFour(
                    $bankAccount?->iban
                ),

            'swift_code' =>
                $bankAccount?->swift_code,

            'status' =>
                $validation['ready']
                    ? 'ready'
                    : 'exception',

            'exception_code' =>
                $validation['code'],

            'exception_message' =>
                $validation['message'],

            'metadata' => array_merge(
                $item->metadata ?? [],
                [
                    'last_revalidated_at' =>
                        now()->toIso8601String(),

                    'bank_account_verified' =>
                        (bool) $bankAccount?->is_verified,

                    'bank_account_primary' =>
                        (bool) $bankAccount?->is_primary,
                ]
            ),
        ])->save();
    }


    /*
    |--------------------------------------------------------------------------
    | فحص حساب الموظف
    |--------------------------------------------------------------------------
    */

    private function validateEmployeeBankAccount(
        ?Employee $employee,
        ?EmployeeBankAccount $account,
        PayrollSetting $settings,
        ?PayrollRunItem $runItem
    ): array {
        if (!$employee) {
            return $this->exception(
                'employee_not_found',
                'الموظف غير موجود.'
            );
        }

        if (!$runItem) {
            return $this->exception(
                'payroll_item_not_found',
                'عنصر مسير الرواتب غير موجود.'
            );
        }

        if ((float) $runItem->net_salary <= 0) {
            return $this->exception(
                'invalid_net_salary',
                'صافي راتب الموظف يجب أن يكون أكبر من صفر.'
            );
        }

        if (!$account) {
            return $this->exception(
                'bank_account_missing',
                'لا يوجد حساب بنكي رئيسي للموظف.'
            );
        }

        if (!$account->is_active) {
            return $this->exception(
                'bank_account_inactive',
                'الحساب البنكي للموظف غير نشط.'
            );
        }

        if (!$account->is_primary) {
            return $this->exception(
                'bank_account_not_primary',
                'الحساب البنكي المحدد ليس الحساب الرئيسي.'
            );
        }

        if ($account->payment_method !== 'bank_transfer') {
            return $this->exception(
                'unsupported_payment_method',
                'طريقة دفع الموظف ليست تحويلًا بنكيًا.'
            );
        }

        if (
            $settings->require_verified_bank_account
            && !$account->is_verified
        ) {
            return $this->exception(
                'bank_account_not_verified',
                'الحساب البنكي للموظف غير موثّق.'
            );
        }

        if (!$account->bank_name) {
            return $this->exception(
                'bank_name_missing',
                'اسم بنك الموظف غير مسجل.'
            );
        }

        if (!$account->account_holder_name) {
            return $this->exception(
                'account_holder_missing',
                'اسم صاحب الحساب غير مسجل.'
            );
        }

        if (!$account->iban) {
            return $this->exception(
                'iban_missing',
                'رقم الآيبان غير مسجل.'
            );
        }

        if (
            strtoupper(
                (string) $account->currency_code
            )
            !==
            strtoupper(
                (string) (
                    $runItem->currency_code
                    ?? 'SAR'
                )
            )
        ) {
            return $this->exception(
                'currency_mismatch',
                'عملة الحساب البنكي لا تطابق عملة مسير الرواتب.'
            );
        }

        return [
            'ready' =>
                true,

            'code' =>
                null,

            'message' =>
                null,
        ];
    }


    private function exception(
        string $code,
        string $message
    ): array {
        return [
            'ready' =>
                false,

            'code' =>
                $code,

            'message' =>
                $message,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | فحص المسير والإعدادات
    |--------------------------------------------------------------------------
    */

    private function ensurePayrollRunCanBeTransferred(
        PayrollRun $payrollRun
    ): void {
        if ($payrollRun->status !== 'approved') {
            throw ValidationException::withMessages([
                'payroll_run_id' =>
                    'يجب اعتماد مسير الرواتب قبل إنشاء دفعة التحويل.',
            ]);
        }

        if (
            !$payrollRun->items()
                ->where('net_salary', '>', 0)
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'payroll_run_id' =>
                    'لا يحتوي مسير الرواتب على مبالغ قابلة للتحويل.',
            ]);
        }
    }


    private function validateCompanyBankSettings(
        PayrollSetting $settings
    ): void {
        $errors = [];

        if (!$settings->payroll_bank_name) {
            $errors[] = 'اسم بنك الشركة غير مسجل.';
        }

        if (!$settings->payroll_account_holder_name) {
            $errors[] = 'اسم صاحب حساب الشركة غير مسجل.';
        }

        if (!$settings->payroll_iban) {
            $errors[] = 'آيبان حساب الشركة غير مسجل.';
        }

        if ($errors) {
            throw ValidationException::withMessages([
                'payroll_settings' =>
                    implode(' ', $errors),
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | تحميل عناصر المسير
    |--------------------------------------------------------------------------
    */

    private function loadPayrollRunItems(
        PayrollRun $payrollRun,
        int $tenantId
    ): Collection {
        return PayrollRunItem::query()
            ->where('tenant_id', $tenantId)
            ->where('payroll_run_id', $payrollRun->id)
            ->whereIn('status', [
                'approved',
                'calculated',
            ])
            ->where('net_salary', '>', 0)
            ->with([
                'employee.primaryBankAccount',
            ])
            ->orderBy('employee_id')
            ->get();
    }


    /*
    |--------------------------------------------------------------------------
    | الأرقام والمراجع
    |--------------------------------------------------------------------------
    */

    private function generateBatchNumber(
        int $tenantId
    ): string {
        $year = now()->format('Y');

        $lastId = PayrollPaymentBatch::withTrashed()
            ->where('tenant_id', $tenantId)
            ->lockForUpdate()
            ->max('id');

        $sequence = ((int) $lastId) + 1;

        return 'PB-'
            . $year
            . '-'
            . str_pad(
                (string) $sequence,
                6,
                '0',
                STR_PAD_LEFT
            );
    }


    private function generatePaymentReference(
        PayrollSetting $settings,
        string $batchNumber
    ): string {
        $prefix = strtoupper(
            trim(
                (string) (
                    $settings->payment_reference_prefix
                    ?: 'PAY'
                )
            )
        );

        return $prefix
            . '-'
            . str_replace(
                'PB-',
                '',
                $batchNumber
            );
    }


    private function defaultBatchName(
        PayrollRun $payrollRun
    ): string {
        $periodName =
            $payrollRun->period?->name
            ?? $payrollRun->name
            ?? $payrollRun->run_number
            ?? $payrollRun->id;

        return 'دفعة تحويل رواتب - '
            . $periodName;
    }


    private function employeeName(
        Employee $employee
    ): string {
        if (
            isset($employee->full_name)
            && trim((string) $employee->full_name) !== ''
        ) {
            return trim(
                (string) $employee->full_name
            );
        }

        return trim(
            implode(
                ' ',
                array_filter([
                    $employee->first_name,
                    $employee->father_name,
                    $employee->grandfather_name,
                    $employee->family_name,
                ])
            )
        );
    }


    private function lastFour(
        mixed $value
    ): ?string {
        $value = preg_replace(
            '/\s+/',
            '',
            (string) $value
        );

        if ($value === '') {
            return null;
        }

        return substr(
            $value,
            -4
        );
    }


    private function ensureSameTenant(
        object $model,
        int $tenantId
    ): void {
        if (
            (int) $model->tenant_id
            !== $tenantId
        ) {
            throw ValidationException::withMessages([
                'tenant' =>
                    'لا يمكن الوصول إلى بيانات شركة أخرى.',
            ]);
        }
    }


    private function loadBatch(
        PayrollPaymentBatch $batch
    ): PayrollPaymentBatch {
        return $batch
            ->refresh()
            ->load([
                'payrollRun.period',
                'createdBy:id,name',
                'validatedBy:id,name',
                'items.employee:id,employee_number,first_name,father_name,grandfather_name,family_name',
            ]);
    }
}