<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreSalaryComponentRequest;
use App\Http\Requests\Tenant\UpdateSalaryComponentRequest;
use App\Models\SalaryComponent;
use App\Models\Tenant;
use App\Services\HR\SalaryComponentService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalaryComponentController extends Controller
{
    public function __construct(
        private readonly SalaryComponentService $componentService
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Page
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request
    ): View {
        $this->ensureCanView(
            $request
        );

        return view(
            'tenant.payroll.salary-components.index'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Data
    |--------------------------------------------------------------------------
    */

    public function data(
        Request $request
    ): JsonResponse {
        $this->ensureCanView(
            $request
        );

        $tenant =
            $this->resolveTenant(
                $request
            );

        $search = trim(
            (string) $request->input(
                'search',
                ''
            )
        );

        $type =
            $request->input(
                'type'
            );

        $category =
            $request->input(
                'category'
            );

        $method =
            $request->input(
                'calculation_method'
            );

        $status =
            $request->input(
                'status'
            );

        $perPage = min(
            max(
                (int) $request->input(
                    'per_page',
                    15
                ),
                10
            ),
            100
        );

        $query =
            SalaryComponent::query()
                ->where(
                    'tenant_id',
                    $tenant->id
                )
                ->with([
                    'percentageBaseComponent:id,tenant_id,code,name,type',
                ])
                ->select([
                    'id',
                    'uuid',
                    'tenant_id',
                    'code',
                    'name',
                    'name_en',
                    'type',
                    'category',
                    'calculation_method',
                    'percentage_base_component_id',
                    'default_amount',
                    'default_percentage',
                    'default_rate',
                    'formula',
                    'is_taxable',
                    'is_subject_to_insurance',
                    'is_included_in_overtime_base',
                    'is_proratable',
                    'is_recurring',
                    'requires_input',
                    'affects_net_salary',
                    'is_system',
                    'is_active',
                    'sort_order',
                    'metadata',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                ]);

        /*
         * الأرشيف يجب تحديده صراحة.
         */
        if ($status === 'archived') {
            $query->onlyTrashed();
        } elseif ($status === 'all') {
            $query->withTrashed();
        } elseif ($status === 'active') {
            $query->where(
                'is_active',
                true
            );
        } elseif ($status === 'inactive') {
            $query->where(
                'is_active',
                false
            );
        }

        if ($search !== '') {
            $query->where(
                function ($query) use (
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
                        )
                        ->orWhere(
                            'name_en',
                            'like',
                            "%{$search}%"
                        );
                }
            );
        }

        if (
            $type &&
            array_key_exists(
                $type,
                SalaryComponent::TYPES
            )
        ) {
            $query->where(
                'type',
                $type
            );
        }

        if (
            $category &&
            array_key_exists(
                $category,
                SalaryComponent::CATEGORIES
            )
        ) {
            $query->where(
                'category',
                $category
            );
        }

        if (
            $method &&
            array_key_exists(
                $method,
                SalaryComponent::
                    CALCULATION_METHODS
            )
        ) {
            $query->where(
                'calculation_method',
                $method
            );
        }

        $paginator =
            $query
                ->orderBy(
                    'sort_order'
                )
                ->orderBy(
                    'name'
                )
                ->orderBy(
                    'id'
                )
                ->paginate(
                    $perPage
                );

        $canManage =
            $request->user()
                ->can(
                    'payroll.manage'
                );

        $paginator->through(
            fn (
                SalaryComponent $component
            ) => $this->transformComponent(
                $component,
                $canManage
            )
        );

        return response()->json(
            $paginator
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Options
    |--------------------------------------------------------------------------
    */

    public function options(
        Request $request
    ): JsonResponse {
        $this->ensureCanView(
            $request
        );

        $tenant =
            $this->resolveTenant(
                $request
            );

        $baseComponents =
            SalaryComponent::query()
                ->where(
                    'tenant_id',
                    $tenant->id
                )
                ->active()
                ->earnings()
                ->ordered()
                ->get([
                    'id',
                    'code',
                    'name',
                    'category',
                    'calculation_method',
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

                        'label' =>
                            $component->name .
                            ' (' .
                            $component->code .
                            ')',

                        'category' =>
                            $component->category,

                        'calculation_method' =>
                            $component
                                ->calculation_method,
                    ]
                )
                ->values();

        return response()->json([
            'types' =>
                SalaryComponent::TYPES,

            'categories' =>
                SalaryComponent::CATEGORIES,

            'calculation_methods' =>
                SalaryComponent::
                    CALCULATION_METHODS,

            'base_components' =>
                $baseComponents,

            'can_manage' =>
                $request->user()
                    ->can(
                        'payroll.manage'
                    ),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreSalaryComponentRequest $request
    ): JsonResponse {
        $tenant =
            $this->resolveTenant(
                $request
            );

        try {
            $component =
                $this->componentService
                    ->create(
                        tenant: $tenant,
                        actor: $request->user(),
                        data: $request->validated()
                    );

            return response()->json([
                'success' =>
                    true,

                'message' =>
                    'تم إنشاء مكون الراتب بنجاح.',

                'salary_component' =>
                    $this->transformComponent(
                        $component,
                        true
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
    | Show
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        SalaryComponent $salaryComponent
    ): JsonResponse {
        $this->ensureCanView(
            $request
        );

        $this->ensureComponentTenant(
            $request,
            $salaryComponent
        );

        $salaryComponent->load(
            'percentageBaseComponent:id,tenant_id,code,name,type'
        );

        return response()->json([
            'success' =>
                true,

            'salary_component' =>
                $this->transformComponent(
                    $salaryComponent,
                    $request->user()
                        ->can(
                            'payroll.manage'
                        )
                ),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateSalaryComponentRequest $request,
        SalaryComponent $salaryComponent
    ): JsonResponse {
        try {
            $salaryComponent =
                $this->componentService
                    ->update(
                        component:
                            $salaryComponent,

                        actor:
                            $request->user(),

                        data:
                            $request->validated()
                    );

            return response()->json([
                'success' =>
                    true,

                'message' =>
                    'تم تحديث مكون الراتب بنجاح.',

                'salary_component' =>
                    $this->transformComponent(
                        $salaryComponent,
                        true
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
    | Archive
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        SalaryComponent $salaryComponent
    ): JsonResponse {
        $this->ensureCanManage(
            $request
        );

        $this->ensureComponentTenant(
            $request,
            $salaryComponent
        );

        try {
            $this->componentService
                ->archive(
                    component:
                        $salaryComponent,

                    actor:
                        $request->user()
                );

            return response()->json([
                'success' =>
                    true,

                'message' =>
                    'تمت أرشفة مكون الراتب بنجاح.',
            ]);
        } catch (DomainException $exception) {
            return $this->domainError(
                $exception
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Restore
    |--------------------------------------------------------------------------
    */

    public function restore(
        Request $request,
        SalaryComponent $salaryComponent
    ): JsonResponse {
        $this->ensureCanManage(
            $request
        );

        $this->ensureComponentTenant(
            $request,
            $salaryComponent
        );

        try {
            $salaryComponent =
                $this->componentService
                    ->restore(
                        component:
                            $salaryComponent,

                        actor:
                            $request->user()
                    );

            return response()->json([
                'success' =>
                    true,

                'message' =>
                    'تمت استعادة مكون الراتب بنجاح.',

                'salary_component' =>
                    $this->transformComponent(
                        $salaryComponent,
                        true
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
    | Transform
    |--------------------------------------------------------------------------
    */

    private function transformComponent(
        SalaryComponent $component,
        bool $canManage
    ): array {
        return [
            'id' =>
                $component->id,

            'uuid' =>
                $component->uuid,

            'code' =>
                $component->code,

            'name' =>
                $component->name,

            'name_en' =>
                $component->name_en,

            'display_name' =>
                $component->display_name,

            'type' =>
                $component->type,

            'type_label' =>
                $component->type_label,

            'category' =>
                $component->category,

            'category_label' =>
                $component->category_label,

            'calculation_method' =>
                $component
                    ->calculation_method,

            'calculation_method_label' =>
                $component
                    ->calculation_method_label,

            'percentage_base_component_id' =>
                $component
                    ->percentage_base_component_id,

            'percentage_base_component' =>
                $component
                    ->percentageBaseComponent
                    ? [
                        'id' =>
                            $component
                                ->percentageBaseComponent
                                ->id,

                        'code' =>
                            $component
                                ->percentageBaseComponent
                                ->code,

                        'name' =>
                            $component
                                ->percentageBaseComponent
                                ->name,
                    ]
                    : null,

            'default_amount' =>
                $component->default_amount,

            'default_percentage' =>
                $component
                    ->default_percentage,

            'default_rate' =>
                $component->default_rate,

            'formula' =>
                $component->formula,

            'is_taxable' =>
                (bool) $component
                    ->is_taxable,

            'is_subject_to_insurance' =>
                (bool) $component
                    ->is_subject_to_insurance,

            'is_included_in_overtime_base' =>
                (bool) $component
                    ->is_included_in_overtime_base,

            'is_proratable' =>
                (bool) $component
                    ->is_proratable,

            'is_recurring' =>
                (bool) $component
                    ->is_recurring,

            'requires_input' =>
                (bool) $component
                    ->requires_input,

            'affects_net_salary' =>
                (bool) $component
                    ->affects_net_salary,

            'is_system' =>
                (bool) $component
                    ->is_system,

            'is_active' =>
                (bool) $component
                    ->is_active,

            'sort_order' =>
                (int) $component
                    ->sort_order,

            'metadata' =>
                $component->metadata,

            'is_archived' =>
                $component->trashed(),

            'can_edit' =>
                $canManage &&
                !$component->is_system &&
                !$component->trashed(),

            'can_archive' =>
                $canManage &&
                !$component->is_system &&
                !$component->trashed(),

            'can_restore' =>
                $canManage &&
                $component->trashed(),

            'created_at' =>
                $component->created_at
                    ?->toIso8601String(),

            'updated_at' =>
                $component->updated_at
                    ?->toIso8601String(),

            'deleted_at' =>
                $component->deleted_at
                    ?->toIso8601String(),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Tenant
    |--------------------------------------------------------------------------
    */

    private function resolveTenant(
        Request $request
    ): Tenant {
        return Tenant::query()
            ->findOrFail(
                $request->user()
                    ->tenant_id
            );
    }


    private function ensureComponentTenant(
        Request $request,
        SalaryComponent $component
    ): void {
        if (
            (int) $component->tenant_id !==
            (int) $request->user()
                ->tenant_id
        ) {
            abort(
                404
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    */

    private function ensureCanView(
        Request $request
    ): void {
        $user =
            $request->user();

        abort_unless(
            $user &&
            (
                $user->can(
                    'payroll.view'
                ) ||
                $user->can(
                    'payroll.manage'
                )
            ),
            403,
            'ليس لديك صلاحية عرض مكونات الرواتب.'
        );
    }


    private function ensureCanManage(
        Request $request
    ): void {
        abort_unless(
            $request->user()?->can(
                'payroll.manage'
            ),
            403,
            'ليس لديك صلاحية إدارة مكونات الرواتب.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Errors
    |--------------------------------------------------------------------------
    */

    private function domainError(
        DomainException $exception
    ): JsonResponse {
        return response()->json([
            'success' =>
                false,

            'message' =>
                $exception->getMessage(),
        ], 422);
    }
}