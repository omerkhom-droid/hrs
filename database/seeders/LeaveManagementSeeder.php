<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeaveManagementSeeder extends Seeder
{
    public function run(): void
    {
        $createdCount = 0;

        Tenant::query()
            ->select([
                'id',
                'name',
            ])
            ->orderBy('id')
            ->chunkById(
                100,
                function ($tenants) use (
                    &$createdCount
                ) {
                    foreach ($tenants as $tenant) {
                        DB::transaction(
                            function () use (
                                $tenant,
                                &$createdCount
                            ) {
                                foreach (
                                    $this->defaultLeaveTypes()
                                    as $data
                                ) {
                                    $existing =
                                        LeaveType::withoutGlobalScopes()
                                            ->withTrashed()
                                            ->where(
                                                'tenant_id',
                                                $tenant->id
                                            )
                                            ->where(
                                                'code',
                                                $data['code']
                                            )
                                            ->first();

                                    /*
                                     * لا نعدل الأنواع الموجودة حتى لا
                                     * نفقد إعدادات الشركة المخصصة.
                                     */
                                    if ($existing) {
                                        continue;
                                    }

                                    LeaveType::withoutGlobalScopes()
                                        ->create(
                                            array_merge(
                                                $data,
                                                [
                                                    'tenant_id' =>
                                                        $tenant->id,
                                                ]
                                            )
                                        );

                                    $createdCount++;
                                }
                            }
                        );
                    }
                }
            );

        $this->command?->info(
            "تم إنشاء {$createdCount} نوع إجازة افتراضي."
        );
    }

    private function defaultLeaveTypes(): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | الإجازة السنوية
            |--------------------------------------------------------------------------
            */

            [
                'code' =>
                    'ANNUAL',

                'name' =>
                    'الإجازة السنوية',

                'name_en' =>
                    'Annual Leave',

                'unit' =>
                    'day',

                'payment_type' =>
                    'paid',

                'paid_percentage' =>
                    100,

                'default_entitlement' =>
                    21,

                'accrual_method' =>
                    'monthly',

                'requires_balance' =>
                    true,

                'allow_negative_balance' =>
                    false,

                'allow_during_probation' =>
                    false,

                'allow_half_day' =>
                    true,

                'requires_attachment' =>
                    false,

                'minimum_notice_days' =>
                    3,

                'maximum_consecutive_days' =>
                    null,

                'allow_carry_forward' =>
                    true,

                'maximum_carry_forward' =>
                    5,

                'gender' =>
                    'all',

                'sort_order' =>
                    10,

                'is_active' =>
                    true,

                'metadata' => [
                    'source' =>
                        'system_default',

                    'editable' =>
                        true,
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | الإجازة المرضية
            |--------------------------------------------------------------------------
            */

            [
                'code' =>
                    'SICK',

                'name' =>
                    'الإجازة المرضية',

                'name_en' =>
                    'Sick Leave',

                'unit' =>
                    'day',

                'payment_type' =>
                    'paid',

                'paid_percentage' =>
                    100,

                'default_entitlement' =>
                    0,

                'accrual_method' =>
                    'none',

                /*
                 * لا يخصم من رصيد ثابت لأن بعض الشركات
                 * تطبق شرائح مختلفة للإجازة المرضية.
                 */
                'requires_balance' =>
                    false,

                'allow_negative_balance' =>
                    false,

                'allow_during_probation' =>
                    true,

                'allow_half_day' =>
                    true,

                'requires_attachment' =>
                    true,

                'minimum_notice_days' =>
                    0,

                'maximum_consecutive_days' =>
                    null,

                'allow_carry_forward' =>
                    false,

                'maximum_carry_forward' =>
                    0,

                'gender' =>
                    'all',

                'sort_order' =>
                    20,

                'is_active' =>
                    true,

                'metadata' => [
                    'source' =>
                        'system_default',

                    'editable' =>
                        true,

                    'requires_medical_review' =>
                        true,
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | الإجازة الاضطرارية
            |--------------------------------------------------------------------------
            */

            [
                'code' =>
                    'EMERGENCY',

                'name' =>
                    'الإجازة الاضطرارية',

                'name_en' =>
                    'Emergency Leave',

                'unit' =>
                    'day',

                'payment_type' =>
                    'paid',

                'paid_percentage' =>
                    100,

                'default_entitlement' =>
                    5,

                'accrual_method' =>
                    'annual',

                'requires_balance' =>
                    true,

                'allow_negative_balance' =>
                    false,

                'allow_during_probation' =>
                    true,

                'allow_half_day' =>
                    true,

                'requires_attachment' =>
                    false,

                'minimum_notice_days' =>
                    0,

                'maximum_consecutive_days' =>
                    3,

                'allow_carry_forward' =>
                    false,

                'maximum_carry_forward' =>
                    0,

                'gender' =>
                    'all',

                'sort_order' =>
                    30,

                'is_active' =>
                    true,

                'metadata' => [
                    'source' =>
                        'system_default',

                    'editable' =>
                        true,
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | الإجازة بدون راتب
            |--------------------------------------------------------------------------
            */

            [
                'code' =>
                    'UNPAID',

                'name' =>
                    'إجازة بدون راتب',

                'name_en' =>
                    'Unpaid Leave',

                'unit' =>
                    'day',

                'payment_type' =>
                    'unpaid',

                'paid_percentage' =>
                    0,

                'default_entitlement' =>
                    0,

                'accrual_method' =>
                    'none',

                'requires_balance' =>
                    false,

                'allow_negative_balance' =>
                    false,

                'allow_during_probation' =>
                    false,

                'allow_half_day' =>
                    true,

                'requires_attachment' =>
                    false,

                'minimum_notice_days' =>
                    7,

                'maximum_consecutive_days' =>
                    null,

                'allow_carry_forward' =>
                    false,

                'maximum_carry_forward' =>
                    0,

                'gender' =>
                    'all',

                'sort_order' =>
                    40,

                'is_active' =>
                    true,

                'metadata' => [
                    'source' =>
                        'system_default',

                    'editable' =>
                        true,

                    'affects_payroll' =>
                        true,
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | إجازة أخرى
            |--------------------------------------------------------------------------
            */

            [
                'code' =>
                    'OTHER',

                'name' =>
                    'إجازة أخرى',

                'name_en' =>
                    'Other Leave',

                'unit' =>
                    'day',

                'payment_type' =>
                    'unpaid',

                'paid_percentage' =>
                    0,

                'default_entitlement' =>
                    0,

                'accrual_method' =>
                    'none',

                'requires_balance' =>
                    false,

                'allow_negative_balance' =>
                    false,

                'allow_during_probation' =>
                    true,

                'allow_half_day' =>
                    true,

                'requires_attachment' =>
                    false,

                'minimum_notice_days' =>
                    0,

                'maximum_consecutive_days' =>
                    null,

                'allow_carry_forward' =>
                    false,

                'maximum_carry_forward' =>
                    0,

                'gender' =>
                    'all',

                'sort_order' =>
                    100,

                'is_active' =>
                    true,

                'metadata' => [
                    'source' =>
                        'system_default',

                    'editable' =>
                        true,
                ],
            ],
        ];
    }
}