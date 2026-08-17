<?php

namespace App\Services\Organization;

use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BranchService
{
    /*
    |--------------------------------------------------------------------------
    | Create Branch
    |--------------------------------------------------------------------------
    */

    public function create(
        array $data,
        int $tenantId
    ): Branch {
        return DB::transaction(
            function () use (
                $data,
                $tenantId
            ) {
                /*
                 * قفل فروع الشركة لتجنب إنشاء
                 * أكثر من فرع رئيسي في نفس الوقت.
                 */
                $branchesQuery = Branch::query()
                    ->withoutGlobalScope('tenant')
                    ->where(
                        'tenant_id',
                        $tenantId
                    )
                    ->lockForUpdate();


                $hasBranches =
                    $branchesQuery
                        ->clone()
                        ->exists();



                /*
                 * أول فرع للشركة يصبح رئيسيًا تلقائيًا.
                 */
                if (!$hasBranches) {
                    $data['is_main'] =
                        true;

                    $data['is_active'] =
                        true;
                }


                /*
                 * إذا تم تحديد الفرع كرئيسي،
                 * نلغي الرئيسي من بقية الفروع.
                 */
                if (
                    (bool) (
                        $data['is_main']
                        ?? false
                    )
                ) {
                    Branch::query()
                        ->withoutGlobalScope('tenant')
                        ->where(
                            'tenant_id',
                            $tenantId
                        )
                        ->where(
                            'is_main',
                            true
                        )
                        ->update([
                            'is_main' =>
                                false,
                        ]);

                    $data['is_active'] =
                        true;
                }


                $data['tenant_id'] =
                    $tenantId;


                return Branch::create(
                    $data
                );
            },
            3
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Update Branch
    |--------------------------------------------------------------------------
    */

    public function update(
        Branch $branch,
        array $data
    ): Branch {
        return DB::transaction(
            function () use (
                $branch,
                $data
            ) {
                $branch = Branch::query()
                    ->whereKey(
                        $branch->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();


                /*
                 * لا نسمح بإلغاء الفرع الرئيسي
                 * بدون اختيار فرع رئيسي بديل.
                 */
                if (
                    $branch->is_main &&
                    !(
                        (bool) (
                            $data['is_main']
                            ?? false
                        )
                    )
                ) {
                    throw ValidationException::withMessages([
                        'is_main' =>
                            'لا يمكن إلغاء الفرع الرئيسي. اختر فرعًا آخر كرئيسي أولًا.',
                    ]);
                }


                /*
                 * لا يمكن إيقاف الفرع الرئيسي.
                 */
                if (
                    $branch->is_main &&
                    !(
                        (bool) (
                            $data['is_active']
                            ?? false
                        )
                    )
                ) {
                    throw ValidationException::withMessages([
                        'is_active' =>
                            'لا يمكن إيقاف الفرع الرئيسي.',
                    ]);
                }


                /*
                 * تحويل هذا الفرع إلى الرئيسي.
                 */
                if (
                    !$branch->is_main &&
                    (
                        (bool) (
                            $data['is_main']
                            ?? false
                        )
                    )
                ) {
                    Branch::query()
                        ->withoutGlobalScope('tenant')
                        ->where(
                            'tenant_id',
                            $branch->tenant_id
                        )
                        ->where(
                            'id',
                            '!=',
                            $branch->id
                        )
                        ->where(
                            'is_main',
                            true
                        )
                        ->update([
                            'is_main' =>
                                false,
                        ]);

                    $data['is_active'] =
                        true;
                }


                $branch->update(
                    $data
                );


                return $branch->refresh();
            },
            3
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Branch
    |--------------------------------------------------------------------------
    */

    public function delete(
        Branch $branch
    ): void {
        DB::transaction(
            function () use ($branch) {
                $branch = Branch::query()
                    ->whereKey(
                        $branch->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();


                if ($branch->is_main) {
                    throw ValidationException::withMessages([
                        'branch' =>
                            'لا يمكن أرشفة الفرع الرئيسي.',
                    ]);
                }


                if (
                    $branch
                        ->departments()
                        ->exists()
                ) {
                    throw ValidationException::withMessages([
                        'branch' =>
                            'لا يمكن أرشفة الفرع لوجود إدارات مرتبطة به.',
                    ]);
                }


                if (
                    $branch
                        ->workLocations()
                        ->exists()
                ) {
                    throw ValidationException::withMessages([
                        'branch' =>
                            'لا يمكن أرشفة الفرع لوجود مواقع عمل مرتبطة به.',
                    ]);
                }


                $branch->delete();
            },
            3
        );
    }
}