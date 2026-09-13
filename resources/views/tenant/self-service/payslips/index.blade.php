@extends('layouts.tenant')

@section('title', 'قسائم رواتبي')
@section('page-title', 'قسائم رواتبي')

@php
    $payslipRoutes = [
        'data' => route(
            'app.self-service.payslips.data'
        ),

        'options' => route(
            'app.self-service.payslips.options'
        ),

        'show' => route(
            'app.self-service.payslips.show',
            ['payrollItem' => '__ID__']
        ),

        'print' => route(
            'app.self-service.payslips.print',
            ['payrollItem' => '__ID__']
        ),
    ];

    $employeeName = $employee
        ? trim(
            collect([
                $employee->first_name,
                $employee->father_name,
                $employee->grandfather_name,
                $employee->family_name,
            ])
                ->filter()
                ->implode(' ')
        )
        : null;
@endphp

@push('styles')
<style>
    .payslip-hero {
        position: relative;
        overflow: hidden;
        border: 0;
        border-radius: 22px;
        color: #fff;
        background:
            linear-gradient(
                135deg,
                #0b1f45,
                #1769ff
            );
        box-shadow:
            0 14px 36px
            rgba(23, 105, 255, .2);
    }

    .payslip-hero::before,
    .payslip-hero::after {
        content: "";
        position: absolute;
        border-radius: 50%;
        background:
            rgba(255, 255, 255, .08);
    }

    .payslip-hero::before {
        width: 230px;
        height: 230px;
        top: -120px;
        left: -70px;
    }

    .payslip-hero::after {
        width: 130px;
        height: 130px;
        bottom: -70px;
        right: 25%;
    }

    .hero-content {
        position: relative;
        z-index: 2;
        padding: 28px;
    }

    .hero-label {
        color: rgba(255, 255, 255, .72);
        font-size: .84rem;
    }

    .hero-name {
        margin: 8px 0 5px;
        font-size: 1.45rem;
        font-weight: 800;
    }

    .hero-number {
        color: rgba(255, 255, 255, .78);
    }

    .stat-card {
        border: 0;
        border-radius: 18px;
        box-shadow:
            0 7px 24px
            rgba(15, 23, 42, .06);
    }

    .stat-label {
        color: #64748b;
        font-size: .8rem;
        margin-bottom: 8px;
    }

    .stat-value {
        color: #0f172a;
        font-size: 1.2rem;
        font-weight: 800;
    }

    .filter-card {
        border: 0;
        border-radius: 18px;
        box-shadow:
            0 7px 24px
            rgba(15, 23, 42, .06);
    }

    .payslip-card {
        position: relative;
        height: 100%;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        background: #fff;
        transition:
            transform .2s ease,
            box-shadow .2s ease;
    }

    .payslip-card:hover {
        transform: translateY(-3px);
        box-shadow:
            0 14px 32px
            rgba(15, 23, 42, .09);
    }

    .payslip-card-header {
        padding: 18px;
        border-bottom: 1px solid #edf1f5;
        background: #f8fafc;
    }

    .payslip-card-body {
        padding: 18px;
    }

    .period-name {
        color: #0f172a;
        font-size: 1rem;
        font-weight: 800;
    }

    .run-number {
        margin-top: 4px;
        color: #64748b;
        font-size: .78rem;
    }

    .salary-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        padding: 10px 0;
        border-bottom: 1px dashed #e2e8f0;
    }

    .salary-row:last-child {
        border-bottom: 0;
    }

    .salary-label {
        color: #64748b;
        font-size: .82rem;
    }

    .salary-value {
        direction: ltr;
        display: inline-block;
        color: #0f172a;
        font-weight: 800;
        white-space: nowrap;
    }

    .salary-value.earning {
        color: #047857;
    }

    .salary-value.deduction {
        color: #be123c;
    }

    .salary-value.net {
        color: #1d4ed8;
        font-size: 1rem;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6px 12px;
        border-radius: 999px;
        font-size: .78rem;
        font-weight: 800;
    }

    .status-approved {
        background: #dcfce7;
        color: #15803d;
    }

    .status-paid {
        background: #d1fae5;
        color: #047857;
    }

    .empty-state {
        padding: 65px 20px;
        text-align: center;
        color: #64748b;
        border: 1px dashed #cbd5e1;
        border-radius: 18px;
        background: #fff;
    }

    .loading-state {
        padding: 65px 20px;
        text-align: center;
        color: #64748b;
    }

    /*
    |--------------------------------------------------------------------------
    | موديل التفاصيل
    |--------------------------------------------------------------------------
    */

    .self-payslip-modal {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 1095;
        overflow-x: hidden;
        overflow-y: auto;
        padding: 24px 12px;
        background: rgba(15, 23, 42, .65);
    }

    .self-payslip-dialog {
        width: 100%;
        max-width: 980px;
        min-height: calc(100vh - 48px);
        margin: 0 auto;
        display: flex;
        align-items: center;
    }

    .self-payslip-content {
        width: 100%;
        max-height: calc(100vh - 48px);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        border-radius: 20px;
        background: #fff;
        box-shadow:
            0 25px 75px
            rgba(15, 23, 42, .3);
    }

    .self-payslip-header,
    .self-payslip-footer {
        flex: 0 0 auto;
        padding: 18px 22px;
        background: #fff;
    }

    .self-payslip-header {
        border-bottom: 1px solid #e2e8f0;
    }

    .self-payslip-footer {
        border-top: 1px solid #e2e8f0;
    }

    .self-payslip-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        overscroll-behavior: contain;
        padding: 22px;
    }

    body.self-payslip-open {
        overflow: hidden;
    }

    .modal-close-button {
        width: 38px;
        height: 38px;
        border: 0;
        border-radius: 11px;
        background: #f1f5f9;
        color: #0f172a;
        font-size: 1.3rem;
    }

    .detail-section {
        overflow: hidden;
        margin-bottom: 18px;
        border: 1px solid #e2e8f0;
        border-radius: 15px;
    }

    .detail-section-title {
        padding: 13px 16px;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
        font-weight: 800;
    }

    .detail-section-body {
        padding: 16px;
    }

    .detail-label {
        margin-bottom: 5px;
        color: #64748b;
        font-size: .78rem;
    }

    .detail-value {
        color: #0f172a;
        font-weight: 700;
    }

    .total-box {
        height: 100%;
        padding: 15px;
        border-radius: 14px;
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
        white-space: nowrap;
        background: #f8fafc;
    }

    @media (max-width: 767.98px) {
        .self-payslip-modal {
            padding: 0;
        }

        .self-payslip-dialog {
            min-height: 100vh;
        }

        .self-payslip-content {
            min-height: 100vh;
            max-height: 100vh;
            border-radius: 0;
        }

        .self-payslip-header,
        .self-payslip-footer,
        .self-payslip-body {
            padding: 16px;
        }
    }
</style>
@endpush

@section('content')

@if(!$employee)

    <div class="alert alert-danger">
        لا يوجد ملف موظف مرتبط بهذا المستخدم.
        يرجى التواصل مع إدارة الموارد البشرية.
    </div>

@endif


<div id="pageAlert"></div>


<div class="card payslip-hero mb-4">

    <div class="hero-content">

        <div class="row align-items-center g-4">

            <div class="col-lg-7">

                <div class="hero-label">
                    قسائم الرواتب الخاصة بي
                </div>

                <div class="hero-name">

                    {{ $employeeName ?: auth()->user()->name }}

                </div>

                <div class="hero-number" dir="ltr">

                    {{ $employee?->employee_number ?? '-' }}

                </div>

            </div>


            <div class="col-lg-5">

                <div class="row g-3">

                    <div class="col-6">

                        <div class="p-3 rounded-4 bg-white bg-opacity-10">

                            <div class="hero-label mb-2">
                                آخر صافي راتب
                            </div>

                            <div
                                id="lastNetSalary"
                                class="fw-bold fs-5"
                                dir="ltr"
                            >
                                -
                            </div>

                        </div>

                    </div>


                    <div class="col-6">

                        <div class="p-3 rounded-4 bg-white bg-opacity-10">

                            <div class="hero-label mb-2">
                                عدد القسائم
                            </div>

                            <div
                                id="totalPayslips"
                                class="fw-bold fs-5"
                            >
                                0
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<div class="row g-3 mb-4">

    <div class="col-md-4">

        <div class="card stat-card h-100">

            <div class="card-body">

                <div class="stat-label">
                    مجموع صافي الرواتب
                </div>

                <div
                    id="totalNet"
                    class="stat-value text-success"
                    dir="ltr"
                >
                    -
                </div>

            </div>

        </div>

    </div>


    <div class="col-md-4">

        <div class="card stat-card h-100">

            <div class="card-body">

                <div class="stat-label">
                    مجموع الاستحقاقات
                </div>

                <div
                    id="totalEarnings"
                    class="stat-value text-primary"
                    dir="ltr"
                >
                    -
                </div>

            </div>

        </div>

    </div>


    <div class="col-md-4">

        <div class="card stat-card h-100">

            <div class="card-body">

                <div class="stat-label">
                    مجموع الخصومات
                </div>

                <div
                    id="totalDeductions"
                    class="stat-value text-danger"
                    dir="ltr"
                >
                    -
                </div>

            </div>

        </div>

    </div>

</div>


<div class="card filter-card mb-4">

    <div class="card-body p-4">

        <div class="row g-3 align-items-end">

            <div class="col-md-5">

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


            <div class="col-md-3">

                <label
                    for="statusFilter"
                    class="form-label"
                >
                    حالة القسيمة
                </label>

                <select
                    id="statusFilter"
                    class="form-select"
                >
                    <option value="">
                        جميع الحالات
                    </option>

                    <option value="approved">
                        معتمدة
                    </option>

                    <option value="paid">
                        مدفوعة
                    </option>
                </select>

            </div>


            <div class="col-md-2">

                <label
                    for="perPageFilter"
                    class="form-label"
                >
                    عدد القسائم
                </label>

                <select
                    id="perPageFilter"
                    class="form-select"
                >
                    <option value="6">
                        6
                    </option>

                    <option value="12" selected>
                        12
                    </option>

                    <option value="24">
                        24
                    </option>
                </select>

            </div>


            <div class="col-md-2">

                <button
                    type="button"
                    id="resetFilters"
                    class="btn btn-outline-secondary w-100"
                >
                    إعادة الفلاتر
                </button>

            </div>

        </div>

    </div>

</div>


<div
    id="payslipCards"
    class="row g-4"
>

    <div class="col-12">

        <div class="loading-state">
            جاري تحميل قسائم الرواتب...
        </div>

    </div>

</div>


<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-4">

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


<div
    id="payslipModal"
    class="self-payslip-modal"
>

    <div class="self-payslip-dialog">

        <div class="self-payslip-content">

            <div class="self-payslip-header">

                <div class="d-flex justify-content-between align-items-center gap-3">

                    <div>

                        <h5 class="mb-1">
                            تفاصيل قسيمة الراتب
                        </h5>

                        <div
                            id="modalPeriodName"
                            class="text-muted small"
                        ></div>

                    </div>

                    <button
                        type="button"
                        class="modal-close-button close-payslip-modal"
                    >
                        ×
                    </button>

                </div>

            </div>


            <div
                id="payslipModalBody"
                class="self-payslip-body"
            >
                جاري تحميل البيانات...
            </div>


            <div class="self-payslip-footer">

                <div class="d-flex justify-content-end gap-2">

                    <button
                        type="button"
                        class="btn btn-light close-payslip-modal"
                    >
                        إغلاق
                    </button>

                    <button
                        type="button"
                        id="printPayslipButton"
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


    function numericValue(value) {

        const number = Number(value);

        return Number.isFinite(number)
            ? number
            : 0;

    }


    function money(value, currency) {

        const amount =
            numericValue(value);

        return new Intl.NumberFormat(
            'ar-SA',
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }
        ).format(amount) +
            ' ' +
            escapeHtml(currency || 'SAR');

    }


    function buildUrl(template, id) {

        return template.replace(
            '__ID__',
            encodeURIComponent(id)
        );

    }


    function statusLabel(status) {

        const labels = {
            approved: 'معتمدة',
            paid: 'مدفوعة',
        };

        return labels[status] || status || '-';

    }


    function statusBadge(status) {

        return `

            <span class="status-badge status-${escapeHtml(status)}">

                ${escapeHtml(
                    statusLabel(status)
                )}

            </span>

        `;

    }


    function showAlert(message) {

        $('#pageAlert').html(`

            <div class="alert alert-danger">

                ${escapeHtml(message)}

            </div>

        `);

    }


    function showAjaxError(xhr) {

        let message =
            xhr.responseJSON?.message ||
            'حدث خطأ أثناء تحميل البيانات.';

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

        showAlert(message);

    }


    function loadOptions() {

        $.ajax({

            url: routes.options,

            type: 'GET',

            success: function (response) {

                const periods =
                    response.periods || [];

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

            },

            error: function (xhr) {
                showAjaxError(xhr);
            },

        });

    }


    function renderCards(records) {

        if (!records.length) {

            $('#payslipCards').html(`

                <div class="col-12">

                    <div class="empty-state">

                        لا توجد قسائم رواتب متاحة حاليًا.

                    </div>

                </div>

            `);

            return;
        }


        let html = '';


        $.each(
            records,
            function (index, item) {

                const period =
                    item.payroll_period || {};

                const run =
                    item.payroll_run || {};

                const currency =
                    item.currency_code ||
                    run.currency_code ||
                    'SAR';


                html += `

                    <div class="col-xl-4 col-md-6">

                        <div class="payslip-card">

                            <div class="payslip-card-header">

                                <div class="d-flex justify-content-between align-items-start gap-3">

                                    <div>

                                        <div class="period-name">

                                            ${valueOrDash(
                                                period.name
                                            )}

                                        </div>

                                        <div class="run-number">

                                            رقم المسير:
                                            ${valueOrDash(
                                                run.run_number
                                            )}

                                        </div>

                                    </div>

                                    ${statusBadge(item.status)}

                                </div>

                            </div>


                            <div class="payslip-card-body">

                                <div class="salary-row">

                                    <span class="salary-label">
                                        الراتب الأساسي
                                    </span>

                                    <span class="salary-value">

                                        ${money(
                                            item.basic_salary,
                                            currency
                                        )}

                                    </span>

                                </div>


                                <div class="salary-row">

                                    <span class="salary-label">
                                        إجمالي الاستحقاقات
                                    </span>

                                    <span class="salary-value earning">

                                        ${money(
                                            item.total_earnings,
                                            currency
                                        )}

                                    </span>

                                </div>


                                <div class="salary-row">

                                    <span class="salary-label">
                                        الخصومات
                                    </span>

                                    <span class="salary-value deduction">

                                        ${money(
                                            item.total_deductions,
                                            currency
                                        )}

                                    </span>

                                </div>


                                <div class="salary-row">

                                    <span class="salary-label">
                                        صافي الراتب
                                    </span>

                                    <span class="salary-value net">

                                        ${money(
                                            item.net_salary,
                                            currency
                                        )}

                                    </span>

                                </div>


                                <div class="d-flex gap-2 mt-3">

                                    <button
                                        type="button"
                                        class="btn btn-primary flex-grow-1 btn-show-payslip"
                                        data-id="${escapeHtml(item.id)}"
                                    >
                                        عرض التفاصيل
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary btn-print-payslip"
                                        data-id="${escapeHtml(item.id)}"
                                    >
                                        طباعة
                                    </button>

                                </div>

                            </div>

                        </div>

                    </div>

                `;

            }
        );


        $('#payslipCards').html(html);

    }


    function renderSummary(response, records) {

        const summary =
            response.summary || {};

        const currency =
            summary.currency_code ||
            records[0]?.currency_code ||
            'SAR';


        $('#totalPayslips').text(
            response.total || 0
        );


        $('#totalNet').html(

            money(
                summary.total_net_salary,
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


        if (records.length) {

            $('#lastNetSalary').html(

                money(
                    records[0].net_salary,
                    records[0].currency_code ||
                    currency
                )

            );

        } else {

            $('#lastNetSalary').text('-');

        }

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


        $('#paginationInfo').text(

            total
                ? `عرض ${from} إلى ${to} من ${total}`
                : 'لا توجد بيانات'

        );


        let html = '';


        html += `

            <button
                type="button"
                class="btn btn-sm btn-outline-secondary page-button"
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
                    class="btn btn-sm ${
                        page === current
                            ? 'btn-primary'
                            : 'btn-outline-secondary'
                    } page-button"
                    data-page="${page}"
                >
                    ${page}
                </button>

            `;

        }


        html += `

            <button
                type="button"
                class="btn btn-sm btn-outline-secondary page-button"
                data-page="${current + 1}"
                ${current >= last ? 'disabled' : ''}
            >
                التالي
            </button>

        `;


        $('#paginationButtons').html(html);

    }


    function loadPayslips(page) {

        currentPage = page || 1;


        $('#payslipCards').html(`

            <div class="col-12">

                <div class="loading-state">
                    جاري تحميل قسائم الرواتب...
                </div>

            </div>

        `);


        $.ajax({

            url: routes.data,

            type: 'GET',

            data: {
                page:
                    currentPage,

                payroll_period_id:
                    $('#periodFilter').val(),

                status:
                    $('#statusFilter').val(),

                per_page:
                    $('#perPageFilter').val(),
            },

            success: function (response) {

                const records =
                    response.data || [];

                renderCards(records);

                renderSummary(
                    response,
                    records
                );

                renderPagination(response);

            },

            error: function (xhr) {

                renderCards([]);
                showAjaxError(xhr);

            },

        });

    }


    function componentRows(
        components,
        currency
    ) {

        if (!components.length) {

            return `

                <tr>

                    <td
                        colspan="4"
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

                                ${valueOrDash(
                                    component.component_name
                                )}

                            </div>

                            <div class="text-muted small">

                                ${valueOrDash(
                                    component.component_code
                                )}

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

                            <span
                                class="fw-bold"
                                dir="ltr"
                            >

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

        const period =
            item.payroll_period || {};

        const run =
            item.payroll_run || {};

        const employee =
            item.employee || {};

        const currency =
            item.currency_code ||
            run.currency_code ||
            'SAR';

        const components =
            item.components || [];


        $('#modalPeriodName').text(

            period.name ||
            period.code ||
            ''

        );


        $('#payslipModalBody').html(`

            <div class="detail-section">

                <div class="detail-section-title">
                    بيانات القسيمة
                </div>

                <div class="detail-section-body">

                    <div class="row g-3">

                        <div class="col-md-4">

                            <div class="detail-label">
                                الموظف
                            </div>

                            <div class="detail-value">
                                ${valueOrDash(employee.full_name)}
                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="detail-label">
                                الرقم الوظيفي
                            </div>

                            <div
                                class="detail-value"
                                dir="ltr"
                            >
                                ${valueOrDash(employee.employee_number)}
                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="detail-label">
                                الحالة
                            </div>

                            <div>
                                ${statusBadge(item.status)}
                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="detail-label">
                                فترة الرواتب
                            </div>

                            <div class="detail-value">
                                ${valueOrDash(period.name)}
                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="detail-label">
                                رقم المسير
                            </div>

                            <div class="detail-value">
                                ${valueOrDash(run.run_number)}
                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="detail-label">
                                تاريخ الدفع
                            </div>

                            <div class="detail-value">
                                ${valueOrDash(period.payment_date)}
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

                        <div class="fw-bold" dir="ltr">

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
                            إجمالي الراتب
                        </div>

                        <div class="fw-bold" dir="ltr">

                            ${money(
                                item.gross_salary,
                                currency
                            )}

                        </div>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="total-box deduction">

                        <div class="small mb-2">
                            الخصومات
                        </div>

                        <div class="fw-bold" dir="ltr">

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

                        <div class="fw-bold" dir="ltr">

                            ${money(
                                item.net_salary,
                                currency
                            )}

                        </div>

                    </div>

                </div>

            </div>


            <div class="detail-section mb-0">

                <div class="detail-section-title">
                    تفاصيل مكونات الراتب
                </div>

                <div class="table-responsive">

                    <table class="table component-table mb-0">

                        <thead>

                            <tr>
                                <th>#</th>
                                <th>المكون</th>
                                <th>النوع</th>
                                <th>المبلغ</th>
                            </tr>

                        </thead>

                        <tbody>

                            ${componentRows(
                                components,
                                currency
                            )}

                        </tbody>

                    </table>

                </div>

            </div>

        `);

    }


    function openPayslip(id) {

        currentPayslipId = id;


        $('#printPayslipButton')
            .prop('disabled', true);


        $('#payslipModalBody').html(`

            <div class="loading-state">
                جاري تحميل تفاصيل القسيمة...
            </div>

        `);


        $('#payslipModal').show();

        $('body').addClass(
            'self-payslip-open'
        );


        $.ajax({

            url: buildUrl(
                routes.show,
                id
            ),

            type: 'GET',

            success: function (response) {

                renderDetails(
                    response.payslip ||
                    response.data ||
                    response
                );

                $('#printPayslipButton')
                    .prop('disabled', false);

            },

            error: function (xhr) {

                closePayslip();
                showAjaxError(xhr);

            },

        });

    }


    function closePayslip() {

        $('#payslipModal').hide();

        $('body').removeClass(
            'self-payslip-open'
        );

        currentPayslipId = null;

    }


    $(function () {

        loadOptions();
        loadPayslips(1);


        $('#periodFilter, #statusFilter, #perPageFilter').on(
            'change',
            function () {
                loadPayslips(1);
            }
        );


        $('#resetFilters').on(
            'click',
            function () {

                $('#periodFilter').val('');
                $('#statusFilter').val('');
                $('#perPageFilter').val('12');

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
            '.btn-show-payslip',
            function () {

                openPayslip(
                    $(this).data('id')
                );

            }
        );


        $(document).on(
            'click',
            '.btn-print-payslip',
            function () {

                window.open(
                    buildUrl(
                        routes.print,
                        $(this).data('id')
                    ),
                    '_blank'
                );

            }
        );


        $('#printPayslipButton').on(
            'click',
            function () {

                if (!currentPayslipId) {
                    return;
                }

                window.open(
                    buildUrl(
                        routes.print,
                        currentPayslipId
                    ),
                    '_blank'
                );

            }
        );


        $('.close-payslip-modal').on(
            'click',
            function () {
                closePayslip();
            }
        );


        $('#payslipModal').on(
            'click',
            function (event) {

                if (event.target === this) {
                    closePayslip();
                }

            }
        );


        $(document).on(
            'keydown',
            function (event) {

                if (
                    event.key === 'Escape' &&
                    $('#payslipModal').is(':visible')
                ) {
                    closePayslip();
                }

            }
        );

    });

})(jQuery);
</script>
@endpush