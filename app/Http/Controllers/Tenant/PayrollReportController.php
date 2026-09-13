<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollReportController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | صفحة تقارير الرواتب
    |--------------------------------------------------------------------------
    */

    public function index(): View
    {
        return view(
            'tenant.payroll.reports.index'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | خيارات الفلاتر
    |--------------------------------------------------------------------------
    */

    public function options(
        Request $request
    ): JsonResponse {
        $tenantId = (int) $request
            ->user()
            ->tenant_id;

        $periods = PayrollPeriod::query()
            ->where(
                'tenant_id',
                $tenantId
            )
            ->whereHas('runs')
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

                    'status' =>
                        $period->status,
                ]
            )
            ->values();

        $runs = PayrollRun::query()
            ->where(
                'tenant_id',
                $tenantId
            )
            ->whereNotIn(
                'status',
                [
                    'draft',
                    'cancelled',
                ]
            )
            ->with([
                'period:id,tenant_id,code,name',
            ])
            ->latest('id')
            ->get([
                'id',
                'tenant_id',
                'payroll_period_id',
                'run_number',
                'type',
                'status',
                'currency_code',
            ])
            ->map(
                fn (PayrollRun $run) => [
                    'id' =>
                        $run->id,

                    'payroll_period_id' =>
                        $run->payroll_period_id,

                    'run_number' =>
                        $run->run_number,

                    'type' =>
                        $run->type,

                    'status' =>
                        $run->status,

                    'currency_code' =>
                        $run->currency_code,

                    'period_name' =>
                        $run->period?->name,
                ]
            )
            ->values();

        $departments = Department::query()
            ->where(
                'tenant_id',
                $tenantId
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get([
                'id',
                'code',
                'name',
            ]);

        return response()->json([
            'periods' =>
                $periods,

            'runs' =>
                $runs,

            'departments' =>
                $departments,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | بيانات التقرير
    |--------------------------------------------------------------------------
    */

    public function data(
        Request $request
    ): JsonResponse {
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

        $query = $this->reportQuery(
            $request
        );

        $summaryQuery =
            clone $query;

        $currencyCode =
            (clone $summaryQuery)
                ->value('currency_code') ??
            $request->user()
                ?->tenant
                ?->currency_code ??
            'SAR';

        $summary = [
            'records_count' =>
                (clone $summaryQuery)->count(),

            'employees_count' =>
                (clone $summaryQuery)
                    ->distinct()
                    ->count('employee_id'),

            'total_basic_salary' =>
                (float) (clone $summaryQuery)
                    ->sum('basic_salary'),

            'total_gross_salary' =>
                (float) (clone $summaryQuery)
                    ->sum('gross_salary'),

            'total_earnings' =>
                (float) (clone $summaryQuery)
                    ->sum('total_earnings'),

            'total_deductions' =>
                (float) (clone $summaryQuery)
                    ->sum('total_deductions'),

            'total_net_salary' =>
                (float) (clone $summaryQuery)
                    ->sum('net_salary'),

            'scheduled_work_days' =>
                (float) (clone $summaryQuery)
                    ->sum('scheduled_work_days'),

            'actual_work_days' =>
                (float) (clone $summaryQuery)
                    ->sum('actual_work_days'),

            'absent_days' =>
                (float) (clone $summaryQuery)
                    ->sum('absent_days'),

            'paid_leave_days' =>
                (float) (clone $summaryQuery)
                    ->sum('paid_leave_days'),

            'unpaid_leave_days' =>
                (float) (clone $summaryQuery)
                    ->sum('unpaid_leave_days'),

            'overtime_minutes' =>
                (int) (clone $summaryQuery)
                    ->sum('overtime_minutes'),

            'currency_code' =>
                $currencyCode,
        ];

        $paginator = $query
            ->latest(
                'payroll_run_items.id'
            )
            ->paginate($perPage);

        $paginator->getCollection()
            ->transform(
                fn (PayrollRunItem $item) =>
                    $this->payload($item)
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
    | استعلام التقرير
    |--------------------------------------------------------------------------
    */

    private function reportQuery(
        Request $request
    ): Builder {
        $tenantId = (int) $request
            ->user()
            ->tenant_id;

        $search = trim(
            (string) $request->get(
                'search',
                ''
            )
        );

        $periodId = $request->integer(
            'payroll_period_id'
        );

        $runId = $request->integer(
            'payroll_run_id'
        );

        $departmentId = $request->integer(
            'department_id'
        );

        $status = trim(
            (string) $request->get(
                'status',
                ''
            )
        );

        return PayrollRunItem::query()
            ->where(
                'payroll_run_items.tenant_id',
                $tenantId
            )
            ->whereHas(
                'payrollRun',
                function ($query) use (
                    $tenantId,
                    $periodId,
                    $runId
                ) {
                    $query->where(
                        'tenant_id',
                        $tenantId
                    );

                    if ($periodId) {
                        $query->where(
                            'payroll_period_id',
                            $periodId
                        );
                    }

                    if ($runId) {
                        $query->where(
                            'id',
                            $runId
                        );
                    }
                }
            )
            ->when(
                $departmentId,
                function ($query) use (
                    $departmentId
                ) {
                    $query->whereHas(
                        'employee',
                        fn ($employeeQuery) =>
                            $employeeQuery->where(
                                'department_id',
                                $departmentId
                            )
                    );
                }
            )
            ->when(
                in_array(
                    $status,
                    [
                        'pending',
                        'calculated',
                        'exception',
                        'approved',
                        'paid',
                    ],
                    true
                ),
                fn ($query) =>
                    $query->where(
                        'payroll_run_items.status',
                        $status
                    )
            )
            ->when(
                $search !== '',
                function ($query) use ($search) {
                    $query->whereHas(
                        'employee',
                        function ($employeeQuery) use (
                            $search
                        ) {
                            $employeeQuery->where(
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
            )
            ->with([
                'employee.department',
                'employee.jobTitle',
                'employee.branch',
                'payrollRun.period',
            ]);
    }


    /*
    |--------------------------------------------------------------------------
    | تجهيز السجل
    |--------------------------------------------------------------------------
    */

    private function payload(
        PayrollRunItem $item
    ): array {
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
        ];
    }



    /*
    |--------------------------------------------------------------------------
    | تصدير تقرير الرواتب
    |--------------------------------------------------------------------------
    */

    public function export(
        Request $request
    ): StreamedResponse {
        $query = $this->reportQuery(
            $request
        )->orderBy(
            'payroll_run_items.id'
        );

        $fileName =
            'payroll-report-' .
            now()->format('Ymd-His') .
            '.csv';

        return response()->streamDownload(
            function () use ($query) {

                $handle = fopen(
                    'php://output',
                    'w'
                );

                /*
                 * BOM حتى تظهر اللغة العربية
                 * بصورة صحيحة داخل Excel.
                 */
                fwrite(
                    $handle,
                    "\xEF\xBB\xBF"
                );

                fputcsv(
                    $handle,
                    [
                        'الرقم الوظيفي',
                        'اسم الموظف',
                        'القسم',
                        'المسمى الوظيفي',
                        'الفرع',
                        'فترة الرواتب',
                        'رقم المسير',
                        'حالة القسيمة',
                        'الراتب الأساسي',
                        'إجمالي الراتب',
                        'الاستحقاقات',
                        'الخصومات',
                        'صافي الراتب',
                        'أيام العمل المجدولة',
                        'أيام العمل الفعلية',
                        'أيام الغياب',
                        'الإجازات المدفوعة',
                        'الإجازات غير المدفوعة',
                        'ساعات العمل الإضافي',
                        'العملة',
                    ]
                );

                $query->chunkById(
                    500,
                    function ($items) use (
                        $handle
                    ) {
                        foreach ($items as $item) {
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

                            $statusLabel = match (
                                $item->status
                            ) {
                                'pending' =>
                                    'بانتظار الحساب',

                                'calculated' =>
                                    'محسوبة',

                                'exception' =>
                                    'بها مشكلة',

                                'approved' =>
                                    'معتمدة',

                                'paid' =>
                                    'مدفوعة',

                                default =>
                                    $item->status,
                            };

                            fputcsv(
                                $handle,
                                [
                                    $this->safeCsvText(
                                        $employee
                                            ?->employee_number
                                    ),

                                    $this->safeCsvText(
                                        $employeeName
                                    ),

                                    $this->safeCsvText(
                                        $employee
                                            ?->department
                                            ?->name
                                    ),

                                    $this->safeCsvText(
                                        $employee
                                            ?->jobTitle
                                            ?->name
                                    ),

                                    $this->safeCsvText(
                                        $employee
                                            ?->branch
                                            ?->name
                                    ),

                                    $this->safeCsvText(
                                        $period?->name
                                    ),

                                    $this->safeCsvText(
                                        $run?->run_number
                                    ),

                                    $statusLabel,

                                    number_format(
                                        (float) $item
                                            ->basic_salary,
                                        2,
                                        '.',
                                        ''
                                    ),

                                    number_format(
                                        (float) $item
                                            ->gross_salary,
                                        2,
                                        '.',
                                        ''
                                    ),

                                    number_format(
                                        (float) $item
                                            ->total_earnings,
                                        2,
                                        '.',
                                        ''
                                    ),

                                    number_format(
                                        (float) $item
                                            ->total_deductions,
                                        2,
                                        '.',
                                        ''
                                    ),

                                    number_format(
                                        (float) $item
                                            ->net_salary,
                                        2,
                                        '.',
                                        ''
                                    ),

                                    number_format(
                                        (float) $item
                                            ->scheduled_work_days,
                                        2,
                                        '.',
                                        ''
                                    ),

                                    number_format(
                                        (float) $item
                                            ->actual_work_days,
                                        2,
                                        '.',
                                        ''
                                    ),

                                    number_format(
                                        (float) $item
                                            ->absent_days,
                                        2,
                                        '.',
                                        ''
                                    ),

                                    number_format(
                                        (float) $item
                                            ->paid_leave_days,
                                        2,
                                        '.',
                                        ''
                                    ),

                                    number_format(
                                        (float) $item
                                            ->unpaid_leave_days,
                                        2,
                                        '.',
                                        ''
                                    ),

                                    number_format(
                                        (
                                            (int) $item
                                                ->overtime_minutes
                                        ) / 60,
                                        2,
                                        '.',
                                        ''
                                    ),

                                    $item->currency_code,
                                ]
                            );
                        }
                    },
                    'payroll_run_items.id',
                    'id'
                );

                fclose($handle);
            },
            $fileName,
            [
                'Content-Type' =>
                    'text/csv; charset=UTF-8',

                'Cache-Control' =>
                    'no-store, no-cache',
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | حماية خلايا Excel من تنفيذ الصيغ
    |--------------------------------------------------------------------------
    */

    private function safeCsvText(
        mixed $value
    ): string {
        $value = trim(
            (string) ($value ?? '')
        );

        if (
            $value !== '' &&
            in_array(
                mb_substr($value, 0, 1),
                [
                    '=',
                    '+',
                    '-',
                    '@',
                ],
                true
            )
        ) {
            return "'" . $value;
        }

        return $value;
    }


}