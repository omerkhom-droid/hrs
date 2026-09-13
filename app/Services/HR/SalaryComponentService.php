<?php

namespace App\Services\HR;

use App\Models\SalaryComponent;
use App\Models\Tenant;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class SalaryComponentService
{
    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function create(
        Tenant $tenant,
        User $actor,
        array $data
    ): SalaryComponent {
        $this->ensureActorTenant(
            $tenant,
            $actor
        );

        return DB::transaction(
            function () use (
                $tenant,
                $actor,
                $data
            ) {
                $data =
                    $this->prepareData(
                        tenant: $tenant,
                        data: $data
                    );

                $this->ensureSingleBasicSalary(
                    tenant: $tenant,
                    category:
                        $data['category']
                        ?? null
                );

                $this->validatePercentageBase(
                    tenant: $tenant,
                    data: $data
                );

                $data['tenant_id'] =
                    $tenant->id;

                $data =
                    $this->withAuditMetadata(
                        data: $data,
                        actor: $actor,
                        action: 'created'
                    );

                $component =
                    SalaryComponent::create(
                        $data
                    );

                return $component
                    ->refresh()
                    ->load(
                        'percentageBaseComponent'
                    );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        SalaryComponent $component,
        User $actor,
        array $data
    ): SalaryComponent {
        $this->ensureComponentAccess(
            $component,
            $actor
        );

        return DB::transaction(
            function () use (
                $component,
                $actor,
                $data
            ) {
                $component =
                    SalaryComponent::query()
                        ->where(
                            'tenant_id',
                            $component->tenant_id
                        )
                        ->lockForUpdate()
                        ->findOrFail(
                            $component->id
                        );

                if ($component->is_system) {
                    throw new DomainException(
                        'مكون الراتب النظامي لا يمكن تعديله.'
                    );
                }

                $tenant =
                    Tenant::query()
                        ->findOrFail(
                            $component->tenant_id
                        );

                $data =
                    $this->prepareData(
                        tenant: $tenant,
                        data: $data,
                        component: $component
                    );

                $this->ensureProtectedFieldsNotChanged(
                    $component,
                    $data
                );

                $this->ensureSingleBasicSalary(
                    tenant: $tenant,
                    category:
                        $data['category']
                        ?? $component->category,
                    ignoredComponentId:
                        $component->id
                );

                $this->validatePercentageBase(
                    tenant: $tenant,
                    data: $data,
                    component: $component
                );

                $data =
                    $this->withAuditMetadata(
                        data: $data,
                        actor: $actor,
                        action: 'updated',
                        component: $component
                    );

                $component->fill(
                    $data
                );

                $component->save();

                return $component
                    ->refresh()
                    ->load(
                        'percentageBaseComponent'
                    );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Archive
    |--------------------------------------------------------------------------
    */

    public function archive(
        SalaryComponent $component,
        User $actor
    ): void {
        $this->ensureComponentAccess(
            $component,
            $actor
        );

        DB::transaction(
            function () use (
                $component,
                $actor
            ) {
                $component =
                    SalaryComponent::query()
                        ->where(
                            'tenant_id',
                            $component->tenant_id
                        )
                        ->lockForUpdate()
                        ->findOrFail(
                            $component->id
                        );

                if ($component->is_system) {
                    throw new DomainException(
                        'لا يمكن أرشفة مكون راتب نظامي.'
                    );
                }

                if (
                    $this->hasActiveStructureUsage(
                        $component
                    )
                ) {
                    throw new DomainException(
                        'لا يمكن أرشفة المكون لأنه مستخدم في هيكل راتب نشط.'
                    );
                }

                $hasActiveDependents =
                    SalaryComponent::query()
                        ->where(
                            'tenant_id',
                            $component->tenant_id
                        )
                        ->where(
                            'percentage_base_component_id',
                            $component->id
                        )
                        ->where(
                            'is_active',
                            true
                        )
                        ->exists();

                if ($hasActiveDependents) {
                    throw new DomainException(
                        'لا يمكن أرشفة المكون لأن هناك مكونات نشطة تُحسب كنسبة منه.'
                    );
                }

                $metadata =
                    $component->metadata
                    ?? [];

                $metadata['audit'] = [
                    ...(
                        $metadata['audit']
                        ?? []
                    ),

                    'last_action' =>
                        'archived',

                    'archived_by' =>
                        $actor->id,

                    'archived_at' =>
                        now()->toIso8601String(),
                ];

                $component->forceFill([
                    'is_active' =>
                        false,

                    'metadata' =>
                        $metadata,
                ])->save();

                $component->delete();
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Restore
    |--------------------------------------------------------------------------
    */

    public function restore(
        SalaryComponent $component,
        User $actor
    ): SalaryComponent {
        $this->ensureComponentAccess(
            $component,
            $actor
        );

        return DB::transaction(
            function () use (
                $component,
                $actor
            ) {
                $component =
                    SalaryComponent::withTrashed()
                        ->where(
                            'tenant_id',
                            $component->tenant_id
                        )
                        ->lockForUpdate()
                        ->findOrFail(
                            $component->id
                        );

                if (!$component->trashed()) {
                    throw new DomainException(
                        'مكون الراتب غير مؤرشف.'
                    );
                }

                $codeExists =
                    SalaryComponent::query()
                        ->where(
                            'tenant_id',
                            $component->tenant_id
                        )
                        ->where(
                            'code',
                            $component->code
                        )
                        ->whereKeyNot(
                            $component->id
                        )
                        ->exists();

                if ($codeExists) {
                    throw new DomainException(
                        'لا يمكن استعادة المكون لأن الكود مستخدم في مكون آخر.'
                    );
                }

                $metadata =
                    $component->metadata
                    ?? [];

                $metadata['audit'] = [
                    ...(
                        $metadata['audit']
                        ?? []
                    ),

                    'last_action' =>
                        'restored',

                    'restored_by' =>
                        $actor->id,

                    'restored_at' =>
                        now()->toIso8601String(),
                ];

                $component->restore();

                $component->forceFill([
                    'is_active' =>
                        true,

                    'metadata' =>
                        $metadata,
                ])->save();

                return $component
                    ->refresh()
                    ->load(
                        'percentageBaseComponent'
                    );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Data Preparation
    |--------------------------------------------------------------------------
    */

    private function prepareData(
        Tenant $tenant,
        array $data,
        ?SalaryComponent $component = null
    ): array {
        $method =
            $data['calculation_method']
            ?? $component?->calculation_method
            ?? SalaryComponent::METHOD_FIXED;

        $data['calculation_method'] =
            $method;

        $data['code'] =
            strtoupper(
                trim(
                    (string) (
                        $data['code']
                        ?? $component?->code
                        ?? ''
                    )
                )
            );

        $data['name'] =
            trim(
                (string) (
                    $data['name']
                    ?? $component?->name
                    ?? ''
                )
            );

        $data['name_en'] =
            filled(
                $data['name_en']
                ?? null
            )
                ? trim(
                    (string) $data[
                        'name_en'
                    ]
                )
                : null;

        $data['sort_order'] =
            (int) (
                $data['sort_order']
                ?? $component?->sort_order
                ?? 0
            );

        /*
         * إزالة الحقول غير المستخدمة
         * بناءً على طريقة الحساب.
         */
        switch ($method) {
            case SalaryComponent::METHOD_FIXED:
                $data['default_amount'] =
                    round(
                        (float) (
                            $data[
                                'default_amount'
                            ] ?? 0
                        ),
                        4
                    );

                $data[
                    'percentage_base_component_id'
                ] = null;

                $data['default_percentage'] =
                    null;

                $data['default_rate'] =
                    null;

                $data['formula'] =
                    null;

                break;

            case SalaryComponent::METHOD_PERCENTAGE:
                $data['default_amount'] =
                    0;

                $data['default_percentage'] =
                    round(
                        (float) (
                            $data[
                                'default_percentage'
                            ] ?? 0
                        ),
                        4
                    );

                $data['default_rate'] =
                    null;

                $data['formula'] =
                    null;

                break;

            case SalaryComponent::METHOD_FORMULA:
                $data['default_amount'] =
                    0;

                $data[
                    'percentage_base_component_id'
                ] = null;

                $data['default_percentage'] =
                    null;

                $data['default_rate'] =
                    null;

                $data['formula'] =
                    trim(
                        (string) (
                            $data['formula']
                            ?? ''
                        )
                    );

                break;

            case SalaryComponent::METHOD_QUANTITY_RATE:
                $data['default_amount'] =
                    0;

                $data[
                    'percentage_base_component_id'
                ] = null;

                $data['default_percentage'] =
                    null;

                $data['default_rate'] =
                    round(
                        (float) (
                            $data[
                                'default_rate'
                            ] ?? 0
                        ),
                        4
                    );

                $data['formula'] =
                    null;

                break;

            default:
                throw new DomainException(
                    'طريقة حساب مكون الراتب غير مدعومة.'
                );
        }

        $booleanDefaults = [
            'is_taxable' =>
                false,

            'is_subject_to_insurance' =>
                false,

            'is_included_in_overtime_base' =>
                false,

            'is_proratable' =>
                true,

            'is_recurring' =>
                true,

            'requires_input' =>
                false,

            'affects_net_salary' =>
                true,

            'is_active' =>
                true,
        ];

        foreach (
            $booleanDefaults
            as $field => $default
        ) {
            if (
                array_key_exists(
                    $field,
                    $data
                )
            ) {
                $data[$field] =
                    (bool) $data[$field];

                continue;
            }

            if ($component) {
                $data[$field] =
                    (bool) $component
                        ->getAttribute(
                            $field
                        );

                continue;
            }

            $data[$field] =
                $default;
        }

        /*
         * لا يسمح للطلب بإنشاء
         * مكون نظامي.
         */
        unset(
            $data['is_system'],
            $data['tenant_id'],
            $data['uuid']
        );

        return $data;
    }


    /*
    |--------------------------------------------------------------------------
    | Percentage Validation
    |--------------------------------------------------------------------------
    */

    private function validatePercentageBase(
        Tenant $tenant,
        array $data,
        ?SalaryComponent $component = null
    ): void {
        $method =
            $data['calculation_method']
            ?? $component?->calculation_method;

        if (
            $method !==
            SalaryComponent::METHOD_PERCENTAGE
        ) {
            return;
        }

        $baseComponentId =
            $data[
                'percentage_base_component_id'
            ]
            ?? $component
                ?->percentage_base_component_id;

        if (!$baseComponentId) {
            throw new DomainException(
                'يجب تحديد المكون الأساسي لحساب النسبة.'
            );
        }

        if (
            $component &&
            (int) $component->id ===
            (int) $baseComponentId
        ) {
            throw new DomainException(
                'لا يمكن احتساب المكون كنسبة من نفسه.'
            );
        }

        $baseComponent =
            SalaryComponent::query()
                ->where(
                    'tenant_id',
                    $tenant->id
                )
                ->where(
                    'is_active',
                    true
                )
                ->findOrFail(
                    $baseComponentId
                );

        if (
            !$baseComponent->isEarning()
        ) {
            throw new DomainException(
                'المكون الأساسي لحساب النسبة يجب أن يكون استحقاقًا.'
            );
        }

        /*
         * منع الحلقة الدائرية:
         * A يعتمد على B و B يعتمد على A.
         */
        $visited = [];

        $current =
            $baseComponent;

        while ($current) {
            if (
                in_array(
                    (int) $current->id,
                    $visited,
                    true
                )
            ) {
                throw new DomainException(
                    'تم اكتشاف اعتماد دائري بين مكونات الرواتب.'
                );
            }

            $visited[] =
                (int) $current->id;

            if (
                $component &&
                (int) $current->id ===
                (int) $component->id
            ) {
                throw new DomainException(
                    'لا يمكن إنشاء اعتماد دائري بين مكونات الرواتب.'
                );
            }

            if (
                !$current
                    ->percentage_base_component_id
            ) {
                break;
            }

            $current =
                SalaryComponent::query()
                    ->where(
                        'tenant_id',
                        $tenant->id
                    )
                    ->find(
                        $current
                            ->percentage_base_component_id
                    );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Basic Salary Validation
    |--------------------------------------------------------------------------
    */

    private function ensureSingleBasicSalary(
        Tenant $tenant,
        ?string $category,
        ?int $ignoredComponentId = null
    ): void {
        if (
            $category !==
            'basic_salary'
        ) {
            return;
        }

        $exists =
            SalaryComponent::query()
                ->where(
                    'tenant_id',
                    $tenant->id
                )
                ->where(
                    'category',
                    'basic_salary'
                )
                ->when(
                    $ignoredComponentId,
                    fn ($query) =>
                        $query->where(
                            'id',
                            '!=',
                            $ignoredComponentId
                        )
                )
                ->exists();

        if ($exists) {
            throw new DomainException(
                'يوجد مكون راتب أساسي مسجل مسبقًا لهذه الشركة.'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Protected Fields
    |--------------------------------------------------------------------------
    */

    private function ensureProtectedFieldsNotChanged(
        SalaryComponent $component,
        array $data
    ): void {
        if (
            !$this->hasHistoricalUsage(
                $component
            )
        ) {
            return;
        }

        $protectedFields = [
            'code',
            'type',
            'category',
            'calculation_method',
            'percentage_base_component_id',
        ];

        foreach (
            $protectedFields
            as $field
        ) {
            if (
                !array_key_exists(
                    $field,
                    $data
                )
            ) {
                continue;
            }

            $oldValue =
                $component
                    ->getAttribute(
                        $field
                    );

            $newValue =
                $data[$field];

            if (
                (string) $oldValue !==
                (string) $newValue
            ) {
                throw new DomainException(
                    'لا يمكن تغيير الحقل [' .
                    $field .
                    '] لأن المكون مستخدم في بيانات رواتب سابقة.'
                );
            }
        }
    }


    private function hasHistoricalUsage(
        SalaryComponent $component
    ): bool {
        $usedInStructures =
            DB::table(
                'employee_salary_components'
            )
                ->where(
                    'salary_component_id',
                    $component->id
                )
                ->exists();

        if ($usedInStructures) {
            return true;
        }

        return DB::table(
            'payroll_run_item_components'
        )
            ->where(
                'salary_component_id',
                $component->id
            )
            ->exists();
    }


    private function hasActiveStructureUsage(
        SalaryComponent $component
    ): bool {
        return DB::table(
            'employee_salary_components'
        )
            ->where(
                'salary_component_id',
                $component->id
            )
            ->where(
                'is_active',
                true
            )
            ->whereNull(
                'deleted_at'
            )
            ->exists();
    }


    /*
    |--------------------------------------------------------------------------
    | Audit Metadata
    |--------------------------------------------------------------------------
    */

    private function withAuditMetadata(
        array $data,
        User $actor,
        string $action,
        ?SalaryComponent $component = null
    ): array {
        $existingMetadata =
            $component?->metadata
            ?? [];

        $requestMetadata =
            isset($data['metadata']) &&
            is_array($data['metadata'])
                ? $data['metadata']
                : [];

        $metadata =
            array_replace_recursive(
                $existingMetadata,
                $requestMetadata
            );

        $metadata['audit'] = [
            ...(
                $metadata['audit']
                ?? []
            ),

            'last_action' =>
                $action,

            'last_actor_id' =>
                $actor->id,

            'last_action_at' =>
                now()->toIso8601String(),
        ];

        if (
            $action === 'created'
        ) {
            $metadata['audit'][
                'created_by'
            ] = $actor->id;

            $metadata['audit'][
                'created_at'
            ] = now()
                ->toIso8601String();
        }

        $data['metadata'] =
            $metadata;

        return $data;
    }


    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

    private function ensureActorTenant(
        Tenant $tenant,
        User $actor
    ): void {
        if (
            (int) $actor->tenant_id !==
            (int) $tenant->id
        ) {
            throw new DomainException(
                'لا يمكن إدارة مكونات رواتب شركة أخرى.'
            );
        }

        if (
            !$actor->can(
                'payroll.manage'
            )
        ) {
            throw new DomainException(
                'ليس لديك صلاحية إدارة مكونات الرواتب.'
            );
        }
    }


    private function ensureComponentAccess(
        SalaryComponent $component,
        User $actor
    ): void {
        if (
            (int) $component->tenant_id !==
            (int) $actor->tenant_id
        ) {
            throw new DomainException(
                'لا يمكن إدارة مكون راتب تابع لشركة أخرى.'
            );
        }

        if (
            !$actor->can(
                'payroll.manage'
            )
        ) {
            throw new DomainException(
                'ليس لديك صلاحية إدارة مكونات الرواتب.'
            );
        }
    }
}