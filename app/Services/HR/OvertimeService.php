<?php

namespace App\Services\HR;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

class OvertimeService
{
    private const MINIMUM_MINUTES = 15;
    private const MAXIMUM_MINUTES = 720;
    private const DUPLICATE_REQUEST_MESSAGE =
        'يوجد طلب عمل إضافي قائم لهذا الموظف في التاريخ المحدد.';

    public function __construct(
        private readonly MobileDeviceService $deviceService,
        private readonly AttendanceApprovalService $approvalService
    ) {
    }

    public function create(
        Tenant $tenant,
        User $user,
        array $data
    ): OvertimeRequest {
        $employee = $this->resolveEmployee($tenant, $user, $data);

        return DB::transaction(fn () => $this->createForEmployee(
            $tenant,
            $user,
            $employee,
            $data,
            null,
            1
        ));
    }

    /**
     * إنشاء تكليف واحد لعدة موظفين مع بقاء سجل مستقل لكل موظف.
     * الموظفون الذين لديهم طلب قائم في اليوم نفسه يتم تخطيهم فقط.
     */
    public function createMany(
        Tenant $tenant,
        User $user,
        array $data
    ): array {
        if (!$user->can('attendance.manage')) {
            throw new LogicException(
                'ليس لديك صلاحية إنشاء طلب عمل إضافي جماعي.'
            );
        }

        $employeeIds = collect($data['employee_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($employeeIds->isEmpty()) {
            throw new LogicException('يجب اختيار موظف واحد على الأقل.');
        }

        $employees = Employee::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('id', $employeeIds)
            ->orderBy('id')
            ->get();

        if ($employees->count() !== $employeeIds->count()) {
            throw new LogicException(
                'تتضمن القائمة موظفًا غير موجود أو تابعًا لشركة أخرى.'
            );
        }

        $invalidEmployees = $employees->reject(
            fn (Employee $employee) => in_array(
                $employee->employment_status,
                ['probation', 'active'],
                true
            )
        );

        if ($invalidEmployees->isNotEmpty()) {
            throw new LogicException(
                'لا تسمح حالة بعض الموظفين المحددين بطلب عمل إضافي.'
            );
        }

        $batchUuid = (string) Str::uuid();

        return DB::transaction(function () use (
            $tenant,
            $user,
            $data,
            $employees,
            $batchUuid
        ) {
            $created = collect();
            $skipped = collect();
            $batchSize = $employees->count();

            foreach ($employees as $employee) {
                try {
                    $created->push($this->createForEmployee(
                        $tenant,
                        $user,
                        $employee,
                        $data,
                        $batchUuid,
                        $batchSize
                    ));
                } catch (LogicException $exception) {
                    if (
                        $exception->getMessage()
                        !== self::DUPLICATE_REQUEST_MESSAGE
                    ) {
                        throw $exception;
                    }

                    $skipped->push([
                        'employee_id' => $employee->id,
                        'employee_number' => $employee->employee_number,
                        'employee_name' => $employee->full_name,
                        'reason' => $exception->getMessage(),
                    ]);
                }
            }

            if ($created->isEmpty()) {
                throw new LogicException(
                    'لم يتم إنشاء أي طلب؛ جميع الموظفين المحددين لديهم طلبات قائمة في التاريخ نفسه.'
                );
            }

            return [
                'batch_uuid' => $batchUuid,
                'created' => $created,
                'skipped' => $skipped,
            ];
        });
    }

    public function approve(
        OvertimeRequest $request,
        User $approver,
        int $approvedMinutes,
        ?string $notes = null
    ): OvertimeRequest {
        return DB::transaction(function () use (
            $request,
            $approver,
            $approvedMinutes,
            $notes
        ) {
            $request = OvertimeRequest::query()
                ->where('tenant_id', $approver->tenant_id)
                ->whereKey($request->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($request->status !== 'pending') {
                throw new LogicException(
                    'لا يمكن اعتماد طلب ليس بانتظار الاعتماد.'
                );
            }

            if ($approvedMinutes > $request->requested_minutes) {
                throw new LogicException(
                    'الدقائق المعتمدة لا يمكن أن تتجاوز الدقائق المطلوبة.'
                );
            }

            $metadata = $request->metadata ?? [];
            $metadata['approved_limit_minutes'] = $approvedMinutes;

            $request->forceFill([
                'status' => 'approved',
                'approved_minutes' => $approvedMinutes,
                'decision_notes' => $notes,
                'approved_at' => now(),
                'approved_by' => $approver->id,
                'rejected_at' => null,
                'metadata' => $metadata,
            ])->save();

            $this->syncLinkedAttendance($request);

            return $this->load($request->refresh());
        });
    }

    public function reject(
        OvertimeRequest $request,
        User $approver,
        string $notes
    ): OvertimeRequest {
        return DB::transaction(function () use (
            $request,
            $approver,
            $notes
        ) {
            $request = OvertimeRequest::query()
                ->where('tenant_id', $approver->tenant_id)
                ->whereKey($request->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($request->status !== 'pending') {
                throw new LogicException(
                    'لا يمكن رفض طلب ليس بانتظار الاعتماد.'
                );
            }

            $request->forceFill([
                'status' => 'rejected',
                'approved_minutes' => null,
                'decision_notes' => $notes,
                'rejected_at' => now(),
                'approved_at' => null,
                'approved_by' => $approver->id,
            ])->save();

            return $this->load($request);
        });
    }

    public function cancel(
        OvertimeRequest $request,
        User $user
    ): OvertimeRequest {
        return DB::transaction(function () use ($request, $user) {
            $request = OvertimeRequest::query()
                ->where('tenant_id', $user->tenant_id)
                ->whereKey($request->id)
                ->lockForUpdate()
                ->firstOrFail();

            $canManage = $user->can('attendance.manage');
            $isRequester = (int) $request->requested_by === (int) $user->id;

            if (!$canManage && !$isRequester) {
                throw new LogicException(
                    'لا يمكنك إلغاء طلب تابع لمستخدم آخر.'
                );
            }

            if (!in_array($request->status, ['pending', 'approved'], true)) {
                throw new LogicException(
                    'لا يمكن إلغاء الطلب في حالته الحالية.'
                );
            }

            if ($request->actual_minutes > 0) {
                throw new LogicException(
                    'بدأ تنفيذ العمل الإضافي فعليًا ولا يمكن إلغاء الطلب.'
                );
            }

            $request->forceFill([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'approved_minutes' => null,
            ])->save();

            if ($request->attendance_record_id) {
                AttendanceRecord::query()
                    ->where('tenant_id', $request->tenant_id)
                    ->whereKey($request->attendance_record_id)
                    ->update(['approved_overtime_minutes' => 0]);
            }

            return $this->load($request);
        });
    }

    public function syncWithAttendance(
        AttendanceRecord $record
    ): void {
        $request = OvertimeRequest::query()
            ->where('tenant_id', $record->tenant_id)
            ->where('employee_id', $record->employee_id)
            ->whereDate('overtime_date', $record->attendance_date)
            ->whereIn('status', ['approved', 'completed'])
            ->latest('approved_at')
            ->lockForUpdate()
            ->first();

        if (!$request) {
            $record->forceFill([
                'approved_overtime_minutes' => 0,
            ])->save();

            return;
        }

        $request->attendance_record_id = $record->id;
        $this->syncLinkedAttendance($request, $record);
    }

    private function syncLinkedAttendance(
        OvertimeRequest $request,
        ?AttendanceRecord $record = null
    ): void {
        $record ??= $request->attendanceRecord;

        if (!$record || !$record->check_out_at) {
            return;
        }

        $actualMinutes = (int) $record->overtime_minutes;
        $approvedLimit = (int) data_get(
            $request->metadata,
            'approved_limit_minutes',
            $request->approved_minutes ?? $request->requested_minutes
        );
        $payableMinutes = min($actualMinutes, $approvedLimit);

        $request->forceFill([
            'attendance_record_id' => $record->id,
            'actual_minutes' => $actualMinutes,
            'approved_minutes' => $payableMinutes,
            'status' => 'completed',
            'completed_at' => now(),
        ])->save();

        $metadata = $record->metadata ?? [];
        $metadata['overtime_request_id'] = $request->id;

        $record->forceFill([
            'approved_overtime_minutes' => $payableMinutes,
            'metadata' => $metadata,
        ])->save();

        if ($record->approval_status !== 'approved') {
            $record->loadMissing([
                'shift.policy',
                'workLocation',
            ]);

            if ($record->shift?->policy) {
                $this->approvalService->applyAutomaticDecision(
                    $record,
                    $record->shift->policy,
                    $record->workLocation
                );

                $record->save();
            }
        }
    }

    private function createForEmployee(
        Tenant $tenant,
        User $user,
        Employee $employee,
        array $data,
        ?string $batchUuid,
        int $batchSize
    ): OvertimeRequest {
        $timezone = $employee->timezone
            ?: $tenant->timezone
            ?: 'Asia/Riyadh';
        $date = Carbon::parse($data['overtime_date'], $timezone)
            ->toDateString();

        [$plannedStart, $plannedEnd, $requestedMinutes] =
            $this->plannedPeriod(
                $date,
                $data['start_time'],
                $data['end_time'],
                $timezone
            );

        $exists = OvertimeRequest::query()
            ->where('tenant_id', $tenant->id)
            ->where('employee_id', $employee->id)
            ->whereDate('overtime_date', $date)
            ->whereIn('status', [
                'pending',
                'approved',
                'completed',
            ])
            ->lockForUpdate()
            ->exists();

        if ($exists) {
            throw new LogicException(self::DUPLICATE_REQUEST_MESSAGE);
        }

        $attendance = AttendanceRecord::query()
            ->where('tenant_id', $tenant->id)
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $date)
            ->first();

        $metadata = [
            'created_channel' => 'web',
        ];

        if ($batchUuid) {
            $metadata['batch_uuid'] = $batchUuid;
            $metadata['batch_size'] = $batchSize;
        }

        $request = OvertimeRequest::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'attendance_record_id' => $attendance?->id,
            'overtime_date' => $date,
            'timezone' => $timezone,
            'planned_start_at' => $plannedStart->copy()->utc(),
            'planned_end_at' => $plannedEnd->copy()->utc(),
            'requested_minutes' => $requestedMinutes,
            'actual_minutes' => $attendance?->check_out_at
                ? (int) $attendance->overtime_minutes
                : 0,
            'approved_minutes' => null,
            'type' => $data['type'],
            'status' => 'pending',
            'reason' => $data['reason'],
            'requested_at' => now(),
            'requested_by' => $user->id,
            'created_by' => $user->id,
            'metadata' => $metadata,
        ]);

        return $this->load($request);
    }

    private function resolveEmployee(
        Tenant $tenant,
        User $user,
        array $data
    ): Employee {
        if (!empty($data['employee_id'])) {
            if (!$user->can('attendance.manage')) {
                throw new LogicException(
                    'لا يمكنك إنشاء طلب لموظف آخر.'
                );
            }

            $employee = Employee::query()
                ->where('tenant_id', $tenant->id)
                ->whereKey((int) $data['employee_id'])
                ->firstOrFail();
        } else {
            $employee = $this->deviceService->employeeForUser(
                $tenant,
                $user
            );
        }

        if (!in_array(
            $employee->employment_status,
            ['probation', 'active'],
            true
        )) {
            throw new LogicException(
                'حالة الموظف الحالية لا تسمح بطلب عمل إضافي.'
            );
        }

        return $employee;
    }

    private function plannedPeriod(
        string $date,
        string $startTime,
        string $endTime,
        string $timezone
    ): array {
        $start = Carbon::createFromFormat(
            'Y-m-d H:i',
            $date . ' ' . $startTime,
            $timezone
        );
        $end = Carbon::createFromFormat(
            'Y-m-d H:i',
            $date . ' ' . $endTime,
            $timezone
        );

        if ($end->lte($start)) {
            $end->addDay();
        }

        $minutes = (int) $start->diffInMinutes($end);

        if ($minutes < self::MINIMUM_MINUTES) {
            throw new LogicException(
                'الحد الأدنى لطلب العمل الإضافي 15 دقيقة.'
            );
        }

        if ($minutes > self::MAXIMUM_MINUTES) {
            throw new LogicException(
                'مدة العمل الإضافي لا يمكن أن تتجاوز 12 ساعة.'
            );
        }

        return [$start, $end, $minutes];
    }

    private function load(OvertimeRequest $request): OvertimeRequest
    {
        return $request->load([
            'employee:id,tenant_id,user_id,employee_number,department_id,job_title_id,first_name,father_name,grandfather_name,family_name',
            'employee.department:id,tenant_id,name',
            'employee.jobTitle:id,tenant_id,name',
            'attendanceRecord:id,tenant_id,employee_id,attendance_date,check_in_at,check_out_at,overtime_minutes,approved_overtime_minutes',
            'requestedBy:id,tenant_id,name',
            'approvedBy:id,tenant_id,name',
        ]);
    }
}
