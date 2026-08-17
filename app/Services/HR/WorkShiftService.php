<?php

namespace App\Services\HR;

use App\Models\AttendancePolicy;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkShift;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

class WorkShiftService
{
    public function ensureDefaultPolicy(
        Tenant $tenant
    ): AttendancePolicy {
        $policy = AttendancePolicy::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_default', true)
            ->first();

        if ($policy) {
            return $policy;
        }

        return AttendancePolicy::query()->create([
            'tenant_id' => $tenant->id,
            'code' => 'DEFAULT',
            'name' => 'سياسة الدوام الأساسية',
            'timezone' => $tenant->timezone ?: 'Asia/Riyadh',
            'late_grace_minutes' => 10,
            'early_leave_grace_minutes' => 5,
            'early_check_in_minutes' => 120,
            'late_check_out_minutes' => 240,
            'overtime_after_minutes' => 0,
            'rounding_rule' => 'none',
            'allow_web' => true,
            'allow_mobile' => true,
            'require_geofence' => false,
            'allow_outside_geofence' => false,
            'require_photo' => false,
            'auto_check_out' => false,
            'weekend_days' => [5, 6],
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    public function updateDefaultPolicy(
        Tenant $tenant,
        User $actor,
        array $data
    ): AttendancePolicy {
        $this->ensureActorBelongsToTenant($actor, $tenant);

        $policy = $this->ensureDefaultPolicy($tenant);
        $policy->fill($data);
        $policy->is_default = true;
        $policy->is_active = true;
        $policy->save();

        return $policy->refresh();
    }

    public function createShift(
        Tenant $tenant,
        User $actor,
        array $data
    ): WorkShift {
        $this->ensureActorBelongsToTenant($actor, $tenant);
        $this->ensurePolicyBelongsToTenant(
            $tenant,
            (int) $data['attendance_policy_id']
        );

        $data = $this->normalizeShiftData($data);

        return DB::transaction(function () use ($tenant, $data) {
            if (
                $data['is_default']
                || !WorkShift::query()->where('tenant_id', $tenant->id)->exists()
            ) {
                WorkShift::query()
                    ->where('tenant_id', $tenant->id)
                    ->update(['is_default' => false]);

                $data['is_default'] = true;
            }

            $shift = new WorkShift($data);
            $shift->tenant_id = $tenant->id;
            $shift->save();

            return $shift->load('policy');
        });
    }

    public function updateShift(
        WorkShift $shift,
        User $actor,
        array $data
    ): WorkShift {
        $this->ensureShiftAccess($shift, $actor);

        if (isset($data['attendance_policy_id'])) {
            $this->ensurePolicyBelongsToTenant(
                Tenant::query()->findOrFail($shift->tenant_id),
                (int) $data['attendance_policy_id']
            );
        }

        $data = $this->normalizeShiftData($data, $shift);

        return DB::transaction(function () use ($shift, $data) {
            if (($data['is_default'] ?? false) === true) {
                WorkShift::query()
                    ->where('tenant_id', $shift->tenant_id)
                    ->where('id', '!=', $shift->id)
                    ->update(['is_default' => false]);
            }

            $shift->fill($data);
            $shift->save();

            if (
                !$shift->is_default
                && !WorkShift::query()
                    ->where('tenant_id', $shift->tenant_id)
                    ->where('is_default', true)
                    ->exists()
            ) {
                $shift->is_default = true;
                $shift->save();
            }

            return $shift->load('policy');
        });
    }

    public function archiveShift(
        WorkShift $shift,
        User $actor
    ): void {
        $this->ensureShiftAccess($shift, $actor);

        $hasAssignments = EmployeeShiftAssignment::query()
            ->where('work_shift_id', $shift->id)
            ->exists();

        $hasAttendanceRecords = $shift
            ->attendanceRecords()
            ->withTrashed()
            ->exists();

        if ($hasAssignments || $hasAttendanceRecords) {
            throw new LogicException(
                'لا يمكن أرشفة وردية مستخدمة. يمكنك تعطيلها للحفاظ على السجل التاريخي.'
            );
        }

        $shift->delete();
    }

    public function assignEmployee(
        Tenant $tenant,
        User $actor,
        array $data
    ): EmployeeShiftAssignment {
        $this->ensureActorBelongsToTenant($actor, $tenant);

        $employee = Employee::query()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($data['employee_id']);

        $shift = WorkShift::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->findOrFail($data['work_shift_id']);

        $from = Carbon::parse($data['effective_from'])->startOfDay();
        $to = !empty($data['effective_to'])
            ? Carbon::parse($data['effective_to'])->startOfDay()
            : null;

        return DB::transaction(function () use (
            $tenant,
            $actor,
            $employee,
            $shift,
            $data,
            $from,
            $to
        ) {
            $assignments = EmployeeShiftAssignment::query()
                ->where('tenant_id', $tenant->id)
                ->where('employee_id', $employee->id)
                ->lockForUpdate()
                ->get();

            foreach ($assignments as $assignment) {
                $existingFrom = $assignment->effective_from->copy()->startOfDay();
                $existingTo = $assignment->effective_to?->copy()->startOfDay();

                $overlaps = $existingFrom->lte($to ?: Carbon::create(9999, 12, 31))
                    && ($existingTo === null || $existingTo->gte($from));

                if (!$overlaps) {
                    continue;
                }

                if ($existingFrom->lt($from)) {
                    $assignment->effective_to = $from->copy()->subDay();
                    $assignment->save();
                    continue;
                }

                if ($existingFrom->equalTo($from)) {
                    $assignment->delete();
                    continue;
                }

                throw new LogicException(
                    'يوجد تكليف مستقبلي متداخل مع الفترة المحددة.'
                );
            }

            $assignment = new EmployeeShiftAssignment($data);
            $assignment->tenant_id = $tenant->id;
            $assignment->employee_id = $employee->id;
            $assignment->work_shift_id = $shift->id;
            $assignment->created_by = $actor->id;
            $assignment->save();

            return $assignment->load([
                'employee:id,tenant_id,employee_number,first_name,father_name,grandfather_name,family_name',
                'shift:id,tenant_id,code,name,start_time,end_time',
            ]);
        });
    }

    public function endAssignment(
        EmployeeShiftAssignment $assignment,
        User $actor,
        mixed $effectiveTo = null
    ): EmployeeShiftAssignment {
        if ((int) $assignment->tenant_id !== (int) $actor->tenant_id) {
            throw new LogicException(
                'لا يمكن إدارة تكليف تابع لشركة أخرى.'
            );
        }

        $endDate = $effectiveTo
            ? Carbon::parse($effectiveTo)->startOfDay()
            : today();

        if ($endDate->lt($assignment->effective_from)) {
            throw new LogicException(
                'تاريخ نهاية التكليف لا يمكن أن يسبق تاريخ بدايته.'
            );
        }

        $assignment->effective_to = $endDate;
        $assignment->save();

        return $assignment->load([
            'employee:id,tenant_id,employee_number,first_name,father_name,grandfather_name,family_name',
            'shift:id,tenant_id,code,name,start_time,end_time',
        ]);
    }

    private function normalizeShiftData(
        array $data,
        ?WorkShift $current = null
    ): array {
        $start = $data['start_time'] ?? $current?->start_time;
        $end = $data['end_time'] ?? $current?->end_time;
        $break = (int) ($data['break_minutes'] ?? $current?->break_minutes ?? 0);
        $crossesMidnight = (bool) (
            $data['crosses_midnight']
            ?? $current?->crosses_midnight
            ?? false
        );

        if ($start && $end) {
            $startMinutes = $this->timeToMinutes($start);
            $endMinutes = $this->timeToMinutes($end);

            if ($crossesMidnight || $endMinutes <= $startMinutes) {
                $endMinutes += 1440;
                $data['crosses_midnight'] = true;
            }

            $duration = $endMinutes - $startMinutes;

            if ($break >= $duration) {
                throw new LogicException(
                    'مدة الاستراحة يجب أن تكون أقل من مدة الوردية.'
                );
            }

            $data['working_minutes'] = $duration - $break;
        }

        return $data;
    }

    private function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = array_map(
            'intval',
            explode(':', substr($time, 0, 5))
        );

        return ($hours * 60) + $minutes;
    }

    private function ensurePolicyBelongsToTenant(
        Tenant $tenant,
        int $policyId
    ): void {
        $exists = AttendancePolicy::query()
            ->where('tenant_id', $tenant->id)
            ->whereKey($policyId)
            ->exists();

        if (!$exists) {
            throw new LogicException(
                'سياسة الحضور المحددة لا تتبع الشركة الحالية.'
            );
        }
    }

    private function ensureActorBelongsToTenant(
        User $actor,
        Tenant $tenant
    ): void {
        if ((int) $actor->tenant_id !== (int) $tenant->id) {
            throw new LogicException(
                'لا يمكن إدارة دوام شركة أخرى.'
            );
        }
    }

    private function ensureShiftAccess(
        WorkShift $shift,
        User $actor
    ): void {
        if ((int) $shift->tenant_id !== (int) $actor->tenant_id) {
            throw new LogicException(
                'لا يمكن إدارة وردية تابعة لشركة أخرى.'
            );
        }
    }
}
