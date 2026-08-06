<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\System\StoreTenantRequest;
use App\Http\Requests\System\UpdateTenantRequest;
use App\Models\Tenant;
use App\Services\SaaS\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function __construct(
        private readonly TenantService $tenantService
    ) {
    }

    public function index(): View
    {
        return view('system.tenants.index');
    }

    public function data(Request $request): JsonResponse
    {
        $search = trim(
            (string) $request->get('search', '')
        );

        $status = $request->get('status');

        $perPage = min(
            max((int) $request->get('per_page', 15), 10),
            100
        );

        $query = Tenant::query()
            ->select([
                'id',
                'uuid',
                'code',
                'name',
                'contact_name',
                'email',
                'phone',
                'country_code',
                'currency_code',
                'timezone',
                'locale',
                'status',
                'created_at',
            ])
            ->latest('id');

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (
            $status &&
            in_array(
                $status,
                ['active', 'suspended', 'inactive'],
                true
            )
        ) {
            $query->where('status', $status);
        }

        return response()->json(
            $query->paginate($perPage)
        );
    }

    public function store(
        StoreTenantRequest $request
    ): JsonResponse {
        $tenant = $this->tenantService->create(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء العميل بنجاح.',
            'tenant' => $tenant,
        ], 201);
    }

    public function show(
        Tenant $tenant
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'tenant' => $tenant,
        ]);
    }

    public function update(
        UpdateTenantRequest $request,
        Tenant $tenant
    ): JsonResponse {
        $tenant = $this->tenantService->update(
            $tenant,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث بيانات العميل بنجاح.',
            'tenant' => $tenant,
        ]);
    }

    public function destroy(
        Tenant $tenant
    ): JsonResponse {
        $this->tenantService->delete($tenant);

        return response()->json([
            'success' => true,
            'message' => 'تم حذف العميل بنجاح.',
        ]);
    }
}