<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollRunItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SelfServicePayslipController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | صفحة قسائم الموظف
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request
    ): View {
        $employee = $this->resolveEmployee(
            $request
        );

        return view(
            'tenant.self-service.payslips.index',
            [
                'employee' =>
                    $employee,
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | بيانات القسائم
    |--------------------------------------------------------------------------
    */

    public function data(
        Request $request
    ): JsonResponse {
        $employee = $this->resolveEmployee(
            $request
        );

        if (!$employee) {
            return response()->json([
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => 15,
                'from' => null,
                'to' => null,
                'total' => 0,

                'summary' => [
                    'total_basic_salary' => 0,
                    'total_earnings' => 0,
                    'total_deductions' => 0,
                    'total_net_salary' => 0,
                    'currency_code' =>
                        $request->user()
                            ?->tenant
                            ?->currency_code ??
                        'SAR',
                ],

                'message' =>
                    'لا يوجد ملف موظف مرتبط بهذا المستخدم.',
            ]);
        }

        $tenantId = (int) $request
            ->user()
            ->tenant_id;

        $perPage = min(
            max(
                (int) $request->get(
                    'per_page',
                    12
                ),
                6
            ),
            50
        );

        $periodId = $request->integer(
            'payroll_period_id'
        );

        $status = trim(
            (string) $request->get(
                'status',
                ''
            )
        );

        $query = PayrollRunItem::query()
            ->where(
                'tenant_id',
                $tenantId
            )
            ->where(
                'employee_id',
                $employee->id
            )
            ->whereIn(
                'status',
                [
                    'approved',
                    'paid',
                ]
            )
            ->whereHas(
                'payrollRun',
                function ($query) use (
                    $tenantId,
                    $periodId
                ) {
                    $query
                        ->where(
                            'tenant_id',
                            $tenantId
                        )
                        ->whereIn(
                            'status',
                            [
                                'approved',
                                'paid',
                            ]
                        );

                    if ($periodId) {
                        $query->where(
                            'payroll_period_id',
                            $periodId
                        );
                    }
                }
            )
            ->when(
                in_array(
                    $status,
                    [
                        'approved',
                        'paid',
                    ],
                    true
                ),
                fn ($query) =>
                    $query->where(
                        'status',
                        $status
                    )
            )
            ->with([
                'employee.department',
                'employee.jobTitle',
                'employee.branch',
                'payrollRun.period',
            ])
            ->latest('id');

        $summaryQuery = clone $query;

        $summary = [
            'total_basic_salary' =>
                (float) (clone $summaryQuery)
                    ->sum('basic_salary'),

            'total_earnings' =>
                (float) (clone $summaryQuery)
                    ->sum('total_earnings'),

            'total_deductions' =>
                (float) (clone $summaryQuery)
                    ->sum('total_deductions'),

            'total_net_salary' =>
                (float) (clone $summaryQuery)
                    ->sum('net_salary'),

            'currency_code' =>
                $request->user()
                    ?->tenant
                    ?->currency_code ??
                'SAR',
        ];

        $paginator = $query->paginate(
            $perPage
        );

        $paginator->getCollection()
            ->transform(
                fn (PayrollRunItem $item) =>
                    $this->payload(
                        $item,
                        false
                    )
            );

        return response()->json(
            array_merge(
                $paginator->toArray(),
                [
                    'summary' =>
                        $summary,
                ]
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | خيارات البحث
    |--------------------------------------------------------------------------
    */

    public function options(
        Request $request
    ): JsonResponse {
        $employee = $this->resolveEmployee(
            $request
        );

        if (!$employee) {
            return response()->json([
                'periods' => [],
            ]);
        }

        $tenantId = (int) $request
            ->user()
            ->tenant_id;

        $periods = PayrollPeriod::query()
            ->where(
                'tenant_id',
                $tenantId
            )
            ->whereHas(
                'runs',
                function ($query) use (
                    $tenantId,
                    $employee
                ) {
                    $query
                        ->where(
                            'tenant_id',
                            $tenantId
                        )
                        ->whereIn(
                            'status',
                            [
                                'approved',
                                'paid',
                            ]
                        )
                        ->whereHas(
                            'items',
                            function ($query) use (
                                $tenantId,
                                $employee
                            ) {
                                $query
                                    ->where(
                                        'tenant_id',
                                        $tenantId
                                    )
                                    ->where(
                                        'employee_id',
                                        $employee->id
                                    )
                                    ->whereIn(
                                        'status',
                                        [
                                            'approved',
                                            'paid',
                                        ]
                                    );
                            }
                        );
                }
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
                            ?->format('Y-m-d'),

                    'end_date' =>
                        $period->end_date
                            ?->format('Y-m-d'),

                    'payment_date' =>
                        $period->payment_date
                            ?->format('Y-m-d'),

                    'status' =>
                        $period->status,
                ]
            )
            ->values();

        return response()->json([
            'periods' =>
                $periods,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | عرض قسيمة واحدة
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        PayrollRunItem $payrollItem
    ): JsonResponse {
        $employee = $this->resolveEmployee(
            $request
        );

        $this->authorizePayslip(
            $request,
            $employee,
            $payrollItem
        );

        return response()->json([
            'success' => true,

            'payslip' =>
                $this->payload(
                    $payrollItem,
                    true
                ),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | طباعة القسيمة
    |--------------------------------------------------------------------------
    */

    public function print(
        Request $request,
        PayrollRunItem $payrollItem
    ): View {
        $employee = $this->resolveEmployee(
            $request
        );

        $this->authorizePayslip(
            $request,
            $employee,
            $payrollItem
        );

        return view(
            'tenant.payroll.payslips.print',
            [
                'payslip' =>
                    $this->payload(
                        $payrollItem,
                        true
                    ),

                'tenant' =>
                    $request->user()->tenant,
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | الموظف المرتبط بالمستخدم
    |--------------------------------------------------------------------------
    */

    private function resolveEmployee(
        Request $request
    ): ?Employee {
        $user = $request->user();

        if (
            !$user ||
            !$user->tenant_id
        ) {
            return null;
        }

        return Employee::query()
            ->where(
                'tenant_id',
                $user->tenant_id
            )
            ->where(
                'user_id',
                $user->id
            )
            ->first();
    }


    /*
    |--------------------------------------------------------------------------
    | حماية القسيمة
    |--------------------------------------------------------------------------
    */

    private function authorizePayslip(
        Request $request,
        ?Employee $employee,
        PayrollRunItem $payrollItem
    ): void {
        if (
            !$employee ||
            (int) $payrollItem->tenant_id !==
                (int) $request->user()->tenant_id ||
            (int) $payrollItem->employee_id !==
                (int) $employee->id ||
            !in_array(
                $payrollItem->status,
                [
                    'approved',
                    'paid',
                ],
                true
            )
        ) {
            abort(404);
        }

        $runIsAvailable = $payrollItem
            ->payrollRun()
            ->whereIn(
                'status',
                [
                    'approved',
                    'paid',
                ]
            )
            ->exists();

        if (!$runIsAvailable) {
            abort(404);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | تجهيز بيانات القسيمة
    |--------------------------------------------------------------------------
    */

    private function payload(
        PayrollRunItem $item,
        bool $withComponents = true
    ): array {
        $relations = [
            'employee.department',
            'employee.jobTitle',
            'employee.branch',
            'payrollRun.period',
        ];

        if ($withComponents) {
            $relations[] =
                'components';
        }

        $item->loadMissing(
            $relations
        );

        $employee =
            $item->employee;

        $run =
            $item->payrollRun;

        $period =
            $run?->period;

        $employeeName = trim(
            collect([
                $employee?->first_name,
                $employee?->father_name,
                $employee?->grandfather_name,
                $employee?->family_name,
            ])
                ->filter()
                ->implode(' ')
        );

        return [
            'id' =>
                $item->id,

            'uuid' =>
                $item->uuid,

            'employee' => [
                'id' =>
                    $employee?->id,

                'employee_number' =>
                    $employee?->employee_number,

                'full_name' =>
                    $employeeName ?: '-',

                'department' => [
                    'id' =>
                        $employee?->department?->id,

                    'name' =>
                        $employee?->department?->name,
                ],

                'job_title' => [
                    'id' =>
                        $employee?->jobTitle?->id,

                    'name' =>
                        $employee?->jobTitle?->name,
                ],

                'branch' => [
                    'id' =>
                        $employee?->branch?->id,

                    'name' =>
                        $employee?->branch?->name,
                ],
            ],

            'payroll_run' => [
                'id' =>
                    $run?->id,

                'run_number' =>
                    $run?->run_number,

                'type' =>
                    $run?->type,

                'status' =>
                    $run?->status,

                'currency_code' =>
                    $run?->currency_code,

                'notes' =>
                    $run?->notes,
            ],

            'payroll_period' => [
                'id' =>
                    $period?->id,

                'code' =>
                    $period?->code,

                'name' =>
                    $period?->name,

                'year' =>
                    $period?->year,

                'month' =>
                    $period?->month,

                'start_date' =>
                    $period?->start_date
                        ?->format('Y-m-d'),

                'end_date' =>
                    $period?->end_date
                        ?->format('Y-m-d'),

                'payment_date' =>
                    $period?->payment_date
                        ?->format('Y-m-d'),
            ],

            'currency_code' =>
                $item->currency_code,

            'basic_salary' =>
                (float) $item->basic_salary,

            'gross_salary' =>
                (float) $item->gross_salary,

            'total_earnings' =>
                (float) $item->total_earnings,

            'total_deductions' =>
                (float) $item->total_deductions,

            'net_salary' =>
                (float) $item->net_salary,

            'scheduled_work_days' =>
                (float) $item->scheduled_work_days,

            'actual_work_days' =>
                (float) $item->actual_work_days,

            'absent_days' =>
                (float) $item->absent_days,

            'paid_leave_days' =>
                (float) $item->paid_leave_days,

            'unpaid_leave_days' =>
                (float) $item->unpaid_leave_days,

            'overtime_minutes' =>
                (int) $item->overtime_minutes,

            'status' =>
                $item->status,

            'components' =>
                $withComponents
                    ? $item->components
                        ->map(
                            fn ($component) => [
                                'id' =>
                                    $component->id,

                                'component_code' =>
                                    $component->component_code,

                                'component_name' =>
                                    $component->component_name,

                                'type' =>
                                    $component->type,

                                'category' =>
                                    $component->category,

                                'source' =>
                                    $component->source,

                                'quantity' =>
                                    $component->quantity !== null
                                        ? (float) $component->quantity
                                        : null,

                                'rate' =>
                                    $component->rate !== null
                                        ? (float) $component->rate
                                        : null,

                                'percentage' =>
                                    $component->percentage !== null
                                        ? (float) $component->percentage
                                        : null,

                                'amount' =>
                                    (float) $component->amount,
                            ]
                        )
                        ->values()
                        ->all()
                    : [],
        ];
    }
}