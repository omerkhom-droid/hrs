<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can(
            'attendance.manage'
        );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => mb_strtoupper(
                trim((string) $this->input('code'))
            ),
            'name' => trim((string) $this->input('name')),
            'name_en' => $this->clean($this->input('name_en')),
            'crosses_midnight' => $this->boolean('crosses_midnight'),
            'is_default' => $this->boolean('is_default'),
            'is_active' => $this->boolean('is_active'),
            'work_days' => array_values(array_unique(
                array_map('intval', (array) $this->input('work_days', []))
            )),
        ]);
    }

    public function rules(): array
    {
        $tenantId = (int) $this->user()->tenant_id;

        return [
            'attendance_policy_id' => [
                'required',
                'integer',
                Rule::exists('attendance_policies', 'id')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at'),
            ],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('work_shifts', 'code')
                    ->where('tenant_id', $tenantId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'shift_type' => [
                'required',
                Rule::in(['regular', 'flexible', 'night']),
            ],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'crosses_midnight' => ['required', 'boolean'],
            'break_minutes' => ['required', 'integer', 'between:0,720'],
            'work_days' => ['required', 'array', 'min:1'],
            'work_days.*' => ['required', 'integer', 'between:0,6'],
            'is_default' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'attendance_policy_id' => 'سياسة الحضور',
            'code' => 'كود الوردية',
            'name' => 'اسم الوردية',
            'shift_type' => 'نوع الوردية',
            'start_time' => 'وقت البداية',
            'end_time' => 'وقت النهاية',
            'break_minutes' => 'مدة الاستراحة',
            'work_days' => 'أيام العمل',
        ];
    }

    protected function clean(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
