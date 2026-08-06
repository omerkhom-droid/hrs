<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class SaasPlansSeeder extends Seeder
{
    public function run(): void
    {
        $features = [
            ['code' => 'organization', 'name_ar' => 'الهيكل التنظيمي'],
            ['code' => 'employees', 'name_ar' => 'إدارة الموظفين'],
            ['code' => 'attendance', 'name_ar' => 'الحضور والانصراف'],
            ['code' => 'leaves', 'name_ar' => 'الإجازات'],
            ['code' => 'payroll', 'name_ar' => 'مسير الرواتب'],
            ['code' => 'recruitment', 'name_ar' => 'التوظيف'],
            ['code' => 'performance', 'name_ar' => 'إدارة الأداء'],
            ['code' => 'training', 'name_ar' => 'التدريب'],
            ['code' => 'self_service', 'name_ar' => 'الخدمة الذاتية'],
            ['code' => 'reports', 'name_ar' => 'التقارير'],
            ['code' => 'api_access', 'name_ar' => 'الوصول إلى API'],
            ['code' => 'audit_logs', 'name_ar' => 'سجل العمليات'],
        ];

        foreach ($features as $feature) {
            Feature::updateOrCreate(
                ['code' => $feature['code']],
                [
                    'name_ar' => $feature['name_ar'],
                    'type' => 'boolean',
                    'is_active' => true,
                ]
            );
        }

        $plans = [
            [
                'code' => 'starter',
                'name_ar' => 'البداية',
                'trial_days' => 15,
                'users_limit' => 10,
                'employees_limit' => 100,
                'companies_limit' => 1,
                'branches_limit' => 3,
                'storage_limit_mb' => 1024,
                'features' => [
                    'organization',
                    'employees',
                    'attendance',
                    'leaves',
                    'self_service',
                    'reports',
                ],
            ],
            [
                'code' => 'business',
                'name_ar' => 'الأعمال',
                'trial_days' => 15,
                'users_limit' => 50,
                'employees_limit' => 1000,
                'companies_limit' => 5,
                'branches_limit' => 20,
                'storage_limit_mb' => 10240,
                'features' => [
                    'organization',
                    'employees',
                    'attendance',
                    'leaves',
                    'payroll',
                    'recruitment',
                    'performance',
                    'training',
                    'self_service',
                    'reports',
                    'audit_logs',
                ],
            ],
            [
                'code' => 'enterprise',
                'name_ar' => 'الشركات الكبرى',
                'trial_days' => 30,
                'users_limit' => null,
                'employees_limit' => null,
                'companies_limit' => null,
                'branches_limit' => null,
                'storage_limit_mb' => null,
                'features' => array_column($features, 'code'),
            ],
        ];

        foreach ($plans as $planData) {
            $featureCodes = $planData['features'];
            unset($planData['features']);

            $plan = Plan::updateOrCreate(
                ['code' => $planData['code']],
                $planData + [
                    'monthly_price' => 0,
                    'yearly_price' => 0,
                    'is_active' => true,
                ]
            );

            $featureIds = Feature::whereIn('code', $featureCodes)->pluck('id');

            $syncData = $featureIds
                ->mapWithKeys(fn ($id) => [
                    $id => ['is_enabled' => true],
                ])
                ->all();

            $plan->features()->sync($syncData);
        }
    }
}