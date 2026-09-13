<?php

namespace App\Http\Requests\Tenant;

use App\Models\PayrollAdjustment;
use Illuminate\Validation\Validator;

class UpdatePayrollAdjustmentRequest extends StorePayrollAdjustmentRequest
{
    public function authorize(): bool
    {
        if (!parent::authorize()) {
            return false;
        }

        $adjustment =
            $this->payrollAdjustment();

        if (!$adjustment) {
            return false;
        }

        return
            (int) $adjustment->tenant_id ===
            (int) $this->user()->tenant_id;
    }


    public function after(): array
    {
        return array_merge(
            parent::after(),
            [
                function (
                    Validator $validator
                ): void {
                    $adjustment =
                        $this->payrollAdjustment();

                    if (!$adjustment) {
                        $validator
                            ->errors()
                            ->add(
                                'adjustment',
                                'تسوية الرواتب غير موجودة.'
                            );

                        return;
                    }

                    /*
                     * لا يسمح بتعديل التسوية
                     * إلا عندما تكون مسودة.
                     */
                    if (
                        $adjustment->status !==
                        PayrollAdjustment::STATUS_DRAFT
                    ) {
                        $validator
                            ->errors()
                            ->add(
                                'adjustment',
                                'لا يمكن تعديل التسوية بعد إرسالها للاعتماد أو تطبيقها على الرواتب.'
                            );
                    }

                    /*
                     * منع تعديل تسوية مرتبطة
                     * ببند راتب تم احتسابه.
                     */
                    if (
                        $adjustment
                            ->applied_payroll_item_id
                        !== null
                    ) {
                        $validator
                            ->errors()
                            ->add(
                                'adjustment',
                                'لا يمكن تعديل تسوية تم تطبيقها على تشغيل الرواتب.'
                            );
                    }
                },
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | الحصول على التسوية من Route Model Binding
    |--------------------------------------------------------------------------
    */

    public function payrollAdjustment(): ?PayrollAdjustment
    {
        $adjustment =
            $this->route(
                'payrollAdjustment'
            )
            ??
            $this->route(
                'adjustment'
            );

        if (
            $adjustment instanceof
            PayrollAdjustment
        ) {
            return $adjustment;
        }

        if (
            is_numeric($adjustment)
        ) {
            return PayrollAdjustment::withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $this->user()->tenant_id
                )
                ->whereKey(
                    (int) $adjustment
                )
                ->whereNull(
                    'deleted_at'
                )
                ->first();
        }

        return null;
    }
}