<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\HR\LeaveRequestService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveApprovalController extends Controller
{
    public function __construct(
        private readonly LeaveRequestService $leaveService
    ) {
    }

    public function index(): View
    {
        $this->ensurePermission();

        $tenantId =
            $this->tenantId();

        $summary = [
            'pending' =>
                LeaveRequest::query()
                    ->where(
                        'tenant_id',
                        $tenantId
                    )
                    ->pending()
                    ->count(),

            'requested_today' =>
                LeaveRequest::query()
                    ->where(
                        'tenant_id',
                        $tenantId
                    )
                    ->pending()
                    ->whereDate(
                        'requested_at',
                        now()->toDateString()
                    )
                    ->count(),

            'approved_this_month' =>
                LeaveRequest::query()
                    ->where(
                        'tenant_id',
                        $tenantId
                    )
                    ->approved()
                    ->whereYear(
                        'approved_at',
                        now()->year
                    )
                    ->whereMonth(
                        'approved_at',
                        now()->month
                    )
                    ->count(),

            'rejected_this_month' =>
                LeaveRequest::query()
                    ->where(
                        'tenant_id',
                        $tenantId
                    )
                    ->where(
                        'status',
                        LeaveRequest::STATUS_REJECTED
                    )
                    ->whereYear(
                        'rejected_at',
                        now()->year
                    )
                    ->whereMonth(
                        'rejected_at',
                        now()->month
                    )
                    ->count(),
        ];

        return view(
            'tenant.leave-approvals.index',
            compact('summary')
        );
    }

    public function data(
        Request $request
    ): JsonResponse {
        $this->ensurePermission();

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
            ->pending()
            ->with([
                'employee:id,employee_number,first_name,father_name,grandfather_name,family_name,department_id,job_title_id',
                'employee.department:id,name',
                'employee.jobTitle:id,name',
                'leaveType:id,code,name,name_en,unit,requires_balance',
                'replacementEmployee:id,employee_number,first_name,father_name,grandfather_name,family_name',
                'requester:id,name',
            ])
            ->orderBy('requested_at')
            ->orderBy('id');

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
                'start_date',
                '>=',
                $request->get('date_from')
            );
        }

        if ($request->filled('date_to')) {
            $query->whereDate(
                'end_date',
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

                    'department' =>
                        $item->employee
                            ?->department
                            ?->name,

                    'job_title' =>
                        $item->employee
                            ?->jobTitle
                            ?->name,
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

                    'requires_balance' =>
                        $item->leaveType
                            ?->requires_balance,
                ],

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

                'duration_label' =>
                    $item->duration_label,

                'reason' =>
                    $item->reason,

                'requested_at' =>
                    $item->requested_at
                        ?->format(
                            'Y-m-d H:i:s'
                        ),

                'current_approval_level' =>
                    $item
                        ->current_approval_level,

                'required_approval_levels' =>
                    $item
                        ->required_approval_levels,

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

    public function approve(
        Request $request,
        LeaveRequest $leaveRequest
    ): JsonResponse {
        $this->ensurePermission();

        $this->ensureTenantModel(
            $leaveRequest
        );

        $validated =
            $request->validate([
                'notes' => [
                    'nullable',
                    'string',
                    'max:2000',
                ],
            ]);

        try {
            $leaveRequest =
                $this->leaveService
                    ->approve(
                        request: $leaveRequest,
                        approver: $request->user(),
                        notes: $validated['notes']
                            ?? null
                    );

            $message =
                $leaveRequest->isApproved()
                    ? 'تم اعتماد طلب الإجازة نهائيًا.'
                    : 'تم اعتماد المستوى الحالي وإرسال الطلب للمستوى التالي.';

            return response()->json([
                'success' =>
                    true,

                'message' =>
                    $message,

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

    public function reject(
        Request $request,
        LeaveRequest $leaveRequest
    ): JsonResponse {
        $this->ensurePermission();

        $this->ensureTenantModel(
            $leaveRequest
        );

        $validated =
            $request->validate([
                'notes' => [
                    'required',
                    'string',
                    'min:3',
                    'max:2000',
                ],
            ], [
                'notes.required' =>
                    'سبب الرفض مطلوب.',

                'notes.min' =>
                    'سبب الرفض يجب ألا يقل عن 3 أحرف.',
            ]);

        try {
            $leaveRequest =
                $this->leaveService
                    ->reject(
                        request: $leaveRequest,
                        approver: $request->user(),
                        notes: $validated['notes']
                    );

            return response()->json([
                'success' =>
                    true,

                'message' =>
                    'تم رفض طلب الإجازة.',

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

    public function bulkApprove(
        Request $request
    ): JsonResponse {
        $this->ensurePermission();

        $validated =
            $request->validate([
                'request_ids' => [
                    'required',
                    'array',
                    'min:1',
                    'max:100',
                ],

                'request_ids.*' => [
                    'required',
                    'integer',
                    'distinct',
                ],

                'notes' => [
                    'nullable',
                    'string',
                    'max:2000',
                ],
            ], [
                'request_ids.required' =>
                    'يجب تحديد طلب واحد على الأقل.',

                'request_ids.max' =>
                    'يمكن اعتماد 100 طلب كحد أقصى في العملية الواحدة.',
            ]);

        $approved = [];
        $failed = [];

        foreach (
            $validated['request_ids']
            as $requestId
        ) {
            $leaveRequest =
                LeaveRequest::query()
                    ->where(
                        'tenant_id',
                        $this->tenantId()
                    )
                    ->find($requestId);

            if (!$leaveRequest) {
                $failed[] = [
                    'id' =>
                        $requestId,

                    'message' =>
                        'الطلب غير موجود.',
                ];

                continue;
            }

            try {
                $result =
                    $this->leaveService
                        ->approve(
                            request:
                                $leaveRequest,
                            approver:
                                $request->user(),
                            notes:
                                $validated['notes']
                                ?? null
                        );

                $approved[] = [
                    'id' =>
                        $result->id,

                    'status' =>
                        $result->status,

                    'message' =>
                        $result->isApproved()
                            ? 'تم الاعتماد النهائي.'
                            : 'تم اعتماد المستوى الحالي.',
                ];
            } catch (DomainException $exception) {
                $failed[] = [
                    'id' =>
                        $requestId,

                    'message' =>
                        $exception
                            ->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' =>
                count($approved) > 0,

            'message' =>
                'تم اعتماد ' .
                count($approved) .
                ' طلب، وتعذر اعتماد ' .
                count($failed) .
                ' طلب.',

            'approved' =>
                $approved,

            'failed' =>
                $failed,
        ]);
    }

    public function bulkReject(
        Request $request
    ): JsonResponse {
        $this->ensurePermission();

        $validated =
            $request->validate([
                'request_ids' => [
                    'required',
                    'array',
                    'min:1',
                    'max:100',
                ],

                'request_ids.*' => [
                    'required',
                    'integer',
                    'distinct',
                ],

                'notes' => [
                    'required',
                    'string',
                    'min:3',
                    'max:2000',
                ],
            ], [
                'request_ids.required' =>
                    'يجب تحديد طلب واحد على الأقل.',

                'notes.required' =>
                    'سبب الرفض مطلوب.',
            ]);

        $rejected = [];
        $failed = [];

        foreach (
            $validated['request_ids']
            as $requestId
        ) {
            $leaveRequest =
                LeaveRequest::query()
                    ->where(
                        'tenant_id',
                        $this->tenantId()
                    )
                    ->find($requestId);

            if (!$leaveRequest) {
                $failed[] = [
                    'id' =>
                        $requestId,

                    'message' =>
                        'الطلب غير موجود.',
                ];

                continue;
            }

            try {
                $result =
                    $this->leaveService
                        ->reject(
                            request:
                                $leaveRequest,
                            approver:
                                $request->user(),
                            notes:
                                $validated['notes']
                        );

                $rejected[] = [
                    'id' =>
                        $result->id,
                ];
            } catch (DomainException $exception) {
                $failed[] = [
                    'id' =>
                        $requestId,

                    'message' =>
                        $exception
                            ->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' =>
                count($rejected) > 0,

            'message' =>
                'تم رفض ' .
                count($rejected) .
                ' طلب، وتعذر رفض ' .
                count($failed) .
                ' طلب.',

            'rejected' =>
                $rejected,

            'failed' =>
                $failed,
        ]);
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
                ?->can('leave.approve'),
            403,
            'ليس لديك صلاحية اعتماد الإجازات.'
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