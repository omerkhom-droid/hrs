<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOvertimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (!$user) {
            return false;
        }

        if (
            $this->filled('employee_id')
            || $this->filled('employee_ids')
        ) {
            return (bool) $user->can('attendance.manage');
        }

        return (bool) (
            $user->can('self_service.attendance')
            || $user->can('attendance.manage')
        );
    }

    protected function prepareForValidation(): void
    {
        $employeeIds = collect($this->input('employee_ids', []))
            ->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $this->merge([
            'reason' => trim((string) $this->input('reason')),
            'employee_ids' => $employeeIds,
        ]);
    }

    public function rules(): array
    {
        $tenantId = $this->user()?->tenant_id;

        $employeeRule = Rule::exists('employees', 'id')->where(
            fn ($query) => $query
                ->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')
        );

        $employeeIdsRules = ['nullable', 'array', 'max:500'];

        if ($this->user()?->can('attendance.manage')) {
            $employeeIdsRules[] = 'required_without:employee_id';
            $employeeIdsRules[] = 'min:1';
        } else {
            $employeeIdsRules[] = 'prohibited';
        }

        return [
            'employee_id' => [
                'nullable',
                'integer',
                $employeeRule,
            ],
            'employee_ids' => $employeeIdsRules,
            'employee_ids.*' => [
                'integer',
                'distinct',
                $employeeRule,
            ],
            'overtime_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'type' => [
                'required',
                Rule::in([
                    'regular_day',
                    'rest_day',
                    'holiday',
                    'emergency',
                ]),
            ],
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'employee_id' => 'الموظف',
            'employee_ids' => 'الموظفون',
            'employee_ids.*' => 'الموظف المحدد',
            'overtime_date' => 'تاريخ العمل الإضافي',
            'start_time' => 'وقت البداية',
            'end_time' => 'وقت النهاية',
            'type' => 'نوع العمل الإضافي',
            'reason' => 'سبب العمل الإضافي',
        ];
    }
}
