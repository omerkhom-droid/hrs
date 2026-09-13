<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StorePayrollPaymentBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = Auth::user();

        if (
            !$user
            || !$user->tenant_id
            || !$user->is_active
        ) {
            return false;
        }

        return $user->can('payroll.manage');
    }


    protected function prepareForValidation(): void
    {
        $this->merge([
            'payroll_run_id' =>
                $this->filled('payroll_run_id')
                    ? (int) $this->input('payroll_run_id')
                    : null,

            'name' =>
                $this->filled('name')
                    ? trim((string) $this->input('name'))
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

            'file_format' =>
                strtolower(
                    trim(
                        (string) $this->input(
                            'file_format',
                            'bank_csv'
                        )
                    )
                ),

            'notes' =>
                $this->filled('notes')
                    ? trim((string) $this->input('notes'))
                    : null,
        ]);
    }


    public function rules(): array
    {
        $tenantId = (int) Auth::user()->tenant_id;

        return [
            'payroll_run_id' => [
                'required',
                'integer',

                Rule::exists(
                    'payroll_runs',
                    'id'
                )->where(function ($query) use ($tenantId) {
                    $query
                        ->where(
                            'tenant_id',
                            $tenantId
                        )
                        ->whereNull(
                            'deleted_at'
                        );
                }),
            ],

            'name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'payment_date' => [
                'required',
                'date',
            ],

            'currency_code' => [
                'required',
                'string',
                'size:3',
                Rule::in([
                    'SAR',
                    'AED',
                    'BHD',
                    'EGP',
                    'KWD',
                    'OMR',
                    'QAR',
                    'USD',
                    'EUR',
                ]),
            ],

            'file_format' => [
                'required',
                'string',
                Rule::in([
                    'bank_csv',
                    'csv',
                    'txt',
                    'sif',
                ]),
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }


    public function messages(): array
    {
        return [
            'payroll_run_id.required' =>
                'يجب اختيار مسير الرواتب.',

            'payroll_run_id.integer' =>
                'مسير الرواتب المحدد غير صحيح.',

            'payroll_run_id.exists' =>
                'مسير الرواتب غير موجود أو لا يتبع الشركة الحالية.',

            'name.string' =>
                'اسم دفعة التحويل غير صحيح.',

            'name.max' =>
                'اسم دفعة التحويل يجب ألا يتجاوز 255 حرفًا.',

            'payment_date.required' =>
                'يجب تحديد تاريخ تحويل الرواتب.',

            'payment_date.date' =>
                'تاريخ تحويل الرواتب غير صحيح.',

            'currency_code.required' =>
                'يجب تحديد عملة التحويل.',

            'currency_code.size' =>
                'رمز العملة يجب أن يتكون من ثلاثة أحرف.',

            'currency_code.in' =>
                'عملة التحويل المحددة غير مدعومة.',

            'file_format.required' =>
                'يجب تحديد صيغة ملف التحويل.',

            'file_format.in' =>
                'صيغة ملف التحويل المحددة غير صحيحة.',

            'notes.string' =>
                'ملاحظات الدفعة غير صحيحة.',

            'notes.max' =>
                'الملاحظات يجب ألا تتجاوز 5000 حرف.',
        ];
    }


    public function attributes(): array
    {
        return [
            'payroll_run_id' =>
                'مسير الرواتب',

            'name' =>
                'اسم دفعة التحويل',

            'payment_date' =>
                'تاريخ التحويل',

            'currency_code' =>
                'العملة',

            'file_format' =>
                'صيغة الملف',

            'notes' =>
                'الملاحظات',
        ];
    }
}