<?php

namespace App\Services\SaaS;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantService
{
    public function create(array $data): Tenant
    {
        return DB::transaction(function () use ($data) {

            $data['uuid'] = (string) Str::uuid();

            $data['slug'] =
                strtolower($data['code']);

            return Tenant::create($data);
        });
    }

    public function update(
        Tenant $tenant,
        array $data
    ): Tenant {
        return DB::transaction(function () use ($tenant, $data) {

            /*
             * code و uuid لا يتم تعديلهما.
             */
            unset(
                $data['code'],
                $data['uuid'],
                $data['slug']
            );

            $tenant->update($data);

            return $tenant->refresh();
        });
    }

    public function delete(Tenant $tenant): void
    {
        DB::transaction(function () use ($tenant) {
            $tenant->delete();
        });
    }
}