<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return
            $user !== null &&
            (
                $user->can(
                    'attendance.manage'
                ) ||
                $user->can(
                    'leave.manage'
                )
            );
    }


    public function rules(): array
    {
        $tenantId = (int) $this
            ->user()
            ->tenant_id;

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

            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9_-]+$/',

                Rule::unique(
                    'holidays',
                    'code'
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
                    'public',
                    'company',
                    'national',
                    'religious',
                    'other',
                ]),
            ],

            'start_date' => [
                'required',
                'date_format:Y-m-d',
            ],

            'end_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:start_date',
            ],

            'is_paid' => [
                'sometimes',
                'boolean',
            ],

            'exclude_from_leave_days' => [
                'sometimes',
                'boolean',
            ],

            'affects_attendance' => [
                'sometimes',
                'boolean',
            ],

            'is_recurring' => [
                'sometimes',
                'boolean',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],

            'metadata' => [
                'nullable',
                'array',
            ],
        ];
    }


    protected function prepareForValidation(): void
    {
        $data = [
            'code' =>
                strtoupper(
                    trim(
                        (string) $this->input(
                            'code'
                        )
                    )
                ),

            'name' =>
                trim(
                    (string) $this->input(
                        'name'
                    )
                ),

            'name_en' =>
                $this->filled('name_en')
                    ? trim(
                        (string) $this->input(
                            'name_en'
                        )
                    )
                    : null,

            'branch_id' =>
                $this->filled('branch_id')
                    ? $this->input(
                        'branch_id'
                    )
                    : null,
        ];


        $booleanFields = [
            'is_paid',
            'exclude_from_leave_days',
            'affects_attendance',
            'is_recurring',
            'is_active',
        ];


        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
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


    public function attributes(): array
    {
        return [
            'branch_id' =>
                'الفرع',

            'code' =>
                'كود العطلة',

            'name' =>
                'اسم العطلة',

            'name_en' =>
                'الاسم الإنجليزي',

            'type' =>
                'نوع العطلة',

            'start_date' =>
                'تاريخ البداية',

            'end_date' =>
                'تاريخ النهاية',

            'is_paid' =>
                'عطلة مدفوعة',

            'exclude_from_leave_days' =>
                'استبعادها من أيام الإجازة',

            'affects_attendance' =>
                'تطبيقها على الحضور',

            'is_recurring' =>
                'التكرار السنوي',

            'is_active' =>
                'الحالة',
        ];
    }


    public function messages(): array
    {
        return [
            'branch_id.exists' =>
                'الفرع المحدد غير موجود أو لا يتبع الشركة.',

            'code.regex' =>
                'كود العطلة يقبل الأحرف الإنجليزية والأرقام والشرطة فقط.',

            'code.unique' =>
                'كود العطلة مستخدم مسبقًا داخل الشركة.',

            'type.in' =>
                'نوع العطلة المحدد غير صحيح.',

            'start_date.date_format' =>
                'تاريخ بداية العطلة غير صحيح.',

            'end_date.date_format' =>
                'تاريخ نهاية العطلة غير صحيح.',

            'end_date.after_or_equal' =>
                'تاريخ النهاية يجب أن يساوي أو يلي تاريخ البداية.',
        ];
    }
}