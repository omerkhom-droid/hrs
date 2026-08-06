<?php

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Seeder;

class FeatureSeeder extends Seeder
{
    public function run(): void
    {
        $features = [

            // HR Core
            [
                'code' => 'hr.employees',
                'name' => 'إدارة الموظفين',
                'module' => 'hr',
            ],
            [
                'code' => 'hr.contracts',
                'name' => 'عقود الموظفين',
                'module' => 'hr',
            ],
            [
                'code' => 'hr.documents',
                'name' => 'وثائق الموظفين',
                'module' => 'hr',
            ],

            // Organization
            [
                'code' => 'organization.multi_company',
                'name' => 'تعدد الشركات',
                'module' => 'organization',
            ],
            [
                'code' => 'organization.branches',
                'name' => 'إدارة الفروع',
                'module' => 'organization',
            ],
            [
                'code' => 'organization.structure',
                'name' => 'الأقسام والمسميات الوظيفية',
                'module' => 'organization',
            ],

            // Attendance
            [
                'code' => 'attendance.basic',
                'name' => 'الحضور والانصراف',
                'module' => 'attendance',
            ],
            [
                'code' => 'attendance.shifts',
                'name' => 'إدارة الورديات',
                'module' => 'attendance',
            ],
            [
                'code' => 'attendance.overtime',
                'name' => 'العمل الإضافي',
                'module' => 'attendance',
            ],

            // Leaves
            [
                'code' => 'leave.management',
                'name' => 'الإجازات والأرصدة',
                'module' => 'leave',
            ],

            // Payroll
            [
                'code' => 'payroll.management',
                'name' => 'الرواتب ومسير الرواتب',
                'module' => 'payroll',
            ],

            // Recruitment
            [
                'code' => 'recruitment.management',
                'name' => 'التوظيف والمرشحين',
                'module' => 'recruitment',
            ],

            // Performance
            [
                'code' => 'performance.management',
                'name' => 'تقييم الأداء',
                'module' => 'performance',
            ],

            // Training
            [
                'code' => 'training.management',
                'name' => 'التدريب والتطوير',
                'module' => 'training',
            ],

            // Self Service
            [
                'code' => 'self_service.portal',
                'name' => 'الخدمة الذاتية للموظف',
                'module' => 'self_service',
            ],

            // Workflow
            [
                'code' => 'workflow.approvals',
                'name' => 'مسارات الموافقات',
                'module' => 'workflow',
            ],

            // Reports
            [
                'code' => 'reports.advanced',
                'name' => 'التقارير المتقدمة',
                'module' => 'reports',
            ],

            // Integration
            [
                'code' => 'integration.api',
                'name' => 'API',
                'module' => 'integration',
            ],
            [
                'code' => 'integration.webhooks',
                'name' => 'Webhooks',
                'module' => 'integration',
            ],

            // Audit
            [
                'code' => 'audit.advanced',
                'name' => 'سجل التدقيق المتقدم',
                'module' => 'audit',
            ],
        ];

        foreach ($features as $feature) {

            Feature::updateOrCreate(
                [
                    'code' => $feature['code'],
                ],
                [
                    'name' => $feature['name'],
                    'module' => $feature['module'],
                    'type' => 'boolean',
                    'description' => null,
                    'is_active' => true,
                ]
            );
        }
    }
}