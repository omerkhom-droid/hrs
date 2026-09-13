<?php

namespace App\Http\Requests\Tenant;

use App\Models\Employee;
use App\Models\LeaveType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class StoreLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user =
            $this->user();

        if (
            !$user ||
            !$user->tenant_id
        ) {
            return false;
        }

        return
            $user->can('leave.manage') ||
            $user->can('self_service.leave');
    }

    protected function prepareForValidation(): void
    {
        $employeeId =
            $this->input('employee_id');

        /*
         * مستخدم الخدمة الذاتية لا يمكنه
         * إنشاء طلب لموظف آخر.
         */
        if (
            !$this->user()
                ?->can('leave.manage')
        ) {
            $employeeId =
                $this->resolveCurrentEmployeeId();
        }

        $this->merge([
            'employee_id' =>
                $employeeId,

            'reason' =>
                trim(
                    (string) $this->input(
                        'reason'
                    )
                ),

            'handover_notes' =>
                $this->filled(
                    'handover_notes'
                )
                    ? trim(
                        (string) $this->input(
                            'handover_notes'
                        )
                    )
                    : null,

            'contact_during_leave' =>
                $this->filled(
                    'contact_during_leave'
                )
                    ? trim(
                        (string) $this->input(
                            'contact_during_leave'
                        )
                    )
                    : null,

            'start_session' =>
                $this->input(
                    'start_session',
                    'full_day'
                ),

            'end_session' =>
                $this->input(
                    'end_session',
                    'full_day'
                ),
        ]);
    }

    public function rules(): array
    {
        $tenantId =
            (int) $this->user()
                ->tenant_id;

        $maximumAttachmentSize =
            (int) config(
                'hr.leave.attachments.maximum_size',
                5120
            );

        $allowedExtensions =
            config(
                'hr.leave.attachments.allowed_extensions',
                [
                    'pdf',
                    'jpg',
                    'jpeg',
                    'png',
                    'webp',
                ]
            );

        return [
            'employee_id' => [
                'required',
                'integer',
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

            'leave_type_id' => [
                'required',
                'integer',
                Rule::exists(
                    'leave_types',
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

            'replacement_employee_id' => [
                'nullable',
                'integer',
                'different:employee_id',
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

            'start_date' => [
                'required',
                'date_format:Y-m-d',
            ],

            'end_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:start_date',
            ],

            'start_session' => [
                'required',
                Rule::in([
                    'full_day',
                    'first_half',
                    'second_half',
                ]),
            ],

            'end_session' => [
                'required',
                Rule::in([
                    'full_day',
                    'first_half',
                    'second_half',
                ]),
            ],

            /*
             * مطلوب فقط للإجازة بالساعات.
             * سيتم التحقق منه بعد معرفة نوع الإجازة.
             */
            'requested_amount' => [
                'nullable',
                'numeric',
                'min:0.25',
                'max:24',
            ],

            'reason' => [
                'required',
                'string',
                'min:3',
                'max:2000',
            ],

            'handover_notes' => [
                'nullable',
                'string',
                'max:3000',
            ],

            'contact_during_leave' => [
                'nullable',
                'string',
                'max:255',
            ],

            'attachment' => [
                'nullable',
                File::types(
                    $allowedExtensions
                )->max(
                    $maximumAttachmentSize
                ),
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (
                Validator $validator
            ) {
                $this->validateEmployeeAccess(
                    $validator
                );

                $this->validateLeaveTypeRules(
                    $validator
                );
            },
        ];
    }

    private function validateEmployeeAccess(
        Validator $validator
    ): void {
        if (
            $this->user()
                ?->can('leave.manage')
        ) {
            return;
        }

        $currentEmployeeId =
            $this->resolveCurrentEmployeeId();

        if (!$currentEmployeeId) {
            $validator->errors()->add(
                'employee_id',
                'لا يوجد ملف موظف مرتبط بحسابك.'
            );

            return;
        }

        if (
            (int) $this->input(
                'employee_id'
            ) !==
            $currentEmployeeId
        ) {
            $validator->errors()->add(
                'employee_id',
                'لا يمكنك إنشاء طلب إجازة لموظف آخر.'
            );
        }
    }

    private function validateLeaveTypeRules(
        Validator $validator
    ): void {
        if (
            !$this->filled(
                'leave_type_id'
            )
        ) {
            return;
        }

        $leaveType =
            LeaveType::withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $this->user()->tenant_id
                )
                ->whereKey(
                    $this->input(
                        'leave_type_id'
                    )
                )
                ->first();

        if (!$leaveType) {
            return;
        }

        if (
            $leaveType->unit === 'hour' &&
            !$this->filled(
                'requested_amount'
            )
        ) {
            $validator->errors()->add(
                'requested_amount',
                'عدد ساعات الإجازة مطلوب.'
            );
        }

        if (
            $leaveType->unit === 'hour' &&
            $this->input('start_date') !==
                $this->input('end_date')
        ) {
            $validator->errors()->add(
                'end_date',
                'الإجازة بالساعات يجب أن تكون في يوم واحد.'
            );
        }

        if (
            !$leaveType->allow_half_day &&
            (
                $this->input(
                    'start_session'
                ) !== 'full_day' ||
                $this->input(
                    'end_session'
                ) !== 'full_day'
            )
        ) {
            $validator->errors()->add(
                'start_session',
                'نوع الإجازة المحدد لا يسمح بنصف يوم.'
            );
        }

        if (
            $leaveType->requires_attachment &&
            !$this->hasFile('attachment') &&
            !$this->routeHasExistingAttachment()
        ) {
            $validator->errors()->add(
                'attachment',
                'يجب إرفاق مستند لهذا النوع من الإجازات.'
            );
        }
    }

    private function resolveCurrentEmployeeId(): ?int
    {
        $employeeId =
            Employee::withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $this->user()
                        ?->tenant_id
                )
                ->where(
                    'user_id',
                    $this->user()
                        ?->id
                )
                ->whereNull(
                    'deleted_at'
                )
                ->value('id');

        return $employeeId
            ? (int) $employeeId
            : null;
    }

    private function routeHasExistingAttachment(): bool
    {
        $leaveRequest =
            $this->route('leaveRequest')
            ?? $this->route(
                'leave_request'
            );

        if (
            is_object($leaveRequest) &&
            !empty(
                $leaveRequest
                    ->attachment_path
            )
        ) {
            return true;
        }

        return false;
    }

    public function messages(): array
    {
        return [
            'employee_id.required' =>
                'الموظف مطلوب.',

            'employee_id.exists' =>
                'الموظف المحدد غير موجود.',

            'leave_type_id.required' =>
                'نوع الإجازة مطلوب.',

            'leave_type_id.exists' =>
                'نوع الإجازة غير موجود أو غير نشط.',

            'replacement_employee_id.different' =>
                'الموظف البديل يجب أن يختلف عن صاحب الطلب.',

            'replacement_employee_id.exists' =>
                'الموظف البديل غير موجود.',

            'start_date.required' =>
                'تاريخ بداية الإجازة مطلوب.',

            'start_date.date_format' =>
                'صيغة تاريخ البداية غير صحيحة.',

            'end_date.required' =>
                'تاريخ نهاية الإجازة مطلوب.',

            'end_date.date_format' =>
                'صيغة تاريخ النهاية غير صحيحة.',

            'end_date.after_or_equal' =>
                'تاريخ النهاية يجب ألا يسبق تاريخ البداية.',

            'start_session.required' =>
                'فترة بداية الإجازة مطلوبة.',

            'start_session.in' =>
                'فترة بداية الإجازة غير صحيحة.',

            'end_session.required' =>
                'فترة نهاية الإجازة مطلوبة.',

            'end_session.in' =>
                'فترة نهاية الإجازة غير صحيحة.',

            'requested_amount.numeric' =>
                'عدد الساعات يجب أن يكون رقمًا.',

            'requested_amount.min' =>
                'أقل مدة للإجازة هي ربع ساعة.',

            'requested_amount.max' =>
                'عدد ساعات الإجازة لا يمكن أن يتجاوز 24 ساعة.',

            'reason.required' =>
                'سبب الإجازة مطلوب.',

            'reason.min' =>
                'سبب الإجازة يجب ألا يقل عن 3 أحرف.',

            'reason.max' =>
                'سبب الإجازة طويل جدًا.',

            'attachment.max' =>
                'حجم المرفق يتجاوز الحد المسموح.',

            'attachment.mimes' =>
                'نوع المرفق غير مسموح.',
        ];
    }

    public function attributes(): array
    {
        return [
            'employee_id' =>
                'الموظف',

            'leave_type_id' =>
                'نوع الإجازة',

            'replacement_employee_id' =>
                'الموظف البديل',

            'start_date' =>
                'تاريخ البداية',

            'end_date' =>
                'تاريخ النهاية',

            'start_session' =>
                'فترة البداية',

            'end_session' =>
                'فترة النهاية',

            'requested_amount' =>
                'المدة المطلوبة',

            'reason' =>
                'سبب الإجازة',

            'handover_notes' =>
                'ملاحظات التسليم',

            'contact_during_leave' =>
                'بيانات التواصل أثناء الإجازة',

            'attachment' =>
                'المرفق',
        ];
    }
}