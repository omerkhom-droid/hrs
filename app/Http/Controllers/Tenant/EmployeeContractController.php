<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreEmployeeContractRequest;
use App\Http\Requests\Tenant\TerminateEmployeeContractRequest;
use App\Http\Requests\Tenant\UpdateEmployeeContractRequest;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\Tenant;
use App\Services\HR\EmployeeContractService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use LogicException;

class EmployeeContractController extends Controller
{
    public function __construct(
        private readonly EmployeeContractService $contractService
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorizePermission(
            $request,
            'contracts.view'
        );

        return view('tenant.contracts.index');
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorizePermission(
            $request,
            'contracts.view'
        );

        $perPage = min(
            max($request->integer('per_page', 15), 10),
            100
        );

        $allowedStatuses = [
            'draft',
            'active',
            'suspended',
            'expired',
            'terminated',
            'cancelled',
        ];

        $allowedTypes = [
            'indefinite',
            'fixed_term',
            'temporary',
            'seasonal',
            'part_time',
            'training',
        ];

        $allowedSorts = [
            'contract_number',
            'start_date',
            'end_date',
            'basic_salary',
            'status',
            'created_at',
        ];

        $status = (string) $request->input('status', '');
        $type = (string) $request->input('contract_type', '');
        $sortBy = (string) $request->input(
            'sort_by',
            'created_at'
        );
        $sortDirection = strtolower(
            (string) $request->input(
                'sort_direction',
                'desc'
            )
        );

        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }

        if (!in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = 'desc';
        }

        $query = EmployeeContract::query()
            ->with([
                'employee:id,tenant_id,employee_number,department_id,job_title_id,first_name,father_name,grandfather_name,family_name',
                'employee.department:id,tenant_id,code,name',
                'employee.jobTitle:id,tenant_id,code,name',
            ])
            ->search($request->input('search'));

        $archiveStatus = (string) $request->input(
            'archive_status',
            'active'
        );

        if ($archiveStatus === 'only') {
            $query->onlyTrashed();
        } elseif ($archiveStatus === 'with') {
            $query->withTrashed();
        }

        if (in_array($status, $allowedStatuses, true)) {
            $query->withStatus($status);
        }

        if (in_array($type, $allowedTypes, true)) {
            $query->where('contract_type', $type);
        }

        if ($request->filled('employee_id')) {
            $query->where(
                'employee_id',
                $request->integer('employee_id')
            );
        }

        if ($request->filled('start_from')) {
            $query->whereDate(
                'start_date',
                '>=',
                $request->input('start_from')
            );
        }

        if ($request->filled('end_to')) {
            $query->whereDate(
                'end_date',
                '<=',
                $request->input('end_to')
            );
        }

        $expiringDays = $request->integer('expiring_days');

        if ($expiringDays > 0 && $expiringDays <= 3650) {
            $query
                ->whereIn('status', ['active', 'suspended'])
                ->whereNotNull('end_date')
                ->whereBetween('end_date', [
                    today(),
                    today()->addDays($expiringDays),
                ]);
        }

        $contracts = $query
            ->orderBy($sortBy, $sortDirection)
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $contracts->through(
            fn (EmployeeContract $contract) =>
                $this->listPayload($contract)
        );

        return response()->json($contracts);
    }

    public function options(Request $request): JsonResponse
    {
        $this->authorizeAnyPermission(
            $request,
            [
                'contracts.view',
                'contracts.create',
                'contracts.update',
            ]
        );

        $employees = Employee::query()
            ->whereNotIn('employment_status', ['terminated'])
            ->orderBy('first_name')
            ->orderBy('family_name')
            ->get([
                'id',
                'employee_number',
                'first_name',
                'father_name',
                'grandfather_name',
                'family_name',
                'employment_status',
            ])
            ->map(fn (Employee $employee) => [
                'id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'name' => $employee->full_name,
                'employment_status' => $employee->employment_status,
                'employment_status_label' =>
                    $employee->employment_status_label,
            ]);

        $tenant = $this->currentTenant($request);

        return response()->json([
            'success' => true,
            'options' => [
                'employees' => $employees,
                'default_currency' =>
                    $tenant->currency_code ?: 'SAR',
                'contract_types' => [
                    ['value' => 'indefinite', 'label' => 'غير محدد المدة'],
                    ['value' => 'fixed_term', 'label' => 'محدد المدة'],
                    ['value' => 'temporary', 'label' => 'مؤقت'],
                    ['value' => 'seasonal', 'label' => 'موسمي'],
                    ['value' => 'part_time', 'label' => 'دوام جزئي'],
                    ['value' => 'training', 'label' => 'تدريب'],
                ],
                'pay_frequencies' => [
                    ['value' => 'monthly', 'label' => 'شهري'],
                    ['value' => 'daily', 'label' => 'يومي'],
                    ['value' => 'hourly', 'label' => 'بالساعة'],
                ],
            ],
        ]);
    }

    public function store(
        StoreEmployeeContractRequest $request
    ): JsonResponse {
        try {
            $contract = $this->contractService->create(
                $this->currentTenant($request),
                $request->user(),
                $request->validated()
            );
        } catch (LogicException $exception) {
            return $this->logicError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء مسودة العقد بنجاح.',
            'contract' => $this->detailsPayload($contract),
        ], 201);
    }

    public function show(
        Request $request,
        EmployeeContract $contract
    ): JsonResponse {
        $this->authorizePermission(
            $request,
            'contracts.view'
        );

        return response()->json([
            'success' => true,
            'contract' => $this->detailsPayload(
                $this->loadContract($contract)
            ),
        ]);
    }

    public function update(
        UpdateEmployeeContractRequest $request,
        EmployeeContract $contract
    ): JsonResponse {
        try {
            $contract = $this->contractService->update(
                $contract,
                $request->user(),
                $request->validated()
            );
        } catch (LogicException $exception) {
            return $this->logicError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث مسودة العقد بنجاح.',
            'contract' => $this->detailsPayload($contract),
        ]);
    }

    public function activate(
        Request $request,
        EmployeeContract $contract
    ): JsonResponse {
        $this->authorizePermission($request, 'contracts.update');

        return $this->runLifecycleAction(
            fn () => $this->contractService->activate(
                $contract,
                $request->user()
            ),
            'تم تفعيل العقد بنجاح.'
        );
    }

    public function suspend(
        Request $request,
        EmployeeContract $contract
    ): JsonResponse {
        $this->authorizePermission($request, 'contracts.update');

        return $this->runLifecycleAction(
            fn () => $this->contractService->suspend(
                $contract,
                $request->user()
            ),
            'تم إيقاف العقد بنجاح.'
        );
    }

    public function resume(
        Request $request,
        EmployeeContract $contract
    ): JsonResponse {
        $this->authorizePermission($request, 'contracts.update');

        return $this->runLifecycleAction(
            fn () => $this->contractService->resume(
                $contract,
                $request->user()
            ),
            'تم استئناف العقد بنجاح.'
        );
    }

    public function terminate(
        TerminateEmployeeContractRequest $request,
        EmployeeContract $contract
    ): JsonResponse {
        return $this->runLifecycleAction(
            fn () => $this->contractService->terminate(
                $contract,
                $request->user(),
                $request->validated()
            ),
            'تم إنهاء العقد بنجاح.'
        );
    }

    public function cancel(
        Request $request,
        EmployeeContract $contract
    ): JsonResponse {
        $this->authorizePermission($request, 'contracts.update');

        return $this->runLifecycleAction(
            fn () => $this->contractService->cancel(
                $contract,
                $request->user()
            ),
            'تم إلغاء مسودة العقد.'
        );
    }

    public function destroy(
        Request $request,
        EmployeeContract $contract
    ): JsonResponse {
        $this->authorizePermission($request, 'contracts.update');

        try {
            $this->contractService->archive(
                $contract,
                $request->user()
            );
        } catch (LogicException $exception) {
            return $this->logicError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'تمت أرشفة العقد بنجاح.',
        ]);
    }

    public function restore(
        Request $request,
        EmployeeContract $contract
    ): JsonResponse {
        $this->authorizePermission($request, 'contracts.update');

        try {
            $contract = $this->contractService->restore(
                $contract,
                $request->user()
            );
        } catch (LogicException $exception) {
            return $this->logicError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'تمت استعادة العقد بنجاح.',
            'contract' => $this->detailsPayload($contract),
        ]);
    }

    private function runLifecycleAction(
        callable $action,
        string $message
    ): JsonResponse {
        try {
            $contract = $action();
        } catch (LogicException $exception) {
            return $this->logicError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'contract' => $this->detailsPayload($contract),
        ]);
    }

    private function currentTenant(Request $request): Tenant
    {
        return Tenant::query()->findOrFail(
            $request->user()->tenant_id
        );
    }

    private function loadContract(
        EmployeeContract $contract
    ): EmployeeContract {
        return $contract->load([
            'employee:id,tenant_id,employee_number,department_id,job_title_id,first_name,father_name,grandfather_name,family_name',
            'employee.department:id,tenant_id,code,name',
            'employee.jobTitle:id,tenant_id,code,name',
            'renewedFrom:id,tenant_id,employee_id,contract_number',
            'renewals:id,tenant_id,employee_id,renewed_from_id,contract_number,status,start_date,end_date',
            'activatedBy:id,tenant_id,name',
            'terminatedBy:id,tenant_id,name',
        ]);
    }

    private function listPayload(
        EmployeeContract $contract
    ): array {
        return [
            'id' => $contract->id,
            'uuid' => $contract->uuid,
            'contract_number' => $contract->contract_number,
            'employee' => $contract->employee
                ? [
                    'id' => $contract->employee->id,
                    'employee_number' =>
                        $contract->employee->employee_number,
                    'name' => $contract->employee->full_name,
                    'department' =>
                        $contract->employee->department?->name,
                    'job_title' =>
                        $contract->employee->jobTitle?->name,
                ]
                : null,
            'contract_type' => $contract->contract_type,
            'contract_type_label' =>
                $contract->contract_type_label,
            'status' => $contract->status,
            'status_label' => $contract->status_label,
            'start_date' => $contract->start_date?->toDateString(),
            'end_date' => $contract->end_date?->toDateString(),
            'gross_salary' => $contract->gross_salary,
            'currency_code' => $contract->currency_code,
            'pay_frequency' => $contract->pay_frequency,
            'pay_frequency_label' =>
                $contract->pay_frequency_label,
            'auto_renew' => $contract->auto_renew,
            'is_archived' => $contract->trashed(),
            'deleted_at' => $contract->deleted_at?->toISOString(),
        ];
    }

    private function detailsPayload(
        EmployeeContract $contract
    ): array {
        return $this->loadContract($contract)->toArray();
    }

    private function logicError(
        LogicException $exception
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $exception->getMessage(),
        ], 422);
    }

    private function authorizePermission(
        Request $request,
        string $permission
    ): void {
        abort_unless(
            $request->user()?->can($permission),
            403,
            'ليس لديك صلاحية لتنفيذ هذا الإجراء.'
        );
    }

    private function authorizeAnyPermission(
        Request $request,
        array $permissions
    ): void {
        $allowed = collect($permissions)->contains(
            fn (string $permission) =>
                $request->user()?->can($permission)
        );

        abort_unless(
            $allowed,
            403,
            'ليس لديك صلاحية لتنفيذ هذا الإجراء.'
        );
    }
}
