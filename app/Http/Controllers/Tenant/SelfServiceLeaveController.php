<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreLeaveRequest;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\HR\LeaveBalanceService;
use App\Services\HR\LeaveRequestService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SelfServiceLeaveController extends Controller
{
    public function __construct(
        private readonly LeaveRequestService $leaveService,
        private readonly LeaveBalanceService $balanceService
    ) {
    }

    public function index(): View
    {
        $this->ensurePermission();

        $employee =
            $this->currentEmployee();

        $summary = [
            'total' =>
                $employee
                    ? LeaveRequest::query()
                        ->forEmployee(
                            $employee->id
                        )
                        ->count()
                    : 0,

            'pending' =>
                $employee
                    ? LeaveRequest::query()
                        ->forEmployee(
                            $employee->id
                        )
                        ->pending()
                        ->count()
                    : 0,

            'approved' =>
                $employee
                    ? LeaveRequest::query()
                        ->forEmployee(
                            $employee->id
                        )
                        ->approved()
                        ->count()
                    : 0,

            'current_year_days' =>
                $employee
                    ? (float) LeaveRequest::query()
                        ->forEmployee(
                            $employee->id
                        )
                        ->approved()
                        ->whereYear(
                            'start_date',
                            now()->year
                        )
                        ->sum(
                            'approved_amount'
                        )
                    : 0,
        ];

        return view(
            'tenant.self-service.leave.index',
            compact(
                'employee',
                'summary'
            )
        );
    }

    public function data(): JsonResponse
    {
        $this->ensurePermission();

        $employee =
            $this->requireCurrentEmployee();

        $requests =
            LeaveRequest::query()
                ->forEmployee(
                    $employee->id
                )
                ->with([
                    'leaveType:id,code,name,name_en,unit',
                    'replacementEmployee:id,employee_number,first_name,father_name,grandfather_name,family_name',
                    'approver:id,name',
                ])
                ->latest('id')
                ->paginate(15);

        $requests->through(
            fn (LeaveRequest $item) => [
                'id' =>
                    $item->id,

                'uuid' =>
                    $item->uuid,

                'leave_type' => [
                    'id' =>
                        $item->leaveType?->id,

                    'code' =>
                        $item->leaveType?->code,

                    'name' =>
                        $item->leaveType
                            ?->display_name,

                    'unit' =>
                        $item->leaveType?->unit,
                ],

                'replacement_employee' =>
                    $item->replacementEmployee
                        ? [
                            'id' =>
                                $item
                                    ->replacementEmployee
                                    ->id,

                            'name' =>
                                $this->employeeName(
                                    $item
                                        ->replacementEmployee
                                ),
                        ]
                        : null,

                'start_date' =>
                    $item->start_date
                        ?->format('Y-m-d'),

                'end_date' =>
                    $item->end_date
                        ?->format('Y-m-d'),

                'return_date' =>
                    $item->return_date
                        ?->format('Y-m-d'),

                'requested_amount' =>
                    $item->requested_amount,

                'approved_amount' =>
                    $item->approved_amount,

                'duration_label' =>
                    $item->duration_label,

                'status' =>
                    $item->status,

                'status_label' =>
                    $item->status_label,

                'status_color' =>
                    $item->status_color,

                'reason' =>
                    $item->reason,

                'decision_notes' =>
                    $item->decision_notes,

                'requested_at' =>
                    $item->requested_at
                        ?->format(
                            'Y-m-d H:i:s'
                        ),

                'can_edit' =>
                    $item->isDraft(),

                'can_submit' =>
                    $item->canBeSubmitted(),

                'can_cancel' =>
                    $item->canBeCancelled(),

                'has_attachment' =>
                    filled(
                        $item->attachment_path
                    ),
            ]
        );

        return response()->json(
            $requests
        );
    }

    public function options(): JsonResponse
    {
        $this->ensurePermission();

        $employee =
            $this->requireCurrentEmployee();

        $leaveTypes =
            LeaveType::query()
                ->active()
                ->forGender(
                    $employee->gender
                )
                ->ordered()
                ->get();

        $types = $leaveTypes->map(
            function (
                LeaveType $leaveType
            ) use ($employee) {
                $balance = null;

                if (
                    $leaveType
                        ->requires_balance
                ) {
                    $balance =
                        $this->balanceService
                            ->getOrCreate(
                                employee: $employee,
                                leaveType: $leaveType,
                                year: now()->year
                            );
                }

                return [
                    'id' =>
                        $leaveType->id,

                    'code' =>
                        $leaveType->code,

                    'name' =>
                        $leaveType
                            ->display_name,

                    'unit' =>
                        $leaveType->unit,

                    'unit_label' =>
                        $leaveType
                            ->unit_label,

                    'payment_type' =>
                        $leaveType
                            ->payment_type,

                    'payment_type_label' =>
                        $leaveType
                            ->payment_type_label,

                    'requires_balance' =>
                        $leaveType
                            ->requires_balance,

                    'allow_half_day' =>
                        $leaveType
                            ->allow_half_day,

                    'requires_attachment' =>
                        $leaveType
                            ->requires_attachment,

                    'minimum_notice_days' =>
                        $leaveType
                            ->minimum_notice_days,

                    'maximum_consecutive_days' =>
                        $leaveType
                            ->maximum_consecutive_days,

                    'available_balance' =>
                        $balance
                            ?->available_balance,
                ];
            }
        )->values();

        $replacementEmployees =
            Employee::query()
                ->whereKeyNot(
                    $employee->id
                )
                ->whereNotIn(
                    'employment_status',
                    [
                        'terminated',
                    ]
                )
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
                ->map(
                    fn (Employee $item) => [
                        'id' =>
                            $item->id,

                        'employee_number' =>
                            $item
                                ->employee_number,

                        'name' =>
                            $this->employeeName(
                                $item
                            ),
                    ]
                )
                ->values();

        return response()->json([
            'success' =>
                true,

            'leave_types' =>
                $types,

            'replacement_employees' =>
                $replacementEmployees,
        ]);
    }

    public function store(
        StoreLeaveRequest $request
    ): JsonResponse {
        $this->ensurePermission();

        $employee =
            $this->requireCurrentEmployee();

        $data =
            $request->validated();

        $data['employee_id'] =
            $employee->id;

        $attachmentPath = null;

        try {
            $leaveType =
                $this->findLeaveType(
                    (int) $data[
                        'leave_type_id'
                    ],
                    $employee
                );

            if (
                $request->hasFile(
                    'attachment'
                )
            ) {
                $attachmentPath =
                    $this->storeAttachment(
                        $request,
                        $employee
                    );

                $data['attachment_path'] =
                    $attachmentPath;
            }

            $leaveRequest =
                $this->leaveService
                    ->createDraft(
                        employee: $employee,
                        leaveType: $leaveType,
                        data: $data,
                        actor: $request->user()
                    );

            if (
                $request->boolean(
                    'submit_now'
                )
            ) {
                $leaveRequest =
                    $this->leaveService
                        ->submit(
                            $leaveRequest,
                            $request->user()
                        );
            }

            return response()->json([
                'success' =>
                    true,

                'message' =>
                    $request->boolean(
                        'submit_now'
                    )
                        ? 'تم إرسال طلب الإجازة للاعتماد.'
                        : 'تم حفظ طلب الإجازة كمسودة.',

                'leave_request' =>
                    $leaveRequest,
            ], 201);
        } catch (DomainException $exception) {
            $this->deleteAttachment(
                $attachmentPath
            );

            return response()->json([
                'success' =>
                    false,

                'message' =>
                    $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            $this->deleteAttachment(
                $attachmentPath
            );

            throw $exception;
        }
    }

    public function show(
        LeaveRequest $leaveRequest
    ): JsonResponse {
        $this->ensurePermission();

        $this->ensureOwnedRequest(
            $leaveRequest
        );

        $leaveRequest->load([
            'leaveType',
            'replacementEmployee',
            'days',
            'requester:id,name',
            'approver:id,name',
            'canceller:id,name',
        ]);

        return response()->json([
            'success' =>
                true,

            'leave_request' =>
                $leaveRequest,

            'attachment_url' =>
                $leaveRequest
                    ->attachment_path
                        ? route(
                            'app.self-service.leave.attachment',
                            $leaveRequest
                        )
                        : null,
        ]);
    }

    public function update(
        StoreLeaveRequest $request,
        LeaveRequest $leaveRequest
    ): JsonResponse {
        $this->ensurePermission();

        $this->ensureOwnedRequest(
            $leaveRequest
        );

        $employee =
            $this->requireCurrentEmployee();

        $data =
            $request->validated();

        $data['employee_id'] =
            $employee->id;

        $newAttachmentPath = null;
        $oldAttachmentPath =
            $leaveRequest->attachment_path;

        try {
            $leaveType =
                $this->findLeaveType(
                    (int) $data[
                        'leave_type_id'
                    ],
                    $employee
                );

            if (
                $request->hasFile(
                    'attachment'
                )
            ) {
                $newAttachmentPath =
                    $this->storeAttachment(
                        $request,
                        $employee
                    );

                $data['attachment_path'] =
                    $newAttachmentPath;
            }

            $leaveRequest =
                $this->leaveService
                    ->updateDraft(
                        request: $leaveRequest,
                        leaveType: $leaveType,
                        data: $data,
                        actor: $request->user()
                    );

            if ($newAttachmentPath) {
                $this->deleteAttachment(
                    $oldAttachmentPath
                );
            }

            return response()->json([
                'success' =>
                    true,

                'message' =>
                    'تم تحديث طلب الإجازة.',

                'leave_request' =>
                    $leaveRequest,
            ]);
        } catch (DomainException $exception) {
            $this->deleteAttachment(
                $newAttachmentPath
            );

            return response()->json([
                'success' =>
                    false,

                'message' =>
                    $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            $this->deleteAttachment(
                $newAttachmentPath
            );

            throw $exception;
        }
    }

    public function submit(
        Request $request,
        LeaveRequest $leaveRequest
    ): JsonResponse {
        $this->ensurePermission();

        $this->ensureOwnedRequest(
            $leaveRequest
        );

        try {
            $leaveRequest =
                $this->leaveService
                    ->submit(
                        $leaveRequest,
                        $request->user()
                    );

            return response()->json([
                'success' =>
                    true,

                'message' =>
                    'تم إرسال طلب الإجازة للاعتماد.',

                'leave_request' =>
                    $leaveRequest,
            ]);
        } catch (DomainException $exception) {
            return response()->json([
                'success' =>
                    false,

                'message' =>
                    $exception->getMessage(),
            ], 422);
        }
    }

    public function cancel(
        Request $request,
        LeaveRequest $leaveRequest
    ): JsonResponse {
        $this->ensurePermission();

        $this->ensureOwnedRequest(
            $leaveRequest
        );

        $validated =
            $request->validate([
                'reason' => [
                    'required',
                    'string',
                    'min:3',
                    'max:2000',
                ],
            ], [
                'reason.required' =>
                    'سبب الإلغاء مطلوب.',
            ]);

        try {
            $leaveRequest =
                $this->leaveService
                    ->cancel(
                        request: $leaveRequest,
                        actor: $request->user(),
                        notes: $validated['reason']
                    );

            return response()->json([
                'success' =>
                    true,

                'message' =>
                    'تم إلغاء طلب الإجازة.',

                'leave_request' =>
                    $leaveRequest,
            ]);
        } catch (DomainException $exception) {
            return response()->json([
                'success' =>
                    false,

                'message' =>
                    $exception->getMessage(),
            ], 422);
        }
    }

    public function destroy(
        LeaveRequest $leaveRequest
    ): JsonResponse {
        $this->ensurePermission();

        $this->ensureOwnedRequest(
            $leaveRequest
        );

        if (!$leaveRequest->isDraft()) {
            return response()->json([
                'success' =>
                    false,

                'message' =>
                    'يمكن حذف المسودات فقط.',
            ], 422);
        }

        $attachmentPath =
            $leaveRequest->attachment_path;

        $leaveRequest->delete();

        $this->deleteAttachment(
            $attachmentPath
        );

        return response()->json([
            'success' =>
                true,

            'message' =>
                'تم حذف مسودة الإجازة.',
        ]);
    }

    public function attachment(
        LeaveRequest $leaveRequest
    ): StreamedResponse|JsonResponse {
        $this->ensurePermission();

        $this->ensureOwnedRequest(
            $leaveRequest
        );

        $path =
            $leaveRequest->attachment_path;

        $disk =
            config(
                'hr.leave.attachments.disk',
                'public'
            );

        if (
            !$path ||
            !Storage::disk($disk)
                ->exists($path)
        ) {
            return response()->json([
                'success' =>
                    false,

                'message' =>
                    'المرفق غير موجود.',
            ], 404);
        }

        return Storage::disk($disk)
            ->response(
                $path,
                basename($path),
                [
                    'Content-Disposition' =>
                        'inline',
                ]
            );
    }

    private function currentEmployee(): ?Employee
    {
        return Employee::query()
            ->where(
                'tenant_id',
                $this->tenantId()
            )
            ->where(
                'user_id',
                auth()->id()
            )
            ->first();
    }

    private function requireCurrentEmployee(): Employee
    {
        $employee =
            $this->currentEmployee();

        if (!$employee) {
            throw new DomainException(
                'لا يوجد ملف موظف مرتبط بحسابك.'
            );
        }

        return $employee;
    }

    private function findLeaveType(
        int $leaveTypeId,
        Employee $employee
    ): LeaveType {
        return LeaveType::query()
            ->active()
            ->forGender(
                $employee->gender
            )
            ->findOrFail(
                $leaveTypeId
            );
    }

    private function ensureOwnedRequest(
        LeaveRequest $leaveRequest
    ): void {
        $employee =
            $this->requireCurrentEmployee();

        abort_unless(
            (int) $leaveRequest->tenant_id ===
                $this->tenantId() &&
            (int) $leaveRequest->employee_id ===
                (int) $employee->id,
            404
        );
    }

    private function storeAttachment(
        StoreLeaveRequest $request,
        Employee $employee
    ): string {
        $disk =
            config(
                'hr.leave.attachments.disk',
                'public'
            );

        $directory =
            "tenants/{$this->tenantId()}" .
            "/employees/{$employee->id}" .
            '/leave-requests';

        return $request
            ->file('attachment')
            ->store(
                $directory,
                $disk
            );
    }

    private function deleteAttachment(
        ?string $path
    ): void {
        if (!$path) {
            return;
        }

        $disk =
            config(
                'hr.leave.attachments.disk',
                'public'
            );

        Storage::disk($disk)
            ->delete($path);
    }

    private function employeeName(
        ?Employee $employee
    ): string {
        if (!$employee) {
            return '-';
        }

        return collect([
            $employee->first_name,
            $employee->father_name,
            $employee->grandfather_name,
            $employee->family_name,
        ])
            ->filter()
            ->implode(' ');
    }

    private function tenantId(): int
    {
        $tenantId =
            auth()->user()
                ?->tenant_id;

        abort_if(
            !$tenantId,
            403,
            'لا يوجد عميل مرتبط بالحساب.'
        );

        return (int) $tenantId;
    }

    private function ensurePermission(): void
    {
        abort_unless(
            auth()->user()
                ?->can(
                    'self_service.leave'
                ),
            403,
            'ليس لديك صلاحية استخدام خدمة الإجازات.'
        );
    }
}