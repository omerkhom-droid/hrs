<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StorePayrollRunRequest;
use App\Models\Employee;
use App\Models\EmployeeSalaryStructure;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Services\HR\PayrollRunService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use LogicException;

class PayrollRunController extends Controller
{
    public function __construct(
        private readonly PayrollRunService $payrollRunService
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
            'tenant.payroll.runs.index'
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

        $year =
            $request->integer('year');

        $month =
            $request->integer('month');

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
            PayrollRun::withoutGlobalScopes()
                ->with([
                    'period',
                    'createdBy:id,name',
                    'calculatedBy:id,name',
                    'approvedBy:id,name',
                    'paidBy:id,name',
                ])
                ->withCount([
                    'items',

                    'items as exception_items_count' =>
                        fn ($query) =>
                            $query->where(
                                'status',
                                PayrollRunItem::STATUS_EXCEPTION
                            ),

                    'items as calculated_items_count' =>
                        fn ($query) =>
                            $query->where(
                                'status',
                                PayrollRunItem::STATUS_CALCULATED
                            ),

                    'items as approved_items_count' =>
                        fn ($query) =>
                            $query->where(
                                'status',
                                PayrollRunItem::STATUS_APPROVED
                            ),

                    'items as paid_items_count' =>
                        fn ($query) =>
                            $query->where(
                                'status',
                                PayrollRunItem::STATUS_PAID
                            ),
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
                            'run_number',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'notes',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhereHas(
                            'period',
                            function ($query) use ($search) {
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

        if (
            $status &&
            in_array(
                $status,
                [
                    PayrollRun::STATUS_DRAFT,
                    PayrollRun::STATUS_CALCULATING,
                    PayrollRun::STATUS_CALCULATED,
                    PayrollRun::STATUS_REVIEW,
                    PayrollRun::STATUS_APPROVED,
                    PayrollRun::STATUS_PAID,
                    PayrollRun::STATUS_CANCELLED,
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
                    PayrollRun::TYPE_REGULAR,
                    PayrollRun::TYPE_OFF_CYCLE,
                    PayrollRun::TYPE_FINAL_SETTLEMENT,
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

        if ($year) {
            $query->whereHas(
                'period',
                fn ($query) =>
                    $query->where(
                        'year',
                        $year
                    )
            );
        }

        if (
            $month >= 1 &&
            $month <= 12
        ) {
            $query->whereHas(
                'period',
                fn ($query) =>
                    $query->where(
                        'month',
                        $month
                    )
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
                    fn (PayrollRun $run) =>
                        $this->runPayload(
                            $run,
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
    | خيارات الإنشاء والفلاتر
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

        $periods =
            PayrollPeriod::withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $tenantId
                )
                ->whereNull(
                    'deleted_at'
                )
                ->whereIn(
                    'status',
                    [
                        PayrollPeriod::STATUS_OPEN,
                        PayrollPeriod::STATUS_PROCESSING,
                        PayrollPeriod::STATUS_REVIEW,
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

                        'is_locked' =>
                            (bool) $period->is_locked,

                        'can_create_run' =>
                            $period->status ===
                                PayrollPeriod::STATUS_OPEN
                            &&
                            !$period->is_locked,
                    ]
                )
                ->values();

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
                ->whereExists(
                    function ($query) use ($tenantId) {
                        $query
                            ->selectRaw('1')
                            ->from(
                                'employee_salary_structures'
                            )
                            ->whereColumn(
                                'employee_salary_structures.employee_id',
                                'employees.id'
                            )
                            ->where(
                                'employee_salary_structures.tenant_id',
                                $tenantId
                            )
                            ->where(
                                'employee_salary_structures.status',
                                EmployeeSalaryStructure::STATUS_ACTIVE
                            )
                            ->whereNull(
                                'employee_salary_structures.deleted_at'
                            );
                    }
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
                            $employee->employee_number,

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

        $tenant =
            $request->user()->tenant;

        return response()->json([
            'success' =>
                true,

            'periods' =>
                $periods,

            'employees' =>
                $employees,

            'default_currency' =>
                strtoupper(
                    $tenant?->currency_code
                    ?? 'SAR'
                ),

            'types' => [
                [
                    'value' =>
                        PayrollRun::TYPE_REGULAR,

                    'label' =>
                        'تشغيل عادي',
                ],
                [
                    'value' =>
                        PayrollRun::TYPE_OFF_CYCLE,

                    'label' =>
                        'تشغيل خارج الدورة',
                ],
                [
                    'value' =>
                        PayrollRun::TYPE_FINAL_SETTLEMENT,

                    'label' =>
                        'تسوية نهائية',
                ],
            ],
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | إنشاء التشغيل
    |--------------------------------------------------------------------------
    */

    public function store(
        StorePayrollRunRequest $request
    ): JsonResponse {
        try {
            $run =
                $this->payrollRunService
                    ->create(
                        $request->validated(),
                        $request->user()
                    );

            return response()->json([
                'success' =>
                    true,

                'message' =>
                    'تم إنشاء تشغيل الرواتب بنجاح.',

                'payroll_run' =>
                    $this->runPayload(
                        $run,
                        $request
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
    | تفاصيل التشغيل
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        PayrollRun $payrollRun
    ): JsonResponse {
        $this->authorizeRunView(
            $request,
            $payrollRun
        );

        $payrollRun->load([
            'period',
            'createdBy:id,name',
            'calculatedBy:id,name',
            'approvedBy:id,name',
            'paidBy:id,name',
        ]);

        $payrollRun->loadCount([
            'items',

            'items as exception_items_count' =>
                fn ($query) =>
                    $query->where(
                        'status',
                        PayrollRunItem::STATUS_EXCEPTION
                    ),

            'items as calculated_items_count' =>
                fn ($query) =>
                    $query->where(
                        'status',
                        PayrollRunItem::STATUS_CALCULATED
                    ),

            'items as approved_items_count' =>
                fn ($query) =>
                    $query->where(
                        'status',
                        PayrollRunItem::STATUS_APPROVED
                    ),

            'items as paid_items_count' =>
                fn ($query) =>
                    $query->where(
                        'status',
                        PayrollRunItem::STATUS_PAID
                    ),
        ]);

        return response()->json([
            'success' =>
                true,

            'payroll_run' =>
                $this->runPayload(
                    $payrollRun,
                    $request,
                    true
                ),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | بنود الموظفين داخل التشغيل
    |--------------------------------------------------------------------------
    */

    public function itemsData(
        Request $request,
        PayrollRun $payrollRun
    ): JsonResponse {
        $this->authorizeRunView(
            $request,
            $payrollRun
        );

        $search =
            trim(
                (string) $request->get(
                    'search',
                    ''
                )
            );

        $status =
            $request->get('status');

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
            PayrollRunItem::withoutGlobalScopes()
                ->with([
                    'employee',
                    'salaryStructure:id,version,effective_from,effective_to',
                ])
                ->withCount(
                    'components'
                )
                ->where(
                    'tenant_id',
                    $payrollRun->tenant_id
                )
                ->where(
                    'payroll_run_id',
                    $payrollRun->id
                )
                ->whereNull(
                    'deleted_at'
                )
                ->orderBy('id');

        if ($search !== '') {
            $query->whereHas(
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

        if (
            $status &&
            in_array(
                $status,
                [
                    PayrollRunItem::STATUS_PENDING,
                    PayrollRunItem::STATUS_CALCULATED,
                    PayrollRunItem::STATUS_EXCEPTION,
                    PayrollRunItem::STATUS_APPROVED,
                    PayrollRunItem::STATUS_PAID,
                ],
                true
            )
        ) {
            $query->where(
                'status',
                $status
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
                    fn (PayrollRunItem $item) =>
                        $this->itemPayload(
                            $item
                        )
                )
        );

        return response()->json(
            $paginator
        );
    }


    /*
    |--------------------------------------------------------------------------
    | تفاصيل راتب موظف داخل التشغيل
    |--------------------------------------------------------------------------
    */

    public function showItem(
        Request $request,
        PayrollRun $payrollRun,
        PayrollRunItem $payrollItem
    ): JsonResponse {
        $this->authorizeRunView(
            $request,
            $payrollRun
        );

        abort_unless(
            (int) $payrollItem->tenant_id ===
                (int) $payrollRun->tenant_id
            &&
            (int) $payrollItem->payroll_run_id ===
                (int) $payrollRun->id,
            404
        );

        $payrollItem->load([
            'employee',
            'salaryStructure',
            'components' =>
                fn ($query) =>
                    $query->orderBy('id'),
            'components.salaryComponent',
        ]);

        return response()->json([
            'success' =>
                true,

            'payroll_item' =>
                array_merge(
                    $this->itemPayload(
                        $payrollItem
                    ),
                    [
                        'calculation_snapshot' =>
                            $payrollItem
                                ->calculation_snapshot,

                        'metadata' =>
                            $payrollItem->metadata,

                        'components' =>
                            $payrollItem
                                ->components
                                ->map(
                                    fn ($component) => [
                                        'id' =>
                                            $component->id,

                                        'salary_component_id' =>
                                            $component
                                                ->salary_component_id,

                                        'component_code' =>
                                            $component
                                                ->component_code,

                                        'component_name' =>
                                            $component
                                                ->component_name,

                                        'type' =>
                                            $component->type,

                                        'type_label' =>
                                            $component->type ===
                                                'earning'
                                                ? 'استحقاق'
                                                : 'خصم',

                                        'category' =>
                                            $component->category,

                                        'source' =>
                                            $component->source,

                                        'source_label' =>
                                            $this->componentSourceLabel(
                                                $component->source
                                            ),

                                        'quantity' =>
                                            $component->quantity,

                                        'rate' =>
                                            $component->rate,

                                        'percentage' =>
                                            $component->percentage,

                                        'amount' =>
                                            (float) $component->amount,

                                        'is_taxable' =>
                                            (bool) $component->is_taxable,

                                        'is_subject_to_insurance' =>
                                            (bool) $component
                                                ->is_subject_to_insurance,

                                        'metadata' =>
                                            $component->metadata,
                                    ]
                                )
                                ->values(),
                    ]
                ),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | حساب التشغيل
    |--------------------------------------------------------------------------
    */

    public function calculate(
        Request $request,
        PayrollRun $payrollRun
    ): JsonResponse {
        try {
            $run =
                $this->payrollRunService
                    ->calculate(
                        $payrollRun,
                        $request->user()
                    );

            return $this->actionResponse(
                $request,
                $run,
                'تم حساب تشغيل الرواتب بنجاح.'
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
    | إرسال للمراجعة
    |--------------------------------------------------------------------------
    */

    public function submitForReview(
        Request $request,
        PayrollRun $payrollRun
    ): JsonResponse {
        try {
            $run =
                $this->payrollRunService
                    ->submitForReview(
                        $payrollRun,
                        $request->user()
                    );

            return $this->actionResponse(
                $request,
                $run,
                'تم إرسال تشغيل الرواتب للمراجعة.'
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
    | إعادة للحساب
    |--------------------------------------------------------------------------
    */

    public function returnToCalculation(
        Request $request,
        PayrollRun $payrollRun
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
                    'سبب إعادة التشغيل للحساب مطلوب.',

                'reason.max' =>
                    'السبب يجب ألا يتجاوز 1000 حرف.',
            ]);

        try {
            $run =
                $this->payrollRunService
                    ->returnToCalculation(
                        $payrollRun,
                        $request->user(),
                        $validated['reason']
                    );

            return $this->actionResponse(
                $request,
                $run,
                'تمت إعادة تشغيل الرواتب إلى مرحلة الحساب.'
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
    | اعتماد التشغيل
    |--------------------------------------------------------------------------
    */

    public function approve(
        Request $request,
        PayrollRun $payrollRun
    ): JsonResponse {
        try {
            $run =
                $this->payrollRunService
                    ->approve(
                        $payrollRun,
                        $request->user()
                    );

            return $this->actionResponse(
                $request,
                $run,
                'تم اعتماد تشغيل الرواتب بنجاح.'
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
    | تسجيل الدفع
    |--------------------------------------------------------------------------
    */

    public function markPaid(
        Request $request,
        PayrollRun $payrollRun
    ): JsonResponse {
        try {
            $run =
                $this->payrollRunService
                    ->markPaid(
                        $payrollRun,
                        $request->user()
                    );

            return $this->actionResponse(
                $request,
                $run,
                'تم تسجيل تشغيل الرواتب كمدفوع.'
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
    | إلغاء التشغيل
    |--------------------------------------------------------------------------
    */

    public function cancel(
        Request $request,
        PayrollRun $payrollRun
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
                    'سبب إلغاء تشغيل الرواتب مطلوب.',

                'reason.max' =>
                    'السبب يجب ألا يتجاوز 1000 حرف.',
            ]);

        try {
            $run =
                $this->payrollRunService
                    ->cancel(
                        $payrollRun,
                        $request->user(),
                        $validated['reason']
                    );

            return $this->actionResponse(
                $request,
                $run,
                'تم إلغاء تشغيل الرواتب.'
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
    | حذف التشغيل
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        PayrollRun $payrollRun
    ): JsonResponse {
        try {
            $this->payrollRunService
                ->delete(
                    $payrollRun,
                    $request->user()
                );

            return response()->json([
                'success' =>
                    true,

                'message' =>
                    'تم حذف تشغيل الرواتب بنجاح.',
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
    | تحويل بيانات التشغيل
    |--------------------------------------------------------------------------
    */

    private function runPayload(
        PayrollRun $run,
        Request $request,
        bool $details = false
    ): array {
        $period =
            $run->period;

        $canProcess =
            $request->user()->can(
                'payroll.process'
            );

        $canManage =
            $request->user()->can(
                'payroll.manage'
            );

        $canApprove =
            $request->user()->can(
                'payroll.approve'
            );

        $exceptionCount =
            (int) (
                $run->exception_items_count
                ?? 0
            );

        $payload = [
            'id' =>
                $run->id,

            'uuid' =>
                $run->uuid,

            'run_number' =>
                $run->run_number,

            'payroll_period_id' =>
                $run->payroll_period_id,

            'type' =>
                $run->type,

            'type_label' =>
                $this->runTypeLabel(
                    $run->type
                ),

            'status' =>
                $run->status,

            'status_label' =>
                $this->runStatusLabel(
                    $run->status
                ),

            'currency_code' =>
                $run->currency_code,

            'employee_count' =>
                (int) $run->employee_count,

            'items_count' =>
                (int) (
                    $run->items_count
                    ?? $run->employee_count
                ),

            'exception_items_count' =>
                $exceptionCount,

            'calculated_items_count' =>
                (int) (
                    $run->calculated_items_count
                    ?? 0
                ),

            'approved_items_count' =>
                (int) (
                    $run->approved_items_count
                    ?? 0
                ),

            'paid_items_count' =>
                (int) (
                    $run->paid_items_count
                    ?? 0
                ),

            'total_basic_salary' =>
                (float) $run
                    ->total_basic_salary,

            'total_earnings' =>
                (float) $run
                    ->total_earnings,

            'total_deductions' =>
                (float) $run
                    ->total_deductions,

            'total_net_salary' =>
                (float) $run
                    ->total_net_salary,

            'notes' =>
                $run->notes,

            'calculated_at' =>
                $run->calculated_at
                    ?->toDateTimeString(),

            'approved_at' =>
                $run->approved_at
                    ?->toDateTimeString(),

            'paid_at' =>
                $run->paid_at
                    ?->toDateTimeString(),

            'created_at' =>
                $run->created_at
                    ?->toDateTimeString(),

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

                        'is_locked' =>
                            (bool) $period
                                ->is_locked,
                    ]
                    : null,

            'can_calculate' =>
                $canProcess
                &&
                in_array(
                    $run->status,
                    [
                        PayrollRun::STATUS_DRAFT,
                        PayrollRun::STATUS_CALCULATED,
                        PayrollRun::STATUS_REVIEW,
                    ],
                    true
                )
                &&
                !$period?->is_locked,

            'can_submit_review' =>
                $canProcess
                &&
                $run->status ===
                    PayrollRun::STATUS_CALCULATED
                &&
                $exceptionCount === 0
                &&
                (int) $run->employee_count > 0,

            'can_return_to_calculation' =>
                $canApprove
                &&
                $run->status ===
                    PayrollRun::STATUS_REVIEW,

            'can_approve' =>
                $canApprove
                &&
                $run->status ===
                    PayrollRun::STATUS_REVIEW
                &&
                $exceptionCount === 0,

            'can_mark_paid' =>
                $canApprove
                &&
                $run->status ===
                    PayrollRun::STATUS_APPROVED,

            'can_cancel' =>
                (
                    $canManage ||
                    $canApprove
                )
                &&
                !in_array(
                    $run->status,
                    [
                        PayrollRun::STATUS_PAID,
                        PayrollRun::STATUS_CANCELLED,
                    ],
                    true
                ),

            'can_delete' =>
                $canManage
                &&
                $run->status ===
                    PayrollRun::STATUS_DRAFT,
        ];

        if ($details) {
            $payload['audit'] = [
                'created_by' =>
                    $run->createdBy?->name,

                'calculated_by' =>
                    $run->calculatedBy?->name,

                'approved_by' =>
                    $run->approvedBy?->name,

                'paid_by' =>
                    $run->paidBy?->name,
            ];

            $payload['metadata'] =
                $run->metadata;
        }

        return $payload;
    }


    /*
    |--------------------------------------------------------------------------
    | تحويل بيانات بند الموظف
    |--------------------------------------------------------------------------
    */

    private function itemPayload(
        PayrollRunItem $item
    ): array {
        $employee =
            $item->employee;

        return [
            'id' =>
                $item->id,

            'uuid' =>
                $item->uuid,

            'employee_id' =>
                $item->employee_id,

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

            'salary_structure_id' =>
                $item->salary_structure_id,

            'salary_structure_version' =>
                $item->salaryStructure
                    ?->version,

            'currency_code' =>
                $item->currency_code,

            'basic_salary' =>
                (float) $item
                    ->basic_salary,

            'gross_salary' =>
                (float) $item
                    ->gross_salary,

            'total_earnings' =>
                (float) $item
                    ->total_earnings,

            'total_deductions' =>
                (float) $item
                    ->total_deductions,

            'net_salary' =>
                (float) $item
                    ->net_salary,

            'scheduled_work_days' =>
                (float) $item
                    ->scheduled_work_days,

            'actual_work_days' =>
                (float) $item
                    ->actual_work_days,

            'absent_days' =>
                (float) $item
                    ->absent_days,

            'paid_leave_days' =>
                (float) $item
                    ->paid_leave_days,

            'unpaid_leave_days' =>
                (float) $item
                    ->unpaid_leave_days,

            'overtime_minutes' =>
                (int) $item
                    ->overtime_minutes,

            'status' =>
                $item->status,

            'status_label' =>
                $this->itemStatusLabel(
                    $item->status
                ),

            'components_count' =>
                (int) (
                    $item->components_count
                    ?? $item->components?->count()
                    ?? 0
                ),

            'errors' =>
                $item->errors,

            'created_at' =>
                $item->created_at
                    ?->toDateTimeString(),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function authorizeRunView(
        Request $request,
        PayrollRun $payrollRun
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
            (int) $payrollRun->tenant_id,
            404
        );
    }


    private function actionResponse(
        Request $request,
        PayrollRun $run,
        string $message
    ): JsonResponse {
        $run->load([
            'period',
            'createdBy:id,name',
            'calculatedBy:id,name',
            'approvedBy:id,name',
            'paidBy:id,name',
        ]);

        $run->loadCount([
            'items',

            'items as exception_items_count' =>
                fn ($query) =>
                    $query->where(
                        'status',
                        PayrollRunItem::STATUS_EXCEPTION
                    ),

            'items as calculated_items_count' =>
                fn ($query) =>
                    $query->where(
                        'status',
                        PayrollRunItem::STATUS_CALCULATED
                    ),

            'items as approved_items_count' =>
                fn ($query) =>
                    $query->where(
                        'status',
                        PayrollRunItem::STATUS_APPROVED
                    ),

            'items as paid_items_count' =>
                fn ($query) =>
                    $query->where(
                        'status',
                        PayrollRunItem::STATUS_PAID
                    ),
        ]);

        return response()->json([
            'success' =>
                true,

            'message' =>
                $message,

            'payroll_run' =>
                $this->runPayload(
                    $run,
                    $request,
                    true
                ),
        ]);
    }


    private function businessError(
        LogicException | AuthorizationException $exception
    ): JsonResponse {
        $status =
            $exception instanceof
                AuthorizationException
                ? 403
                : 422;

        return response()->json([
            'success' =>
                false,

            'message' =>
                $exception->getMessage(),
        ], $status);
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


    private function runTypeLabel(
        string $type
    ): string {
        return match ($type) {
            PayrollRun::TYPE_REGULAR =>
                'تشغيل عادي',

            PayrollRun::TYPE_OFF_CYCLE =>
                'خارج الدورة',

            PayrollRun::TYPE_FINAL_SETTLEMENT =>
                'تسوية نهائية',

            default =>
                'غير محدد',
        };
    }


    private function runStatusLabel(
        string $status
    ): string {
        return match ($status) {
            PayrollRun::STATUS_DRAFT =>
                'مسودة',

            PayrollRun::STATUS_CALCULATING =>
                'جاري الحساب',

            PayrollRun::STATUS_CALCULATED =>
                'تم الحساب',

            PayrollRun::STATUS_REVIEW =>
                'قيد المراجعة',

            PayrollRun::STATUS_APPROVED =>
                'معتمد',

            PayrollRun::STATUS_PAID =>
                'مدفوع',

            PayrollRun::STATUS_CANCELLED =>
                'ملغى',

            default =>
                'غير محدد',
        };
    }


    private function itemStatusLabel(
        string $status
    ): string {
        return match ($status) {
            PayrollRunItem::STATUS_PENDING =>
                'بانتظار الحساب',

            PayrollRunItem::STATUS_CALCULATED =>
                'تم الحساب',

            PayrollRunItem::STATUS_EXCEPTION =>
                'يحتاج مراجعة',

            PayrollRunItem::STATUS_APPROVED =>
                'معتمد',

            PayrollRunItem::STATUS_PAID =>
                'مدفوع',

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


    private function componentSourceLabel(
        string $source
    ): string {
        return match ($source) {
            'salary_structure' =>
                'هيكل الراتب',

            'attendance' =>
                'الحضور',

            'overtime' =>
                'العمل الإضافي',

            'leave' =>
                'الإجازات',

            'manual' =>
                'تسوية يدوية',

            'system' =>
                'النظام',

            default =>
                'غير محدد',
        };
    }
}