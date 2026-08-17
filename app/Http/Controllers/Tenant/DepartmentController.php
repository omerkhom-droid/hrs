<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreDepartmentRequest;
use App\Http\Requests\Tenant\UpdateDepartmentRequest;
use App\Models\Branch;
use App\Models\Department;
use App\Services\Organization\DepartmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function __construct(
        private readonly DepartmentService $departmentService
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Departments Page
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request
    ): View {
        $this->ensurePermission(
            $request,
            'departments.view',
            'غير مصرح لك بعرض الإدارات.'
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


        return view(
            'tenant.organization.departments.index',
            [
                'branches' =>
                    $branches,
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Departments Data
    |--------------------------------------------------------------------------
    */

    public function data(
        Request $request
    ): JsonResponse {
        $this->ensurePermission(
            $request,
            'departments.view',
            'غير مصرح لك بعرض الإدارات.'
        );


        $search = trim(
            (string) $request->get(
                'search',
                ''
            )
        );


        $status =
            $request->get('status');


        $branchId =
            $request->filled('branch_id')
                ? (int) $request->get('branch_id')
                : null;


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


        $query = Department::query()
            ->select([
                'id',
                'tenant_id',
                'branch_id',
                'parent_id',
                'code',
                'name',
                'name_en',
                'description',
                'sort_order',
                'is_active',
                'created_at',
            ])
            ->with([
                'branch:id,code,name',
                'parent:id,code,name',
            ])
            ->withCount([
                'children',
                'jobTitles',
            ])
            ->orderBy('sort_order')
            ->orderBy('name');


        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($search !== '') {
            $query->where(
                function ($query) use (
                    $search
                ) {
                    $query
                        ->where(
                            'name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'name_en',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'code',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'description',
                            'like',
                            "%{$search}%"
                        );
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Branch Filter
        |--------------------------------------------------------------------------
        */

        if ($branchId) {
            $query->where(
                'branch_id',
                $branchId
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Status Filter
        |--------------------------------------------------------------------------
        */

        if ($status === 'active') {
            $query->where(
                'is_active',
                true
            );
        }


        if ($status === 'inactive') {
            $query->where(
                'is_active',
                false
            );
        }


        return response()->json(
            $query->paginate(
                $perPage
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Parent Department Options
    |--------------------------------------------------------------------------
    */

    public function options(
        Request $request
    ): JsonResponse {
        $this->ensurePermission(
            $request,
            'departments.view',
            'غير مصرح لك بعرض الإدارات.'
        );


        $query = Department::query()
            ->active()
            ->select([
                'id',
                'branch_id',
                'parent_id',
                'code',
                'name',
            ])
            ->orderBy('sort_order')
            ->orderBy('name');


        /*
         * عند إرسال branch_id فارغًا،
         * نعرض الإدارات العامة فقط.
         */
        if ($request->has('branch_id')) {
            if ($request->filled('branch_id')) {
                $query->where(
                    'branch_id',
                    (int) $request->get(
                        'branch_id'
                    )
                );
            } else {
                $query->whereNull(
                    'branch_id'
                );
            }
        }


        if ($request->filled('exclude_id')) {
            $query->where(
                'id',
                '!=',
                (int) $request->get(
                    'exclude_id'
                )
            );
        }


        return response()->json([
            'success' =>
                true,

            'departments' =>
                $query->get(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Organization Tree
    |--------------------------------------------------------------------------
    */

    public function tree(
        Request $request
    ): JsonResponse {
        $this->ensurePermission(
            $request,
            'departments.view',
            'غير مصرح لك بعرض الهيكل التنظيمي.'
        );


        $query = Department::query()
            ->roots()
            ->with([
                'childrenRecursive',
                'branch:id,code,name',
            ])
            ->orderBy('sort_order')
            ->orderBy('name');


        if ($request->filled('branch_id')) {
            $query->where(
                'branch_id',
                (int) $request->get(
                    'branch_id'
                )
            );
        }


        return response()->json([
            'success' =>
                true,

            'departments' =>
                $query->get(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Create Department
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreDepartmentRequest $request
    ): JsonResponse {
        $department =
            $this->departmentService->create(
                $request->validated(),
                (int) $request
                    ->user()
                    ->tenant_id
            );


        return response()->json([
            'success' =>
                true,

            'message' =>
                'تم إنشاء الإدارة بنجاح.',

            'department' =>
                $department->load([
                    'branch:id,code,name',
                    'parent:id,code,name',
                ]),
        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | Show Department
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        Department $department
    ): JsonResponse {
        $this->ensurePermission(
            $request,
            'departments.view',
            'غير مصرح لك بعرض الإدارة.'
        );


        return response()->json([
            'success' =>
                true,

            'department' =>
                $department->load([
                    'branch:id,code,name',
                    'parent:id,code,name',
                ]),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Update Department
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateDepartmentRequest $request,
        Department $department
    ): JsonResponse {
        $department =
            $this->departmentService->update(
                $department,
                $request->validated()
            );


        return response()->json([
            'success' =>
                true,

            'message' =>
                'تم تحديث بيانات الإدارة بنجاح.',

            'department' =>
                $department->load([
                    'branch:id,code,name',
                    'parent:id,code,name',
                ]),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Archive Department
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        Department $department
    ): JsonResponse {
        $this->ensurePermission(
            $request,
            'departments.delete',
            'غير مصرح لك بأرشفة الإدارة.'
        );


        $this->departmentService->delete(
            $department
        );


        return response()->json([
            'success' =>
                true,

            'message' =>
                'تمت أرشفة الإدارة بنجاح.',
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
            $request->user()->can(
                $permission
            ),
            403,
            $message
        );
    }
}