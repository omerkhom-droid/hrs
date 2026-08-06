<?php

namespace App\Http\Requests\System;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => $this->email
                ? strtolower(trim($this->email))
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],

            'country_code' => ['required', 'string', 'size:2'],
            'timezone' => ['required', 'string', 'max:100'],
            'locale' => ['required', 'string', 'max:10'],
            'currency_code' => ['required', 'string', 'size:3'],

            'status' => [
                'required',
                'in:active,suspended,inactive',
            ],
        ];
    }
}