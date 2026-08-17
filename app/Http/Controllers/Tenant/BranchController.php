<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreBranchRequest;
use App\Http\Requests\Tenant\UpdateBranchRequest;
use App\Models\Branch;
use App\Services\Organization\BranchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function __construct(
        private readonly BranchService $branchService
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Branches Page
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request
    ): View {
        abort_unless(
            $request->user()->can(
                'branches.view'
            ),
            403,
            'غير مصرح لك بعرض الفروع.'
        );

        return view(
            'tenant.organization.branches.index'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Branches Data
    |--------------------------------------------------------------------------
    */

    public function data(
        Request $request
    ): JsonResponse {
        abort_unless(
            $request->user()->can(
                'branches.view'
            ),
            403,
            'غير مصرح لك بعرض الفروع.'
        );


        $search = trim(
            (string) $request->get(
                'search',
                ''
            )
        );


        $status =
            $request->get('status');


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


        $query = Branch::query()
            ->select([
                'id',
                'tenant_id',
                'code',
                'name',
                'name_en',
                'email',
                'phone',
                'country_code',
                'city',
                'address',
                'timezone',
                'is_main',
                'is_active',
                'created_at',
            ])
            ->withCount([
                'departments',
                'workLocations',
            ])
            ->orderByDesc(
                'is_main'
            )
            ->orderByDesc(
                'id'
            );


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
                            'city',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'email',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'phone',
                            'like',
                            "%{$search}%"
                        );
                }
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
    | Create Branch
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreBranchRequest $request
    ): JsonResponse {
        $branch =
            $this->branchService->create(
                $request->validated(),
                (int) $request
                    ->user()
                    ->tenant_id
            );


        return response()->json([
            'success' =>
                true,

            'message' =>
                'تم إنشاء الفرع بنجاح.',

            'branch' =>
                $branch,
        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | Show Branch
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        Branch $branch
    ): JsonResponse {
        abort_unless(
            $request->user()->can(
                'branches.view'
            ),
            403,
            'غير مصرح لك بعرض الفرع.'
        );


        return response()->json([
            'success' =>
                true,

            'branch' =>
                $branch,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Update Branch
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateBranchRequest $request,
        Branch $branch
    ): JsonResponse {
        $branch =
            $this->branchService->update(
                $branch,
                $request->validated()
            );


        return response()->json([
            'success' =>
                true,

            'message' =>
                'تم تحديث بيانات الفرع بنجاح.',

            'branch' =>
                $branch,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Archive Branch
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        Branch $branch
    ): JsonResponse {
        abort_unless(
            $request->user()->can(
                'branches.delete'
            ),
            403,
            'غير مصرح لك بأرشفة الفرع.'
        );


        $this->branchService->delete(
            $branch
        );


        return response()->json([
            'success' =>
                true,

            'message' =>
                'تمت أرشفة الفرع بنجاح.',
        ]);
    }
}