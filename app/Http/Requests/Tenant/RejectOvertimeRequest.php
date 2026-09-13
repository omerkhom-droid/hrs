<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class RejectOvertimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('attendance.approve');
    }

    public function rules(): array
    {
        return [
            'decision_notes' => ['required', 'string', 'min:3', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'decision_notes' => 'سبب الرفض',
        ];
    }
}
