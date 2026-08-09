<?php

namespace App\Services\Access;

use App\Models\Tenant;
use App\Models\User;
use LogicException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TenantAccessService
{
    public const PERMISSIONS = [
        'dashboard.view',

        'users.view',
        'users.create',
        'users.update',
        'users.deactivate',

        'roles.view',
        'roles.manage',

        'organization.view',
        'organization.manage',

        'employees.view',
        'employees.create',
        'employees.update',
        'employees.archive',
        'employees.import',
        'employees.export',

        'contracts.view',
        'contracts.create',
        'contracts.update',
        'contracts.end',

        'documents.view',
        'documents.manage',

        'attendance.view',
        'attendance.manage',
        'attendance.approve',

        'leave.view',
        'leave.manage',
        'leave.approve',

        'payroll.view',
        'payroll.manage',
        'payroll.process',
        'payroll.approve',

        'recruitment.view',
        'recruitment.manage',

        'performance.view',
        'performance.manage',

        'training.view',
        'training.manage',

        'reports.view',
        'reports.export',

        'audit.view',

        'settings.view',
        'settings.update',

        'self_service.profile',
        'self_service.leave',
        'self_service.attendance',
    ];

    public function ensureDefaults(Tenant $tenant): void
    {
        $registrar = app(PermissionRegistrar::class);

        $registrar->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $previousTenantId = getPermissionsTeamId();

        try {
            setPermissionsTeamId($tenant->id);

            foreach ($this->defaultRoles() as $roleName => $permissions) {
                $role = Role::findOrCreate($roleName, 'web');

                $role->syncPermissions($permissions);
            }
        } finally {
            setPermissionsTeamId($previousTenantId);
        }

        $registrar->forgetCachedPermissions();
    }

    public function assignRole(
        User $user,
        Tenant $tenant,
        string $roleName
    ): void {
        if ((int) $user->tenant_id !== (int) $tenant->id) {
            throw new LogicException(
                'لا يمكن منح المستخدم دوراً تابعاً لعميل آخر.'
            );
        }

        $this->ensureDefaults($tenant);

        $previousTenantId = getPermissionsTeamId();

        try {
            setPermissionsTeamId($tenant->id);

            $user->syncRoles([$roleName]);
        } finally {
            setPermissionsTeamId($previousTenantId);
        }
    }

    private function defaultRoles(): array
    {
        return [
            'tenant_owner' => self::PERMISSIONS,

            'hr_manager' => [
                'dashboard.view',

                'users.view',

                'organization.view',
                'organization.manage',

                'employees.view',
                'employees.create',
                'employees.update',
                'employees.archive',
                'employees.import',
                'employees.export',

                'contracts.view',
                'contracts.create',
                'contracts.update',
                'contracts.end',

                'documents.view',
                'documents.manage',

                'attendance.view',
                'attendance.manage',
                'attendance.approve',

                'leave.view',
                'leave.manage',
                'leave.approve',

                'recruitment.view',
                'recruitment.manage',

                'performance.view',
                'performance.manage',

                'training.view',
                'training.manage',

                'reports.view',
                'reports.export',

                'audit.view',
            ],

            'hr_officer' => [
                'dashboard.view',

                'organization.view',

                'employees.view',
                'employees.create',
                'employees.update',

                'contracts.view',
                'contracts.create',
                'contracts.update',

                'documents.view',
                'documents.manage',

                'attendance.view',
                'attendance.manage',

                'leave.view',
                'leave.manage',

                'recruitment.view',
                'recruitment.manage',

                'training.view',

                'reports.view',
            ],

            'payroll_manager' => [
                'dashboard.view',

                'employees.view',
                'contracts.view',

                'payroll.view',
                'payroll.manage',
                'payroll.process',
                'payroll.approve',

                'reports.view',
                'reports.export',
            ],

            'manager' => [
                'dashboard.view',

                'attendance.view',
                'attendance.approve',

                'leave.view',
                'leave.approve',

                'performance.view',
                'performance.manage',

                'self_service.profile',
                'self_service.leave',
                'self_service.attendance',
            ],

            'employee' => [
                'dashboard.view',
                'self_service.profile',
                'self_service.leave',
                'self_service.attendance',
            ],
        ];
    }
}