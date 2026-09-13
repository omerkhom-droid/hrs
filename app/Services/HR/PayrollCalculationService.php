<?php

namespace App\Services\HR;

use App\Models\Employee;
use App\Models\EmployeeSalaryStructure;
use App\Models\PayrollAdjustment;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\EmployeeLoanInstallment;
use Illuminate\Support\Collection;
use LogicException;

class PayrollCalculationService
{
    /**
     * حساب راتب موظف واحد داخل تشغيل الرواتب.
     */
    public function calculate(
        Employee $employee,
        PayrollPeriod $period,
        PayrollRun $run,
        array $workMetrics = []
    ): array {
        $this->ensureSameTenant(
            $employee,
            $period,
            $run
        );

        $structure =
            $this->resolveSalaryStructure(
                $employee,
                $period,
                $run
            );

        $metrics =
            $this->normalizeWorkMetrics(
                $workMetrics
            );

        /*
         * حساب مكونات هيكل راتب الموظف.
         */
        $structureComponents =
            $this->calculateStructureComponents(
                $structure,
                $metrics['proration_ratio']
            );


        $adjustmentData =
            $this->calculateAdjustments(
                $employee,
                $period,
                $run
            );

        $loanData =
            $this->calculateLoanInstallments(
                $employee,
                $period,
                $run
            );

        $components = collect(
            $structureComponents
        )
            ->merge(
                $adjustmentData['components']
            )
            ->merge(
                $loanData['components']
            )
            ->values();

        $components =
            $this->ensureBasicSalaryComponent(
                $components,
                $structure,
                $metrics['proration_ratio']
            );

        $totals =
            $this->calculateTotals(
                $components
            );

        $errors = [];
        $warnings = [];

        if ($components->isEmpty()) {
            $errors[] =
                'لا توجد مكونات راتب فعالة للموظف.';
        }

        if ($totals['basic_salary'] <= 0) {
            $errors[] =
                'الراتب الأساسي للموظف غير صحيح أو يساوي صفرًا.';
        }

        if ($totals['net_salary'] < 0) {
            $warnings[] =
                'صافي الراتب سالب ويحتاج إلى مراجعة.';
        }

        if (
            round(
                (float) $structure->basic_salary,
                2
            ) !==
            round(
                (float) $totals['unprorated_basic_salary'],
                2
            )
        ) {
            $warnings[] =
                'قيمة الراتب الأساسي المحسوبة تختلف عن القيمة المحفوظة في هيكل الراتب.';
        }

        return [
            'employee_id' =>
                $employee->id,

            'salary_structure_id' =>
                $structure->id,

            'currency_code' =>
                $run->currency_code,

            'basic_salary' =>
                $this->money(
                    $totals['basic_salary']
                ),

            'gross_salary' =>
                $this->money(
                    $totals['gross_salary']
                ),

            'total_earnings' =>
                $this->money(
                    $totals['total_earnings']
                ),

            'total_deductions' =>
                $this->money(
                    $totals['total_deductions']
                ),

            'net_salary' =>
                $this->money(
                    $totals['net_salary']
                ),

            'scheduled_work_days' =>
                $metrics['scheduled_work_days'],

            'actual_work_days' =>
                $metrics['actual_work_days'],

            'absent_days' =>
                $metrics['absent_days'],

            'paid_leave_days' =>
                $metrics['paid_leave_days'],

            'unpaid_leave_days' =>
                $metrics['unpaid_leave_days'],

            'overtime_minutes' =>
                $metrics['overtime_minutes'],

            'proration_ratio' =>
                $metrics['proration_ratio'],

            'components' =>
                $components->all(),

            'adjustment_ids' =>
                $adjustmentData['adjustment_ids'],
            
            'loan_installment_ids' =>
                $loanData['installment_ids'],


            'errors' =>
                $errors,

            'warnings' =>
                $warnings,

            'calculation_snapshot' => [
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

                'salary_structure' => [
                    'id' =>
                        $structure->id,

                    'uuid' =>
                        $structure->uuid,

                    'version' =>
                        $structure->version,

                    'effective_from' =>
                        $structure->effective_from?->toDateString(),

                    'effective_to' =>
                        $structure->effective_to?->toDateString(),

                    'currency_code' =>
                        $structure->currency_code,

                    'basic_salary' =>
                        $this->money(
                            $structure->basic_salary
                        ),
                ],

                'period' => [
                    'id' =>
                        $period->id,

                    'code' =>
                        $period->code,

                    'start_date' =>
                        $period->start_date?->toDateString(),

                    'end_date' =>
                        $period->end_date?->toDateString(),

                    'payment_date' =>
                        $period->payment_date?->toDateString(),
                ],

                'work_metrics' =>
                    $metrics,

                'totals' => [
                    'basic_salary' =>
                        $this->money(
                            $totals['basic_salary']
                        ),

                    'gross_salary' =>
                        $this->money(
                            $totals['gross_salary']
                        ),

                    'total_earnings' =>
                        $this->money(
                            $totals['total_earnings']
                        ),

                    'total_deductions' =>
                        $this->money(
                            $totals['total_deductions']
                        ),

                    'net_salary' =>
                        $this->money(
                            $totals['net_salary']
                        ),
                ],

                'warnings' =>
                    $warnings,
            ],
        ];
    }


    /**
     * جلب أقساط السلف المستحقة وإضافتها
     * كاستقطاعات إلى مسير الرواتب.
     */
    private function calculateLoanInstallments(
        Employee $employee,
        PayrollPeriod $period,
        PayrollRun $run
    ): array {
        $installments =
            EmployeeLoanInstallment::withoutGlobalScopes()
                ->with('loan')
                ->where(
                    'tenant_id',
                    $employee->tenant_id
                )
                ->where(
                    'employee_id',
                    $employee->id
                )
                ->whereIn('status', [
                    'pending',
                    'partially_paid',
                ])
                ->whereNull('payroll_run_id')
                ->whereDate(
                    'due_date',
                    '<=',
                    $period->end_date
                )
                ->whereHas(
                    'loan',
                    function ($query) use ($employee) {
                        $query
                            ->withoutGlobalScopes()
                            ->where(
                                'tenant_id',
                                $employee->tenant_id
                            )
                            ->whereIn('status', [
                                'approved',
                                'active',
                            ])
                            ->whereNull('deleted_at');
                    }
                )
                ->orderBy('due_date')
                ->orderBy('installment_number')
                ->lockForUpdate()
                ->get();

        $components = [];
        $installmentIds = [];

        foreach ($installments as $installment) {
            $loan = $installment->loan;

            if (!$loan) {
                continue;
            }

            $amount = $this->money(
                $installment->remaining_amount
            );

            if ($amount <= 0) {
                continue;
            }

            $components[] = [
                'salary_component_id' => null,

                'component_code' =>
                    'LOAN_INSTALLMENT',

                'component_name' =>
                    'قسط سلفة '
                    . $loan->request_number
                    . ' - القسط '
                    . $installment->installment_number,

                'type' => 'deduction',
                'category' => 'loan',
                'source' => 'system',

                'quantity' => 1,
                'rate' => $amount,
                'percentage' => null,
                'amount' => $amount,

                'is_taxable' => false,

                'is_subject_to_insurance' =>
                    false,

                'reference_type' =>
                    EmployeeLoanInstallment::class,

                'reference_id' =>
                    $installment->id,

                'metadata' => [
                    'affects_net_salary' => true,

                    'loan_id' =>
                        $loan->id,

                    'loan_uuid' =>
                        $loan->uuid,

                    'request_number' =>
                        $loan->request_number,

                    'installment_number' =>
                        $installment
                            ->installment_number,

                    'due_date' =>
                        $installment
                            ->due_date
                            ?->toDateString(),

                    'generated_by_system' => true,
                ],
            ];

            $installmentIds[] =
                $installment->id;
        }

        return [
            'components' => $components,

            'installment_ids' =>
                $installmentIds,
        ];
    }


    /**
     * التأكد أن جميع السجلات تتبع الشركة نفسها.
     */
    private function ensureSameTenant(
        Employee $employee,
        PayrollPeriod $period,
        PayrollRun $run
    ): void {
        if (
            (int) $employee->tenant_id
                !== (int) $period->tenant_id
            ||
            (int) $employee->tenant_id
                !== (int) $run->tenant_id
        ) {
            throw new LogicException(
                'لا يمكن حساب راتب موظف تابع لشركة أخرى.'
            );
        }

        if (
            (int) $run->payroll_period_id
                !== (int) $period->id
        ) {
            throw new LogicException(
                'تشغيل الرواتب غير مرتبط بالفترة المحددة.'
            );
        }
    }


    /**
     * الحصول على هيكل الراتب الفعال للموظف.
     */
    private function resolveSalaryStructure(
        Employee $employee,
        PayrollPeriod $period,
        PayrollRun $run
    ): EmployeeSalaryStructure {
        $structure =
            EmployeeSalaryStructure::withoutGlobalScopes()
                ->with([
                    'components' =>
                        fn ($query) =>
                            $query
                                ->where(
                                    'is_active',
                                    true
                                )
                                ->whereNull(
                                    'deleted_at'
                                ),

                    'components.salaryComponent',
                ])
                ->where(
                    'tenant_id',
                    $employee->tenant_id
                )
                ->where(
                    'employee_id',
                    $employee->id
                )
                ->where(
                    'status',
                    EmployeeSalaryStructure::STATUS_ACTIVE
                )
                ->whereDate(
                    'effective_from',
                    '<=',
                    $period->end_date
                )
                ->where(function ($query) use ($period) {
                    $query
                        ->whereNull(
                            'effective_to'
                        )
                        ->orWhereDate(
                            'effective_to',
                            '>=',
                            $period->start_date
                        );
                })
                ->whereNull(
                    'deleted_at'
                )
                ->latest(
                    'effective_from'
                )
                ->latest(
                    'version'
                )
                ->first();

        if (!$structure) {
            throw new LogicException(
                'لا يوجد هيكل راتب فعال للموظف: '
                . $this->employeeName($employee)
            );
        }

        if (
            strtoupper(
                $structure->currency_code
            )
            !==
            strtoupper(
                $run->currency_code
            )
        ) {
            throw new LogicException(
                'عملة هيكل راتب الموظف لا تطابق عملة تشغيل الرواتب.'
            );
        }

        return $structure;
    }


    /**
     * حساب مكونات هيكل الراتب.
     */
    private function calculateStructureComponents(
        EmployeeSalaryStructure $structure,
        float $prorationRatio
    ): array {
        $rows =
            $structure->components
                ->filter(
                    fn ($row) =>
                        $row->is_active &&
                        $row->salaryComponent &&
                        $row->salaryComponent->is_active
                )
                ->sortBy(
                    fn ($row) =>
                        sprintf(
                            '%010d-%010d',
                            $row->salaryComponent
                                ->sort_order ?? 0,
                            $row->id
                        )
                )
                ->values();

        $rowsByComponentId =
            $rows->keyBy(
                'salary_component_id'
            );

        $rowsByCode = [];

        foreach ($rows as $row) {
            $code = strtoupper(
                trim(
                    (string) $row
                        ->salaryComponent
                        ->code
                )
            );

            if ($code !== '') {
                $rowsByCode[$code] = $row;
            }
        }

        $calculatedById = [];
        $resolving = [];

        $resolve = function (
            $row
        ) use (
            &$resolve,
            &$calculatedById,
            &$resolving,
            $rowsByComponentId,
            $rowsByCode,
            $prorationRatio,
            $structure
        ): float {
            $componentId =
                (int) $row->salary_component_id;

            if (
                array_key_exists(
                    $componentId,
                    $calculatedById
                )
            ) {
                return $calculatedById[
                    $componentId
                ];
            }

            if (
                isset(
                    $resolving[$componentId]
                )
            ) {
                throw new LogicException(
                    'يوجد اعتماد دائري بين مكونات الراتب.'
                );
            }

            $resolving[$componentId] = true;

            $definition =
                $row->salaryComponent;

            $method =
                $definition->calculation_method
                ?: 'fixed';

            $amount = match ($method) {
                'fixed' =>
                    $this->fixedAmount(
                        $row,
                        $definition
                    ),

                'percentage' =>
                    $this->percentageAmount(
                        $row,
                        $definition,
                        $rowsByComponentId,
                        $resolve
                    ),

                'quantity_rate' =>
                    $this->quantityRateAmount(
                        $row,
                        $definition
                    ),

                'formula' =>
                    $this->formulaAmount(
                        $row,
                        $definition,
                        $rowsByCode,
                        $resolve
                    ),

                default =>
                    throw new LogicException(
                        'طريقة حساب مكون الراتب غير مدعومة: '
                        . $method
                    ),
            };

            /*
             * المكونات الثابتة يتم تخفيضها عند وجود
             * غياب أو إجازة غير مدفوعة إذا كانت قابلة للنسبة.
             *
             * مكون النسبة أو المعادلة يعتمد غالبًا على مكون
             * تم تخفيضه مسبقًا، لذلك لا نطبق النسبة مرتين.
             */
            if (
                $definition->is_proratable &&
                in_array(
                    $method,
                    [
                        'fixed',
                        'quantity_rate',
                    ],
                    true
                )
            ) {
                $amount *= $prorationRatio;
            }

            $amount =
                $this->money(
                    max(
                        0,
                        $amount
                    )
                );

            unset(
                $resolving[$componentId]
            );

            $calculatedById[$componentId] =
                $amount;

            return $amount;
        };

        $components = [];

        foreach ($rows as $row) {
            $definition =
                $row->salaryComponent;

            $amount =
                $resolve($row);

            $components[] = [
                'salary_component_id' =>
                    $definition->id,

                'component_code' =>
                    $definition->code,

                'component_name' =>
                    $definition->name,

                'type' =>
                    $definition->type,

                'category' =>
                    $definition->category,

                'source' =>
                    'salary_structure',

                'quantity' =>
                    $row->quantity,

                'rate' =>
                    $row->rate,

                'percentage' =>
                    $row->percentage,

                'amount' =>
                    $amount,

                'is_taxable' =>
                    (bool) $definition->is_taxable,

                'is_subject_to_insurance' =>
                    (bool) $definition
                        ->is_subject_to_insurance,

                'reference_type' =>
                    EmployeeSalaryStructure::class,

                'reference_id' =>
                    $structure->id,

                'metadata' => [
                    'salary_structure_component_id' =>
                        $row->id,

                    'salary_structure_version' =>
                        $structure->version,

                    'calculation_method' =>
                        $definition->calculation_method,

                    'affects_net_salary' =>
                        (bool) $definition
                            ->affects_net_salary,

                    'is_proratable' =>
                        (bool) $definition
                            ->is_proratable,

                    'proration_ratio' =>
                        $prorationRatio,

                    'formula' =>
                        $row->formula
                        ?: $definition->formula,
                ],
            ];
        }

        return $components;
    }


    private function fixedAmount(
        $row,
        $definition
    ): float {
        if ($row->amount !== null) {
            return (float) $row->amount;
        }

        return (float) (
            $definition->default_amount
            ?? 0
        );
    }


    private function percentageAmount(
        $row,
        $definition,
        Collection $rowsByComponentId,
        callable $resolve
    ): float {
        $percentage =
            $row->percentage !== null
                ? (float) $row->percentage
                : (float) (
                    $definition->default_percentage
                    ?? 0
                );

        $baseComponentId =
            $definition
                ->percentage_base_component_id;

        if (!$baseComponentId) {
            throw new LogicException(
                'لم يتم تحديد مكون أساس النسبة للمكون: '
                . $definition->name
            );
        }

        $baseRow =
            $rowsByComponentId->get(
                $baseComponentId
            );

        if (!$baseRow) {
            throw new LogicException(
                'مكون أساس النسبة غير موجود داخل هيكل الراتب: '
                . $definition->name
            );
        }

        $baseAmount =
            $resolve($baseRow);

        return
            $baseAmount *
            ($percentage / 100);
    }


    private function quantityRateAmount(
        $row,
        $definition
    ): float {
        $quantity =
            $row->quantity !== null
                ? (float) $row->quantity
                : 0;

        $rate =
            $row->rate !== null
                ? (float) $row->rate
                : (float) (
                    $definition->default_rate
                    ?? 0
                );

        return $quantity * $rate;
    }


    private function formulaAmount(
        $row,
        $definition,
        array $rowsByCode,
        callable $resolve
    ): float {
        $formula =
            trim(
                (string) (
                    $row->formula
                    ?: $definition->formula
                )
            );

        if ($formula === '') {
            throw new LogicException(
                'لم يتم تحديد معادلة المكون: '
                . $definition->name
            );
        }

        preg_match_all(
            '/[A-Za-z_][A-Za-z0-9_]*/',
            $formula,
            $matches
        );

        $values = [];

        foreach (
            array_unique(
                $matches[0] ?? []
            ) as $code
        ) {
            $normalizedCode =
                strtoupper($code);

            if (
                !isset(
                    $rowsByCode[
                        $normalizedCode
                    ]
                )
            ) {
                throw new LogicException(
                    'المعادلة تحتوي على مكون غير موجود: '
                    . $code
                );
            }

            $values[$normalizedCode] =
                $resolve(
                    $rowsByCode[
                        $normalizedCode
                    ]
                );
        }

        $numericFormula =
            preg_replace_callback(
                '/[A-Za-z_][A-Za-z0-9_]*/',
                function ($match) use ($values) {
                    $code =
                        strtoupper(
                            $match[0]
                        );

                    return (string) (
                        $values[$code]
                        ?? 0
                    );
                },
                $formula
            );

        return $this->evaluateFormula(
            $numericFormula
        );
    }


    /**
     * إضافة التسويات المعتمدة للفترة.
     */
    private function calculateAdjustments(
        Employee $employee,
        PayrollPeriod $period,
        PayrollRun $run
    ): array {
        $adjustments =
            PayrollAdjustment::withoutGlobalScopes()
                ->with(
                    'salaryComponent'
                )
                ->where(
                    'tenant_id',
                    $employee->tenant_id
                )
                ->where(
                    'employee_id',
                    $employee->id
                )
                ->where(
                    'status',
                    PayrollAdjustment::STATUS_APPROVED
                )
                ->whereNull(
                    'applied_payroll_item_id'
                )
                ->whereNull(
                    'deleted_at'
                )
                ->where(function ($query) use ($period) {
                    $query
                        ->where(
                            'payroll_period_id',
                            $period->id
                        )
                        ->orWhere(function ($query) use ($period) {
                            $query
                                ->whereNull(
                                    'payroll_period_id'
                                )
                                ->whereDate(
                                    'effective_date',
                                    '>=',
                                    $period->start_date
                                )
                                ->whereDate(
                                    'effective_date',
                                    '<=',
                                    $period->end_date
                                );
                        });
                })
                ->orderBy('id')
                ->get();

        $components = [];
        $adjustmentIds = [];

        foreach ($adjustments as $adjustment) {
            if (
                strtoupper(
                    $adjustment->currency_code
                )
                !==
                strtoupper(
                    $run->currency_code
                )
            ) {
                throw new LogicException(
                    'عملة التسوية رقم '
                    . $adjustment->adjustment_number
                    . ' لا تطابق عملة تشغيل الرواتب.'
                );
            }

            $definition =
                $adjustment->salaryComponent;

            $amount =
                $this->money(
                    abs(
                        (float) $adjustment->amount
                    )
                );

            $components[] = [
                'salary_component_id' =>
                    $definition?->id,

                'component_code' =>
                    $definition?->code
                    ?: $adjustment
                        ->adjustment_number,

                'component_name' =>
                    $definition?->name
                    ?: $adjustment->reason
                    ?: 'تسوية راتب',

                'type' =>
                    $adjustment->type,

                'category' =>
                    $definition?->category
                    ?: 'adjustment',

                'source' =>
                    'manual',

                'quantity' =>
                    null,

                'rate' =>
                    null,

                'percentage' =>
                    null,

                'amount' =>
                    $amount,

                'is_taxable' =>
                    (bool) (
                        $definition?->is_taxable
                        ?? false
                    ),

                'is_subject_to_insurance' =>
                    (bool) (
                        $definition
                            ?->is_subject_to_insurance
                        ?? false
                    ),

                'reference_type' =>
                    PayrollAdjustment::class,

                'reference_id' =>
                    $adjustment->id,

                'metadata' => [
                    'adjustment_number' =>
                        $adjustment
                            ->adjustment_number,

                    'effective_date' =>
                        $adjustment
                            ->effective_date
                            ?->toDateString(),

                    'reason' =>
                        $adjustment->reason,

                    'affects_net_salary' =>
                        $definition
                            ? (bool) $definition
                                ->affects_net_salary
                            : true,
                ],
            ];

            $adjustmentIds[] =
                $adjustment->id;
        }

        return [
            'components' =>
                $components,

            'adjustment_ids' =>
                $adjustmentIds,
        ];
    }


    /**
     * ضمان وجود مكون الراتب الأساسي.
     */
    private function ensureBasicSalaryComponent(
        Collection $components,
        EmployeeSalaryStructure $structure,
        float $prorationRatio
    ): Collection {
        $basicComponent =
            $components->first(
                function ($component) {
                    $code =
                        strtoupper(
                            (string) (
                                $component[
                                    'component_code'
                                ] ?? ''
                            )
                        );

                    $category =
                        strtolower(
                            (string) (
                                $component[
                                    'category'
                                ] ?? ''
                            )
                        );

                    return
                        in_array(
                            $code,
                            [
                                'BASIC',
                                'BASIC_SALARY',
                                'BASE_SALARY',
                            ],
                            true
                        )
                        ||
                        in_array(
                            $category,
                            [
                                'basic',
                                'basic_salary',
                                'base_salary',
                            ],
                            true
                        );
                }
            );

        if ($basicComponent) {
            return $components;
        }

        $components->prepend([
            'salary_component_id' =>
                null,

            'component_code' =>
                'BASIC',

            'component_name' =>
                'الراتب الأساسي',

            'type' =>
                'earning',

            'category' =>
                'basic_salary',

            'source' =>
                'system',

            'quantity' =>
                null,

            'rate' =>
                null,

            'percentage' =>
                null,

            'amount' =>
                $this->money(
                    (float) $structure
                        ->basic_salary *
                    $prorationRatio
                ),

            'is_taxable' =>
                false,

            'is_subject_to_insurance' =>
                true,

            'reference_type' =>
                EmployeeSalaryStructure::class,

            'reference_id' =>
                $structure->id,

            'metadata' => [
                'affects_net_salary' =>
                    true,

                'is_proratable' =>
                    true,

                'proration_ratio' =>
                    $prorationRatio,

                'original_amount' =>
                    $this->money(
                        $structure->basic_salary
                    ),

                'generated_by_system' =>
                    true,
            ],
        ]);

        return $components;
    }


    /**
     * حساب الإجماليات النهائية.
     */
    private function calculateTotals(
        Collection $components
    ): array {
        $totalEarnings = 0;
        $totalDeductions = 0;
        $basicSalary = 0;
        $unproratedBasicSalary = 0;

        foreach ($components as $component) {
            $amount =
                $this->money(
                    $component['amount'] ?? 0
                );

            $affectsNet =
                $component['metadata']
                    ['affects_net_salary']
                ?? true;

            $code =
                strtoupper(
                    (string) (
                        $component[
                            'component_code'
                        ] ?? ''
                    )
                );

            $category =
                strtolower(
                    (string) (
                        $component[
                            'category'
                        ] ?? ''
                    )
                );

            $isBasic =
                in_array(
                    $code,
                    [
                        'BASIC',
                        'BASIC_SALARY',
                        'BASE_SALARY',
                    ],
                    true
                )
                ||
                in_array(
                    $category,
                    [
                        'basic',
                        'basic_salary',
                        'base_salary',
                    ],
                    true
                );

            if ($isBasic) {
                $basicSalary = $amount;

                $ratio =
                    (float) (
                        $component['metadata']
                            ['proration_ratio']
                        ?? 1
                    );

                $unproratedBasicSalary =
                    $ratio > 0
                        ? $amount / $ratio
                        : $amount;
            }

            if (!$affectsNet) {
                continue;
            }

            if (
                $component['type']
                    === 'earning'
            ) {
                $totalEarnings += $amount;
            }

            if (
                $component['type']
                    === 'deduction'
            ) {
                $totalDeductions += $amount;
            }
        }

        $totalEarnings =
            $this->money(
                $totalEarnings
            );

        $totalDeductions =
            $this->money(
                $totalDeductions
            );

        return [
            'basic_salary' =>
                $this->money(
                    $basicSalary
                ),

            'unprorated_basic_salary' =>
                $this->money(
                    $unproratedBasicSalary
                ),

            'gross_salary' =>
                $totalEarnings,

            'total_earnings' =>
                $totalEarnings,

            'total_deductions' =>
                $totalDeductions,

            'net_salary' =>
                $this->money(
                    $totalEarnings -
                    $totalDeductions
                ),
        ];
    }


    /**
     * بيانات الحضور المستخدمة في احتساب النسبة.
     */
    private function normalizeWorkMetrics(
        array $metrics
    ): array {
        $scheduledWorkDays =
            max(
                0,
                (float) (
                    $metrics[
                        'scheduled_work_days'
                    ] ?? 0
                )
            );

        $actualWorkDays =
            max(
                0,
                (float) (
                    $metrics[
                        'actual_work_days'
                    ] ?? 0
                )
            );

        $absentDays =
            max(
                0,
                (float) (
                    $metrics[
                        'absent_days'
                    ] ?? 0
                )
            );

        $paidLeaveDays =
            max(
                0,
                (float) (
                    $metrics[
                        'paid_leave_days'
                    ] ?? 0
                )
            );

        $unpaidLeaveDays =
            max(
                0,
                (float) (
                    $metrics[
                        'unpaid_leave_days'
                    ] ?? 0
                )
            );

        $overtimeMinutes =
            max(
                0,
                (int) (
                    $metrics[
                        'overtime_minutes'
                    ] ?? 0
                )
            );

        if (
            array_key_exists(
                'proration_ratio',
                $metrics
            )
        ) {
            $prorationRatio =
                (float) $metrics[
                    'proration_ratio'
                ];
        } elseif (
            $scheduledWorkDays > 0
        ) {
            $unpaidDays =
                $absentDays +
                $unpaidLeaveDays;

            $payableDays =
                max(
                    0,
                    $scheduledWorkDays -
                    $unpaidDays
                );

            $prorationRatio =
                $payableDays /
                $scheduledWorkDays;
        } else {
            /*
             * في حال عدم إرسال ملخص الحضور
             * لا يتم خصم أي أيام تلقائيًا.
             */
            $prorationRatio = 1;
        }

        $prorationRatio =
            max(
                0,
                min(
                    1,
                    $prorationRatio
                )
            );

        return [
            'scheduled_work_days' =>
                $scheduledWorkDays,

            'actual_work_days' =>
                $actualWorkDays,

            'absent_days' =>
                $absentDays,

            'paid_leave_days' =>
                $paidLeaveDays,

            'unpaid_leave_days' =>
                $unpaidLeaveDays,

            'overtime_minutes' =>
                $overtimeMinutes,

            'proration_ratio' =>
                round(
                    $prorationRatio,
                    6
                ),
        ];
    }


    /**
     * تنفيذ معادلة رياضية آمنة بدون eval.
     */
    private function evaluateFormula(
        string $formula
    ): float {
        $expression =
            preg_replace(
                '/\s+/',
                '',
                $formula
            );

        if (
            $expression === null ||
            $expression === ''
        ) {
            throw new LogicException(
                'معادلة مكون الراتب فارغة.'
            );
        }

        preg_match_all(
            '/\d+(?:\.\d+)?|[()+\-*\/]/',
            $expression,
            $matches
        );

        $tokens =
            $matches[0] ?? [];

        if (
            implode('', $tokens)
                !== $expression
        ) {
            throw new LogicException(
                'معادلة مكون الراتب تحتوي على رموز غير مسموحة.'
            );
        }

        $tokens =
            $this->normalizeUnaryMinus(
                $tokens
            );

        $output = [];
        $operators = [];

        $precedence = [
            '+' => 1,
            '-' => 1,
            '*' => 2,
            '/' => 2,
        ];

        foreach ($tokens as $token) {
            if (is_numeric($token)) {
                $output[] = $token;
                continue;
            }

            if ($token === '(') {
                $operators[] = $token;
                continue;
            }

            if ($token === ')') {
                while (
                    !empty($operators) &&
                    end($operators) !== '('
                ) {
                    $output[] =
                        array_pop(
                            $operators
                        );
                }

                if (
                    empty($operators) ||
                    end($operators) !== '('
                ) {
                    throw new LogicException(
                        'الأقواس في معادلة الراتب غير صحيحة.'
                    );
                }

                array_pop($operators);

                continue;
            }

            while (
                !empty($operators) &&
                end($operators) !== '(' &&
                $precedence[
                    end($operators)
                ] >= $precedence[$token]
            ) {
                $output[] =
                    array_pop(
                        $operators
                    );
            }

            $operators[] = $token;
        }

        while (!empty($operators)) {
            $operator =
                array_pop(
                    $operators
                );

            if (
                $operator === '(' ||
                $operator === ')'
            ) {
                throw new LogicException(
                    'الأقواس في معادلة الراتب غير صحيحة.'
                );
            }

            $output[] = $operator;
        }

        $stack = [];

        foreach ($output as $token) {
            if (is_numeric($token)) {
                $stack[] =
                    (float) $token;

                continue;
            }

            if (count($stack) < 2) {
                throw new LogicException(
                    'ترتيب معادلة الراتب غير صحيح.'
                );
            }

            $right =
                array_pop($stack);

            $left =
                array_pop($stack);

            $result = match ($token) {
                '+' =>
                    $left + $right,

                '-' =>
                    $left - $right,

                '*' =>
                    $left * $right,

                '/' =>
                    $right == 0
                        ? throw new LogicException(
                            'لا يمكن القسمة على صفر في معادلة الراتب.'
                        )
                        : $left / $right,

                default =>
                    throw new LogicException(
                        'عملية حسابية غير مدعومة.'
                    ),
            };

            $stack[] = $result;
        }

        if (count($stack) !== 1) {
            throw new LogicException(
                'معادلة مكون الراتب غير صحيحة.'
            );
        }

        return (float) $stack[0];
    }


    private function normalizeUnaryMinus(
        array $tokens
    ): array {
        $normalized = [];
        $previous = null;

        foreach ($tokens as $token) {
            if (
                $token === '-' &&
                (
                    $previous === null ||
                    $previous === '(' ||
                    in_array(
                        $previous,
                        ['+', '-', '*', '/'],
                        true
                    )
                )
            ) {
                $normalized[] = '0';
            }

            $normalized[] = $token;
            $previous = $token;
        }

        return $normalized;
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


    private function money(
        mixed $value
    ): float {
        return round(
            (float) $value,
            2
        );
    }
}