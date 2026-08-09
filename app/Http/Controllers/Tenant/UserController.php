<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = $request->user();
        $tenantId = $currentUser->tenant_id;

        $search = trim((string) $request->get('q'));
        $status = $request->get('status');
        $roleId = $request->get('role');

        $isOwner = $currentUser->hasRole('tenant_owner');


        $users = User::query()

            ->where('tenant_id', $tenantId)

            ->where('is_system_admin', false)

            // لا تعرض المستخدم الحالي
            ->where('id', '!=', $currentUser->id)

            // غير المالك لا يرى مالك الحساب
            ->when(
                !$isOwner,
                function ($query) {

                    $query->whereDoesntHave(
                        'roles',
                        function ($query) {

                            $query->where(
                                'name',
                                'tenant_owner'
                            );
                        }
                    );
                }
            )

            ->when(
                $search,
                function ($query) use ($search) {

                    $query->where(
                        function ($query) use ($search) {

                            $query
                                ->where(
                                    'name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'email',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            )

            ->when(
                $status === 'active',
                fn ($query) =>
                    $query->where('is_active', true)
            )

            ->when(
                $status === 'inactive',
                fn ($query) =>
                    $query->where('is_active', false)
            )

            ->when(
                $roleId,
                function ($query) use ($roleId) {

                    $query->whereHas(
                        'roles',
                        function ($query) use ($roleId) {

                            $query->where(
                                'roles.id',
                                $roleId
                            );
                        }
                    );
                }
            )

            ->with('roles:id,name')

            ->latest('id')

            ->paginate(15)

            ->withQueryString();


        $roles = Role::query()

            ->where('tenant_id', $tenantId)

            // لا تعرض tenant_owner للمستخدم الأقل
            ->when(
                !$isOwner,
                fn ($query) =>
                    $query->where(
                        'name',
                        '!=',
                        'tenant_owner'
                    )
            )

            ->orderBy('name')

            ->get([
                'id',
                'name',
            ]);


        return view(
            'tenant.users.index',
            compact(
                'users',
                'roles',
                'search',
                'status',
                'roleId'
            )
        );
    }

    public function show(Request $request, int $user)
    {
        $user = $this->tenantUser($request, $user);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => (bool) $user->is_active,
                'roles' => $user->roles->pluck('id')->values(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],

            'email' => [
                'required',
                'email',
                'max:190',
                Rule::unique('users', 'email'),
            ],

            'password' => [
                'required',
                'string',
                'min:10',
                'confirmed',
            ],

            'roles' => [
                'required',
                'array',
                'min:1',
            ],

            'roles.*' => [
                'integer',
            ],
        ]);

        $roles = $this->validateRoles(
            $tenantId,
            $data['roles']
        );

        $user = DB::transaction(function () use (
            $data,
            $tenantId,
            $roles
        ) {
            $user = User::create([
                'tenant_id' => $tenantId,
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'password' => Hash::make($data['password']),
                'is_system_admin' => false,
                'is_active' => true,
                'locale' => 'ar',
            ]);

            $user->syncRoles($roles);

            return $user;
        });

        return response()->json([
            'message' => 'تم إنشاء المستخدم بنجاح.',
            'id' => $user->id,
        ]);
    }

    public function update(
        Request $request,
        int $user
    ) {
        $user = $this->tenantUser($request, $user);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'email' => [
                'required',
                'email',
                'max:190',
                Rule::unique('users', 'email')
                    ->ignore($user->id),
            ],

            'password' => [
                'nullable',
                'string',
                'min:10',
                'confirmed',
            ],

            'roles' => [
                'required',
                'array',
                'min:1',
            ],

            'roles.*' => [
                'integer',
            ],
        ]);

        $roles = $this->validateRoles(
            $request->user()->tenant_id,
            $data['roles']
        );

        $removingOwner =
            $user->hasRole('tenant_owner') &&
            !$roles->contains('name', 'tenant_owner');

        if (
            $removingOwner &&
            $this->activeOwnerCount(
                $request->user()->tenant_id
            ) <= 1
        ) {
            throw ValidationException::withMessages([
                'roles' =>
                    'لا يمكن إزالة دور مالك الحساب من آخر مالك.',
            ]);
        }

        DB::transaction(function () use (
            $user,
            $data,
            $roles
        ) {
            $user->name = $data['name'];
            $user->email = strtolower($data['email']);

            if (!empty($data['password'])) {
                $user->password = Hash::make(
                    $data['password']
                );
            }

            $user->save();

            $user->syncRoles($roles);
        });

        return response()->json([
            'message' => 'تم تحديث المستخدم بنجاح.',
        ]);
    }

    public function status(
        Request $request,
        int $user
    ) {
        $user = $this->tenantUser($request, $user);

        $data = $request->validate([
            'is_active' => [
                'required',
                'boolean',
            ],
        ]);

        $active = (bool) $data['is_active'];

        if (
            !$active &&
            $user->id === $request->user()->id
        ) {
            throw ValidationException::withMessages([
                'is_active' =>
                    'لا يمكنك تعطيل حسابك الحالي.',
            ]);
        }

        if (
            !$active &&
            $user->is_active &&
            $user->hasRole('tenant_owner') &&
            $this->activeOwnerCount(
                $request->user()->tenant_id
            ) <= 1
        ) {
            throw ValidationException::withMessages([
                'is_active' =>
                    'لا يمكن تعطيل آخر مالك فعال للحساب.',
            ]);
        }

        $user->update([
            'is_active' => $active,
        ]);

        return response()->json([
            'message' => $active
                ? 'تم تفعيل المستخدم.'
                : 'تم تعطيل المستخدم.',
        ]);
    }

    private function tenantUser(
        Request $request,
        int $id
    ): User {

        $currentUser = $request->user();

        /*
         * الحساب الحالي تتم إدارته
         * من صفحة الملف الشخصي وليس Users.
         */
        if ($currentUser->id === $id) {
            abort(
                403,
                'لا يمكن إدارة حسابك من شاشة المستخدمين.'
            );
        }


        $user = User::query()

            ->where(
                'tenant_id',
                $currentUser->tenant_id
            )

            ->where(
                'is_system_admin',
                false
            )

            ->with('roles:id,name')

            ->findOrFail($id);


        /*
         * tenant_owner منصب محمي.
         *
         * لا يستطيع مستخدم أقل
         * مشاهدة أو تعديل مالك الحساب.
         */
        if (
            $user->hasRole('tenant_owner') &&
            !$currentUser->hasRole('tenant_owner')
        ) {
            abort(
                403,
                'غير مصرح لك بالوصول إلى مالك الحساب.'
            );
        }


        return $user;
    }

    private function validateRoles(
        int $tenantId,
        array $roleIds
    ) {
        $roleIds = collect($roleIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $roles = Role::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $roleIds)
            ->get();

        if (
            $roles->contains('name', 'tenant_owner') &&
            !auth()->user()->hasRole('tenant_owner')
        ) {
            throw ValidationException::withMessages([
                'roles' =>
                    'غير مصرح لك بإسناد دور مالك الحساب.',
            ]);
        }
        if ($roles->count() !== $roleIds->count()) {
            throw ValidationException::withMessages([
                'roles' => 'يوجد دور غير صالح.',
            ]);
        }

        return $roles;
    }

    private function activeOwnerCount(
        int $tenantId
    ): int {
        return User::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->role('tenant_owner')
            ->count();
    }
}