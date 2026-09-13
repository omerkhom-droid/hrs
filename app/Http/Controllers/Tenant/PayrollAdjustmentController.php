<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StorePayrollAdjustmentRequest;
use App\Http\Requests\Tenant\UpdatePayrollAdjustmentRequest;
use App\Models\Employee;
use App\Models\PayrollAdjustment;
use App\Models\PayrollPeriod;
use App\Models\SalaryComponent;
use App\Services\HR\PayrollAdjustmentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use LogicException;

class PayrollAdjustmentController extends Controller
{
    public function __construct(
        private readonly PayrollAdjustmentService $adjustmentService
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | الواجهة
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request
    ): View {
        abort_unless(
            $request->user()->can(
                'payroll.view'
            ),
            403
        );

        return view(
            'tenant.payroll.adjustments.index'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | بيانات الجدول
    |--------------------------------------------------------------------------
    */

    public function data(
        Request $request
    ): JsonResponse {
        abort_unless(
            $request->user()->can(
                'payroll.view'
            ),
            403
        );

        $tenantId =
            (int) $request
                ->user()
                ->tenant_id;

        $search =
            trim(
                (string) $request->get(
                    'search',
                    ''
                )
            );

        $status =
            $request->get('status');

        $type =
            $request->get('type');

        $periodId =
            $request->integer(
                'payroll_period_id'
            );

        $employeeId =
            $request->integer(
                'employee_id'
            );

        $dateFrom =
            $request->get(
                'date_from'
            );

        $dateTo =
            $request->get(
                'date_to'
            );

        $perPage =
            min(
                max(
                    $request->integer(
                        'per_page',
                        15
                    ),
                    10
                ),
                100
            );

        $query =
            PayrollAdjustment::withoutGlobalScopes()
                ->with([
                    'employee',
                    'payrollPeriod',
                    'salaryComponent',
                    'createdBy:id,name',
                    'approvedBy:id,name',
                    'appliedPayrollItem.payrollRun',
                ])
                ->where(
                    'tenant_id',
                    $tenantId
                )
                ->whereNull(
                    'deleted_at'
                )
                ->latest('id');

        if ($search !== '') {
            $query->where(
                function ($query) use ($search) {
                    $query
                        ->where(
                            'adjustment_number',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'reason',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'notes',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhereHas(
                            'employee',
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
                        );
                }
            );
        }

        if (
            $status &&
            in_array(
                $status,
                [
                    PayrollAdjustment::STATUS_DRAFT,
                    PayrollAdjustment::STATUS_PENDING,
                    PayrollAdjustment::STATUS_APPROVED,
                    PayrollAdjustment::STATUS_APPLIED,
                    PayrollAdjustment::STATUS_REJECTED,
                    PayrollAdjustment::STATUS_CANCELLED,
                ],
                true
            )
        ) {
            $query->where(
                'status',
                $status
            );
        }

        if (
            $type &&
            in_array(
                $type,
                [
                    PayrollAdjustment::TYPE_EARNING,
                    PayrollAdjustment::TYPE_DEDUCTION,
                ],
                true
            )
        ) {
            $query->where(
                'type',
                $type
            );
        }

        if ($periodId) {
            $query->where(
                'payroll_period_id',
                $periodId
            );
        }

        if ($employeeId) {
            $query->where(
                'employee_id',
                $employeeId
            );
        }

        if ($dateFrom) {
            $query->whereDate(
                'effective_date',
                '>=',
                $dateFrom
            );
        }

        if ($dateTo) {
            $query->whereDate(
                'effective_date',
                '<=',
                $dateTo
            );
        }

        $paginator =
            $query->paginate(
                $perPage
            );

        $paginator->setCollection(
            $paginator
                ->getCollection()
                ->map(
                    fn (
                        PayrollAdjustment $adjustment
                    ) =>
                        $this->adjustmentPayload(
                            $adjustment,
                            $request
                        )
                )
        );

        return response()->json(
            $paginator
        );
    }


    /*
    |--------------------------------------------------------------------------
    | خيارات النموذج والفلاتر
    |--------------------------------------------------------------------------
    */

    public function options(
        Request $request
    ): JsonResponse {
        abort_unless(
            $request->user()->can(
                'payroll.view'
            ),
            403
        );

        $tenantId =
            (int) $request
                ->user()
                ->tenant_id;

        $employees =
            Employee::withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $tenantId
                )
                ->whereNull(
                    'deleted_at'
                )
                ->whereNotIn(
                    'employment_status',
                    ['terminated']
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
                    'employment_status',
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

                        'employment_status' =>
                            $employee
                                ->employment_status,
                    ]
                )
                ->values();

        $periods =
            PayrollPeriod::withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $tenantId
                )
                ->whereNull(
                    'deleted_at'
                )
                ->where(
                    'is_locked',
                    false
                )
                ->whereNotIn(
                    'status',
                    [
                        PayrollPeriod::STATUS_PAID,
                        PayrollPeriod::STATUS_CLOSED,
                        PayrollPeriod::STATUS_CANCELLED,
                    ]
                )
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->get([
                    'id',
                    'code',
                    'name',
                    'year',
                    'month',
                    'start_date',
                    'end_date',
                    'payment_date',
                    'status',
                    'is_locked',
                ])
                ->map(
                    fn (PayrollPeriod $period) => [
                        'id' =>
                            $period->id,

                        'code' =>
                            $period->code,

                        'name' =>
                            $period->name,

                        'year' =>
                            $period->year,

                        'month' =>
                            $period->month,

                        'start_date' =>
                            $period->start_date
                                ?->toDateString(),

                        'end_date' =>
                            $period->end_date
                                ?->toDateString(),

                        'payment_date' =>
                            $period->payment_date
                                ?->toDateString(),

                        'status' =>
                            $period->status,

                        'status_label' =>
                            $this->periodStatusLabel(
                                $period->status
                            ),
                    ]
                )
                ->values();

        $components =
            SalaryComponent::withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $tenantId
                )
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    'affects_net_salary',
                    true
                )
                ->whereNull(
                    'deleted_at'
                )
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get([
                    'id',
                    'code',
                    'name',
                    'type',
                    'category',
                    'is_active',
                    'affects_net_salary',
                ])
                ->map(
                    fn (
                        SalaryComponent $component
                    ) => [
                        'id' =>
                            $component->id,

                        'code' =>
                            $component->code,

                        'name' =>
                            $component->name,

                        'type' =>
                            $component->type,

                        'type_label' =>
                            $component->type ===
                                PayrollAdjustment::TYPE_EARNING
                                ? 'استحقاق'
                                : 'خصم',

                        'category' =>
                            $component->category,
                    ]
                )
                ->values();

        return response()->json([
            'success' =>
                true,

            'employees' =>
                $employees,

            'periods' =>
                $periods,

            'components' =>
                $components,

            'default_currency' =>
                strtoupper(
                    $request
                        ->user()
                        ->tenant
                        ?->currency_code
                    ?? 'SAR'
                ),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | إنشاء التسوية
    |--------------------------------------------------------------------------
    */

    public function store(
        StorePayrollAdjustmentRequest $request
    ): JsonResponse {
        try {
            $adjustment =
                $this->adjustmentService
                    ->create(
                        $request->validated(),
                        $request->user()
                    );

            return response()->json([
                'success' =>
                    true,

                'message' =>
                    'تم إنشاء تسوية الرواتب بنجاح.',

                'adjustment' =>
                    $this->adjustmentPayload(
                        $adjustment,
                        $request,
                        true
                    ),
            ], 201);
        } catch (
            LogicException |
            AuthorizationException $exception
        ) {
            return $this->businessError(
                $exception
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | عرض التسوية
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        PayrollAdjustment $payrollAdjustment
    ): JsonResponse {
        $this->authorizeAdjustmentView(
            $request,
            $payrollAdjustment
        );

        $payrollAdjustment->load([
            'employee',
            'payrollPeriod',
            'salaryComponent',
            'createdBy:id,name',
            'approvedBy:id,name',
            'appliedPayrollItem.payrollRun',
        ]);

        return response()->json([
            'success' =>
                true,

            'adjustment' =>
                $this->adjustmentPayload(
                    $payrollAdjustment,
                    $request,
                    true
                ),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | تعديل التسوية
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdatePayrollAdjustmentRequest $request,
        PayrollAdjustment $payrollAdjustment
    ): JsonResponse {
        try {
            $adjustment =
                $this->adjustmentService
                    ->update(
                        $payrollAdjustment,
                        $request->validated(),
                        $request->user()
                    );

            return response()->json([
                'success' =>
                    true,

                'message' =>
                    'تم تحديث تسوية الرواتب بنجاح.',

                'adjustment' =>
                    $this->adjustmentPayload(
                        $adjustment,
                        $request,
                        true
                    ),
            ]);
        } catch (
            LogicException |
            AuthorizationException $exception
        ) {
            return $this->businessError(
                $exception
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | إرسال التسوية للاعتماد
    |--------------------------------------------------------------------------
    */

    public function submit(
        Request $request,
        PayrollAdjustment $payrollAdjustment
    ): JsonResponse {
        try {
            $adjustment =
                $this->adjustmentService
                    ->submit(
                        $payrollAdjustment,
                        $request->user()
                    );

            return $this->actionResponse(
                $request,
                $adjustment,
                'تم إرسال التسوية للاعتماد.'
            );
        } catch (
            LogicException |
            AuthorizationException $exception
        ) {
            return $this->businessError(
                $exception
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | اعتماد التسوية
    |--------------------------------------------------------------------------
    */

    public function approve(
        Request $request,
        PayrollAdjustment $payrollAdjustment
    ): JsonResponse {
        try {
            $adjustment =
                $this->adjustmentService
                    ->approve(
                        $payrollAdjustment,
                        $request->user()
                    );

            return $this->actionResponse(
                $request,
                $adjustment,
                'تم اعتماد التسوية بنجاح.'
            );
        } catch (
            LogicException |
            AuthorizationException $exception
        ) {
            return $this->businessError(
                $exception
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | رفض التسوية
    |--------------------------------------------------------------------------
    */

    public function reject(
        Request $request,
        PayrollAdjustment $payrollAdjustment
    ): JsonResponse {
        $validated =
            $request->validate([
                'reason' => [
                    'required',
                    'string',
                    'max:1000',
                ],
            ], [
                'reason.required' =>
                    'سبب رفض التسوية مطلوب.',

                'reason.max' =>
                    'سبب الرفض يجب ألا يتجاوز 1000 حرف.',
            ]);

        try {
            $adjustment =
                $this->adjustmentService
                    ->reject(
                        $payrollAdjustment,
                        $request->user(),
                        $validated['reason']
                    );

            return $this->actionResponse(
                $request,
                $adjustment,
                'تم رفض التسوية.'
            );
        } catch (
            LogicException |
            AuthorizationException $exception
        ) {
            return $this->businessError(
                $exception
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | إعادة التسوية إلى مسودة
    |--------------------------------------------------------------------------
    */

    public function returnToDraft(
        Request $request,
        PayrollAdjustment $payrollAdjustment
    ): JsonResponse {
        try {
            $adjustment =
                $this->adjustmentService
                    ->returnToDraft(
                        $payrollAdjustment,
                        $request->user()
                    );

            return $this->actionResponse(
                $request,
                $adjustment,
                'تمت إعادة التسوية إلى المسودة.'
            );
        } catch (
            LogicException |
            AuthorizationException $exception
        ) {
            return $this->businessError(
                $exception
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | إلغاء التسوية
    |--------------------------------------------------------------------------
    */

    public function cancel(
        Request $request,
        PayrollAdjustment $payrollAdjustment
    ): JsonResponse {
        $validated =
            $request->validate([
                'reason' => [
                    'required',
                    'string',
                    'max:1000',
                ],
            ], [
                'reason.required' =>
                    'سبب إلغاء التسوية مطلوب.',

                'reason.max' =>
                    'سبب الإلغاء يجب ألا يتجاوز 1000 حرف.',
            ]);

        try {
            $adjustment =
                $this->adjustmentService
                    ->cancel(
                        $payrollAdjustment,
                        $request->user(),
                        $validated['reason']
                    );

            return $this->actionResponse(
                $request,
                $adjustment,
                'تم إلغاء التسوية.'
            );
        } catch (
            LogicException |
            AuthorizationException $exception
        ) {
            return $this->businessError(
                $exception
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | حذف التسوية
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        PayrollAdjustment $payrollAdjustment
    ): JsonResponse {
        try {
            $this->adjustmentService
                ->delete(
                    $payrollAdjustment,
                    $request->user()
                );

            return response()->json([
                'success' =>
                    true,

                'message' =>
                    'تم حذف تسوية الرواتب بنجاح.',
            ]);
        } catch (
            LogicException |
            AuthorizationException $exception
        ) {
            return $this->businessError(
                $exception
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | تحويل بيانات التسوية
    |--------------------------------------------------------------------------
    */

    private function adjustmentPayload(
        PayrollAdjustment $adjustment,
        Request $request,
        bool $details = false
    ): array {
        $employee =
            $adjustment->employee;

        $period =
            $adjustment->payrollPeriod;

        $component =
            $adjustment->salaryComponent;

        $appliedItem =
            $adjustment
                ->appliedPayrollItem;

        $canManage =
            $request->user()->can(
                'payroll.manage'
            );

        $canApprove =
            $request->user()->can(
                'payroll.approve'
            );

        $payload = [
            'id' =>
                $adjustment->id,

            'uuid' =>
                $adjustment->uuid,

            'adjustment_number' =>
                $adjustment
                    ->adjustment_number,

            'employee_id' =>
                $adjustment->employee_id,

            'employee' =>
                $employee
                    ? [
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
                    : null,

            'payroll_period_id' =>
                $adjustment
                    ->payroll_period_id,

            'period' =>
                $period
                    ? [
                        'id' =>
                            $period->id,

                        'code' =>
                            $period->code,

                        'name' =>
                            $period->name,

                        'year' =>
                            $period->year,

                        'month' =>
                            $period->month,

                        'start_date' =>
                            $period
                                ->start_date
                                ?->toDateString(),

                        'end_date' =>
                            $period
                                ->end_date
                                ?->toDateString(),
                    ]
                    : null,

            'salary_component_id' =>
                $adjustment
                    ->salary_component_id,

            'salary_component' =>
                $component
                    ? [
                        'id' =>
                            $component->id,

                        'code' =>
                            $component->code,

                        'name' =>
                            $component->name,

                        'type' =>
                            $component->type,

                        'category' =>
                            $component->category,
                    ]
                    : null,

            'type' =>
                $adjustment->type,

            'type_label' =>
                $adjustment->type ===
                    PayrollAdjustment::TYPE_EARNING
                    ? 'استحقاق'
                    : 'خصم',

            'amount' =>
                (float) $adjustment->amount,

            'currency_code' =>
                $adjustment->currency_code,

            'effective_date' =>
                $adjustment
                    ->effective_date
                    ?->toDateString(),

            'status' =>
                $adjustment->status,

            'status_label' =>
                $this->statusLabel(
                    $adjustment->status
                ),

            'reason' =>
                $adjustment->reason,

            'notes' =>
                $adjustment->notes,

            'approved_at' =>
                $adjustment
                    ->approved_at
                    ?->toDateTimeString(),

            'created_at' =>
                $adjustment
                    ->created_at
                    ?->toDateTimeString(),

            'applied_payroll_item_id' =>
                $adjustment
                    ->applied_payroll_item_id,

            'applied_run' =>
                $appliedItem
                    ? [
                        'payroll_item_id' =>
                            $appliedItem->id,

                        'payroll_run_id' =>
                            $appliedItem
                                ->payroll_run_id,

                        'run_number' =>
                            $appliedItem
                                ->payrollRun
                                ?->run_number,
                    ]
                    : null,

            'can_edit' =>
                $canManage
                &&
                $adjustment->status ===
                    PayrollAdjustment::STATUS_DRAFT
                &&
                !$adjustment
                    ->applied_payroll_item_id,

            'can_submit' =>
                $canManage
                &&
                $adjustment->status ===
                    PayrollAdjustment::STATUS_DRAFT
                &&
                !$adjustment
                    ->applied_payroll_item_id,

            'can_approve' =>
                $canApprove
                &&
                $adjustment->status ===
                    PayrollAdjustment::STATUS_PENDING,

            'can_reject' =>
                $canApprove
                &&
                $adjustment->status ===
                    PayrollAdjustment::STATUS_PENDING,

            'can_return_to_draft' =>
                $canManage
                &&
                $adjustment->status ===
                    PayrollAdjustment::STATUS_REJECTED
                &&
                !$adjustment
                    ->applied_payroll_item_id,

            'can_cancel' =>
                $canManage
                &&
                !in_array(
                    $adjustment->status,
                    [
                        PayrollAdjustment::STATUS_APPLIED,
                        PayrollAdjustment::STATUS_CANCELLED,
                    ],
                    true
                )
                &&
                !$adjustment
                    ->applied_payroll_item_id,

            'can_delete' =>
                $canManage
                &&
                $adjustment->status ===
                    PayrollAdjustment::STATUS_DRAFT
                &&
                !$adjustment
                    ->applied_payroll_item_id,
        ];

        if ($details) {
            $payload['audit'] = [
                'created_by' =>
                    $adjustment
                        ->createdBy
                        ?->name,

                'approved_by' =>
                    $adjustment
                        ->approvedBy
                        ?->name,
            ];

            $payload['metadata'] =
                $adjustment->metadata;
        }

        return $payload;
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function actionResponse(
        Request $request,
        PayrollAdjustment $adjustment,
        string $message
    ): JsonResponse {
        $adjustment->load([
            'employee',
            'payrollPeriod',
            'salaryComponent',
            'createdBy:id,name',
            'approvedBy:id,name',
            'appliedPayrollItem.payrollRun',
        ]);

        return response()->json([
            'success' =>
                true,

            'message' =>
                $message,

            'adjustment' =>
                $this->adjustmentPayload(
                    $adjustment,
                    $request,
                    true
                ),
        ]);
    }


    private function authorizeAdjustmentView(
        Request $request,
        PayrollAdjustment $adjustment
    ): void {
        abort_unless(
            $request->user()->can(
                'payroll.view'
            ),
            403
        );

        abort_unless(
            (int) $request
                ->user()
                ->tenant_id
            ===
            (int) $adjustment
                ->tenant_id,
            404
        );
    }


    private function businessError(
        LogicException | AuthorizationException $exception
    ): JsonResponse {
        return response()->json([
            'success' =>
                false,

            'message' =>
                $exception->getMessage(),
        ], $exception instanceof
            AuthorizationException
                ? 403
                : 422
        );
    }


    private function employeeName(
        Employee $employee
    ): string {
        if (
            isset($employee->full_name) &&
            trim(
                (string) $employee->full_name
            ) !== ''
        ) {
            return trim(
                (string) $employee->full_name
            );
        }

        return trim(
            implode(
                ' ',
                array_filter([
                    $employee->first_name,
                    $employee->father_name,
                    $employee->grandfather_name,
                    $employee->family_name,
                ])
            )
        );
    }


    private function statusLabel(
        string $status
    ): string {
        return match ($status) {
            PayrollAdjustment::STATUS_DRAFT =>
                'مسودة',

            PayrollAdjustment::STATUS_PENDING =>
                'بانتظار الاعتماد',

            PayrollAdjustment::STATUS_APPROVED =>
                'معتمدة',

            PayrollAdjustment::STATUS_APPLIED =>
                'مطبقة',

            PayrollAdjustment::STATUS_REJECTED =>
                'مرفوضة',

            PayrollAdjustment::STATUS_CANCELLED =>
                'ملغاة',

            default =>
                'غير محدد',
        };
    }


    private function periodStatusLabel(
        string $status
    ): string {
        return match ($status) {
            PayrollPeriod::STATUS_DRAFT =>
                'مسودة',

            PayrollPeriod::STATUS_OPEN =>
                'مفتوحة',

            PayrollPeriod::STATUS_PROCESSING =>
                'قيد المعالجة',

            PayrollPeriod::STATUS_REVIEW =>
                'قيد المراجعة',

            PayrollPeriod::STATUS_APPROVED =>
                'معتمدة',

            PayrollPeriod::STATUS_PAID =>
                'مدفوعة',

            PayrollPeriod::STATUS_CLOSED =>
                'مغلقة',

            PayrollPeriod::STATUS_CANCELLED =>
                'ملغاة',

            default =>
                'غير محدد',
        };
    }
}