<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\System\StorePlanRequest;
use App\Http\Requests\System\UpdatePlanRequest;
use App\Models\Plan;
use App\Services\SaaS\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function __construct(
        private readonly PlanService $planService
    ) {
    }

    public function index(): View
    {
        return view('system.plans.index');
    }

    public function data(Request $request): JsonResponse
    {
        $search = trim((string) $request->get('search', ''));

        $perPage = min(
            max((int) $request->get('per_page', 15), 10),
            100
        );

        $query = Plan::query()
            ->orderBy('sort_order')
            ->orderBy('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where(
                'is_active',
                $request->get('status') === '1'
            );
        }

        return response()->json(
            $query->paginate($perPage)
        );
    }

    public function store(
        StorePlanRequest $request
    ): JsonResponse {
        $plan = $this->planService->create(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الباقة بنجاح.',
            'plan' => $plan,
        ], 201);
    }

    public function show(Plan $plan): JsonResponse
    {
        return response()->json([
            'success' => true,
            'plan' => $plan,
        ]);
    }

    public function update(
        UpdatePlanRequest $request,
        Plan $plan
    ): JsonResponse {
        $plan = $this->planService->update(
            $plan,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الباقة بنجاح.',
            'plan' => $plan,
        ]);
    }

    public function destroy(Plan $plan): JsonResponse
    {
        $this->planService->delete($plan);

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الباقة بنجاح.',
        ]);
    }
}