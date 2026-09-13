@php
    use Illuminate\Support\Carbon;

    /*
    |--------------------------------------------------------------------------
    | دعم المصفوفة أو الموديل
    |--------------------------------------------------------------------------
    */

    $item =
        $payslip ??
        $payrollItem ??
        [];

    $company =
        $tenant ??
        auth()->user()?->tenant;

    $employee =
        data_get($item, 'employee', []);

    $run =
        data_get($item, 'payroll_run') ??
        data_get($item, 'payrollRun') ??
        data_get($item, 'run', []);

    $period =
        data_get($item, 'payroll_period') ??
        data_get($item, 'payrollPeriod') ??
        data_get($run, 'period', []);

    $components = collect(
        data_get($item, 'components', [])
    );

    $earnings = $components
        ->where('type', 'earning')
        ->values();

    $deductions = $components
        ->where('type', 'deduction')
        ->values();

    $currency =
        data_get($item, 'currency_code') ??
        data_get($run, 'currency_code') ??
        $company?->currency_code ??
        'SAR';

    $employeeName =
        data_get($employee, 'full_name') ??
        data_get($employee, 'name') ??
        data_get($item, 'employee_name') ??
        '-';

    $employeeNumber =
        data_get($employee, 'employee_number') ??
        data_get($item, 'employee_number') ??
        '-';

    $departmentName =
        data_get($employee, 'department.name') ??
        data_get($employee, 'department_name') ??
        '-';

    $jobTitleName =
        data_get($employee, 'job_title.name') ??
        data_get($employee, 'jobTitle.name') ??
        data_get($employee, 'job_title_name') ??
        '-';

    $branchName =
        data_get($employee, 'branch.name') ??
        data_get($employee, 'branch_name') ??
        '-';

    $formatMoney = function ($value) use ($currency) {
        return number_format(
            (float) ($value ?? 0),
            2
        ) . ' ' . $currency;
    };

    $formatDate = function ($value) {
        if (!$value) {
            return '-';
        }

        try {
            return Carbon::parse($value)
                ->format('d/m/Y');
        } catch (Throwable $exception) {
            return $value;
        }
    };

    $statusLabels = [
        'pending' =>
            'بانتظار الحساب',

        'calculated' =>
            'محسوبة',

        'exception' =>
            'بها مشكلة',

        'approved' =>
            'معتمدة',

        'paid' =>
            'مدفوعة',
    ];

    $status =
        data_get($item, 'status', 'pending');

    $statusLabel =
        $statusLabels[$status] ??
        $status;

    $companyTimezone =
        $company?->timezone ??
        config('app.timezone', 'Asia/Riyadh');

    $printedAt = now()
        ->timezone($companyTimezone)
        ->format('d/m/Y h:i A');
@endphp

<!DOCTYPE html>

<html
    lang="ar"
    dir="rtl"
>

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        قسيمة راتب - {{ $employeeName }}
    </title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px;
            background: #eef2f7;
            color: #172033;
            font-family:
                Tahoma,
                Arial,
                sans-serif;
            font-size: 13px;
        }

        .print-actions {
            max-width: 210mm;
            margin: 0 auto 16px;
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }

        .print-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 9px;
            padding: 10px 18px;
            background: #1769ff;
            color: #fff;
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
        }

        .print-button.secondary {
            background: #fff;
            color: #334155;
            border: 1px solid #cbd5e1;
        }

        .payslip {
            width: 100%;
            max-width: 210mm;
            min-height: 280mm;
            margin: 0 auto;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 12px 35px rgba(15, 23, 42, .12);
            overflow: hidden;
        }

        .header {
            padding: 28px 32px;
            background:
                linear-gradient(
                    135deg,
                    #0b1f45,
                    #1769ff
                );
            color: #fff;
        }

        .header-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
        }

        .company-name {
            margin: 0 0 8px;
            font-size: 23px;
            font-weight: 800;
        }

        .company-meta {
            color: rgba(255, 255, 255, .78);
            line-height: 1.8;
        }

        .document-title {
            text-align: left;
        }

        .document-title h1 {
            margin: 0 0 7px;
            font-size: 25px;
        }

        .document-number {
            color: rgba(255, 255, 255, .78);
            direction: ltr;
        }

        .content {
            padding: 28px 32px;
        }

        .status-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 22px;
        }

        .period-title {
            font-size: 17px;
            font-weight: 800;
        }

        .status-badge {
            display: inline-flex;
            border-radius: 999px;
            padding: 7px 15px;
            background: #dcfce7;
            color: #15803d;
            font-weight: 800;
        }

        .section {
            margin-bottom: 22px;
            border: 1px solid #dfe6ef;
            border-radius: 12px;
            overflow: hidden;
        }

        .section-title {
            padding: 12px 16px;
            background: #f7f9fc;
            border-bottom: 1px solid #dfe6ef;
            font-size: 14px;
            font-weight: 800;
        }

        .section-body {
            padding: 17px;
        }

        .details-grid {
            display: grid;
            grid-template-columns:
                repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .detail-label {
            margin-bottom: 6px;
            color: #64748b;
            font-size: 11px;
        }

        .detail-value {
            color: #172033;
            font-size: 13px;
            font-weight: 700;
        }

        .summary-grid {
            display: grid;
            grid-template-columns:
                repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 22px;
        }

        .summary-card {
            padding: 16px;
            border-radius: 11px;
            background: #f7f9fc;
            border: 1px solid #e2e8f0;
        }

        .summary-card.earning {
            background: #ecfdf5;
            border-color: #bbf7d0;
        }

        .summary-card.deduction {
            background: #fff1f2;
            border-color: #fecdd3;
        }

        .summary-card.net {
            background: #eff6ff;
            border-color: #bfdbfe;
        }

        .summary-label {
            margin-bottom: 8px;
            color: #64748b;
            font-size: 11px;
        }

        .summary-value {
            direction: ltr;
            display: inline-block;
            font-size: 15px;
            font-weight: 800;
        }

        .earnings .summary-value {
            color: #047857;
        }

        .deduction .summary-value {
            color: #be123c;
        }

        .net .summary-value {
            color: #1d4ed8;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 11px 12px;
            border-bottom: 1px solid #e2e8f0;
            text-align: right;
        }

        th {
            background: #f8fafc;
            color: #475569;
            font-size: 11px;
            font-weight: 800;
        }

        tbody tr:last-child td {
            border-bottom: 0;
        }

        .component-name {
            font-weight: 700;
        }

        .component-code {
            margin-top: 3px;
            color: #64748b;
            font-size: 10px;
        }

        .amount {
            direction: ltr;
            display: inline-block;
            font-weight: 800;
            white-space: nowrap;
        }

        .earning-amount {
            color: #047857;
        }

        .deduction-amount {
            color: #be123c;
        }

        .empty-row {
            padding: 25px;
            text-align: center;
            color: #64748b;
        }

        .attendance-grid {
            display: grid;
            grid-template-columns:
                repeat(5, minmax(0, 1fr));
            gap: 12px;
        }

        .attendance-item {
            padding: 13px;
            border-radius: 9px;
            background: #f8fafc;
            text-align: center;
        }

        .attendance-value {
            margin-top: 7px;
            font-size: 15px;
            font-weight: 800;
        }

        .notes {
            min-height: 50px;
            line-height: 1.9;
            white-space: pre-wrap;
        }

        .signatures {
            display: grid;
            grid-template-columns:
                repeat(3, minmax(0, 1fr));
            gap: 30px;
            margin-top: 45px;
            padding: 0 15px;
        }

        .signature {
            text-align: center;
        }

        .signature-line {
            margin-top: 48px;
            border-top: 1px solid #64748b;
            padding-top: 8px;
            color: #64748b;
        }

        .footer {
            margin-top: 30px;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            gap: 20px;
            color: #64748b;
            font-size: 10px;
        }

        @page {
            size: A4;
            margin: 10mm;
        }

        @media print {
            body {
                padding: 0;
                background: #fff;
            }

            .print-actions {
                display: none !important;
            }

            .payslip {
                max-width: none;
                min-height: auto;
                border-radius: 0;
                box-shadow: none;
            }

            .header {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .summary-card,
            .status-badge,
            th {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .section,
            .summary-card {
                break-inside: avoid;
            }
        }

        @media screen and (max-width: 768px) {
            body {
                padding: 12px;
            }

            .header-row,
            .status-row {
                flex-direction: column;
            }

            .document-title {
                text-align: right;
            }

            .details-grid,
            .summary-grid,
            .attendance-grid,
            .signatures {
                grid-template-columns: 1fr;
            }

            .content,
            .header {
                padding: 20px;
            }
        }
    </style>

</head>

<body>


<div class="print-actions">

    <a
        href="{{ url()->previous() }}"
        class="print-button secondary"
    >
        رجوع
    </a>

    <button
        type="button"
        id="printPayslip"
        class="print-button"
    >
        طباعة قسيمة الراتب
    </button>

</div>


<main class="payslip">

    <header class="header">

        <div class="header-row">

            <div>

                <h2 class="company-name">
                    {{ $company?->name ?? 'رؤية يوم' }}
                </h2>

                <div class="company-meta">

                    @if($company?->code)
                        <div>
                            كود الشركة:
                            {{ $company->code }}
                        </div>
                    @endif

                    @if($company?->phone)
                        <div dir="ltr">
                            {{ $company->phone }}
                        </div>
                    @endif

                    @if($company?->email)
                        <div dir="ltr">
                            {{ $company->email }}
                        </div>
                    @endif

                </div>

            </div>


            <div class="document-title">

                <h1>
                    قسيمة راتب
                </h1>

                <div class="document-number">

                    {{ data_get($run, 'run_number', '-') }}

                </div>

            </div>

        </div>

    </header>


    <div class="content">

        <div class="status-row">

            <div>

                <div class="period-title">

                    {{ data_get($period, 'name', 'فترة الرواتب') }}

                </div>

                <div class="company-meta" style="color:#64748b">

                    من
                    {{ $formatDate(data_get($period, 'start_date')) }}

                    إلى
                    {{ $formatDate(data_get($period, 'end_date')) }}

                </div>

            </div>

            <span class="status-badge">
                {{ $statusLabel }}
            </span>

        </div>


        <section class="section">

            <div class="section-title">
                بيانات الموظف
            </div>

            <div class="section-body">

                <div class="details-grid">

                    <div>
                        <div class="detail-label">
                            اسم الموظف
                        </div>

                        <div class="detail-value">
                            {{ $employeeName }}
                        </div>
                    </div>


                    <div>
                        <div class="detail-label">
                            الرقم الوظيفي
                        </div>

                        <div
                            class="detail-value"
                            dir="ltr"
                        >
                            {{ $employeeNumber }}
                        </div>
                    </div>


                    <div>
                        <div class="detail-label">
                            المسمى الوظيفي
                        </div>

                        <div class="detail-value">
                            {{ $jobTitleName }}
                        </div>
                    </div>


                    <div>
                        <div class="detail-label">
                            الإدارة أو القسم
                        </div>

                        <div class="detail-value">
                            {{ $departmentName }}
                        </div>
                    </div>


                    <div>
                        <div class="detail-label">
                            الفرع
                        </div>

                        <div class="detail-value">
                            {{ $branchName }}
                        </div>
                    </div>


                    <div>
                        <div class="detail-label">
                            عملة الراتب
                        </div>

                        <div class="detail-value">
                            {{ $currency }}
                        </div>
                    </div>

                </div>

            </div>

        </section>


        <div class="summary-grid">

            <div class="summary-card">

                <div class="summary-label">
                    الراتب الأساسي
                </div>

                <div class="summary-value">
                    {{ $formatMoney(
                        data_get($item, 'basic_salary')
                    ) }}
                </div>

            </div>


            <div class="summary-card earning earnings">

                <div class="summary-label">
                    إجمالي الراتب
                </div>

                <div class="summary-value">
                    {{ $formatMoney(
                        data_get($item, 'gross_salary')
                    ) }}
                </div>

            </div>


            <div class="summary-card deduction">

                <div class="summary-label">
                    إجمالي الخصومات
                </div>

                <div class="summary-value">
                    {{ $formatMoney(
                        data_get($item, 'total_deductions')
                    ) }}
                </div>

            </div>


            <div class="summary-card net">

                <div class="summary-label">
                    صافي الراتب
                </div>

                <div class="summary-value">
                    {{ $formatMoney(
                        data_get($item, 'net_salary')
                    ) }}
                </div>

            </div>

        </div>


        <section class="section">

            <div class="section-title">
                الاستحقاقات
            </div>

            <table>

                <thead>
                    <tr>
                        <th>#</th>
                        <th>البيان</th>
                        <th>المصدر</th>
                        <th>المبلغ</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($earnings as $index => $component)

                        <tr>

                            <td>
                                {{ $index + 1 }}
                            </td>

                            <td>

                                <div class="component-name">

                                    {{ data_get(
                                        $component,
                                        'component_name',
                                        '-'
                                    ) }}

                                </div>

                                <div class="component-code">

                                    {{ data_get(
                                        $component,
                                        'component_code',
                                        ''
                                    ) }}

                                </div>

                            </td>

                            <td>

                                {{ data_get(
                                    $component,
                                    'category',
                                    '-'
                                ) }}

                            </td>

                            <td>

                                <span class="amount earning-amount">

                                    {{ $formatMoney(
                                        data_get(
                                            $component,
                                            'amount'
                                        )
                                    ) }}

                                </span>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td
                                colspan="4"
                                class="empty-row"
                            >
                                لا توجد استحقاقات إضافية.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </section>


        <section class="section">

            <div class="section-title">
                الخصومات
            </div>

            <table>

                <thead>
                    <tr>
                        <th>#</th>
                        <th>البيان</th>
                        <th>المصدر</th>
                        <th>المبلغ</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($deductions as $index => $component)

                        <tr>

                            <td>
                                {{ $index + 1 }}
                            </td>

                            <td>

                                <div class="component-name">

                                    {{ data_get(
                                        $component,
                                        'component_name',
                                        '-'
                                    ) }}

                                </div>

                                <div class="component-code">

                                    {{ data_get(
                                        $component,
                                        'component_code',
                                        ''
                                    ) }}

                                </div>

                            </td>

                            <td>

                                {{ data_get(
                                    $component,
                                    'category',
                                    '-'
                                ) }}

                            </td>

                            <td>

                                <span class="amount deduction-amount">

                                    {{ $formatMoney(
                                        data_get(
                                            $component,
                                            'amount'
                                        )
                                    ) }}

                                </span>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td
                                colspan="4"
                                class="empty-row"
                            >
                                لا توجد خصومات.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </section>


        <section class="section">

            <div class="section-title">
                ملخص الحضور
            </div>

            <div class="section-body">

                <div class="attendance-grid">

                    <div class="attendance-item">

                        <div class="detail-label">
                            أيام العمل المجدولة
                        </div>

                        <div class="attendance-value">

                            {{ data_get(
                                $item,
                                'scheduled_work_days',
                                0
                            ) }}

                        </div>

                    </div>


                    <div class="attendance-item">

                        <div class="detail-label">
                            أيام العمل الفعلية
                        </div>

                        <div class="attendance-value">

                            {{ data_get(
                                $item,
                                'actual_work_days',
                                0
                            ) }}

                        </div>

                    </div>


                    <div class="attendance-item">

                        <div class="detail-label">
                            أيام الغياب
                        </div>

                        <div class="attendance-value">

                            {{ data_get(
                                $item,
                                'absent_days',
                                0
                            ) }}

                        </div>

                    </div>


                    <div class="attendance-item">

                        <div class="detail-label">
                            الإجازات غير المدفوعة
                        </div>

                        <div class="attendance-value">

                            {{ data_get(
                                $item,
                                'unpaid_leave_days',
                                0
                            ) }}

                        </div>

                    </div>


                    <div class="attendance-item">

                        <div class="detail-label">
                            دقائق العمل الإضافي
                        </div>

                        <div class="attendance-value">

                            {{ data_get(
                                $item,
                                'overtime_minutes',
                                0
                            ) }}

                        </div>

                    </div>

                </div>

            </div>

        </section>


        @if(data_get($run, 'notes'))

            <section class="section">

                <div class="section-title">
                    ملاحظات
                </div>

                <div class="section-body notes">
                    {{ data_get($run, 'notes') }}
                </div>

            </section>

        @endif


        <div class="signatures">

            <div class="signature">

                <div>
                    إعداد الموارد البشرية
                </div>

                <div class="signature-line">
                    التوقيع
                </div>

            </div>


            <div class="signature">

                <div>
                    اعتماد الإدارة
                </div>

                <div class="signature-line">
                    التوقيع
                </div>

            </div>


            <div class="signature">

                <div>
                    استلام الموظف
                </div>

                <div class="signature-line">
                    التوقيع
                </div>

            </div>

        </div>


        <footer class="footer">

            <div>
                تمت الطباعة بواسطة:
                {{ auth()->user()?->name ?? '-' }}
            </div>

            <div dir="ltr">
                {{ $printedAt }}
            </div>

        </footer>

    </div>

</main>


@vite([
    'resources/js/app.js',
])


<script>
$(function () {

    $('#printPayslip').on(
        'click',
        function () {
            window.print();
        }
    );

});
</script>

</body>

</html>