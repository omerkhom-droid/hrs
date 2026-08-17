<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreEmployeeRequest;
use App\Http\Requests\Tenant\UpdateEmployeeRequest;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkLocation;
use App\Services\HR\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use LogicException;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeService $employeeService
    ) {
    }

    public function index(
        Request $request
    ): View {
        $this->authorizePermission(
            $request,
            'employees.view'
        );

        return view(
            'tenant.employees.index'
        );
    }

    public function data(
        Request $request
    ): JsonResponse {
        $this->authorizePermission(
            $request,
            'employees.view'
        );

        $perPage = min(
            max(
                (int) $request->integer('per_page', 15),
                10
            ),
            100
        );

        $allowedStatuses = [
            'draft',
            'probation',
            'active',
            'on_leave',
            'suspended',
            'terminated',
        ];

        $allowedTypes = [
            'full_time',
            'part_time',
            'contract',
            'temporary',
            'intern',
            'consultant',
        ];

        $allowedSorts = [
            'employee_number',
            'first_name',
            'family_name',
            'hire_date',
            'employment_status',
            'created_at',
        ];

        $status = (string) $request->input(
            'employment_status',
            ''
        );

        $type = (string) $request->input(
            'employment_type',
            ''
        );

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

        $query = Employee::query()
            ->with([
                'user:id,tenant_id,name,email,is_active',
                'branch:id,tenant_id,code,name',
                'department:id,tenant_id,branch_id,code,name',
                'jobTitle:id,tenant_id,department_id,code,name',
                'workLocation:id,tenant_id,branch_id,code,name',
                'manager:id,tenant_id,employee_number,first_name,father_name,family_name',
            ])
            ->search(
                $request->input('search')
            )
            ->forBranch(
                $request->integer('branch_id') ?: null
            )
            ->forDepartment(
                $request->integer('department_id') ?: null
            );

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
            $query->withEmploymentStatus($status);
        }

        if (in_array($type, $allowedTypes, true)) {
            $query->where(
                'employment_type',
                $type
            );
        }

        if ($request->filled('job_title_id')) {
            $query->where(
                'job_title_id',
                $request->integer('job_title_id')
            );
        }

        if ($request->filled('work_location_id')) {
            $query->where(
                'work_location_id',
                $request->integer('work_location_id')
            );
        }

        $employees = $query
            ->orderBy($sortBy, $sortDirection)
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $employees->through(
            fn (Employee $employee) =>
                $this->employeeListPayload($employee)
        );

        return response()->json($employees);
    }

    public function options(
        Request $request
    ): JsonResponse {
        $this->authorizeAnyPermission(
            $request,
            [
                'employees.view',
                'employees.create',
                'employees.update',
            ]
        );

        $tenantId = (int) $request->user()->tenant_id;
        $branchId = $request->integer('branch_id') ?: null;
        $departmentId = $request->integer('department_id') ?: null;
        $employeeId = $request->integer('employee_id') ?: null;

        $branches = Branch::query()
            ->active()
            ->orderBy('name')
            ->get([
                'id',
                'code',
                'name',
                'is_main',
            ]);

        $departments = Department::query()
            ->active()
            ->forBranch($branchId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get([
                'id',
                'branch_id',
                'parent_id',
                'code',
                'name',
            ]);

        $jobTitles = JobTitle::query()
            ->active()
            ->forDepartment($departmentId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get([
                'id',
                'department_id',
                'code',
                'name',
            ]);

        $workLocations = WorkLocation::query()
            ->active()
            ->forBranch($branchId)
            ->orderBy('name')
            ->get([
                'id',
                'branch_id',
                'code',
                'name',
                'type',
            ]);

        $managers = Employee::query()
            ->whereNotIn('employment_status', [
                'terminated',
            ])
            ->when(
                $employeeId,
                fn ($query) => $query->where(
                    'id',
                    '!=',
                    $employeeId
                )
            )
            ->orderBy('first_name')
            ->orderBy('family_name')
            ->get([
                'id',
                'employee_number',
                'first_name',
                'father_name',
                'grandfather_name',
                'family_name',
            ])
            ->map(fn (Employee $employee) => [
                'id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'name' => $employee->full_name,
            ]);

        $linkedUserIds = Employee::query()
            ->withTrashed()
            ->whereNotNull('user_id')
            ->when(
                $employeeId,
                fn ($query) => $query->where(
                    'id',
                    '!=',
                    $employeeId
                )
            )
            ->pluck('user_id');

        $users = User::query()
            ->where('tenant_id', $tenantId)
            ->where('is_system_admin', false)
            ->where('is_active', true)
            ->whereNotIn('id', $linkedUserIds)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'email',
            ]);

        return response()->json([
            'success' => true,
            'options' => [
                'branches' => $branches,
                'departments' => $departments,
                'job_titles' => $jobTitles,
                'work_locations' => $workLocations,
                'managers' => $managers,
                'users' => $users,
            ],
        ]);
    }

    public function store(
        StoreEmployeeRequest $request
    ): JsonResponse {
        $tenant = $this->currentTenant(
            $request
        );

        $employee = $this->employeeService->create(
            $tenant,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الموظف بنجاح.',
            'employee' => $this->employeeDetailsPayload(
                $employee
            ),
        ], 201);
    }

    public function show(
        Request $request,
        Employee $employee
    ): JsonResponse {
        $this->authorizePermission(
            $request,
            'employees.view'
        );

        $employee->load([
            'user:id,tenant_id,name,email,is_active',
            'branch:id,tenant_id,code,name',
            'department:id,tenant_id,branch_id,code,name',
            'jobTitle:id,tenant_id,department_id,code,name',
            'workLocation:id,tenant_id,branch_id,code,name',
            'manager:id,tenant_id,employee_number,first_name,father_name,grandfather_name,family_name',
        ]);

        return response()->json([
            'success' => true,
            'employee' => $this->employeeDetailsPayload(
                $employee
            ),
        ]);
    }

    public function photo(
        Request $request,
        Employee $employee
    ): BinaryFileResponse {
        $this->authorizePermission(
            $request,
            'employees.view'
        );

        $disk = Storage::disk('public');

        abort_unless(
            $employee->photo_path &&
            $disk->exists($employee->photo_path),
            404,
            'صورة الموظف غير موجودة.'
        );

        $extension = pathinfo(
            $employee->photo_path,
            PATHINFO_EXTENSION
        );

        $fileName =
            'employee-' .
            $employee->uuid .
            ($extension ? '.' . $extension : '');

        return response()->file(
            $disk->path($employee->photo_path),
            [
                'Content-Type' =>
                    $disk->mimeType($employee->photo_path)
                    ?: 'application/octet-stream',

                'Content-Disposition' =>
                    'inline; filename="' . $fileName . '"',

                'Cache-Control' =>
                    'private, max-age=3600',

                'X-Content-Type-Options' =>
                    'nosniff',
            ]
        );
    }
    public function update(
        UpdateEmployeeRequest $request,
        Employee $employee
    ): JsonResponse {
        $employee = $this->employeeService->update(
            $employee,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث بيانات الموظف بنجاح.',
            'employee' => $this->employeeDetailsPayload(
                $employee
            ),
        ]);
    }

    public function destroy(
        Request $request,
        Employee $employee
    ): JsonResponse {
        $this->authorizePermission(
            $request,
            'employees.archive'
        );

        try {
            $this->employeeService->archive(
                $employee
            );
        } catch (LogicException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'تمت أرشفة الموظف بنجاح.',
        ]);
    }

    public function restore(
        Request $request,
        int $employee
    ): JsonResponse {
        $this->authorizePermission(
            $request,
            'employees.archive'
        );

        $employee = Employee::query()
            ->withTrashed()
            ->where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($employee);

        $employee = $this->employeeService->restore(
            $employee
        );

        return response()->json([
            'success' => true,
            'message' => 'تمت استعادة الموظف بنجاح.',
            'employee' => $this->employeeDetailsPayload(
                $employee
            ),
        ]);
    }

    private function currentTenant(
        Request $request
    ): Tenant {
        return Tenant::query()->findOrFail(
            $request->user()->tenant_id
        );
    }

    private function employeeListPayload(
        Employee $employee
    ): array {
        return [
            'id' => $employee->id,
            'uuid' => $employee->uuid,
            'employee_number' => $employee->employee_number,
            'attendance_code' => $employee->attendance_code,
            'name' => $employee->display_name,
            'full_name' => $employee->full_name,
            'photo_url' => $this->photoUrl($employee),
            'branch' => $employee->branch?->only([
                'id',
                'code',
                'name',
            ]),
            'department' => $employee->department?->only([
                'id',
                'code',
                'name',
            ]),
            'job_title' => $employee->jobTitle?->only([
                'id',
                'code',
                'name',
            ]),
            'work_location' => $employee->workLocation?->only([
                'id',
                'code',
                'name',
            ]),
            'manager' => $employee->manager
                ? [
                    'id' => $employee->manager->id,
                    'name' => $employee->manager->full_name,
                ]
                : null,
            'employment_type' => $employee->employment_type,
            'employment_type_label' => $employee->employment_type_label,
            'employment_status' => $employee->employment_status,
            'employment_status_label' => $employee->employment_status_label,
            'hire_date' => $employee->hire_date?->toDateString(),
            'work_email' => $employee->work_email,
            'work_phone' => $employee->work_phone,
            'has_login_account' => $employee->hasLoginAccount(),
            'user_is_active' => $employee->user?->is_active,
            'is_archived' => $employee->trashed(),
            'deleted_at' => $employee->deleted_at?->toISOString(),
        ];
    }

    private function employeeDetailsPayload(
        Employee $employee
    ): array {
        $payload = $employee->toArray();
        $payload['photo_url'] = $this->photoUrl(
            $employee
        );

        return $payload;
    }

    private function photoUrl(
        Employee $employee
    ): ?string {
        if (!$employee->photo_path) {
            return null;
        }

        return route(
            'app.employees.photo',
            [
                'employee' => $employee->id,
                'v' => $employee->updated_at?->timestamp,
            ]
        );
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
        $allowed = collect($permissions)
            ->contains(
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