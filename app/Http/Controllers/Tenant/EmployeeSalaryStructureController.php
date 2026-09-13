<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreEmployeeSalaryStructureRequest;
use App\Http\Requests\Tenant\UpdateEmployeeSalaryStructureRequest;
use App\Models\Employee;
use App\Models\EmployeeSalaryStructure;
use App\Models\SalaryComponent;
use App\Services\HR\EmployeeSalaryStructureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class EmployeeSalaryStructureController extends Controller
{
    public function __construct(
        private readonly EmployeeSalaryStructureService
            $salaryStructureService
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
            'tenant.payroll.salary-structures.index'
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

        $status = $request->get('status');

        $employeeId = $request->integer(
            'employee_id'
        );

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

        $query =
            EmployeeSalaryStructure::query()
                ->with([
                    'employee:id,tenant_id,employee_number,department_id,job_title_id,first_name,father_name,grandfather_name,family_name',

                    'employee.department:id,tenant_id,name',

                    'employee.jobTitle:id,tenant_id,name',

                    'createdBy:id,tenant_id,name',

                    'approvedBy:id,tenant_id,name',
                ])
                ->withCount([
                    'components as components_count' =>
                        function ($query) {
                            $query->where(
                                'is_active',
                                true
                            );
                        },
                ])
                ->where(
                    'tenant_id',
                    $tenantId
                );

        if ($status === 'archived') {
            $query->onlyTrashed();
        } elseif ($status === 'all') {
            $query->withTrashed();
        } elseif (
            in_array(
                $status,
                [
                    EmployeeSalaryStructure::STATUS_DRAFT,
                    EmployeeSalaryStructure::STATUS_ACTIVE,
                    EmployeeSalaryStructure::STATUS_EXPIRED,
                    EmployeeSalaryStructure::STATUS_CANCELLED,
                ],
                true
            )
        ) {
            $query->where(
                'status',
                $status
            );
        }

        if ($employeeId) {
            $query->where(
                'employee_id',
                $employeeId
            );
        }

        if ($search !== '') {
            $query->where(function ($query) use (
                $search
            ) {
                $query
                    ->where(
                        'uuid',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'version',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhereHas(
                        'employee',
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
            });
        }

        $paginator = $query
            ->orderByDesc('id')
            ->paginate($perPage);

        $paginator->through(
            fn (
                EmployeeSalaryStructure $structure
            ) => $this->transformListItem(
                $structure
            )
        );

        return response()->json(
            $paginator
        );
    }


    /*
    |--------------------------------------------------------------------------
    | خيارات النموذج
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

        $employees = Employee::query()
            ->select([
                'id',
                'tenant_id',
                'employee_number',
                'first_name',
                'father_name',
                'grandfather_name',
                'family_name',
                'employment_status',
            ])
            ->where(
                'tenant_id',
                $tenantId
            )
            ->whereNotIn(
                'employment_status',
                ['terminated']
            )
            ->orderBy('first_name')
            ->orderBy('family_name')
            ->get()
            ->map(function (Employee $employee) {
                return [
                    'id' =>
                        $employee->id,

                    'employee_number' =>
                        $employee->employee_number,

                    'name' =>
                        $this->employeeName(
                            $employee
                        ),

                    'employment_status' =>
                        $employee->employment_status,
                ];
            })
            ->values();

        $components =
            SalaryComponent::query()
                ->select([
                    'id',
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
                    'requires_input',
                    'is_system',
                    'sort_order',
                ])
                ->where(
                    'tenant_id',
                    $tenantId
                )
                ->where(
                    'is_active',
                    true
                )
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(function (
                    SalaryComponent $component
                ) {
                    return [
                        'id' =>
                            $component->id,

                        'code' =>
                            $component->code,

                        'name' =>
                            $component->name,

                        'name_en' =>
                            $component->name_en,

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

                        'default_amount' =>
                            $component->default_amount,

                        'default_percentage' =>
                            $component->default_percentage,

                        'default_rate' =>
                            $component->default_rate,

                        'formula' =>
                            $component->formula,

                        'requires_input' =>
                            (bool) $component
                                ->requires_input,

                        'is_system' =>
                            (bool) $component
                                ->is_system,

                        'sort_order' =>
                            (int) $component
                                ->sort_order,
                    ];
                })
                ->values();

        return response()->json([
            'success' => true,

            'employees' =>
                $employees,

            'components' =>
                $components,

            'currency_code' =>
                $request->user()
                    ->tenant?->currency_code
                ?? 'SAR',

            'can_manage' =>
                $request->user()
                    ->can('payroll.manage'),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | إنشاء
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreEmployeeSalaryStructureRequest $request
    ): JsonResponse {
        try {
            $structure =
                $this->salaryStructureService
                    ->create(
                        $request->validated(),
                        $request->user()
                    );

            return response()->json([
                'success' => true,

                'message' =>
                    'تم إنشاء مسودة هيكل راتب الموظف بنجاح.',

                'salary_structure' =>
                    $this->transformDetails(
                        $structure
                    ),
            ], 201);
        } catch (\DomainException $exception) {
            return response()->json([
                'success' => false,
                'message' =>
                    $exception->getMessage(),
            ], 422);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | عرض التفاصيل
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        EmployeeSalaryStructure $salaryStructure
    ): JsonResponse {
        abort_unless(
            $request->user()->can('payroll.view'),
            403
        );

        $this->assertSameTenant(
            $request,
            $salaryStructure
        );

        $salaryStructure->load([
            'employee.department',
            'employee.jobTitle',
            'components.salaryComponent',
            'createdBy',
            'approvedBy',
        ]);

        return response()->json([
            'success' => true,

            'salary_structure' =>
                $this->transformDetails(
                    $salaryStructure
                ),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | تعديل المسودة
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateEmployeeSalaryStructureRequest $request,
        EmployeeSalaryStructure $salaryStructure
    ): JsonResponse {
        try {
            $structure =
                $this->salaryStructureService
                    ->update(
                        $salaryStructure,
                        $request->validated(),
                        $request->user()
                    );

            return response()->json([
                'success' => true,

                'message' =>
                    'تم تحديث مسودة هيكل الراتب بنجاح.',

                'salary_structure' =>
                    $this->transformDetails(
                        $structure
                    ),
            ]);
        } catch (\DomainException $exception) {
            return response()->json([
                'success' => false,
                'message' =>
                    $exception->getMessage(),
            ], 422);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | إعادة الحساب
    |--------------------------------------------------------------------------
    */

    public function recalculate(
        Request $request,
        EmployeeSalaryStructure $salaryStructure
    ): JsonResponse {
        abort_unless(
            $request->user()->can('payroll.manage'),
            403
        );

        $this->assertSameTenant(
            $request,
            $salaryStructure
        );

        try {
            $structure =
                $this->salaryStructureService
                    ->recalculate(
                        $salaryStructure
                    );

            return response()->json([
                'success' => true,

                'message' =>
                    'تمت إعادة حساب هيكل الراتب بنجاح.',

                'salary_structure' =>
                    $this->transformDetails(
                        $structure
                    ),
            ]);
        } catch (\DomainException $exception) {
            return response()->json([
                'success' => false,
                'message' =>
                    $exception->getMessage(),
            ], 422);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | اعتماد الهيكل
    |--------------------------------------------------------------------------
    */

    public function activate(
        Request $request,
        EmployeeSalaryStructure $salaryStructure
    ): JsonResponse {
        abort_unless(
            $request->user()->can('payroll.manage'),
            403
        );

        $this->assertSameTenant(
            $request,
            $salaryStructure
        );

        try {
            $structure =
                $this->salaryStructureService
                    ->activate(
                        $salaryStructure,
                        $request->user()
                    );

            return response()->json([
                'success' => true,

                'message' =>
                    'تم اعتماد وتفعيل هيكل الراتب بنجاح.',

                'salary_structure' =>
                    $this->transformDetails(
                        $structure
                    ),
            ]);
        } catch (\DomainException $exception) {
            return response()->json([
                'success' => false,
                'message' =>
                    $exception->getMessage(),
            ], 422);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | إنشاء إصدار جديد
    |--------------------------------------------------------------------------
    */

    public function revision(
        Request $request,
        EmployeeSalaryStructure $salaryStructure
    ): JsonResponse {
        abort_unless(
            $request->user()->can('payroll.manage'),
            403
        );

        $this->assertSameTenant(
            $request,
            $salaryStructure
        );

        $validated = $request->validate([
            'effective_from' => [
                'required',
                'date',
            ],
        ], [
            'effective_from.required' =>
                'تاريخ بداية الإصدار الجديد مطلوب.',

            'effective_from.date' =>
                'تاريخ بداية الإصدار الجديد غير صحيح.',
        ]);

        try {
            $structure =
                $this->salaryStructureService
                    ->createRevision(
                        $salaryStructure,
                        $validated[
                            'effective_from'
                        ],
                        $request->user()
                    );

            return response()->json([
                'success' => true,

                'message' =>
                    'تم إنشاء إصدار جديد بحالة مسودة.',

                'salary_structure' =>
                    $this->transformDetails(
                        $structure
                    ),
            ], 201);
        } catch (\DomainException $exception) {
            return response()->json([
                'success' => false,
                'message' =>
                    $exception->getMessage(),
            ], 422);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | إلغاء المسودة
    |--------------------------------------------------------------------------
    */

    public function cancel(
        Request $request,
        EmployeeSalaryStructure $salaryStructure
    ): JsonResponse {
        abort_unless(
            $request->user()->can('payroll.manage'),
            403
        );

        $this->assertSameTenant(
            $request,
            $salaryStructure
        );

        $validated = $request->validate([
            'reason' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        try {
            $structure =
                $this->salaryStructureService
                    ->cancel(
                        $salaryStructure,
                        $request->user(),
                        $validated['reason'] ?? null
                    );

            return response()->json([
                'success' => true,

                'message' =>
                    'تم إلغاء مسودة هيكل الراتب.',

                'salary_structure' =>
                    $this->transformDetails(
                        $structure
                    ),
            ]);
        } catch (\DomainException $exception) {
            return response()->json([
                'success' => false,
                'message' =>
                    $exception->getMessage(),
            ], 422);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | حذف المسودة
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        EmployeeSalaryStructure $salaryStructure
    ): JsonResponse {
        abort_unless(
            $request->user()->can('payroll.manage'),
            403
        );

        $this->assertSameTenant(
            $request,
            $salaryStructure
        );

        try {
            $this->salaryStructureService
                ->delete(
                    $salaryStructure,
                    $request->user()
                );

            return response()->json([
                'success' => true,

                'message' =>
                    'تم حذف مسودة هيكل الراتب بنجاح.',
            ]);
        } catch (\DomainException $exception) {
            return response()->json([
                'success' => false,
                'message' =>
                    $exception->getMessage(),
            ], 422);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | تحويل سجل الجدول
    |--------------------------------------------------------------------------
    */

    private function transformListItem(
        EmployeeSalaryStructure $structure
    ): array {
        return [
            'id' =>
                $structure->id,

            'uuid' =>
                $structure->uuid,

            'employee_id' =>
                $structure->employee_id,

            'employee' => [
                'id' =>
                    $structure->employee?->id,

                'employee_number' =>
                    $structure->employee
                        ?->employee_number,

                'name' =>
                    $structure->employee
                        ? $this->employeeName(
                            $structure->employee
                        )
                        : '-',

                'department' =>
                    $structure->employee
                        ?->department?->name,

                'job_title' =>
                    $structure->employee
                        ?->jobTitle?->name,
            ],

            'version' =>
                (int) $structure->version,

            'effective_from' =>
                $structure->effective_from
                    ?->format('Y-m-d'),

            'effective_to' =>
                $structure->effective_to
                    ?->format('Y-m-d'),

            'currency_code' =>
                $structure->currency_code,

            'basic_salary' =>
                (float) $structure->basic_salary,

            'total_earnings' =>
                (float) $structure->total_earnings,

            'total_deductions' =>
                (float) $structure->total_deductions,

            'net_salary' =>
                (float) $structure->net_salary,

            'status' =>
                $structure->status,

            'status_label' =>
                $structure->status_label,

            'status_color' =>
                $structure->status_color,

            'components_count' =>
                (int) $structure
                    ->components_count,

            'approved_at' =>
                $structure->approved_at
                    ?->format('Y-m-d H:i'),

            'approved_by' =>
                $structure->approvedBy?->name,

            'created_at' =>
                $structure->created_at
                    ?->format('Y-m-d H:i'),

            'deleted_at' =>
                $structure->deleted_at
                    ?->format('Y-m-d H:i'),

            'can_edit' =>
                $structure->canBeEdited(),

            'can_activate' =>
                $structure->canBeActivated(),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | تحويل التفاصيل
    |--------------------------------------------------------------------------
    */

    private function transformDetails(
        EmployeeSalaryStructure $structure
    ): array {
        $structure->loadMissing([
            'employee.department',
            'employee.jobTitle',
            'components.salaryComponent',
            'createdBy',
            'approvedBy',
        ]);

        $calculatedComponents =
            data_get(
                $structure->metadata,
                'calculation.components',
                []
            );

        return [
            'id' =>
                $structure->id,

            'uuid' =>
                $structure->uuid,

            'employee_id' =>
                $structure->employee_id,

            'employee' => [
                'id' =>
                    $structure->employee?->id,

                'employee_number' =>
                    $structure->employee
                        ?->employee_number,

                'name' =>
                    $structure->employee
                        ? $this->employeeName(
                            $structure->employee
                        )
                        : '-',

                'department' =>
                    $structure->employee
                        ?->department?->name,

                'job_title' =>
                    $structure->employee
                        ?->jobTitle?->name,
            ],

            'version' =>
                (int) $structure->version,

            'effective_from' =>
                $structure->effective_from
                    ?->format('Y-m-d'),

            'effective_to' =>
                $structure->effective_to
                    ?->format('Y-m-d'),

            'currency_code' =>
                $structure->currency_code,

            'basic_salary' =>
                (float) $structure->basic_salary,

            'total_earnings' =>
                (float) $structure->total_earnings,

            'total_deductions' =>
                (float) $structure->total_deductions,

            'net_salary' =>
                (float) $structure->net_salary,

            'status' =>
                $structure->status,

            'status_label' =>
                $structure->status_label,

            'status_color' =>
                $structure->status_color,

            'notes' =>
                $structure->notes,

            'created_by' =>
                $structure->createdBy?->name,

            'approved_by' =>
                $structure->approvedBy?->name,

            'approved_at' =>
                $structure->approved_at
                    ?->format('Y-m-d H:i'),

            'created_at' =>
                $structure->created_at
                    ?->format('Y-m-d H:i'),

            'can_edit' =>
                $structure->canBeEdited(),

            'can_activate' =>
                $structure->canBeActivated(),

            'components' =>
                $structure->components
                    ->map(function ($row) use (
                        $calculatedComponents
                    ) {
                        $definition =
                            $row->salaryComponent;

                        $code = strtoupper(
                            (string) $definition?->code
                        );

                        return [
                            'id' =>
                                $row->id,

                            'salary_component_id' =>
                                $row
                                    ->salary_component_id,

                            'code' =>
                                $definition?->code,

                            'name' =>
                                $definition?->name,

                            'type' =>
                                $definition?->type,

                            'type_label' =>
                                $definition?->type_label,

                            'category' =>
                                $definition?->category,

                            'category_label' =>
                                $definition
                                    ?->category_label,

                            'calculation_method' =>
                                $definition
                                    ?->calculation_method,

                            'calculation_method_label' =>
                                $definition
                                    ?->calculation_method_label,

                            'amount' =>
                                $row->amount,

                            'percentage' =>
                                $row->percentage,

                            'rate' =>
                                $row->rate,

                            'quantity' =>
                                $row->quantity,

                            'formula' =>
                                $row->formula,

                            'is_active' =>
                                (bool) $row->is_active,

                            'calculated_value' =>
                                (float) (
                                    $calculatedComponents[
                                        $code
                                    ] ?? 0
                                ),
                        ];
                    })
                    ->values(),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function employeeName(
        Employee $employee
    ): string {
        return collect([
            $employee->first_name,
            $employee->father_name,
            $employee->grandfather_name,
            $employee->family_name,
        ])->filter()
            ->implode(' ');
    }


    private function assertSameTenant(
        Request $request,
        EmployeeSalaryStructure $structure
    ): void {
        abort_unless(
            (int) $structure->tenant_id ===
            (int) $request->user()->tenant_id,
            404
        );
    }
}