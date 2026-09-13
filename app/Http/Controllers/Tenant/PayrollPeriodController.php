<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StorePayrollPeriodRequest;
use App\Http\Requests\Tenant\UpdatePayrollPeriodRequest;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Services\HR\PayrollPeriodService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollPeriodController extends Controller
{
    public function __construct(
        private readonly PayrollPeriodService
            $payrollPeriodService
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
            $request->user()->can('payroll.view'),
            403
        );

        return view(
            'tenant.payroll.periods.index'
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
            $request->user()->can('payroll.view'),
            403
        );

        $tenantId =
            (int) $request->user()->tenant_id;

        $search = trim(
            (string) $request->get(
                'search',
                ''
            )
        );

        $status =
            $request->get('status');

        $year =
            $request->integer('year');

        $month =
            $request->integer('month');

        $locked =
            $request->get('locked');

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

        $query = PayrollPeriod::query()
            ->with([
                'createdBy:id,tenant_id,name',
                'lockedBy:id,tenant_id,name',
            ])
            ->withCount([
                'runs',

                'runs as active_runs_count' =>
                    function ($query) {
                        $query->whereNotIn(
                            'status',
                            [
                                PayrollRun::STATUS_CANCELLED,
                            ]
                        );
                    },
            ])
            ->where(
                'tenant_id',
                $tenantId
            );

        if ($search !== '') {
            $query->where(function ($query) use (
                $search
            ) {
                $query
                    ->where(
                        'code',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'name',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        if (
            $status &&
            in_array(
                $status,
                [
                    PayrollPeriod::STATUS_DRAFT,
                    PayrollPeriod::STATUS_OPEN,
                    PayrollPeriod::STATUS_PROCESSING,
                    PayrollPeriod::STATUS_REVIEW,
                    PayrollPeriod::STATUS_APPROVED,
                    PayrollPeriod::STATUS_PAID,
                    PayrollPeriod::STATUS_CLOSED,
                    PayrollPeriod::STATUS_CANCELLED,
                ],
                true
            )
        ) {
            $query->where(
                'status',
                $status
            );
        }

        if ($year) {
            $query->where(
                'year',
                $year
            );
        }

        if (
            $month >= 1 &&
            $month <= 12
        ) {
            $query->where(
                'month',
                $month
            );
        }

        if ($locked === '1') {
            $query->where(
                'is_locked',
                true
            );
        }

        if ($locked === '0') {
            $query->where(
                'is_locked',
                false
            );
        }

        $paginator = $query
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderByDesc('id')
            ->paginate($perPage);

        $paginator->through(
            fn (PayrollPeriod $period) =>
                $this->transformListItem(
                    $period
                )
        );

        return response()->json(
            $paginator
        );
    }


    /*
    |--------------------------------------------------------------------------
    | خيارات الواجهة
    |--------------------------------------------------------------------------
    */

    public function options(
        Request $request
    ): JsonResponse {
        abort_unless(
            $request->user()->can('payroll.view'),
            403
        );

        $tenantId =
            (int) $request->user()->tenant_id;

        $years = PayrollPeriod::query()
            ->where(
                'tenant_id',
                $tenantId
            )
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->map(
                fn ($year) => (int) $year
            )
            ->values();

        if (
            !$years->contains(
                (int) now()->year
            )
        ) {
            $years->prepend(
                (int) now()->year
            );
        }

        return response()->json([
            'success' => true,

            'years' =>
                $years,

            'current_year' =>
                (int) now()->year,

            'current_month' =>
                (int) now()->month,

            'can_manage' =>
                $request->user()
                    ->can('payroll.manage'),

            'can_approve' =>
                $request->user()
                    ->can('payroll.approve'),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | إنشاء الفترة
    |--------------------------------------------------------------------------
    */

    public function store(
        StorePayrollPeriodRequest $request
    ): JsonResponse {
        try {
            $period =
                $this->payrollPeriodService
                    ->create(
                        $request->validated(),
                        $request->user()
                    );

            return response()->json([
                'success' => true,

                'message' =>
                    'تم إنشاء فترة الرواتب بنجاح.',

                'payroll_period' =>
                    $this->transformDetails(
                        $period
                    ),
            ], 201);
        } catch (DomainException $exception) {
            return $this->domainError(
                $exception
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | عرض التفاصيل
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        PayrollPeriod $payrollPeriod
    ): JsonResponse {
        abort_unless(
            $request->user()->can('payroll.view'),
            403
        );

        $this->assertSameTenant(
            $request,
            $payrollPeriod
        );

        $payrollPeriod->load([
            'createdBy:id,tenant_id,name',

            'lockedBy:id,tenant_id,name',

            'runs' => function ($query) {
                $query
                    ->with([
                        'createdBy:id,tenant_id,name',
                    ])
                    ->orderByDesc('id');
            },
        ]);

        return response()->json([
            'success' => true,

            'payroll_period' =>
                $this->transformDetails(
                    $payrollPeriod
                ),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | تعديل الفترة
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdatePayrollPeriodRequest $request,
        PayrollPeriod $payrollPeriod
    ): JsonResponse {
        try {
            $period =
                $this->payrollPeriodService
                    ->update(
                        $payrollPeriod,
                        $request->validated(),
                        $request->user()
                    );

            return response()->json([
                'success' => true,

                'message' =>
                    'تم تحديث فترة الرواتب بنجاح.',

                'payroll_period' =>
                    $this->transformDetails(
                        $period
                    ),
            ]);
        } catch (DomainException $exception) {
            return $this->domainError(
                $exception
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | فتح الفترة
    |--------------------------------------------------------------------------
    */

    public function open(
        Request $request,
        PayrollPeriod $payrollPeriod
    ): JsonResponse {
        abort_unless(
            $request->user()->can('payroll.manage'),
            403
        );

        $this->assertSameTenant(
            $request,
            $payrollPeriod
        );

        try {
            $period =
                $this->payrollPeriodService
                    ->open(
                        $payrollPeriod,
                        $request->user()
                    );

            return response()->json([
                'success' => true,

                'message' =>
                    'تم فتح فترة الرواتب، ويمكن الآن إنشاء المسيرات.',

                'payroll_period' =>
                    $this->transformDetails(
                        $period
                    ),
            ]);
        } catch (DomainException $exception) {
            return $this->domainError(
                $exception
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | قفل الفترة
    |--------------------------------------------------------------------------
    */

    public function lock(
        Request $request,
        PayrollPeriod $payrollPeriod
    ): JsonResponse {
        abort_unless(
            $request->user()->can('payroll.approve'),
            403
        );

        $this->assertSameTenant(
            $request,
            $payrollPeriod
        );

        try {
            $period =
                $this->payrollPeriodService
                    ->lock(
                        $payrollPeriod,
                        $request->user()
                    );

            return response()->json([
                'success' => true,

                'message' =>
                    'تم قفل فترة الرواتب بنجاح.',

                'payroll_period' =>
                    $this->transformDetails(
                        $period
                    ),
            ]);
        } catch (DomainException $exception) {
            return $this->domainError(
                $exception
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | فك قفل الفترة
    |--------------------------------------------------------------------------
    */

    public function unlock(
        Request $request,
        PayrollPeriod $payrollPeriod
    ): JsonResponse {
        abort_unless(
            $request->user()->can('payroll.approve'),
            403
        );

        $this->assertSameTenant(
            $request,
            $payrollPeriod
        );

        $validated = $request->validate([
            'reason' => [
                'required',
                'string',
                'max:1000',
            ],
        ], [
            'reason.required' =>
                'سبب فك قفل الفترة مطلوب.',
        ]);

        try {
            $period =
                $this->payrollPeriodService
                    ->unlock(
                        $payrollPeriod,
                        $request->user(),
                        $validated['reason']
                    );

            return response()->json([
                'success' => true,

                'message' =>
                    'تم فك قفل فترة الرواتب.',

                'payroll_period' =>
                    $this->transformDetails(
                        $period
                    ),
            ]);
        } catch (DomainException $exception) {
            return $this->domainError(
                $exception
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | إلغاء الفترة
    |--------------------------------------------------------------------------
    */

    public function cancel(
        Request $request,
        PayrollPeriod $payrollPeriod
    ): JsonResponse {
        abort_unless(
            $request->user()->can('payroll.manage'),
            403
        );

        $this->assertSameTenant(
            $request,
            $payrollPeriod
        );

        $validated = $request->validate([
            'reason' => [
                'required',
                'string',
                'max:1000',
            ],
        ], [
            'reason.required' =>
                'سبب إلغاء الفترة مطلوب.',
        ]);

        try {
            $period =
                $this->payrollPeriodService
                    ->cancel(
                        $payrollPeriod,
                        $request->user(),
                        $validated['reason']
                    );

            return response()->json([
                'success' => true,

                'message' =>
                    'تم إلغاء فترة الرواتب.',

                'payroll_period' =>
                    $this->transformDetails(
                        $period
                    ),
            ]);
        } catch (DomainException $exception) {
            return $this->domainError(
                $exception
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | إغلاق الفترة
    |--------------------------------------------------------------------------
    */

    public function close(
        Request $request,
        PayrollPeriod $payrollPeriod
    ): JsonResponse {
        abort_unless(
            $request->user()->can('payroll.approve'),
            403
        );

        $this->assertSameTenant(
            $request,
            $payrollPeriod
        );

        try {
            $period =
                $this->payrollPeriodService
                    ->close(
                        $payrollPeriod,
                        $request->user()
                    );

            return response()->json([
                'success' => true,

                'message' =>
                    'تم إغلاق وقفل فترة الرواتب نهائيًا.',

                'payroll_period' =>
                    $this->transformDetails(
                        $period
                    ),
            ]);
        } catch (DomainException $exception) {
            return $this->domainError(
                $exception
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | حذف الفترة
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        PayrollPeriod $payrollPeriod
    ): JsonResponse {
        abort_unless(
            $request->user()->can('payroll.manage'),
            403
        );

        $this->assertSameTenant(
            $request,
            $payrollPeriod
        );

        try {
            $this->payrollPeriodService
                ->delete(
                    $payrollPeriod,
                    $request->user()
                );

            return response()->json([
                'success' => true,

                'message' =>
                    'تم حذف مسودة فترة الرواتب بنجاح.',
            ]);
        } catch (DomainException $exception) {
            return $this->domainError(
                $exception
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | تحويل سجل الجدول
    |--------------------------------------------------------------------------
    */

    private function transformListItem(
        PayrollPeriod $period
    ): array {
        return [
            'id' =>
                $period->id,

            'uuid' =>
                $period->uuid,

            'code' =>
                $period->code,

            'name' =>
                $period->name,

            'year' =>
                (int) $period->year,

            'month' =>
                (int) $period->month,

            'month_name' =>
                $period->month_name,

            'start_date' =>
                $period->start_date
                    ?->format('Y-m-d'),

            'end_date' =>
                $period->end_date
                    ?->format('Y-m-d'),

            'payment_date' =>
                $period->payment_date
                    ?->format('Y-m-d'),

            'duration_days' =>
                $period->duration_days,

            'status' =>
                $period->status,

            'status_label' =>
                $period->status_label,

            'status_color' =>
                $period->status_color,

            'is_locked' =>
                (bool) $period->is_locked,

            'locked_at' =>
                $period->locked_at
                    ?->format('Y-m-d H:i'),

            'locked_by' =>
                $period->lockedBy?->name,

            'runs_count' =>
                (int) $period->runs_count,

            'active_runs_count' =>
                (int) $period
                    ->active_runs_count,

            'created_by' =>
                $period->createdBy?->name,

            'created_at' =>
                $period->created_at
                    ?->format('Y-m-d H:i'),

            'can_edit' =>
                $period->canBeEdited(),

            'can_open' =>
                $period->canBeOpened(),

            'can_create_run' =>
                $period->canCreatePayrollRun(),

            'can_lock' =>
                $period->canBeLocked(),

            'can_unlock' =>
                $period->canBeUnlocked(),

            'can_cancel' =>
                $period->canBeCancelled(),

            'can_delete' =>
                $period->canBeDeleted(),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | تحويل التفاصيل
    |--------------------------------------------------------------------------
    */

    private function transformDetails(
        PayrollPeriod $period
    ): array {
        $period->loadMissing([
            'createdBy:id,tenant_id,name',
            'lockedBy:id,tenant_id,name',
            'runs.createdBy:id,tenant_id,name',
        ]);

        return [
            'id' =>
                $period->id,

            'uuid' =>
                $period->uuid,

            'code' =>
                $period->code,

            'name' =>
                $period->name,

            'year' =>
                (int) $period->year,

            'month' =>
                (int) $period->month,

            'month_name' =>
                $period->month_name,

            'start_date' =>
                $period->start_date
                    ?->format('Y-m-d'),

            'end_date' =>
                $period->end_date
                    ?->format('Y-m-d'),

            'payment_date' =>
                $period->payment_date
                    ?->format('Y-m-d'),

            'duration_days' =>
                $period->duration_days,

            'status' =>
                $period->status,

            'status_label' =>
                $period->status_label,

            'status_color' =>
                $period->status_color,

            'is_locked' =>
                (bool) $period->is_locked,

            'locked_at' =>
                $period->locked_at
                    ?->format('Y-m-d H:i'),

            'locked_by' =>
                $period->lockedBy?->name,

            'created_by' =>
                $period->createdBy?->name,

            'created_at' =>
                $period->created_at
                    ?->format('Y-m-d H:i'),

            'metadata' =>
                $period->metadata,

            'runs' =>
                $period->runs
                    ->map(function (PayrollRun $run) {
                        return [
                            'id' =>
                                $run->id,

                            'run_number' =>
                                $run->run_number,

                            'type' =>
                                $run->type,

                            'type_label' =>
                                $run->type_label,

                            'status' =>
                                $run->status,

                            'status_label' =>
                                $run->status_label,

                            'status_color' =>
                                $run->status_color,

                            'employee_count' =>
                                (int) $run
                                    ->employee_count,

                            'total_earnings' =>
                                (float) $run
                                    ->total_earnings,

                            'total_deductions' =>
                                (float) $run
                                    ->total_deductions,

                            'total_net_salary' =>
                                (float) $run
                                    ->total_net_salary,

                            'currency_code' =>
                                $run->currency_code,

                            'created_at' =>
                                $run->created_at
                                    ?->format(
                                        'Y-m-d H:i'
                                    ),
                        ];
                    })
                    ->values(),

            'can_edit' =>
                $period->canBeEdited(),

            'can_open' =>
                $period->canBeOpened(),

            'can_create_run' =>
                $period->canCreatePayrollRun(),

            'can_lock' =>
                $period->canBeLocked(),

            'can_unlock' =>
                $period->canBeUnlocked(),

            'can_cancel' =>
                $period->canBeCancelled(),

            'can_delete' =>
                $period->canBeDeleted(),
        ];
    }


    private function assertSameTenant(
        Request $request,
        PayrollPeriod $period
    ): void {
        abort_unless(
            (int) $period->tenant_id ===
            (int) $request->user()->tenant_id,
            404
        );
    }


    private function domainError(
        DomainException $exception
    ): JsonResponse {
        return response()->json([
            'success' => false,

            'message' =>
                $exception->getMessage(),
        ], 422);
    }
}