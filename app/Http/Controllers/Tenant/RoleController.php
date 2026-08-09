<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\Access\TenantAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    private const SYSTEM_ROLES = [
        'tenant_owner',
        'hr_manager',
        'hr_officer',
        'payroll_manager',
        'manager',
        'employee',
    ];

    private const ROLE_LABELS = [
        'tenant_owner' => 'مالك الحساب',
        'hr_manager' => 'مدير الموارد البشرية',
        'hr_officer' => 'موظف الموارد البشرية',
        'payroll_manager' => 'مدير الرواتب',
        'manager' => 'مدير',
        'employee' => 'موظف',
    ];

    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $search = trim((string) $request->get('q'));

        $roles = Role::query()
            ->where('tenant_id', $tenantId)

            ->when($search, function ($query) use ($search) {
                $query->where(
                    'name',
                    'like',
                    "%{$search}%"
                );
            })

            ->withCount([
                'permissions',
                'users',
            ])

            ->orderByRaw(
                "CASE WHEN name = 'tenant_owner' THEN 0 ELSE 1 END"
            )

            ->orderBy('name')

            ->paginate(15)

            ->withQueryString();


        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn(
                'name',
                TenantAccessService::PERMISSIONS
            )
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ]);


        return view('tenant.roles.index', [
            'roles' => $roles,
            'permissions' => $permissions,
            'search' => $search,
            'roleLabels' => self::ROLE_LABELS,
            'systemRoles' => self::SYSTEM_ROLES,
        ]);
    }

    public function show(
        Request $request,
        int $role
    ) {
        $role = $this->tenantRole(
            $request,
            $role
        );

        return response()->json([
            'role' => [
                'id' => $role->id,
                'name' => $role->name,

                'label' =>
                    self::ROLE_LABELS[$role->name]
                    ?? $role->name,

                'is_system' =>
                    in_array(
                        $role->name,
                        self::SYSTEM_ROLES,
                        true
                    ),

                'is_locked' =>
                    $role->name === 'tenant_owner',

                'permissions' =>
                    $role->permissions
                        ->pluck('id')
                        ->values(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $request->merge([
            'name' => trim(
                (string) $request->input('name')
            ),
        ]);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',

                Rule::unique(
                    config('permission.table_names.roles'),
                    'name'
                )->where(
                    fn ($query) =>
                        $query
                            ->where(
                                'tenant_id',
                                $tenantId
                            )
                            ->where(
                                'guard_name',
                                'web'
                            )
                ),
            ],

            'permissions' => [
                'required',
                'array',
                'min:1',
            ],

            'permissions.*' => [
                'required',
                'integer',
            ],
        ]);


        if (
            in_array(
                Str::lower($data['name']),
                self::SYSTEM_ROLES,
                true
            )
        ) {
            throw ValidationException::withMessages([
                'name' =>
                    'هذا الاسم محجوز لدور أساسي في النظام.',
            ]);
        }


        $permissions = $this->validPermissions(
            $request,
            $data['permissions']
        );


        $role = DB::transaction(
            function () use (
                $tenantId,
                $data,
                $permissions
            ) {
                $role = Role::create([
                    'tenant_id' => $tenantId,
                    'name' => $data['name'],
                    'guard_name' => 'web',
                ]);

                $role->syncPermissions(
                    $permissions
                );

                return $role;
            }
        );


        return response()->json([
            'message' => 'تم إنشاء الدور بنجاح.',
            'id' => $role->id,
        ]);
    }

    public function update(
        Request $request,
        int $role
    ) {
        $role = $this->tenantRole(
            $request,
            $role
        );

        $this->assertEditable(
            $request,
            $role
        );


        if (
            in_array(
                $role->name,
                self::SYSTEM_ROLES,
                true
            )
        ) {
            /*
             * أسماء الأدوار الأساسية ثابتة،
             * لكن يمكن تعديل صلاحياتها.
             */
            $request->merge([
                'name' => $role->name,
            ]);

        } else {

            $request->merge([
                'name' => trim(
                    (string) $request->input('name')
                ),
            ]);
        }


        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',

                Rule::unique(
                    config('permission.table_names.roles'),
                    'name'
                )
                    ->where(
                        fn ($query) =>
                            $query
                                ->where(
                                    'tenant_id',
                                    $request->user()->tenant_id
                                )
                                ->where(
                                    'guard_name',
                                    'web'
                                )
                    )
                    ->ignore($role->id),
            ],

            'permissions' => [
                'required',
                'array',
                'min:1',
            ],

            'permissions.*' => [
                'required',
                'integer',
            ],
        ]);


        if (
            !in_array(
                $role->name,
                self::SYSTEM_ROLES,
                true
            ) &&
            in_array(
                Str::lower($data['name']),
                self::SYSTEM_ROLES,
                true
            )
        ) {
            throw ValidationException::withMessages([
                'name' =>
                    'هذا الاسم محجوز لدور أساسي في النظام.',
            ]);
        }


        $permissions = $this->validPermissions(
            $request,
            $data['permissions']
        );


        DB::transaction(
            function () use (
                $role,
                $data,
                $permissions
            ) {
                $role->update([
                    'name' => $data['name'],
                ]);

                $role->syncPermissions(
                    $permissions
                );
            }
        );


        return response()->json([
            'message' => 'تم تحديث الدور بنجاح.',
        ]);
    }

    public function destroy(
        Request $request,
        int $role
    ) {
        $role = $this->tenantRole(
            $request,
            $role
        );

        if (
            in_array(
                $role->name,
                self::SYSTEM_ROLES,
                true
            )
        ) {
            throw ValidationException::withMessages([
                'role' =>
                    'لا يمكن حذف الأدوار الأساسية.',
            ]);
        }


        $assignments = DB::table(
            config(
                'permission.table_names.model_has_roles'
            )
        )
            ->where(
                config(
                    'permission.column_names.role_pivot_key',
                    'role_id'
                ) ?: 'role_id',
                $role->id
            )
            ->count();


        if ($assignments > 0) {
            throw ValidationException::withMessages([
                'role' =>
                    'لا يمكن حذف دور مرتبط بمستخدمين.',
            ]);
        }


        $role->delete();


        return response()->json([
            'message' => 'تم حذف الدور بنجاح.',
        ]);
    }

    private function tenantRole(
        Request $request,
        int $id
    ): Role {
        return Role::query()
            ->where(
                'tenant_id',
                $request->user()->tenant_id
            )
            ->with('permissions:id,name')
            ->findOrFail($id);
    }

    private function assertEditable(
        Request $request,
        Role $role
    ): void {
        if ($role->name === 'tenant_owner') {
            abort(
                403,
                'دور مالك الحساب محمي ولا يمكن تعديله.'
            );
        }


        if (
            !$request->user()
                ->hasRole('tenant_owner') &&
            $request->user()
                ->roles
                ->contains('id', $role->id)
        ) {
            abort(
                403,
                'لا يمكنك تعديل الدور المرتبط بحسابك.'
            );
        }
    }

    private function validPermissions(
        Request $request,
        array $permissionIds
    ) {
        $permissionIds = collect(
            $permissionIds
        )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();


        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn(
                'name',
                TenantAccessService::PERMISSIONS
            )
            ->whereIn('id', $permissionIds)
            ->get();


        if (
            $permissions->count()
            !==
            $permissionIds->count()
        ) {
            throw ValidationException::withMessages([
                'permissions' =>
                    'توجد صلاحية غير صالحة.',
            ]);
        }


        /*
         * المستخدم غير المالك لا يستطيع
         * منح صلاحيات لا يمتلكها.
         */
        if (
            !$request->user()
                ->hasRole('tenant_owner')
        ) {
            $allowed = $request->user()
                ->getAllPermissions()
                ->pluck('name');

            $hasForbiddenPermission =
                $permissions
                    ->pluck('name')
                    ->diff($allowed)
                    ->isNotEmpty();


            if ($hasForbiddenPermission) {
                throw ValidationException::withMessages([
                    'permissions' =>
                        'لا يمكنك منح صلاحيات أعلى من صلاحياتك.',
                ]);
            }
        }


        return $permissions;
    }
}