<?php

namespace App\Http\Requests\System;

use Illuminate\Foundation\Http\FormRequest;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->code)),
            'email' => $this->email
                ? strtolower(trim($this->email))
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'min:2',
                'max:30',
                'regex:/^[A-Z0-9_-]+$/',
                'unique:tenants,code',
            ],

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