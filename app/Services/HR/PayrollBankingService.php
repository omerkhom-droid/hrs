<?php

namespace App\Services\HR;

use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use App\Models\PayrollSetting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

class PayrollBankingService
{
    /*
    |--------------------------------------------------------------------------
    | حفظ إعدادات بنك الشركة
    |--------------------------------------------------------------------------
    */

    public function savePayrollSettings(
        Tenant $tenant,
        User $actor,
        array $data
    ): PayrollSetting {
        return DB::transaction(
            function () use (
                $tenant,
                $actor,
                $data
            ) {
                $setting = PayrollSetting::query()
                    ->where(
                        'tenant_id',
                        $tenant->id
                    )
                    ->lockForUpdate()
                    ->first();

                if (!$setting) {
                    $setting = new PayrollSetting([
                        'tenant_id' =>
                            $tenant->id,

                        'created_by' =>
                            $actor->id,
                    ]);
                }

                /*
                 * ترك الحقول المشفرة فارغة أثناء
                 * التعديل يعني الاحتفاظ بالقيمة الحالية.
                 */
                if (
                    $setting->exists &&
                    empty(
                        $data[
                            'payroll_account_number'
                        ] ?? null
                    )
                ) {
                    unset(
                        $data[
                            'payroll_account_number'
                        ]
                    );
                }

                if (
                    $setting->exists &&
                    empty(
                        $data[
                            'payroll_iban'
                        ] ?? null
                    )
                ) {
                    unset(
                        $data[
                            'payroll_iban'
                        ]
                    );

                    unset(
                        $data[
                            'payroll_iban_last4'
                        ]
                    );
                }

                if (
                    !empty(
                        $data[
                            'payroll_iban'
                        ] ?? null
                    )
                ) {
                    $data[
                        'payroll_iban_last4'
                    ] = mb_substr(
                        $data['payroll_iban'],
                        -4
                    );
                }

                $data['updated_by'] =
                    $actor->id;

                $setting->fill($data);
                $setting->save();

                return $setting->refresh();
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | إنشاء حساب بنكي للموظف
    |--------------------------------------------------------------------------
    */

    public function createBankAccount(
        Employee $employee,
        User $actor,
        array $data
    ): EmployeeBankAccount {
        $this->assertSameTenant(
            $employee->tenant_id,
            $actor->tenant_id
        );

        return DB::transaction(
            function () use (
                $employee,
                $actor,
                $data
            ) {
                $employee = Employee::query()
                    ->whereKey(
                        $employee->id
                    )
                    ->where(
                        'tenant_id',
                        $actor->tenant_id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $hasPrimaryAccount =
                    EmployeeBankAccount::query()
                        ->where(
                            'tenant_id',
                            $employee->tenant_id
                        )
                        ->where(
                            'employee_id',
                            $employee->id
                        )
                        ->where(
                            'is_active',
                            true
                        )
                        ->where(
                            'is_primary',
                            true
                        )
                        ->exists();

                /*
                 * أول حساب نشط يصبح أساسيًا تلقائيًا.
                 */
                if (
                    !$hasPrimaryAccount &&
                    (
                        $data['is_active'] ??
                        true
                    )
                ) {
                    $data['is_primary'] =
                        true;
                }

                if (
                    !(
                        $data['is_active'] ??
                        true
                    )
                ) {
                    $data['is_primary'] =
                        false;
                }

                if (
                    $data['is_primary'] ??
                    false
                ) {
                    $this->clearPrimaryAccounts(
                        $employee->tenant_id,
                        $employee->id
                    );
                }

                $account =
                    EmployeeBankAccount::create([
                        ...$data,

                        'tenant_id' =>
                            $employee->tenant_id,

                        'employee_id' =>
                            $employee->id,

                        /*
                         * الحساب الجديد يحتاج مراجعة.
                         */
                        'is_verified' =>
                            false,

                        'verified_at' =>
                            null,

                        'verified_by' =>
                            null,

                        'created_by' =>
                            $actor->id,

                        'updated_by' =>
                            $actor->id,
                    ]);

                return $account
                    ->refresh()
                    ->load([
                        'employee',
                        'createdBy',
                    ]);
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | تعديل حساب الموظف
    |--------------------------------------------------------------------------
    */

    public function updateBankAccount(
        EmployeeBankAccount $account,
        User $actor,
        array $data
    ): EmployeeBankAccount {
        $this->assertSameTenant(
            $account->tenant_id,
            $actor->tenant_id
        );

        return DB::transaction(
            function () use (
                $account,
                $actor,
                $data
            ) {
                $account =
                    EmployeeBankAccount::query()
                        ->whereKey(
                            $account->id
                        )
                        ->where(
                            'tenant_id',
                            $actor->tenant_id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $oldVerificationSignature =
                    $this->verificationSignature(
                        $account
                    );

                /*
                 * الحساب غير النشط لا يكون أساسيًا.
                 */
                if (
                    array_key_exists(
                        'is_active',
                        $data
                    ) &&
                    !$data['is_active']
                ) {
                    $data['is_primary'] =
                        false;
                }

                if (
                    (
                        $data['is_primary'] ??
                        false
                    ) &&
                    (
                        $data['is_active'] ??
                        $account->is_active
                    )
                ) {
                    $this->clearPrimaryAccounts(
                        $account->tenant_id,
                        $account->employee_id,
                        $account->id
                    );
                }

                $data['updated_by'] =
                    $actor->id;

                /*
                 * لا يسمح بنقل الحساب لموظف آخر.
                 */
                unset(
                    $data['employee_id'],
                    $data['tenant_id'],
                    $data['is_verified'],
                    $data['verified_at'],
                    $data['verified_by']
                );

                $account->fill($data);

                $newVerificationSignature =
                    $this->verificationSignature(
                        $account
                    );

                /*
                 * أي تغيير في بيانات الحساب الحساسة
                 * يلغي التحقق السابق.
                 */
                if (
                    $oldVerificationSignature !==
                    $newVerificationSignature
                ) {
                    $account->is_verified =
                        false;

                    $account->verified_at =
                        null;

                    $account->verified_by =
                        null;
                }

                $account->save();

                $this->ensurePrimaryAccount(
                    $account->tenant_id,
                    $account->employee_id
                );

                return $account
                    ->refresh()
                    ->load([
                        'employee',
                        'verifiedBy',
                        'updatedBy',
                    ]);
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | اعتماد الحساب البنكي
    |--------------------------------------------------------------------------
    */

    public function verifyBankAccount(
        EmployeeBankAccount $account,
        User $actor
    ): EmployeeBankAccount {
        $this->assertSameTenant(
            $account->tenant_id,
            $actor->tenant_id
        );

        return DB::transaction(
            function () use (
                $account,
                $actor
            ) {
                $account =
                    EmployeeBankAccount::query()
                        ->whereKey(
                            $account->id
                        )
                        ->where(
                            'tenant_id',
                            $actor->tenant_id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                if (!$account->is_active) {
                    throw new LogicException(
                        'لا يمكن اعتماد حساب بنكي غير نشط.'
                    );
                }

                if (
                    !$account->isBankTransfer()
                ) {
                    throw new LogicException(
                        'الاعتماد البنكي متاح لحسابات التحويل البنكي فقط.'
                    );
                }

                if (
                    !filled($account->iban) ||
                    !filled($account->bank_name) ||
                    !filled($account->bank_code) ||
                    !filled(
                        $account->account_holder_name
                    )
                ) {
                    throw new LogicException(
                        'بيانات الحساب البنكي غير مكتملة.'
                    );
                }

                $account->forceFill([
                    'is_verified' =>
                        true,

                    'verified_at' =>
                        now(),

                    'verified_by' =>
                        $actor->id,

                    'updated_by' =>
                        $actor->id,
                ])->save();

                return $account
                    ->refresh()
                    ->load('verifiedBy');
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | إلغاء اعتماد الحساب
    |--------------------------------------------------------------------------
    */

    public function unverifyBankAccount(
        EmployeeBankAccount $account,
        User $actor
    ): EmployeeBankAccount {
        $this->assertSameTenant(
            $account->tenant_id,
            $actor->tenant_id
        );

        $account->forceFill([
            'is_verified' =>
                false,

            'verified_at' =>
                null,

            'verified_by' =>
                null,

            'updated_by' =>
                $actor->id,
        ])->save();

        return $account->refresh();
    }


    /*
    |--------------------------------------------------------------------------
    | حذف الحساب البنكي
    |--------------------------------------------------------------------------
    */

    public function deleteBankAccount(
        EmployeeBankAccount $account,
        User $actor
    ): void {
        $this->assertSameTenant(
            $account->tenant_id,
            $actor->tenant_id
        );

        DB::transaction(
            function () use (
                $account,
                $actor
            ) {
                $account =
                    EmployeeBankAccount::query()
                        ->whereKey(
                            $account->id
                        )
                        ->where(
                            'tenant_id',
                            $actor->tenant_id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $tenantId =
                    $account->tenant_id;

                $employeeId =
                    $account->employee_id;

                $account->forceFill([
                    'is_primary' =>
                        false,

                    'is_active' =>
                        false,

                    'is_verified' =>
                        false,

                    'verified_at' =>
                        null,

                    'verified_by' =>
                        null,

                    'updated_by' =>
                        $actor->id,
                ])->save();

                $account->delete();

                $this->ensurePrimaryAccount(
                    $tenantId,
                    $employeeId
                );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | إلغاء الحسابات الأساسية الأخرى
    |--------------------------------------------------------------------------
    */

    private function clearPrimaryAccounts(
        int $tenantId,
        int $employeeId,
        ?int $exceptId = null
    ): void {
        EmployeeBankAccount::query()
            ->where(
                'tenant_id',
                $tenantId
            )
            ->where(
                'employee_id',
                $employeeId
            )
            ->when(
                $exceptId,
                fn ($query) =>
                    $query->whereKeyNot(
                        $exceptId
                    )
            )
            ->where(
                'is_primary',
                true
            )
            ->update([
                'is_primary' =>
                    false,

                'updated_at' =>
                    now(),
            ]);
    }


    /*
    |--------------------------------------------------------------------------
    | ضمان وجود حساب أساسي
    |--------------------------------------------------------------------------
    */

    private function ensurePrimaryAccount(
        int $tenantId,
        int $employeeId
    ): void {
        $hasPrimary =
            EmployeeBankAccount::query()
                ->where(
                    'tenant_id',
                    $tenantId
                )
                ->where(
                    'employee_id',
                    $employeeId
                )
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    'is_primary',
                    true
                )
                ->exists();

        if ($hasPrimary) {
            return;
        }

        $replacement =
            EmployeeBankAccount::query()
                ->where(
                    'tenant_id',
                    $tenantId
                )
                ->where(
                    'employee_id',
                    $employeeId
                )
                ->where(
                    'is_active',
                    true
                )
                ->latest('id')
                ->first();

        if ($replacement) {
            $replacement->forceFill([
                'is_primary' =>
                    true,
            ])->save();
        }
    }


    /*
    |--------------------------------------------------------------------------
    | توقيع بيانات التحقق
    |--------------------------------------------------------------------------
    */

    private function verificationSignature(
        EmployeeBankAccount $account
    ): string {
        return hash(
            'sha256',
            implode(
                '|',
                [
                    (string) $account->iban,
                    (string) $account
                        ->account_number,
                    (string) $account
                        ->bank_name,
                    (string) $account
                        ->bank_code,
                    (string) $account
                        ->account_holder_name,
                    (string) $account
                        ->swift_code,
                    (string) $account
                        ->payment_method,
                ]
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | عزل بيانات الشركات
    |--------------------------------------------------------------------------
    */

    private function assertSameTenant(
        int $recordTenantId,
        ?int $actorTenantId
    ): void {
        if (
            $actorTenantId === null ||
            $recordTenantId !==
                (int) $actorTenantId
        ) {
            throw new LogicException(
                'لا يمكن تعديل بيانات شركة أخرى.'
            );
        }
    }
}