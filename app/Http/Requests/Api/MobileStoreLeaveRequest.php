<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Tenant\StoreLeaveRequest;
use App\Models\Employee;

class MobileStoreLeaveRequest extends StoreLeaveRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return (bool) (
            $user &&
            $user->tenant_id &&
            $user->can('self_service.leave')
        );
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $employeeId = Employee::withoutGlobalScopes()
            ->where('tenant_id', $this->user()?->tenant_id)
            ->where('user_id', $this->user()?->id)
            ->whereNull('deleted_at')
            ->value('id');

        /*
         * تطبيق الموظف لا يقبل employee_id من العميل مطلقًا.
         */
        $this->merge([
            'employee_id' => $employeeId
                ? (int) $employeeId
                : null,
        ]);
    }
}
