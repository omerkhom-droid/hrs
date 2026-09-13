<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreHolidayRequest;
use App\Http\Requests\Tenant\UpdateHolidayRequest;
use App\Models\Branch;
use App\Models\Holiday;
use App\Services\HR\HolidayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HolidayController extends Controller
{
    public function __construct(
        private readonly HolidayService $holidayService
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
        $this->ensureViewPermission(
            $request
        );

        return view(
            'tenant.holidays.index',
            [
                'currentYear' =>
                    now()->year,
            ]
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
        $this->ensureViewPermission(
            $request
        );


        $tenantId = (int) $request
            ->user()
            ->tenant_id;


        $search = trim(
            (string) $request->input(
                'search',
                ''
            )
        );


        $type = trim(
            (string) $request->input(
                'type',
                ''
            )
        );


        $status = trim(
            (string) $request->input(
                'status',
                ''
            )
        );


        $branchFilter = $request->input(
            'branch_id',
            ''
        );


        $year = (int) $request->input(
            'year',
            now()->year
        );


        $perPage = min(
            max(
                $request->integer(
                    'per_page',
                    15
                ),
                10
            ),
            100
        );


        $yearStart =
            sprintf(
                '%04d-01-01',
                $year
            );


        $yearEnd =
            sprintf(
                '%04d-12-31',
                $year
            );


        $query = Holiday::query()
            ->where(
                'tenant_id',
                $tenantId
            )
            ->select([
                'id',
                'uuid',
                'tenant_id',
                'branch_id',
                'code',
                'name',
                'name_en',
                'type',
                'start_date',
                'end_date',
                'is_paid',
                'exclude_from_leave_days',
                'affects_attendance',
                'is_recurring',
                'is_active',
                'created_at',
            ])
            ->with([
                'branch:id,code,name',
            ])
            ->where(
                function ($query) use (
                    $yearStart,
                    $yearEnd
                ) {
                    $query
                        ->where(
                            'is_recurring',
                            true
                        )
                        ->orWhere(
                            function ($query) use (
                                $yearStart,
                                $yearEnd
                            ) {
                                $query
                                    ->whereDate(
                                        'start_date',
                                        '<=',
                                        $yearEnd
                                    )
                                    ->whereDate(
                                        'end_date',
                                        '>=',
                                        $yearStart
                                    );
                            }
                        );
                }
            );


        if ($search !== '') {
            $query->where(
                function ($query) use ($search) {
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
            in_array(
                $type,
                [
                    'public',
                    'company',
                    'national',
                    'religious',
                    'other',
                ],
                true
            )
        ) {
            $query->where(
                'type',
                $type
            );
        }


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


        if ($branchFilter === 'global') {
            $query->whereNull(
                'branch_id'
            );
        } elseif (
            is_numeric(
                $branchFilter
            ) &&
            (int) $branchFilter > 0
        ) {
            $query->where(
                'branch_id',
                (int) $branchFilter
            );
        }


        $holidays = $query
            ->orderBy(
                'start_date'
            )
            ->orderBy(
                'name'
            )
            ->paginate(
                $perPage
            );


        $holidays->through(
            function (Holiday $holiday) {
                return [
                    'id' =>
                        $holiday->id,

                    'uuid' =>
                        $holiday->uuid,

                    'code' =>
                        $holiday->code,

                    'name' =>
                        $holiday->name,

                    'name_en' =>
                        $holiday->name_en,

                    'display_name' =>
                        $holiday->display_name,

                    'type' =>
                        $holiday->type,

                    'type_label' =>
                        $holiday->type_label,

                    'start_date' =>
                        $holiday
                            ->start_date
                            ?->toDateString(),

                    'end_date' =>
                        $holiday
                            ->end_date
                            ?->toDateString(),

                    'duration_days' =>
                        $holiday->duration_days,

                    'is_paid' =>
                        $holiday->is_paid,

                    'exclude_from_leave_days' =>
                        $holiday
                            ->exclude_from_leave_days,

                    'affects_attendance' =>
                        $holiday
                            ->affects_attendance,

                    'is_recurring' =>
                        $holiday->is_recurring,

                    'is_active' =>
                        $holiday->is_active,

                    'branch' =>
                        $holiday
                            ->branch
                            ?->only([
                                'id',
                                'code',
                                'name',
                            ]),

                    'created_at' =>
                        $holiday
                            ->created_at
                            ?->toISOString(),
                ];
            }
        );


        return response()->json(
            $holidays
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
        $this->ensureViewPermission(
            $request
        );


        $tenantId = (int) $request
            ->user()
            ->tenant_id;


        $branches = Branch::query()
            ->where(
                'tenant_id',
                $tenantId
            )
            ->where(
                'is_active',
                true
            )
            ->select([
                'id',
                'code',
                'name',
            ])
            ->orderByDesc(
                'is_main'
            )
            ->orderBy(
                'name'
            )
            ->get();


        return response()->json([
            'success' =>
                true,

            'branches' =>
                $branches,

            'types' =>
                $this->holidayTypes(),

            'years' =>
                collect(
                    range(
                        now()->year - 2,
                        now()->year + 3
                    )
                )
                    ->reverse()
                    ->values(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreHolidayRequest $request
    ): JsonResponse {
        $this->ensureManagePermission(
            $request
        );


        $tenant = $request
            ->user()
            ->tenant;


        abort_unless(
            $tenant !== null,
            403,
            'تعذر تحديد الشركة الحالية.'
        );


        $holiday = $this
            ->holidayService
            ->create(
                tenant:
                    $tenant,

                data:
                    $request->validated()
            );


        $holiday->load([
            'branch:id,code,name',
        ]);


        return response()->json([
            'success' =>
                true,

            'message' =>
                'تم إنشاء العطلة بنجاح.',

            'holiday' =>
                $holiday,
        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        Holiday $holiday
    ): JsonResponse {
        $this->ensureViewPermission(
            $request
        );


        $this->ensureHolidayTenant(
            $request,
            $holiday
        );


        $holiday->load([
            'branch:id,code,name',
        ]);


        return response()->json([
            'success' =>
                true,

            'holiday' => [
                'id' =>
                    $holiday->id,

                'uuid' =>
                    $holiday->uuid,

                'branch_id' =>
                    $holiday->branch_id,

                'code' =>
                    $holiday->code,

                'name' =>
                    $holiday->name,

                'name_en' =>
                    $holiday->name_en,

                'type' =>
                    $holiday->type,

                'start_date' =>
                    $holiday
                        ->start_date
                        ?->toDateString(),

                'end_date' =>
                    $holiday
                        ->end_date
                        ?->toDateString(),

                'is_paid' =>
                    $holiday->is_paid,

                'exclude_from_leave_days' =>
                    $holiday
                        ->exclude_from_leave_days,

                'affects_attendance' =>
                    $holiday
                        ->affects_attendance,

                'is_recurring' =>
                    $holiday->is_recurring,

                'is_active' =>
                    $holiday->is_active,

                'branch' =>
                    $holiday->branch,
            ],
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateHolidayRequest $request,
        Holiday $holiday
    ): JsonResponse {
        $this->ensureManagePermission(
            $request
        );


        $this->ensureHolidayTenant(
            $request,
            $holiday
        );


        $tenant = $request
            ->user()
            ->tenant;


        abort_unless(
            $tenant !== null,
            403,
            'تعذر تحديد الشركة الحالية.'
        );


        $holiday = $this
            ->holidayService
            ->update(
                tenant:
                    $tenant,

                holiday:
                    $holiday,

                data:
                    $request->validated()
            );


        $holiday->load([
            'branch:id,code,name',
        ]);


        return response()->json([
            'success' =>
                true,

            'message' =>
                'تم تحديث العطلة بنجاح.',

            'holiday' =>
                $holiday,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        Holiday $holiday
    ): JsonResponse {
        $this->ensureManagePermission(
            $request
        );


        $this->ensureHolidayTenant(
            $request,
            $holiday
        );


        $tenant = $request
            ->user()
            ->tenant;


        abort_unless(
            $tenant !== null,
            403,
            'تعذر تحديد الشركة الحالية.'
        );


        $this->holidayService->delete(
            tenant:
                $tenant,

            holiday:
                $holiday
        );


        return response()->json([
            'success' =>
                true,

            'message' =>
                'تم حذف العطلة بنجاح.',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    */

    private function ensureViewPermission(
        Request $request
    ): void {
        $user =
            $request->user();


        abort_unless(
            $user !== null &&
            (
                $user->can(
                    'attendance.view'
                ) ||
                $user->can(
                    'attendance.manage'
                ) ||
                $user->can(
                    'leave.view'
                ) ||
                $user->can(
                    'leave.manage'
                )
            ),
            403,
            'غير مصرح لك بعرض العطلات الرسمية.'
        );
    }


    private function ensureManagePermission(
        Request $request
    ): void {
        $user =
            $request->user();


        abort_unless(
            $user !== null &&
            (
                $user->can(
                    'attendance.manage'
                ) ||
                $user->can(
                    'leave.manage'
                )
            ),
            403,
            'غير مصرح لك بإدارة العطلات الرسمية.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Tenant Protection
    |--------------------------------------------------------------------------
    */

    private function ensureHolidayTenant(
        Request $request,
        Holiday $holiday
    ): void {
        abort_unless(
            (int) $holiday->tenant_id ===
            (int) $request
                ->user()
                ->tenant_id,
            404
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function holidayTypes(): array
    {
        return [
            [
                'value' =>
                    'public',

                'label' =>
                    'عطلة رسمية',
            ],

            [
                'value' =>
                    'company',

                'label' =>
                    'عطلة داخلية للشركة',
            ],

            [
                'value' =>
                    'national',

                'label' =>
                    'عطلة وطنية',
            ],

            [
                'value' =>
                    'religious',

                'label' =>
                    'عطلة دينية',
            ],

            [
                'value' =>
                    'other',

                'label' =>
                    'أخرى',
            ],
        ];
    }
}