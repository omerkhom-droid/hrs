<?php

namespace App\Http\Requests\Tenant;

use App\Models\LeaveType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user =
            $this->user();

        if (!$user) {
            return false;
        }

        if ($user->is_system_admin) {
            return true;
        }

        return
            $user->tenant_id !== null &&
            $user->can('leave.manage');
    }

    protected function prepareForValidation(): void
    {
        $paymentType =
            $this->input(
                'payment_type',
                'paid'
            );

        $unit =
            $this->input(
                'unit',
                'day'
            );

        $allowCarryForward =
            $this->boolean(
                'allow_carry_forward'
            );

        $requiresBalance =
            $this->boolean(
                'requires_balance'
            );

        $paidPercentage =
            $this->input(
                'paid_percentage'
            );

        if ($paymentType === 'paid') {
            $paidPercentage = 100;
        }

        if ($paymentType === 'unpaid') {
            $paidPercentage = 0;
        }

        $this->merge([
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

            'unit' =>
                $unit,

            'payment_type' =>
                $paymentType,

            'paid_percentage' =>
                $paidPercentage,

            'requires_balance' =>
                $requiresBalance,

            'allow_negative_balance' =>
                $requiresBalance &&
                $this->boolean(
                    'allow_negative_balance'
                ),

            'allow_during_probation' =>
                $this->boolean(
                    'allow_during_probation'
                ),

            'allow_half_day' =>
                $unit === 'day' &&
                $this->boolean(
                    'allow_half_day'
                ),

            'requires_attachment' =>
                $this->boolean(
                    'requires_attachment'
                ),

            'allow_carry_forward' =>
                $requiresBalance &&
                $allowCarryForward,

            'maximum_carry_forward' =>
                $requiresBalance &&
                $allowCarryForward
                    ? $this->input(
                        'maximum_carry_forward',
                        0
                    )
                    : 0,

            'is_active' =>
                $this->boolean(
                    'is_active'
                ),

            'sort_order' =>
                $this->input(
                    'sort_order',
                    0
                ),

            'gender' =>
                $this->input(
                    'gender',
                    'all'
                ),
        ]);
    }

    public function rules(): array
    {
        $tenantId =
            (int) $this->user()
                ->tenant_id;

        $leaveTypeId =
            $this->resolveLeaveTypeId();

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique(
                    'leave_types',
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
                        $leaveTypeId
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

            'unit' => [
                'required',
                Rule::in([
                    'day',
                    'hour',
                ]),
            ],

            'payment_type' => [
                'required',
                Rule::in([
                    'paid',
                    'unpaid',
                    'partially_paid',
                ]),
            ],

            'paid_percentage' => [
                Rule::requiredIf(
                    $this->input(
                        'payment_type'
                    ) === 'partially_paid'
                ),
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],

            'default_entitlement' => [
                'required',
                'numeric',
                'min:0',
                'max:9999.99',
            ],

            'accrual_method' => [
                'required',
                Rule::in([
                    'none',
                    'annual',
                    'monthly',
                ]),
            ],

            'requires_balance' => [
                'required',
                'boolean',
            ],

            'allow_negative_balance' => [
                'required',
                'boolean',
            ],

            'allow_during_probation' => [
                'required',
                'boolean',
            ],

            'allow_half_day' => [
                'required',
                'boolean',
            ],

            'requires_attachment' => [
                'required',
                'boolean',
            ],

            'minimum_notice_days' => [
                'required',
                'integer',
                'min:0',
                'max:365',
            ],

            'maximum_consecutive_days' => [
                'nullable',
                'integer',
                'min:1',
                'max:3650',
            ],

            'allow_carry_forward' => [
                'required',
                'boolean',
            ],

            'maximum_carry_forward' => [
                'required',
                'numeric',
                'min:0',
                'max:9999.99',
            ],

            'gender' => [
                'required',
                Rule::in([
                    'all',
                    'male',
                    'female',
                ]),
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

    public function after(): array
    {
        return [
            function (
                Validator $validator
            ) {
                if (
                    $this->input(
                        'payment_type'
                    ) ===
                        'partially_paid' &&
                    (
                        (float) $this->input(
                            'paid_percentage'
                        ) <= 0 ||
                        (float) $this->input(
                            'paid_percentage'
                        ) >= 100
                    )
                ) {
                    $validator->errors()->add(
                        'paid_percentage',
                        'نسبة الأجر الجزئي يجب أن تكون أكبر من صفر وأقل من 100.'
                    );
                }

                if (
                    $this->boolean(
                        'allow_carry_forward'
                    ) &&
                    (float) $this->input(
                        'maximum_carry_forward'
                    ) <= 0
                ) {
                    $validator->errors()->add(
                        'maximum_carry_forward',
                        'يجب تحديد الحد الأعلى للرصيد المرحّل.'
                    );
                }

                if (
                    !$this->boolean(
                        'requires_balance'
                    ) &&
                    $this->input(
                        'accrual_method'
                    ) !== 'none'
                ) {
                    $validator->errors()->add(
                        'accrual_method',
                        'نوع الإجازة الذي لا يحتاج رصيدًا يجب أن تكون طريقة استحقاقه بدون تراكم.'
                    );
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' =>
                'كود نوع الإجازة مطلوب.',

            'code.unique' =>
                'كود نوع الإجازة مستخدم مسبقًا.',

            'code.regex' =>
                'الكود يقبل الأحرف الإنجليزية الكبيرة والأرقام والشرطة فقط.',

            'name.required' =>
                'اسم نوع الإجازة مطلوب.',

            'unit.required' =>
                'وحدة احتساب الإجازة مطلوبة.',

            'unit.in' =>
                'وحدة احتساب الإجازة غير صحيحة.',

            'payment_type.required' =>
                'نوع دفع الإجازة مطلوب.',

            'payment_type.in' =>
                'نوع دفع الإجازة غير صحيح.',

            'paid_percentage.required' =>
                'نسبة الأجر الجزئي مطلوبة.',

            'paid_percentage.min' =>
                'نسبة الأجر لا يمكن أن تكون أقل من صفر.',

            'paid_percentage.max' =>
                'نسبة الأجر لا يمكن أن تتجاوز 100%.',

            'default_entitlement.required' =>
                'الاستحقاق الافتراضي مطلوب.',

            'default_entitlement.min' =>
                'الاستحقاق الافتراضي لا يمكن أن يكون سالبًا.',

            'accrual_method.required' =>
                'طريقة الاستحقاق مطلوبة.',

            'minimum_notice_days.required' =>
                'مدة الإشعار المسبق مطلوبة.',

            'maximum_consecutive_days.min' =>
                'الحد الأعلى للإجازة المتصلة يجب أن يكون يومًا واحدًا على الأقل.',

            'gender.required' =>
                'الفئة المستفيدة مطلوبة.',

            'is_active.required' =>
                'حالة نوع الإجازة مطلوبة.',
        ];
    }

    public function attributes(): array
    {
        return [
            'code' =>
                'الكود',

            'name' =>
                'الاسم العربي',

            'name_en' =>
                'الاسم الإنجليزي',

            'unit' =>
                'وحدة الاحتساب',

            'payment_type' =>
                'نوع الدفع',

            'paid_percentage' =>
                'نسبة الأجر',

            'default_entitlement' =>
                'الاستحقاق الافتراضي',

            'accrual_method' =>
                'طريقة الاستحقاق',

            'minimum_notice_days' =>
                'مدة الإشعار المسبق',

            'maximum_consecutive_days' =>
                'الحد الأعلى للإجازة المتصلة',

            'maximum_carry_forward' =>
                'الحد الأعلى للترحيل',

            'gender' =>
                'الفئة المستفيدة',

            'sort_order' =>
                'ترتيب العرض',

            'is_active' =>
                'الحالة',
        ];
    }

    private function resolveLeaveTypeId(): ?int
    {
        $leaveType =
            $this->route('leaveType')
            ?? $this->route('leave_type');

        if ($leaveType instanceof LeaveType) {
            return (int) $leaveType->id;
        }

        if (is_numeric($leaveType)) {
            return (int) $leaveType;
        }

        return null;
    }
}