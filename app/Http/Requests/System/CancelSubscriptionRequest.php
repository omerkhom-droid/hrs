<?php

namespace App\Http\Requests\System;

use Illuminate\Foundation\Http\FormRequest;

class CancelSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cancellation_reason' => [
                'required',
                'string',
                'max:1000',
            ],
        ];
    }
}