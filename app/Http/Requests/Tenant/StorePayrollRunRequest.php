<?php

namespace App\Http\Requests\Tenant;

use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePayrollRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return
            $user !== null &&
            $user->tenant_id !== null &&
            $user->can('payroll.process');
    }


    protected function prepareForValidation(): void
    {
        $employeeIds = $this->input(
            'employee_ids'
        );

        if (is_array($employeeIds)) {
            $employeeIds = array_values(
                array_unique(
                    array_filter(
                        array_map(
                            'intval',
                            $employeeIds
                        )
                    )
                )
            );
        }


        $defaultCurrency =
            $this->user()?->tenant?->currency_code
            ?? 'SAR';


        $this->merge([
            'type' =>
                $this->input(
                    'type',
                    PayrollRun::TYPE_REGULAR
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

            'employee_ids' =>
                $employeeIds,

            'notes' =>
                $this->filled('notes')
                    ? trim(
                        (string) $this->input('notes')
                    )
                    : null,
        ]);
    }


    public function rules(): array
    {
        $tenantId =
            (int) $this->user()->tenant_id;

        return [
            'payroll_period_id' => [
                'required',
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


            'type' => [
                'required',

                Rule::in([
                    PayrollRun::TYPE_REGULAR,
                    PayrollRun::TYPE_OFF_CYCLE,
                    PayrollRun::TYPE_FINAL_SETTLEMENT,
                ]),
            ],


            'currency_code' => [
                'required',
                'string',
                'size:3',
                'regex:/^[A-Z]{3}$/',
            ],


            /*
             * إذا لم يتم إرسال الموظفين،
             * سيقوم النظام باختيار جميع الموظفين المؤهلين.
             */
            'employee_ids' => [
                'nullable',
                'array',
                'min:1',
                'max:1000',
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
                if (
                    $validator->errors()
                        ->has('payroll_period_id')
                ) {
                    return;
                }


                $tenantId =
                    (int) $this->user()->tenant_id;


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


                if (!$period) {
                    return;
                }


                /*
                 * لا يسمح بإنشاء تشغيل رواتب
                 * إلا عندما تكون الفترة مفتوحة.
                 */
                if (
                    $period->status
                        !== PayrollPeriod::STATUS_OPEN
                ) {
                    $validator->errors()->add(
                        'payroll_period_id',
                        'يجب فتح فترة الرواتب قبل إنشاء تشغيل جديد.'
                    );
                }


                if ($period->is_locked) {
                    $validator->errors()->add(
                        'payroll_period_id',
                        'فترة الرواتب مقفلة ولا يمكن إنشاء تشغيل جديد عليها.'
                    );
                }


                /*
                 * لا يسمح بأكثر من تشغيل رواتب عادي
                 * غير ملغى لنفس الفترة.
                 */
                if (
                    $this->input('type')
                        === PayrollRun::TYPE_REGULAR
                ) {
                    $regularRunExists =
                        PayrollRun::withoutGlobalScopes()
                            ->where(
                                'tenant_id',
                                $tenantId
                            )
                            ->where(
                                'payroll_period_id',
                                $period->id
                            )
                            ->where(
                                'type',
                                PayrollRun::TYPE_REGULAR
                            )
                            ->where(
                                'status',
                                '!=',
                                PayrollRun::STATUS_CANCELLED
                            )
                            ->whereNull(
                                'deleted_at'
                            )
                            ->exists();


                    if ($regularRunExists) {
                        $validator->errors()->add(
                            'type',
                            'يوجد تشغيل رواتب عادي لهذه الفترة مسبقًا. يمكنك استخدام تشغيل خارج الدورة عند الحاجة.'
                        );
                    }
                }
            },
        ];
    }


    public function messages(): array
    {
        return [
            'payroll_period_id.required' =>
                'فترة الرواتب مطلوبة.',

            'payroll_period_id.exists' =>
                'فترة الرواتب المحددة غير موجودة.',


            'type.required' =>
                'نوع تشغيل الرواتب مطلوب.',

            'type.in' =>
                'نوع تشغيل الرواتب غير صحيح.',


            'currency_code.required' =>
                'رمز العملة مطلوب.',

            'currency_code.size' =>
                'رمز العملة يجب أن يتكون من ثلاثة أحرف.',

            'currency_code.regex' =>
                'رمز العملة يجب أن يحتوي على أحرف إنجليزية كبيرة فقط.',


            'employee_ids.array' =>
                'قائمة الموظفين غير صحيحة.',

            'employee_ids.min' =>
                'يجب اختيار موظف واحد على الأقل.',

            'employee_ids.max' =>
                'لا يمكن اختيار أكثر من 1000 موظف في تشغيل واحد.',


            'employee_ids.*.required' =>
                'رقم الموظف مطلوب.',

            'employee_ids.*.integer' =>
                'رقم الموظف غير صحيح.',

            'employee_ids.*.distinct' =>
                'يوجد موظف مكرر في القائمة.',

            'employee_ids.*.exists' =>
                'أحد الموظفين المحددين غير موجود أو لا يتبع الشركة.',


            'notes.max' =>
                'الملاحظات يجب ألا تتجاوز 2000 حرف.',
        ];
    }


    public function attributes(): array
    {
        return [
            'payroll_period_id' =>
                'فترة الرواتب',

            'type' =>
                'نوع التشغيل',

            'currency_code' =>
                'العملة',

            'employee_ids' =>
                'الموظفون',

            'employee_ids.*' =>
                'الموظف',

            'notes' =>
                'الملاحظات',
        ];
    }
}