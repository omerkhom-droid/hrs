<?php

namespace App\Services\HR;

use App\Models\AttendancePolicy;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkLocation;
use App\Models\WorkShift;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

class AttendanceService
{
    public function __construct(
        private readonly WorkShiftService $shiftService
    ) {
    }

    public function createManualRecord(
        Tenant $tenant,
        User $actor,
        array $data
    ): AttendanceRecord {
        $this->ensureActorBelongsToTenant($actor, $tenant);

        $employee = Employee::query()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($data['employee_id']);

        $attendanceDate = Carbon::parse(
            $data['attendance_date']
        )->toDateString();

        $exists = AttendanceRecord::query()
            ->where('tenant_id', $tenant->id)
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $attendanceDate)
            ->exists();

        if ($exists) {
            throw new LogicException(
                'يوجد سجل حضور لهذا الموظف في التاريخ المحدد.'
            );
        }

        $shift = $this->resolveShift(
            $tenant,
            $employee,
            $attendanceDate,
            $data['work_shift_id'] ?? null
        );

        $policy = $shift?->policy
            ?: $this->shiftService->ensureDefaultPolicy($tenant);

        $location = $this->resolveLocation(
            $tenant,
            $employee,
            $data['work_location_id'] ?? null
        );

        $values = $this->prepareRecordValues(
            $data,
            $attendanceDate,
            $shift,
            $policy
        );

        $record = new AttendanceRecord($values);
        $record->tenant_id = $tenant->id;
        $record->employee_id = $employee->id;
        $record->work_shift_id = $shift?->id;
        $record->work_location_id = $location?->id;
        $record->check_in_source = $record->check_in_at
            ? 'manual'
            : null;
        $record->check_out_source = $record->check_out_at
            ? 'manual'
            : null;
        $record->created_by = $actor->id;
        $record->approval_status = 'pending';
        $record->save();

        return $this->loadRelations($record);
    }

    public function updateManualRecord(
        AttendanceRecord $record,
        User $actor,
        array $data
    ): AttendanceRecord {
        $this->ensureRecordAccess($record, $actor);

        if ($record->approval_status === 'approved') {
            throw new LogicException(
                'يجب إلغاء اعتماد السجل قبل تعديله.'
            );
        }

        $tenant = Tenant::query()->findOrFail($record->tenant_id);
        $employee = $record->employee;
        $attendanceDate = $record->attendance_date->toDateString();

        $shift = $this->resolveShift(
            $tenant,
            $employee,
            $attendanceDate,
            $data['work_shift_id'] ?? $record->work_shift_id
        );

        $policy = $shift?->policy
            ?: $this->shiftService->ensureDefaultPolicy($tenant);

        $location = $this->resolveLocation(
            $tenant,
            $employee,
            $data['work_location_id'] ?? $record->work_location_id
        );

        $merged = [
            'status' => $record->status,
            'check_in_at' => $this->localDateTime(
                $record->check_in_at,
                $record->timezone
            ),
            'check_out_at' => $this->localDateTime(
                $record->check_out_at,
                $record->timezone
            ),
            'notes' => $record->notes,
            ...$data,
        ];

        $values = $this->prepareRecordValues(
            $merged,
            $attendanceDate,
            $shift,
            $policy
        );

        $record->fill($values);
        $record->work_shift_id = $shift?->id;
        $record->work_location_id = $location?->id;
        $record->check_in_source = $record->check_in_at
            ? 'manual'
            : null;
        $record->check_out_source = $record->check_out_at
            ? 'manual'
            : null;
        $record->approval_status = 'pending';
        $record->approved_at = null;
        $record->approved_by = null;
        $record->save();

        return $this->loadRelations($record);
    }

    public function approve(
        AttendanceRecord $record,
        User $actor
    ): AttendanceRecord {
        $this->ensureRecordAccess($record, $actor);

        if ($record->status === 'incomplete') {
            throw new LogicException(
                'لا يمكن اعتماد سجل غير مكتمل.'
            );
        }

        $record->forceFill([
            'approval_status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $actor->id,
        ])->save();

        return $this->loadRelations($record);
    }

    public function reopen(
        AttendanceRecord $record,
        User $actor
    ): AttendanceRecord {
        $this->ensureRecordAccess($record, $actor);

        $record->forceFill([
            'approval_status' => 'pending',
            'approved_at' => null,
            'approved_by' => null,
        ])->save();

        return $this->loadRelations($record);
    }

    public function archive(
        AttendanceRecord $record,
        User $actor
    ): void {
        $this->ensureRecordAccess($record, $actor);

        if ($record->approval_status === 'approved') {
            throw new LogicException(
                'لا يمكن أرشفة سجل معتمد قبل إلغاء اعتماده.'
            );
        }

        $record->delete();
    }

    /**
     * Resolve the same shift, policy, location and scheduled times used by
     * manual attendance. Mobile/API punch services call this method so the
     * business rules are not duplicated in controllers.
     */
    public function resolvePunchContext(
        Tenant $tenant,
        Employee $employee,
        string $attendanceDate,
        mixed $shiftId = null,
        mixed $locationId = null
    ): array {
        if ((int) $employee->tenant_id !== (int) $tenant->id) {
            throw new LogicException(
                'الموظف لا يتبع الشركة الحالية.'
            );
        }

        $shift = $this->resolveShift(
            $tenant,
            $employee,
            $attendanceDate,
            $shiftId
        );

        $policy = $shift?->policy
            ?: $this->shiftService->ensureDefaultPolicy($tenant);

        $location = $this->resolveLocation(
            $tenant,
            $employee,
            $locationId
        );

        [$scheduledIn, $scheduledOut] = $this->scheduledTimes(
            $attendanceDate,
            $shift,
            $policy->timezone ?: 'Asia/Riyadh'
        );

        return [
            'shift' => $shift,
            'policy' => $policy,
            'location' => $location,
            'scheduled_in' => $scheduledIn,
            'scheduled_out' => $scheduledOut,
        ];
    }

    /**
     * Calculate attendance values through the same calculator used by the
     * web administration records.
     */
    public function calculatePunchMetrics(
        string $requestedStatus,
        ?Carbon $checkIn,
        ?Carbon $checkOut,
        ?Carbon $scheduledIn,
        ?Carbon $scheduledOut,
        int $plannedBreakMinutes,
        AttendancePolicy $policy
    ): array {
        return $this->calculateMetrics(
            $requestedStatus,
            $checkIn,
            $checkOut,
            $scheduledIn,
            $scheduledOut,
            $plannedBreakMinutes,
            $policy
        );
    }

    private function prepareRecordValues(
        array $data,
        string $attendanceDate,
        ?WorkShift $shift,
        AttendancePolicy $policy
    ): array {
        $timezone = $policy->timezone ?: 'Asia/Riyadh';
        [$scheduledIn, $scheduledOut] = $this->scheduledTimes(
            $attendanceDate,
            $shift,
            $timezone
        );

        $checkIn = $this->parseLocalDateTime(
            $data['check_in_at'] ?? null,
            $timezone
        );
        $checkOut = $this->parseLocalDateTime(
            $data['check_out_at'] ?? null,
            $timezone
        );

        $status = $data['status'] ?? 'incomplete';
        $metrics = $this->calculateMetrics(
            $status,
            $checkIn,
            $checkOut,
            $scheduledIn,
            $scheduledOut,
            $shift?->break_minutes ?? 0,
            $policy
        );

        return [
            'attendance_date' => $attendanceDate,
            'timezone' => $timezone,
            'scheduled_check_in_at' => $scheduledIn,
            'scheduled_check_out_at' => $scheduledOut,
            'check_in_at' => $checkIn,
            'check_out_at' => $checkOut,
            'status' => $metrics['status'],
            'work_minutes' => $metrics['work_minutes'],
            'break_minutes' => $metrics['break_minutes'],
            'late_minutes' => $metrics['late_minutes'],
            'early_leave_minutes' => $metrics['early_leave_minutes'],
            'overtime_minutes' => $metrics['overtime_minutes'],
            'notes' => $data['notes'] ?? null,
        ];
    }

    private function calculateMetrics(
        string $requestedStatus,
        ?Carbon $checkIn,
        ?Carbon $checkOut,
        ?Carbon $scheduledIn,
        ?Carbon $scheduledOut,
        int $plannedBreakMinutes,
        AttendancePolicy $policy
    ): array {
        if (in_array($requestedStatus, [
            'absent',
            'on_leave',
            'holiday',
        ], true)) {
            return [
                'status' => $requestedStatus,
                'work_minutes' => 0,
                'break_minutes' => 0,
                'late_minutes' => 0,
                'early_leave_minutes' => 0,
                'overtime_minutes' => 0,
            ];
        }

        if (!$checkIn) {
            return [
                'status' => 'incomplete',
                'work_minutes' => 0,
                'break_minutes' => 0,
                'late_minutes' => 0,
                'early_leave_minutes' => 0,
                'overtime_minutes' => 0,
            ];
        }

        $lateMinutes = 0;

        if ($scheduledIn && $checkIn->gt($scheduledIn)) {
            $lateMinutes = max(
                0,
                (int) floor($scheduledIn->diffInMinutes($checkIn))
                    - $policy->late_grace_minutes
            );
        }

        if (!$checkOut) {
            return [
                'status' => 'incomplete',
                'work_minutes' => 0,
                'break_minutes' => 0,
                'late_minutes' => $lateMinutes,
                'early_leave_minutes' => 0,
                'overtime_minutes' => 0,
            ];
        }

        $totalMinutes = max(
            0,
            (int) floor($checkIn->diffInMinutes($checkOut))
        );
        $breakMinutes = min($plannedBreakMinutes, $totalMinutes);
        $workMinutes = max(0, $totalMinutes - $breakMinutes);
        $earlyLeaveMinutes = 0;
        $overtimeMinutes = 0;

        if ($scheduledOut && $checkOut->lt($scheduledOut)) {
            $earlyLeaveMinutes = max(
                0,
                (int) floor($checkOut->diffInMinutes($scheduledOut))
                    - $policy->early_leave_grace_minutes
            );
        }

        if ($scheduledOut) {
            $overtimeStartsAt = $scheduledOut->copy()->addMinutes(
                $policy->overtime_after_minutes
            );

            if ($checkOut->gt($overtimeStartsAt)) {
                $overtimeMinutes = (int) floor(
                    $overtimeStartsAt->diffInMinutes($checkOut)
                );
            }
        }

        $status = $requestedStatus === 'remote'
            ? 'remote'
            : ($lateMinutes > 0 ? 'late' : 'present');

        return [
            'status' => $status,
            'work_minutes' => $workMinutes,
            'break_minutes' => $breakMinutes,
            'late_minutes' => $lateMinutes,
            'early_leave_minutes' => $earlyLeaveMinutes,
            'overtime_minutes' => $overtimeMinutes,
        ];
    }

    private function scheduledTimes(
        string $attendanceDate,
        ?WorkShift $shift,
        string $timezone
    ): array {
        if (!$shift) {
            return [null, null];
        }

        $start = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $attendanceDate . ' ' . $this->normalizeTime($shift->start_time),
            $timezone
        );

        $end = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $attendanceDate . ' ' . $this->normalizeTime($shift->end_time),
            $timezone
        );

        if ($shift->crosses_midnight || $end->lte($start)) {
            $end->addDay();
        }

        return [$start->utc(), $end->utc()];
    }

    private function resolveShift(
        Tenant $tenant,
        Employee $employee,
        string $attendanceDate,
        mixed $shiftId
    ): ?WorkShift {
        if ($shiftId) {
            return WorkShift::query()
                ->with('policy')
                ->where('tenant_id', $tenant->id)
                ->findOrFail($shiftId);
        }

        $assignment = EmployeeShiftAssignment::query()
            ->with('shift.policy')
            ->where('tenant_id', $tenant->id)
            ->where('employee_id', $employee->id)
            ->effectiveOn($attendanceDate)
            ->orderByDesc('is_primary')
            ->orderByDesc('effective_from')
            ->first();

        if ($assignment?->shift) {
            return $assignment->shift;
        }

        return WorkShift::query()
            ->with('policy')
            ->where('tenant_id', $tenant->id)
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    private function resolveLocation(
        Tenant $tenant,
        Employee $employee,
        mixed $locationId
    ): ?WorkLocation {
        $locationId = $locationId ?: $employee->work_location_id;

        if (!$locationId) {
            return null;
        }

        return WorkLocation::query()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($locationId);
    }

    private function parseLocalDateTime(
        mixed $value,
        string $timezone
    ): ?Carbon {
        if (!$value) {
            return null;
        }

        return Carbon::parse($value, $timezone)->utc();
    }

    private function localDateTime(
        mixed $value,
        string $timezone
    ): ?string {
        if (!$value) {
            return null;
        }

        return Carbon::parse($value)
            ->timezone($timezone)
            ->format('Y-m-d H:i:s');
    }

    private function normalizeTime(string $time): string
    {
        return strlen($time) === 5 ? $time . ':00' : $time;
    }

    private function loadRelations(
        AttendanceRecord $record
    ): AttendanceRecord {
        return $record->load([
            'employee:id,tenant_id,employee_number,department_id,job_title_id,first_name,father_name,grandfather_name,family_name',
            'employee.department:id,tenant_id,name',
            'employee.jobTitle:id,tenant_id,name',
            'shift:id,tenant_id,code,name,start_time,end_time',
            'workLocation:id,tenant_id,code,name',
            'approvedBy:id,tenant_id,name',
            'createdBy:id,tenant_id,name',
        ]);
    }

    private function ensureActorBelongsToTenant(
        User $actor,
        Tenant $tenant
    ): void {
        if ((int) $actor->tenant_id !== (int) $tenant->id) {
            throw new LogicException(
                'لا يمكن إدارة حضور شركة أخرى.'
            );
        }
    }

    private function ensureRecordAccess(
        AttendanceRecord $record,
        User $actor
    ): void {
        if ((int) $record->tenant_id !== (int) $actor->tenant_id) {
            throw new LogicException(
                'لا يمكن إدارة سجل تابع لشركة أخرى.'
            );
        }
    }
}