<?php

namespace App\Services\SaaS;

use App\Models\Plan;
use Illuminate\Support\Facades\DB;

class PlanService
{
    public function create(array $data): Plan
    {
        return DB::transaction(function () use ($data) {
            return Plan::create($data);
        });
    }

    public function update(
        Plan $plan,
        array $data
    ): Plan {
        return DB::transaction(function () use ($plan, $data) {

            unset($data['code']);

            $plan->update($data);

            return $plan->refresh();
        });
    }

    public function delete(Plan $plan): void
    {
        DB::transaction(function () use ($plan) {
            $plan->delete();
        });
    }
}