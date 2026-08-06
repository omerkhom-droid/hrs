<?php

namespace App\Http\Requests\System;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],

            'monthly_price' => ['required', 'numeric', 'min:0'],
            'yearly_price' => ['required', 'numeric', 'min:0'],

            'currency_code' => ['required', 'string', 'size:3'],

            'trial_days' => ['required', 'integer', 'min:0', 'max:365'],

            'max_users' => ['nullable', 'integer', 'min:1'],
            'max_employees' => ['nullable', 'integer', 'min:1'],
            'max_branches' => ['nullable', 'integer', 'min:1'],

            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }
}