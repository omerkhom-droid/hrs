@extends('layouts.tenant')

@section('title', 'تقارير الرواتب')
@section('page-title', 'تقارير الرواتب')

@php
    $reportRoutes = [
        'data' => route(
            'app.payroll.reports.data'
        ),

        'options' => route(
            'app.payroll.reports.options'
        ),

        'export' => route(
            'app.payroll.reports.export'
        ),
    ];
@endphp

@push('styles')
<style>
    .report-stat-card {
        height: 100%;
        border: 0;
        border-radius: 18px;
        box-shadow:
            0 7px 24px
            rgba(15, 23, 42, .06);
    }

    .report-stat-label {
        margin-bottom: 8px;
        color: #64748b;
        font-size: .8rem;
    }

    .report-stat-value {
        direction: ltr;
        display: inline-block;
        color: #0f172a;
        font-size: 1.25rem;
        font-weight: 800;
    }

    .report-stat-card.net {
        background:
            linear-gradient(
                135deg,
                #0b1f45,
                #1769ff
            );
        color: #fff;
    }

    .report-stat-card.net .report-stat-label {
        color: rgba(255, 255, 255, .72);
    }

    .report-stat-card.net .report-stat-value {
        color: #fff;
    }

    .filter-card,
    .report-table-card {
        border: 0;
        border-radius: 18px;
        box-shadow:
            0 7px 24px
            rgba(15, 23, 42, .06);
    }

    .report-table-card {
        overflow: hidden;
    }

    .report-table th {
        padding: 13px;
        white-space: nowrap;
        background: #f8fafc;
        color: #475569;
        font-size: .8rem;
        font-weight: 800;
    }

    .report-table td {
        padding: 13px;
        vertical-align: middle;
    }

    .employee-name {
        color: #0f172a;
        font-weight: 800;
        white-space: nowrap;
    }

    .employee-number {
        margin-top: 3px;
        color: #64748b;
        font-size: .75rem;
    }

    .secondary-text {
        margin-top: 3px;
        color: #64748b;
        font-size: .75rem;
    }

    .money-value {
        direction: ltr;
        display: inline-block;
        font-weight: 800;
        white-space: nowrap;
    }

    .net-value {
        color: #047857;
    }

    .deduction-value {
        color: #be123c;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6px 11px;
        border-radius: 999px;
        font-size: .75rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .status-pending {
        background: #fef3c7;
        color: #b45309;
    }

    .status-calculated {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .status-exception {
        background: #fee2e2;
        color: #b91c1c;
    }

    .status-approved {
        background: #dcfce7;
        color: #15803d;
    }

    .status-paid {
        background: #d1fae5;
        color: #047857;
    }

    .attendance-cell {
        min-width: 105px;
    }

    .attendance-progress {
        height: 5px;
        margin-top: 7px;
        overflow: hidden;
        border-radius: 999px;
        background: #e2e8f0;
    }

    .attendance-progress-bar {
        height: 100%;
        border-radius: 999px;
        background: #1769ff;
    }

    .report-loading {
        min-height: 200px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #64748b;
    }

    .report-empty {
        padding: 55px 20px !important;
        text-align: center;
        color: #64748b;
    }

    .pagination-button {
        min-width: 38px;
    }

    @media print {
        .no-print,
        .sidebar,
        .tenant-sidebar,
        header {
            display: none !important;
        }

        .report-table-card,
        .filter-card,
        .report-stat-card {
            box-shadow: none;
        }
    }
</style>
@endpush

@section('content')

<div id="reportAlert"></div>


<div class="row g-3 mb-4">

    <div class="col-xl-2 col-md-4 col-6">

        <div class="card report-stat-card">

            <div class="card-body">

                <div class="report-stat-label">
                    عدد الموظفين
                </div>

                <div
                    id="employeesCount"
                    class="report-stat-value"
                >
                    0
                </div>

            </div>

        </div>

    </div>


    <div class="col-xl-2 col-md-4 col-6">

        <div class="card report-stat-card">

            <div class="card-body">

                <div class="report-stat-label">
                    الأساسي
                </div>

                <div
                    id="totalBasic"
                    class="report-stat-value"
                >
                    -
                </div>

            </div>

        </div>

    </div>


    <div class="col-xl-2 col-md-4 col-6">

        <div class="card report-stat-card">

            <div class="card-body">

                <div class="report-stat-label">
                    إجمالي الراتب
                </div>

                <div
                    id="totalGross"
                    class="report-stat-value text-primary"
                >
                    -
                </div>

            </div>

        </div>

    </div>


    <div class="col-xl-2 col-md-4 col-6">

        <div class="card report-stat-card">

            <div class="card-body">

                <div class="report-stat-label">
                    الاستحقاقات
                </div>

                <div
                    id="totalEarnings"
                    class="report-stat-value text-success"
                >
                    -
                </div>

            </div>

        </div>

    </div>


    <div class="col-xl-2 col-md-4 col-6">

        <div class="card report-stat-card">

            <div class="card-body">

                <div class="report-stat-label">
                    الخصومات
                </div>

                <div
                    id="totalDeductions"
                    class="report-stat-value text-danger"
                >
                    -
                </div>

            </div>

        </div>

    </div>


    <div class="col-xl-2 col-md-4 col-6">

        <div class="card report-stat-card net">

            <div class="card-body">

                <div class="report-stat-label">
                    صافي الرواتب
                </div>

                <div
                    id="totalNet"
                    class="report-stat-value"
                >
                    -
                </div>

            </div>

        </div>

    </div>

</div>


<div class="card filter-card mb-4 no-print">

    <div class="card-body p-4">

        <div class="row g-3 align-items-end">

            <div class="col-xl-3 col-md-6">

                <label
                    for="searchFilter"
                    class="form-label"
                >
                    البحث
                </label>

                <input
                    type="text"
                    id="searchFilter"
                    class="form-control"
                    placeholder="اسم الموظف أو الرقم الوظيفي"
                >

            </div>


            <div class="col-xl-2 col-md-6">

                <label
                    for="periodFilter"
                    class="form-label"
                >
                    فترة الرواتب
                </label>

                <select
                    id="periodFilter"
                    class="form-select"
                >
                    <option value="">
                        جميع الفترات
                    </option>
                </select>

            </div>


            <div class="col-xl-2 col-md-6">

                <label
                    for="runFilter"
                    class="form-label"
                >
                    مسير الرواتب
                </label>

                <select
                    id="runFilter"
                    class="form-select"
                >
                    <option value="">
                        جميع المسيرات
                    </option>
                </select>

            </div>


            <div class="col-xl-2 col-md-6">

                <label
                    for="departmentFilter"
                    class="form-label"
                >
                    القسم
                </label>

                <select
                    id="departmentFilter"
                    class="form-select"
                >
                    <option value="">
                        جميع الأقسام
                    </option>
                </select>

            </div>


            <div class="col-xl-2 col-md-6">

                <label
                    for="statusFilter"
                    class="form-label"
                >
                    الحالة
                </label>

                <select
                    id="statusFilter"
                    class="form-select"
                >
                    <option value="">
                        جميع الحالات
                    </option>

                    <option value="pending">
                        بانتظار الحساب
                    </option>

                    <option value="calculated">
                        محسوبة
                    </option>

                    <option value="exception">
                        بها مشكلة
                    </option>

                    <option value="approved">
                        معتمدة
                    </option>

                    <option value="paid">
                        مدفوعة
                    </option>
                </select>

            </div>


            <div class="col-xl-1 col-md-6">

                <label
                    for="perPageFilter"
                    class="form-label"
                >
                    العدد
                </label>

                <select
                    id="perPageFilter"
                    class="form-select"
                >
                    <option value="10">
                        10
                    </option>

                    <option value="15" selected>
                        15
                    </option>

                    <option value="25">
                        25
                    </option>

                    <option value="50">
                        50
                    </option>

                    <option value="100">
                        100
                    </option>
                </select>

            </div>


            <div class="col-12">

                <div class="d-flex justify-content-end gap-2">

                    <button
                        type="button"
                        id="resetFilters"
                        class="btn btn-outline-secondary"
                    >
                        إعادة الفلاتر
                    </button>

                    <button
                        type="button"
                        id="searchButton"
                        class="btn btn-primary px-4"
                    >
                        عرض التقرير
                    </button>

                    @can('reports.export')

                        <button
                            type="button"
                            id="exportReport"
                            class="btn btn-success px-4"
                        >
                            تصدير Excel
                        </button>

                    @endcan

                </div>

            </div>

        </div>

    </div>

</div>


<div class="row g-3 mb-4">

    <div class="col-md-3 col-6">

        <div class="card report-stat-card">

            <div class="card-body">

                <div class="report-stat-label">
                    أيام العمل الفعلية
                </div>

                <div
                    id="actualWorkDays"
                    class="report-stat-value"
                >
                    0
                </div>

            </div>

        </div>

    </div>


    <div class="col-md-3 col-6">

        <div class="card report-stat-card">

            <div class="card-body">

                <div class="report-stat-label">
                    أيام الغياب
                </div>

                <div
                    id="absentDays"
                    class="report-stat-value text-danger"
                >
                    0
                </div>

            </div>

        </div>

    </div>


    <div class="col-md-3 col-6">

        <div class="card report-stat-card">

            <div class="card-body">

                <div class="report-stat-label">
                    الإجازات غير المدفوعة
                </div>

                <div
                    id="unpaidLeaveDays"
                    class="report-stat-value text-warning"
                >
                    0
                </div>

            </div>

        </div>

    </div>


    <div class="col-md-3 col-6">

        <div class="card report-stat-card">

            <div class="card-body">

                <div class="report-stat-label">
                    ساعات العمل الإضافي
                </div>

                <div
                    id="overtimeHours"
                    class="report-stat-value text-primary"
                >
                    0
                </div>

            </div>

        </div>

    </div>

</div>


<div class="card report-table-card">

    <div class="card-header bg-white border-0 px-4 py-3">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

            <div>

                <h5 class="mb-1">
                    تفاصيل تقرير الرواتب
                </h5>

                <div class="text-muted small">
                    ملخص رواتب الموظفين والحضور لكل مسير.
                </div>

            </div>

            <span
                id="recordsBadge"
                class="badge bg-primary-subtle text-primary"
            >
                0 سجل
            </span>

        </div>

    </div>


    <div class="table-responsive">

        <table class="table report-table mb-0">

            <thead>

                <tr>
                    <th>#</th>
                    <th>الموظف</th>
                    <th>القسم</th>
                    <th>الفترة والمسير</th>
                    <th>الأساسي</th>
                    <th>الإجمالي</th>
                    <th>الاستحقاقات</th>
                    <th>الخصومات</th>
                    <th>الصافي</th>
                    <th>الحضور</th>
                    <th>الغياب</th>
                    <th>الإضافي</th>
                    <th>الحالة</th>
                </tr>

            </thead>

            <tbody id="reportRows">

                <tr>

                    <td colspan="13">

                        <div class="report-loading">
                            جاري تحميل التقرير...
                        </div>

                    </td>

                </tr>

            </tbody>

        </table>

    </div>


    <div class="card-footer bg-white border-0 px-4 py-3">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

            <div
                id="paginationInfo"
                class="text-muted small"
            >
                لا توجد بيانات
            </div>

            <div
                id="paginationButtons"
                class="d-flex gap-1 flex-wrap no-print"
            ></div>

        </div>

    </div>

</div>

@endsection


@push('scripts')
<script>
(function ($) {

    'use strict';


    const routes = {{
        Illuminate\Support\Js::from(
            $reportRoutes
        )
    }};


    let allRuns = [];
    let currentPage = 1;


    function escapeHtml(value) {

        return $('<div>')
            .text(value ?? '')
            .html();

    }


    function valueOrDash(value) {

        if (
            value === null ||
            value === undefined ||
            value === ''
        ) {
            return '-';
        }

        return escapeHtml(value);

    }


    function numericValue(value) {

        const number = Number(value);

        return Number.isFinite(number)
            ? number
            : 0;

    }


    function formatNumber(value, decimals) {

        return new Intl.NumberFormat(
            'ar-SA',
            {
                minimumFractionDigits: 0,
                maximumFractionDigits:
                    decimals ?? 2,
            }
        ).format(
            numericValue(value)
        );

    }


    function money(value, currency) {

        return new Intl.NumberFormat(
            'ar-SA',
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }
        ).format(
            numericValue(value)
        ) +
            ' ' +
            escapeHtml(currency || 'SAR');

    }


    function statusLabel(status) {

        const labels = {

            pending:
                'بانتظار الحساب',

            calculated:
                'محسوبة',

            exception:
                'بها مشكلة',

            approved:
                'معتمدة',

            paid:
                'مدفوعة',

        };

        return labels[status] || status || '-';

    }


    function statusBadge(status) {

        return `

            <span class="status-badge status-${escapeHtml(status || 'pending')}">

                ${escapeHtml(
                    statusLabel(status)
                )}

            </span>

        `;

    }


    function runTypeLabel(type) {

        const labels = {

            regular:
                'عادي',

            off_cycle:
                'خارج الدورة',

            final_settlement:
                'تصفية نهائية',

        };

        return labels[type] || type || '-';

    }


    function showError(xhr) {

        let message =
            xhr.responseJSON?.message ||
            'حدث خطأ أثناء تحميل التقرير.';

        const errors =
            xhr.responseJSON?.errors;

        if (errors) {

            const firstError =
                Object.values(errors)
                    .flat()
                    .filter(Boolean)[0];

            if (firstError) {
                message = firstError;
            }

        }

        $('#reportAlert').html(`

            <div class="alert alert-danger">

                ${escapeHtml(message)}

            </div>

        `);

    }


    function loadOptions() {

        $.ajax({

            url: routes.options,

            type: 'GET',

            success: function (response) {

                const periods =
                    response.periods || [];

                const departments =
                    response.departments || [];

                allRuns =
                    response.runs || [];


                $('#periodFilter').html(`

                    <option value="">
                        جميع الفترات
                    </option>

                `);


                $.each(
                    periods,
                    function (index, period) {

                        $('#periodFilter').append(`

                            <option value="${escapeHtml(period.id)}">

                                ${escapeHtml(
                                    period.name ||
                                    period.code
                                )}

                            </option>

                        `);

                    }
                );


                $('#departmentFilter').html(`

                    <option value="">
                        جميع الأقسام
                    </option>

                `);


                $.each(
                    departments,
                    function (index, department) {

                        $('#departmentFilter').append(`

                            <option value="${escapeHtml(department.id)}">

                                ${escapeHtml(department.name)}

                            </option>

                        `);

                    }
                );


                renderRunOptions();

            },

            error: function (xhr) {
                showError(xhr);
            },

        });

    }


    function renderRunOptions() {

        const periodId =
            $('#periodFilter').val();

        const selectedRun =
            $('#runFilter').val();


        $('#runFilter').html(`

            <option value="">
                جميع المسيرات
            </option>

        `);


        $.each(
            allRuns,
            function (index, run) {

                if (
                    periodId &&
                    String(run.payroll_period_id) !==
                    String(periodId)
                ) {
                    return;
                }


                $('#runFilter').append(`

                    <option value="${escapeHtml(run.id)}">

                        ${escapeHtml(run.run_number)}
                        -
                        ${escapeHtml(
                            runTypeLabel(run.type)
                        )}

                    </option>

                `);

            }
        );


        if (
            $('#runFilter option[value="' +
                selectedRun +
                '"]').length
        ) {
            $('#runFilter').val(
                selectedRun
            );
        }

    }


    function renderSummary(summary) {

        const currency =
            summary.currency_code ||
            'SAR';


        $('#employeesCount').text(
            formatNumber(
                summary.employees_count,
                0
            )
        );


        $('#totalBasic').html(
            money(
                summary.total_basic_salary,
                currency
            )
        );


        $('#totalGross').html(
            money(
                summary.total_gross_salary,
                currency
            )
        );


        $('#totalEarnings').html(
            money(
                summary.total_earnings,
                currency
            )
        );


        $('#totalDeductions').html(
            money(
                summary.total_deductions,
                currency
            )
        );


        $('#totalNet').html(
            money(
                summary.total_net_salary,
                currency
            )
        );


        $('#actualWorkDays').text(
            formatNumber(
                summary.actual_work_days,
                2
            )
        );


        $('#absentDays').text(
            formatNumber(
                summary.absent_days,
                2
            )
        );


        $('#unpaidLeaveDays').text(
            formatNumber(
                summary.unpaid_leave_days,
                2
            )
        );


        $('#overtimeHours').text(
            formatNumber(
                numericValue(
                    summary.overtime_minutes
                ) / 60,
                2
            )
        );

    }


    function attendancePercentage(
        actual,
        scheduled
    ) {

        const scheduledDays =
            numericValue(scheduled);

        if (scheduledDays <= 0) {
            return 0;
        }

        return Math.min(
            100,
            Math.max(
                0,
                (
                    numericValue(actual) /
                    scheduledDays
                ) * 100
            )
        );

    }


    function renderRows(records, from) {

        if (!records.length) {

            $('#reportRows').html(`

                <tr>

                    <td
                        colspan="13"
                        class="report-empty"
                    >
                        لا توجد بيانات مطابقة للفلاتر.
                    </td>

                </tr>

            `);

            return;
        }


        let html = '';


        $.each(
            records,
            function (index, item) {

                const employee =
                    item.employee || {};

                const department =
                    employee.department || {};

                const jobTitle =
                    employee.job_title || {};

                const period =
                    item.payroll_period || {};

                const run =
                    item.payroll_run || {};

                const currency =
                    item.currency_code ||
                    'SAR';

                const attendance =
                    attendancePercentage(
                        item.actual_work_days,
                        item.scheduled_work_days
                    );


                html += `

                    <tr>

                        <td>
                            ${(from || 1) + index}
                        </td>


                        <td>

                            <div class="employee-name">

                                ${valueOrDash(
                                    employee.full_name
                                )}

                            </div>

                            <div
                                class="employee-number"
                                dir="ltr"
                            >

                                ${valueOrDash(
                                    employee.employee_number
                                )}

                            </div>

                        </td>


                        <td>

                            <div class="fw-bold">

                                ${valueOrDash(
                                    department.name
                                )}

                            </div>

                            <div class="secondary-text">

                                ${valueOrDash(
                                    jobTitle.name
                                )}

                            </div>

                        </td>


                        <td>

                            <div class="fw-bold">

                                ${valueOrDash(
                                    period.name
                                )}

                            </div>

                            <div class="secondary-text">

                                ${valueOrDash(
                                    run.run_number
                                )}

                                -

                                ${escapeHtml(
                                    runTypeLabel(run.type)
                                )}

                            </div>

                        </td>


                        <td>

                            <span class="money-value">

                                ${money(
                                    item.basic_salary,
                                    currency
                                )}

                            </span>

                        </td>


                        <td>

                            <span class="money-value">

                                ${money(
                                    item.gross_salary,
                                    currency
                                )}

                            </span>

                        </td>


                        <td>

                            <span class="money-value text-success">

                                ${money(
                                    item.total_earnings,
                                    currency
                                )}

                            </span>

                        </td>


                        <td>

                            <span class="money-value deduction-value">

                                ${money(
                                    item.total_deductions,
                                    currency
                                )}

                            </span>

                        </td>


                        <td>

                            <span class="money-value net-value">

                                ${money(
                                    item.net_salary,
                                    currency
                                )}

                            </span>

                        </td>


                        <td class="attendance-cell">

                            <div class="small">

                                ${formatNumber(
                                    item.actual_work_days,
                                    2
                                )}

                                من

                                ${formatNumber(
                                    item.scheduled_work_days,
                                    2
                                )}

                            </div>

                            <div class="attendance-progress">

                                <div
                                    class="attendance-progress-bar"
                                    style="width: ${attendance}%"
                                ></div>

                            </div>

                        </td>


                        <td>

                            <span class="${
                                numericValue(item.absent_days) > 0
                                    ? 'text-danger fw-bold'
                                    : ''
                            }">

                                ${formatNumber(
                                    item.absent_days,
                                    2
                                )}

                            </span>

                        </td>


                        <td>

                            ${formatNumber(
                                numericValue(
                                    item.overtime_minutes
                                ) / 60,
                                2
                            )}

                            ساعة

                        </td>


                        <td>
                            ${statusBadge(item.status)}
                        </td>

                    </tr>

                `;

            }
        );


        $('#reportRows').html(html);

    }


    function renderPagination(response) {

        const current =
            Number(response.current_page || 1);

        const last =
            Number(response.last_page || 1);

        const total =
            Number(response.total || 0);

        const from =
            response.from || 0;

        const to =
            response.to || 0;


        $('#recordsBadge').text(
            `${total} سجل`
        );


        $('#paginationInfo').text(

            total
                ? `عرض ${from} إلى ${to} من ${total}`
                : 'لا توجد بيانات'

        );


        let html = '';


        html += `

            <button
                type="button"
                class="btn btn-sm btn-outline-secondary pagination-button page-button"
                data-page="${current - 1}"
                ${current <= 1 ? 'disabled' : ''}
            >
                السابق
            </button>

        `;


        for (
            let page = Math.max(1, current - 2);
            page <= Math.min(last, current + 2);
            page++
        ) {

            html += `

                <button
                    type="button"
                    class="btn btn-sm pagination-button page-button ${
                        page === current
                            ? 'btn-primary'
                            : 'btn-outline-secondary'
                    }"
                    data-page="${page}"
                >
                    ${page}
                </button>

            `;

        }


        html += `

            <button
                type="button"
                class="btn btn-sm btn-outline-secondary pagination-button page-button"
                data-page="${current + 1}"
                ${current >= last ? 'disabled' : ''}
            >
                التالي
            </button>

        `;


        $('#paginationButtons').html(
            html
        );

    }


    function loadReport(page) {

        currentPage = page || 1;


        $('#reportRows').html(`

            <tr>

                <td colspan="13">

                    <div class="report-loading">
                        جاري تحميل التقرير...
                    </div>

                </td>

            </tr>

        `);


        $.ajax({

            url: routes.data,

            type: 'GET',

            data: {

                page:
                    currentPage,

                search:
                    $('#searchFilter').val(),

                payroll_period_id:
                    $('#periodFilter').val(),

                payroll_run_id:
                    $('#runFilter').val(),

                department_id:
                    $('#departmentFilter').val(),

                status:
                    $('#statusFilter').val(),

                per_page:
                    $('#perPageFilter').val(),

            },

            success: function (response) {

                const records =
                    response.data || [];

                renderSummary(
                    response.summary || {}
                );

                renderRows(
                    records,
                    response.from
                );

                renderPagination(response);

            },

            error: function (xhr) {

                renderRows([], 0);
                showError(xhr);

            },

        });

    }


    $(function () {

        loadOptions();
        loadReport(1);


        $('#periodFilter').on(
            'change',
            function () {

                renderRunOptions();
                loadReport(1);

            }
        );


        $('#runFilter, #departmentFilter, #statusFilter, #perPageFilter').on(
            'change',
            function () {
                loadReport(1);
            }
        );


        $('#searchButton').on(
            'click',
            function () {
                loadReport(1);
            }
        );


        $('#searchFilter').on(
            'keydown',
            function (event) {

                if (event.key === 'Enter') {

                    event.preventDefault();
                    loadReport(1);

                }

            }
        );


        $('#resetFilters').on(
            'click',
            function () {

                $('#searchFilter').val('');
                $('#periodFilter').val('');
                $('#runFilter').val('');
                $('#departmentFilter').val('');
                $('#statusFilter').val('');
                $('#perPageFilter').val('15');

                renderRunOptions();
                loadReport(1);

            }
        );


        $(document).on(
            'click',
            '.page-button',
            function () {

                const page =
                    Number($(this).data('page'));

                if (page > 0) {
                    loadReport(page);
                }

            }
        );



        $('#exportReport').on(
            'click',
            function () {

                if (!routes.export) {

                    $('#reportAlert').html(`

                        <div class="alert alert-danger">
                            رابط تصدير التقرير غير معرف.
                        </div>

                    `);

                    return;
                }


                const parameters = $.param({

                    search:
                        $('#searchFilter').val(),

                    payroll_period_id:
                        $('#periodFilter').val(),

                    payroll_run_id:
                        $('#runFilter').val(),

                    department_id:
                        $('#departmentFilter').val(),

                    status:
                        $('#statusFilter').val(),

                });


                window.location.href =
                    routes.export +
                    '?' +
                    parameters;

            }
        );
        


    });

})(jQuery);
</script>
@endpush