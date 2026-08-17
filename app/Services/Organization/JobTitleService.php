<?php

namespace App\Services\Organization;

use App\Models\Department;
use App\Models\JobTitle;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class JobTitleService
{
    /**
     * Create a job title for the current tenant.
     */
    public function create(
        array $data,
        int $tenantId
    ): JobTitle {
        if ($tenantId <= 0) {
            throw new InvalidArgumentException(
                'Tenant ID is required to create a job title.'
            );
        }

        $payload = $this->normalizePayload($data);

        $this->ensureDepartmentBelongsToTenant(
            $payload['department_id'],
            $tenantId
        );

        return DB::transaction(
            function () use ($payload, $tenantId): JobTitle {
                $jobTitle = new JobTitle($payload);
                $jobTitle->tenant_id = $tenantId;
                $jobTitle->save();

                return $jobTitle->refresh();
            }
        );
    }

    /**
     * Update a job title without allowing its tenant to change.
     */
    public function update(
        JobTitle $jobTitle,
        array $data
    ): JobTitle {
        $tenantId = (int) $jobTitle->tenant_id;

        if ($tenantId <= 0) {
            throw new InvalidArgumentException(
                'The job title is not linked to a tenant.'
            );
        }

        $payload = $this->normalizePayload($data);

        $this->ensureDepartmentBelongsToTenant(
            $payload['department_id'],
            $tenantId
        );

        return DB::transaction(
            function () use ($jobTitle, $payload): JobTitle {
                $jobTitle->fill($payload);
                $jobTitle->save();

                return $jobTitle->refresh();
            }
        );
    }

    /**
     * Archive a job title using soft deletes.
     */
    public function delete(
        JobTitle $jobTitle
    ): void {
        DB::transaction(
            function () use ($jobTitle): void {
                $jobTitle->delete();
            }
        );
    }

    /**
     * Keep only fields owned by the job-title module.
     */
    private function normalizePayload(
        array $data
    ): array {
        $payload = Arr::only($data, [
            'department_id',
            'code',
            'name',
            'name_en',
            'description',
            'sort_order',
            'is_active',
        ]);

        $payload['department_id'] = !empty($payload['department_id'])
            ? (int) $payload['department_id']
            : null;

        $payload['code'] = strtoupper(
            trim((string) ($payload['code'] ?? ''))
        );

        $payload['name'] = trim(
            (string) ($payload['name'] ?? '')
        );

        $payload['name_en'] = isset($payload['name_en'])
            && trim((string) $payload['name_en']) !== ''
                ? trim((string) $payload['name_en'])
                : null;

        $payload['description'] = isset($payload['description'])
            && trim((string) $payload['description']) !== ''
                ? trim((string) $payload['description'])
                : null;

        $payload['sort_order'] = (int) ($payload['sort_order'] ?? 0);
        $payload['is_active'] = (bool) ($payload['is_active'] ?? true);

        return $payload;
    }

    /**
     * Defence-in-depth tenant validation for service-level calls.
     */
    private function ensureDepartmentBelongsToTenant(
        ?int $departmentId,
        int $tenantId
    ): void {
        if (!$departmentId) {
            return;
        }

        $exists = Department::query()
            ->whereKey($departmentId)
            ->where('tenant_id', $tenantId)
            ->exists();

        if (!$exists) {
            throw ValidationException::withMessages([
                'department_id' =>
                    'الإدارة المختارة غير موجودة أو لا تتبع للشركة الحالية.',
            ]);
        }
    }
}
