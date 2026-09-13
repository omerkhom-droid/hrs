<?php

namespace App\Http\Requests\Tenant;

use App\Models\SalaryComponent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSalaryComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user =
            $this->user();

        return
            $user !== null &&
            $user->tenant_id !== null &&
            $user->can(
                'payroll.manage'
            );
    }


    public function rules(): array
    {
        $tenantId =
            (int) $this->user()
                ->tenant_id;

        $componentId =
            $this->componentId();

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9_-]+$/',

                Rule::unique(
                    'salary_components',
                    'code'
                )
                    ->where(
                        fn ($query) =>
                            $query
                                ->where(
                                    'tenant_id',
                                    $tenantId
                                )
                                ->whereNull(
                                    'deleted_at'
                                )
                    )
                    ->ignore(
                        $componentId
                    ),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'name_en' => [
                'nullable',
                'string',
                'max:255',
            ],

            'type' => [
                'required',

                Rule::in(
                    array_keys(
                        SalaryComponent::TYPES
                    )
                ),
            ],

            'category' => [
                'required',

                Rule::in(
                    array_keys(
                        SalaryComponent::CATEGORIES
                    )
                ),
            ],

            'calculation_method' => [
                'required',

                Rule::in(
                    array_keys(
                        SalaryComponent::
                            CALCULATION_METHODS
                    )
                ),
            ],

            'percentage_base_component_id' => [
                'nullable',

                Rule::requiredIf(
                    fn () =>
                        $this->input(
                            'calculation_method'
                        ) ===
                        SalaryComponent::
                            METHOD_PERCENTAGE
                ),

                'integer',

                Rule::notIn(
                    $componentId
                        ? [$componentId]
                        : []
                ),

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
                            ->where(
                                'is_active',
                                true
                            )
                            ->whereNull(
                                'deleted_at'
                            )
                ),
            ],

            'default_amount' => [
                Rule::requiredIf(
                    fn () =>
                        $this->input(
                            'calculation_method'
                        ) ===
                        SalaryComponent::
                            METHOD_FIXED
                ),

                'nullable',
                'numeric',
                'min:0',
                'max:99999999999999',
            ],

            'default_percentage' => [
                Rule::requiredIf(
                    fn () =>
                        $this->input(
                            'calculation_method'
                        ) ===
                        SalaryComponent::
                            METHOD_PERCENTAGE
                ),

                'nullable',
                'numeric',
                'gt:0',
                'max:100',
            ],

            'default_rate' => [
                Rule::requiredIf(
                    fn () =>
                        $this->input(
                            'calculation_method'
                        ) ===
                        SalaryComponent::
                            METHOD_QUANTITY_RATE
                ),

                'nullable',
                'numeric',
                'min:0',
                'max:99999999999999',
            ],

            'formula' => [
                Rule::requiredIf(
                    fn () =>
                        $this->input(
                            'calculation_method'
                        ) ===
                        SalaryComponent::
                            METHOD_FORMULA
                ),

                'nullable',
                'string',
                'max:2000',
            ],

            'is_taxable' => [
                'sometimes',
                'boolean',
            ],

            'is_subject_to_insurance' => [
                'sometimes',
                'boolean',
            ],

            'is_included_in_overtime_base' => [
                'sometimes',
                'boolean',
            ],

            'is_proratable' => [
                'sometimes',
                'boolean',
            ],

            'is_recurring' => [
                'sometimes',
                'boolean',
            ],

            'requires_input' => [
                'sometimes',
                'boolean',
            ],

            'affects_net_salary' => [
                'sometimes',
                'boolean',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],

            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
                'max:999999',
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
        $validator->after(
            function (
                Validator $validator
            ) {
                $type =
                    $this->input(
                        'type'
                    );

                $category =
                    $this->input(
                        'category'
                    );

                $method =
                    $this->input(
                        'calculation_method'
                    );

                /*
                 * الراتب الأساسي يجب أن يكون
                 * استحقاقًا بمبلغ ثابت.
                 */
                if (
                    $category ===
                    'basic_salary' &&
                    $type !==
                    SalaryComponent::
                        TYPE_EARNING
                ) {
                    $validator->errors()
                        ->add(
                            'type',
                            'الراتب الأساسي يجب أن يكون من نوع استحقاق.'
                        );
                }

                if (
                    $category ===
                    'basic_salary' &&
                    $method !==
                    SalaryComponent::
                        METHOD_FIXED
                ) {
                    $validator->errors()
                        ->add(
                            'calculation_method',
                            'الراتب الأساسي يجب أن يستخدم طريقة المبلغ الثابت.'
                        );
                }

                /*
                 * التأكد أن مكون النسبة استحقاق
                 * ولا يتبع شركة أخرى.
                 */
                if (
                    $method ===
                    SalaryComponent::
                        METHOD_PERCENTAGE &&
                    $this->filled(
                        'percentage_base_component_id'
                    )
                ) {
                    $baseComponent =
                        SalaryComponent::query()
                            ->where(
                                'tenant_id',
                                $this->user()
                                    ->tenant_id
                            )
                            ->find(
                                $this->input(
                                    'percentage_base_component_id'
                                )
                            );

                    if (!$baseComponent) {
                        return;
                    }

                    if (
                        !$baseComponent
                            ->isEarning()
                    ) {
                        $validator->errors()
                            ->add(
                                'percentage_base_component_id',
                                'المكون الأساسي لحساب النسبة يجب أن يكون من نوع استحقاق.'
                            );
                    }
                }
            }
        );
    }


    protected function prepareForValidation(): void
    {
        $data = [
            'code' =>
                strtoupper(
                    trim(
                        (string) $this->input(
                            'code',
                            ''
                        )
                    )
                ),

            'name' =>
                trim(
                    (string) $this->input(
                        'name',
                        ''
                    )
                ),

            'name_en' =>
                $this->filled(
                    'name_en'
                )
                    ? trim(
                        (string) $this->input(
                            'name_en'
                        )
                    )
                    : null,

            'formula' =>
                $this->filled(
                    'formula'
                )
                    ? trim(
                        (string) $this->input(
                            'formula'
                        )
                    )
                    : null,

            'percentage_base_component_id' =>
                $this->filled(
                    'percentage_base_component_id'
                )
                    ? (int) $this->input(
                        'percentage_base_component_id'
                    )
                    : null,

            'sort_order' =>
                $this->filled(
                    'sort_order'
                )
                    ? (int) $this->input(
                        'sort_order'
                    )
                    : 0,
        ];

        foreach (
            $this->booleanFields()
            as $field
        ) {
            if ($this->exists($field)) {
                $data[$field] =
                    $this->boolean(
                        $field
                    );
            }
        }

        $this->merge(
            $data
        );
    }


    public function messages(): array
    {
        return [
            'code.required' =>
                'كود مكون الراتب مطلوب.',

            'code.max' =>
                'كود المكون يجب ألا يتجاوز 50 حرفًا.',

            'code.regex' =>
                'كود المكون يقبل الأحرف الإنجليزية الكبيرة والأرقام والشرطة فقط.',

            'code.unique' =>
                'كود مكون الراتب مستخدم مسبقًا.',

            'name.required' =>
                'اسم مكون الراتب مطلوب.',

            'name.max' =>
                'اسم المكون يجب ألا يتجاوز 255 حرفًا.',

            'name_en.max' =>
                'الاسم الإنجليزي يجب ألا يتجاوز 255 حرفًا.',

            'type.required' =>
                'نوع مكون الراتب مطلوب.',

            'type.in' =>
                'نوع مكون الراتب غير صحيح.',

            'category.required' =>
                'تصنيف مكون الراتب مطلوب.',

            'category.in' =>
                'تصنيف مكون الراتب غير صحيح.',

            'calculation_method.required' =>
                'طريقة حساب المكون مطلوبة.',

            'calculation_method.in' =>
                'طريقة حساب المكون غير صحيحة.',

            'percentage_base_component_id.required' =>
                'يجب تحديد المكون الأساسي لحساب النسبة.',

            'percentage_base_component_id.exists' =>
                'المكون الأساسي المحدد غير موجود أو غير نشط.',

            'percentage_base_component_id.not_in' =>
                'لا يمكن احتساب المكون كنسبة من نفسه.',

            'default_amount.required' =>
                'المبلغ الافتراضي مطلوب.',

            'default_amount.numeric' =>
                'المبلغ الافتراضي يجب أن يكون رقمًا.',

            'default_amount.min' =>
                'المبلغ الافتراضي لا يمكن أن يكون سالبًا.',

            'default_percentage.required' =>
                'النسبة الافتراضية مطلوبة.',

            'default_percentage.numeric' =>
                'النسبة الافتراضية يجب أن تكون رقمًا.',

            'default_percentage.gt' =>
                'النسبة الافتراضية يجب أن تكون أكبر من صفر.',

            'default_percentage.max' =>
                'النسبة الافتراضية لا يمكن أن تتجاوز 100%.',

            'default_rate.required' =>
                'المعدل الافتراضي مطلوب.',

            'default_rate.numeric' =>
                'المعدل الافتراضي يجب أن يكون رقمًا.',

            'formula.required' =>
                'المعادلة الحسابية مطلوبة.',

            'formula.max' =>
                'المعادلة الحسابية طويلة جدًا.',

            'sort_order.integer' =>
                'ترتيب العرض يجب أن يكون رقمًا صحيحًا.',
        ];
    }


    public function attributes(): array
    {
        return [
            'code' =>
                'كود المكون',

            'name' =>
                'اسم المكون',

            'name_en' =>
                'الاسم الإنجليزي',

            'type' =>
                'نوع المكون',

            'category' =>
                'التصنيف',

            'calculation_method' =>
                'طريقة الحساب',

            'percentage_base_component_id' =>
                'المكون الأساسي للنسبة',

            'default_amount' =>
                'المبلغ الافتراضي',

            'default_percentage' =>
                'النسبة الافتراضية',

            'default_rate' =>
                'المعدل الافتراضي',

            'formula' =>
                'المعادلة الحسابية',

            'sort_order' =>
                'ترتيب العرض',
        ];
    }


    protected function componentId(): ?int
    {
        $component =
            $this->route(
                'salaryComponent'
            )
            ?? $this->route(
                'salary_component'
            );

        if (
            $component instanceof
            SalaryComponent
        ) {
            return (int) $component->id;
        }

        if (
            is_numeric(
                $component
            )
        ) {
            return (int) $component;
        }

        return null;
    }


    private function booleanFields(): array
    {
        return [
            'is_taxable',
            'is_subject_to_insurance',
            'is_included_in_overtime_base',
            'is_proratable',
            'is_recurring',
            'requires_input',
            'affects_net_salary',
            'is_active',
        ];
    }
}