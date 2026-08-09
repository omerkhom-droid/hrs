<?php

namespace App\Http\Requests\System;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }


    /*
    |--------------------------------------------------------------------------
    | تجهيز البيانات قبل التحقق
    |--------------------------------------------------------------------------
    */

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' =>
                strtoupper(
                    trim((string) $this->input('code'))
                ),

            'email' =>
                strtolower(
                    trim((string) $this->input('email'))
                ),

            'admin_email' =>
                strtolower(
                    trim((string) $this->input('admin_email'))
                ),

            'country_code' =>
                strtoupper(
                    trim(
                        (string) $this->input(
                            'country_code',
                            'SA'
                        )
                    )
                ),

            'currency_code' =>
                strtoupper(
                    trim(
                        (string) $this->input(
                            'currency_code',
                            'SAR'
                        )
                    )
                ),

            'timezone' =>
                $this->input(
                    'timezone',
                    'Asia/Riyadh'
                ),

            'locale' =>
                $this->input(
                    'locale',
                    'ar'
                ),

            /*
             * العميل الجديد يبدأ نشطًا.
             */
            'status' =>
                'active',

            'use_trial' =>
                $this->boolean('use_trial'),

            'auto_renew' =>
                $this->boolean('auto_renew'),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    */

    public function rules(): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | بيانات العميل
            |--------------------------------------------------------------------------
            */

            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('tenants', 'code'),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'contact_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'country_code' => [
                'required',
                'string',
                'size:2',
                'regex:/^[A-Z]{2}$/',
            ],

            'currency_code' => [
                'required',
                'string',
                'size:3',
                'regex:/^[A-Z]{3}$/',
            ],

            'timezone' => [
                'required',
                'timezone',
            ],

            'locale' => [
                'required',
                Rule::in([
                    'ar',
                    'en',
                ]),
            ],

            'status' => [
                'required',
                Rule::in([
                    'active',
                ]),
            ],


            /*
            |--------------------------------------------------------------------------
            | بيانات الاشتراك
            |--------------------------------------------------------------------------
            */

            'plan_id' => [
                'required',
                'integer',

                Rule::exists(
                    'plans',
                    'id'
                )->where(
                    fn ($query) =>
                        $query->where(
                            'is_active',
                            true
                        )
                ),
            ],

            'billing_cycle' => [
                'required',
                Rule::in([
                    'monthly',
                    'yearly',
                ]),
            ],

            'starts_at' => [
                'required',
                'date_format:Y-m-d',
            ],

            'use_trial' => [
                'required',
                'boolean',
            ],

            'auto_renew' => [
                'required',
                'boolean',
            ],


            /*
            |--------------------------------------------------------------------------
            | بيانات مدير العميل
            |--------------------------------------------------------------------------
            */

            'admin_name' => [
                'required',
                'string',
                'max:255',
            ],

            'admin_email' => [
                'required',
                'email',
                'max:255',
                Rule::unique(
                    'users',
                    'email'
                ),
            ],

            'password' => [
                'required',
                'confirmed',

                Password::min(10)
                    ->letters()
                    ->numbers(),
            ],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Validation Messages
    |--------------------------------------------------------------------------
    */

    public function messages(): array
    {
        return [
            'code.required' =>
                'كود العميل مطلوب.',

            'code.regex' =>
                'كود العميل يقبل الأحرف الإنجليزية الكبيرة والأرقام والشرطة فقط.',

            'code.unique' =>
                'كود العميل مستخدم مسبقًا.',

            'plan_id.required' =>
                'يجب اختيار الباقة.',

            'plan_id.exists' =>
                'الباقة المحددة غير موجودة أو غير نشطة.',

            'starts_at.required' =>
                'تاريخ بداية الاشتراك مطلوب.',

            'starts_at.date_format' =>
                'تاريخ بداية الاشتراك غير صحيح.',

            'admin_name.required' =>
                'اسم مدير حساب العميل مطلوب.',

            'admin_email.required' =>
                'بريد مدير حساب العميل مطلوب.',

            'admin_email.unique' =>
                'بريد مدير الحساب مستخدم مسبقًا.',

            'password.required' =>
                'كلمة المرور مطلوبة.',

            'password.confirmed' =>
                'تأكيد كلمة المرور غير مطابق.',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Arabic Attribute Names
    |--------------------------------------------------------------------------
    */

    public function attributes(): array
    {
        return [
            'code' =>
                'كود العميل',

            'name' =>
                'اسم العميل',

            'contact_name' =>
                'مسؤول التواصل',

            'email' =>
                'بريد المنشأة',

            'phone' =>
                'رقم الجوال',

            'country_code' =>
                'الدولة',

            'currency_code' =>
                'العملة',

            'timezone' =>
                'المنطقة الزمنية',

            'locale' =>
                'اللغة',

            'plan_id' =>
                'الباقة',

            'billing_cycle' =>
                'دورة الفوترة',

            'starts_at' =>
                'تاريخ بداية الاشتراك',

            'admin_name' =>
                'اسم مدير الحساب',

            'admin_email' =>
                'بريد مدير الحساب',

            'password' =>
                'كلمة المرور',
        ];
    }
}