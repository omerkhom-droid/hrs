<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\MobileStoreLeaveRequest;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Tenant;
use App\Services\HR\LeaveBalanceService;
use App\Services\HR\LeaveRequestService;
use App\Services\HR\MobileDeviceService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class MobileLeaveController extends Controller
{
    public function __construct(
        private readonly LeaveRequestService $leaveService,
        private readonly LeaveBalanceService $balanceService,
        private readonly MobileDeviceService $deviceService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $this->ensurePermission($request);
        $employee = $this->currentEmployee($request);

        $validated = $request->validate([
            'status' => [
                'nullable',
                'in:draft,pending,approved,rejected,cancelled',
            ],
            'per_page' => [
                'nullable',
                'integer',
                'between:10,50',
            ],
        ]);

        $query = LeaveRequest::query()
            ->where('tenant_id', $employee->tenant_id)
            ->forEmployee($employee->id)
            ->with([
                'leaveType:id,code,name,name_en,unit',
                'replacementEmployee:id,employee_number,first_name,father_name,grandfather_name,family_name',
                'approver:id,name',
            ])
            ->latest('id');

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $records = $query->paginate(
            (int) ($validated['per_page'] ?? 15)
        );

        return response()->json([
            'success' => true,
            'data' => array_map(
                fn (LeaveRequest $item) => $this->requestPayload($item),
                $records->items()
            ),
            'meta' => [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
                'has_more_pages' => $records->hasMorePages(),
            ],
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        $this->ensurePermission($request);
        $employee = $this->currentEmployee($request);

        $types = LeaveType::query()
            ->where('tenant_id', $employee->tenant_id)
            ->active()
            ->forGender($employee->gender)
            ->ordered()
            ->get()
            ->map(function (LeaveType $leaveType) use ($employee) {
                $balance = null;

                if ($leaveType->requires_balance) {
                    $balance = $this->balanceService->getOrCreate(
                        employee: $employee,
                        leaveType: $leaveType,
                        year: now()->year
                    );
                }

                return $this->leaveTypePayload(
                    $leaveType,
                    $balance
                );
            })
            ->values();

        $replacementEmployees = Employee::query()
            ->where('tenant_id', $employee->tenant_id)
            ->whereKeyNot($employee->id)
            ->where('employment_status', 'active')
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
            ->map(fn (Employee $item) => [
                'id' => $item->id,
                'employee_number' => $item->employee_number,
                'name' => $this->employeeName($item),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'leave_types' => $types,
            'replacement_employees' => $replacementEmployees,
        ]);
    }

    public function balances(Request $request): JsonResponse
    {
        $this->ensurePermission($request);
        $employee = $this->currentEmployee($request);

        $year = $request->validate([
            'year' => ['nullable', 'integer', 'between:2000,2100'],
        ])['year'] ?? now()->year;

        $balances = LeaveBalance::query()
            ->where('tenant_id', $employee->tenant_id)
            ->forEmployee($employee->id)
            ->forYear((int) $year)
            ->with('leaveType:id,code,name,name_en,unit')
            ->orderBy('leave_type_id')
            ->get()
            ->map(fn (LeaveBalance $balance) => [
                'id' => $balance->id,
                'year' => $balance->year,
                'leave_type' => $balance->leaveType
                    ? [
                        'id' => $balance->leaveType->id,
                        'code' => $balance->leaveType->code,
                        'name' => $balance->leaveType->display_name,
                        'unit' => $balance->leaveType->unit,
                    ]
                    : null,
                'opening_balance' => (float) $balance->opening_balance,
                'accrued_balance' => (float) $balance->accrued_balance,
                'carried_forward' => (float) $balance->carried_forward,
                'adjustment_balance' => (float) $balance->adjustment_balance,
                'used_balance' => (float) $balance->used_balance,
                'pending_balance' => (float) $balance->pending_balance,
                'available_balance' => (float) $balance->available_balance,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'year' => (int) $year,
            'data' => $balances,
        ]);
    }

    public function store(MobileStoreLeaveRequest $request): JsonResponse
    {
        $this->ensurePermission($request);
        $employee = $this->currentEmployee($request);
        $data = $request->validated();
        $data['employee_id'] = $employee->id;
        $attachmentPath = null;

        try {
            $leaveType = $this->findLeaveType(
                (int) $data['leave_type_id'],
                $employee
            );

            if ($request->hasFile('attachment')) {
                $attachmentPath = $this->storeAttachment(
                    $request,
                    $employee
                );
                $data['attachment_path'] = $attachmentPath;
            }

            $leaveRequest = $this->leaveService->createDraft(
                employee: $employee,
                leaveType: $leaveType,
                data: $data,
                actor: $request->user()
            );

            if ($request->boolean('submit_now')) {
                $leaveRequest = $this->leaveService->submit(
                    $leaveRequest,
                    $request->user()
                );
            }

            return response()->json([
                'success' => true,
                'message' => $request->boolean('submit_now')
                    ? 'تم إرسال طلب الإجازة للاعتماد.'
                    : 'تم حفظ طلب الإجازة كمسودة.',
                'leave_request' => $this->requestPayload($leaveRequest),
            ], 201);
        } catch (DomainException $exception) {
            $this->deleteAttachment($attachmentPath);
            return $this->domainError($exception);
        } catch (Throwable $exception) {
            $this->deleteAttachment($attachmentPath);
            throw $exception;
        }
    }

    public function show(
        Request $request,
        LeaveRequest $leaveRequest
    ): JsonResponse {
        $this->ensurePermission($request);
        $this->ensureOwnedRequest($request, $leaveRequest);

        $leaveRequest->load([
            'leaveType',
            'replacementEmployee',
            'days',
            'requester:id,name',
            'approver:id,name',
            'canceller:id,name',
        ]);

        return response()->json([
            'success' => true,
            'leave_request' => $this->requestPayload(
                $leaveRequest,
                true
            ),
        ]);
    }

    public function update(
        MobileStoreLeaveRequest $request,
        LeaveRequest $leaveRequest
    ): JsonResponse {
        $this->ensurePermission($request);
        $this->ensureOwnedRequest($request, $leaveRequest);

        $employee = $this->currentEmployee($request);
        $data = $request->validated();
        $data['employee_id'] = $employee->id;
        $newAttachmentPath = null;
        $oldAttachmentPath = $leaveRequest->attachment_path;

        try {
            $leaveType = $this->findLeaveType(
                (int) $data['leave_type_id'],
                $employee
            );

            if ($request->hasFile('attachment')) {
                $newAttachmentPath = $this->storeAttachment(
                    $request,
                    $employee
                );
                $data['attachment_path'] = $newAttachmentPath;
            }

            $leaveRequest = $this->leaveService->updateDraft(
                request: $leaveRequest,
                leaveType: $leaveType,
                data: $data,
                actor: $request->user()
            );

            if ($newAttachmentPath) {
                $this->deleteAttachment($oldAttachmentPath);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث طلب الإجازة.',
                'leave_request' => $this->requestPayload($leaveRequest),
            ]);
        } catch (DomainException $exception) {
            $this->deleteAttachment($newAttachmentPath);
            return $this->domainError($exception);
        } catch (Throwable $exception) {
            $this->deleteAttachment($newAttachmentPath);
            throw $exception;
        }
    }

    public function submit(
        Request $request,
        LeaveRequest $leaveRequest
    ): JsonResponse {
        $this->ensurePermission($request);
        $this->ensureOwnedRequest($request, $leaveRequest);

        try {
            $leaveRequest = $this->leaveService->submit(
                $leaveRequest,
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال طلب الإجازة للاعتماد.',
                'leave_request' => $this->requestPayload($leaveRequest),
            ]);
        } catch (DomainException $exception) {
            return $this->domainError($exception);
        }
    }

    public function cancel(
        Request $request,
        LeaveRequest $leaveRequest
    ): JsonResponse {
        $this->ensurePermission($request);
        $this->ensureOwnedRequest($request, $leaveRequest);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
        ], [
            'reason.required' => 'سبب الإلغاء مطلوب.',
        ]);

        try {
            $leaveRequest = $this->leaveService->cancel(
                request: $leaveRequest,
                actor: $request->user(),
                notes: $validated['reason']
            );

            return response()->json([
                'success' => true,
                'message' => 'تم إلغاء طلب الإجازة.',
                'leave_request' => $this->requestPayload($leaveRequest),
            ]);
        } catch (DomainException $exception) {
            return $this->domainError($exception);
        }
    }

    public function destroy(
        Request $request,
        LeaveRequest $leaveRequest
    ): JsonResponse {
        $this->ensurePermission($request);
        $this->ensureOwnedRequest($request, $leaveRequest);

        if (!$leaveRequest->isDraft()) {
            return response()->json([
                'success' => false,
                'message' => 'يمكن حذف المسودات فقط.',
            ], 422);
        }

        $attachmentPath = $leaveRequest->attachment_path;
        $leaveRequest->delete();
        $this->deleteAttachment($attachmentPath);

        return response()->json([
            'success' => true,
            'message' => 'تم حذف مسودة الإجازة.',
        ]);
    }

    public function attachment(
        Request $request,
        LeaveRequest $leaveRequest
    ): StreamedResponse|JsonResponse {
        $this->ensurePermission($request);
        $this->ensureOwnedRequest($request, $leaveRequest);

        $path = $leaveRequest->attachment_path;
        $disk = config('hr.leave.attachments.disk', 'public');

        if (!$path || !Storage::disk($disk)->exists($path)) {
            return response()->json([
                'success' => false,
                'message' => 'المرفق غير موجود.',
            ], 404);
        }

        return Storage::disk($disk)->response(
            $path,
            basename($path),
            ['Content-Disposition' => 'inline']
        );
    }

    private function currentTenant(Request $request): Tenant
    {
        return Tenant::query()->findOrFail(
            $request->user()->tenant_id
        );
    }

    private function currentEmployee(Request $request): Employee
    {
        return $this->deviceService->employeeForUser(
            $this->currentTenant($request),
            $request->user()
        );
    }

    private function findLeaveType(
        int $leaveTypeId,
        Employee $employee
    ): LeaveType {
        return LeaveType::query()
            ->where('tenant_id', $employee->tenant_id)
            ->active()
            ->forGender($employee->gender)
            ->findOrFail($leaveTypeId);
    }

    private function ensureOwnedRequest(
        Request $request,
        LeaveRequest $leaveRequest
    ): void {
        $employee = $this->currentEmployee($request);

        abort_unless(
            (int) $leaveRequest->tenant_id === (int) $employee->tenant_id &&
            (int) $leaveRequest->employee_id === (int) $employee->id,
            404
        );
    }

    private function storeAttachment(
        MobileStoreLeaveRequest $request,
        Employee $employee
    ): string {
        $disk = config('hr.leave.attachments.disk', 'public');
        $directory = "tenants/{$employee->tenant_id}" .
            "/employees/{$employee->id}/leave-requests";

        return $request->file('attachment')->store(
            $directory,
            $disk
        );
    }

    private function deleteAttachment(?string $path): void
    {
        if ($path) {
            Storage::disk(
                config('hr.leave.attachments.disk', 'public')
            )->delete($path);
        }
    }

    private function requestPayload(
        LeaveRequest $item,
        bool $includeDays = false
    ): array {
        $item->loadMissing([
            'leaveType',
            'replacementEmployee',
            'approver:id,name',
        ]);

        $payload = [
            'id' => $item->id,
            'uuid' => $item->uuid,
            'leave_type' => $item->leaveType
                ? [
                    'id' => $item->leaveType->id,
                    'code' => $item->leaveType->code,
                    'name' => $item->leaveType->display_name,
                    'unit' => $item->leaveType->unit,
                ]
                : null,
            'replacement_employee' => $item->replacementEmployee
                ? [
                    'id' => $item->replacementEmployee->id,
                    'name' => $this->employeeName(
                        $item->replacementEmployee
                    ),
                ]
                : null,
            'start_date' => $item->start_date?->format('Y-m-d'),
            'end_date' => $item->end_date?->format('Y-m-d'),
            'return_date' => $item->return_date?->format('Y-m-d'),
            'start_session' => $item->start_session,
            'end_session' => $item->end_session,
            'requested_amount' => (float) $item->requested_amount,
            'approved_amount' => (float) $item->approved_amount,
            'duration_label' => $item->duration_label,
            'status' => $item->status,
            'status_label' => $item->status_label,
            'status_color' => $item->status_color,
            'reason' => $item->reason,
            'handover_notes' => $item->handover_notes,
            'contact_during_leave' => $item->contact_during_leave,
            'decision_notes' => $item->decision_notes,
            'requested_at' => $item->requested_at?->toIso8601String(),
            'approved_at' => $item->approved_at?->toIso8601String(),
            'cancelled_at' => $item->cancelled_at?->toIso8601String(),
            'approver' => $item->approver
                ? [
                    'id' => $item->approver->id,
                    'name' => $item->approver->name,
                ]
                : null,
            'can_edit' => $item->isDraft(),
            'can_submit' => $item->canBeSubmitted(),
            'can_cancel' => $item->canBeCancelled(),
            'has_attachment' => filled($item->attachment_path),
        ];

        if ($includeDays) {
            $item->loadMissing('days');
            $payload['days'] = $item->days->map(fn ($day) => [
                'date' => $day->leave_date?->format('Y-m-d'),
                'amount' => (float) $day->amount,
                'is_working_day' => (bool) $day->is_working_day,
                'is_paid' => (bool) $day->is_paid,
            ])->values();
        }

        return $payload;
    }

    private function leaveTypePayload(
        LeaveType $leaveType,
        ?LeaveBalance $balance
    ): array {
        return [
            'id' => $leaveType->id,
            'code' => $leaveType->code,
            'name' => $leaveType->display_name,
            'unit' => $leaveType->unit,
            'unit_label' => $leaveType->unit_label,
            'payment_type' => $leaveType->payment_type,
            'payment_type_label' => $leaveType->payment_type_label,
            'requires_balance' => (bool) $leaveType->requires_balance,
            'allow_half_day' => (bool) $leaveType->allow_half_day,
            'requires_attachment' => (bool) $leaveType->requires_attachment,
            'minimum_notice_days' => (int) $leaveType->minimum_notice_days,
            'maximum_consecutive_days' => $leaveType->maximum_consecutive_days
                ? (int) $leaveType->maximum_consecutive_days
                : null,
            'available_balance' => $balance
                ? (float) $balance->available_balance
                : null,
        ];
    }

    private function employeeName(Employee $employee): string
    {
        return collect([
            $employee->first_name,
            $employee->father_name,
            $employee->grandfather_name,
            $employee->family_name,
        ])->filter()->implode(' ');
    }

    private function ensurePermission(Request $request): void
    {
        abort_unless(
            $request->user()?->can('self_service.leave'),
            403,
            'ليس لديك صلاحية استخدام خدمة الإجازات.'
        );
    }

    private function domainError(
        DomainException $exception
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $exception->getMessage(),
        ], 422);
    }
}
