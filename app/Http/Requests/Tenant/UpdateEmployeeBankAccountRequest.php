<?php

namespace App\Http\Requests\Tenant;

use App\Models\EmployeeBankAccount;
use Illuminate\Validation\Rule;

class UpdateEmployeeBankAccountRequest extends
    StoreEmployeeBankAccountRequest
{
    protected function prepareForValidation(): void
    {
        $bankAccount =
            $this->route('bankAccount');

        if (
            $bankAccount instanceof
            EmployeeBankAccount
        ) {
            /*
             * لا نسمح بنقل الحساب البنكي
             * من موظف إلى موظف آخر.
             */
            $this->merge([
                'employee_id' =>
                    $bankAccount->employee_id,
            ]);

            /*
             * إذا ترك المستخدم IBAN فارغًا أثناء
             * التعديل، نحتفظ بالقيمة الحالية.
             */
            if (
                !$this->filled('iban') &&
                $bankAccount->iban
            ) {
                $this->merge([
                    'iban' =>
                        $bankAccount->iban,
                ]);
            }

            /*
             * الاحتفاظ برقم الحساب الحالي
             * إذا لم تتم كتابة رقم جديد.
             */
            if (
                !$this->filled(
                    'account_number'
                ) &&
                $bankAccount->account_number
            ) {
                $this->merge([
                    'account_number' =>
                        $bankAccount
                            ->account_number,
                ]);
            }
        }

        parent::prepareForValidation();
    }


    public function rules(): array
    {
        $rules =
            parent::rules();

        $tenantId = (int) $this
            ->user()
            ->tenant_id;

        $employeeId = (int) $this->input(
            'employee_id'
        );

        $bankAccount =
            $this->route('bankAccount');

        $bankAccountId =
            $bankAccount instanceof
            EmployeeBankAccount
                ? $bankAccount->id
                : (int) $bankAccount;

        /*
         * تجاهل السجل الحالي عند التحقق
         * من تكرار IBAN.
         */
        $rules['iban_hash'] = [
            'nullable',
            'string',
            'size:64',

            Rule::unique(
                'employee_bank_accounts',
                'iban_hash'
            )
                ->ignore(
                    $bankAccountId
                )
                ->where(
                    function ($query) use (
                        $tenantId,
                        $employeeId
                    ) {
                        $query
                            ->where(
                                'tenant_id',
                                $tenantId
                            )
                            ->where(
                                'employee_id',
                                $employeeId
                            )
                            ->whereNull(
                                'deleted_at'
                            );
                    }
                ),
        ];

        return $rules;
    }
}