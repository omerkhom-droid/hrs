<?php

namespace App\Http\Requests\Tenant;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return (bool) (
            $user?->tenant_id &&
            (
                $user->can('loans.manage') ||
                $user->can('self_service.loans')
            )
        );
    }

    protected function prepareForValidation(): void
    {
        $employeeId = $this->input('employee_id');

        /*
         * موظف الخدمة الذاتية لا يستطيع
         * إنشاء سلفة لموظف آخر.
         */
        if (!$this->user()?->can('loans.manage')) {
            $employeeId = $this->currentEmployeeId();
        }

        $this->merge([
            'employee_id' => $employeeId,

            'loan_type' =>
                $this->input(
                    'loan_type',
                    'salary_advance'
                ),

            'installments_count' =>
                $this->input(
                    'installments_count',
                    1
                ),

            'reason' => trim(
                (string) $this->input('reason')
            ),

            'employee_notes' =>
                $this->filled('employee_notes')
                    ? trim(
                        (string) $this->input(
                            'employee_notes'
                        )
                    )
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
                Rule::exists('employees', 'id')
                    ->where(
                        fn ($query) =>
                            $query
                                ->where(
                                    'tenant_id',
                                    $tenantId
                                )
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
                'min:1',
                'max:60',
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

    public function messages(): array
    {
        return [
            'employee_id.required' =>
                'الموظف مطلوب.',

            'employee_id.exists' =>
                'الموظف المحدد غير موجود.',

            'loan_type.required' =>
                'نوع السلفة مطلوب.',

            'loan_type.in' =>
                'نوع السلفة غير صحيح.',

            'requested_amount.required' =>
                'مبلغ السلفة مطلوب.',

            'requested_amount.numeric' =>
                'مبلغ السلفة يجب أن يكون رقمًا.',

            'requested_amount.min' =>
                'مبلغ السلفة يجب أن يكون أكبر من صفر.',

            'requested_amount.max' =>
                'مبلغ السلفة يتجاوز الحد المسموح.',

            'installments_count.required' =>
                'عدد الأقساط مطلوب.',

            'installments_count.integer' =>
                'عدد الأقساط يجب أن يكون رقمًا صحيحًا.',

            'installments_count.min' =>
                'يجب أن يكون عدد الأقساط قسطًا واحدًا على الأقل.',

            'installments_count.max' =>
                'عدد الأقساط لا يمكن أن يتجاوز 60 قسطًا.',

            'first_installment_date.date_format' =>
                'صيغة تاريخ أول قسط غير صحيحة.',

            'reason.required' =>
                'سبب طلب السلفة مطلوب.',

            'reason.min' =>
                'سبب طلب السلفة يجب ألا يقل عن 3 أحرف.',

            'reason.max' =>
                'سبب طلب السلفة طويل جدًا.',

            'employee_notes.max' =>
                'ملاحظات الموظف طويلة جدًا.',
        ];
    }

    public function attributes(): array
    {
        return [
            'employee_id' => 'الموظف',
            'loan_type' => 'نوع السلفة',
            'requested_amount' => 'مبلغ السلفة',
            'installments_count' => 'عدد الأقساط',
            'first_installment_date' => 'تاريخ أول قسط',
            'reason' => 'سبب السلفة',
            'employee_notes' => 'ملاحظات الموظف',
        ];
    }

    private function currentEmployeeId(): ?int
    {
        $employeeId = Employee::withoutGlobalScopes()
            ->where(
                'tenant_id',
                $this->user()?->tenant_id
            )
            ->where(
                'user_id',
                $this->user()?->id
            )
            ->whereNull('deleted_at')
            ->value('id');

        return $employeeId
            ? (int) $employeeId
            : null;
    }
}