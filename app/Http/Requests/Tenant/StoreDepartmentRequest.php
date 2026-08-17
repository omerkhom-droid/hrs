<?php

namespace App\Http\Requests\Tenant;

use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return
            $this->user() !== null &&
            $this->user()->can(
                'departments.create'
            );
    }


    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' =>
                strtoupper(
                    trim(
                        (string) $this->input(
                            'code'
                        )
                    )
                ),

            'branch_id' =>
                $this->filled('branch_id')
                    ? (int) $this->input('branch_id')
                    : null,

            'parent_id' =>
                $this->filled('parent_id')
                    ? (int) $this->input('parent_id')
                    : null,

            'sort_order' =>
                (int) $this->input(
                    'sort_order',
                    0
                ),

            'is_active' =>
                $this->boolean(
                    'is_active'
                ),
        ]);
    }


    public function rules(): array
    {
        $tenantId =
            $this->user()->tenant_id;

        return [
            'branch_id' => [
                'nullable',
                'integer',

                Rule::exists(
                    'branches',
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

            'parent_id' => [
                'nullable',
                'integer',

                Rule::exists(
                    'departments',
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

            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9_-]+$/',

                Rule::unique(
                    'departments',
                    'code'
                )->where(
                    fn ($query) =>
                        $query->where(
                            'tenant_id',
                            $tenantId
                        )
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

            'description' => [
                'nullable',
                'string',
                'max:3000',
            ],

            'sort_order' => [
                'required',
                'integer',
                'min:0',
                'max:999999',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Parent Department Validation
    |--------------------------------------------------------------------------
    */

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (!$this->filled('parent_id')) {
                    return;
                }

                $parent = Department::query()
                    ->find(
                        $this->input('parent_id')
                    );

                if (!$parent) {
                    return;
                }

                $parentBranchId =
                    $parent->branch_id
                        ? (int) $parent->branch_id
                        : null;

                $branchId =
                    $this->filled('branch_id')
                        ? (int) $this->input('branch_id')
                        : null;

                if ($parentBranchId !== $branchId) {
                    $validator
                        ->errors()
                        ->add(
                            'parent_id',
                            'يجب أن يتبع القسم الرئيسي والفرعي لنفس الفرع.'
                        );
                }
            },
        ];
    }


    public function messages(): array
    {
        return [
            'branch_id.exists' =>
                'الفرع المحدد غير موجود.',

            'parent_id.exists' =>
                'الإدارة الرئيسية المحددة غير موجودة.',

            'code.required' =>
                'كود الإدارة مطلوب.',

            'code.regex' =>
                'كود الإدارة يقبل الأحرف الإنجليزية الكبيرة والأرقام والشرطة فقط.',

            'code.unique' =>
                'كود الإدارة مستخدم مسبقًا داخل الشركة.',

            'name.required' =>
                'اسم الإدارة مطلوب.',

            'sort_order.min' =>
                'ترتيب الإدارة لا يمكن أن يكون سالبًا.',
        ];
    }


    public function attributes(): array
    {
        return [
            'branch_id' =>
                'الفرع',

            'parent_id' =>
                'الإدارة الرئيسية',

            'code' =>
                'كود الإدارة',

            'name' =>
                'اسم الإدارة',

            'name_en' =>
                'اسم الإدارة بالإنجليزية',

            'description' =>
                'الوصف',

            'sort_order' =>
                'الترتيب',

            'is_active' =>
                'الحالة',
        ];
    }
}