<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJobTitleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('job_titles.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'department_id' => $this->filled('department_id')
                ? (int) $this->input('department_id')
                : null,

            'code' => strtoupper(
                trim((string) $this->input('code'))
            ),

            'name' => trim(
                (string) $this->input('name')
            ),

            'name_en' => $this->filled('name_en')
                ? trim((string) $this->input('name_en'))
                : null,

            'description' => $this->filled('description')
                ? trim((string) $this->input('description'))
                : null,

            'sort_order' => $this->filled('sort_order')
                ? (int) $this->input('sort_order')
                : 0,

            'is_active' => $this->has('is_active')
                ? $this->boolean('is_active')
                : true,
        ]);
    }

    public function rules(): array
    {
        $tenantId = (int) $this->user()->tenant_id;

        return [
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')
                    ->where(
                        fn ($query) => $query
                            ->where('tenant_id', $tenantId)
                            ->whereNull('deleted_at')
                    ),
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9._-]+$/',
                Rule::unique('job_titles', 'code')
                    ->where(
                        fn ($query) => $query
                            ->where('tenant_id', $tenantId)
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
                'max:5000',
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

    public function messages(): array
    {
        return [
            'department_id.integer' => 'الإدارة المختارة غير صحيحة.',
            'department_id.exists' => 'الإدارة المختارة غير موجودة أو لا تتبع للشركة الحالية.',

            'code.required' => 'كود المسمى الوظيفي مطلوب.',
            'code.max' => 'يجب ألا يتجاوز الكود 50 حرفًا.',
            'code.regex' => 'الكود يقبل الحروف الإنجليزية والأرقام والشرطة والنقطة فقط.',
            'code.unique' => 'كود المسمى الوظيفي مستخدم مسبقًا داخل الشركة.',

            'name.required' => 'اسم المسمى الوظيفي بالعربية مطلوب.',
            'name.max' => 'يجب ألا يتجاوز الاسم العربي 255 حرفًا.',

            'name_en.max' => 'يجب ألا يتجاوز الاسم الإنجليزي 255 حرفًا.',
            'description.max' => 'يجب ألا يتجاوز الوصف 5000 حرف.',

            'sort_order.required' => 'ترتيب العرض مطلوب.',
            'sort_order.integer' => 'ترتيب العرض يجب أن يكون رقمًا صحيحًا.',
            'sort_order.min' => 'ترتيب العرض لا يمكن أن يكون سالبًا.',
            'sort_order.max' => 'قيمة ترتيب العرض كبيرة جدًا.',

            'is_active.required' => 'حالة المسمى الوظيفي مطلوبة.',
            'is_active.boolean' => 'حالة المسمى الوظيفي غير صحيحة.',
        ];
    }
}
