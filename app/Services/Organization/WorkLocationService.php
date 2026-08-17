<?php

namespace App\Services\Organization;

use App\Models\Branch;
use App\Models\WorkLocation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class WorkLocationService
{
    public function create(
        array $data,
        int $tenantId
    ): WorkLocation {
        if ($tenantId <= 0) {
            throw new InvalidArgumentException(
                'Tenant ID is required to create a work location.'
            );
        }

        $payload = $this->normalizePayload($data);

        $this->ensureBranchBelongsToTenant(
            $payload['branch_id'],
            $tenantId
        );

        $this->ensureCoordinatesAreComplete($payload);

        return DB::transaction(
            function () use ($payload, $tenantId): WorkLocation {
                $workLocation = new WorkLocation($payload);
                $workLocation->tenant_id = $tenantId;
                $workLocation->save();

                return $workLocation->refresh();
            }
        );
    }

    public function update(
        WorkLocation $workLocation,
        array $data
    ): WorkLocation {
        $tenantId = (int) $workLocation->tenant_id;

        if ($tenantId <= 0) {
            throw new InvalidArgumentException(
                'The work location is not linked to a tenant.'
            );
        }

        $payload = $this->normalizePayload($data);

        $this->ensureBranchBelongsToTenant(
            $payload['branch_id'],
            $tenantId
        );

        $this->ensureCoordinatesAreComplete($payload);

        return DB::transaction(
            function () use ($workLocation, $payload): WorkLocation {
                $workLocation->fill($payload);
                $workLocation->save();

                return $workLocation->refresh();
            }
        );
    }

    public function delete(
        WorkLocation $workLocation
    ): void {
        DB::transaction(
            function () use ($workLocation): void {
                $workLocation->delete();
            }
        );
    }

    private function normalizePayload(
        array $data
    ): array {
        $payload = Arr::only($data, [
            'branch_id',
            'code',
            'name',
            'name_en',
            'type',
            'country_code',
            'city',
            'address',
            'latitude',
            'longitude',
            'attendance_radius',
            'timezone',
            'is_active',
        ]);

        $payload['branch_id'] = !empty($payload['branch_id'])
            ? (int) $payload['branch_id']
            : null;

        $payload['code'] = strtoupper(
            trim((string) ($payload['code'] ?? ''))
        );

        $payload['name'] = trim(
            (string) ($payload['name'] ?? '')
        );

        $payload['name_en'] = $this->nullableString(
            $payload['name_en'] ?? null
        );

        $payload['type'] = strtolower(
            trim((string) ($payload['type'] ?? 'office'))
        );

        $payload['country_code'] = strtoupper(
            trim((string) ($payload['country_code'] ?? 'SA'))
        );

        $payload['city'] = $this->nullableString(
            $payload['city'] ?? null
        );

        $payload['address'] = $this->nullableString(
            $payload['address'] ?? null
        );

        $payload['latitude'] = $this->nullableCoordinate(
            $payload['latitude'] ?? null
        );

        $payload['longitude'] = $this->nullableCoordinate(
            $payload['longitude'] ?? null
        );

        $payload['attendance_radius'] = (int) (
            $payload['attendance_radius'] ?? 100
        );

        $payload['timezone'] = trim(
            (string) (
                $payload['timezone']
                ?? config('app.timezone', 'Asia/Riyadh')
            )
        );

        $payload['is_active'] = (bool) (
            $payload['is_active'] ?? true
        );

        return $payload;
    }

    private function nullableString(
        mixed $value
    ): ?string {
        $value = trim((string) ($value ?? ''));

        return $value !== ''
            ? $value
            : null;
    }

    private function nullableCoordinate(
        mixed $value
    ): ?string {
        if (
            $value === null
            || $value === ''
        ) {
            return null;
        }

        return number_format(
            (float) $value,
            7,
            '.',
            ''
        );
    }

    private function ensureBranchBelongsToTenant(
        ?int $branchId,
        int $tenantId
    ): void {
        if (!$branchId) {
            return;
        }

        $exists = Branch::query()
            ->whereKey($branchId)
            ->where('tenant_id', $tenantId)
            ->exists();

        if (!$exists) {
            throw ValidationException::withMessages([
                'branch_id' =>
                    'الفرع المختار غير موجود أو لا يتبع للشركة الحالية.',
            ]);
        }
    }

    private function ensureCoordinatesAreComplete(
        array $payload
    ): void {
        $hasLatitude = $payload['latitude'] !== null;
        $hasLongitude = $payload['longitude'] !== null;

        if ($hasLatitude === $hasLongitude) {
            return;
        }

        throw ValidationException::withMessages([
            'latitude' =>
                'يجب إدخال خط العرض وخط الطول معًا.',

            'longitude' =>
                'يجب إدخال خط العرض وخط الطول معًا.',
        ]);
    }
}