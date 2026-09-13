<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreLeaveTypeRequest;
use App\Models\LeaveType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveTypeController extends Controller
{
    public function index(): View
    {
        $this->ensurePermission(
            'leave.view'
        );

        $tenantId =
            $this->tenantId();

        $summary = [
            'total' =>
                LeaveType::query()
                    ->where(
                        'tenant_id',
                        $tenantId
                    )
                    ->count(),

            'active' =>
                LeaveType::query()
                    ->where(
                        'tenant_id',
                        $tenantId
                    )
                    ->where(
                        'is_active',
                        true
                    )
                    ->count(),

            'requires_balance' =>
                LeaveType::query()
                    ->where(
                        'tenant_id',
                        $tenantId
                    )
                    ->where(
                        'requires_balance',
                        true
                    )
                    ->count(),

            'paid' =>
                LeaveType::query()
                    ->where(
                        'tenant_id',
                        $tenantId
                    )
                    ->where(
                        'payment_type',
                        'paid'
                    )
                    ->count(),
        ];

        return view(
            'tenant.leave-types.index',
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

        $query = LeaveType::query()
            ->where(
                'tenant_id',
                $tenantId
            )
            ->withCount([
                'requests',
                'balances',
            ]);

        $query->search(
            $request->get('search')
        );

        if (
            $request->filled('status') &&
            in_array(
                $request->get('status'),
                [
                    'active',
                    'inactive',
                ],
                true
            )
        ) {
            $query->where(
                'is_active',
                $request->get('status') ===
                    'active'
            );
        }

        if (
            $request->filled('unit') &&
            in_array(
                $request->get('unit'),
                [
                    'day',
                    'hour',
                ],
                true
            )
        ) {
            $query->where(
                'unit',
                $request->get('unit')
            );
        }

        if (
            $request->filled(
                'payment_type'
            ) &&
            in_array(
                $request->get(
                    'payment_type'
                ),
                [
                    'paid',
                    'unpaid',
                    'partially_paid',
                ],
                true
            )
        ) {
            $query->where(
                'payment_type',
                $request->get(
                    'payment_type'
                )
            );
        }

        if (
            $request->filled(
                'requires_balance'
            ) &&
            in_array(
                (string) $request->get(
                    'requires_balance'
                ),
                [
                    '0',
                    '1',
                ],
                true
            )
        ) {
            $query->where(
                'requires_balance',
                (bool) $request->integer(
                    'requires_balance'
                )
            );
        }

        $sortBy =
            $request->get(
                'sort_by',
                'sort_order'
            );

        $sortDirection =
            strtolower(
                $request->get(
                    'sort_direction',
                    'asc'
                )
            );

        if (
            !in_array(
                $sortBy,
                [
                    'id',
                    'code',
                    'name',
                    'sort_order',
                    'created_at',
                ],
                true
            )
        ) {
            $sortBy =
                'sort_order';
        }

        if (
            !in_array(
                $sortDirection,
                [
                    'asc',
                    'desc',
                ],
                true
            )
        ) {
            $sortDirection =
                'asc';
        }

        $results = $query
            ->orderBy(
                $sortBy,
                $sortDirection
            )
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json(
            $results
        );
    }

    public function store(
        StoreLeaveTypeRequest $request
    ): JsonResponse {
        $this->ensurePermission(
            'leave.manage'
        );

        $data =
            $request->validated();

        unset(
            $data['description']
        );

        $leaveType =
            LeaveType::create(
                array_merge(
                    $data,
                    [
                        'tenant_id' =>
                            $this->tenantId(),
                    ]
                )
            );

        return response()->json([
            'success' =>
                true,

            'message' =>
                'تم إنشاء نوع الإجازة بنجاح.',

            'leave_type' =>
                $leaveType->fresh(),
        ], 201);
    }

    public function show(
        LeaveType $leaveType
    ): JsonResponse {
        $this->ensurePermission(
            'leave.view'
        );

        $this->ensureTenantModel(
            $leaveType
        );

        $leaveType->loadCount([
            'requests',
            'balances',
        ]);

        return response()->json([
            'success' =>
                true,

            'leave_type' =>
                $leaveType,
        ]);
    }

    public function update(
        StoreLeaveTypeRequest $request,
        LeaveType $leaveType
    ): JsonResponse {
        $this->ensurePermission(
            'leave.manage'
        );

        $this->ensureTenantModel(
            $leaveType
        );

        $data =
            $request->validated();

        unset(
            $data['description']
        );

        $leaveType->update(
            $data
        );

        return response()->json([
            'success' =>
                true,

            'message' =>
                'تم تحديث نوع الإجازة بنجاح.',

            'leave_type' =>
                $leaveType->fresh(),
        ]);
    }

    public function destroy(
        LeaveType $leaveType
    ): JsonResponse {
        $this->ensurePermission(
            'leave.manage'
        );

        $this->ensureTenantModel(
            $leaveType
        );

        if (!$leaveType->is_active) {
            return response()->json([
                'success' =>
                    true,

                'message' =>
                    'نوع الإجازة معطل مسبقًا.',
            ]);
        }

        /*
         * لا نحذف نوع الإجازة حتى لا تتأثر
         * الطلبات والأرصدة والسجلات التاريخية.
         */
        $leaveType->update([
            'is_active' =>
                false,
        ]);

        return response()->json([
            'success' =>
                true,

            'message' =>
                'تم تعطيل نوع الإجازة مع الاحتفاظ بسجلاته.',
        ]);
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
        LeaveType $leaveType
    ): void {
        abort_unless(
            (int) $leaveType->tenant_id ===
                $this->tenantId(),
            404
        );
    }
}