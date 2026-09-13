<?php

namespace App\Http\Requests\Tenant;

use App\Models\PayrollPeriod;

class UpdatePayrollPeriodRequest extends
    StorePayrollPeriodRequest
{
    private ?PayrollPeriod $resolvedPeriod =
        null;


    public function authorize(): bool
    {
        $user = $this->user();

        $period =
            $this->resolvePayrollPeriod();

        if (
            !$user ||
            !$user->tenant_id ||
            !$user->can('payroll.manage') ||
            !$period
        ) {
            return false;
        }

        if (
            (int) $period->tenant_id !==
            (int) $user->tenant_id
        ) {
            return false;
        }

        /*
         * تعديل الفترة مسموح وهي مسودة
         * وغير مقفلة فقط.
         */
        return $period->canBeEdited();
    }


    protected function ignoredPayrollPeriodId(): ?int
    {
        return $this
            ->resolvePayrollPeriod()
            ?->id;
    }


    private function resolvePayrollPeriod():
        ?PayrollPeriod
    {
        if ($this->resolvedPeriod) {
            return $this->resolvedPeriod;
        }

        $period =
            $this->route('payrollPeriod')
            ?? $this->route('period');

        if (
            $period instanceof
            PayrollPeriod
        ) {
            return $this->resolvedPeriod =
                $period;
        }

        if (
            is_numeric($period) &&
            $this->user()?->tenant_id
        ) {
            return $this->resolvedPeriod =
                PayrollPeriod::query()
                    ->where(
                        'tenant_id',
                        $this->user()->tenant_id
                    )
                    ->whereKey(
                        (int) $period
                    )
                    ->first();
        }

        return null;
    }
}