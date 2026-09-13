<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\ApproveOvertimeRequest;
use App\Http\Requests\Tenant\BulkOvertimeDecisionRequest;
use App\Http\Requests\Tenant\RejectOvertimeRequest;
use App\Http\Requests\Tenant\StoreOvertimeRequest;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\Tenant;
use App\Services\HR\MobileDeviceService;
use App\Services\HR\OvertimeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use LogicException;

class OvertimeController extends Controller
{
    public function __construct(
        private readonly OvertimeService $overtimeService,
        private readonly MobileDeviceService $deviceService
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorizeView($request);

        return view('tenant.attendance.overtime', [
            'canViewAll' => $request->user()->can('attendance.view'),
            'canManage' => $request->user()->can('attendance.manage'),
            'canApprove' => $request->user()->can('attendance.approve'),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorizeView($request);

        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => [
                'nullable',
                'date',
                'after_or_equal:date_from',
            ],
            'status' => [
                'nullable',
                'in:pending,approved,rejected,cancelled,completed',
            ],
            'employee_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'between:10,100'],
        ]);

        $query = OvertimeRequest::query()
            ->with([
                'employee:id,tenant_id,user_id,employee_number,department_id,job_title_id,first_name,father_name,grandfather_name,family_name',
                'employee.department:id,tenant_id,name',
                'employee.jobTitle:id,tenant_id,name',
                'attendanceRecord:id,tenant_id,employee_id,attendance_date,check_in_at,check_out_at,overtime_minutes,approved_overtime_minutes',
                'requestedBy:id,tenant_id,name',
                'approvedBy:id,tenant_id,name',
            ])
            ->search($request->input('search'));

        if (!$request->user()->can('attendance.view')) {
            try {
                $employee = $this->deviceService->employeeForUser(
                    $this->currentTenant($request),
                    $request->user()
                );
            } catch (LogicException $exception) {
                return $this->logicError($exception);
            }

            $query->where('employee_id', $employee->id);
        } elseif ($request->filled('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate(
                'overtime_date',
                '>=',
                $request->input('date_from')
            );
        }

        if ($request->filled('date_to')) {
            $query->whereDate(
                'overtime_date',
                '<=',
                $request->input('date_to')
            );
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $summaryQuery = clone $query;
        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'pending' => (clone $summaryQuery)
                ->where('status', 'pending')
                ->count(),
            'approved' => (clone $summaryQuery)
                ->where('status', 'approved')
                ->count(),
            'completed' => (clone $summaryQuery)
                ->where('status', 'completed')
                ->count(),
            'actual_minutes' => (int) (clone $summaryQuery)
                ->sum('actual_minutes'),
            'approved_minutes' => (int) (clone $summaryQuery)
                ->sum('approved_minutes'),
        ];

        $requests = $query
            ->orderByDesc('overtime_date')
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 15));

        $requests->through(
            fn (OvertimeRequest $item) =>
                $this->payload($request, $item)
        );

        return response()->json([
            ...$requests->toArray(),
            'summary' => $summary,
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        $this->authorizeView($request);

        $employees = collect();
        $branches = collect();
        $departments = collect();

        if ($request->user()->can('attendance.manage')) {
            $employees = Employee::query()
                ->with([
                    'branch:id,tenant_id,name',
                    'department:id,tenant_id,name',
                ])
                ->where('tenant_id', $request->user()->tenant_id)
                ->whereIn('employment_status', ['probation', 'active'])
                ->orderBy('first_name')
                ->orderBy('family_name')
                ->get([
                    'id',
                    'employee_number',
                    'branch_id',
                    'department_id',
                    'first_name',
                    'father_name',
                    'grandfather_name',
                    'family_name',
                ])
                ->map(fn (Employee $employee) => [
                    'id' => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'name' => $employee->full_name,
                    'branch_id' => $employee->branch_id,
                    'branch_name' => $employee->branch?->name,
                    'department_id' => $employee->department_id,
                    'department_name' => $employee->department?->name,
                ]);

            $branches = $employees
                ->whereNotNull('branch_id')
                ->unique('branch_id')
                ->sortBy('branch_name')
                ->values()
                ->map(fn (array $employee) => [
                    'id' => $employee['branch_id'],
                    'name' => $employee['branch_name'] ?: 'فرع غير مسمى',
                ]);

            $departments = $employees
                ->whereNotNull('department_id')
                ->unique('department_id')
                ->sortBy('department_name')
                ->values()
                ->map(fn (array $employee) => [
                    'id' => $employee['department_id'],
                    'name' => $employee['department_name'] ?: 'إدارة غير مسماة',
                ]);
        }

        return response()->json([
            'success' => true,
            'options' => [
                'employees' => $employees,
                'branches' => $branches,
                'departments' => $departments,
                'today' => now(
                    $request->user()->tenant?->timezone ?: 'Asia/Riyadh'
                )->toDateString(),
            ],
        ]);
    }

    public function store(StoreOvertimeRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            if (!empty($data['employee_ids'])) {
                $result = $this->overtimeService->createMany(
                    $this->currentTenant($request),
                    $request->user(),
                    $data
                );

                $created = $result['created'];
                $skipped = $result['skipped'];
                $message = 'تم إنشاء ' . $created->count()
                    . ' طلب عمل إضافي وإرسالها للاعتماد.';

                if ($skipped->isNotEmpty()) {
                    $message .= ' تم تخطي ' . $skipped->count()
                        . ' موظف لوجود طلب قائم.';
                }

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'batch_uuid' => $result['batch_uuid'],
                    'created_count' => $created->count(),
                    'skipped_count' => $skipped->count(),
                    'skipped' => $skipped->values(),
                    'requests' => $created
                        ->map(fn (OvertimeRequest $item) =>
                            $this->payload($request, $item))
                        ->values(),
                ], 201);
            }

            $item = $this->overtimeService->create(
                $this->currentTenant($request),
                $request->user(),
                $data
            );
        } catch (LogicException $exception) {
            return $this->logicError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال طلب العمل الإضافي للاعتماد.',
            'request' => $this->payload($request, $item),
        ], 201);
    }

    public function show(
        Request $request,
        OvertimeRequest $overtimeRequest
    ): JsonResponse {
        $this->authorizeVisible($request, $overtimeRequest);

        $overtimeRequest->load([
            'employee.department',
            'employee.jobTitle',
            'attendanceRecord',
            'requestedBy',
            'approvedBy',
        ]);

        return response()->json([
            'success' => true,
            'request' => $this->payload($request, $overtimeRequest),
        ]);
    }

    public function bulkDecision(
        BulkOvertimeDecisionRequest $request
    ): JsonResponse {
        try {
            $result = $this->overtimeService->bulkDecision(
                $this->currentTenant($request),
                $request->user(),
                $request->validated()
            );
        } catch (LogicException $exception) {
            return $this->logicError($exception);
        }

        $processed = $result['processed'];
        $skipped = $result['skipped'];
        $message = $result['decision'] === 'approve'
            ? 'تم اعتماد ' . $processed->count() . ' طلب عمل إضافي.'
            : 'تم رفض ' . $processed->count() . ' طلب عمل إضافي.';

        if ($skipped->isNotEmpty()) {
            $message .= ' تم تخطي ' . $skipped->count()
                . ' طلب لتغير حالته.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'decision' => $result['decision'],
            'processed_count' => $processed->count(),
            'skipped_count' => $skipped->count(),
            'skipped' => $skipped->values(),
            'requests' => $processed
                ->map(fn (OvertimeRequest $item) =>
                    $this->payload($request, $item))
                ->values(),
        ]);
    }

    public function approve(
        ApproveOvertimeRequest $request,
        OvertimeRequest $overtimeRequest
    ): JsonResponse {
        $this->ensureSameTenant($request, $overtimeRequest);

        try {
            $item = $this->overtimeService->approve(
                $overtimeRequest,
                $request->user(),
                $request->integer('approved_minutes'),
                $request->validated('decision_notes')
            );
        } catch (LogicException $exception) {
            return $this->logicError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم اعتماد طلب العمل الإضافي.',
            'request' => $this->payload($request, $item),
        ]);
    }

    public function reject(
        RejectOvertimeRequest $request,
        OvertimeRequest $overtimeRequest
    ): JsonResponse {
        $this->ensureSameTenant($request, $overtimeRequest);

        try {
            $item = $this->overtimeService->reject(
                $overtimeRequest,
                $request->user(),
                $request->validated('decision_notes')
            );
        } catch (LogicException $exception) {
            return $this->logicError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم رفض طلب العمل الإضافي.',
            'request' => $this->payload($request, $item),
        ]);
    }

    public function cancel(
        Request $request,
        OvertimeRequest $overtimeRequest
    ): JsonResponse {
        $this->authorizeVisible($request, $overtimeRequest);

        try {
            $item = $this->overtimeService->cancel(
                $overtimeRequest,
                $request->user()
            );
        } catch (LogicException $exception) {
            return $this->logicError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء طلب العمل الإضافي.',
            'request' => $this->payload($request, $item),
        ]);
    }

    private function payload(
        Request $request,
        OvertimeRequest $item
    ): array {
        $employee = $item->employee;
        $canManage = $request->user()->can('attendance.manage');
        $isRequester = (int) $item->requested_by
            === (int) $request->user()->id;

        return [
            'id' => $item->id,
            'uuid' => $item->uuid,
            'batch_uuid' => data_get($item->metadata, 'batch_uuid'),
            'batch_size' => (int) data_get(
                $item->metadata,
                'batch_size',
                1
            ),
            'employee' => $employee ? [
                'id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'name' => $employee->full_name,
                'department' => $employee->department?->name,
                'job_title' => $employee->jobTitle?->name,
            ] : null,
            'overtime_date' => $item->overtime_date?->toDateString(),
            'timezone' => $item->timezone,
            'planned_start_at' => $this->localDateTime(
                $item->planned_start_at,
                $item->timezone
            ),
            'planned_end_at' => $this->localDateTime(
                $item->planned_end_at,
                $item->timezone
            ),
            'requested_minutes' => $item->requested_minutes,
            'actual_minutes' => $item->actual_minutes,
            'approved_minutes' => $item->approved_minutes,
            'authorized_minutes' => (int) data_get(
                $item->metadata,
                'approved_limit_minutes',
                $item->approved_minutes ?? 0
            ),
            'requested_duration_label' =>
                $item->requested_duration_label,
            'actual_duration_label' => $item->actual_duration_label,
            'approved_duration_label' =>
                $item->approved_duration_label,
            'type' => $item->type,
            'type_label' => $item->type_label,
            'status' => $item->status,
            'status_label' => $item->status_label,
            'reason' => $item->reason,
            'decision_notes' => $item->decision_notes,
            'requested_by' => $item->requestedBy?->name,
            'approved_by' => $item->approvedBy?->name,
            'requested_at' => $this->localDateTime(
                $item->requested_at,
                $item->timezone
            ),
            'approved_at' => $this->localDateTime(
                $item->approved_at,
                $item->timezone
            ),
            'attendance' => $item->attendanceRecord ? [
                'id' => $item->attendanceRecord->id,
                'check_in_at' => $this->localDateTime(
                    $item->attendanceRecord->check_in_at,
                    $item->timezone
                ),
                'check_out_at' => $this->localDateTime(
                    $item->attendanceRecord->check_out_at,
                    $item->timezone
                ),
                'overtime_minutes' =>
                    $item->attendanceRecord->overtime_minutes,
                'approved_overtime_minutes' =>
                    $item->attendanceRecord->approved_overtime_minutes,
            ] : null,
            'can_approve' =>
                $request->user()->can('attendance.approve')
                && $item->status === 'pending',
            'can_cancel' =>
                ($canManage || $isRequester)
                && in_array($item->status, ['pending', 'approved'], true)
                && $item->actual_minutes === 0,
        ];
    }

    private function localDateTime(
        mixed $value,
        ?string $timezone
    ): ?string {
        if (!$value) {
            return null;
        }

        return Carbon::parse($value)
            ->timezone($timezone ?: 'Asia/Riyadh')
            ->format('Y-m-d H:i');
    }

    private function authorizeView(Request $request): void
    {
        abort_unless(
            $request->user()?->can('attendance.view')
            || $request->user()?->can('self_service.attendance'),
            403,
            'ليس لديك صلاحية لعرض العمل الإضافي.'
        );
    }

    private function authorizeVisible(
        Request $request,
        OvertimeRequest $item
    ): void {
        $this->authorizeView($request);
        $this->ensureSameTenant($request, $item);

        if ($request->user()->can('attendance.view')) {
            return;
        }

        abort_unless(
            (int) $item->employee?->user_id === (int) $request->user()->id,
            404
        );
    }

    private function ensureSameTenant(
        Request $request,
        OvertimeRequest $item
    ): void {
        abort_unless(
            (int) $request->user()->tenant_id === (int) $item->tenant_id,
            404
        );
    }

    private function currentTenant(Request $request): Tenant
    {
        return Tenant::query()->findOrFail(
            $request->user()->tenant_id
        );
    }

    private function logicError(LogicException $exception): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $exception->getMessage(),
        ], 422);
    }
}
