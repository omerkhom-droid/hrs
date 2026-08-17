<?php

namespace App\Services\HR;

use App\Models\Employee;
use App\Models\EmployeeMobileDevice;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

class MobileDeviceService
{
    public function employeeForUser(
        Tenant $tenant,
        User $user
    ): Employee {
        if (
            (int) $user->tenant_id !== (int) $tenant->id
            || $user->is_system_admin
        ) {
            throw new LogicException(
                'هذا الحساب غير مرتبط بالشركة الحالية.'
            );
        }

        $employee = Employee::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->where('employment_status', 'active')
            ->first();

        if (!$employee) {
            throw new LogicException(
                'لا يوجد ملف موظف نشط مرتبط بهذا الحساب.'
            );
        }

        return $employee;
    }

    public function register(
        Tenant $tenant,
        User $user,
        array $data,
        ?string $ip = null
    ): EmployeeMobileDevice {
        $employee = $this->employeeForUser($tenant, $user);

        return DB::transaction(function () use (
            $tenant,
            $user,
            $employee,
            $data,
            $ip
        ) {
            $device = EmployeeMobileDevice::withTrashed()
                ->where('tenant_id', $tenant->id)
                ->where('user_id', $user->id)
                ->where('device_uuid', $data['device_uuid'])
                ->lockForUpdate()
                ->first();

            if (!$device) {
                $device = new EmployeeMobileDevice();
                $device->tenant_id = $tenant->id;
                $device->user_id = $user->id;
                $device->employee_id = $employee->id;
                $device->device_uuid = $data['device_uuid'];
            }

            if ($device->trashed()) {
                $device->restore();
            }

            $device->fill([
                'platform' => $data['platform'],
                'device_name' => $data['device_name'] ?? null,
                'device_model' => $data['device_model'] ?? null,
                'os_version' => $data['os_version'] ?? null,
                'app_version' => $data['app_version'] ?? null,
                'push_token' => $data['push_token'] ?? $device->push_token,
                'is_active' => true,
                'last_seen_at' => now(),
                'last_ip' => $ip,
            ]);
            $device->employee_id = $employee->id;
            $device->save();

            return $device->refresh();
        });
    }

    public function activeDevice(
        Tenant $tenant,
        User $user,
        string $deviceUuid,
        ?string $ip = null
    ): EmployeeMobileDevice {
        $employee = $this->employeeForUser($tenant, $user);

        $device = EmployeeMobileDevice::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->where('employee_id', $employee->id)
            ->where('device_uuid', $deviceUuid)
            ->where('is_active', true)
            ->first();

        if (!$device) {
            throw new LogicException(
                'الجهاز غير مسجل أو تم تعطيله. سجّل الجهاز ثم حاول مرة أخرى.'
            );
        }

        $device->forceFill([
            'last_seen_at' => now(),
            'last_ip' => $ip,
        ])->save();

        return $device;
    }
}