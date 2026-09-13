<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreBulkLeaveBalanceRequest;
use App\Http\Requests\Tenant\StoreLeaveBalanceAdjustmentRequest;
use App\Http\Requests\Tenant\StoreLeaveBalanceCarryForwardRequest;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveBalanceTransaction;
use App\Models\LeaveType;
use App\Services\HR\LeaveBalanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveBalanceController extends Controller
{
    public function __construct(
        private readonly LeaveBalanceService $leaveBalanceService
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Page
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request
    ): View {
        $this->ensureViewPermission(
            $request
        );

        return view(
            'tenant.leave-balances.index',
            [
                'currentYear' =>
                    now()->year,
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Balances Data
    |--------------------------------------------------------------------------
    */

    public function data(
        Request $request
    ): JsonResponse {
        $this->ensureViewPermission(
            $request
        );

        $tenantId = (int) $request
            ->user()
            ->tenant_id;

        $search = trim(
            (string) $request->input(
                'search',
                ''
            )
        );

        $year = (int) $request->input(
            'year',
            now()->year
        );

        $leaveTypeId = $request->integer(
            'leave_type_id'
        );

        $perPage = min(
            max(
                $request->integer(
                    'per_page',
                    15
                ),
                10
            ),
            100
        );

        $employees = Employee::query()
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
            ->select([
                'id',
                'tenant_id',
                'employee_number',
                'first_name',
                'father_name',
                'grandfather_name',
                'family_name',
                'employment_status',
                'department_id',
                'job_title_id',
                'photo_path',
            ])
            ->with([
                'department:id,name',

                'jobTitle:id,name',

                'leaveBalances' => function (
                    $query
                ) use (
                    $year,
                    $leaveTypeId
                ) {
                    $query
                        ->where(
                            'year',
                            $year
                        )
                        ->when(
                            $leaveTypeId,
                            fn ($query) =>
                                $query->where(
                                    'leave_type_id',
                                    $leaveTypeId
                                )
                        )
                        ->with([
                            'leaveType:id,code,name',
                        ])
                        ->orderBy(
                            'leave_type_id'
                        );
                },
            ])
            ->when(
                $search !== '',
                function ($query) use ($search) {
                    $query->where(
                        function ($query) use ($search) {
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
                                    'grandfather_name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'family_name',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            )
            ->orderBy(
                'first_name'
            )
            ->orderBy(
                'family_name'
            )
            ->paginate(
                $perPage
            );

        $employees->through(
            function (Employee $employee) {
                return [
                    'id' =>
                        $employee->id,

                    'employee_number' =>
                        $employee->employee_number,

                    'name' =>
                        $this->employeeName(
                            $employee
                        ),

                    'employment_status' =>
                        $employee->employment_status,

                    'department' =>
                        $employee
                            ->department
                            ?->name,

                    'job_title' =>
                        $employee
                            ->jobTitle
                            ?->name,

                    'balances' =>
                        $employee
                            ->leaveBalances
                            ->map(
                                function (
                                    LeaveBalance $balance
                                ) {
                                    return [
                                        'id' =>
                                            $balance->id,

                                        'leave_type_id' =>
                                            $balance
                                                ->leave_type_id,

                                        'year' =>
                                            $balance->year,

                                        'opening_balance' =>
                                            $balance
                                                ->opening_balance,

                                        'accrued_balance' =>
                                            $balance
                                                ->accrued_balance,

                                        'carried_forward' =>
                                            $balance
                                                ->carried_forward,

                                        'adjustment_balance' =>
                                            $balance
                                                ->adjustment_balance,

                                        'used_balance' =>
                                            $balance
                                                ->used_balance,

                                        'pending_balance' =>
                                            $balance
                                                ->pending_balance,

                                        'available_balance' =>
                                            $balance
                                                ->available_balance,

                                        'calculated_at' =>
                                            $balance
                                                ->calculated_at,

                                        'leave_type' =>
                                            $balance
                                                ->leaveType
                                                ?->only([
                                                    'id',
                                                    'code',
                                                    'name',
                                                ]),
                                    ];
                                }
                            )
                            ->values(),
                ];
            }
        );

        return response()->json(
            $employees
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Form Options
    |--------------------------------------------------------------------------
    */

    public function options(
        Request $request
    ): JsonResponse {
        $this->ensureViewPermission(
            $request
        );

        $tenantId = (int) $request
            ->user()
            ->tenant_id;

        $employees = Employee::query()
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
            ->select([
                'id',
                'employee_number',
                'first_name',
                'father_name',
                'grandfather_name',
                'family_name',
                'department_id',
            ])
            ->with([
                'department:id,name',
            ])
            ->orderBy(
                'first_name'
            )
            ->orderBy(
                'family_name'
            )
            ->get()
            ->map(
                function (Employee $employee) {
                    return [
                        'id' =>
                            $employee->id,

                        'employee_number' =>
                            $employee
                                ->employee_number,

                        'name' =>
                            $this->employeeName(
                                $employee
                            ),

                        'department' =>
                            $employee
                                ->department
                                ?->name,
                    ];
                }
            );

        $leaveTypes = LeaveType::query()
            ->where(
                'tenant_id',
                $tenantId
            )
            ->where(
                'is_active',
                true
            )
            ->where(
                'requires_balance',
                true
            )
            ->select([
                'id',
                'code',
                'name',
                'unit',
                'allow_negative_balance',
                'allow_carry_forward',
                'maximum_carry_forward',
            ])
            ->orderBy(
                'sort_order'
            )
            ->orderBy(
                'name'
            )
            ->get();

        return response()->json([
            'success' =>
                true,

            'employees' =>
                $employees,

            'leave_types' =>
                $leaveTypes,

            'years' =>
                collect(
                    range(
                        now()->year - 2,
                        now()->year + 2
                    )
                )
                    ->reverse()
                    ->values(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Store Individual Adjustment
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreLeaveBalanceAdjustmentRequest $request
    ): JsonResponse {
        $this->ensureManagePermission(
            $request
        );

        $validated =
            $request->validated();

        $tenantId = (int) $request
            ->user()
            ->tenant_id;

        $tenant = $request
            ->user()
            ->tenant;

        abort_unless(
            $tenant !== null,
            403,
            'تعذر تحديد الشركة الحالية.'
        );

        $employee = Employee::query()
            ->where(
                'tenant_id',
                $tenantId
            )
            ->findOrFail(
                $validated['employee_id']
            );

        $leaveType = LeaveType::query()
            ->where(
                'tenant_id',
                $tenantId
            )
            ->where(
                'is_active',
                true
            )
            ->where(
                'requires_balance',
                true
            )
            ->findOrFail(
                $validated['leave_type_id']
            );

        $balance = $this
            ->leaveBalanceService
            ->applyManualAdjustment(
                tenant:
                    $tenant,

                employee:
                    $employee,

                leaveType:
                    $leaveType,

                year:
                    (int) $validated['year'],

                mode:
                    $validated['mode'],

                days:
                    (float) $validated['days'],

                reason:
                    $validated['reason'],

                actor:
                    $request->user()
            );

        $balance->load([
            'leaveType:id,code,name',
        ]);

        return response()->json([
            'success' =>
                true,

            'message' =>
                'تم تحديث رصيد الإجازة بنجاح.',

            'balance' =>
                $balance,
        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | Store Bulk Adjustment
    |--------------------------------------------------------------------------
    */

    public function bulkStore(
        StoreBulkLeaveBalanceRequest $request
    ): JsonResponse {
        $this->ensureManagePermission(
            $request
        );

        $validated =
            $request->validated();

        $tenantId = (int) $request
            ->user()
            ->tenant_id;

        $tenant = $request
            ->user()
            ->tenant;

        abort_unless(
            $tenant !== null,
            403,
            'تعذر تحديد الشركة الحالية.'
        );

        $leaveType = LeaveType::query()
            ->where(
                'tenant_id',
                $tenantId
            )
            ->where(
                'is_active',
                true
            )
            ->where(
                'requires_balance',
                true
            )
            ->findOrFail(
                $validated['leave_type_id']
            );

        $result = $this
            ->leaveBalanceService
            ->applyBulkManualAdjustment(
                tenant:
                    $tenant,

                leaveType:
                    $leaveType,

                employeeIds:
                    $validated['employee_ids'],

                year:
                    (int) $validated['year'],

                mode:
                    $validated['mode'],

                days:
                    (float) $validated['days'],

                reason:
                    $validated['reason'],

                actor:
                    $request->user()
            );

        $processedCount = (int)
            $result['processed_count'];

        return response()->json([
            'success' =>
                true,

            'message' =>
                "تم تحديث أرصدة {$processedCount} موظف بنجاح.",

            'batch_uuid' =>
                $result['batch_uuid'],

            'processed_count' =>
                $processedCount,

            'employee_ids' =>
                $result['employee_ids'],
        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | Employee Balances
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        Employee $employee
    ): JsonResponse {
        $this->ensureViewPermission(
            $request
        );

        $tenantId = (int) $request
            ->user()
            ->tenant_id;

        abort_unless(
            (int) $employee->tenant_id ===
            $tenantId,
            404
        );

        $year = (int) $request->input(
            'year',
            now()->year
        );

        $employee->load([
            'department:id,name',

            'jobTitle:id,name',

            'leaveBalances' => function (
                $query
            ) use ($year) {
                $query
                    ->where(
                        'year',
                        $year
                    )
                    ->with([
                        'leaveType:id,code,name',
                    ])
                    ->orderBy(
                        'leave_type_id'
                    );
            },
        ]);

        return response()->json([
            'success' =>
                true,

            'employee' => [
                'id' =>
                    $employee->id,

                'employee_number' =>
                    $employee->employee_number,

                'name' =>
                    $this->employeeName(
                        $employee
                    ),

                'department' =>
                    $employee
                        ->department
                        ?->name,

                'job_title' =>
                    $employee
                        ->jobTitle
                        ?->name,

                'balances' =>
                    $employee
                        ->leaveBalances,
            ],
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Store Carry Forward
    |--------------------------------------------------------------------------
    */

    public function carryForwardStore(
        StoreLeaveBalanceCarryForwardRequest $request
    ): JsonResponse {
        $this->ensureManagePermission(
            $request
        );


        $validated =
            $request->validated();


        $tenantId = (int) $request
            ->user()
            ->tenant_id;


        $tenant = $request
            ->user()
            ->tenant;


        abort_unless(
            $tenant !== null,
            403,
            'تعذر تحديد الشركة الحالية.'
        );


        $leaveType = LeaveType::query()
            ->where(
                'tenant_id',
                $tenantId
            )
            ->where(
                'is_active',
                true
            )
            ->where(
                'requires_balance',
                true
            )
            ->where(
                'allow_carry_forward',
                true
            )
            ->findOrFail(
                $validated['leave_type_id']
            );


        $result = $this
            ->leaveBalanceService
            ->bulkCarryForward(
                tenant:
                    $tenant,

                leaveType:
                    $leaveType,

                employeeIds:
                    $validated['employee_ids'],

                sourceYear:
                    (int) $validated['source_year'],

                targetYear:
                    (int) $validated['target_year'],

                reason:
                    $validated['reason'],

                actor:
                    $request->user()
            );


        $processedCount =
            (int) $result['processed_count'];


        $skippedCount =
            (int) $result['skipped_count'];


        $message =
            "تم إقفال وترحيل أرصدة {$processedCount} موظف بنجاح.";


        if ($skippedCount > 0) {
            $message .=
                " تم تجاوز {$skippedCount} موظف لعدم وجود رصيد متاح.";
        }


        return response()->json([
            'success' =>
                true,

            'message' =>
                $message,

            'batch_uuid' =>
                $result['batch_uuid'],

            'processed_count' =>
                $processedCount,

            'skipped_count' =>
                $skippedCount,

            'processed_employee_ids' =>
                $result[
                    'processed_employee_ids'
                ],

            'skipped_employee_ids' =>
                $result[
                    'skipped_employee_ids'
                ],
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Employee Balance History
    |--------------------------------------------------------------------------
    */

    public function history(
        Request $request,
        Employee $employee
    ): JsonResponse {
        $this->ensureViewPermission(
            $request
        );

        $tenantId = (int) $request
            ->user()
            ->tenant_id;

        abort_unless(
            (int) $employee->tenant_id ===
            $tenantId,
            404
        );

        $year = (int) $request->input(
            'year',
            now()->year
        );

        $leaveTypeId = $request->integer(
            'leave_type_id'
        );

        $perPage = min(
            max(
                $request->integer(
                    'per_page',
                    15
                ),
                10
            ),
            100
        );

        $transactions =
            LeaveBalanceTransaction::query()
                ->join(
                    'leave_balances',
                    'leave_balances.id',
                    '=',
                    'leave_balance_transactions.leave_balance_id'
                )
                ->join(
                    'leave_types',
                    'leave_types.id',
                    '=',
                    'leave_balances.leave_type_id'
                )
                ->leftJoin(
                    'users as transaction_users',
                    'transaction_users.id',
                    '=',
                    'leave_balance_transactions.created_by'
                )
                ->where(
                    'leave_balance_transactions.tenant_id',
                    $tenantId
                )
                ->where(
                    'leave_balances.tenant_id',
                    $tenantId
                )
                ->where(
                    'leave_balances.employee_id',
                    $employee->id
                )
                ->where(
                    'leave_balances.year',
                    $year
                )
                ->when(
                    $leaveTypeId,
                    fn ($query) =>
                        $query->where(
                            'leave_balances.leave_type_id',
                            $leaveTypeId
                        )
                )
                ->select([
                    'leave_balance_transactions.id',
                    'leave_balance_transactions.uuid',
                    'leave_balance_transactions.type',
                    'leave_balance_transactions.amount',
                    'leave_balance_transactions.balance_after',
                    'leave_balance_transactions.effective_date',
                    'leave_balance_transactions.notes',
                    'leave_balance_transactions.metadata',
                    'leave_balance_transactions.created_at',

                    'leave_balances.year',

                    'leave_types.id as leave_type_id',
                    'leave_types.code as leave_type_code',
                    'leave_types.name as leave_type_name',

                    'transaction_users.name as created_by_name',
                ])
                ->orderByDesc(
                    'leave_balance_transactions.id'
                )
                ->paginate(
                    $perPage
                );

        $transactions->through(
            function (
                LeaveBalanceTransaction $transaction
            ) {
                return [
                    'id' =>
                        $transaction->id,

                    'uuid' =>
                        $transaction->uuid,

                    'type' =>
                        $transaction->type,

                    'type_label' =>
                        $this->transactionTypeLabel(
                            $transaction->type
                        ),

                    'amount' =>
                        round(
                            (float) $transaction->amount,
                            2
                        ),

                    'balance_after' =>
                        round(
                            (float) $transaction
                                ->balance_after,
                            2
                        ),

                    'effective_date' =>
                        $transaction
                            ->effective_date,

                    'notes' =>
                        $transaction->notes,

                    'created_at' =>
                        $transaction
                            ->created_at
                            ?->toISOString(),

                    'created_by_name' =>
                        $transaction
                            ->created_by_name ??
                        'النظام',

                    'leave_type' => [
                        'id' =>
                            $transaction
                                ->leave_type_id,

                        'code' =>
                            $transaction
                                ->leave_type_code,

                        'name' =>
                            $transaction
                                ->leave_type_name,
                    ],
                ];
            }
        );

        return response()->json([
            'success' =>
                true,

            'employee' => [
                'id' =>
                    $employee->id,

                'employee_number' =>
                    $employee->employee_number,

                'name' =>
                    $this->employeeName(
                        $employee
                    ),
            ],

            'transactions' =>
                $transactions,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    */

    private function ensureViewPermission(
        Request $request
    ): void {
        $user =
            $request->user();

        abort_unless(
            $user !== null &&
            (
                $user->can('leave.view') ||
                $user->can('leave.manage')
            ),
            403,
            'غير مصرح لك بعرض أرصدة الإجازات.'
        );
    }


    private function ensureManagePermission(
        Request $request
    ): void {
        abort_unless(
            $request
                ->user()
                ?->can(
                    'leave.manage'
                ),
            403,
            'غير مصرح لك بإدارة أرصدة الإجازات.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function employeeName(
        Employee $employee
    ): string {
        return collect([
            $employee->first_name,
            $employee->father_name,
            $employee->grandfather_name,
            $employee->family_name,
        ])
            ->filter()
            ->implode(' ');
    }


    private function transactionTypeLabel(
        ?string $type
    ): string {
        return match ($type) {
            'opening' =>
                'رصيد افتتاحي',

            'accrual' =>
                'استحقاق',

            'carry_forward' =>
                'رصيد مرحّل',

            'usage' =>
                'استخدام رصيد',

            'reversal' =>
                'إعادة رصيد',

            'adjustment' =>
                'تسوية يدوية',

            'expiry' =>
                'انتهاء رصيد',

            default =>
                'حركة رصيد',
        };
    }
}