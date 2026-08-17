<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreJobTitleRequest;
use App\Http\Requests\Tenant\UpdateJobTitleRequest;
use App\Models\Branch;
use App\Models\Department;
use App\Models\JobTitle;
use App\Services\Organization\JobTitleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JobTitleController extends Controller
{
    public function __construct(
        private readonly JobTitleService $jobTitleService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Job Titles Page
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request
    ): View {
        $this->ensurePermission(
            $request,
            'job_titles.view',
            'غير مصرح لك بعرض المسميات الوظيفية.'
        );

        $branches = Branch::query()
            ->active()
            ->orderByDesc('is_main')
            ->orderBy('name')
            ->get([
                'id',
                'code',
                'name',
                'is_main',
            ]);

        $departments = Department::query()
            ->active()
            ->with([
                'branch:id,code,name',
                'parent:id,code,name',
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get([
                'id',
                'branch_id',
                'parent_id',
                'code',
                'name',
                'name_en',
                'is_active',
            ]);

        return view(
            'tenant.organization.job-titles.index',
            [
                'branches' => $branches,
                'departments' => $departments,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Job Titles Data
    |--------------------------------------------------------------------------
    */

    public function data(
        Request $request
    ): JsonResponse {
        $this->ensurePermission(
            $request,
            'job_titles.view',
            'غير مصرح لك بعرض المسميات الوظيفية.'
        );

        $search = trim(
            (string) $request->get('search', '')
        );

        $status = $request->get('status');

        $branchId = $request->filled('branch_id')
            ? (int) $request->get('branch_id')
            : null;

        $departmentId = $request->filled('department_id')
            ? (int) $request->get('department_id')
            : null;

        $perPage = min(
            max(
                (int) $request->get('per_page', 15),
                10
            ),
            100
        );

        $query = JobTitle::query()
            ->select([
                'id',
                'tenant_id',
                'department_id',
                'code',
                'name',
                'name_en',
                'description',
                'sort_order',
                'is_active',
                'created_at',
            ])
            ->with([
                'department:id,branch_id,parent_id,code,name',
                'department.branch:id,code,name',
                'department.parent:id,code,name',
            ])
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($search !== '') {
            $query->where(
                function ($query) use ($search) {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas(
                            'department',
                            function ($departmentQuery) use ($search) {
                                $departmentQuery
                                    ->where('name', 'like', "%{$search}%")
                                    ->orWhere('code', 'like', "%{$search}%");
                            }
                        );
                }
            );
        }

        if ($branchId) {
            $query->whereHas(
                'department',
                fn ($departmentQuery) => $departmentQuery
                    ->where('branch_id', $branchId)
            );
        }

        if ($departmentId) {
            $query->where(
                'department_id',
                $departmentId
            );
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        }

        if ($status === 'inactive') {
            $query->where('is_active', false);
        }

        return response()->json(
            $query->paginate($perPage)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Job Title Options
    |--------------------------------------------------------------------------
    |
    | يستخدم في نماذج الموظفين وواجهات API وتطبيق Flutter لاحقًا.
    |
    */

    public function options(
        Request $request
    ): JsonResponse {
        $this->ensurePermission(
            $request,
            'job_titles.view',
            'غير مصرح لك بعرض المسميات الوظيفية.'
        );

        $search = trim(
            (string) $request->get('search', '')
        );

        $limit = min(
            max(
                (int) $request->get('limit', 50),
                10
            ),
            100
        );

        $query = JobTitle::query()
            ->active()
            ->select([
                'id',
                'department_id',
                'code',
                'name',
                'name_en',
            ])
            ->with([
                'department:id,branch_id,code,name',
                'department.branch:id,code,name',
            ])
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($request->filled('department_id')) {
            $query->where(
                'department_id',
                (int) $request->get('department_id')
            );
        }

        if ($request->filled('branch_id')) {
            $branchId = (int) $request->get('branch_id');

            $query->whereHas(
                'department',
                fn ($departmentQuery) => $departmentQuery
                    ->where('branch_id', $branchId)
            );
        }

        if ($search !== '') {
            $query->where(
                function ($query) use ($search) {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                }
            );
        }

        return response()->json([
            'success' => true,
            'job_titles' => $query
                ->limit($limit)
                ->get(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Job Title
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreJobTitleRequest $request
    ): JsonResponse {
        $jobTitle = $this->jobTitleService->create(
            $request->validated(),
            (int) $request->user()->tenant_id
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء المسمى الوظيفي بنجاح.',
            'job_title' => $jobTitle->load([
                'department:id,branch_id,parent_id,code,name',
                'department.branch:id,code,name',
                'department.parent:id,code,name',
            ]),
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Show Job Title
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        JobTitle $jobTitle
    ): JsonResponse {
        $this->ensurePermission(
            $request,
            'job_titles.view',
            'غير مصرح لك بعرض المسمى الوظيفي.'
        );

        return response()->json([
            'success' => true,
            'job_title' => $jobTitle->load([
                'department:id,branch_id,parent_id,code,name',
                'department.branch:id,code,name',
                'department.parent:id,code,name',
            ]),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Job Title
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateJobTitleRequest $request,
        JobTitle $jobTitle
    ): JsonResponse {
        $jobTitle = $this->jobTitleService->update(
            $jobTitle,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث المسمى الوظيفي بنجاح.',
            'job_title' => $jobTitle->load([
                'department:id,branch_id,parent_id,code,name',
                'department.branch:id,code,name',
                'department.parent:id,code,name',
            ]),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Archive Job Title
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        JobTitle $jobTitle
    ): JsonResponse {
        $this->ensurePermission(
            $request,
            'job_titles.delete',
            'غير مصرح لك بأرشفة المسمى الوظيفي.'
        );

        $this->jobTitleService->delete($jobTitle);

        return response()->json([
            'success' => true,
            'message' => 'تمت أرشفة المسمى الوظيفي بنجاح.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Permission Helper
    |--------------------------------------------------------------------------
    */

    private function ensurePermission(
        Request $request,
        string $permission,
        string $message
    ): void {
        abort_unless(
            $request->user()->can($permission),
            403,
            $message
        );
    }
}
