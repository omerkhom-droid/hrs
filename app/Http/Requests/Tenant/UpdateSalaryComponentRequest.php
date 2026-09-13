<?php

namespace App\Http\Requests\Tenant;

use App\Models\SalaryComponent;

class UpdateSalaryComponentRequest extends StoreSalaryComponentRequest
{
    public function authorize(): bool
    {
        $user =
            $this->user();

        $component =
            $this->route(
                'salaryComponent'
            )
            ?? $this->route(
                'salary_component'
            );

        if (
            !$user ||
            !$user->tenant_id ||
            !$user->can(
                'payroll.manage'
            )
        ) {
            return false;
        }

        if (
            !$component instanceof
            SalaryComponent
        ) {
            return false;
        }

        return
            (int) $component->tenant_id ===
            (int) $user->tenant_id;
    }
}