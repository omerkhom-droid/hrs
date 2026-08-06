<?php

namespace App\Http\Requests\System;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => [
                'required',
                'integer',

                Rule::exists('tenants', 'id')
                    ->whereNull('deleted_at')
                    ->where('status', 'active'),
            ],

            'plan_id' => [
                'required',
                'integer',

                Rule::exists('plans', 'id')
                    ->whereNull('deleted_at')
                    ->where('is_active', true),
            ],

            'billing_cycle' => [
                'required',
                Rule::in([
                    'monthly',
                    'yearly',
                ]),
            ],

            'starts_at' => [
                'required',
                'date',
            ],

            'use_trial' => [
                'required',
                'boolean',
            ],

            'auto_renew' => [
                'required',
                'boolean',
            ],
        ];
    }
}