<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlanFeatureController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Edit Plan Features
    |--------------------------------------------------------------------------
    */

    public function edit(Plan $plan): View
    {
        $features = Feature::query()
            ->where('is_active', true)
            ->orderBy('module')
            ->orderBy('name')
            ->get()
            ->groupBy('module');


        $selectedFeatures = $plan
            ->features()
            ->pluck('features.id');


        return view(
            'system.plans.features',
            compact(
                'plan',
                'features',
                'selectedFeatures'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Update Plan Features
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        Plan $plan
    ): JsonResponse {

        $validated = $request->validate([

            'features' => [
                'nullable',
                'array',
            ],

            'features.*' => [
                'integer',

                Rule::exists('features', 'id')
                    ->where(function ($query) {
                        $query->where(
                            'is_active',
                            true
                        );
                    }),
            ],

        ]);


        $featureIds = collect(
            $validated['features'] ?? []
        )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();


        $syncData = [];

        foreach ($featureIds as $featureId) {

            $syncData[$featureId] = [
                'value' => '1',
            ];

        }


        DB::transaction(
            function () use (
                $plan,
                $syncData
            ) {

                $plan
                    ->features()
                    ->sync($syncData);

            }
        );


        return response()->json([
            'success' => true,
            'message' =>
                'تم تحديث خصائص الباقة بنجاح.',
        ]);
    }
}