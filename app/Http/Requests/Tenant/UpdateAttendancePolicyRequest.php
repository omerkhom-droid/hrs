<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttendancePolicyRequest extends FormRequest
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
            'allow_web' => $this->boolean('allow_web'),
            'allow_mobile' => $this->boolean('allow_mobile'),
            'require_geofence' => $this->boolean('require_geofence'),
            'allow_outside_geofence' => $this->boolean('allow_outside_geofence'),
            'require_photo' => $this->boolean('require_photo'),
            'auto_check_out' => $this->boolean('auto_check_out'),
            'weekend_days' => array_values(array_unique(
                array_map('intval', (array) $this->input('weekend_days', []))
            )),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'timezone' => ['required', 'timezone'],
            'late_grace_minutes' => ['required', 'integer', 'between:0,1440'],
            'early_leave_grace_minutes' => ['required', 'integer', 'between:0,1440'],
            'early_check_in_minutes' => ['required', 'integer', 'between:0,1440'],
            'late_check_out_minutes' => ['required', 'integer', 'between:0,1440'],
            'overtime_after_minutes' => ['required', 'integer', 'between:0,1440'],
            'rounding_rule' => [
                'required',
                Rule::in(['none', 'nearest_5', 'nearest_10', 'nearest_15']),
            ],
            'allow_web' => ['required', 'boolean'],
            'allow_mobile' => ['required', 'boolean'],
            'require_geofence' => ['required', 'boolean'],
            'allow_outside_geofence' => ['required', 'boolean'],
            'require_photo' => ['required', 'boolean'],
            'auto_check_out' => ['required', 'boolean'],
            'auto_check_out_after_minutes' => [
                'required',
                'integer',
                'between:0,1440',
            ],
            'approval_mode' => [
                'required',
                Rule::in(['manual', 'auto_clean']),
            ],
            'max_location_accuracy' => [
                'required',
                'integer',
                'between:10,500',
            ],
            'weekend_days' => ['required', 'array'],
            'weekend_days.*' => ['integer', 'between:0,6'],
        ];
    }

    public function attributes(): array
    {
        return [
            'auto_check_out_after_minutes' =>
                'مدة انتظار الانصراف التلقائي',
            'approval_mode' => 'طريقة اعتماد الحضور',
            'max_location_accuracy' => 'أقصى دقة مسموحة للموقع',
        ];
    }
}
