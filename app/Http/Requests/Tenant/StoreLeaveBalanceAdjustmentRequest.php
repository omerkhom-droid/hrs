<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreLeaveBalanceAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return
            $user !== null &&
            $user->tenant_id !== null &&
            $user->can('leave.manage');
    }


    protected function prepareForValidation(): void
    {
        $this->merge([
            'mode' =>
                strtolower(
                    trim(
                        (string) $this->input('mode', 'add')
                    )
                ),

            'days' =>
                $this->normalizeNumber(
                    $this->input('days')
                ),

            'year' =>
                (int) $this->input(
                    'year',
                    now()->year
                ),

            'reason' =>
                trim(
                    (string) $this->input('reason', '')
                ),
        ]);
    }


    public function rules(): array
    {
        $tenantId = $this->user()?->tenant_id;

        return [
            'employee_id' => [
                'required',
                'integer',

                Rule::exists(
                    'employees',
                    'id'
                )->where(function ($query) use ($tenantId) {
                    $query
                        ->where('tenant_id', $tenantId)
                        ->whereNull('deleted_at');
                }),
            ],

            'leave_type_id' => [
                'required',
                'integer',

                Rule::exists(
                    'leave_types',
                    'id'
                )->where(function ($query) use ($tenantId) {
                    $query
                        ->where('tenant_id', $tenantId)
                        ->where('is_active', true)
                        ->whereNull('deleted_at');
                }),
            ],

            /*
             * add: إضافة إلى الرصيد.
             * deduct: خصم من الرصيد.
             * set: تعيين الرصيد إلى قيمة محددة.
             */
            'mode' => [
                'required',
                Rule::in([
                    'add',
                    'deduct',
                    'set',
                ]),
            ],

            'days' => [
                'required',
                'numeric',
                'min:0',
                'max:9999',
            ],

            'year' => [
                'required',
                'integer',
                'min:2000',
                'max:' . (now()->year + 5),
            ],

            'reason' => [
                'required',
                'string',
                'min:5',
                'max:1000',
            ],
        ];
    }


    public function withValidator(
        Validator $validator
    ): void {
        $validator->after(function (
            Validator $validator
        ) {
            $mode = $this->input('mode');

            $days = (float) $this->input(
                'days',
                0
            );

            /*
             * يسمح بالقيمة صفر فقط عند تعيين الرصيد.
             */
            if (
                in_array(
                    $mode,
                    ['add', 'deduct'],
                    true
                ) &&
                $days <= 0
            ) {
                $validator
                    ->errors()
                    ->add(
                        'days',
                        'عدد الأيام يجب أن يكون أكبر من صفر.'
                    );
            }
        });
    }


    public function messages(): array
    {
        return [
            'employee_id.required' =>
                'يجب اختيار الموظف.',

            'employee_id.exists' =>
                'الموظف المحدد غير موجود أو لا يتبع هذه الشركة.',

            'leave_type_id.required' =>
                'يجب اختيار نوع الإجازة.',

            'leave_type_id.exists' =>
                'نوع الإجازة غير موجود أو غير نشط.',

            'mode.required' =>
                'يجب اختيار نوع التسوية.',

            'mode.in' =>
                'نوع التسوية المحدد غير صحيح.',

            'days.required' =>
                'يجب إدخال عدد الأيام.',

            'days.numeric' =>
                'عدد الأيام يجب أن يكون رقمًا.',

            'days.min' =>
                'عدد الأيام لا يمكن أن يكون سالبًا.',

            'days.max' =>
                'عدد الأيام المدخل أكبر من الحد المسموح.',

            'year.required' =>
                'يجب تحديد سنة الرصيد.',

            'year.integer' =>
                'سنة الرصيد غير صحيحة.',

            'year.min' =>
                'سنة الرصيد غير صحيحة.',

            'year.max' =>
                'سنة الرصيد تتجاوز الحد المسموح.',

            'reason.required' =>
                'يجب كتابة سبب التسوية.',

            'reason.min' =>
                'سبب التسوية يجب ألا يقل عن 5 أحرف.',

            'reason.max' =>
                'سبب التسوية يجب ألا يتجاوز 1000 حرف.',
        ];
    }


    private function normalizeNumber(
        mixed $value
    ): mixed {
        if (
            $value === null ||
            $value === ''
        ) {
            return $value;
        }

        $value = strtr(
            (string) $value,
            [
                '٠' => '0',
                '١' => '1',
                '٢' => '2',
                '٣' => '3',
                '٤' => '4',
                '٥' => '5',
                '٦' => '6',
                '٧' => '7',
                '٨' => '8',
                '٩' => '9',
                '٫' => '.',
                '٬' => '',
                ',' => '.',
            ]
        );

        return trim($value);
    }
}