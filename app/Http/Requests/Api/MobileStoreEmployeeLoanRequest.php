<?php

namespace App\Http\Requests\Api;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MobileStoreEmployeeLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) (
            $this->user()?->tenant_id &&
            $this->user()?->can('self_service.loans')
        );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'employee_id' => $this->currentEmployeeId(),
            'loan_type' => $this->input(
                'loan_type',
                'salary_advance'
            ),
            'installments_count' => $this->input(
                'installments_count',
                1
            ),
            'reason' => trim(
                (string) $this->input('reason')
            ),
            'employee_notes' => $this->filled('employee_notes')
                ? trim((string) $this->input('employee_notes'))
                : null,
        ]);
    }

    public function rules(): array
    {
        $tenantId = (int) $this->user()->tenant_id;

        return [
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')->where(
                    fn ($query) => $query
                        ->where('tenant_id', $tenantId)
                        ->where('user_id', $this->user()->id)
                        ->whereNull('deleted_at')
                ),
            ],
            'loan_type' => [
                'required',
                Rule::in([
                    'salary_advance',
                    'personal_loan',
                    'emergency_loan',
                ]),
            ],
            'requested_amount' => [
                'required',
                'numeric',
                'min:1',
                'max:999999999.99',
            ],
            'installments_count' => [
                'required',
                'integer',
                'between:1,60',
            ],
            'first_installment_date' => [
                'nullable',
                'date_format:Y-m-d',
            ],
            'reason' => [
                'required',
                'string',
                'min:3',
                'max:2000',
            ],
            'employee_notes' => [
                'nullable',
                'string',
                'max:3000',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (!$this->currentEmployeeId()) {
                    $validator->errors()->add(
                        'employee_id',
                        'لا يوجد ملف موظف مرتبط بحسابك.'
                    );
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'لا يوجد ملف موظف مرتبط بحسابك.',
            'employee_id.exists' => 'ملف الموظف المرتبط بالحساب غير صحيح.',
            'loan_type.required' => 'نوع السلفة مطلوب.',
            'loan_type.in' => 'نوع السلفة غير صحيح.',
            'requested_amount.required' => 'مبلغ السلفة مطلوب.',
            'requested_amount.numeric' => 'مبلغ السلفة يجب أن يكون رقمًا.',
            'requested_amount.min' => 'مبلغ السلفة يجب أن يكون أكبر من صفر.',
            'requested_amount.max' => 'مبلغ السلفة يتجاوز الحد المسموح.',
            'installments_count.required' => 'عدد الأقساط مطلوب.',
            'installments_count.integer' => 'عدد الأقساط يجب أن يكون رقمًا صحيحًا.',
            'installments_count.between' => 'عدد الأقساط يجب أن يكون بين 1 و60.',
            'first_installment_date.date_format' => 'صيغة تاريخ أول قسط غير صحيحة.',
            'reason.required' => 'سبب طلب السلفة مطلوب.',
            'reason.min' => 'سبب طلب السلفة يجب ألا يقل عن 3 أحرف.',
            'reason.max' => 'سبب طلب السلفة طويل جدًا.',
            'employee_notes.max' => 'الملاحظات طويلة جدًا.',
        ];
    }

    private function currentEmployeeId(): ?int
    {
        $id = Employee::withoutGlobalScopes()
            ->where('tenant_id', $this->user()?->tenant_id)
            ->where('user_id', $this->user()?->id)
            ->whereNull('deleted_at')
            ->value('id');

        return $id ? (int) $id : null;
    }
}
