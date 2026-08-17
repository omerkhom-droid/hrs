<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreWorkLocationRequest;
use App\Http\Requests\Tenant\UpdateWorkLocationRequest;
use App\Models\Branch;
use App\Models\WorkLocation;
use App\Services\Organization\WorkLocationService;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkLocationController extends Controller
{
    private const TYPES = [
        'office',
        'site',
        'warehouse',
        'remote',
        'other',
    ];

    public function __construct(
        private readonly WorkLocationService $workLocationService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Work Locations Page
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request
    ): View {
        $this->ensurePermission(
            $request,
            'work_locations.view',
            'غير مصرح لك بعرض مواقع العمل.'
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
            'tenant.organization.work-locations.index',
            [
                'branches' => $branches,
                'timezones' => DateTimeZone::listIdentifiers(),
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Work Locations Data
    |--------------------------------------------------------------------------
    */

    public function data(
        Request $request
    ): JsonResponse {
        $this->ensurePermission(
            $request,
            'work_locations.view',
            'غير مصرح لك بعرض مواقع العمل.'
        );

        $search = trim(
            (string) $request->get('search', '')
        );

        $branchId = $request->filled('branch_id')
            ? (int) $request->get('branch_id')
            : null;

        $type = (string) $request->get('type', '');
        $status = $request->get('status');

        $perPage = min(
            max(
                (int) $request->get('per_page', 15),
                10
            ),
            100
        );

        $query = WorkLocation::query()
            ->select([
                'id',
                'tenant_id',
                'branch_id',
                'code',
                'name',
                'name_en',
                'type',
                'country_code',
                'city',
                'address',
                'latitude',
                'longitude',
                'attendance_radius',
                'timezone',
                'is_active',
                'created_at',
            ])
            ->with([
                'branch:id,code,name',
            ])
            ->orderBy('name');

        if ($search !== '') {
            $query->where(
                function ($query) use ($search) {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhereHas(
                            'branch',
                            function ($branchQuery) use ($search) {
                                $branchQuery
                                    ->where('name', 'like', "%{$search}%")
                                    ->orWhere('code', 'like', "%{$search}%");
                            }
                        );
                }
            );
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if (in_array($type, self::TYPES, true)) {
            $query->where('type', $type);
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        }

        if ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $workLocations = $query->paginate($perPage);

        $workLocations
            ->getCollection()
            ->each(
                fn (WorkLocation $workLocation) =>
                    $workLocation->append('type_label')
            );

        return response()->json($workLocations);
    }

    /*
    |--------------------------------------------------------------------------
    | Work Location Options
    |--------------------------------------------------------------------------
    |
    | يستخدم في الموظفين والحضور وواجهات API وتطبيق Flutter لاحقًا.
    |
    */

    public function options(
        Request $request
    ): JsonResponse {
        $this->ensurePermission(
            $request,
            'work_locations.view',
            'غير مصرح لك بعرض مواقع العمل.'
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

        $query = WorkLocation::query()
            ->active()
            ->select([
                'id',
                'branch_id',
                'code',
                'name',
                'name_en',
                'type',
                'city',
                'latitude',
                'longitude',
                'attendance_radius',
                'timezone',
            ])
            ->with([
                'branch:id,code,name',
            ])
            ->orderBy('name');

        if ($request->filled('branch_id')) {
            $query->where(
                'branch_id',
                (int) $request->get('branch_id')
            );
        }

        $type = (string) $request->get('type', '');

        if (in_array($type, self::TYPES, true)) {
            $query->where('type', $type);
        }

        if ($search !== '') {
            $query->where(
                function ($query) use ($search) {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%");
                }
            );
        }

        $workLocations = $query
            ->limit($limit)
            ->get();

        $workLocations->each(
            fn (WorkLocation $workLocation) =>
                $workLocation->append('type_label')
        );

        return response()->json([
            'success' => true,
            'work_locations' => $workLocations,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Work Location
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreWorkLocationRequest $request
    ): JsonResponse {
        $workLocation = $this->workLocationService->create(
            $request->validated(),
            (int) $request->user()->tenant_id
        );

        $workLocation
            ->load('branch:id,code,name')
            ->append('type_label');

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء موقع العمل بنجاح.',
            'work_location' => $workLocation,
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Show Work Location
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        WorkLocation $workLocation
    ): JsonResponse {
        $this->ensurePermission(
            $request,
            'work_locations.view',
            'غير مصرح لك بعرض موقع العمل.'
        );

        $workLocation
            ->load('branch:id,code,name')
            ->append('type_label');

        return response()->json([
            'success' => true,
            'work_location' => $workLocation,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Work Location
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateWorkLocationRequest $request,
        WorkLocation $workLocation
    ): JsonResponse {
        $workLocation = $this->workLocationService->update(
            $workLocation,
            $request->validated()
        );

        $workLocation
            ->load('branch:id,code,name')
            ->append('type_label');

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث موقع العمل بنجاح.',
            'work_location' => $workLocation,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Archive Work Location
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        WorkLocation $workLocation
    ): JsonResponse {
        $this->ensurePermission(
            $request,
            'work_locations.delete',
            'غير مصرح لك بأرشفة موقع العمل.'
        );

        $this->workLocationService->delete($workLocation);

        return response()->json([
            'success' => true,
            'message' => 'تمت أرشفة موقع العمل بنجاح.',
        ]);
    }

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