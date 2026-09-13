<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollRunItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollPayslipController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | واجهة قسائم الرواتب
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
            'tenant.payroll.payslips.index'
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

        $periodId =
            $request->integer(
                'payroll_period_id'
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
                    'payrollRun.period',
                    'salaryStructure:id,version,effective_from,effective_to',
                ])
                ->withCount(
                    'components'
                )
                ->where(
                    'tenant_id',
                    $tenantId
                )
                ->whereNull(
                    'deleted_at'
                )
                ->where(
                    'status',
                    '!=',
                    PayrollRunItem::STATUS_EXCEPTION
                )
                ->latest('id');

        if ($search !== '') {
            $query->where(
                function ($query) use ($search) {
                    $query
                        ->whereHas(
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
                        )
                        ->orWhereHas(
                            'payrollRun',
                            fn ($query) =>
                                $query->where(
                                    'run_number',
                                    'like',
                                    "%{$search}%"
                                )
                        );
                }
            );
        }

        if ($periodId) {
            $query->whereHas(
                'payrollRun',
                fn ($query) =>
                    $query->where(
                        'payroll_period_id',
                        $periodId
                    )
            );
        }

        if (
            $status &&
            in_array(
                $status,
                [
                    PayrollRunItem::STATUS_CALCULATED,
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
                        $this->payslipPayload(
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
    | خيارات الفلاتر
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
                ->whereHas(
                    'runs.items',
                    fn ($query) =>
                        $query->where(
                            'status',
                            '!=',
                            PayrollRunItem::STATUS_EXCEPTION
                        )
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
                    ]
                )
                ->values();

        return response()->json([
            'success' =>
                true,

            'periods' =>
                $periods,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | تفاصيل قسيمة الراتب
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        PayrollRunItem $payrollItem
    ): JsonResponse {
        $this->authorizePayslip(
            $request,
            $payrollItem
        );

        $payrollItem->load([
            'employee.department',
            'employee.jobTitle',
            'employee.branch',
            'payrollRun.period',
            'payrollRun.approvedBy:id,name',
            'payrollRun.paidBy:id,name',
            'salaryStructure',
            'components' =>
                fn ($query) =>
                    $query->orderBy('id'),
            'components.salaryComponent',
        ]);

        return response()->json([
            'success' =>
                true,

            'payslip' =>
                array_merge(
                    $this->payslipPayload(
                        $payrollItem
                    ),
                    [
                        'components' =>
                            $payrollItem
                                ->components
                                ->map(
                                    fn ($component) => [
                                        'id' =>
                                            $component->id,

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
                                            $this->sourceLabel(
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
                                    ]
                                )
                                ->values(),

                        'calculation_snapshot' =>
                            $payrollItem
                                ->calculation_snapshot,

                        'metadata' =>
                            $payrollItem->metadata,

                        'approved_by' =>
                            $payrollItem
                                ->payrollRun
                                ?->approvedBy
                                ?->name,

                        'paid_by' =>
                            $payrollItem
                                ->payrollRun
                                ?->paidBy
                                ?->name,
                    ]
                ),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | صفحة طباعة القسيمة
    |--------------------------------------------------------------------------
    */

    public function print(
        Request $request,
        PayrollRunItem $payrollItem
    ): View {
        $this->authorizePayslip(
            $request,
            $payrollItem
        );

        $payrollItem->load([
            'employee.department',
            'employee.jobTitle',
            'employee.branch',
            'payrollRun.period',
            'payrollRun.approvedBy:id,name',
            'payrollRun.paidBy:id,name',
            'salaryStructure',
            'components' =>
                fn ($query) =>
                    $query->orderBy('id'),
        ]);

        return view(
            'tenant.payroll.payslips.print',
            [
                'payrollItem' =>
                    $payrollItem,

                'tenant' =>
                    $request->user()->tenant,
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | تحويل بيانات القسيمة
    |--------------------------------------------------------------------------
    */

    private function payslipPayload(
        PayrollRunItem $item
    ): array {
        $employee =
            $item->employee;

        $run =
            $item->payrollRun;

        $period =
            $run?->period;

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

                        'department' =>
                            $employee
                                ->department
                                ?->name,

                        'job_title' =>
                            $employee
                                ->jobTitle
                                ?->name,

                        'branch' =>
                            $employee
                                ->branch
                                ?->name,
                    ]
                    : null,

            'payroll_run_id' =>
                $item->payroll_run_id,

            'run_number' =>
                $run?->run_number,

            'run_status' =>
                $run?->status,

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

                        'payment_date' =>
                            $period
                                ->payment_date
                                ?->toDateString(),
                    ]
                    : null,

            'salary_structure_id' =>
                $item->salary_structure_id,

            'salary_structure_version' =>
                $item
                    ->salaryStructure
                    ?->version,

            'currency_code' =>
                $item->currency_code,

            'basic_salary' =>
                (float) $item->basic_salary,

            'gross_salary' =>
                (float) $item->gross_salary,

            'total_earnings' =>
                (float) $item
                    ->total_earnings,

            'total_deductions' =>
                (float) $item
                    ->total_deductions,

            'net_salary' =>
                (float) $item->net_salary,

            'scheduled_work_days' =>
                (float) $item
                    ->scheduled_work_days,

            'actual_work_days' =>
                (float) $item
                    ->actual_work_days,

            'absent_days' =>
                (float) $item->absent_days,

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
                $this->statusLabel(
                    $item->status
                ),

            'components_count' =>
                (int) (
                    $item->components_count
                    ?? $item->components?->count()
                    ?? 0
                ),

            'created_at' =>
                $item->created_at
                    ?->toDateTimeString(),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | التحقق من الصلاحية والشركة
    |--------------------------------------------------------------------------
    */

    private function authorizePayslip(
        Request $request,
        PayrollRunItem $payrollItem
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
            (int) $payrollItem
                ->tenant_id,
            404
        );

        abort_if(
            $payrollItem->status ===
                PayrollRunItem::STATUS_EXCEPTION,
            404
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
            PayrollRunItem::STATUS_PENDING =>
                'بانتظار الحساب',

            PayrollRunItem::STATUS_CALCULATED =>
                'تم الحساب',

            PayrollRunItem::STATUS_APPROVED =>
                'معتمد',

            PayrollRunItem::STATUS_PAID =>
                'مدفوع',

            default =>
                'غير محدد',
        };
    }


    private function sourceLabel(
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