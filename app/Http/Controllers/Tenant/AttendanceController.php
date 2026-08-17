<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreAttendanceRecordRequest;
use App\Http\Requests\Tenant\UpdateAttendanceRecordRequest;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\WorkLocation;
use App\Models\WorkShift;
use App\Services\HR\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use LogicException;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendanceService
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorizePermission($request, 'attendance.view');

        return view('tenant.attendance.index');
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'attendance.view');

        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => [
                'nullable',
                'date',
                'after_or_equal:date_from',
            ],
        ]);

        $perPage = min(
            max($request->integer('per_page', 15), 10),
            100
        );
        $from = $request->date('date_from')
            ?: today()->startOfMonth();
        $to = $request->date('date_to') ?: today();

        $query = AttendanceRecord::query()
            ->with([
                'employee:id,tenant_id,employee_number,department_id,job_title_id,first_name,father_name,grandfather_name,family_name',
                'employee.department:id,tenant_id,name',
                'employee.jobTitle:id,tenant_id,name',
                'shift:id,tenant_id,code,name,start_time,end_time',
                'workLocation:id,tenant_id,code,name',
                'approvedBy:id,tenant_id,name',
            ])
            ->whereBetween('attendance_date', [
                $from->toDateString(),
                $to->toDateString(),
            ])
            ->search($request->input('search'));

        if ($request->filled('employee_id')) {
            $query->where(
                'employee_id',
                $request->integer('employee_id')
            );
        }

        if ($request->filled('work_shift_id')) {
            $query->where(
                'work_shift_id',
                $request->integer('work_shift_id')
            );
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('approval_status')) {
            $query->where(
                'approval_status',
                $request->input('approval_status')
            );
        }

        $summary = $this->summaryPayload(clone $query);

        $records = $query
            ->orderByDesc('attendance_date')
            ->orderByDesc('check_in_at')
            ->paginate($perPage);

        $records->through(
            fn (AttendanceRecord $record) =>
                $this->listPayload($record)
        );

        return response()->json([
            ...$records->toArray(),
            'summary' => $summary,
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'attendance.view');

        $employees = Employee::query()
            ->whereNotIn('employment_status', ['terminated'])
            ->orderBy('first_name')
            ->orderBy('family_name')
            ->get([
                'id',
                'employee_number',
                'first_name',
                'father_name',
                'grandfather_name',
                'family_name',
            ])
            ->map(fn (Employee $employee) => [
                'id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'name' => $employee->full_name,
            ]);

        $shifts = WorkShift::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('start_time')
            ->get([
                'id',
                'code',
                'name',
                'start_time',
                'end_time',
            ])
            ->map(fn (WorkShift $shift) => [
                'id' => $shift->id,
                'code' => $shift->code,
                'name' => $shift->name,
                'time_range' => $shift->time_range,
            ]);

        $locations = WorkLocation::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'code', 'name'])
            ->map(fn (WorkLocation $location) => [
                'id' => $location->id,
                'code' => $location->code,
                'name' => $location->name,
            ]);

        return response()->json([
            'success' => true,
            'options' => [
                'employees' => $employees,
                'shifts' => $shifts,
                'locations' => $locations,
                'today' => today()->toDateString(),
            ],
        ]);
    }

    public function store(
        StoreAttendanceRecordRequest $request
    ): JsonResponse {
        try {
            $record = $this->attendanceService->createManualRecord(
                $this->currentTenant($request),
                $request->user(),
                $request->validated()
            );
        } catch (LogicException $exception) {
            return $this->logicError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء سجل الحضور بنجاح.',
            'record' => $this->detailsPayload($record),
        ], 201);
    }

    public function show(
        Request $request,
        AttendanceRecord $record
    ): JsonResponse {
        $this->authorizePermission($request, 'attendance.view');
        $this->ensureSameTenant($request, $record);

        return response()->json([
            'success' => true,
            'record' => $this->detailsPayload($record),
        ]);
    }

    public function update(
        UpdateAttendanceRecordRequest $request,
        AttendanceRecord $record
    ): JsonResponse {
        try {
            $record = $this->attendanceService->updateManualRecord(
                $record,
                $request->user(),
                $request->validated()
            );
        } catch (LogicException $exception) {
            return $this->logicError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث سجل الحضور بنجاح.',
            'record' => $this->detailsPayload($record),
        ]);
    }

    public function approve(
        Request $request,
        AttendanceRecord $record
    ): JsonResponse {
        $this->authorizePermission($request, 'attendance.approve');

        try {
            $record = $this->attendanceService->approve(
                $record,
                $request->user()
            );
        } catch (LogicException $exception) {
            return $this->logicError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم اعتماد سجل الحضور.',
            'record' => $this->detailsPayload($record),
        ]);
    }

    public function reopen(
        Request $request,
        AttendanceRecord $record
    ): JsonResponse {
        $this->authorizePermission($request, 'attendance.approve');

        try {
            $record = $this->attendanceService->reopen(
                $record,
                $request->user()
            );
        } catch (LogicException $exception) {
            return $this->logicError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء اعتماد سجل الحضور.',
            'record' => $this->detailsPayload($record),
        ]);
    }

    public function destroy(
        Request $request,
        AttendanceRecord $record
    ): JsonResponse {
        $this->authorizePermission($request, 'attendance.manage');

        try {
            $this->attendanceService->archive(
                $record,
                $request->user()
            );
        } catch (LogicException $exception) {
            return $this->logicError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'تمت أرشفة سجل الحضور.',
        ]);
    }

    private function summaryPayload($query): array
    {
        return [
            'total' => (clone $query)->count(),
            'present' => (clone $query)
                ->whereIn('status', ['present', 'late', 'remote'])
                ->count(),
            'late' => (clone $query)
                ->where('status', 'late')
                ->count(),
            'absent' => (clone $query)
                ->where('status', 'absent')
                ->count(),
            'incomplete' => (clone $query)
                ->where('status', 'incomplete')
                ->count(),
            'pending' => (clone $query)
                ->where('approval_status', 'pending')
                ->count(),
            'work_minutes' => (int) (clone $query)->sum('work_minutes'),
            'overtime_minutes' =>
                (int) (clone $query)->sum('overtime_minutes'),
        ];
    }

    private function listPayload(AttendanceRecord $record): array
    {
        return [
            'id' => $record->id,
            'uuid' => $record->uuid,
            'employee' => $record->employee
                ? [
                    'id' => $record->employee->id,
                    'employee_number' =>
                        $record->employee->employee_number,
                    'name' => $record->employee->full_name,
                    'department' =>
                        $record->employee->department?->name,
                    'job_title' =>
                        $record->employee->jobTitle?->name,
                ]
                : null,
            'shift' => $record->shift,
            'work_location' => $record->workLocation,
            'attendance_date' =>
                $record->attendance_date?->toDateString(),
            'check_in_at' => $this->localDateTime($record, 'check_in_at'),
            'check_out_at' => $this->localDateTime($record, 'check_out_at'),
            'status' => $record->status,
            'status_label' => $record->status_label,
            'work_minutes' => $record->work_minutes,
            'work_duration_label' => $record->work_duration_label,
            'late_minutes' => $record->late_minutes,
            'early_leave_minutes' => $record->early_leave_minutes,
            'overtime_minutes' => $record->overtime_minutes,
            'approval_status' => $record->approval_status,
            'approval_status_label' =>
                $record->approval_status_label,
            'approved_by' => $record->approvedBy?->name,
            'notes' => $record->notes,
        ];
    }

    private function detailsPayload(AttendanceRecord $record): array
    {
        $record->load([
            'employee:id,tenant_id,employee_number,department_id,job_title_id,first_name,father_name,grandfather_name,family_name',
            'employee.department:id,tenant_id,name',
            'employee.jobTitle:id,tenant_id,name',
            'shift:id,tenant_id,code,name,start_time,end_time',
            'workLocation:id,tenant_id,code,name',
            'approvedBy:id,tenant_id,name',
            'createdBy:id,tenant_id,name',
        ]);

        return [
            ...$record->toArray(),
            'scheduled_check_in_local' =>
                $this->localDateTime($record, 'scheduled_check_in_at'),
            'scheduled_check_out_local' =>
                $this->localDateTime($record, 'scheduled_check_out_at'),
            'check_in_local' =>
                $this->localDateTime($record, 'check_in_at'),
            'check_out_local' =>
                $this->localDateTime($record, 'check_out_at'),
        ];
    }

    private function localDateTime(
        AttendanceRecord $record,
        string $field
    ): ?string {
        $value = $record->{$field};

        if (!$value) {
            return null;
        }

        return Carbon::parse($value)
            ->timezone($record->timezone ?: 'Asia/Riyadh')
            ->format('Y-m-d H:i');
    }

    private function currentTenant(Request $request): Tenant
    {
        return Tenant::query()->findOrFail(
            $request->user()->tenant_id
        );
    }

    private function ensureSameTenant(
        Request $request,
        AttendanceRecord $record
    ): void {
        abort_unless(
            (int) $request->user()->tenant_id
                === (int) $record->tenant_id,
            404
        );
    }

    private function authorizePermission(
        Request $request,
        string $permission
    ): void {
        abort_unless(
            $request->user()?->can($permission),
            403,
            'ليس لديك صلاحية لتنفيذ هذا الإجراء.'
        );
    }

    private function logicError(
        LogicException $exception
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $exception->getMessage(),
        ], 422);
    }
}
