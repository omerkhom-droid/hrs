<?php

namespace App\Http\Requests\Tenant;

use App\Models\EmployeeContract;
use Illuminate\Validation\Rule;

class UpdateEmployeeContractRequest extends StoreEmployeeContractRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can(
            'contracts.update'
        );
    }

    protected function prepareForValidation(): void
    {
        $values = [];

        if ($this->has('contract_number')) {
            $values['contract_number'] = $this->cleanUpper(
                $this->input('contract_number')
            );
        }

        if ($this->has('currency_code')) {
            $values['currency_code'] = $this->cleanUpper(
                $this->input('currency_code')
            );
        }

        if ($this->has('auto_renew')) {
            $values['auto_renew'] = $this->boolean(
                'auto_renew'
            );
        }

        $this->merge($values);
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $tenantId = (int) $this->user()->tenant_id;
        $contract = $this->route('contract');
        $contractId = $contract instanceof EmployeeContract
            ? $contract->getKey()
            : $contract;

        foreach ($rules as $field => $fieldRules) {
            $rules[$field] = [
                'sometimes',
                ...$fieldRules,
            ];
        }

        $rules['employee_id'] = ['prohibited'];
        $rules['renewed_from_id'] = ['prohibited'];

        $rules['contract_number'] = [
            'sometimes',
            'required',
            'string',
            'max:50',
            Rule::unique('employee_contracts', 'contract_number')
                ->where('tenant_id', $tenantId)
                ->ignore($contractId),
        ];

        return $rules;
    }
}
