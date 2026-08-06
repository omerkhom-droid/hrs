<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\System\StoreSubscriptionRequest;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\SaaS\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService
    ) {
    }

    public function index(): View
    {
        $tenants = Tenant::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get([
                'id',
                'code',
                'name'
            ]);

        $plans = Plan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view(
            'system.subscriptions.index',
            compact(
                'tenants',
                'plans'
            )
        );
    }


    public function data(Request $request): JsonResponse
    {
        $query = Subscription::query()
            ->with([
                'tenant:id,code,name',
                'plan:id,code,name',
            ])
            ->latest('id');


        if ($request->filled('search')) {

            $search = trim(
                (string) $request->search
            );

            $query->where(function ($query) use ($search) {

                $query
                    ->whereHas(
                        'tenant',
                        function ($query) use ($search) {
                            $query
                                ->where(
                                    'name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'code',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    )
                    ->orWhereHas(
                        'plan',
                        function ($query) use ($search) {
                            $query->where(
                                'name',
                                'like',
                                "%{$search}%"
                            );
                        }
                    );

            });
        }


        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->status
            );
        }


        if ($request->filled('tenant_id')) {
            $query->where(
                'tenant_id',
                $request->tenant_id
            );
        }


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


        return response()->json(
            $query->paginate($perPage)
        );
    }


    public function store(
        StoreSubscriptionRequest $request
    ): JsonResponse {

        $subscription =
            $this->subscriptionService->create(
                $request->validated(),
                $request->user()->id
            );


        return response()->json([
            'success' => true,
            'message' =>
                'تم إنشاء الاشتراك بنجاح.',
            'subscription' =>
                $subscription,
        ], 201);
    }


    public function show(
        Subscription $subscription
    ): JsonResponse {

        $subscription->load([
            'tenant',
            'plan',
        ]);

        return response()->json([
            'success' => true,
            'subscription' =>
                $subscription,
        ]);
    }
}