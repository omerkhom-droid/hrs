<?php

namespace App\Services\SaaS;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SubscriptionService
{
    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function create(
        array $data,
        ?int $actorId = null
    ): Subscription {

        return DB::transaction(function () use ($data, $actorId) {

            $tenant = Tenant::query()
                ->whereKey($data['tenant_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($tenant->status !== 'active') {
                throw ValidationException::withMessages([
                    'tenant_id' => 'العميل غير نشط.',
                ]);
            }

            $plan = Plan::query()
                ->whereKey($data['plan_id'])
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();

            $startsAt = CarbonImmutable::parse(
                $data['starts_at']
            )->startOfDay();


            $exists = Subscription::query()
                ->where('tenant_id', $tenant->id)
                ->whereIn('status', [
                    'trial',
                    'active',
                    'suspended',
                    'scheduled',
                ])
                ->where(function ($query) use ($startsAt) {
                    $query
                        ->whereNull('ends_at')
                        ->orWhere('ends_at', '>=', $startsAt);
                })
                ->exists();


            if ($exists) {
                throw ValidationException::withMessages([
                    'tenant_id' =>
                        'هذا العميل لديه اشتراك قائم بالفعل.',
                ]);
            }


            $useTrial =
                (bool) $data['use_trial']
                && $plan->trial_days > 0;


            if ($useTrial) {

                $trialEndsAt =
                    $startsAt->addDays(
                        $plan->trial_days
                    );

                return Subscription::create([
                    'uuid' => (string) Str::uuid(),
                    'tenant_id' => $tenant->id,
                    'plan_id' => $plan->id,
                    'status' => 'trial',
                    'billing_cycle' => $data['billing_cycle'],
                    'price' => 0,
                    'currency_code' => $plan->currency_code,
                    'starts_at' => $startsAt,
                    'trial_ends_at' => $trialEndsAt,
                    'ends_at' => $trialEndsAt,
                    'auto_renew' => (bool) $data['auto_renew'],
                    'plan_snapshot' => $this->snapshot($plan),

                    'metadata' => [
                        'source' => 'system_admin',
                        'created_by' => $actorId,
                        'events' => [],
                    ],
                ]);
            }


            return $this->createPaid(
                tenant: $tenant,
                plan: $plan,
                billingCycle: $data['billing_cycle'],
                startsAt: $startsAt,
                autoRenew: (bool) $data['auto_renew'],
                actorId: $actorId
            );
        });
    }


    /*
    |--------------------------------------------------------------------------
    | Convert Trial To Paid
    |--------------------------------------------------------------------------
    */

    public function convertTrial(
        Subscription $subscription,
        string $billingCycle,
        ?int $actorId = null
    ): Subscription {

        return DB::transaction(function () use (
            $subscription,
            $billingCycle,
            $actorId
        ) {

            $subscription = Subscription::query()
                ->whereKey($subscription->id)
                ->lockForUpdate()
                ->firstOrFail();


            if ($subscription->status !== 'trial') {
                throw ValidationException::withMessages([
                    'subscription' =>
                        'هذا الاشتراك ليس اشتراكًا تجريبيًا.',
                ]);
            }


            $plan = Plan::query()
                ->whereKey($subscription->plan_id)
                ->where('is_active', true)
                ->first();


            if (!$plan) {
                throw ValidationException::withMessages([
                    'plan' =>
                        'الباقة الحالية غير متاحة للاشتراك.',
                ]);
            }


            $now = CarbonImmutable::now();


            $subscription->status = 'expired';
            $subscription->ends_at = $now;

            $subscription->metadata =
                $this->addEvent(
                    $subscription,
                    'trial_converted',
                    $actorId
                );

            $subscription->save();


            return $this->createPaid(
                tenant: $subscription->tenant,
                plan: $plan,
                billingCycle: $billingCycle,
                startsAt: $now,
                autoRenew: $subscription->auto_renew,
                actorId: $actorId,
                extraMetadata: [
                    'source' => 'trial_conversion',
                    'previous_subscription_id' =>
                        $subscription->id,
                ]
            );
        });
    }


    /*
    |--------------------------------------------------------------------------
    | Renew
    |--------------------------------------------------------------------------
    */

    public function renew(
        Subscription $subscription,
        string $billingCycle,
        ?int $actorId = null
    ): Subscription {

        return DB::transaction(function () use (
            $subscription,
            $billingCycle,
            $actorId
        ) {

            $subscription = Subscription::query()
                ->whereKey($subscription->id)
                ->lockForUpdate()
                ->firstOrFail();


            if (!in_array(
                $subscription->status,
                ['active', 'expired'],
                true
            )) {
                throw ValidationException::withMessages([
                    'subscription' =>
                        'لا يمكن تجديد هذا الاشتراك بحالته الحالية.',
                ]);
            }


            $scheduledExists = Subscription::query()
                ->where('tenant_id', $subscription->tenant_id)
                ->where('status', 'scheduled')
                ->exists();


            if ($scheduledExists) {
                throw ValidationException::withMessages([
                    'subscription' =>
                        'يوجد تجديد مجدول مسبقًا لهذا العميل.',
                ]);
            }


            $plan = Plan::query()
                ->whereKey($subscription->plan_id)
                ->where('is_active', true)
                ->first();


            if (!$plan) {
                throw ValidationException::withMessages([
                    'plan' =>
                        'الباقة الحالية غير متاحة للتجديد.',
                ]);
            }


            $now = CarbonImmutable::now();


            if (
                $subscription->status === 'active'
                && $subscription->ends_at
                && $subscription->ends_at->isFuture()
            ) {

                $startsAt =
                    CarbonImmutable::instance(
                        $subscription->ends_at
                    );

            } else {

                $startsAt = $now;

            }


            return $this->createPaid(
                tenant: $subscription->tenant,
                plan: $plan,
                billingCycle: $billingCycle,
                startsAt: $startsAt,
                autoRenew: $subscription->auto_renew,
                actorId: $actorId,
                extraMetadata: [
                    'source' => 'renewal',
                    'previous_subscription_id' =>
                        $subscription->id,
                ]
            );
        });
    }


    /*
    |--------------------------------------------------------------------------
    | Change Plan
    |--------------------------------------------------------------------------
    */

    public function changePlan(
        Subscription $subscription,
        Plan $newPlan,
        string $billingCycle,
        ?int $actorId = null
    ): Subscription {

        return DB::transaction(function () use (
            $subscription,
            $newPlan,
            $billingCycle,
            $actorId
        ) {

            $subscription = Subscription::query()
                ->whereKey($subscription->id)
                ->lockForUpdate()
                ->firstOrFail();


            if (!in_array(
                $subscription->status,
                ['trial', 'active', 'suspended'],
                true
            )) {
                throw ValidationException::withMessages([
                    'subscription' =>
                        'لا يمكن تغيير باقة هذا الاشتراك.',
                ]);
            }


            if ($subscription->plan_id === $newPlan->id) {
                throw ValidationException::withMessages([
                    'plan_id' =>
                        'العميل مشترك بالفعل في هذه الباقة.',
                ]);
            }


            if (!$newPlan->is_active) {
                throw ValidationException::withMessages([
                    'plan_id' =>
                        'الباقة المحددة غير نشطة.',
                ]);
            }


            $scheduledExists = Subscription::query()
                ->where('tenant_id', $subscription->tenant_id)
                ->where('status', 'scheduled')
                ->exists();


            if ($scheduledExists) {
                throw ValidationException::withMessages([
                    'subscription' =>
                        'يوجد تجديد مجدول. يجب إلغاؤه قبل تغيير الباقة.',
                ]);
            }


            $now = CarbonImmutable::now();


            $subscription->status = 'expired';
            $subscription->ends_at = $now;

            $subscription->metadata =
                $this->addEvent(
                    $subscription,
                    'plan_changed',
                    $actorId,
                    [
                        'new_plan_id' => $newPlan->id,
                    ]
                );

            $subscription->save();


            return $this->createPaid(
                tenant: $subscription->tenant,
                plan: $newPlan,
                billingCycle: $billingCycle,
                startsAt: $now,
                autoRenew: $subscription->auto_renew,
                actorId: $actorId,
                extraMetadata: [
                    'source' => 'plan_change',
                    'previous_subscription_id' =>
                        $subscription->id,
                ]
            );
        });
    }


    /*
    |--------------------------------------------------------------------------
    | Suspend
    |--------------------------------------------------------------------------
    */

    public function suspend(
        Subscription $subscription,
        ?int $actorId = null
    ): Subscription {

        if ($subscription->status !== 'active') {
            throw ValidationException::withMessages([
                'subscription' =>
                    'يمكن تعليق الاشتراك الفعال فقط.',
            ]);
        }


        $subscription->status = 'suspended';

        $subscription->metadata =
            $this->addEvent(
                $subscription,
                'suspended',
                $actorId
            );

        $subscription->save();

        return $subscription->refresh();
    }


    /*
    |--------------------------------------------------------------------------
    | Resume
    |--------------------------------------------------------------------------
    */

    public function resume(
        Subscription $subscription,
        ?int $actorId = null
    ): Subscription {

        if ($subscription->status !== 'suspended') {
            throw ValidationException::withMessages([
                'subscription' =>
                    'هذا الاشتراك غير موقوف.',
            ]);
        }


        if (
            $subscription->ends_at
            && $subscription->ends_at->isPast()
        ) {
            throw ValidationException::withMessages([
                'subscription' =>
                    'انتهت مدة الاشتراك. يجب تجديده.',
            ]);
        }


        $subscription->status = 'active';

        $subscription->metadata =
            $this->addEvent(
                $subscription,
                'resumed',
                $actorId
            );

        $subscription->save();

        return $subscription->refresh();
    }


    /*
    |--------------------------------------------------------------------------
    | Cancel
    |--------------------------------------------------------------------------
    */

    public function cancel(
        Subscription $subscription,
        string $reason,
        ?int $actorId = null
    ): Subscription {

        if (!in_array(
            $subscription->status,
            [
                'trial',
                'active',
                'suspended',
                'scheduled',
            ],
            true
        )) {
            throw ValidationException::withMessages([
                'subscription' =>
                    'لا يمكن إلغاء هذا الاشتراك.',
            ]);
        }


        $subscription->status = 'cancelled';
        $subscription->cancelled_at = now();
        $subscription->cancellation_reason = $reason;


        if ($subscription->starts_at->isPast()) {
            $subscription->ends_at = now();
        }


        $subscription->metadata =
            $this->addEvent(
                $subscription,
                'cancelled',
                $actorId,
                [
                    'reason' => $reason,
                ]
            );


        $subscription->save();

        return $subscription->refresh();
    }


    /*
    |--------------------------------------------------------------------------
    | Create Paid Subscription
    |--------------------------------------------------------------------------
    */

    private function createPaid(
        Tenant $tenant,
        Plan $plan,
        string $billingCycle,
        CarbonImmutable $startsAt,
        bool $autoRenew,
        ?int $actorId = null,
        array $extraMetadata = []
    ): Subscription {

        $now = CarbonImmutable::now();


        $status =
            $startsAt->greaterThan($now)
                ? 'scheduled'
                : 'active';


        if ($billingCycle === 'yearly') {

            $price = $plan->yearly_price;

            $endsAt =
                $startsAt->addYear();

        } else {

            $price = $plan->monthly_price;

            $endsAt =
                $startsAt->addMonthNoOverflow();

        }


        $metadata = array_merge([
            'source' => 'system_admin',
            'created_by' => $actorId,
            'events' => [],
        ], $extraMetadata);


        return Subscription::create([

            'uuid' => (string) Str::uuid(),

            'tenant_id' => $tenant->id,

            'plan_id' => $plan->id,

            'status' => $status,

            'billing_cycle' => $billingCycle,

            'price' => $price,

            'currency_code' =>
                $plan->currency_code,

            'starts_at' => $startsAt,

            'trial_ends_at' => null,

            'ends_at' => $endsAt,

            'auto_renew' => $autoRenew,

            'plan_snapshot' =>
                $this->snapshot($plan),

            'metadata' => $metadata,

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Plan Snapshot
    |--------------------------------------------------------------------------
    */

    private function snapshot(Plan $plan): array
    {
        $plan->load('features');


        return [

            'id' => $plan->id,

            'code' => $plan->code,

            'name' => $plan->name,

            'monthly_price' =>
                $plan->monthly_price,

            'yearly_price' =>
                $plan->yearly_price,

            'currency_code' =>
                $plan->currency_code,

            'trial_days' =>
                $plan->trial_days,

            'limits' => [

                'users' =>
                    $plan->max_users,

                'employees' =>
                    $plan->max_employees,

                'branches' =>
                    $plan->max_branches,

            ],

            'features' =>
                $plan->features
                    ->map(fn ($feature) => [

                        'code' =>
                            $feature->code,

                        'value' =>
                            $feature->pivot->value,

                    ])
                    ->values()
                    ->all(),

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Event History
    |--------------------------------------------------------------------------
    */

    private function addEvent(
        Subscription $subscription,
        string $type,
        ?int $actorId,
        array $data = []
    ): array {

        $metadata =
            $subscription->metadata ?? [];


        $events =
            $metadata['events'] ?? [];


        $events[] = [

            'type' => $type,

            'at' => now()->toIso8601String(),

            'actor_id' => $actorId,

            'data' => $data,

        ];


        $metadata['events'] = $events;


        return $metadata;
    }
}