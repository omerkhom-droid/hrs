<?php

namespace App\Http\Requests\Tenant;

use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateBranchRequest extends FormRequest
{
    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

    public function authorize(): bool
    {
        $user =
            $this->user();

        $branch =
            $this->route('branch');

        if (
            !$user ||
            !$branch instanceof Branch
        ) {
            return false;
        }

        return
            $user->can(
                'branches.update'
            ) &&
            (int) $branch->tenant_id ===
            (int) $user->tenant_id;
    }


    /*
    |--------------------------------------------------------------------------
    | Prepare Data
    |--------------------------------------------------------------------------
    */

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

            'email' =>
                $this->filled('email')
                    ? strtolower(
                        trim(
                            (string) $this->input(
                                'email'
                            )
                        )
                    )
                    : null,

            'country_code' =>
                strtoupper(
                    trim(
                        (string) $this->input(
                            'country_code',
                            'SA'
                        )
                    )
                ),

            'timezone' =>
                $this->input(
                    'timezone',
                    'Asia/Riyadh'
                ),

            'is_main' =>
                $this->boolean(
                    'is_main'
                ),

            'is_active' =>
                $this->boolean(
                    'is_active'
                ),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    */

    public function rules(): array
    {
        $branch =
            $this->route('branch');

        $tenantId =
            $this->user()->tenant_id;

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9_-]+$/',

                Rule::unique(
                    'branches',
                    'code'
                )
                    ->where(
                        fn ($query) =>
                            $query->where(
                                'tenant_id',
                                $tenantId
                            )
                    )
                    ->ignore(
                        $branch->id
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

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'country_code' => [
                'required',
                'string',
                'size:2',
                'regex:/^[A-Z]{2}$/',
            ],

            'city' => [
                'nullable',
                'string',
                'max:255',
            ],

            'address' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'timezone' => [
                'required',
                'timezone',
            ],

            'is_main' => [
                'required',
                'boolean',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Additional Validation
    |--------------------------------------------------------------------------
    */

    public function after(): array
    {
        return [
            function (Validator $validator) {

                if (
                    $this->boolean('is_main') &&
                    !$this->boolean('is_active')
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'is_active',
                            'لا يمكن إيقاف الفرع الرئيسي.'
                        );
                }
            },
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Validation Messages
    |--------------------------------------------------------------------------
    */

    public function messages(): array
    {
        return [
            'code.required' =>
                'كود الفرع مطلوب.',

            'code.regex' =>
                'كود الفرع يقبل الأحرف الإنجليزية الكبيرة والأرقام والشرطة فقط.',

            'code.unique' =>
                'كود الفرع مستخدم مسبقًا داخل الشركة.',

            'name.required' =>
                'اسم الفرع مطلوب.',

            'email.email' =>
                'البريد الإلكتروني غير صحيح.',

            'country_code.size' =>
                'رمز الدولة يجب أن يتكون من حرفين.',

            'timezone.timezone' =>
                'المنطقة الزمنية غير صحيحة.',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Arabic Attributes
    |--------------------------------------------------------------------------
    */

    public function attributes(): array
    {
        return [
            'code' =>
                'كود الفرع',

            'name' =>
                'اسم الفرع',

            'name_en' =>
                'اسم الفرع بالإنجليزية',

            'email' =>
                'البريد الإلكتروني',

            'phone' =>
                'رقم التواصل',

            'country_code' =>
                'الدولة',

            'city' =>
                'المدينة',

            'address' =>
                'العنوان',

            'timezone' =>
                'المنطقة الزمنية',

            'is_main' =>
                'الفرع الرئيسي',

            'is_active' =>
                'الحالة',
        ];
    }
}