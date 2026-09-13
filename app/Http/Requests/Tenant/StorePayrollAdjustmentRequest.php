<?php

namespace App\Http\Requests\Tenant;

use App\Models\PayrollAdjustment;
use App\Models\PayrollPeriod;
use App\Models\SalaryComponent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePayrollAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return
            $user !== null &&
            $user->tenant_id !== null &&
            $user->can('payroll.manage');
    }


    protected function prepareForValidation(): void
    {
        $defaultCurrency =
            $this->user()
                ?->tenant
                ?->currency_code
            ?? 'SAR';

        $this->merge([
            'employee_id' =>
                $this->filled('employee_id')
                    ? (int) $this->input(
                        'employee_id'
                    )
                    : null,

            'payroll_period_id' =>
                $this->filled(
                    'payroll_period_id'
                )
                    ? (int) $this->input(
                        'payroll_period_id'
                    )
                    : null,

            'salary_component_id' =>
                $this->filled(
                    'salary_component_id'
                )
                    ? (int) $this->input(
                        'salary_component_id'
                    )
                    : null,

            'type' =>
                strtolower(
                    trim(
                        (string) $this->input(
                            'type'
                        )
                    )
                ),

            'currency_code' =>
                strtoupper(
                    trim(
                        (string) $this->input(
                            'currency_code',
                            $defaultCurrency
                        )
                    )
                ),

            'reason' =>
                trim(
                    (string) $this->input(
                        'reason'
                    )
                ),

            'notes' =>
                $this->filled('notes')
                    ? trim(
                        (string) $this->input(
                            'notes'
                        )
                    )
                    : null,
        ]);
    }


    public function rules(): array
    {
        $tenantId =
            (int) $this->user()->tenant_id;

        return [
            'employee_id' => [
                'required',
                'integer',

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


            'payroll_period_id' => [
                'nullable',
                'integer',

                Rule::exists(
                    'payroll_periods',
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


            'salary_component_id' => [
                'nullable',
                'integer',

                Rule::exists(
                    'salary_components',
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


            'type' => [
                'required',

                Rule::in([
                    PayrollAdjustment::TYPE_EARNING,
                    PayrollAdjustment::TYPE_DEDUCTION,
                ]),
            ],


            'amount' => [
                'required',
                'numeric',
                'gt:0',
                'max:999999999.99',
            ],


            'currency_code' => [
                'required',
                'string',
                'size:3',
                'regex:/^[A-Z]{3}$/',
            ],


            'effective_date' => [
                'required',
                'date',
            ],


            'reason' => [
                'required',
                'string',
                'max:500',
            ],


            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }


    public function after(): array
    {
        return [
            function (
                Validator $validator
            ): void {
                if ($validator->errors()->any()) {
                    return;
                }

                $tenantId =
                    (int) $this->user()->tenant_id;


                /*
                 * التحقق من فترة الرواتب.
                 */
                if (
                    $this->filled(
                        'payroll_period_id'
                    )
                ) {
                    $period =
                        PayrollPeriod::withoutGlobalScopes()
                            ->where(
                                'tenant_id',
                                $tenantId
                            )
                            ->whereKey(
                                $this->integer(
                                    'payroll_period_id'
                                )
                            )
                            ->whereNull(
                                'deleted_at'
                            )
                            ->first();

                    if ($period) {
                        if ($period->is_locked) {
                            $validator
                                ->errors()
                                ->add(
                                    'payroll_period_id',
                                    'فترة الرواتب المحددة مقفلة.'
                                );
                        }

                        if (
                            in_array(
                                $period->status,
                                [
                                    PayrollPeriod::STATUS_PAID,
                                    PayrollPeriod::STATUS_CLOSED,
                                    PayrollPeriod::STATUS_CANCELLED,
                                ],
                                true
                            )
                        ) {
                            $validator
                                ->errors()
                                ->add(
                                    'payroll_period_id',
                                    'حالة فترة الرواتب لا تسمح بإضافة تسوية.'
                                );
                        }

                        $effectiveDate =
                            Carbon::parse(
                                $this->input(
                                    'effective_date'
                                )
                            )->startOfDay();

                        $startDate =
                            $period
                                ->start_date
                                ->copy()
                                ->startOfDay();

                        $endDate =
                            $period
                                ->end_date
                                ->copy()
                                ->endOfDay();

                        if (
                            $effectiveDate->lt(
                                $startDate
                            )
                            ||
                            $effectiveDate->gt(
                                $endDate
                            )
                        ) {
                            $validator
                                ->errors()
                                ->add(
                                    'effective_date',
                                    'تاريخ استحقاق التسوية يجب أن يكون داخل فترة الرواتب المحددة.'
                                );
                        }
                    }
                }


                /*
                 * التحقق من مكون الراتب.
                 */
                if (
                    $this->filled(
                        'salary_component_id'
                    )
                ) {
                    $component =
                        SalaryComponent::withoutGlobalScopes()
                            ->where(
                                'tenant_id',
                                $tenantId
                            )
                            ->whereKey(
                                $this->integer(
                                    'salary_component_id'
                                )
                            )
                            ->whereNull(
                                'deleted_at'
                            )
                            ->first();

                    if ($component) {
                        if (!$component->is_active) {
                            $validator
                                ->errors()
                                ->add(
                                    'salary_component_id',
                                    'مكون الراتب المحدد غير نشط.'
                                );
                        }

                        if (
                            $component->type !==
                            $this->input('type')
                        ) {
                            $validator
                                ->errors()
                                ->add(
                                    'salary_component_id',
                                    'نوع مكون الراتب لا يطابق نوع التسوية.'
                                );
                        }

                        if (
                            !$component
                                ->affects_net_salary
                        ) {
                            $validator
                                ->errors()
                                ->add(
                                    'salary_component_id',
                                    'مكون الراتب المحدد لا يؤثر على صافي الراتب.'
                                );
                        }
                    }
                }
            },
        ];
    }


    public function messages(): array
    {
        return [
            'employee_id.required' =>
                'الموظف مطلوب.',

            'employee_id.exists' =>
                'الموظف المحدد غير موجود أو لا يتبع الشركة.',


            'payroll_period_id.exists' =>
                'فترة الرواتب المحددة غير موجودة.',


            'salary_component_id.exists' =>
                'مكون الراتب المحدد غير موجود.',


            'type.required' =>
                'نوع التسوية مطلوب.',

            'type.in' =>
                'نوع التسوية يجب أن يكون استحقاقًا أو خصمًا.',


            'amount.required' =>
                'مبلغ التسوية مطلوب.',

            'amount.numeric' =>
                'مبلغ التسوية يجب أن يكون رقمًا.',

            'amount.gt' =>
                'مبلغ التسوية يجب أن يكون أكبر من صفر.',

            'amount.max' =>
                'مبلغ التسوية أكبر من الحد المسموح.',


            'currency_code.required' =>
                'رمز العملة مطلوب.',

            'currency_code.size' =>
                'رمز العملة يجب أن يتكون من ثلاثة أحرف.',

            'currency_code.regex' =>
                'رمز العملة يجب أن يحتوي على أحرف إنجليزية كبيرة.',


            'effective_date.required' =>
                'تاريخ استحقاق التسوية مطلوب.',

            'effective_date.date' =>
                'تاريخ استحقاق التسوية غير صحيح.',


            'reason.required' =>
                'سبب التسوية مطلوب.',

            'reason.max' =>
                'سبب التسوية يجب ألا يتجاوز 500 حرف.',


            'notes.max' =>
                'الملاحظات يجب ألا تتجاوز 2000 حرف.',
        ];
    }


    public function attributes(): array
    {
        return [
            'employee_id' =>
                'الموظف',

            'payroll_period_id' =>
                'فترة الرواتب',

            'salary_component_id' =>
                'مكون الراتب',

            'type' =>
                'نوع التسوية',

            'amount' =>
                'المبلغ',

            'currency_code' =>
                'العملة',

            'effective_date' =>
                'تاريخ الاستحقاق',

            'reason' =>
                'سبب التسوية',

            'notes' =>
                'الملاحظات',
        ];
    }
}