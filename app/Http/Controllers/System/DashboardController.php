<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;

class DashboardController extends Controller
{
    public function index()
    {
        $now = now('UTC');

        $stats = [

            'tenants_total' =>
                Tenant::query()->count(),

            'tenants_active' =>
                Tenant::query()
                    ->where('status', 'active')
                    ->count(),

            'subscriptions_active' =>
                Subscription::query()
                    ->where('status', 'active')
                    ->where('starts_at', '<=', $now)
                    ->where(function ($query) use ($now) {
                        $query
                            ->whereNull('ends_at')
                            ->orWhere('ends_at', '>', $now);
                    })
                    ->count(),

            'subscriptions_trial' =>
                Subscription::query()
                    ->where('status', 'trial')
                    ->where('starts_at', '<=', $now)
                    ->where(function ($query) use ($now) {
                        $query
                            ->whereNull('ends_at')
                            ->orWhere('ends_at', '>', $now);
                    })
                    ->count(),

            'plans_active' =>
                Plan::query()
                    ->where('is_active', true)
                    ->count(),

            'expiring_soon' =>
                Subscription::query()
                    ->whereIn('status', [
                        'active',
                        'trial',
                    ])
                    ->whereBetween('ends_at', [
                        $now,
                        $now->copy()->addDays(30),
                    ])
                    ->count(),
        ];


        $latestTenants = Tenant::query()
            ->latest('id')
            ->limit(5)
            ->get();


        $expiringSubscriptions = Subscription::query()
            ->with([
                'tenant',
                'plan',
            ])
            ->whereIn('status', [
                'active',
                'trial',
            ])
            ->whereBetween('ends_at', [
                $now,
                $now->copy()->addDays(30),
            ])
            ->orderBy('ends_at')
            ->limit(5)
            ->get();


        return view(
            'system.dashboard',
            compact(
                'stats',
                'latestTenants',
                'expiringSubscriptions'
            )
        );
    }
}