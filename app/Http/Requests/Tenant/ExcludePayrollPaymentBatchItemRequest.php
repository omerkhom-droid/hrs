<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ExcludePayrollPaymentBatchItemRequest extends FormRequest
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
            'reason' =>
                trim(
                    (string) $this->input(
                        'reason',
                        ''
                    )
                ),
        ]);
    }


    public function rules(): array
    {
        return [
            'reason' => [
                'required',
                'string',
                'min:5',
                'max:2000',
            ],
        ];
    }


    public function messages(): array
    {
        return [
            'reason.required' =>
                'يجب كتابة سبب استبعاد الموظف.',

            'reason.min' =>
                'سبب الاستبعاد يجب ألا يقل عن 5 أحرف.',

            'reason.max' =>
                'سبب الاستبعاد يجب ألا يتجاوز 2000 حرف.',
        ];
    }
}