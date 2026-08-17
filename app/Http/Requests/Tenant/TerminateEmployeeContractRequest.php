<?php

namespace App\Http\Requests\Tenant;

use App\Models\EmployeeContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class TerminateEmployeeContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can(
            'contracts.end'
        );
    }

    public function rules(): array
    {
        return [
            'termination_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
            'termination_reason' => [
                'required',
                'string',
                'min:5',
                'max:5000',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $contract = $this->route('contract');

            if (
                $contract instanceof EmployeeContract &&
                $this->filled('termination_date') &&
                $contract->start_date &&
                strtotime((string) $this->input('termination_date'))
                    < $contract->start_date->startOfDay()->timestamp
            ) {
                $validator->errors()->add(
                    'termination_date',
                    'تاريخ الإنهاء لا يمكن أن يسبق بداية العقد.'
                );
            }
        });
    }

    public function attributes(): array
    {
        return [
            'termination_date' => 'تاريخ الإنهاء',
            'termination_reason' => 'سبب الإنهاء',
        ];
    }
}
