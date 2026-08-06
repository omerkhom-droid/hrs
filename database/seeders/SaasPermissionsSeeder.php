<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class SaasPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $resources = [
            'tenants',
            'plans',
            'subscriptions',
            'companies',
            'branches',
            'departments',
            'cost_centers',
            'positions',
            'grades',
            'users',
            'roles',
            'employees',
            'contracts',
            'documents',
            'shifts',
            'attendance',
            'leaves',
            'payroll',
            'recruitment',
            'performance',
            'training',
            'reports',
            'settings',
        ];

        foreach ($resources as $resource) {
            foreach (['view', 'create', 'update', 'delete'] as $action) {
                Permission::findOrCreate(
                    "{$resource}.{$action}",
                    'web'
                );
            }
        }

        $specialPermissions = [
            'tenants.suspend',
            'tenants.activate',

            'subscriptions.renew',
            'subscriptions.cancel',

            'users.activate',
            'users.deactivate',
            'users.reset_password',

            'roles.assign_permissions',

            'employees.import',
            'employees.export',
            'employees.archive',
            'employees.manage_documents',

            'attendance.import',
            'attendance.approve',
            'attendance.correct',

            'leaves.approve',
            'leaves.reject',
            'leaves.manage_balances',

            'payroll.run',
            'payroll.approve',
            'payroll.cancel',
            'payroll.export',
            'payroll.close',

            'recruitment.approve_offer',

            'performance.approve',

            'reports.export',

            'settings.manage_approval_workflows',
            'settings.manage_integrations',
        ];

        foreach ($specialPermissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}