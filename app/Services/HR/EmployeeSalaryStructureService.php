<?php

namespace App\Services\HR;

use App\Models\Employee;
use App\Models\EmployeeSalaryComponent;
use App\Models\EmployeeSalaryStructure;
use App\Models\SalaryComponent;
use App\Models\User;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EmployeeSalaryStructureService
{
    /*
    |--------------------------------------------------------------------------
    | إنشاء مسودة هيكل راتب
    |--------------------------------------------------------------------------
    */

    public function create(
        array $data,
        User $actor
    ): EmployeeSalaryStructure {
        $this->authorizeActor($actor);

        return DB::transaction(function () use (
            $data,
            $actor
        ) {
            $tenantId =
                (int) $actor->tenant_id;

            $employee = Employee::query()
                ->where(
                    'tenant_id',
                    $tenantId
                )
                ->whereKey(
                    $data['employee_id']
                )
                ->lockForUpdate()
                ->firstOrFail();

            $existingDraft =
                EmployeeSalaryStructure::query()
                    ->where(
                        'tenant_id',
                        $tenantId
                    )
                    ->where(
                        'employee_id',
                        $employee->id
                    )
                    ->where(
                        'status',
                        EmployeeSalaryStructure::STATUS_DRAFT
                    )
                    ->lockForUpdate()
                    ->first();

            if ($existingDraft) {
                throw new DomainException(
                    'يوجد بالفعل هيكل راتب بحالة مسودة لهذا الموظف.'
                );
            }

            $version = (
                (int) EmployeeSalaryStructure::query()
                    ->where(
                        'tenant_id',
                        $tenantId
                    )
                    ->where(
                        'employee_id',
                        $employee->id
                    )
                    ->max('version')
            ) + 1;

            $structure =
                EmployeeSalaryStructure::create([
                    'tenant_id' =>
                        $tenantId,

                    'employee_id' =>
                        $employee->id,

                    'version' =>
                        $version,

                    'effective_from' =>
                        $data['effective_from'],

                    'effective_to' =>
                        $data['effective_to'] ?? null,

                    'currency_code' =>
                        $data['currency_code'],

                    'basic_salary' =>
                        0,

                    'total_earnings' =>
                        0,

                    'total_deductions' =>
                        0,

                    'net_salary' =>
                        0,

                    'status' =>
                        EmployeeSalaryStructure::STATUS_DRAFT,

                    'notes' =>
                        $data['notes'] ?? null,

                    'created_by' =>
                        $actor->id,

                    'metadata' => [
                        'created_source' => 'web',
                        'created_at' =>
                            now()->toIso8601String(),
                    ],
                ]);

            $this->syncComponents(
                $structure,
                $data['components'],
                $tenantId
            );

            return $this->recalculate(
                $structure
            );
        });
    }


    /*
    |--------------------------------------------------------------------------
    | تعديل المسودة
    |--------------------------------------------------------------------------
    */

    public function update(
        EmployeeSalaryStructure $structure,
        array $data,
        User $actor
    ): EmployeeSalaryStructure {
        $this->authorizeActor(
            $actor,
            $structure
        );

        if (!$structure->canBeEdited()) {
            throw new DomainException(
                'لا يمكن تعديل هيكل راتب بعد اعتماده. أنشئ إصدارًا جديدًا.'
            );
        }

        return DB::transaction(function () use (
            $structure,
            $data,
            $actor
        ) {
            $structure =
                EmployeeSalaryStructure::query()
                    ->where(
                        'tenant_id',
                        $actor->tenant_id
                    )
                    ->whereKey($structure->id)
                    ->lockForUpdate()
                    ->firstOrFail();

            if (!$structure->isDraft()) {
                throw new DomainException(
                    'هيكل الراتب لم يعد في حالة مسودة.'
                );
            }

            if (
                (int) $structure->employee_id !==
                (int) $data['employee_id']
            ) {
                throw new DomainException(
                    'لا يمكن تغيير الموظف المرتبط بهيكل الراتب.'
                );
            }

            $metadata =
                $structure->metadata ?? [];

            $metadata['last_update'] = [
                'user_id' =>
                    $actor->id,

                'updated_at' =>
                    now()->toIso8601String(),
            ];

            $structure->update([
                'effective_from' =>
                    $data['effective_from'],

                'effective_to' =>
                    $data['effective_to'] ?? null,

                'currency_code' =>
                    $data['currency_code'],

                'notes' =>
                    $data['notes'] ?? null,

                'metadata' =>
                    $metadata,
            ]);

            $this->syncComponents(
                $structure,
                $data['components'],
                (int) $actor->tenant_id
            );

            return $this->recalculate(
                $structure
            );
        });
    }


    /*
    |--------------------------------------------------------------------------
    | إنشاء إصدار جديد من هيكل سابق
    |--------------------------------------------------------------------------
    */

    public function createRevision(
        EmployeeSalaryStructure $source,
        Carbon|string $effectiveFrom,
        User $actor
    ): EmployeeSalaryStructure {
        $this->authorizeActor(
            $actor,
            $source
        );

        return DB::transaction(function () use (
            $source,
            $effectiveFrom,
            $actor
        ) {
            $source =
                EmployeeSalaryStructure::query()
                    ->with([
                        'components.salaryComponent',
                    ])
                    ->where(
                        'tenant_id',
                        $actor->tenant_id
                    )
                    ->whereKey($source->id)
                    ->lockForUpdate()
                    ->firstOrFail();

            if (
                !in_array(
                    $source->status,
                    [
                        EmployeeSalaryStructure::STATUS_ACTIVE,
                        EmployeeSalaryStructure::STATUS_EXPIRED,
                    ],
                    true
                )
            ) {
                throw new DomainException(
                    'يمكن إنشاء إصدار جديد من هيكل نشط أو منتهي فقط.'
                );
            }

            $existingDraft =
                EmployeeSalaryStructure::query()
                    ->where(
                        'tenant_id',
                        $actor->tenant_id
                    )
                    ->where(
                        'employee_id',
                        $source->employee_id
                    )
                    ->where(
                        'status',
                        EmployeeSalaryStructure::STATUS_DRAFT
                    )
                    ->lockForUpdate()
                    ->exists();

            if ($existingDraft) {
                throw new DomainException(
                    'يوجد إصدار مسودة لهذا الموظف بالفعل.'
                );
            }

            $version = (
                (int) EmployeeSalaryStructure::query()
                    ->where(
                        'tenant_id',
                        $actor->tenant_id
                    )
                    ->where(
                        'employee_id',
                        $source->employee_id
                    )
                    ->max('version')
            ) + 1;

            $structure =
                EmployeeSalaryStructure::create([
                    'tenant_id' =>
                        $actor->tenant_id,

                    'employee_id' =>
                        $source->employee_id,

                    'version' =>
                        $version,

                    'effective_from' =>
                        Carbon::parse(
                            $effectiveFrom
                        )->toDateString(),

                    'effective_to' =>
                        null,

                    'currency_code' =>
                        $source->currency_code,

                    'basic_salary' =>
                        $source->basic_salary,

                    'total_earnings' =>
                        $source->total_earnings,

                    'total_deductions' =>
                        $source->total_deductions,

                    'net_salary' =>
                        $source->net_salary,

                    'status' =>
                        EmployeeSalaryStructure::STATUS_DRAFT,

                    'notes' =>
                        $source->notes,

                    'created_by' =>
                        $actor->id,

                    'metadata' => [
                        'created_source' =>
                            'revision',

                        'source_structure_id' =>
                            $source->id,

                        'source_version' =>
                            $source->version,

                        'created_at' =>
                            now()->toIso8601String(),
                    ],
                ]);

            foreach ($source->components as $component) {
                EmployeeSalaryComponent::create([
                    'tenant_id' =>
                        $actor->tenant_id,

                    'salary_structure_id' =>
                        $structure->id,

                    'salary_component_id' =>
                        $component->salary_component_id,

                    'amount' =>
                        $component->amount,

                    'percentage' =>
                        $component->percentage,

                    'rate' =>
                        $component->rate,

                    'quantity' =>
                        $component->quantity,

                    'formula' =>
                        $component->formula,

                    'is_active' =>
                        $component->is_active,

                    'metadata' =>
                        $component->metadata,
                ]);
            }

            return $structure
                ->refresh()
                ->load([
                    'employee',
                    'components.salaryComponent',
                ]);
        });
    }


    /*
    |--------------------------------------------------------------------------
    | اعتماد وتفعيل هيكل الراتب
    |--------------------------------------------------------------------------
    */

    public function activate(
        EmployeeSalaryStructure $structure,
        User $actor
    ): EmployeeSalaryStructure {
        $this->authorizeActor(
            $actor,
            $structure
        );

        return DB::transaction(function () use (
            $structure,
            $actor
        ) {
            $structure =
                EmployeeSalaryStructure::query()
                    ->with([
                        'components.salaryComponent',
                    ])
                    ->where(
                        'tenant_id',
                        $actor->tenant_id
                    )
                    ->whereKey($structure->id)
                    ->lockForUpdate()
                    ->firstOrFail();

            if (!$structure->isDraft()) {
                throw new DomainException(
                    'يمكن اعتماد هيكل الراتب عندما يكون مسودة فقط.'
                );
            }

            if (
                !$structure->components()
                    ->where(
                        'is_active',
                        true
                    )
                    ->exists()
            ) {
                throw new DomainException(
                    'لا يمكن اعتماد هيكل راتب لا يحتوي على مكونات.'
                );
            }

            $structure =
                $this->recalculate(
                    $structure
                );

            if (
                (float) $structure->basic_salary <= 0
            ) {
                throw new DomainException(
                    'يجب أن يحتوي الهيكل على راتب أساسي أكبر من صفر.'
                );
            }

            $effectiveFrom =
                $structure->effective_from
                    ->copy()
                    ->startOfDay();

            /*
             * منع وجود هيكل مستقبلي يتداخل مع الهيكل الجديد.
             */
            $futureConflict =
                EmployeeSalaryStructure::query()
                    ->where(
                        'tenant_id',
                        $actor->tenant_id
                    )
                    ->where(
                        'employee_id',
                        $structure->employee_id
                    )
                    ->where(
                        'status',
                        EmployeeSalaryStructure::STATUS_ACTIVE
                    )
                    ->whereKeyNot(
                        $structure->id
                    )
                    ->whereDate(
                        'effective_from',
                        '>',
                        $effectiveFrom->toDateString()
                    )
                    ->when(
                        $structure->effective_to,
                        function ($query) use ($structure) {
                            $query->whereDate(
                                'effective_from',
                                '<=',
                                $structure->effective_to
                                    ->toDateString()
                            );
                        }
                    )
                    ->lockForUpdate()
                    ->exists();

            if ($futureConflict) {
                throw new DomainException(
                    'يوجد هيكل راتب نشط مستقبلي يتداخل مع هذا الهيكل.'
                );
            }

            /*
             * إنهاء الهيكل السابق في اليوم السابق
             * لبداية الهيكل الجديد.
             */
            $previousStructures =
                EmployeeSalaryStructure::query()
                    ->where(
                        'tenant_id',
                        $actor->tenant_id
                    )
                    ->where(
                        'employee_id',
                        $structure->employee_id
                    )
                    ->where(
                        'status',
                        EmployeeSalaryStructure::STATUS_ACTIVE
                    )
                    ->whereKeyNot(
                        $structure->id
                    )
                    ->whereDate(
                        'effective_from',
                        '<=',
                        $effectiveFrom->toDateString()
                    )
                    ->where(function ($query) use (
                        $effectiveFrom
                    ) {
                        $query
                            ->whereNull(
                                'effective_to'
                            )
                            ->orWhereDate(
                                'effective_to',
                                '>=',
                                $effectiveFrom
                                    ->toDateString()
                            );
                    })
                    ->lockForUpdate()
                    ->get();

            foreach (
                $previousStructures as $previous
            ) {
                $endDate =
                    $effectiveFrom
                        ->copy()
                        ->subDay();

                $previousStatus =
                    $endDate->isBefore(
                        now()->startOfDay()
                    )
                        ? EmployeeSalaryStructure::STATUS_EXPIRED
                        : EmployeeSalaryStructure::STATUS_ACTIVE;

                $previous->update([
                    'effective_to' =>
                        $endDate->toDateString(),

                    'status' =>
                        $previousStatus,

                    'metadata' =>
                        array_merge(
                            $previous->metadata ?? [],
                            [
                                'replaced_by' => [
                                    'structure_id' =>
                                        $structure->id,

                                    'version' =>
                                        $structure->version,

                                    'processed_at' =>
                                        now()->toIso8601String(),
                                ],
                            ]
                        ),
                ]);
            }

            $metadata =
                $structure->metadata ?? [];

            $metadata['approval'] = [
                'approved_by' =>
                    $actor->id,

                'approved_at' =>
                    now()->toIso8601String(),
            ];

            $structure->update([
                'status' =>
                    EmployeeSalaryStructure::STATUS_ACTIVE,

                'approved_by' =>
                    $actor->id,

                'approved_at' =>
                    now(),

                'metadata' =>
                    $metadata,
            ]);

            return $structure
                ->refresh()
                ->load([
                    'employee',
                    'components.salaryComponent',
                    'approvedBy',
                ]);
        });
    }


    /*
    |--------------------------------------------------------------------------
    | إلغاء المسودة
    |--------------------------------------------------------------------------
    */

    public function cancel(
        EmployeeSalaryStructure $structure,
        User $actor,
        ?string $reason = null
    ): EmployeeSalaryStructure {
        $this->authorizeActor(
            $actor,
            $structure
        );

        return DB::transaction(function () use (
            $structure,
            $actor,
            $reason
        ) {
            $structure =
                EmployeeSalaryStructure::query()
                    ->where(
                        'tenant_id',
                        $actor->tenant_id
                    )
                    ->whereKey($structure->id)
                    ->lockForUpdate()
                    ->firstOrFail();

            if (!$structure->isDraft()) {
                throw new DomainException(
                    'يمكن إلغاء هيكل الراتب عندما يكون مسودة فقط.'
                );
            }

            $metadata =
                $structure->metadata ?? [];

            $metadata['cancellation'] = [
                'reason' =>
                    $reason,

                'cancelled_by' =>
                    $actor->id,

                'cancelled_at' =>
                    now()->toIso8601String(),
            ];

            $structure->update([
                'status' =>
                    EmployeeSalaryStructure::STATUS_CANCELLED,

                'metadata' =>
                    $metadata,
            ]);

            return $structure->refresh();
        });
    }


    /*
    |--------------------------------------------------------------------------
    | حذف المسودة
    |--------------------------------------------------------------------------
    */

    public function delete(
        EmployeeSalaryStructure $structure,
        User $actor
    ): void {
        $this->authorizeActor(
            $actor,
            $structure
        );

        DB::transaction(function () use (
            $structure,
            $actor
        ) {
            $structure =
                EmployeeSalaryStructure::query()
                    ->where(
                        'tenant_id',
                        $actor->tenant_id
                    )
                    ->whereKey($structure->id)
                    ->lockForUpdate()
                    ->firstOrFail();

            if (!$structure->isDraft()) {
                throw new DomainException(
                    'يمكن حذف مسودة هيكل الراتب فقط.'
                );
            }

            $structure->components()
                ->delete();

            $structure->delete();
        });
    }


    /*
    |--------------------------------------------------------------------------
    | مزامنة مكونات الهيكل
    |--------------------------------------------------------------------------
    */

    private function syncComponents(
        EmployeeSalaryStructure $structure,
        array $rows,
        int $tenantId
    ): void {
        $componentIds = collect($rows)
            ->pluck('salary_component_id')
            ->map(
                fn ($id) => (int) $id
            )
            ->values();

        $definitions =
            SalaryComponent::query()
                ->where(
                    'tenant_id',
                    $tenantId
                )
                ->whereIn(
                    'id',
                    $componentIds
                )
                ->where(
                    'is_active',
                    true
                )
                ->get()
                ->keyBy('id');

        if (
            $definitions->count() !==
            $componentIds->unique()->count()
        ) {
            throw new DomainException(
                'يوجد مكون راتب غير متاح أو تابع لشركة أخرى.'
            );
        }

        $existing =
            EmployeeSalaryComponent::withTrashed()
                ->where(
                    'tenant_id',
                    $tenantId
                )
                ->where(
                    'salary_structure_id',
                    $structure->id
                )
                ->get()
                ->keyBy('salary_component_id');

        $savedIds = [];

        foreach ($rows as $row) {
            $componentId =
                (int) $row['salary_component_id'];

            $definition =
                $definitions->get(
                    $componentId
                );

            $values = [
                'tenant_id' =>
                    $tenantId,

                'salary_structure_id' =>
                    $structure->id,

                'salary_component_id' =>
                    $componentId,

                'amount' =>
                    $row['amount']
                    ?? $definition->default_amount,

                'percentage' =>
                    $row['percentage']
                    ?? $definition->default_percentage,

                'rate' =>
                    $row['rate']
                    ?? $definition->default_rate,

                'quantity' =>
                    $row['quantity'] ?? null,

                'formula' =>
                    $row['formula']
                    ?? $definition->formula,

                'is_active' =>
                    (bool) (
                        $row['is_active']
                        ?? true
                    ),

                'metadata' =>
                    $row['metadata'] ?? null,
            ];

            $component =
                $existing->get(
                    $componentId
                );

            if ($component) {
                if ($component->trashed()) {
                    $component->restore();
                }

                $component->fill($values);
                $component->save();
            } else {
                $component =
                    EmployeeSalaryComponent::create(
                        $values
                    );
            }

            $savedIds[] =
                $component->id;
        }

        EmployeeSalaryComponent::query()
            ->where(
                'tenant_id',
                $tenantId
            )
            ->where(
                'salary_structure_id',
                $structure->id
            )
            ->when(
                $savedIds,
                fn ($query) =>
                    $query->whereNotIn(
                        'id',
                        $savedIds
                    )
            )
            ->delete();
    }


    /*
    |--------------------------------------------------------------------------
    | إعادة حساب الهيكل
    |--------------------------------------------------------------------------
    */

    public function recalculate(
        EmployeeSalaryStructure $structure
    ): EmployeeSalaryStructure {
        $structure->load([
            'components.salaryComponent',
        ]);

        $rows = $structure->components
            ->filter(
                fn (
                    EmployeeSalaryComponent $row
                ) => $row->is_active
            )
            ->values();

        if ($rows->isEmpty()) {
            throw new DomainException(
                'هيكل الراتب لا يحتوي على مكونات نشطة.'
            );
        }

        $valuesByComponentId = [];
        $valuesByCode = [];
        $pending = $rows->keyBy(
            'salary_component_id'
        );

        /*
         * المبلغ الثابت والكمية × السعر.
         */
        foreach ($pending as $componentId => $row) {
            $definition =
                $row->salaryComponent;

            if (!$definition) {
                throw new DomainException(
                    'تعذر العثور على تعريف أحد مكونات الراتب.'
                );
            }

            if (
                $definition->calculation_method ===
                'fixed'
            ) {
                $value = round(
                    (float) (
                        $row->amount
                        ?? $definition->default_amount
                        ?? 0
                    ),
                    2
                );

                $this->storeCalculatedValue(
                    $valuesByComponentId,
                    $valuesByCode,
                    $definition,
                    $value
                );

                $pending->forget(
                    $componentId
                );
            }

            if (
                $definition->calculation_method ===
                'quantity_rate'
            ) {
                $value = round(
                    (float) (
                        $row->quantity ?? 0
                    ) *
                    (float) (
                        $row->rate
                        ?? $definition->default_rate
                        ?? 0
                    ),
                    2
                );

                $this->storeCalculatedValue(
                    $valuesByComponentId,
                    $valuesByCode,
                    $definition,
                    $value
                );

                $pending->forget(
                    $componentId
                );
            }
        }

        /*
         * حساب النسب والمعادلات حسب الاعتمادات.
         */
        $maximumPasses =
            max(5, $pending->count() + 2);

        for (
            $pass = 0;
            $pass < $maximumPasses;
            $pass++
        ) {
            $resolvedInPass = 0;

            foreach (
                $pending as $componentId => $row
            ) {
                $definition =
                    $row->salaryComponent;

                if (
                    $definition->calculation_method ===
                    'percentage'
                ) {
                    $baseComponentId =
                        (int) $definition
                            ->percentage_base_component_id;

                    if (
                        !array_key_exists(
                            $baseComponentId,
                            $valuesByComponentId
                        )
                    ) {
                        continue;
                    }

                    $percentage = (float) (
                        $row->percentage
                        ?? $definition->default_percentage
                        ?? 0
                    );

                    $value = round(
                        $valuesByComponentId[
                            $baseComponentId
                        ] *
                        ($percentage / 100),
                        2
                    );

                    $this->storeCalculatedValue(
                        $valuesByComponentId,
                        $valuesByCode,
                        $definition,
                        $value
                    );

                    $pending->forget(
                        $componentId
                    );

                    $resolvedInPass++;

                    continue;
                }

                if (
                    $definition->calculation_method ===
                    'formula'
                ) {
                    $formula = trim(
                        (string) (
                            $row->formula
                            ?? $definition->formula
                            ?? ''
                        )
                    );

                    $result =
                        $this->calculateFormula(
                            $formula,
                            $valuesByCode
                        );

                    if ($result === null) {
                        continue;
                    }

                    $value = round(
                        max(0, $result),
                        2
                    );

                    $this->storeCalculatedValue(
                        $valuesByComponentId,
                        $valuesByCode,
                        $definition,
                        $value
                    );

                    $pending->forget(
                        $componentId
                    );

                    $resolvedInPass++;
                }
            }

            if ($pending->isEmpty()) {
                break;
            }

            if ($resolvedInPass === 0) {
                break;
            }
        }

        if ($pending->isNotEmpty()) {
            $codes = $pending
                ->map(
                    fn ($row) =>
                        $row->salaryComponent?->code
                        ?? $row->salary_component_id
                )
                ->implode(', ');

            throw new DomainException(
                'تعذر حساب مكونات الراتب التالية بسبب اعتماد دائري أو معادلة غير مكتملة: ' .
                $codes
            );
        }

        $basicSalary = 0;
        $totalEarnings = 0;
        $totalDeductions = 0;

        foreach ($rows as $row) {
            $definition =
                $row->salaryComponent;

            $value =
                $valuesByComponentId[
                    $definition->id
                ] ?? 0;

            if (
                $definition->category ===
                'basic_salary'
            ) {
                $basicSalary = $value;
            }

            if ($definition->type === 'earning') {
                $totalEarnings += $value;
            }

            if ($definition->type === 'deduction') {
                $totalDeductions += $value;
            }
        }

        $basicSalary =
            round($basicSalary, 2);

        $totalEarnings =
            round($totalEarnings, 2);

        $totalDeductions =
            round($totalDeductions, 2);

        $netSalary = round(
            $totalEarnings -
            $totalDeductions,
            2
        );

        $metadata =
            $structure->metadata ?? [];

        $metadata['calculation'] = [
            'calculated_at' =>
                now()->toIso8601String(),

            'components' =>
                $valuesByCode,

            'basic_salary' =>
                $basicSalary,

            'total_earnings' =>
                $totalEarnings,

            'total_deductions' =>
                $totalDeductions,

            'net_salary' =>
                $netSalary,
        ];

        $structure->update([
            'basic_salary' =>
                $basicSalary,

            'total_earnings' =>
                $totalEarnings,

            'total_deductions' =>
                $totalDeductions,

            'net_salary' =>
                $netSalary,

            'metadata' =>
                $metadata,
        ]);

        return $structure
            ->refresh()
            ->load([
                'employee',
                'components.salaryComponent',
                'createdBy',
                'approvedBy',
            ]);
    }


    private function storeCalculatedValue(
        array &$valuesByComponentId,
        array &$valuesByCode,
        SalaryComponent $definition,
        float $value
    ): void {
        $value = round(
            max(0, $value),
            2
        );

        $valuesByComponentId[
            $definition->id
        ] = $value;

        $valuesByCode[
            strtoupper($definition->code)
        ] = $value;
    }


    /*
    |--------------------------------------------------------------------------
    | حاسبة المعادلات الآمنة
    |--------------------------------------------------------------------------
    |
    | تدعم:
    | +  -  *  /  والأقواس.
    | لا تستخدم eval حفاظًا على أمان النظام.
    |
    */

    private function calculateFormula(
        string $formula,
        array $variables
    ): ?float {
        if ($formula === '') {
            throw new DomainException(
                'معادلة مكون الراتب فارغة.'
            );
        }

        $expression =
            preg_replace(
                '/\s+/',
                '',
                strtoupper($formula)
            );

        preg_match_all(
            '/[A-Z_][A-Z0-9_]*|\d+(?:\.\d+)?|[()+\-*\/]/',
            $expression,
            $matches
        );

        $tokens =
            $matches[0] ?? [];

        if (
            implode('', $tokens) !==
            $expression
        ) {
            throw new DomainException(
                'تحتوي معادلة الراتب على رموز غير مسموحة.'
            );
        }

        foreach ($tokens as &$token) {
            if (
                preg_match(
                    '/^[A-Z_][A-Z0-9_]*$/',
                    $token
                )
            ) {
                if (
                    !array_key_exists(
                        $token,
                        $variables
                    )
                ) {
                    return null;
                }

                $token = (string) (
                    (float) $variables[$token]
                );
            }
        }

        unset($token);

        $position = 0;

        $result =
            $this->parseExpression(
                $tokens,
                $position
            );

        if ($position !== count($tokens)) {
            throw new DomainException(
                'معادلة مكون الراتب غير صحيحة.'
            );
        }

        return $result;
    }


    private function parseExpression(
        array $tokens,
        int &$position
    ): float {
        $value =
            $this->parseTerm(
                $tokens,
                $position
            );

        while (
            isset($tokens[$position]) &&
            in_array(
                $tokens[$position],
                ['+', '-'],
                true
            )
        ) {
            $operator =
                $tokens[$position++];

            $right =
                $this->parseTerm(
                    $tokens,
                    $position
                );

            $value = $operator === '+'
                ? $value + $right
                : $value - $right;
        }

        return $value;
    }


    private function parseTerm(
        array $tokens,
        int &$position
    ): float {
        $value =
            $this->parseFactor(
                $tokens,
                $position
            );

        while (
            isset($tokens[$position]) &&
            in_array(
                $tokens[$position],
                ['*', '/'],
                true
            )
        ) {
            $operator =
                $tokens[$position++];

            $right =
                $this->parseFactor(
                    $tokens,
                    $position
                );

            if (
                $operator === '/' &&
                abs($right) < 0.0000001
            ) {
                throw new DomainException(
                    'لا يمكن القسمة على صفر في معادلة الراتب.'
                );
            }

            $value = $operator === '*'
                ? $value * $right
                : $value / $right;
        }

        return $value;
    }


    private function parseFactor(
        array $tokens,
        int &$position
    ): float {
        if (!isset($tokens[$position])) {
            throw new DomainException(
                'معادلة مكون الراتب غير مكتملة.'
            );
        }

        $token =
            $tokens[$position];

        if ($token === '+') {
            $position++;

            return $this->parseFactor(
                $tokens,
                $position
            );
        }

        if ($token === '-') {
            $position++;

            return -$this->parseFactor(
                $tokens,
                $position
            );
        }

        if ($token === '(') {
            $position++;

            $value =
                $this->parseExpression(
                    $tokens,
                    $position
                );

            if (
                !isset($tokens[$position]) ||
                $tokens[$position] !== ')'
            ) {
                throw new DomainException(
                    'الأقواس في معادلة الراتب غير متطابقة.'
                );
            }

            $position++;

            return $value;
        }

        if (!is_numeric($token)) {
            throw new DomainException(
                'معادلة مكون الراتب غير صحيحة.'
            );
        }

        $position++;

        return (float) $token;
    }


    /*
    |--------------------------------------------------------------------------
    | الحماية
    |--------------------------------------------------------------------------
    */

    private function authorizeActor(
        User $actor,
        ?EmployeeSalaryStructure $structure = null
    ): void {
        if (
            !$actor->tenant_id ||
            !$actor->can('payroll.manage')
        ) {
            throw new DomainException(
                'غير مصرح لك بإدارة هياكل الرواتب.'
            );
        }

        if (
            $structure &&
            (int) $structure->tenant_id !==
            (int) $actor->tenant_id
        ) {
            throw new DomainException(
                'لا يمكن إدارة هيكل راتب تابع لشركة أخرى.'
            );
        }
    }
}