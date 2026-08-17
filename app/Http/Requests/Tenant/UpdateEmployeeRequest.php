<?php

namespace App\Http\Requests\Tenant;

use App\Models\Department;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\WorkLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can(
            'employees.update'
        );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'employee_number' =>
                $this->cleanUpper('employee_number'),

            'attendance_code' =>
                $this->cleanUpper('attendance_code'),

            'first_name' =>
                $this->clean('first_name'),

            'father_name' =>
                $this->clean('father_name'),

            'grandfather_name' =>
                $this->clean('grandfather_name'),

            'family_name' =>
                $this->clean('family_name'),

            'name_en' =>
                $this->clean('name_en'),

            'identity_number' =>
                $this->cleanUpper('identity_number'),

            'nationality_code' =>
                $this->cleanUpper('nationality_code'),

            'personal_email' =>
                $this->cleanLower('personal_email'),

            'work_email' =>
                $this->cleanLower('work_email'),

            'country_code' =>
                $this->cleanUpper('country_code') ?: 'SA',
        ]);
    }

    public function rules(): array
    {
        $tenantId = (int) $this->user()->tenant_id;
        $employee = $this->employee();
        $employeeId = $employee?->id;

        return [
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at'),
                Rule::unique('employees', 'user_id')
                    ->where('tenant_id', $tenantId)
                    ->ignore($employeeId),
            ],

            'employee_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('employees', 'employee_number')
                    ->where('tenant_id', $tenantId)
                    ->ignore($employeeId),
            ],

            'attendance_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('employees', 'attendance_code')
                    ->where('tenant_id', $tenantId)
                    ->ignore($employeeId),
            ],

            'branch_id' => [
                'nullable',
                'integer',
                Rule::exists('branches', 'id')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at'),
            ],

            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at'),
            ],

            'job_title_id' => [
                'nullable',
                'integer',
                Rule::exists('job_titles', 'id')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at'),
            ],

            'work_location_id' => [
                'nullable',
                'integer',
                Rule::exists('work_locations', 'id')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at'),
            ],

            'manager_id' => [
                'nullable',
                'integer',
                Rule::exists('employees', 'id')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at'),
                Rule::notIn(array_filter([$employeeId])),
            ],

            'first_name' => [
                'required',
                'string',
                'max:255',
            ],

            'father_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'grandfather_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'family_name' => [
                'required',
                'string',
                'max:255',
            ],

            'name_en' => [
                'nullable',
                'string',
                'max:255',
            ],

            'identity_type' => [
                'nullable',
                'required_with:identity_number',
                Rule::in([
                    'national_id',
                    'iqama',
                    'passport',
                    'gcc',
                    'other',
                ]),
            ],

            'identity_number' => [
                'nullable',
                'required_with:identity_type',
                'string',
                'max:100',
                Rule::unique('employees', 'identity_number')
                    ->where('tenant_id', $tenantId)
                    ->ignore($employeeId),
            ],

            'identity_expiry_date' => [
                'nullable',
                'date',
            ],

            'nationality_code' => [
                'nullable',
                'string',
                'size:2',
            ],

            'gender' => [
                'nullable',
                Rule::in([
                    'male',
                    'female',
                ]),
            ],

            'birth_date' => [
                'nullable',
                'date',
                'before:today',
            ],

            'marital_status' => [
                'nullable',
                Rule::in([
                    'single',
                    'married',
                    'divorced',
                    'widowed',
                ]),
            ],

            'personal_email' => [
                'nullable',
                'email:rfc',
                'max:255',
            ],

            'work_email' => [
                'nullable',
                'email:rfc',
                'max:255',
                Rule::unique('employees', 'work_email')
                    ->where('tenant_id', $tenantId)
                    ->ignore($employeeId),
            ],

            'personal_phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'work_phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'country_code' => [
                'required',
                'string',
                'size:2',
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

            'emergency_contact_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'emergency_contact_relation' => [
                'nullable',
                'string',
                'max:100',
            ],

            'emergency_contact_phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'employment_type' => [
                'required',
                Rule::in([
                    'full_time',
                    'part_time',
                    'contract',
                    'temporary',
                    'intern',
                    'consultant',
                ]),
            ],

            'employment_status' => [
                'required',
                Rule::in([
                    'draft',
                    'probation',
                    'active',
                    'on_leave',
                    'suspended',
                    'terminated',
                ]),
            ],

            'hire_date' => [
                'required',
                'date',
            ],

            'probation_end_date' => [
                'nullable',
                'date',
                'after_or_equal:hire_date',
            ],

            'confirmation_date' => [
                'nullable',
                'date',
                'after_or_equal:hire_date',
            ],

            'termination_date' => [
                Rule::requiredIf(
                    $this->input('employment_status') === 'terminated'
                ),
                'nullable',
                'date',
                'after_or_equal:hire_date',
            ],

            'termination_reason' => [
                Rule::requiredIf(
                    $this->input('employment_status') === 'terminated'
                ),
                'nullable',
                'string',
                'max:2000',
            ],

            'timezone' => [
                'nullable',
                'timezone',
            ],

            'photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'remove_photo' => [
                'nullable',
                'boolean',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $this->validateOrganizationRelations(
                    $validator
                );
            },
        ];
    }

    public function messages(): array
    {
        return [
            'employee_number.required' =>
                'الرقم الوظيفي مطلوب.',

            'employee_number.unique' =>
                'الرقم الوظيفي مستخدم مسبقًا.',

            'attendance_code.unique' =>
                'كود الحضور مستخدم مسبقًا.',

            'first_name.required' =>
                'الاسم الأول مطلوب.',

            'family_name.required' =>
                'اسم العائلة مطلوب.',

            'identity_number.required_with' =>
                'رقم الهوية مطلوب عند تحديد نوع الهوية.',

            'identity_number.unique' =>
                'رقم الهوية مستخدم لموظف آخر.',

            'work_email.unique' =>
                'البريد الوظيفي مستخدم لموظف آخر.',

            'manager_id.not_in' =>
                'لا يمكن تعيين الموظف مديرًا مباشرًا لنفسه.',

            'hire_date.required' =>
                'تاريخ التعيين مطلوب.',

            'termination_date.required' =>
                'تاريخ انتهاء الخدمة مطلوب.',

            'termination_reason.required' =>
                'سبب انتهاء الخدمة مطلوب.',

            'photo.max' =>
                'حجم الصورة يجب ألا يتجاوز 5 ميجابايت.',
        ];
    }

    private function employee(): ?Employee
    {
        $employee = $this->route('employee');

        if ($employee instanceof Employee) {
            return $employee;
        }

        return Employee::query()->find($employee);
    }

    private function validateOrganizationRelations(
        Validator $validator
    ): void {
        $tenantId = (int) $this->user()->tenant_id;
        $branchId = $this->integer('branch_id') ?: null;
        $departmentId = $this->integer('department_id') ?: null;

        if ($branchId && $departmentId) {
            $department = Department::query()
                ->whereKey($departmentId)
                ->where('tenant_id', $tenantId)
                ->first();

            if (
                $department?->branch_id &&
                (int) $department->branch_id !== $branchId
            ) {
                $validator->errors()->add(
                    'department_id',
                    'الإدارة المحددة لا تتبع الفرع المختار.'
                );
            }
        }

        if ($departmentId && $this->filled('job_title_id')) {
            $jobTitle = JobTitle::query()
                ->whereKey($this->integer('job_title_id'))
                ->where('tenant_id', $tenantId)
                ->first();

            if (
                $jobTitle?->department_id &&
                (int) $jobTitle->department_id !== $departmentId
            ) {
                $validator->errors()->add(
                    'job_title_id',
                    'المسمى الوظيفي لا يتبع الإدارة المختارة.'
                );
            }
        }

        if ($branchId && $this->filled('work_location_id')) {
            $location = WorkLocation::query()
                ->whereKey($this->integer('work_location_id'))
                ->where('tenant_id', $tenantId)
                ->first();

            if (
                $location?->branch_id &&
                (int) $location->branch_id !== $branchId
            ) {
                $validator->errors()->add(
                    'work_location_id',
                    'موقع العمل لا يتبع الفرع المختار.'
                );
            }
        }
    }

    private function clean(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));

        return $value !== '' ? $value : null;
    }

    private function cleanUpper(string $key): ?string
    {
        $value = $this->clean($key);

        return $value !== null
            ? mb_strtoupper($value)
            : null;
    }

    private function cleanLower(string $key): ?string
    {
        $value = $this->clean($key);

        return $value !== null
            ? mb_strtolower($value)
            : null;
    }
}