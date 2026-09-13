<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreLeaveBalanceCarryForwardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return
            $this->user() !== null &&
            $this->user()->can(
                'leave.manage'
            );
    }


    public function rules(): array
    {
        $tenantId = (int) $this
            ->user()
            ->tenant_id;

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
                            ->whereNot(
                                'employment_status',
                                'terminated'
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
                            ->where(
                                'requires_balance',
                                true
                            )
                            ->where(
                                'allow_carry_forward',
                                true
                            )
                            ->whereNull(
                                'deleted_at'
                            )
                ),
            ],

            'source_year' => [
                'required',
                'integer',
                'between:2020,2099',
            ],

            'target_year' => [
                'required',
                'integer',
                'between:2021,2100',
            ],

            'reason' => [
                'required',
                'string',
                'min:3',
                'max:500',
            ],

            'confirmed' => [
                'required',
                'accepted',
            ],
        ];
    }


    public function after(): array
    {
        return [
            function (Validator $validator) {
                $sourceYear = (int) $this->input(
                    'source_year'
                );

                $targetYear = (int) $this->input(
                    'target_year'
                );

                if (
                    $sourceYear > 0 &&
                    $targetYear > 0 &&
                    $targetYear !==
                    $sourceYear + 1
                ) {
                    $validator->errors()->add(
                        'target_year',
                        'يجب أن تكون السنة المستهدفة هي السنة التالية مباشرة.'
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

            'source_year' =>
                'السنة المصدر',

            'target_year' =>
                'السنة المستهدفة',

            'reason' =>
                'سبب الترحيل',

            'confirmed' =>
                'تأكيد إقفال الأرصدة',
        ];
    }


    public function messages(): array
    {
        return [
            'employee_ids.required' =>
                'يجب اختيار موظف واحد على الأقل.',

            'employee_ids.max' =>
                'لا يمكن ترحيل أرصدة أكثر من 500 موظف دفعة واحدة.',

            'employee_ids.*.distinct' =>
                'يوجد موظف مكرر في القائمة.',

            'employee_ids.*.exists' =>
                'أحد الموظفين غير موجود أو غير متاح للترحيل.',

            'leave_type_id.exists' =>
                'نوع الإجازة غير موجود أو لا يسمح بترحيل الرصيد.',

            'source_year.between' =>
                'السنة المصدر غير صحيحة.',

            'target_year.between' =>
                'السنة المستهدفة غير صحيحة.',

            'confirmed.accepted' =>
                'يجب تأكيد إقفال أرصدة السنة المصدر قبل التنفيذ.',
        ];
    }


    protected function prepareForValidation(): void
    {
        $sourceYear = (int) $this->input(
            'source_year'
        );

        /*
         * إذا لم ترسل الواجهة السنة المستهدفة،
         * يتم تحديد السنة التالية تلقائيًا.
         */
        if (
            $sourceYear > 0 &&
            !$this->filled('target_year')
        ) {
            $this->merge([
                'target_year' =>
                    $sourceYear + 1,
            ]);
        }
    }
}