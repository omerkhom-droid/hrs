@extends('layouts.tenant')

@section('title', 'قسائم الرواتب')
@section('page-title', 'قسائم الرواتب')

@php
    $payslipRoutes = [
        'data' => route(
            'app.payslips.data'
        ),

        'options' => route(
            'app.payslips.options'
        ),

        'show' => route(
            'app.payslips.show',
            ['payrollItem' => '__ID__']
        ),

        'print' => route(
            'app.payslips.print',
            ['payrollItem' => '__ID__']
        ),
    ];
@endphp

@push('styles')
<style>
    .payslip-stat-card {
        border: 0;
        border-radius: 18px;
        box-shadow: 0 6px 22px rgba(15, 23, 42, .06);
    }

    .payslip-stat-value {
        font-size: 1.45rem;
        font-weight: 800;
        color: #0f172a;
    }

    .payslip-filters {
        border: 0;
        border-radius: 18px;
        box-shadow: 0 6px 22px rgba(15, 23, 42, .06);
    }

    .payslip-table-card {
        border: 0;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 6px 22px rgba(15, 23, 42, .06);
    }

    .payslip-table th {
        white-space: nowrap;
        background: #f8fafc;
        color: #475569;
        font-weight: 700;
        padding: 14px;
    }

    .payslip-table td {
        padding: 14px;
        vertical-align: middle;
    }

    .employee-name {
        font-weight: 800;
        color: #0f172a;
    }

    .employee-number {
        color: #64748b;
        font-size: .82rem;
        margin-top: 3px;
    }

    .money-value {
        direction: ltr;
        display: inline-block;
        font-weight: 700;
        white-space: nowrap;
    }

    .net-salary {
        color: #047857;
        font-size: 1rem;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: 6px 12px;
        font-size: .8rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .status-calculated {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .status-approved {
        background: #dcfce7;
        color: #15803d;
    }

    .status-paid {
        background: #d1fae5;
        color: #047857;
    }

    .status-exception {
        background: #fee2e2;
        color: #b91c1c;
    }

    .status-pending {
        background: #fef3c7;
        color: #b45309;
    }

    .payslip-action {
        min-width: 72px;
    }

    .payslip-empty {
        padding: 55px 20px !important;
        text-align: center;
        color: #64748b;
    }

    .payslip-loading {
        min-height: 180px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #64748b;
    }

    /*
    |--------------------------------------------------------------------------
    | الموديل المخصص
    |--------------------------------------------------------------------------
    */

    .payslip-modal {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 1090;
        overflow-x: hidden;
        overflow-y: auto;
        background: rgba(15, 23, 42, .62);
        padding: 24px 12px;
    }

    .payslip-modal-dialog {
        width: 100%;
        max-width: 1050px;
        margin: 0 auto;
        min-height: calc(100vh - 48px);
        display: flex;
        align-items: center;
    }

    .payslip-modal-content {
        width: 100%;
        max-height: calc(100vh - 48px);
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 24px 70px rgba(15, 23, 42, .28);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .payslip-modal-header,
    .payslip-modal-footer {
        flex: 0 0 auto;
        padding: 18px 22px;
        background: #fff;
    }

    .payslip-modal-header {
        border-bottom: 1px solid #e2e8f0;
    }

    .payslip-modal-footer {
        border-top: 1px solid #e2e8f0;
    }

    .payslip-modal-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        overscroll-behavior: contain;
        padding: 22px;
    }

    .payslip-modal-close {
        border: 0;
        width: 38px;
        height: 38px;
        border-radius: 12px;
        background: #f1f5f9;
        color: #0f172a;
        font-size: 1.3rem;
    }

    body.payslip-modal-open {
        overflow: hidden;
    }

    .details-section {
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        overflow: hidden;
        margin-bottom: 18px;
    }

    .details-section-title {
        background: #f8fafc;
        padding: 13px 16px;
        font-weight: 800;
        color: #0f172a;
        border-bottom: 1px solid #e2e8f0;
    }

    .details-section-body {
        padding: 16px;
    }

    .details-label {
        color: #64748b;
        font-size: .8rem;
        margin-bottom: 5px;
    }

    .details-value {
        font-weight: 700;
        color: #0f172a;
    }

    .total-box {
        height: 100%;
        border-radius: 15px;
        padding: 16px;
        background: #f8fafc;
    }

    .total-box.earning {
        background: #ecfdf5;
        color: #047857;
    }

    .total-box.deduction {
        background: #fff1f2;
        color: #be123c;
    }

    .total-box.net {
        background: #eff6ff;
        color: #1d4ed8;
    }

    .component-table th {
        background: #f8fafc;
        white-space: nowrap;
    }

    .pagination-button {
        min-width: 38px;
    }

    @media (max-width: 767.98px) {
        .payslip-modal {
            padding: 0;
        }

        .payslip-modal-dialog {
            min-height: 100vh;
        }

        .payslip-modal-content {
            min-height: 100vh;
            max-height: 100vh;
            border-radius: 0;
        }

        .payslip-table-card {
            border-radius: 14px;
        }
    }
</style>
@endpush

@section('content')

<div id="pageAlert"></div>

<div class="row g-3 mb-4">

    <div class="col-md-3 col-6">
        <div class="card payslip-stat-card h-100">
            <div class="card-body">
                <div class="text-muted small mb-2">
                    عدد القسائم
                </div>

                <div
                    class="payslip-stat-value"
                    id="statCount"
                >
                    -
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-6">
        <div class="card payslip-stat-card h-100">
            <div class="card-body">
                <div class="text-muted small mb-2">
                    إجمالي الأساسي
                </div>

                <div
                    class="payslip-stat-value"
                    id="statBasic"
                >
                    -
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-6">
        <div class="card payslip-stat-card h-100">
            <div class="card-body">
                <div class="text-muted small mb-2">
                    إجمالي الخصومات
                </div>

                <div
                    class="payslip-stat-value text-danger"
                    id="statDeductions"
                >
                    -
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-6">
        <div class="card payslip-stat-card h-100">
            <div class="card-body">
                <div class="text-muted small mb-2">
                    صافي الرواتب
                </div>

                <div
                    class="payslip-stat-value text-success"
                    id="statNet"
                >
                    -
                </div>
            </div>
        </div>
    </div>

</div>


<div class="card payslip-filters mb-4">

    <div class="card-body p-4">

        <div class="row g-3 align-items-end">

            <div class="col-lg-4">

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


            <div class="col-lg-3">

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


            <div class="col-lg-2">

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

                    <option value="calculated">
                        محسوبة
                    </option>

                    <option value="approved">
                        معتمدة
                    </option>

                    <option value="paid">
                        مدفوعة
                    </option>

                    <option value="exception">
                        بها مشكلة
                    </option>
                </select>

            </div>


            <div class="col-lg-1">

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
                </select>

            </div>


            <div class="col-lg-2">

                <div class="d-flex gap-2">

                    <button
                        type="button"
                        id="searchButton"
                        class="btn btn-primary flex-grow-1"
                    >
                        بحث
                    </button>

                    <button
                        type="button"
                        id="resetButton"
                        class="btn btn-outline-secondary"
                    >
                        إعادة
                    </button>

                </div>

            </div>

        </div>

    </div>

</div>


<div class="card payslip-table-card">

    <div class="card-header bg-white border-0 px-4 py-3">

        <div class="d-flex justify-content-between align-items-center gap-3">

            <div>

                <h5 class="mb-1">
                    قائمة قسائم الرواتب
                </h5>

                <div class="text-muted small">
                    عرض تفاصيل رواتب الموظفين وطباعتها.
                </div>

            </div>

            <span
                id="recordsBadge"
                class="badge bg-primary-subtle text-primary"
            >
                0 قسيمة
            </span>

        </div>

    </div>


    <div class="table-responsive">

        <table class="table payslip-table mb-0">

            <thead>
                <tr>
                    <th>#</th>
                    <th>الموظف</th>
                    <th>الفترة</th>
                    <th>مسير الرواتب</th>
                    <th>الراتب الأساسي</th>
                    <th>الاستحقاقات</th>
                    <th>الخصومات</th>
                    <th>الصافي</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>

            <tbody id="payslipRows">

                <tr>
                    <td colspan="10">
                        <div class="payslip-loading">
                            جاري تحميل البيانات...
                        </div>
                    </td>
                </tr>

            </tbody>

        </table>

    </div>


    <div class="card-footer bg-white border-0 px-4 py-3">

        <div
            class="d-flex justify-content-between align-items-center flex-wrap gap-3"
        >

            <div
                id="paginationInfo"
                class="text-muted small"
            >
                لا توجد بيانات
            </div>

            <div
                id="paginationButtons"
                class="d-flex gap-1 flex-wrap"
            ></div>

        </div>

    </div>

</div>


<div
    id="payslipDetailsModal"
    class="payslip-modal"
>

    <div class="payslip-modal-dialog">

        <div class="payslip-modal-content">

            <div class="payslip-modal-header">

                <div class="d-flex justify-content-between align-items-center gap-3">

                    <div>

                        <h5 class="mb-1">
                            تفاصيل قسيمة الراتب
                        </h5>

                        <div
                            id="modalSubtitle"
                            class="text-muted small"
                        ></div>

                    </div>

                    <button
                        type="button"
                        class="payslip-modal-close btn-close-payslip"
                        aria-label="إغلاق"
                    >
                        ×
                    </button>

                </div>

            </div>


            <div
                id="payslipDetailsBody"
                class="payslip-modal-body"
            >
                جاري تحميل البيانات...
            </div>


            <div class="payslip-modal-footer">

                <div class="d-flex justify-content-end gap-2">

                    <button
                        type="button"
                        class="btn btn-light btn-close-payslip"
                    >
                        إغلاق
                    </button>

                    <button
                        type="button"
                        id="modalPrintButton"
                        class="btn btn-primary"
                        disabled
                    >
                        طباعة القسيمة
                    </button>

                </div>

            </div>

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
            $payslipRoutes
        )
    }};


    let currentPage = 1;
    let currentPayslipId = null;


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


    function numberValue(value) {

        const number = Number(value);

        return Number.isFinite(number)
            ? number
            : 0;

    }


    function money(value, currency) {

        const amount = numberValue(value);

        try {

            return new Intl.NumberFormat(
                'ar-SA',
                {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                }
            ).format(amount) +
                ' ' +
                escapeHtml(currency || 'SAR');

        } catch (error) {

            return amount.toFixed(2) +
                ' ' +
                escapeHtml(currency || 'SAR');

        }

    }


    function showAlert(message, type) {

        $('#pageAlert').html(`

            <div
                class="alert alert-${type || 'danger'} alert-dismissible fade show"
                role="alert"
            >

                ${escapeHtml(message)}

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                ></button>

            </div>

        `);

    }


    function showAjaxError(xhr) {

        let message =
            xhr.responseJSON?.message ||
            'حدث خطأ أثناء تنفيذ العملية.';


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


        showAlert(message, 'danger');

    }


    function itemEmployee(item) {

        return item.employee || {};

    }


    function itemRun(item) {

        return (
            item.payroll_run ||
            item.payrollRun ||
            item.run ||
            {}
        );

    }


    function itemPeriod(item) {

        const run = itemRun(item);

        return (
            item.payroll_period ||
            item.payrollPeriod ||
            item.period ||
            run.period ||
            {}
        );

    }


    function statusText(status) {

        const statuses = {

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

        return statuses[status] || status || 'غير محدد';

    }


    function statusBadge(status) {

        return `

            <span class="status-badge status-${escapeHtml(status || 'pending')}">

                ${escapeHtml(statusText(status))}

            </span>

        `;

    }


    function runTypeText(type) {

        const types = {

            regular:
                'مسير عادي',

            off_cycle:
                'مسير خارج الدورة',

            final_settlement:
                'تصفية نهائية',

        };

        return types[type] || type || '-';

    }


    function payslipUrl(template, id) {

        return template.replace(
            '__ID__',
            encodeURIComponent(id)
        );

    }


    function loadOptions() {

        $.ajax({

            url: routes.options,

            type: 'GET',

            success: function (response) {

                const data =
                    response.data || response;

                const periods =
                    data.periods || [];

                const selectedValue =
                    $('#periodFilter').val();


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

                                ${escapeHtml(period.name || period.code)}

                            </option>

                        `);

                    }
                );


                $('#periodFilter').val(
                    selectedValue
                );

            },

            error: function (xhr) {

                showAjaxError(xhr);

            },

        });

    }


    function loadingRows() {

        $('#payslipRows').html(`

            <tr>

                <td colspan="10">

                    <div class="payslip-loading">
                        جاري تحميل البيانات...
                    </div>

                </td>

            </tr>

        `);

    }


    function emptyRows() {

        $('#payslipRows').html(`

            <tr>

                <td
                    colspan="10"
                    class="payslip-empty"
                >

                    لا توجد قسائم رواتب مطابقة للبحث.

                </td>

            </tr>

        `);

    }


    function renderRows(records, from) {

        if (!records.length) {

            emptyRows();
            return;

        }


        let html = '';


        $.each(
            records,
            function (index, item) {

                const employee =
                    itemEmployee(item);

                const run =
                    itemRun(item);

                const period =
                    itemPeriod(item);

                const currency =
                    item.currency_code ||
                    run.currency_code ||
                    'SAR';

                const employeeName =
                    employee.full_name ||
                    employee.name ||
                    item.employee_name ||
                    '-';

                const employeeNumber =
                    employee.employee_number ||
                    item.employee_number ||
                    '-';


                html += `

                    <tr>

                        <td>
                            ${(from || 1) + index}
                        </td>


                        <td>

                            <div class="employee-name">
                                ${escapeHtml(employeeName)}
                            </div>

                            <div class="employee-number" dir="ltr">
                                ${escapeHtml(employeeNumber)}
                            </div>

                        </td>


                        <td>

                            <div class="fw-bold">
                                ${valueOrDash(period.name)}
                            </div>

                            <div class="text-muted small">
                                ${valueOrDash(period.code)}
                            </div>

                        </td>


                        <td>

                            <div class="fw-bold">
                                ${valueOrDash(run.run_number)}
                            </div>

                            <div class="text-muted small">
                                ${escapeHtml(runTypeText(run.type))}
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

                            <span class="money-value text-success">

                                ${money(
                                    item.total_earnings,
                                    currency
                                )}

                            </span>

                        </td>


                        <td>

                            <span class="money-value text-danger">

                                ${money(
                                    item.total_deductions,
                                    currency
                                )}

                            </span>

                        </td>


                        <td>

                            <span class="money-value net-salary">

                                ${money(
                                    item.net_salary,
                                    currency
                                )}

                            </span>

                        </td>


                        <td>
                            ${statusBadge(item.status)}
                        </td>


                        <td>

                            <div class="d-flex gap-1">

                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary payslip-action btn-details"
                                    data-id="${escapeHtml(item.id)}"
                                >
                                    عرض
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-dark payslip-action btn-print"
                                    data-id="${escapeHtml(item.id)}"
                                >
                                    طباعة
                                </button>

                            </div>

                        </td>

                    </tr>

                `;

            }
        );


        $('#payslipRows').html(html);

    }


    function renderPagination(response) {

        const current =
            Number(response.current_page || 1);

        const last =
            Number(response.last_page || 1);

        const from =
            response.from || 0;

        const to =
            response.to || 0;

        const total =
            response.total || 0;


        $('#paginationInfo').text(

            total
                ? `عرض ${from} إلى ${to} من ${total}`
                : 'لا توجد بيانات'

        );


        $('#recordsBadge').text(
            `${total} قسيمة`
        );


        let buttons = '';


        buttons += `

            <button
                type="button"
                class="btn btn-sm btn-outline-secondary pagination-button page-button"
                data-page="${current - 1}"
                ${current <= 1 ? 'disabled' : ''}
            >
                السابق
            </button>

        `;


        const start =
            Math.max(1, current - 2);

        const end =
            Math.min(last, current + 2);


        for (
            let page = start;
            page <= end;
            page++
        ) {

            buttons += `

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


        buttons += `

            <button
                type="button"
                class="btn btn-sm btn-outline-secondary pagination-button page-button"
                data-page="${current + 1}"
                ${current >= last ? 'disabled' : ''}
            >
                التالي
            </button>

        `;


        $('#paginationButtons').html(buttons);

    }


    function updateSummary(response, records) {

        const summary =
            response.summary ||
            response.meta?.summary ||
            {};


        const currency =
            summary.currency_code ||
            records[0]?.currency_code ||
            'SAR';


        $('#statCount').text(
            response.total ?? records.length
        );


        $('#statBasic').html(

            summary.total_basic_salary !== undefined
                ? money(
                    summary.total_basic_salary,
                    currency
                )
                : '-'

        );


        $('#statDeductions').html(

            summary.total_deductions !== undefined
                ? money(
                    summary.total_deductions,
                    currency
                )
                : '-'

        );


        $('#statNet').html(

            summary.total_net_salary !== undefined
                ? money(
                    summary.total_net_salary,
                    currency
                )
                : '-'

        );

    }


    function loadPayslips(page) {

        currentPage = page || 1;

        loadingRows();


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

                status:
                    $('#statusFilter').val(),

                per_page:
                    $('#perPageFilter').val(),

            },

            success: function (response) {

                const paginator =
                    response.data?.data
                        ? response.data
                        : response;

                const records =
                    paginator.data || [];


                renderRows(
                    records,
                    paginator.from
                );

                renderPagination(paginator);

                updateSummary(
                    paginator,
                    records
                );

            },

            error: function (xhr) {

                emptyRows();
                showAjaxError(xhr);

            },

        });

    }


    function renderComponents(components, currency) {

        if (!components.length) {

            return `

                <tr>

                    <td
                        colspan="5"
                        class="text-center text-muted py-4"
                    >
                        لا توجد مكونات راتب.
                    </td>

                </tr>

            `;

        }


        let html = '';


        $.each(
            components,
            function (index, component) {

                html += `

                    <tr>

                        <td>
                            ${index + 1}
                        </td>

                        <td>

                            <div class="fw-bold">
                                ${valueOrDash(component.component_name)}
                            </div>

                            <div class="text-muted small">
                                ${valueOrDash(component.component_code)}
                            </div>

                        </td>

                        <td>
                            ${
                                component.type === 'earning'
                                    ? '<span class="text-success">استحقاق</span>'
                                    : '<span class="text-danger">خصم</span>'
                            }
                        </td>

                        <td>
                            ${valueOrDash(component.category)}
                        </td>

                        <td>

                            <span class="money-value">

                                ${money(
                                    component.amount,
                                    currency
                                )}

                            </span>

                        </td>

                    </tr>

                `;

            }
        );


        return html;

    }


    function renderDetails(item) {

        const employee =
            itemEmployee(item);

        const run =
            itemRun(item);

        const period =
            itemPeriod(item);

        const currency =
            item.currency_code ||
            run.currency_code ||
            'SAR';

        const components =
            item.components || [];


        $('#modalSubtitle').text(

            employee.full_name ||
            employee.name ||
            item.employee_name ||
            ''

        );


        $('#payslipDetailsBody').html(`

            <div class="details-section">

                <div class="details-section-title">
                    بيانات الموظف والمسير
                </div>

                <div class="details-section-body">

                    <div class="row g-3">

                        <div class="col-md-4">

                            <div class="details-label">
                                الموظف
                            </div>

                            <div class="details-value">
                                ${
                                    valueOrDash(
                                        employee.full_name ||
                                        employee.name ||
                                        item.employee_name
                                    )
                                }
                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="details-label">
                                الرقم الوظيفي
                            </div>

                            <div
                                class="details-value"
                                dir="ltr"
                            >
                                ${
                                    valueOrDash(
                                        employee.employee_number ||
                                        item.employee_number
                                    )
                                }
                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="details-label">
                                القسم
                            </div>

                            <div class="details-value">
                                ${
                                    valueOrDash(
                                        employee.department?.name ||
                                        employee.department_name
                                    )
                                }
                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="details-label">
                                الفترة
                            </div>

                            <div class="details-value">
                                ${valueOrDash(period.name)}
                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="details-label">
                                رقم المسير
                            </div>

                            <div class="details-value">
                                ${valueOrDash(run.run_number)}
                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="details-label">
                                الحالة
                            </div>

                            <div class="details-value">
                                ${statusBadge(item.status)}
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <div class="row g-3 mb-4">

                <div class="col-md-3">

                    <div class="total-box">

                        <div class="small mb-2">
                            الراتب الأساسي
                        </div>

                        <div class="fw-bold fs-5 money-value">

                            ${money(
                                item.basic_salary,
                                currency
                            )}

                        </div>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="total-box earning">

                        <div class="small mb-2">
                            إجمالي الاستحقاقات
                        </div>

                        <div class="fw-bold fs-5 money-value">

                            ${money(
                                item.total_earnings,
                                currency
                            )}

                        </div>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="total-box deduction">

                        <div class="small mb-2">
                            إجمالي الخصومات
                        </div>

                        <div class="fw-bold fs-5 money-value">

                            ${money(
                                item.total_deductions,
                                currency
                            )}

                        </div>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="total-box net">

                        <div class="small mb-2">
                            صافي الراتب
                        </div>

                        <div class="fw-bold fs-5 money-value">

                            ${money(
                                item.net_salary,
                                currency
                            )}

                        </div>

                    </div>

                </div>

            </div>


            <div class="details-section mb-0">

                <div class="details-section-title">
                    تفاصيل مكونات الراتب
                </div>

                <div class="table-responsive">

                    <table class="table component-table mb-0">

                        <thead>

                            <tr>
                                <th>#</th>
                                <th>المكون</th>
                                <th>النوع</th>
                                <th>التصنيف</th>
                                <th>المبلغ</th>
                            </tr>

                        </thead>

                        <tbody>

                            ${renderComponents(
                                components,
                                currency
                            )}

                        </tbody>

                    </table>

                </div>

            </div>

        `);

    }


    function openDetails(id) {

        currentPayslipId = id;


        $('#modalPrintButton')
            .prop('disabled', true);


        $('#modalSubtitle').text('');


        $('#payslipDetailsBody').html(`

            <div class="payslip-loading">
                جاري تحميل تفاصيل القسيمة...
            </div>

        `);


        $('#payslipDetailsModal').show();

        $('body').addClass(
            'payslip-modal-open'
        );


        $.ajax({

            url: payslipUrl(
                routes.show,
                id
            ),

            type: 'GET',

            success: function (response) {

                const item =
                    response.payslip ||
                    response.data ||
                    response.payroll_item ||
                    response;


                renderDetails(item);


                $('#modalPrintButton')
                    .prop('disabled', false);

            },

            error: function (xhr) {

                closeDetails();
                showAjaxError(xhr);

            },

        });

    }


    function closeDetails() {

        $('#payslipDetailsModal').hide();

        $('body').removeClass(
            'payslip-modal-open'
        );

        currentPayslipId = null;

    }


    $(function () {

        loadOptions();
        loadPayslips(1);


        $('#searchButton').on(
            'click',
            function () {
                loadPayslips(1);
            }
        );


        $('#searchFilter').on(
            'keydown',
            function (event) {

                if (event.key === 'Enter') {

                    event.preventDefault();
                    loadPayslips(1);

                }

            }
        );


        $('#periodFilter, #statusFilter, #perPageFilter').on(
            'change',
            function () {
                loadPayslips(1);
            }
        );


        $('#resetButton').on(
            'click',
            function () {

                $('#searchFilter').val('');
                $('#periodFilter').val('');
                $('#statusFilter').val('');
                $('#perPageFilter').val('15');

                loadPayslips(1);

            }
        );


        $(document).on(
            'click',
            '.page-button',
            function () {

                const page =
                    Number($(this).data('page'));

                if (page > 0) {
                    loadPayslips(page);
                }

            }
        );


        $(document).on(
            'click',
            '.btn-details',
            function () {

                openDetails(
                    $(this).data('id')
                );

            }
        );


        $(document).on(
            'click',
            '.btn-print',
            function () {

                const id =
                    $(this).data('id');

                window.open(
                    payslipUrl(
                        routes.print,
                        id
                    ),
                    '_blank'
                );

            }
        );


        $('#modalPrintButton').on(
            'click',
            function () {

                if (!currentPayslipId) {
                    return;
                }

                window.open(
                    payslipUrl(
                        routes.print,
                        currentPayslipId
                    ),
                    '_blank'
                );

            }
        );


        $(document).on(
            'click',
            '.btn-close-payslip',
            function () {
                closeDetails();
            }
        );


        $('#payslipDetailsModal').on(
            'click',
            function (event) {

                if (
                    event.target === this
                ) {
                    closeDetails();
                }

            }
        );


        $(document).on(
            'keydown',
            function (event) {

                if (
                    event.key === 'Escape' &&
                    $('#payslipDetailsModal').is(':visible')
                ) {
                    closeDetails();
                }

            }
        );

    });

})(jQuery);
</script>
@endpush