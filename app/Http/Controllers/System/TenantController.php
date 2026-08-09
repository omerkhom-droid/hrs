<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\System\StoreTenantRequest;
use App\Http\Requests\System\UpdateTenantRequest;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\SaaS\TenantProvisioningService;
use App\Services\SaaS\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function __construct(
        private readonly TenantService $tenantService,
        private readonly TenantProvisioningService $tenantProvisioningService,
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | صفحة العملاء
    |--------------------------------------------------------------------------
    */

    public function index(): View
    {
        $plans = Plan::query()
            ->where(
                'is_active',
                true
            )
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'monthly_price',
                'yearly_price',
                'currency_code',
                'trial_days',
            ]);

        return view(
            'system.tenants.index',
            [
                'plans' =>
                    $plans,

                'defaultStartDate' =>
                    now(
                        config(
                            'app.timezone'
                        )
                    )->toDateString(),
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | بيانات العملاء
    |--------------------------------------------------------------------------
    */

    public function data(
        Request $request
    ): JsonResponse {
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


        $query = Tenant::query()
            ->select([
                'tenants.id',
                'tenants.uuid',
                'tenants.code',
                'tenants.name',
                'tenants.contact_name',
                'tenants.email',
                'tenants.phone',
                'tenants.country_code',
                'tenants.currency_code',
                'tenants.timezone',
                'tenants.locale',
                'tenants.status',
                'tenants.created_at',
            ])


            /*
            |--------------------------------------------------------------------------
            | عدد المستخدمين
            |--------------------------------------------------------------------------
            */

            ->selectSub(
                function ($query) {
                    $query
                        ->from('users')
                        ->selectRaw('COUNT(*)')
                        ->whereColumn(
                            'users.tenant_id',
                            'tenants.id'
                        )
                        ->whereNull(
                            'users.deleted_at'
                        );
                },
                'users_count'
            )


            /*
            |--------------------------------------------------------------------------
            | حالة الاشتراك الحالي
            |--------------------------------------------------------------------------
            */

            ->selectSub(
                function ($query) {
                    $query
                        ->from(
                            'subscriptions as current_subscription'
                        )
                        ->select(
                            'current_subscription.status'
                        )
                        ->whereColumn(
                            'current_subscription.tenant_id',
                            'tenants.id'
                        )
                        ->whereIn(
                            'current_subscription.status',
                            [
                                'trial',
                                'active',
                                'suspended',
                                'scheduled',
                            ]
                        )
                        ->orderByDesc(
                            'current_subscription.id'
                        )
                        ->limit(1);
                },
                'subscription_status'
            )


            /*
            |--------------------------------------------------------------------------
            | تاريخ نهاية الاشتراك
            |--------------------------------------------------------------------------
            */

            ->selectSub(
                function ($query) {
                    $query
                        ->from(
                            'subscriptions as current_subscription'
                        )
                        ->select(
                            'current_subscription.ends_at'
                        )
                        ->whereColumn(
                            'current_subscription.tenant_id',
                            'tenants.id'
                        )
                        ->whereIn(
                            'current_subscription.status',
                            [
                                'trial',
                                'active',
                                'suspended',
                                'scheduled',
                            ]
                        )
                        ->orderByDesc(
                            'current_subscription.id'
                        )
                        ->limit(1);
                },
                'ends_at'
            )


            /*
            |--------------------------------------------------------------------------
            | اسم الباقة
            |--------------------------------------------------------------------------
            */

            ->selectSub(
                function ($query) {
                    $query
                        ->from(
                            'subscriptions as current_subscription'
                        )
                        ->join(
                            'plans',
                            'plans.id',
                            '=',
                            'current_subscription.plan_id'
                        )
                        ->select(
                            'plans.name'
                        )
                        ->whereColumn(
                            'current_subscription.tenant_id',
                            'tenants.id'
                        )
                        ->whereIn(
                            'current_subscription.status',
                            [
                                'trial',
                                'active',
                                'suspended',
                                'scheduled',
                            ]
                        )
                        ->orderByDesc(
                            'current_subscription.id'
                        )
                        ->limit(1);
                },
                'plan_name'
            )

            ->latest(
                'tenants.id'
            );


        /*
        |--------------------------------------------------------------------------
        | البحث
        |--------------------------------------------------------------------------
        */

        if ($search !== '') {
            $query->where(
                function ($query) use (
                    $search
                ) {
                    $query
                        ->where(
                            'tenants.name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'tenants.code',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'tenants.contact_name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'tenants.email',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'tenants.phone',
                            'like',
                            "%{$search}%"
                        );
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | فلتر الحالة
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $status,
                [
                    'active',
                    'suspended',
                    'inactive',
                ],
                true
            )
        ) {
            $query->where(
                'tenants.status',
                $status
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
    | إنشاء العميل والمدير والاشتراك
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreTenantRequest $request
    ): JsonResponse {
        $result =
            $this->tenantProvisioningService
                ->create(
                    $request->validated(),
                    $request->user()?->id
                );


        return response()->json([
            'success' =>
                true,

            'message' =>
                'تم إنشاء العميل ومدير الحساب والاشتراك بنجاح.',

            'tenant' =>
                $result['tenant'],

            'owner' => [
                'id' =>
                    $result['owner']->id,

                'name' =>
                    $result['owner']->name,

                'email' =>
                    $result['owner']->email,
            ],

            'subscription' => [
                'id' =>
                    $result['subscription']->id,

                'status' =>
                    $result['subscription']->status,

                'starts_at' =>
                    $result['subscription']->starts_at,

                'ends_at' =>
                    $result['subscription']->ends_at,
            ],

            'login_url' =>
                route('app.login'),
        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | عرض العميل
    |--------------------------------------------------------------------------
    */

    public function show(
        Tenant $tenant
    ): JsonResponse {
        return response()->json([
            'success' =>
                true,

            'tenant' =>
                $tenant,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | تحديث العميل
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateTenantRequest $request,
        Tenant $tenant
    ): JsonResponse {
        $tenant =
            $this->tenantService->update(
                $tenant,
                $request->validated()
            );


        return response()->json([
            'success' =>
                true,

            'message' =>
                'تم تحديث بيانات العميل بنجاح.',

            'tenant' =>
                $tenant,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | أرشفة العميل
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Tenant $tenant
    ): JsonResponse {
        $this->tenantService->delete(
            $tenant
        );


        return response()->json([
            'success' =>
                true,

            'message' =>
                'تمت أرشفة العميل بنجاح.',
        ]);
    }
}