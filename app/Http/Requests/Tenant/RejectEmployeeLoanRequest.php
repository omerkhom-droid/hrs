<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class RejectEmployeeLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) (
            $this->user()?->tenant_id &&
            $this->user()?->can('loans.approve')
        );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'rejection_reason' => trim(
                (string) $this->input(
                    'rejection_reason'
                )
            ),
        ]);
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => [
                'required',
                'string',
                'min:3',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_reason.required' =>
                'سبب رفض السلفة مطلوب.',

            'rejection_reason.min' =>
                'سبب الرفض يجب ألا يقل عن 3 أحرف.',

            'rejection_reason.max' =>
                'سبب الرفض طويل جدًا.',
        ];
    }

    public function attributes(): array
    {
        return [
            'rejection_reason' => 'سبب الرفض',
        ];
    }
}