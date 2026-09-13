<?php

namespace App\Http\Requests\Tenant;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return
            $user &&
            (
                $user->can('employees.update') ||
                $user->can('payroll.manage')
            );
    }


    protected function prepareForValidation(): void
    {
        $iban = strtoupper(
            preg_replace(
                '/[\s\-]+/',
                '',
                (string) $this->input(
                    'iban',
                    ''
                )
            )
        );

        $accountNumber = preg_replace(
            '/\s+/',
            '',
            (string) $this->input(
                'account_number',
                ''
            )
        );

        $bankCode = strtoupper(
            trim(
                (string) $this->input(
                    'bank_code',
                    ''
                )
            )
        );

        $branchCode = strtoupper(
            trim(
                (string) $this->input(
                    'branch_code',
                    ''
                )
            )
        );

        $swiftCode = strtoupper(
            preg_replace(
                '/\s+/',
                '',
                (string) $this->input(
                    'swift_code',
                    ''
                )
            )
        );

        $ibanHash = $iban !== ''
            ? hash_hmac(
                'sha256',
                $iban,
                (string) config('app.key')
            )
            : null;

        $ibanLastFour = $iban !== ''
            ? mb_substr(
                $iban,
                -4
            )
            : null;

        $this->merge([
            'iban' =>
                $iban !== ''
                    ? $iban
                    : null,

            'iban_hash' =>
                $ibanHash,

            'iban_last4' =>
                $ibanLastFour,

            'account_number' =>
                $accountNumber !== ''
                    ? $accountNumber
                    : null,

            'bank_code' =>
                $bankCode !== ''
                    ? $bankCode
                    : null,

            'branch_code' =>
                $branchCode !== ''
                    ? $branchCode
                    : null,

            'swift_code' =>
                $swiftCode !== ''
                    ? $swiftCode
                    : null,

            'currency_code' =>
                strtoupper(
                    trim(
                        (string) $this->input(
                            'currency_code',
                            'SAR'
                        )
                    )
                ),

            'payment_method' =>
                strtolower(
                    trim(
                        (string) $this->input(
                            'payment_method',
                            'bank_transfer'
                        )
                    )
                ),

            'is_primary' =>
                $this->boolean(
                    'is_primary'
                ),

            'is_active' =>
                $this->boolean(
                    'is_active',
                    true
                ),
        ]);
    }


    public function rules(): array
    {
        $tenantId = (int) $this
            ->user()
            ->tenant_id;

        $employeeId = (int) $this->input(
            'employee_id'
        );

        $tenantCountry = strtoupper(
            (string) (
                $this->user()
                    ?->tenant
                    ?->country_code ??
                'SA'
            )
        );

        return [
            /*
            |--------------------------------------------------------------------------
            | الموظف
            |--------------------------------------------------------------------------
            */

            'employee_id' => [
                'required',
                'integer',

                Rule::exists(
                    'employees',
                    'id'
                )->where(
                    function ($query) use (
                        $tenantId
                    ) {
                        $query
                            ->where(
                                'tenant_id',
                                $tenantId
                            )
                            ->whereNull(
                                'deleted_at'
                            );
                    }
                ),
            ],


            /*
            |--------------------------------------------------------------------------
            | طريقة صرف الراتب
            |--------------------------------------------------------------------------
            */

            'payment_method' => [
                'required',

                Rule::in([
                    'bank_transfer',
                    'cash',
                    'cheque',
                    'wallet',
                ]),
            ],


            /*
            |--------------------------------------------------------------------------
            | بيانات الحساب
            |--------------------------------------------------------------------------
            */

            'bank_name' => [
                'required_if:payment_method,bank_transfer',
                'nullable',
                'string',
                'max:255',
            ],

            'bank_code' => [
                'nullable',
                'string',
                'max:50',
            ],

            'branch_code' => [
                'nullable',
                'string',
                'max:50',
            ],

            'account_holder_name' => [
                'required_unless:payment_method,cash',
                'nullable',
                'string',
                'max:255',
            ],

            'account_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'iban' => [
                'required_if:payment_method,bank_transfer',
                'nullable',
                'string',
                'min:15',
                'max:34',

                function (
                    string $attribute,
                    mixed $value,
                    Closure $fail
                ) use ($tenantCountry) {
                    if (!$value) {
                        return;
                    }

                    if (
                        $tenantCountry === 'SA' &&
                        !preg_match(
                            '/^SA[0-9]{22}$/',
                            $value
                        )
                    ) {
                        $fail(
                            'IBAN السعودي يجب أن يبدأ بـ SA ويتكون من 24 خانة.'
                        );

                        return;
                    }

                    if (
                        !$this->isValidIban(
                            $value
                        )
                    ) {
                        $fail(
                            'رقم IBAN غير صحيح أو أن رقم التحقق غير صالح.'
                        );
                    }
                },
            ],

            'iban_hash' => [
                'nullable',
                'string',
                'size:64',

                Rule::unique(
                    'employee_bank_accounts',
                    'iban_hash'
                )->where(
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
            ],

            'iban_last4' => [
                'nullable',
                'string',
                'size:4',
            ],

            'swift_code' => [
                'nullable',
                'string',
                'regex:/^[A-Z0-9]{8}([A-Z0-9]{3})?$/',
            ],

            'currency_code' => [
                'required',
                'string',
                'size:3',
                'regex:/^[A-Z]{3}$/',
            ],


            /*
            |--------------------------------------------------------------------------
            | الحالة
            |--------------------------------------------------------------------------
            */

            'is_primary' => [
                'required',
                'boolean',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | التحقق الحسابي من IBAN
    |--------------------------------------------------------------------------
    */

    private function isValidIban(
        string $iban
    ): bool {
        $iban = strtoupper(
            preg_replace(
                '/\s+/',
                '',
                $iban
            )
        );

        if (
            strlen($iban) < 15 ||
            strlen($iban) > 34
        ) {
            return false;
        }

        if (
            !preg_match(
                '/^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/',
                $iban
            )
        ) {
            return false;
        }

        /*
         * نقل أول أربع خانات إلى نهاية الرقم.
         */
        $rearranged =
            substr($iban, 4) .
            substr($iban, 0, 4);

        $numeric = '';

        foreach (
            str_split($rearranged)
            as $character
        ) {
            if (ctype_alpha($character)) {
                $numeric .= (
                    ord($character) - 55
                );
            } else {
                $numeric .= $character;
            }
        }

        /*
         * حساب MOD 97 دون الاعتماد على bcMath.
         */
        $remainder = 0;

        foreach (
            str_split($numeric)
            as $digit
        ) {
            $remainder = (
                ($remainder * 10) +
                (int) $digit
            ) % 97;
        }

        return $remainder === 1;
    }


    public function messages(): array
    {
        return [
            'employee_id.required' =>
                'يجب اختيار الموظف.',

            'employee_id.exists' =>
                'الموظف المحدد غير موجود أو لا يتبع الشركة الحالية.',

            'payment_method.required' =>
                'يجب تحديد طريقة صرف الراتب.',

            'payment_method.in' =>
                'طريقة صرف الراتب غير صحيحة.',

            'bank_name.required_if' =>
                'اسم البنك مطلوب للتحويل البنكي.',

            'account_holder_name.required_unless' =>
                'اسم صاحب الحساب مطلوب.',

            'iban.required_if' =>
                'IBAN مطلوب عند اختيار التحويل البنكي.',

            'iban_hash.unique' =>
                'هذا الحساب البنكي مسجل مسبقًا للموظف.',

            'swift_code.regex' =>
                'رمز SWIFT غير صحيح.',

            'currency_code.size' =>
                'رمز العملة يجب أن يتكون من ثلاثة أحرف.',

            'currency_code.regex' =>
                'رمز العملة يجب أن يحتوي على أحرف إنجليزية فقط.',
        ];
    }


    public function attributes(): array
    {
        return [
            'employee_id' =>
                'الموظف',

            'payment_method' =>
                'طريقة الصرف',

            'bank_name' =>
                'اسم البنك',

            'bank_code' =>
                'رمز البنك',

            'branch_code' =>
                'رمز الفرع',

            'account_holder_name' =>
                'اسم صاحب الحساب',

            'account_number' =>
                'رقم الحساب',

            'iban' =>
                'IBAN',

            'swift_code' =>
                'رمز SWIFT',

            'currency_code' =>
                'العملة',
        ];
    }
}