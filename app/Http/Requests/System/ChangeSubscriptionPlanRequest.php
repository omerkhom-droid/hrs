<?php

namespace App\Http\Requests\System;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeSubscriptionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id' => [
                'required',
                'integer',

                Rule::exists('plans', 'id')
                    ->whereNull('deleted_at')
                    ->where('is_active', true),
            ],

            'billing_cycle' => [
                'required',
                Rule::in(['monthly', 'yearly']),
            ],
        ];
    }
}