<?php

namespace App\Services\SaaS;

use App\Models\User;
use App\Services\Access\TenantAccessService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TenantProvisioningService
{
    public function __construct(
        private readonly TenantService $tenantService,
        private readonly TenantAccessService $tenantAccessService,
        private readonly SubscriptionService $subscriptionService,
    ) {
    }

    public function create(
        array $data,
        ?int $actorId = null
    ): array {
        return DB::transaction(function () use (
            $data,
            $actorId
        ) {
            /*
            |--------------------------------------------------------------------------
            | 1. إنشاء العميل
            |--------------------------------------------------------------------------
            */

            $tenantData = Arr::only($data, [
                'code',
                'name',
                'contact_name',
                'email',
                'phone',
                'country_code',
                'currency_code',
                'timezone',
                'locale',
            ]);

            /*
             * يجب أن يكون العميل نشطًا حتى يتمكن
             * SubscriptionService من إنشاء الاشتراك.
             */
            $tenantData['status'] = 'active';

            $tenant = $this->tenantService->create(
                $tenantData
            );


            /*
            |--------------------------------------------------------------------------
            | 2. التأكد من بريد المستخدم
            |--------------------------------------------------------------------------
            */

            $ownerEmail = strtolower(
                trim($data['admin_email'])
            );

            $emailExists = User::withTrashed()
                ->where('email', $ownerEmail)
                ->exists();

            if ($emailExists) {
                throw ValidationException::withMessages([
                    'admin_email' =>
                        'البريد الإلكتروني لمدير الحساب مستخدم مسبقًا.',
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | 3. إنشاء مستخدم مدير العميل
            |--------------------------------------------------------------------------
            */

            $owner = User::create([
                'tenant_id' =>
                    $tenant->id,

                'name' =>
                    trim($data['admin_name']),

                'email' =>
                    $ownerEmail,

                'password' =>
                    Hash::make($data['password']),

                'is_system_admin' =>
                    false,

                'is_active' =>
                    true,

                'locale' =>
                    $tenant->locale ?: 'ar',
            ]);


            /*
            |--------------------------------------------------------------------------
            | 4. إسناد دور مالك الشركة
            |--------------------------------------------------------------------------
            */

            $this->tenantAccessService->assignRole(
                $owner,
                $tenant,
                'tenant_owner'
            );


            /*
            |--------------------------------------------------------------------------
            | 5. إنشاء الاشتراك الأول
            |--------------------------------------------------------------------------
            */

            $subscription = $this->subscriptionService->create([
                'tenant_id' =>
                    $tenant->id,

                'plan_id' =>
                    (int) $data['plan_id'],

                'starts_at' =>
                    $data['starts_at'],

                'billing_cycle' =>
                    $data['billing_cycle'],

                'use_trial' =>
                    (bool) $data['use_trial'],

                'auto_renew' =>
                    (bool) $data['auto_renew'],
            ], $actorId);


            /*
            |--------------------------------------------------------------------------
            | النتيجة
            |--------------------------------------------------------------------------
            */

            return [
                'tenant' =>
                    $tenant->refresh(),

                'owner' =>
                    $owner->refresh(),

                'subscription' =>
                    $subscription->refresh(),
            ];
        }, 3);
    }
}