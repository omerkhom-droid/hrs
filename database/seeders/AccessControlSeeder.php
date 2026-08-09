<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Services\Access\TenantAccessService;
use Illuminate\Database\Seeder;

class AccessControlSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(TenantAccessService::class);

        Tenant::query()
            ->orderBy('id')
            ->chunkById(100, function ($tenants) use ($service) {
                foreach ($tenants as $tenant) {
                    $service->ensureDefaults($tenant);
                }
            });
    }
}