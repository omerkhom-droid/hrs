<?php

namespace App\Http\Requests\Tenant;

use App\Models\EmployeeSalaryStructure;
use Illuminate\Validation\Rule;

class UpdateEmployeeSalaryStructureRequest extends
    StoreEmployeeSalaryStructureRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        $structure =
            $this->resolveSalaryStructure();

        if (
            !$user ||
            !$user->tenant_id ||
            !$user->can('payroll.manage') ||
            !$structure
        ) {
            return false;
        }

        if (
            (int) $structure->tenant_id !==
            (int) $user->tenant_id
        ) {
            return false;
        }

        /*
         * لا نعدل الهيكل النشط أو المنتهي.
         * التعديل المباشر مسموح للمسودة فقط.
         */
        return $structure->canBeEdited();
    }


    public function rules(): array
    {
        $rules = parent::rules();

        $structure =
            $this->resolveSalaryStructure();

        /*
         * لا يمكن نقل هيكل الراتب من موظف إلى آخر.
         */
        $rules['employee_id'][] = Rule::in([
            (int) ($structure?->employee_id ?? 0),
        ]);

        return $rules;
    }


    public function messages(): array
    {
        return array_merge(
            parent::messages(),
            [
                'employee_id.in' =>
                    'لا يمكن تغيير الموظف المرتبط بهيكل الراتب.',
            ]
        );
    }


    private function resolveSalaryStructure():
        ?EmployeeSalaryStructure
    {
        $structure =
            $this->route('salaryStructure')
            ?? $this->route(
                'employeeSalaryStructure'
            );

        if (
            $structure instanceof
            EmployeeSalaryStructure
        ) {
            return $structure;
        }

        if (
            is_numeric($structure) &&
            $this->user()?->tenant_id
        ) {
            return EmployeeSalaryStructure::query()
                ->where(
                    'tenant_id',
                    $this->user()->tenant_id
                )
                ->whereKey((int) $structure)
                ->first();
        }

        return null;
    }
}