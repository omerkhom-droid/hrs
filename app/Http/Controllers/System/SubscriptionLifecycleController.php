<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\System\CancelSubscriptionRequest;
use App\Http\Requests\System\ChangeSubscriptionPlanRequest;
use App\Http\Requests\System\RenewSubscriptionRequest;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\SaaS\SubscriptionService;
use Illuminate\Http\JsonResponse;

class SubscriptionLifecycleController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $service
    ) {
    }


    public function convertTrial(
        RenewSubscriptionRequest $request,
        Subscription $subscription
    ): JsonResponse {

        $newSubscription =
            $this->service->convertTrial(
                $subscription,
                $request->billing_cycle,
                $request->user()->id
            );


        return response()->json([
            'success' => true,
            'message' =>
                'تم تحويل الاشتراك التجريبي إلى اشتراك مدفوع.',
            'subscription' =>
                $newSubscription,
        ]);
    }


    public function renew(
        RenewSubscriptionRequest $request,
        Subscription $subscription
    ): JsonResponse {

        $newSubscription =
            $this->service->renew(
                $subscription,
                $request->billing_cycle,
                $request->user()->id
            );


        return response()->json([
            'success' => true,
            'message' =>
                'تم تجديد الاشتراك بنجاح.',
            'subscription' =>
                $newSubscription,
        ]);
    }


    public function changePlan(
        ChangeSubscriptionPlanRequest $request,
        Subscription $subscription
    ): JsonResponse {

        $plan = Plan::findOrFail(
            $request->plan_id
        );


        $newSubscription =
            $this->service->changePlan(
                $subscription,
                $plan,
                $request->billing_cycle,
                $request->user()->id
            );


        return response()->json([
            'success' => true,
            'message' =>
                'تم تغيير الباقة بنجاح.',
            'subscription' =>
                $newSubscription,
        ]);
    }


    public function suspend(
        Subscription $subscription
    ): JsonResponse {

        $this->service->suspend(
            $subscription,
            request()->user()->id
        );


        return response()->json([
            'success' => true,
            'message' =>
                'تم تعليق الاشتراك.',
        ]);
    }


    public function resume(
        Subscription $subscription
    ): JsonResponse {

        $this->service->resume(
            $subscription,
            request()->user()->id
        );


        return response()->json([
            'success' => true,
            'message' =>
                'تم إعادة تفعيل الاشتراك.',
        ]);
    }


    public function cancel(
        CancelSubscriptionRequest $request,
        Subscription $subscription
    ): JsonResponse {

        $this->service->cancel(
            $subscription,
            $request->cancellation_reason,
            $request->user()->id
        );


        return response()->json([
            'success' => true,
            'message' =>
                'تم إلغاء الاشتراك.',
        ]);
    }
}