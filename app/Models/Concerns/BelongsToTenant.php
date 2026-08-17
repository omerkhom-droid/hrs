<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    /*
    |--------------------------------------------------------------------------
    | Tenant Isolation
    |--------------------------------------------------------------------------
    */

    public static function bootBelongsToTenant(): void
    {
        /*
         * عزل استعلامات مستخدمي الشركات.
         * مدير المنصة يستطيع رؤية جميع الشركات.
         */
        static::addGlobalScope(
            'tenant',
            function (Builder $builder) {
                $user = auth()->user();

                if (!$user) {
                    return;
                }

                if ($user->is_system_admin) {
                    return;
                }

                /*
                 * منع أي مستخدم غير مربوط بشركة
                 * من رؤية بيانات جميع الشركات.
                 */
                if (!$user->tenant_id) {
                    $builder->whereRaw('1 = 0');

                    return;
                }

                $builder->where(
                    $builder
                        ->getModel()
                        ->qualifyColumn('tenant_id'),
                    $user->tenant_id
                );
            }
        );


        /*
         * وضع tenant_id تلقائيًا عند الإنشاء.
         */
        static::creating(
            function ($model) {
                $user = auth()->user();

                if (!$user) {
                    return;
                }

                if ($user->is_system_admin) {
                    /*
                     * مدير المنصة يجب أن يرسل
                     * tenant_id صراحة عند الإنشاء.
                     */
                    return;
                }

                if (!$user->tenant_id) {
                    throw new AuthorizationException(
                        'المستخدم غير مرتبط بشركة.'
                    );
                }

                if (
                    $model->tenant_id &&
                    (int) $model->tenant_id !==
                    (int) $user->tenant_id
                ) {
                    throw new AuthorizationException(
                        'لا يمكنك إنشاء بيانات لشركة أخرى.'
                    );
                }

                $model->tenant_id =
                    $user->tenant_id;
            }
        );


        /*
         * منع نقل السجل من شركة إلى شركة أخرى.
         */
        static::updating(
            function ($model) {
                $user = auth()->user();

                if (
                    !$user ||
                    $user->is_system_admin
                ) {
                    return;
                }

                if (
                    !$user->tenant_id ||
                    (int) $model->tenant_id !==
                    (int) $user->tenant_id
                ) {
                    throw new AuthorizationException(
                        'لا يمكنك تعديل بيانات شركة أخرى.'
                    );
                }

                if (
                    $model->isDirty('tenant_id')
                ) {
                    throw new AuthorizationException(
                        'لا يمكن تغيير الشركة المرتبطة بالسجل.'
                    );
                }
            }
        );


        /*
         * منع حذف سجل تابع لشركة أخرى.
         */
        static::deleting(
            function ($model) {
                $user = auth()->user();

                if (
                    !$user ||
                    $user->is_system_admin
                ) {
                    return;
                }

                if (
                    !$user->tenant_id ||
                    (int) $model->tenant_id !==
                    (int) $user->tenant_id
                ) {
                    throw new AuthorizationException(
                        'لا يمكنك حذف بيانات شركة أخرى.'
                    );
                }
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Tenant Relationship
    |--------------------------------------------------------------------------
    */

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            Tenant::class
        );
    }
}