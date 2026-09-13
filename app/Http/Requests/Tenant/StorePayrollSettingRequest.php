<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePayrollSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return
            $user &&
            (
                $user->can('settings.update') ||
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
                    'payroll_iban',
                    ''
                )
            )
        );

        $bankCode = strtoupper(
            trim(
                (string) $this->input(
                    'payroll_bank_code',
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

        $accountNumber = preg_replace(
            '/\s+/',
            '',
            (string) $this->input(
                'payroll_account_number',
                ''
            )
        );

        $this->merge([
            'payroll_iban' =>
                $iban !== ''
                    ? $iban
                    : null,

            'payroll_bank_code' =>
                $bankCode !== ''
                    ? $bankCode
                    : null,

            'swift_code' =>
                $swiftCode !== ''
                    ? $swiftCode
                    : null,

            'payroll_account_number' =>
                $accountNumber !== ''
                    ? $accountNumber
                    : null,

            'wps_enabled' =>
                $this->boolean(
                    'wps_enabled'
                ),

            'require_verified_bank_account' =>
                $this->boolean(
                    'require_verified_bank_account'
                ),

            'salary_payment_day' =>
                $this->filled(
                    'salary_payment_day'
                )
                    ? (int) $this->input(
                        'salary_payment_day'
                    )
                    : null,

            'default_file_format' =>
                strtolower(
                    trim(
                        (string) $this->input(
                            'default_file_format',
                            'bank_csv'
                        )
                    )
                ),
        ]);
    }


    public function rules(): array
    {
        $wpsEnabled = $this->boolean(
            'wps_enabled'
        );

        return [
            /*
            |--------------------------------------------------------------------------
            | بيانات المنشأة
            |--------------------------------------------------------------------------
            */

            'establishment_name' => [
                Rule::requiredIf(
                    $wpsEnabled
                ),
                'nullable',
                'string',
                'max:255',
            ],

            'establishment_number' => [
                Rule::requiredIf(
                    $wpsEnabled
                ),
                'nullable',
                'string',
                'max:100',
            ],

            'unified_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'commercial_registration_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'wps_employer_id' => [
                Rule::requiredIf(
                    $wpsEnabled
                ),
                'nullable',
                'string',
                'max:100',
            ],


            /*
            |--------------------------------------------------------------------------
            | بيانات بنك الشركة
            |--------------------------------------------------------------------------
            */

            'payroll_bank_name' => [
                Rule::requiredIf(
                    $wpsEnabled
                ),
                'nullable',
                'string',
                'max:255',
            ],

            'payroll_bank_code' => [
                Rule::requiredIf(
                    $wpsEnabled
                ),
                'nullable',
                'string',
                'max:50',
            ],

            'payroll_account_holder_name' => [
                Rule::requiredIf(
                    $wpsEnabled
                ),
                'nullable',
                'string',
                'max:255',
            ],

            'payroll_account_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'payroll_iban' => [
                Rule::requiredIf(
                    $wpsEnabled
                ),
                'nullable',
                'string',
                'size:24',
                'regex:/^SA[0-9]{22}$/',
            ],

            'swift_code' => [
                'nullable',
                'string',
                'regex:/^[A-Z0-9]{8}([A-Z0-9]{3})?$/',
            ],


            /*
            |--------------------------------------------------------------------------
            | إعدادات ملف الرواتب
            |--------------------------------------------------------------------------
            */

            'wps_enabled' => [
                'required',
                'boolean',
            ],

            'default_file_format' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9_\-]+$/',
            ],

            'salary_payment_day' => [
                'nullable',
                'integer',
                'between:1,31',
            ],

            'payment_reference_prefix' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9_\-]+$/',
            ],

            'require_verified_bank_account' => [
                'required',
                'boolean',
            ],
        ];
    }


    public function messages(): array
    {
        return [
            'establishment_name.required' =>
                'اسم المنشأة مطلوب عند تفعيل حماية الأجور.',

            'establishment_number.required' =>
                'رقم المنشأة مطلوب عند تفعيل حماية الأجور.',

            'wps_employer_id.required' =>
                'معرف المنشأة في حماية الأجور مطلوب.',

            'payroll_bank_name.required' =>
                'اسم بنك الشركة مطلوب عند تفعيل حماية الأجور.',

            'payroll_bank_code.required' =>
                'رمز بنك الشركة مطلوب عند تفعيل حماية الأجور.',

            'payroll_account_holder_name.required' =>
                'اسم صاحب حساب الرواتب مطلوب.',

            'payroll_iban.required' =>
                'IBAN حساب الشركة مطلوب عند تفعيل حماية الأجور.',

            'payroll_iban.size' =>
                'IBAN السعودي يجب أن يتكون من 24 خانة.',

            'payroll_iban.regex' =>
                'IBAN يجب أن يبدأ بـ SA ويتبعه 22 رقمًا.',

            'swift_code.regex' =>
                'رمز SWIFT غير صحيح.',

            'salary_payment_day.between' =>
                'يوم صرف الراتب يجب أن يكون بين 1 و31.',

            'payment_reference_prefix.regex' =>
                'بادئة مرجع الدفع تقبل الحروف الإنجليزية والأرقام والشرطة فقط.',

            'default_file_format.regex' =>
                'رمز تنسيق الملف غير صحيح.',
        ];
    }


    public function attributes(): array
    {
        return [
            'establishment_name' =>
                'اسم المنشأة',

            'establishment_number' =>
                'رقم المنشأة',

            'unified_number' =>
                'الرقم الموحد',

            'commercial_registration_number' =>
                'رقم السجل التجاري',

            'wps_employer_id' =>
                'معرف حماية الأجور',

            'payroll_bank_name' =>
                'اسم البنك',

            'payroll_bank_code' =>
                'رمز البنك',

            'payroll_account_holder_name' =>
                'اسم صاحب الحساب',

            'payroll_account_number' =>
                'رقم الحساب',

            'payroll_iban' =>
                'IBAN الشركة',

            'swift_code' =>
                'رمز SWIFT',

            'salary_payment_day' =>
                'يوم صرف الراتب',
        ];
    }
}