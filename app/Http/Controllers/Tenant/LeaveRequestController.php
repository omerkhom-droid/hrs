<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreLeaveRequest;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\HR\LeaveRequestService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class LeaveRequestController extends Controller
{
    public function __construct(
        private readonly LeaveRequestService $leaveService
    ) {
    }

    public function index(): View
    {
        $this->ensurePermission(
            'leave.view'
        );

        $tenantId =
            $this->tenantId();

        $summary = [
            'total' =>
                LeaveRequest::query()
                    ->where(
                        'tenant_id',
                        $tenantId
                    )
                    ->count(),

            'pending' =>
                LeaveRequest::query()
                    ->where(
                        'tenant_id',
                        $tenantId
                    )
                    ->pending()
                    ->count(),

            'approved' =>
                LeaveRequest::query()
                    ->where(
                        'tenant_id',
                        $tenantId
                    )
                    ->approved()
                    ->count(),

            'on_leave_today' =>
                LeaveRequest::query()
                    ->where(
                        'tenant_id',
                        $tenantId
                    )
                    ->activeDuring(
                        now()->toDateString()
                    )
                    ->count(),
        ];

        return view(
            'tenant.leave-requests.index',
            compact('summary')
        );
    }

    public function data(
        Request $request
    ): JsonResponse {
        $this->ensurePermission(
            'leave.view'
        );

        $tenantId =
            $this->tenantId();

        $perPage = min(
            max(
                (int) $request->get(
                    'per_page',
                    15
                ),
                10
            ),
            100
        );

        $query = LeaveRequest::query()
            ->where(
                'tenant_id',
                $tenantId
            )
            ->with([
                'employee:id,employee_number,first_name,father_name,grandfather_name,family_name',
                'leaveType:id,code,name,name_en,unit',
                'replacementEmployee:id,employee_number,first_name,father_name,grandfather_name,family_name',
                'requester:id,name',
                'approver:id,name',
            ])
            ->latest('id');

        $search = trim(
            (string) $request->get(
                'search',
                ''
            )
        );

        if ($search !== '') {
            $query->where(
                function ($query) use ($search) {
                    $query
                        ->where(
                            'uuid',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhereHas(
                            'employee',
                            function ($query) use (
                                $search
                            ) {
                                $query
                                    ->where(
                                        'employee_number',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        'first_name',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        'father_name',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        'family_name',
                                        'like',
                                        "%{$search}%"
                                    );
                            }
                        )
                        ->orWhereHas(
                            'leaveType',
                            function ($query) use (
                                $search
                            ) {
                                $query
                                    ->where(
                                        'name',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        'code',
                                        'like',
                                        "%{$search}%"
                                    );
                            }
                        );
                }
            );
        }

        if ($request->filled('status')) {
            $query->ofStatus(
                $request->get('status')
            );
        }

        if ($request->filled('employee_id')) {
            $query->forEmployee(
                (int) $request->get(
                    'employee_id'
                )
            );
        }

        if ($request->filled('leave_type_id')) {
            $query->forLeaveType(
                (int) $request->get(
                    'leave_type_id'
                )
            );
        }

        if ($request->filled('date_from')) {
            $query->whereDate(
                'end_date',
                '>=',
                $request->get('date_from')
            );
        }

        if ($request->filled('date_to')) {
            $query->whereDate(
                'start_date',
                '<=',
                $request->get('date_to')
            );
        }

        $results =
            $query->paginate($perPage);

        $results->through(
            fn (LeaveRequest $item) => [
                'id' =>
                    $item->id,

                'uuid' =>
                    $item->uuid,

                'employee' => [
                    'id' =>
                        $item->employee?->id,

                    'employee_number' =>
                        $item->employee
                            ?->employee_number,

                    'name' =>
                        $this->employeeName(
                            $item->employee
                        ),
                ],

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

                'requested_at' =>
                    $item->requested_at
                        ?->format(
                            'Y-m-d H:i:s'
                        ),

                'can_edit' =>
                    $item->isDraft(),

                'can_submit' =>
                    $item->canBeSubmitted(),

                'can_review' =>
                    $item->canBeReviewed(),

                'can_cancel' =>
                    $item->canBeCancelled(),

                'has_attachment' =>
                    filled(
                        $item->attachment_path
                    ),
            ]
        );

        return response()->json(
            $results
        );
    }

    public function options(): JsonResponse
    {
        $this->ensurePermission(
            'leave.manage'
        );

        $tenantId =
            $this->tenantId();

        $leaveTypes =
            LeaveType::query()
                ->where(
                    'tenant_id',
                    $tenantId
                )
                ->active()
                ->ordered()
                ->get([
                    'id',
                    'code',
                    'name',
                    'name_en',
                    'unit',
                    'requires_balance',
                    'allow_half_day',
                    'requires_attachment',
                ])
                ->map(
                    fn (LeaveType $type) => [
                        'id' =>
                            $type->id,

                        'code' =>
                            $type->code,

                        'name' =>
                            $type->display_name,

                        'unit' =>
                            $type->unit,

                        'requires_balance' =>
                            $type
                                ->requires_balance,

                        'allow_half_day' =>
                            $type
                                ->allow_half_day,

                        'requires_attachment' =>
                            $type
                                ->requires_attachment,
                    ]
                )
                ->values();

        $employees =
            Employee::query()
                ->where(
                    'tenant_id',
                    $tenantId
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
                    fn (Employee $employee) => [
                        'id' =>
                            $employee->id,

                        'employee_number' =>
                            $employee
                                ->employee_number,

                        'name' =>
                            $this->employeeName(
                                $employee
                            ),
                    ]
                )
                ->values();

        return response()->json([
            'success' =>
                true,

            'leave_types' =>
                $leaveTypes,

            'employees' =>
                $employees,
        ]);
    }

    public function store(
        StoreLeaveRequest $request
    ): JsonResponse {
        $this->ensurePermission(
            'leave.manage'
        );

        $data =
            $request->validated();

        $attachmentPath = null;

        try {
            $employee =
                $this->findEmployee(
                    (int) $data['employee_id']
                );

            $leaveType =
                $this->findLeaveType(
                    (int) $data[
                        'leave_type_id'
                    ]
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
                        ? 'تم إنشاء الطلب وإرساله للاعتماد.'
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
        $this->ensurePermission(
            'leave.view'
        );

        $this->ensureTenantModel(
            $leaveRequest
        );

        $leaveRequest->load([
            'employee',
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
                            'app.leaves.requests.attachment',
                            $leaveRequest
                        )
                        : null,
        ]);
    }

    public function update(
        StoreLeaveRequest $request,
        LeaveRequest $leaveRequest
    ): JsonResponse {
        $this->ensurePermission(
            'leave.manage'
        );

        $this->ensureTenantModel(
            $leaveRequest
        );

        $data =
            $request->validated();

        $newAttachmentPath = null;
        $oldAttachmentPath =
            $leaveRequest->attachment_path;

        try {
            $leaveType =
                $this->findLeaveType(
                    (int) $data[
                        'leave_type_id'
                    ]
                );

            if (
                $request->hasFile(
                    'attachment'
                )
            ) {
                $newAttachmentPath =
                    $this->storeAttachment(
                        $request,
                        $leaveRequest->employee
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
                    'تم تحديث طلب الإجازة بنجاح.',

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
        $this->ensurePermission(
            'leave.manage'
        );

        $this->ensureTenantModel(
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
        $this->ensurePermission(
            'leave.manage'
        );

        $this->ensureTenantModel(
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
        $this->ensurePermission(
            'leave.manage'
        );

        $this->ensureTenantModel(
            $leaveRequest
        );

        if (!$leaveRequest->isDraft()) {
            return response()->json([
                'success' =>
                    false,

                'message' =>
                    'يمكن حذف الطلبات المحفوظة كمسودة فقط.',
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
                'تم حذف مسودة طلب الإجازة.',
        ]);
    }

    public function attachment(
        LeaveRequest $leaveRequest
    ): StreamedResponse|JsonResponse {
        $this->ensurePermission(
            'leave.view'
        );

        $this->ensureTenantModel(
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

    private function findEmployee(
        int $employeeId
    ): Employee {
        return Employee::query()
            ->where(
                'tenant_id',
                $this->tenantId()
            )
            ->findOrFail(
                $employeeId
            );
    }

    private function findLeaveType(
        int $leaveTypeId
    ): LeaveType {
        return LeaveType::query()
            ->where(
                'tenant_id',
                $this->tenantId()
            )
            ->active()
            ->findOrFail(
                $leaveTypeId
            );
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

    private function ensurePermission(
        string $permission
    ): void {
        abort_unless(
            auth()->user()
                ?->can($permission),
            403,
            'ليس لديك صلاحية لتنفيذ هذا الإجراء.'
        );
    }

    private function ensureTenantModel(
        LeaveRequest $leaveRequest
    ): void {
        abort_unless(
            (int) $leaveRequest->tenant_id ===
                $this->tenantId(),
            404
        );
    }
}