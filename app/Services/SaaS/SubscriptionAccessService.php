<?php

namespace App\Services\SaaS;

use App\Models\Subscription;
use App\Models\Tenant;

class SubscriptionAccessService
{
    public function current(Tenant $tenant): ?Subscription
    {
        return Subscription::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', [
                'trial',
                'active',
            ])
            ->where('starts_at', '<=', now())
            ->where(function ($query) {
                $query
                    ->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            })
            ->latest('starts_at')
            ->first();
    }


    public function evaluate(Tenant $tenant): array
    {
        if ($tenant->status !== 'active') {

            return [
                'allowed' => false,
                'subscription' => null,
                'message' =>
                    'حساب الشركة موقوف حاليًا. يرجى التواصل مع إدارة رؤية يوم.',
            ];
        }


        $current = $this->current($tenant);


        if ($current) {

            return [
                'allowed' => true,
                'subscription' => $current,
                'message' => null,
            ];
        }


        $last = Subscription::query()
            ->where('tenant_id', $tenant->id)
            ->latest('starts_at')
            ->first();


        if (!$last) {

            return [
                'allowed' => false,
                'subscription' => null,
                'message' =>
                    'لا يوجد اشتراك فعال لهذا الحساب.',
            ];
        }


        $message = match ($last->status) {

            'suspended' =>
                'اشتراك الشركة موقوف مؤقتًا.',

            'cancelled' =>
                'تم إلغاء اشتراك الشركة.',

            'expired' =>
                'انتهت صلاحية اشتراك الشركة.',

            'scheduled' =>
                'يوجد اشتراك مجدول ولكنه لم يبدأ بعد.',

            default =>
                'لا يوجد اشتراك فعال لهذا الحساب.',
        };


        return [
            'allowed' => false,
            'subscription' => $last,
            'message' => $message,
        ];
    }
}