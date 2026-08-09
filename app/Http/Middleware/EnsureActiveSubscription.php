<?php

namespace App\Http\Middleware;

use App\Services\SaaS\SubscriptionAccessService;
use Closure;
use Illuminate\Http\Request;

class EnsureActiveSubscription
{
    public function __construct(
        private readonly SubscriptionAccessService $access
    ) {
    }


    public function handle(
        Request $request,
        Closure $next
    ) {

        $tenant =
            $request->user()->tenant;


        $result =
            $this->access->evaluate($tenant);


        if (!$result['allowed']) {

            return response()->view(
                'tenant.subscription-blocked',
                [
                    'tenant' => $tenant,

                    'subscription' =>
                        $result['subscription'],

                    'message' =>
                        $result['message'],
                ],
                402
            );
        }


        $request->attributes->set(
            'current_subscription',
            $result['subscription']
        );


        return $next($request);
    }
}