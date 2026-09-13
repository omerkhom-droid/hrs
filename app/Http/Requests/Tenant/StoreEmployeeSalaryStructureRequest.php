<?php

namespace App\Http\Requests\Tenant;

use App\Models\SalaryComponent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEmployeeSalaryStructureRequest extends FormRequest
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
        $components = collect(
            $this->input('components', [])
        )->map(function ($component) {

            if (!is_array($component)) {
                return $component;
            }

            $component['is_active'] = filter_var(
                $component['is_active'] ?? true,
                FILTER_VALIDATE_BOOLEAN
            );

            return $component;
        })->values()->all();

        $this->merge([
            'currency_code' => strtoupper(
                trim(
                    (string) $this->input(
                        'currency_code',
                        'SAR'
                    )
                )
            ),

            'components' => $components,
        ]);
    }


    public function rules(): array
    {
        $tenantId = (int) $this->user()->tenant_id;

        return [
            'employee_id' => [
                'required',
                'integer',

                Rule::exists(
                    'employees',
                    'id'
                )->where(function ($query) use ($tenantId) {
                    $query
                        ->where(
                            'tenant_id',
                            $tenantId
                        )
                        ->whereNull('deleted_at');
                }),
            ],

            'effective_from' => [
                'required',
                'date',
            ],

            'effective_to' => [
                'nullable',
                'date',
                'after_or_equal:effective_from',
            ],

            'currency_code' => [
                'required',
                'string',
                'size:3',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:3000',
            ],

            'components' => [
                'required',
                'array',
                'min:1',
                'max:100',
            ],

            'components.*.salary_component_id' => [
                'required',
                'integer',
                'distinct',

                Rule::exists(
                    'salary_components',
                    'id'
                )->where(function ($query) use ($tenantId) {
                    $query
                        ->where(
                            'tenant_id',
                            $tenantId
                        )
                        ->where(
                            'is_active',
                            true
                        )
                        ->whereNull('deleted_at');
                }),
            ],

            'components.*.amount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],

            'components.*.percentage' => [
                'nullable',
                'numeric',
                'min:0',
                'max:1000',
            ],

            'components.*.rate' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],

            'components.*.quantity' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.9999',
            ],

            'components.*.formula' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'components.*.is_active' => [
                'required',
                'boolean',
            ],

            'components.*.metadata' => [
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

            $rows = collect(
                $this->input('components', [])
            );

            $componentIds = $rows
                ->pluck('salary_component_id')
                ->filter()
                ->map(
                    fn ($id) => (int) $id
                )
                ->values();

            $definitions = SalaryComponent::query()
                ->where(
                    'tenant_id',
                    $tenantId
                )
                ->whereIn(
                    'id',
                    $componentIds
                )
                ->where(
                    'is_active',
                    true
                )
                ->get()
                ->keyBy('id');


            /*
            |--------------------------------------------------------------------------
            | التحقق من جميع مكونات الراتب
            |--------------------------------------------------------------------------
            */

            foreach ($rows as $index => $row) {
                $componentId = (int) (
                    $row['salary_component_id']
                    ?? 0
                );

                $definition =
                    $definitions->get($componentId);

                if (!$definition) {
                    $validator->errors()->add(
                        "components.{$index}.salary_component_id",
                        'مكون الراتب غير موجود أو غير نشط.'
                    );

                    continue;
                }

                $isActive = filter_var(
                    $row['is_active'] ?? true,
                    FILTER_VALIDATE_BOOLEAN
                );

                if (!$isActive) {
                    continue;
                }

                $this->validateComponentValues(
                    $validator,
                    $definitions,
                    $componentIds,
                    $definition,
                    $row,
                    $index
                );
            }


            /*
            |--------------------------------------------------------------------------
            | الراتب الأساسي
            |--------------------------------------------------------------------------
            */

            $basicSalaryRows = $rows
                ->filter(function ($row) use ($definitions) {
                    $definition = $definitions->get(
                        (int) (
                            $row['salary_component_id']
                            ?? 0
                        )
                    );

                    $isActive = filter_var(
                        $row['is_active'] ?? true,
                        FILTER_VALIDATE_BOOLEAN
                    );

                    return
                        $isActive &&
                        $definition &&
                        $definition->category ===
                            'basic_salary';
                })
                ->values();

            if ($basicSalaryRows->isEmpty()) {
                $validator->errors()->add(
                    'components',
                    'يجب إضافة مكون الراتب الأساسي إلى هيكل راتب الموظف.'
                );

                return;
            }

            if ($basicSalaryRows->count() > 1) {
                $validator->errors()->add(
                    'components',
                    'لا يمكن إضافة أكثر من مكون راتب أساسي واحد.'
                );

                return;
            }

            $basicRow =
                $basicSalaryRows->first();

            $basicDefinition = $definitions->get(
                (int) $basicRow['salary_component_id']
            );

            if (
                $basicDefinition->type !== 'earning' ||
                $basicDefinition->calculation_method !==
                    'fixed'
            ) {
                $validator->errors()->add(
                    'components',
                    'مكون الراتب الأساسي يجب أن يكون استحقاقًا بمبلغ ثابت.'
                );

                return;
            }

            $basicSalary =
                $basicRow['amount']
                ?? $basicDefinition->default_amount
                ?? 0;

            if ((float) $basicSalary <= 0) {
                $validator->errors()->add(
                    'components',
                    'قيمة الراتب الأساسي يجب أن تكون أكبر من صفر.'
                );
            }
        });
    }


    private function validateComponentValues(
        Validator $validator,
        $definitions,
        $selectedComponentIds,
        SalaryComponent $definition,
        array $row,
        int $index
    ): void {
        $method =
            $definition->calculation_method;


        /*
        |--------------------------------------------------------------------------
        | مبلغ ثابت
        |--------------------------------------------------------------------------
        */

        if ($method === 'fixed') {
            $amount =
                $row['amount']
                ?? $definition->default_amount;

            if (
                $amount === null ||
                $amount === ''
            ) {
                $validator->errors()->add(
                    "components.{$index}.amount",
                    'يجب إدخال مبلغ مكون الراتب.'
                );

                return;
            }

            if ((float) $amount < 0) {
                $validator->errors()->add(
                    "components.{$index}.amount",
                    'قيمة مكون الراتب لا يمكن أن تكون سالبة.'
                );
            }

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | نسبة مئوية
        |--------------------------------------------------------------------------
        */

        if ($method === 'percentage') {
            $percentage =
                $row['percentage']
                ?? $definition->default_percentage;

            if (
                $percentage === null ||
                $percentage === ''
            ) {
                $validator->errors()->add(
                    "components.{$index}.percentage",
                    'يجب إدخال النسبة المئوية.'
                );
            }

            if (
                $percentage !== null &&
                (float) $percentage < 0
            ) {
                $validator->errors()->add(
                    "components.{$index}.percentage",
                    'النسبة المئوية لا يمكن أن تكون سالبة.'
                );
            }

            $baseComponentId =
                $definition
                    ->percentage_base_component_id;

            if (!$baseComponentId) {
                $validator->errors()->add(
                    "components.{$index}.salary_component_id",
                    'لم يتم تحديد المكون الأساسي لحساب النسبة.'
                );

                return;
            }

            if (
                !$definitions->has(
                    (int) $baseComponentId
                )
            ) {
                $validator->errors()->add(
                    "components.{$index}.salary_component_id",
                    'المكون الأساسي المستخدم لحساب النسبة غير متاح.'
                );

                return;
            }

            if (
                !$selectedComponentIds->contains(
                    (int) $baseComponentId
                )
            ) {
                $validator->errors()->add(
                    "components.{$index}.salary_component_id",
                    'يجب إضافة المكون الأساسي الذي تعتمد عليه النسبة إلى هيكل الراتب.'
                );
            }

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | معادلة
        |--------------------------------------------------------------------------
        */

        if ($method === 'formula') {
            $formula = trim(
                (string) (
                    $row['formula']
                    ?? $definition->formula
                    ?? ''
                )
            );

            if ($formula === '') {
                $validator->errors()->add(
                    "components.{$index}.formula",
                    'يجب إدخال معادلة حساب مكون الراتب.'
                );
            }

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | كمية × سعر
        |--------------------------------------------------------------------------
        */

        if ($method === 'quantity_rate') {
            $rate =
                $row['rate']
                ?? $definition->default_rate;

            if (
                $rate === null ||
                $rate === ''
            ) {
                $validator->errors()->add(
                    "components.{$index}.rate",
                    'يجب إدخال سعر الوحدة.'
                );
            }

            if (
                $rate !== null &&
                (float) $rate < 0
            ) {
                $validator->errors()->add(
                    "components.{$index}.rate",
                    'سعر الوحدة لا يمكن أن يكون سالبًا.'
                );
            }

            /*
             * الكمية يمكن أن تكون فارغة للمكونات التي
             * تحصل على كميتها لاحقًا من الحضور أو الإضافي.
             */
            if (
                isset($row['quantity']) &&
                $row['quantity'] !== '' &&
                (float) $row['quantity'] < 0
            ) {
                $validator->errors()->add(
                    "components.{$index}.quantity",
                    'الكمية لا يمكن أن تكون سالبة.'
                );
            }
        }
    }


    public function attributes(): array
    {
        return [
            'employee_id' =>
                'الموظف',

            'effective_from' =>
                'تاريخ بداية السريان',

            'effective_to' =>
                'تاريخ نهاية السريان',

            'currency_code' =>
                'العملة',

            'notes' =>
                'الملاحظات',

            'components' =>
                'مكونات الراتب',

            'components.*.salary_component_id' =>
                'مكون الراتب',

            'components.*.amount' =>
                'المبلغ',

            'components.*.percentage' =>
                'النسبة المئوية',

            'components.*.rate' =>
                'سعر الوحدة',

            'components.*.quantity' =>
                'الكمية',

            'components.*.formula' =>
                'المعادلة',

            'components.*.is_active' =>
                'حالة المكون',
        ];
    }


    public function messages(): array
    {
        return [
            'components.required' =>
                'يجب إضافة مكونات إلى هيكل الراتب.',

            'components.min' =>
                'يجب إضافة مكون راتب واحد على الأقل.',

            'components.*.salary_component_id.distinct' =>
                'لا يمكن تكرار مكون الراتب داخل الهيكل نفسه.',

            'effective_to.after_or_equal' =>
                'تاريخ نهاية السريان يجب أن يكون بعد تاريخ البداية أو مساويًا له.',

            'currency_code.size' =>
                'رمز العملة يجب أن يتكون من 3 أحرف.',
        ];
    }
}