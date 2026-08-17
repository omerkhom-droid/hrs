<?php

namespace App\Services\HR;

use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

class EmployeeContractService
{
    public function create(
        Tenant $tenant,
        User $actor,
        array $data
    ): EmployeeContract {
        $this->ensureActorBelongsToTenant(
            $actor,
            $tenant
        );

        return DB::transaction(function () use (
            $tenant,
            $data
        ) {
            Tenant::query()
                ->whereKey($tenant->id)
                ->lockForUpdate()
                ->firstOrFail();

            $employee = Employee::query()
                ->where('tenant_id', $tenant->id)
                ->lockForUpdate()
                ->findOrFail($data['employee_id']);

            $renewedFrom = null;

            if (!empty($data['renewed_from_id'])) {
                $renewedFrom = EmployeeContract::query()
                    ->where('tenant_id', $tenant->id)
                    ->lockForUpdate()
                    ->findOrFail($data['renewed_from_id']);

                if (
                    (int) $renewedFrom->employee_id
                    !== (int) $employee->id
                ) {
                    throw new LogicException(
                        'لا يمكن ربط تجديد العقد بموظف مختلف.'
                    );
                }
            }

            unset(
                $data['tenant_id'],
                $data['uuid'],
                $data['status'],
                $data['activated_at'],
                $data['activated_by'],
                $data['termination_date'],
                $data['termination_reason'],
                $data['terminated_by']
            );

            $data = $this->normalizeContractData(
                $data
            );

            $data['contract_number'] =
                $data['contract_number']
                ?? $this->generateContractNumber($tenant);

            $contract = new EmployeeContract($data);
            $contract->tenant_id = $tenant->id;
            $contract->employee_id = $employee->id;
            $contract->renewed_from_id = $renewedFrom?->id;
            $contract->status = 'draft';
            $contract->save();

            return $this->loadRelations($contract);
        });
    }

    public function update(
        EmployeeContract $contract,
        User $actor,
        array $data
    ): EmployeeContract {
        $this->ensureContractAccess(
            $contract,
            $actor
        );

        if ($contract->status !== 'draft') {
            throw new LogicException(
                'لا يمكن تعديل البيانات الأساسية والمالية بعد تفعيل العقد.'
            );
        }

        unset(
            $data['tenant_id'],
            $data['uuid'],
            $data['employee_id'],
            $data['renewed_from_id'],
            $data['status'],
            $data['activated_at'],
            $data['activated_by'],
            $data['termination_date'],
            $data['termination_reason'],
            $data['terminated_by']
        );

        return DB::transaction(function () use (
            $contract,
            $data
        ) {
            $contract->fill(
                $this->normalizeContractData(
                    $data,
                    $contract
                )
            );
            $contract->save();

            return $this->loadRelations($contract);
        });
    }

    public function activate(
        EmployeeContract $contract,
        User $actor
    ): EmployeeContract {
        $this->ensureContractAccess(
            $contract,
            $actor
        );

        if ($contract->status !== 'draft') {
            throw new LogicException(
                'يمكن تفعيل العقود الموجودة في حالة مسودة فقط.'
            );
        }

        return DB::transaction(function () use (
            $contract,
            $actor
        ) {
            $contract = EmployeeContract::query()
                ->lockForUpdate()
                ->findOrFail($contract->id);

            $this->ensureContractDatesAreValid(
                $contract
            );
            $this->ensureNoOverlappingContract(
                $contract
            );

            $contract->forceFill([
                'status' => 'active',
                'signed_at' => $contract->signed_at ?: now(),
                'activated_at' => now(),
                'activated_by' => $actor->id,
            ])->save();

            $this->activateEmployeeFromContract(
                $contract
            );

            return $this->loadRelations($contract);
        });
    }

    public function suspend(
        EmployeeContract $contract,
        User $actor
    ): EmployeeContract {
        $this->ensureContractAccess($contract, $actor);

        if ($contract->status !== 'active') {
            throw new LogicException(
                'يمكن إيقاف العقد الفعال فقط.'
            );
        }

        return DB::transaction(function () use ($contract) {
            $contract->forceFill([
                'status' => 'suspended',
            ])->save();

            $contract->employee()->update([
                'employment_status' => 'suspended',
            ]);

            return $this->loadRelations($contract);
        });
    }

    public function resume(
        EmployeeContract $contract,
        User $actor
    ): EmployeeContract {
        $this->ensureContractAccess($contract, $actor);

        if ($contract->status !== 'suspended') {
            throw new LogicException(
                'يمكن استئناف العقد الموقوف فقط.'
            );
        }

        return DB::transaction(function () use ($contract) {
            $this->ensureNoOverlappingContract(
                $contract
            );

            $contract->forceFill([
                'status' => 'active',
            ])->save();

            $this->activateEmployeeFromContract(
                $contract
            );

            return $this->loadRelations($contract);
        });
    }

    public function terminate(
        EmployeeContract $contract,
        User $actor,
        array $data
    ): EmployeeContract {
        $this->ensureContractAccess($contract, $actor);

        if (!in_array(
            $contract->status,
            ['active', 'suspended'],
            true
        )) {
            throw new LogicException(
                'يمكن إنهاء العقد الفعال أو الموقوف فقط.'
            );
        }

        $terminationDate = Carbon::parse(
            $data['termination_date']
        )->startOfDay();

        if ($terminationDate->lt($contract->start_date)) {
            throw new LogicException(
                'تاريخ الإنهاء لا يمكن أن يسبق بداية العقد.'
            );
        }

        return DB::transaction(function () use (
            $contract,
            $actor,
            $data,
            $terminationDate
        ) {
            $contract->forceFill([
                'status' => 'terminated',
                'termination_date' => $terminationDate,
                'termination_reason' => $data['termination_reason'],
                'terminated_by' => $actor->id,
            ])->save();

            $hasOtherCurrentContract = EmployeeContract::query()
                ->where('employee_id', $contract->employee_id)
                ->where('id', '!=', $contract->id)
                ->whereIn('status', ['active', 'suspended'])
                ->exists();

            if (
                !$hasOtherCurrentContract &&
                $terminationDate->lte(today())
            ) {
                $contract->employee()->update([
                    'employment_status' => 'terminated',
                    'termination_date' => $terminationDate,
                    'termination_reason' => $data['termination_reason'],
                ]);
            }

            return $this->loadRelations($contract);
        });
    }

    public function cancel(
        EmployeeContract $contract,
        User $actor
    ): EmployeeContract {
        $this->ensureContractAccess($contract, $actor);

        if ($contract->status !== 'draft') {
            throw new LogicException(
                'يمكن إلغاء مسودة العقد فقط.'
            );
        }

        $contract->forceFill([
            'status' => 'cancelled',
        ])->save();

        return $this->loadRelations($contract);
    }

    public function archive(
        EmployeeContract $contract,
        User $actor
    ): void {
        $this->ensureContractAccess($contract, $actor);

        if (!in_array(
            $contract->status,
            ['draft', 'cancelled', 'expired'],
            true
        )) {
            throw new LogicException(
                'لا يمكن أرشفة عقد فعال أو موقوف أو منتهى بالخدمة.'
            );
        }

        $contract->delete();
    }

    public function restore(
        EmployeeContract $contract,
        User $actor
    ): EmployeeContract {
        $this->ensureContractAccess($contract, $actor);

        if ($contract->trashed()) {
            $contract->restore();
        }

        return $this->loadRelations($contract);
    }

    private function normalizeContractData(
        array $data,
        ?EmployeeContract $current = null
    ): array {
        if (array_key_exists('contract_number', $data)) {
            $number = strtoupper(
                trim((string) $data['contract_number'])
            );
            $data['contract_number'] = $number ?: null;
        }

        if (array_key_exists('currency_code', $data)) {
            $data['currency_code'] = strtoupper(
                trim((string) $data['currency_code'])
            );
        }

        $type = $data['contract_type']
            ?? $current?->contract_type;

        if ($type === 'indefinite') {
            $data['end_date'] = null;
            $data['auto_renew'] = false;
        }

        foreach ([
            'housing_allowance',
            'transport_allowance',
            'other_allowances',
        ] as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = $data[$field] ?? 0;
            }
        }

        return $data;
    }

    private function generateContractNumber(
        Tenant $tenant
    ): string {
        $prefix = 'CON-' . now()->format('Y') . '-';

        $lastNumber = EmployeeContract::query()
            ->withTrashed()
            ->where('tenant_id', $tenant->id)
            ->where(
                'contract_number',
                'like',
                $prefix . '%'
            )
            ->orderByDesc('contract_number')
            ->value('contract_number');

        $sequence = $lastNumber
            ? ((int) substr($lastNumber, strlen($prefix))) + 1
            : 1;

        return $prefix . str_pad(
            (string) $sequence,
            6,
            '0',
            STR_PAD_LEFT
        );
    }

    private function ensureContractDatesAreValid(
        EmployeeContract $contract
    ): void {
        if (
            $contract->contract_type !== 'indefinite' &&
            !$contract->end_date
        ) {
            throw new LogicException(
                'يجب تحديد تاريخ نهاية العقد قبل التفعيل.'
            );
        }

        if (
            $contract->end_date &&
            $contract->end_date->lt($contract->start_date)
        ) {
            throw new LogicException(
                'تاريخ نهاية العقد لا يمكن أن يسبق تاريخ البداية.'
            );
        }
    }

    private function ensureNoOverlappingContract(
        EmployeeContract $contract
    ): void {
        $query = EmployeeContract::query()
            ->where('tenant_id', $contract->tenant_id)
            ->where('employee_id', $contract->employee_id)
            ->where('id', '!=', $contract->id)
            ->whereIn('status', ['active', 'suspended'])
            ->where(function ($query) use ($contract) {
                $query
                    ->whereNull('end_date')
                    ->orWhere(
                        'end_date',
                        '>=',
                        $contract->start_date
                    );
            });

        if ($contract->end_date) {
            $query->where(
                'start_date',
                '<=',
                $contract->end_date
            );
        }

        if ($query->exists()) {
            throw new LogicException(
                'يوجد عقد فعال أو موقوف يتداخل مع مدة هذا العقد.'
            );
        }
    }

    private function activateEmployeeFromContract(
        EmployeeContract $contract
    ): void {
        $employeeStatus =
            $contract->probation_end_date &&
            $contract->probation_end_date->gte(today())
                ? 'probation'
                : 'active';

        $employee = $contract->employee()
            ->lockForUpdate()
            ->firstOrFail();

        $employee->employment_status = $employeeStatus;
        $employee->hire_date =
            $employee->hire_date
            ?: $contract->start_date;
        $employee->probation_end_date =
            $contract->probation_end_date;
        $employee->termination_date = null;
        $employee->termination_reason = null;
        $employee->save();
    }

    private function ensureContractAccess(
        EmployeeContract $contract,
        User $actor
    ): void {
        if (
            !$actor->tenant_id ||
            (int) $actor->tenant_id
                !== (int) $contract->tenant_id
        ) {
            throw new LogicException(
                'لا يمكن إدارة عقد تابع لشركة أخرى.'
            );
        }
    }

    private function ensureActorBelongsToTenant(
        User $actor,
        Tenant $tenant
    ): void {
        if (
            !$actor->tenant_id ||
            (int) $actor->tenant_id !== (int) $tenant->id
        ) {
            throw new LogicException(
                'المستخدم لا ينتمي إلى الشركة المحددة.'
            );
        }
    }

    private function loadRelations(
        EmployeeContract $contract
    ): EmployeeContract {
        return $contract->fresh([
            'employee:id,tenant_id,employee_number,first_name,father_name,grandfather_name,family_name',
            'renewedFrom:id,tenant_id,employee_id,contract_number',
            'activatedBy:id,tenant_id,name',
            'terminatedBy:id,tenant_id,name',
        ]);
    }
}
