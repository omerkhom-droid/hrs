<?php

namespace App\Http\Requests\Tenant;

use App\Models\EmployeeContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEmployeeContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can(
            'contracts.create'
        );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'contract_number' => $this->cleanUpper(
                $this->input('contract_number')
            ),
            'currency_code' => $this->cleanUpper(
                $this->input('currency_code')
            ) ?: 'SAR',
            'auto_renew' => $this->boolean('auto_renew'),
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
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at'),
            ],

            'renewed_from_id' => [
                'nullable',
                'integer',
                Rule::exists('employee_contracts', 'id')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at'),
            ],

            'contract_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('employee_contracts', 'contract_number')
                    ->where('tenant_id', $tenantId),
            ],

            'contract_type' => [
                'required',
                Rule::in([
                    'indefinite',
                    'fixed_term',
                    'temporary',
                    'seasonal',
                    'part_time',
                    'training',
                ]),
            ],

            'start_date' => [
                'required',
                'date',
            ],

            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],

            'probation_end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],

            'basic_salary' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999999999.99',
            ],

            'housing_allowance' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999999.99',
            ],

            'transport_allowance' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999999.99',
            ],

            'other_allowances' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999999.99',
            ],

            'currency_code' => [
                'required',
                'string',
                'size:3',
            ],

            'pay_frequency' => [
                'required',
                Rule::in([
                    'monthly',
                    'daily',
                    'hourly',
                ]),
            ],

            'working_hours_per_day' => [
                'required',
                'numeric',
                'min:0.5',
                'max:24',
            ],

            'working_days_per_week' => [
                'required',
                'integer',
                'between:1,7',
            ],

            'annual_leave_days' => [
                'required',
                'integer',
                'between:0,365',
            ],

            'notice_period_days' => [
                'required',
                'integer',
                'between:0,3650',
            ],

            'auto_renew' => [
                'required',
                'boolean',
            ],

            'renewal_notice_days' => [
                'required',
                'integer',
                'between:0,3650',
            ],

            'signed_at' => [
                'nullable',
                'date',
            ],

            'terms' => [
                'nullable',
                'string',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $routeContract = $this->route('contract');
            $contract = $routeContract instanceof EmployeeContract
                ? $routeContract
                : null;

            $type = $this->input(
                'contract_type',
                $contract?->contract_type
            );

            $endDate = $this->exists('end_date')
                ? $this->input('end_date')
                : $contract?->end_date;

            $probationEndDate = $this->exists('probation_end_date')
                ? $this->input('probation_end_date')
                : $contract?->probation_end_date;

            if ($type !== 'indefinite' && !$endDate) {
                $validator->errors()->add(
                    'end_date',
                    'تاريخ نهاية العقد مطلوب للعقود محددة المدة.'
                );
            }

            if ($type === 'indefinite' && $endDate) {
                $validator->errors()->add(
                    'end_date',
                    'العقد غير محدد المدة لا يحتوي على تاريخ نهاية.'
                );
            }

            if (
                $endDate &&
                $probationEndDate &&
                strtotime($probationEndDate) > strtotime($endDate)
            ) {
                $validator->errors()->add(
                    'probation_end_date',
                    'نهاية فترة التجربة يجب ألا تتجاوز نهاية العقد.'
                );
            }
        });
    }

    public function attributes(): array
    {
        return [
            'employee_id' => 'الموظف',
            'contract_number' => 'رقم العقد',
            'contract_type' => 'نوع العقد',
            'start_date' => 'تاريخ البداية',
            'end_date' => 'تاريخ النهاية',
            'probation_end_date' => 'نهاية فترة التجربة',
            'basic_salary' => 'الراتب الأساسي',
            'currency_code' => 'العملة',
        ];
    }

    protected function cleanUpper(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === ''
            ? null
            : mb_strtoupper($value);
    }
}