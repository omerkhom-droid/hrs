<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('work_locations.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $tenantTimezone = $this->user()?->tenant?->timezone
            ?: config('app.timezone', 'Asia/Riyadh');

        $this->merge([
            'branch_id' => $this->filled('branch_id')
                ? (int) $this->input('branch_id')
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

            'type' => strtolower(
                trim((string) $this->input('type', 'office'))
            ),

            'country_code' => strtoupper(
                trim((string) $this->input('country_code', 'SA'))
            ),

            'city' => $this->filled('city')
                ? trim((string) $this->input('city'))
                : null,

            'address' => $this->filled('address')
                ? trim((string) $this->input('address'))
                : null,

            'latitude' => $this->filled('latitude')
                ? $this->input('latitude')
                : null,

            'longitude' => $this->filled('longitude')
                ? $this->input('longitude')
                : null,

            'attendance_radius' => $this->filled('attendance_radius')
                ? (int) $this->input('attendance_radius')
                : 100,

            'timezone' => $this->filled('timezone')
                ? trim((string) $this->input('timezone'))
                : $tenantTimezone,

            'is_active' => $this->has('is_active')
                ? $this->boolean('is_active')
                : true,
        ]);
    }

    public function rules(): array
    {
        $tenantId = (int) $this->user()->tenant_id;

        return [
            'branch_id' => [
                'nullable',
                'integer',
                Rule::exists('branches', 'id')
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
                Rule::unique('work_locations', 'code')
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

            'type' => [
                'required',
                Rule::in([
                    'office',
                    'site',
                    'warehouse',
                    'remote',
                    'other',
                ]),
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
                'max:5000',
            ],

            'latitude' => [
                'nullable',
                'required_with:longitude',
                'numeric',
                'between:-90,90',
            ],

            'longitude' => [
                'nullable',
                'required_with:latitude',
                'numeric',
                'between:-180,180',
            ],

            'attendance_radius' => [
                'required',
                'integer',
                'min:0',
                'max:100000',
            ],

            'timezone' => [
                'required',
                'string',
                'timezone',
                'max:100',
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
            'branch_id.exists' => 'الفرع المختار غير موجود أو لا يتبع للشركة الحالية.',

            'code.required' => 'كود موقع العمل مطلوب.',
            'code.regex' => 'الكود يقبل الحروف الإنجليزية والأرقام والشرطة والنقطة فقط.',
            'code.max' => 'يجب ألا يتجاوز الكود 50 حرفًا.',
            'code.unique' => 'كود موقع العمل مستخدم مسبقًا داخل الشركة.',

            'name.required' => 'اسم موقع العمل بالعربية مطلوب.',
            'name.max' => 'يجب ألا يتجاوز الاسم العربي 255 حرفًا.',
            'name_en.max' => 'يجب ألا يتجاوز الاسم الإنجليزي 255 حرفًا.',

            'type.required' => 'نوع موقع العمل مطلوب.',
            'type.in' => 'نوع موقع العمل المختار غير صحيح.',

            'country_code.required' => 'رمز الدولة مطلوب.',
            'country_code.size' => 'رمز الدولة يجب أن يتكون من حرفين.',
            'country_code.regex' => 'رمز الدولة يجب أن يتكون من حرفين إنجليزيين.',

            'city.max' => 'يجب ألا يتجاوز اسم المدينة 255 حرفًا.',
            'address.max' => 'يجب ألا يتجاوز العنوان 5000 حرف.',

            'latitude.required_with' => 'خط العرض مطلوب عند إدخال خط الطول.',
            'latitude.numeric' => 'خط العرض يجب أن يكون رقمًا.',
            'latitude.between' => 'خط العرض يجب أن يكون بين -90 و90.',

            'longitude.required_with' => 'خط الطول مطلوب عند إدخال خط العرض.',
            'longitude.numeric' => 'خط الطول يجب أن يكون رقمًا.',
            'longitude.between' => 'خط الطول يجب أن يكون بين -180 و180.',

            'attendance_radius.required' => 'نطاق تسجيل الحضور مطلوب.',
            'attendance_radius.integer' => 'نطاق تسجيل الحضور يجب أن يكون رقمًا صحيحًا.',
            'attendance_radius.min' => 'نطاق تسجيل الحضور لا يمكن أن يكون سالبًا.',
            'attendance_radius.max' => 'نطاق تسجيل الحضور لا يمكن أن يتجاوز 100 كيلومتر.',

            'timezone.required' => 'المنطقة الزمنية مطلوبة.',
            'timezone.timezone' => 'المنطقة الزمنية المختارة غير صحيحة.',

            'is_active.boolean' => 'حالة موقع العمل غير صحيحة.',
        ];
    }
}
