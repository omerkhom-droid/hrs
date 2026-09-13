<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class ApproveOvertimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('attendance.approve');
    }

    public function rules(): array
    {
        return [
            'approved_minutes' => [
                'required',
                'integer',
                'between:1,1440',
            ],
            'decision_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'approved_minutes' => 'الدقائق المعتمدة',
            'decision_notes' => 'ملاحظات الاعتماد',
        ];
    }
}
