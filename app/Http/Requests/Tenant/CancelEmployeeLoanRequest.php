<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class CancelEmployeeLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return (bool) (
            $user?->tenant_id &&
            (
                $user->can('loans.manage') ||
                $user->can('self_service.loans')
            )
        );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cancellation_reason' =>
                $this->filled('cancellation_reason')
                    ? trim(
                        (string) $this->input(
                            'cancellation_reason'
                        )
                    )
                    : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'cancellation_reason' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'cancellation_reason.max' =>
                'سبب الإلغاء طويل جدًا.',
        ];
    }

    public function attributes(): array
    {
        return [
            'cancellation_reason' => 'سبب الإلغاء',
        ];
    }
}