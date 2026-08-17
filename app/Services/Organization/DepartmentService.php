<?php

namespace App\Services\Organization;

use App\Models\Branch;
use App\Models\Department;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DepartmentService
{
    /*
    |--------------------------------------------------------------------------
    | Create Department
    |--------------------------------------------------------------------------
    */

    public function create(
        array $data,
        int $tenantId
    ): Department {
        return DB::transaction(
            function () use (
                $data,
                $tenantId
            ) {
                $branchId =
                    $data['branch_id']
                    ?? null;

                $parentId =
                    $data['parent_id']
                    ?? null;

                $isActive =
                    (bool) (
                        $data['is_active']
                        ?? true
                    );


                $this->validateBranch(
                    $branchId,
                    $tenantId,
                    $isActive
                );


                $this->validateParent(
                    parentId: $parentId,
                    branchId: $branchId,
                    tenantId: $tenantId,
                    departmentId: null,
                    departmentActive: $isActive
                );


                $data['tenant_id'] =
                    $tenantId;


                return Department::create(
                    $data
                );
            },
            3
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Update Department
    |--------------------------------------------------------------------------
    */

    public function update(
        Department $department,
        array $data
    ): Department {
        return DB::transaction(
            function () use (
                $department,
                $data
            ) {
                $department = Department::query()
                    ->whereKey(
                        $department->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();


                $newBranchId =
                    array_key_exists(
                        'branch_id',
                        $data
                    )
                        ? $data['branch_id']
                        : $department->branch_id;


                $newParentId =
                    array_key_exists(
                        'parent_id',
                        $data
                    )
                        ? $data['parent_id']
                        : $department->parent_id;


                $newIsActive =
                    array_key_exists(
                        'is_active',
                        $data
                    )
                        ? (bool) $data['is_active']
                        : (bool) $department->is_active;


                $this->validateBranch(
                    $newBranchId,
                    $department->tenant_id,
                    $newIsActive
                );


                $this->validateParent(
                    parentId: $newParentId,
                    branchId: $newBranchId,
                    tenantId: $department->tenant_id,
                    departmentId: $department->id,
                    departmentActive: $newIsActive
                );


                $descendantIds =
                    $this->getDescendantIds(
                        $department
                    );


                /*
                 * منع نقل الإدارة تحت أحد فروعها.
                 */
                if (
                    $newParentId &&
                    in_array(
                        (int) $newParentId,
                        $descendantIds,
                        true
                    )
                ) {
                    throw ValidationException::withMessages([
                        'parent_id' =>
                            'لا يمكن نقل الإدارة تحت أحد أقسامها الفرعية.',
                    ]);
                }


                /*
                 * لا يمكن إيقاف إدارة تحتوي على
                 * أقسام فرعية نشطة.
                 */
                if (
                    $department->is_active &&
                    !$newIsActive &&
                    !empty($descendantIds)
                ) {
                    $hasActiveDescendants =
                        Department::query()
                            ->whereIn(
                                'id',
                                $descendantIds
                            )
                            ->where(
                                'is_active',
                                true
                            )
                            ->exists();


                    if ($hasActiveDescendants) {
                        throw ValidationException::withMessages([
                            'is_active' =>
                                'لا يمكن إيقاف الإدارة لوجود أقسام فرعية نشطة.',
                        ]);
                    }
                }


                /*
                 * لا يمكن إيقاف إدارة لديها
                 * مسميات وظيفية نشطة.
                 */
                if (
                    $department->is_active &&
                    !$newIsActive &&
                    $department
                        ->jobTitles()
                        ->where(
                            'is_active',
                            true
                        )
                        ->exists()
                ) {
                    throw ValidationException::withMessages([
                        'is_active' =>
                            'لا يمكن إيقاف الإدارة لوجود مسميات وظيفية نشطة.',
                    ]);
                }


                $branchChanged =
                    (int) $department->branch_id !==
                    (int) $newBranchId;


                $department->update(
                    $data
                );


                /*
                 * عند نقل إدارة إلى فرع آخر،
                 * ننقل شجرتها الفرعية بالكامل.
                 */
                if (
                    $branchChanged &&
                    !empty($descendantIds)
                ) {
                    Department::query()
                        ->where(
                            'tenant_id',
                            $department->tenant_id
                        )
                        ->whereIn(
                            'id',
                            $descendantIds
                        )
                        ->update([
                            'branch_id' =>
                                $newBranchId,

                            'updated_at' =>
                                now(),
                        ]);
                }


                return $department->refresh();
            },
            3
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Department
    |--------------------------------------------------------------------------
    */

    public function delete(
        Department $department
    ): void {
        DB::transaction(
            function () use ($department) {
                $department = Department::query()
                    ->whereKey(
                        $department->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();


                if (
                    $department
                        ->children()
                        ->exists()
                ) {
                    throw ValidationException::withMessages([
                        'department' =>
                            'لا يمكن أرشفة الإدارة لوجود أقسام فرعية مرتبطة بها.',
                    ]);
                }


                if (
                    $department
                        ->jobTitles()
                        ->exists()
                ) {
                    throw ValidationException::withMessages([
                        'department' =>
                            'لا يمكن أرشفة الإدارة لوجود مسميات وظيفية مرتبطة بها.',
                    ]);
                }


                $department->delete();
            },
            3
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Branch
    |--------------------------------------------------------------------------
    */

    private function validateBranch(
        ?int $branchId,
        int $tenantId,
        bool $departmentActive
    ): void {
        if (!$branchId) {
            return;
        }


        $branch = Branch::query()
            ->whereKey(
                $branchId
            )
            ->where(
                'tenant_id',
                $tenantId
            )
            ->first();


        if (!$branch) {
            throw ValidationException::withMessages([
                'branch_id' =>
                    'الفرع المحدد غير موجود.',
            ]);
        }


        if (
            $departmentActive &&
            !$branch->is_active
        ) {
            throw ValidationException::withMessages([
                'branch_id' =>
                    'لا يمكن إضافة إدارة نشطة داخل فرع غير نشط.',
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Parent
    |--------------------------------------------------------------------------
    */

    private function validateParent(
        ?int $parentId,
        ?int $branchId,
        int $tenantId,
        ?int $departmentId,
        bool $departmentActive
    ): void {
        if (!$parentId) {
            return;
        }


        if (
            $departmentId &&
            (int) $parentId ===
            (int) $departmentId
        ) {
            throw ValidationException::withMessages([
                'parent_id' =>
                    'لا يمكن جعل الإدارة تابعة لنفسها.',
            ]);
        }


        $parent = Department::query()
            ->whereKey(
                $parentId
            )
            ->where(
                'tenant_id',
                $tenantId
            )
            ->first();


        if (!$parent) {
            throw ValidationException::withMessages([
                'parent_id' =>
                    'الإدارة الرئيسية المحددة غير موجودة.',
            ]);
        }


        $parentBranchId =
            $parent->branch_id
                ? (int) $parent->branch_id
                : null;


        $departmentBranchId =
            $branchId
                ? (int) $branchId
                : null;


        if (
            $parentBranchId !==
            $departmentBranchId
        ) {
            throw ValidationException::withMessages([
                'parent_id' =>
                    'يجب أن تكون الإدارة الرئيسية والفرعية داخل نفس الفرع.',
            ]);
        }


        if (
            $departmentActive &&
            !$parent->is_active
        ) {
            throw ValidationException::withMessages([
                'parent_id' =>
                    'لا يمكن إضافة إدارة نشطة تحت إدارة غير نشطة.',
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Get All Descendants
    |--------------------------------------------------------------------------
    */

    private function getDescendantIds(
        Department $department
    ): array {
        $descendantIds = [];

        $pendingIds = [
            $department->id,
        ];


        while (!empty($pendingIds)) {
            $childrenIds = Department::query()
                ->where(
                    'tenant_id',
                    $department->tenant_id
                )
                ->whereIn(
                    'parent_id',
                    $pendingIds
                )
                ->pluck('id')
                ->map(
                    fn ($id) =>
                        (int) $id
                )
                ->all();


            $childrenIds = array_values(
                array_diff(
                    $childrenIds,
                    $descendantIds,
                    [
                        (int) $department->id,
                    ]
                )
            );


            if (empty($childrenIds)) {
                break;
            }


            $descendantIds = array_values(
                array_unique([
                    ...$descendantIds,
                    ...$childrenIds,
                ])
            );


            $pendingIds =
                $childrenIds;
        }


        return $descendantIds;
    }
}