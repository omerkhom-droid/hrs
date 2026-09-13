<?php

namespace App\Http\Requests\Tenant;

use App\Models\PayrollPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePayrollPeriodRequest extends FormRequest
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
        $year = $this->input('year');
        $month = $this->input('month');

        $this->merge([
            'name' => trim(
                (string) $this->input(
                    'name',
                    ''
                )
            ),

            'year' => is_numeric($year)
                ? (int) $year
                : $year,

            'month' => is_numeric($month)
                ? (int) $month
                : $month,
        ]);
    }

    protected function ignoredPayrollPeriodId(): ?int
    {
        return null;
    }

    public function rules(): array
    {
        $tenantId =
            (int) $this->user()->tenant_id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'year' => [
                'required',
                'integer',
                'min:2000',
                'max:2100',
            ],

            'month' => [
                'required',
                'integer',
                'between:1,12',

                Rule::unique(
                    'payroll_periods',
                    'month'
                )
                    ->where(function ($query) use ($tenantId) {
                        $query
                            ->where(
                                'tenant_id',
                                $tenantId
                            )
                            ->where(
                                'year',
                                (int) $this->input('year')
                            )
                            ->whereNull('deleted_at');
                    })
                    ->ignore(
                        $this->ignoredPayrollPeriodId()
                    ),
            ],

            'start_date' => [
                'required',
                'date',
            ],

            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
            ],

            'payment_date' => [
                'required',
                'date',
                'after_or_equal:end_date',
            ],

            'metadata' => [
                'nullable',
                'array',
            ],
        ];
    }


    public function withValidator(
        Validator $validator
    ): void {
        $validator->after(function (
            Validator $validator
        ) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $tenantId =
                (int) $this->user()->tenant_id;

            $startDate = Carbon::parse(
                $this->input('start_date')
            )->startOfDay();

            $endDate = Carbon::parse(
                $this->input('end_date')
            )->startOfDay();


            /*
            |--------------------------------------------------------------------------
            | مدة الفترة
            |--------------------------------------------------------------------------
            */

            $durationDays =
                $startDate->diffInDays(
                    $endDate
                ) + 1;

            if ($durationDays > 62) {
                $validator->errors()->add(
                    'end_date',
                    'لا يمكن أن تتجاوز فترة الرواتب 62 يومًا.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | منع تداخل الفترات
            |--------------------------------------------------------------------------
            */

            $hasOverlap =
                PayrollPeriod::query()
                    ->where(
                        'tenant_id',
                        $tenantId
                    )
                    ->whereNotIn(
                        'status',
                        [
                            PayrollPeriod::STATUS_CANCELLED,
                        ]
                    )
                    ->when(
                        $this->ignoredPayrollPeriodId(),
                        function ($query, $periodId) {
                            $query->whereKeyNot(
                                $periodId
                            );
                        }
                    )
                    ->whereDate(
                        'start_date',
                        '<=',
                        $endDate->toDateString()
                    )
                    ->whereDate(
                        'end_date',
                        '>=',
                        $startDate->toDateString()
                    )
                    ->exists();

            if ($hasOverlap) {
                $validator->errors()->add(
                    'start_date',
                    'تتداخل هذه الفترة مع فترة رواتب موجودة مسبقًا.'
                );
            }
        });
    }


    public function attributes(): array
    {
        return [
            'name' =>
                'اسم فترة الرواتب',

            'year' =>
                'السنة',

            'month' =>
                'الشهر',

            'start_date' =>
                'تاريخ بداية الفترة',

            'end_date' =>
                'تاريخ نهاية الفترة',

            'payment_date' =>
                'تاريخ صرف الرواتب',
        ];
    }


    public function messages(): array
    {
        return [
            'name.required' =>
                'اسم فترة الرواتب مطلوب.',

            'year.required' =>
                'السنة مطلوبة.',

            'year.integer' =>
                'السنة غير صحيحة.',

            'month.required' =>
                'الشهر مطلوب.',

            'month.between' =>
                'الشهر يجب أن يكون من 1 إلى 12.',

            'month.unique' =>
                'توجد فترة رواتب مسجلة لهذا الشهر والسنة.',

            'start_date.required' =>
                'تاريخ بداية الفترة مطلوب.',

            'end_date.required' =>
                'تاريخ نهاية الفترة مطلوب.',

            'end_date.after_or_equal' =>
                'تاريخ النهاية يجب أن يكون بعد تاريخ البداية أو مساويًا له.',

            'payment_date.required' =>
                'تاريخ صرف الرواتب مطلوب.',

            'payment_date.after_or_equal' =>
                'تاريخ صرف الرواتب يجب أن يكون بعد نهاية الفترة أو مساويًا لها.',
        ];
    }
}