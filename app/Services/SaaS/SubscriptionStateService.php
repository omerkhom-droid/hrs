<?php

namespace App\Services\SaaS;

use App\Models\Subscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionStateService
{
    public function process(): array
    {
        $now = now();

        $result = [
            'expired' => 0,
            'activated' => 0,
            'skipped' => 0,
        ];

        /*
        |--------------------------------------------------------------------------
        | Expire Subscriptions
        |--------------------------------------------------------------------------
        */

        Subscription::query()
            ->whereIn('status', [
                'trial',
                'active',
                'suspended',
                'scheduled',
            ])
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', $now)
            ->select('id')
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions) use (&$result, $now) {

                foreach ($subscriptions as $item) {

                    $expired = DB::transaction(
                        function () use ($item, $now) {

                            $subscription = Subscription::query()
                                ->whereKey($item->id)
                                ->lockForUpdate()
                                ->first();

                            if (!$subscription) {
                                return false;
                            }

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
                                return false;
                            }

                            if (
                                !$subscription->ends_at ||
                                $subscription->ends_at->gt($now)
                            ) {
                                return false;
                            }

                            $previousStatus =
                                $subscription->status;

                            $subscription->status = 'expired';

                            $subscription->metadata =
                                $this->addEvent(
                                    $subscription,
                                    'subscription_expired',
                                    [
                                        'previous_status' =>
                                            $previousStatus,
                                    ]
                                );

                            $subscription->save();

                            return true;
                        }
                    );

                    if ($expired) {
                        $result['expired']++;
                    }
                }
            });


        /*
        |--------------------------------------------------------------------------
        | Activate Scheduled Renewals
        |--------------------------------------------------------------------------
        */

        Subscription::query()
            ->where('status', 'scheduled')
            ->where('starts_at', '<=', $now)
            ->where(function ($query) use ($now) {
                $query
                    ->whereNull('ends_at')
                    ->orWhere('ends_at', '>', $now);
            })
            ->select('id')
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions) use (&$result, $now) {

                foreach ($subscriptions as $item) {

                    $status = DB::transaction(
                        function () use ($item, $now) {

                            $subscription = Subscription::query()
                                ->whereKey($item->id)
                                ->lockForUpdate()
                                ->first();

                            if (
                                !$subscription ||
                                $subscription->status !== 'scheduled'
                            ) {
                                return 'skip';
                            }

                            if ($subscription->starts_at->gt($now)) {
                                return 'skip';
                            }

                            if (
                                $subscription->ends_at &&
                                $subscription->ends_at->lte($now)
                            ) {
                                return 'skip';
                            }


                            /*
                             * حماية من تفعيل اشتراكين
                             * لنفس العميل في نفس الوقت.
                             */
                            $hasCurrentSubscription =
                                Subscription::query()
                                    ->where(
                                        'tenant_id',
                                        $subscription->tenant_id
                                    )
                                    ->whereKeyNot(
                                        $subscription->id
                                    )
                                    ->whereIn('status', [
                                        'trial',
                                        'active',
                                        'suspended',
                                    ])
                                    ->where(
                                        'starts_at',
                                        '<=',
                                        $now
                                    )
                                    ->where(function ($query) use ($now) {
                                        $query
                                            ->whereNull('ends_at')
                                            ->orWhere(
                                                'ends_at',
                                                '>',
                                                $now
                                            );
                                    })
                                    ->exists();


                            if ($hasCurrentSubscription) {
                                return 'conflict';
                            }


                            $subscription->status = 'active';

                            $subscription->metadata =
                                $this->addEvent(
                                    $subscription,
                                    'scheduled_subscription_activated'
                                );

                            $subscription->save();

                            return 'activated';
                        }
                    );


                    if ($status === 'activated') {
                        $result['activated']++;
                    }

                    if ($status === 'conflict') {
                        $result['skipped']++;
                    }
                }
            });


        return $result;
    }


    private function addEvent(
        Subscription $subscription,
        string $type,
        array $data = []
    ): array {

        $metadata =
            $subscription->metadata ?? [];

        $events =
            $metadata['events'] ?? [];

        $events[] = [
            'type' => $type,
            'at' => now()->toIso8601String(),
            'actor_id' => null,
            'data' => $data,
        ];

        $metadata['events'] = $events;

        return $metadata;
    }
}