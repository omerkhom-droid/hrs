<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class ApproveEmployeeLoanRequest extends FormRequest
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
            'approval_notes' =>
                $this->filled('approval_notes')
                    ? trim(
                        (string) $this->input(
                            'approval_notes'
                        )
                    )
                    : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'approved_amount' => [
                'required',
                'numeric',
                'min:1',
                'max:999999999.99',
            ],

            'installments_count' => [
                'required',
                'integer',
                'min:1',
                'max:60',
            ],

            'first_installment_date' => [
                'required',
                'date_format:Y-m-d',
            ],

            'approval_notes' => [
                'nullable',
                'string',
                'max:3000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'approved_amount.required' =>
                'المبلغ المعتمد مطلوب.',

            'approved_amount.numeric' =>
                'المبلغ المعتمد يجب أن يكون رقمًا.',

            'approved_amount.min' =>
                'المبلغ المعتمد يجب أن يكون أكبر من صفر.',

            'approved_amount.max' =>
                'المبلغ المعتمد يتجاوز الحد المسموح.',

            'installments_count.required' =>
                'عدد الأقساط مطلوب.',

            'installments_count.integer' =>
                'عدد الأقساط يجب أن يكون رقمًا صحيحًا.',

            'installments_count.min' =>
                'يجب أن يكون عدد الأقساط قسطًا واحدًا على الأقل.',

            'installments_count.max' =>
                'عدد الأقساط لا يمكن أن يتجاوز 60 قسطًا.',

            'first_installment_date.required' =>
                'تاريخ أول قسط مطلوب.',

            'first_installment_date.date_format' =>
                'صيغة تاريخ أول قسط غير صحيحة.',

            'approval_notes.max' =>
                'ملاحظات الاعتماد طويلة جدًا.',
        ];
    }
}