<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBulkLeaveBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return
            $this->user() !== null &&
            $this->user()->can('leave.manage');
    }


    public function rules(): array
    {
        $tenantId = (int) $this->user()->tenant_id;

        return [
            'employee_ids' => [
                'required',
                'array',
                'min:1',
                'max:500',
            ],

            'employee_ids.*' => [
                'required',
                'integer',
                'distinct',

                Rule::exists(
                    'employees',
                    'id'
                )->where(
                    fn ($query) =>
                        $query
                            ->where(
                                'tenant_id',
                                $tenantId
                            )
                            ->whereNull(
                                'deleted_at'
                            )
                ),
            ],

            'leave_type_id' => [
                'required',
                'integer',

                Rule::exists(
                    'leave_types',
                    'id'
                )->where(
                    fn ($query) =>
                        $query
                            ->where(
                                'tenant_id',
                                $tenantId
                            )
                            ->where(
                                'is_active',
                                true
                            )
                            ->whereNull(
                                'deleted_at'
                            )
                ),
            ],

            'year' => [
                'required',
                'integer',
                'between:2020,2100',
            ],

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
                'max:999.99',
                'decimal:0,2',
            ],

            'reason' => [
                'required',
                'string',
                'min:3',
                'max:500',
            ],
        ];
    }


    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (
                    in_array(
                        $this->input('mode'),
                        [
                            'add',
                            'deduct',
                        ],
                        true
                    ) &&
                    (float) $this->input(
                        'days',
                        0
                    ) <= 0
                ) {
                    $validator->errors()->add(
                        'days',
                        'عدد الأيام يجب أن يكون أكبر من صفر.'
                    );
                }
            },
        ];
    }


    public function attributes(): array
    {
        return [
            'employee_ids' =>
                'الموظفون',

            'employee_ids.*' =>
                'الموظف',

            'leave_type_id' =>
                'نوع الإجازة',

            'year' =>
                'السنة',

            'mode' =>
                'نوع العملية',

            'days' =>
                'عدد الأيام',

            'reason' =>
                'سبب التسوية',
        ];
    }


    public function messages(): array
    {
        return [
            'employee_ids.required' =>
                'يجب اختيار موظف واحد على الأقل.',

            'employee_ids.max' =>
                'لا يمكن تنفيذ العملية على أكثر من 500 موظف دفعة واحدة.',

            'employee_ids.*.distinct' =>
                'يوجد موظف مكرر في القائمة.',

            'employee_ids.*.exists' =>
                'أحد الموظفين المحددين غير موجود أو لا يتبع الشركة.',

            'leave_type_id.exists' =>
                'نوع الإجازة غير موجود أو غير نشط.',

            'days.decimal' =>
                'عدد الأيام يقبل منزلتين عشريتين كحد أقصى.',
        ];
    }
}