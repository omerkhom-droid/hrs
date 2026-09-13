<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class BulkApproveAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('attendance.approve');
    }

    public function rules(): array
    {
        return [
            'record_ids' => ['required', 'array', 'min:1', 'max:100'],
            'record_ids.*' => ['required', 'integer', 'distinct', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return [
            'record_ids' => 'سجلات الحضور',
            'record_ids.*' => 'سجل الحضور',
        ];
    }
}
