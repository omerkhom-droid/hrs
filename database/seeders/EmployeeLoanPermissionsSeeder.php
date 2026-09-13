<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class EmployeeLoanPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $guardName = 'web';

        $permissions = [
            'loans.view',
            'loans.manage',
            'loans.approve',
            'self_service.loans',
        ];

        /*
         * تنظيف الكاش قبل التعامل مع الصلاحيات.
         */
        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();

        DB::transaction(function () use (
            $permissions,
            $guardName
        ) {
            foreach ($permissions as $permission) {
                Permission::firstOrCreate([
                    'name' => $permission,
                    'guard_name' => $guardName,
                ]);
            }

            /*
             * مالك الشركة يحصل على جميع صلاحيات السلف.
             */
            $ownerRoles = Role::query()
                ->where('name', 'tenant_owner')
                ->where('guard_name', $guardName)
                ->get();

            foreach ($ownerRoles as $role) {
                setPermissionsTeamId($role->tenant_id);

                $role->givePermissionTo($permissions);
            }

            /*
             * منح صلاحية الخدمة الذاتية لأدوار الموظفين
             * إذا كانت موجودة.
             */
            $employeeRoles = Role::query()
                ->whereIn('name', [
                    'employee',
                    'tenant_employee',
                ])
                ->where('guard_name', $guardName)
                ->get();

            foreach ($employeeRoles as $role) {
                setPermissionsTeamId($role->tenant_id);

                $role->givePermissionTo([
                    'self_service.loans',
                ]);
            }

            setPermissionsTeamId(null);
        });

        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();
    }
}