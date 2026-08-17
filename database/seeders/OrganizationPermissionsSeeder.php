<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class OrganizationPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            /*
            |--------------------------------------------------------------------------
            | الفروع
            |--------------------------------------------------------------------------
            */

            'branches.view',
            'branches.create',
            'branches.update',
            'branches.delete',


            /*
            |--------------------------------------------------------------------------
            | الإدارات
            |--------------------------------------------------------------------------
            */

            'departments.view',
            'departments.create',
            'departments.update',
            'departments.delete',


            /*
            |--------------------------------------------------------------------------
            | المسميات الوظيفية
            |--------------------------------------------------------------------------
            */

            'job_titles.view',
            'job_titles.create',
            'job_titles.update',
            'job_titles.delete',


            /*
            |--------------------------------------------------------------------------
            | مواقع العمل
            |--------------------------------------------------------------------------
            */

            'work_locations.view',
            'work_locations.create',
            'work_locations.update',
            'work_locations.delete',
        ];


        DB::transaction(function () use ($permissions) {

            /*
             * إنشاء الصلاحيات بدون تكرار.
             */
            foreach ($permissions as $permission) {
                Permission::firstOrCreate([
                    'name' =>
                        $permission,

                    'guard_name' =>
                        'web',
                ]);
            }


            /*
             * منح الصلاحيات الجديدة لكل مالكي الشركات الحاليين.
             */
            $ownerRoles = Role::query()
                ->where(
                    'name',
                    'tenant_owner'
                )
                ->get();


            foreach ($ownerRoles as $role) {
                setPermissionsTeamId(
                    $role->tenant_id
                );

                $role->givePermissionTo(
                    $permissions
                );
            }


            /*
             * إعادة سياق الشركة إلى الوضع الافتراضي.
             */
            setPermissionsTeamId(null);
        });


        app(
            PermissionRegistrar::class
        )->forgetCachedPermissions();
    }
}