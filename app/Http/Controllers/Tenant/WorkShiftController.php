<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\AssignEmployeeShiftRequest;
use App\Http\Requests\Tenant\StoreWorkShiftRequest;
use App\Http\Requests\Tenant\UpdateAttendancePolicyRequest;
use App\Http\Requests\Tenant\UpdateWorkShiftRequest;
use App\Models\AttendancePolicy;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\Tenant;
use App\Models\WorkShift;
use App\Services\HR\WorkShiftService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use LogicException;

class WorkShiftController extends Controller
{
    public function __construct(
        private readonly WorkShiftService $shiftService
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorizePermission($request, 'attendance.view');

        return view('tenant.attendance.shifts');
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'attendance.view');

        $perPage = min(
            max($request->integer('per_page', 15), 10),
            100
        );
        $search = trim((string) $request->input('search', ''));

        $query = WorkShift::query()
            ->with('policy:id,tenant_id,name,timezone')
            ->withCount([
                'assignments as active_assignments_count' =>
                    fn (Builder $query) => $query->effectiveOn(today()),
            ]);

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search) {
                $query
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%");
            });
        }

        if ($request->filled('shift_type')) {
            $query->where(
                'shift_type',
                $request->input('shift_type')
            );
        }

        if ($request->filled('is_active')) {
            $query->where(
                'is_active',
                $request->boolean('is_active')
            );
        }

        $shifts = $query
            ->orderByDesc('is_default')
            ->orderBy('start_time')
            ->paginate($perPage);

        $shifts->through(fn (WorkShift $shift) => [
            'id' => $shift->id,
            'uuid' => $shift->uuid,
            'code' => $shift->code,
            'name' => $shift->name,
            'name_en' => $shift->name_en,
            'shift_type' => $shift->shift_type,
            'shift_type_label' => $shift->shift_type_label,
            'start_time' => substr($shift->start_time, 0, 5),
            'end_time' => substr($shift->end_time, 0, 5),
            'time_range' => $shift->time_range,
            'crosses_midnight' => $shift->crosses_midnight,
            'break_minutes' => $shift->break_minutes,
            'working_minutes' => $shift->working_minutes,
            'work_days' => $shift->work_days ?: [],
            'is_default' => $shift->is_default,
            'is_active' => $shift->is_active,
            'policy' => $shift->policy,
            'active_assignments_count' =>
                $shift->active_assignments_count,
        ]);

        return response()->json($shifts);
    }

    public function assignmentsData(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'attendance.view');

        $perPage = min(
            max($request->integer('per_page', 15), 10),
            100
        );

        $query = EmployeeShiftAssignment::query()
            ->with([
                'employee:id,tenant_id,employee_number,department_id,first_name,father_name,grandfather_name,family_name',
                'employee.department:id,tenant_id,name',
                'shift:id,tenant_id,code,name,start_time,end_time',
            ]);

        $search = trim((string) $request->input('search', ''));

        if ($search !== '') {
            $query->whereHas(
                'employee',
                fn (Builder $query) => $query->search($search)
            );
        }

        if ($request->input('period', 'current') === 'current') {
            $query->effectiveOn(today());
        }

        if ($request->filled('work_shift_id')) {
            $query->where(
                'work_shift_id',
                $request->integer('work_shift_id')
            );
        }

        $assignments = $query
            ->orderByDesc('effective_from')
            ->paginate($perPage);

        $assignments->through(
            fn (EmployeeShiftAssignment $assignment) => [
                'id' => $assignment->id,
                'employee' => $assignment->employee
                    ? [
                        'id' => $assignment->employee->id,
                        'employee_number' =>
                            $assignment->employee->employee_number,
                        'name' => $assignment->employee->full_name,
                        'department' =>
                            $assignment->employee->department?->name,
                    ]
                    : null,
                'shift' => $assignment->shift,
                'effective_from' =>
                    $assignment->effective_from?->toDateString(),
                'effective_to' =>
                    $assignment->effective_to?->toDateString(),
                'is_primary' => $assignment->is_primary,
                'notes' => $assignment->notes,
                'is_current' =>
                    $assignment->effective_from?->lte(today())
                    && (
                        !$assignment->effective_to
                        || $assignment->effective_to->gte(today())
                    ),
            ]
        );

        return response()->json($assignments);
    }

    public function options(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'attendance.view');

        $tenant = $this->currentTenant($request);
        $policy = $this->shiftService->ensureDefaultPolicy($tenant);

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

        return response()->json([
            'success' => true,
            'options' => [
                'employees' => $employees,
                'shifts' => $shifts,
                'policy' => $policy,
                'timezones' => [
                    ['value' => 'Asia/Riyadh', 'label' => 'الرياض'],
                    ['value' => 'Asia/Dubai', 'label' => 'دبي'],
                    ['value' => 'Asia/Kuwait', 'label' => 'الكويت'],
                    ['value' => 'Asia/Bahrain', 'label' => 'البحرين'],
                    ['value' => 'Asia/Qatar', 'label' => 'قطر'],
                    ['value' => 'Asia/Muscat', 'label' => 'مسقط'],
                    ['value' => 'Africa/Cairo', 'label' => 'القاهرة'],
                ],
            ],
        ]);
    }

    public function store(
        StoreWorkShiftRequest $request
    ): JsonResponse {
        try {
            $shift = $this->shiftService->createShift(
                $this->currentTenant($request),
                $request->user(),
                $request->validated()
            );
        } catch (LogicException $exception) {
            return $this->logicError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الوردية بنجاح.',
            'shift' => $shift,
        ], 201);
    }

    public function show(
        Request $request,
        WorkShift $shift
    ): JsonResponse {
        $this->authorizePermission($request, 'attendance.view');
        $this->ensureSameTenant($request, $shift->tenant_id);

        return response()->json([
            'success' => true,
            'shift' => $shift->load('policy'),
        ]);
    }

    public function update(
        UpdateWorkShiftRequest $request,
        WorkShift $shift
    ): JsonResponse {
        try {
            $shift = $this->shiftService->updateShift(
                $shift,
                $request->user(),
                $request->validated()
            );
        } catch (LogicException $exception) {
            return $this->logicError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الوردية بنجاح.',
            'shift' => $shift,
        ]);
    }

    public function destroy(
        Request $request,
        WorkShift $shift
    ): JsonResponse {
        $this->authorizePermission($request, 'attendance.manage');

        try {
            $this->shiftService->archiveShift(
                $shift,
                $request->user()
            );
        } catch (LogicException $exception) {
            return $this->logicError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'تمت أرشفة الوردية بنجاح.',
        ]);
    }

    public function updatePolicy(
        UpdateAttendancePolicyRequest $request
    ): JsonResponse {
        $policy = $this->shiftService->updateDefaultPolicy(
            $this->currentTenant($request),
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث سياسة الحضور بنجاح.',
            'policy' => $policy,
        ]);
    }

    public function assign(
        AssignEmployeeShiftRequest $request
    ): JsonResponse {
        try {
            $assignment = $this->shiftService->assignEmployee(
                $this->currentTenant($request),
                $request->user(),
                $request->validated()
            );
        } catch (LogicException $exception) {
            return $this->logicError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تكليف الموظف بالوردية بنجاح.',
            'assignment' => $assignment,
        ], 201);
    }

    public function endAssignment(
        Request $request,
        EmployeeShiftAssignment $assignment
    ): JsonResponse {
        $this->authorizePermission($request, 'attendance.manage');

        $request->validate([
            'effective_to' => ['nullable', 'date'],
        ]);

        try {
            $assignment = $this->shiftService->endAssignment(
                $assignment,
                $request->user(),
                $request->input('effective_to')
            );
        } catch (LogicException $exception) {
            return $this->logicError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إنهاء تكليف الوردية.',
            'assignment' => $assignment,
        ]);
    }

    private function currentTenant(Request $request): Tenant
    {
        return Tenant::query()->findOrFail(
            $request->user()->tenant_id
        );
    }

    private function ensureSameTenant(
        Request $request,
        int $tenantId
    ): void {
        abort_unless(
            (int) $request->user()->tenant_id === $tenantId,
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
