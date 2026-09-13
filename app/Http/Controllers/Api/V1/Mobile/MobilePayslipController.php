<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Models\PayrollRunItemComponent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobilePayslipController extends Controller
{
    public function index(
        Request $request
    ): JsonResponse {
        $this->authorizePayslips($request);

        $validated = $request->validate([
            'year' => [
                'nullable',
                'integer',
                'between:2000,2100',
            ],

            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'between:10,50',
            ],
        ]);

        $employee = $this->currentEmployee(
            $request
        );

        $items = PayrollRunItem::withoutGlobalScopes()
            ->where(
                'tenant_id',
                $request->user()->tenant_id
            )
            ->where(
                'employee_id',
                $employee->id
            )
            ->whereNull('deleted_at')
            ->whereIn(
                'status',
                [
                    PayrollRunItem::STATUS_APPROVED,
                    PayrollRunItem::STATUS_PAID,
                ]
            )
            ->whereHas(
                'payrollRun',
                function ($query) use (
                    $request,
                    $validated
                ) {
                    $query
                        ->withoutGlobalScopes()
                        ->where(
                            'tenant_id',
                            $request->user()->tenant_id
                        )
                        ->whereNull('deleted_at')
                        ->whereIn(
                            'status',
                            [
                                PayrollRun::STATUS_APPROVED,
                                PayrollRun::STATUS_PAID,
                            ]
                        );

                    if (!empty($validated['year'])) {
                        $query->whereHas(
                            'period',
                            function ($periodQuery) use (
                                $validated
                            ) {
                                $periodQuery->whereYear(
                                    'start_date',
                                    (int) $validated['year']
                                );
                            }
                        );
                    }
                }
            )
            ->with([
                'payrollRun.period',
            ])
            ->latest('id')
            ->paginate(
                $validated['per_page'] ?? 10
            );

        return response()->json([
            'success' => true,

            'data' => $items
                ->getCollection()
                ->map(
                    fn (PayrollRunItem $item) =>
                        $this->summaryPayload($item)
                )
                ->values(),

            'meta' => [
                'current_page' =>
                    $items->currentPage(),

                'last_page' =>
                    $items->lastPage(),

                'per_page' =>
                    $items->perPage(),

                'total' =>
                    $items->total(),
            ],
        ]);
    }

    public function show(
        Request $request,
        PayrollRunItem $payrollRunItem
    ): JsonResponse {
        $this->authorizePayslips($request);

        $employee = $this->currentEmployee(
            $request
        );

        abort_unless(
            (int) $payrollRunItem->tenant_id ===
                (int) $request->user()->tenant_id &&
            (int) $payrollRunItem->employee_id ===
                (int) $employee->id,
            404
        );

        abort_unless(
            in_array(
                $payrollRunItem->status,
                [
                    PayrollRunItem::STATUS_APPROVED,
                    PayrollRunItem::STATUS_PAID,
                ],
                true
            ),
            404
        );

        $payrollRunItem->load([
            'payrollRun.period',
            'components',
        ]);

        abort_unless(
            $payrollRunItem->payrollRun &&
            in_array(
                $payrollRunItem
                    ->payrollRun
                    ->status,
                [
                    PayrollRun::STATUS_APPROVED,
                    PayrollRun::STATUS_PAID,
                ],
                true
            ),
            404
        );

        return response()->json([
            'success' => true,

            'data' => [
                ...$this->summaryPayload(
                    $payrollRunItem
                ),

                'attendance_summary' => [
                    'scheduled_work_days' =>
                        (float) $payrollRunItem
                            ->scheduled_work_days,

                    'actual_work_days' =>
                        (float) $payrollRunItem
                            ->actual_work_days,

                    'absent_days' =>
                        (float) $payrollRunItem
                            ->absent_days,

                    'paid_leave_days' =>
                        (float) $payrollRunItem
                            ->paid_leave_days,

                    'unpaid_leave_days' =>
                        (float) $payrollRunItem
                            ->unpaid_leave_days,

                    'overtime_minutes' =>
                        (int) $payrollRunItem
                            ->overtime_minutes,
                ],

                'earnings' => $payrollRunItem
                    ->components
                    ->where(
                        'type',
                        PayrollRunItemComponent::TYPE_EARNING
                    )
                    ->values()
                    ->map(
                        fn (
                            PayrollRunItemComponent $component
                        ) => $this->componentPayload(
                            $component
                        )
                    ),

                'deductions' => $payrollRunItem
                    ->components
                    ->where(
                        'type',
                        PayrollRunItemComponent::TYPE_DEDUCTION
                    )
                    ->values()
                    ->map(
                        fn (
                            PayrollRunItemComponent $component
                        ) => $this->componentPayload(
                            $component
                        )
                    ),
            ],
        ]);
    }

    private function summaryPayload(
        PayrollRunItem $item
    ): array {
        $run = $item->payrollRun;
        $period = $run?->period;

        return [
            'uuid' =>
                $item->uuid,

            'run_number' =>
                $run?->run_number,

            'run_type' =>
                $run?->type,

            'status' =>
                $item->status,

            'status_label' =>
                $item->status_label,

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

            'period' => $period
                ? [
                    'id' =>
                        $period->id,

                    'name' =>
                        $period->name,

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
                : null,

            'approved_at' =>
                $run?->approved_at
                    ?->toIso8601String(),

            'paid_at' =>
                $run?->paid_at
                    ?->toIso8601String(),
        ];
    }

    private function componentPayload(
        PayrollRunItemComponent $component
    ): array {
        return [
            'id' =>
                $component->id,

            'code' =>
                $component->component_code,

            'name' =>
                $component->component_name,

            'type' =>
                $component->type,

            'category' =>
                $component->category,

            'source' =>
                $component->source,

            'source_label' =>
                $component->source_label,

            'quantity' =>
                (float) $component->quantity,

            'rate' =>
                (float) $component->rate,

            'percentage' =>
                (float) $component->percentage,

            'amount' =>
                (float) $component->amount,

            'is_loan_installment' =>
                $component->category === 'loan' ||
                $component->reference_type ===
                    'App\\Models\\EmployeeLoanInstallment',
        ];
    }

    private function currentEmployee(
        Request $request
    ): Employee {
        return Employee::withoutGlobalScopes()
            ->where(
                'tenant_id',
                $request->user()->tenant_id
            )
            ->where(
                'user_id',
                $request->user()->id
            )
            ->whereNull('deleted_at')
            ->firstOrFail();
    }

    private function authorizePayslips(
        Request $request
    ): void {
        abort_unless(
            $request->user()?->can(
                'self_service.payslips'
            ),
            403,
            'لا تملك صلاحية عرض قسائم الرواتب.'
        );
    }
}