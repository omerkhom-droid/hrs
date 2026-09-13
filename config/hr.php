<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Leave Management
    |--------------------------------------------------------------------------
    */

    'leave' => [

        /*
         * أيام نهاية الأسبوع وفق ISO:
         *
         * 1 = الاثنين
         * 2 = الثلاثاء
         * 3 = الأربعاء
         * 4 = الخميس
         * 5 = الجمعة
         * 6 = السبت
         * 7 = الأحد
         */
        'weekend_days' => [
            5,
            6,
        ],

        /*
         * بداية سنة الإجازات.
         */
        'year_start_month' => 1,

        /*
         * عدد المنازل العشرية المستخدمة في الأرصدة.
         */
        'balance_precision' => 2,

        /*
         * السماح بإنشاء طلبات إجازة بأثر رجعي.
         * يفضل إبقاؤه معطلاً للخدمة الذاتية.
         */
        'allow_backdated_requests' => false,

        /*
         * عدد مستويات الاعتماد الافتراضية.
         */
        'default_approval_levels' => 1,

        /*
         * الحد الأعلى لعدد أيام الطلب الواحد
         * عند عدم تحديد حد داخل نوع الإجازة.
         */
        'maximum_request_days' => 365,

        /*
         * إعدادات المرفقات.
         */
        'attachments' => [

            'disk' =>
                'public',

            'directory' =>
                'leave-requests',

            /*
             * الحجم بالكيلوبايت.
             * القيمة الحالية تعادل 5MB.
             */
            'maximum_size' =>
                5120,

            'allowed_extensions' => [
                'pdf',
                'jpg',
                'jpeg',
                'png',
                'webp',
            ],

            'allowed_mime_types' => [
                'application/pdf',
                'image/jpeg',
                'image/png',
                'image/webp',
            ],
        ],

        /*
         * إعدادات الرصيد.
         */
        'balance' => [

            /*
             * إنشاء رصيد الموظف تلقائيًا
             * عند أول طلب إجازة.
             */
            'create_automatically' =>
                true,

            /*
             * مزامنة الاستحقاق الشهري
             * عند فتح الرصيد أو استخدامه.
             */
            'synchronize_accrual' =>
                true,

            /*
             * السماح بالتسويات اليدوية من الإدارة.
             */
            'allow_manual_adjustments' =>
                true,
        ],

        /*
         * حالات الطلب التي تمنع تداخل الإجازات.
         */
        'overlap_statuses' => [
            'pending',
            'approved',
        ],

        /*
         * حالة الطلب الافتراضية.
         */
        'default_status' =>
            'draft',

        /*
         * تنسيق عرض التاريخ داخل الواجهة.
         */
        'date_format' =>
            'd/m/Y',

        /*
         * تنسيق التاريخ المستخدم في قاعدة البيانات.
         */
        'database_date_format' =>
            'Y-m-d',
    ],

];