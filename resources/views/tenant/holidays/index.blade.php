@extends('layouts.tenant')

@section('title', 'العطلات الرسمية')
@section('page-title', 'العطلات الرسمية')

@section('content')

@php
    $canManageHolidays =
        auth()->user()->can('attendance.manage') ||
        auth()->user()->can('leave.manage');
@endphp

<style>
    .holidays-page {
        direction: rtl;
    }

    .holiday-stat-card,
    .holiday-filter-card,
    .holiday-table-card {
        border: 0;
        border-radius: 18px;
        box-shadow: 0 8px 25px rgba(15, 23, 42, .06);
    }

    .holiday-stat-icon {
        width: 48px;
        height: 48px;
        display: grid;
        place-items: center;
        border-radius: 14px;
        background: #e8f0ff;
        color: #0d6efd;
        font-size: 22px;
        flex-shrink: 0;
    }

    .holiday-table th {
        white-space: nowrap;
        color: #475569;
        font-size: 13px;
        font-weight: 700;
    }

    .holiday-table td {
        vertical-align: middle;
        white-space: nowrap;
    }

    .holiday-name {
        white-space: normal;
        min-width: 180px;
    }

    .holiday-code {
        direction: ltr;
        display: inline-block;
        font-size: 12px;
        color: #64748b;
    }

    .holiday-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 20px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 700;
    }

    .holiday-badge-primary {
        background: #e8f0ff;
        color: #0d6efd;
    }

    .holiday-badge-success {
        background: #dcfce7;
        color: #15803d;
    }

    .holiday-badge-warning {
        background: #fef3c7;
        color: #a16207;
    }

    .holiday-badge-danger {
        background: #fee2e2;
        color: #b91c1c;
    }

    .holiday-badge-secondary {
        background: #f1f5f9;
        color: #475569;
    }

    .holiday-property-list {
        display: flex;
        align-items: center;
        gap: 5px;
        flex-wrap: wrap;
        white-space: normal;
        min-width: 210px;
    }

    .holiday-property {
        padding: 4px 7px;
        background: #f1f5f9;
        color: #475569;
        border-radius: 7px;
        font-size: 11px;
    }

    .holiday-action-buttons {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .holiday-action-buttons button {
        white-space: nowrap;
    }

    .holiday-empty-state {
        padding: 55px 15px;
        text-align: center;
        color: #64748b;
    }

    .holiday-empty-state .empty-symbol {
        width: 64px;
        height: 64px;
        margin: 0 auto 14px;
        display: grid;
        place-items: center;
        border-radius: 18px;
        background: #f1f5f9;
        font-size: 28px;
    }

    .holiday-loading {
        padding: 50px;
        text-align: center;
        color: #64748b;
    }

    .holiday-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        flex-wrap: wrap;
    }

    .holiday-pagination-buttons {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }

    .holiday-pagination-buttons button.active {
        background: #0d6efd;
        border-color: #0d6efd;
        color: #fff;
    }

    /*
    |--------------------------------------------------------------------------
    | Custom modal
    |--------------------------------------------------------------------------
    */

    body.ry-modal-open {
        overflow: hidden;
    }

    .ry-modal {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 24px;
        overflow-y: auto;
        background: rgba(15, 23, 42, .65);
    }

    .ry-modal-panel {
        width: 100%;
        max-width: 900px;
        max-height: calc(100vh - 48px);
        margin: auto;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 25px 60px rgba(15, 23, 42, .25);
    }

    .ry-modal-panel.ry-modal-sm {
        max-width: 520px;
    }

    .ry-modal-header {
        flex-shrink: 0;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 15px;
        padding: 20px 24px;
        border-bottom: 1px solid #e2e8f0;
    }

    .ry-modal-body {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        overscroll-behavior: contain;
        padding: 24px;
    }

    .ry-modal-footer {
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        padding: 16px 24px;
        border-top: 1px solid #e2e8f0;
        background: #fff;
    }

    .ry-modal-close {
        width: 38px;
        height: 38px;
        display: grid;
        place-items: center;
        border: 0;
        border-radius: 11px;
        background: #f1f5f9;
        color: #334155;
        font-size: 20px;
    }

    .ry-modal-close:hover {
        background: #e2e8f0;
    }

    .holiday-switch-card {
        height: 100%;
        padding: 15px;
        border: 1px solid #e2e8f0;
        border-radius: 13px;
        background: #f8fafc;
    }

    .holiday-switch-card .form-check-input {
        width: 42px;
        height: 22px;
        cursor: pointer;
    }

    .required-star {
        color: #dc3545;
    }

    .field-error {
        display: block;
        margin-top: 5px;
        color: #dc3545;
        font-size: 12px;
    }

    @media (max-width: 767.98px) {
        .ry-modal {
            padding: 10px;
            align-items: flex-start;
        }

        .ry-modal-panel {
            max-height: calc(100vh - 20px);
            border-radius: 14px;
        }

        .ry-modal-header,
        .ry-modal-body,
        .ry-modal-footer {
            padding: 16px;
        }

        .holiday-pagination {
            justify-content: center;
        }
    }
</style>

<div class="holidays-page">

    <div id="pageAlert" class="alert d-none mb-4"></div>

    <div class="card holiday-stat-card mb-4">
        <div class="card-body p-4">

            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">

                <div class="d-flex align-items-center gap-3">

                    <div class="holiday-stat-icon">
                        ◫
                    </div>

                    <div>
                        <h5 class="mb-1">إدارة العطلات الرسمية</h5>

                        <p class="text-muted mb-0">
                            إدارة العطلات وربطها بالحضور والإجازات والفروع.
                        </p>
                    </div>

                </div>

                @if($canManageHolidays)
                    <button
                        type="button"
                        class="btn btn-primary px-4"
                        id="btnAddHoliday"
                    >
                        + إضافة عطلة
                    </button>
                @endif

            </div>

        </div>
    </div>

    <div class="card holiday-filter-card mb-4">
        <div class="card-body p-4">

            <div class="row g-3">

                <div class="col-xl-3 col-md-6">
                    <label class="form-label">البحث</label>

                    <input
                        type="text"
                        class="form-control"
                        id="filterSearch"
                        placeholder="اسم العطلة أو الكود"
                    >
                </div>

                <div class="col-xl-2 col-md-6">
                    <label class="form-label">النوع</label>

                    <select class="form-select" id="filterType">
                        <option value="">جميع الأنواع</option>
                    </select>
                </div>

                <div class="col-xl-2 col-md-6">
                    <label class="form-label">الفرع</label>

                    <select class="form-select" id="filterBranch">
                        <option value="">جميع الفروع</option>
                    </select>
                </div>

                <div class="col-xl-2 col-md-6">
                    <label class="form-label">السنة</label>

                    <select class="form-select" id="filterYear"></select>
                </div>

                <div class="col-xl-1 col-md-6">
                    <label class="form-label">الحالة</label>

                    <select class="form-select" id="filterStatus">
                        <option value="">الكل</option>
                        <option value="active">نشطة</option>
                        <option value="inactive">غير نشطة</option>
                    </select>
                </div>

                <div class="col-xl-2 col-md-6">
                    <label class="form-label">عدد السجلات</label>

                    <select class="form-select" id="filterPerPage">
                        <option value="10">10</option>
                        <option value="15" selected>15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>

                <div class="col-12">
                    <div class="d-flex gap-2 flex-wrap">

                        <button
                            type="button"
                            class="btn btn-primary px-4"
                            id="btnSearch"
                        >
                            بحث
                        </button>

                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            id="btnReset"
                        >
                            إعادة تعيين
                        </button>

                    </div>
                </div>

            </div>

        </div>
    </div>

    <div class="card holiday-table-card">
        <div class="card-body p-0">

            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap p-4 border-bottom">

                <div>
                    <h6 class="mb-1">قائمة العطلات</h6>

                    <small class="text-muted">
                        العطلات المسجلة داخل الشركة.
                    </small>
                </div>

                <span
                    class="holiday-badge holiday-badge-primary"
                    id="holidaysCount"
                >
                    0 عطلة
                </span>

            </div>

            <div class="table-responsive">

                <table class="table holiday-table table-hover align-middle mb-0">

                    <thead class="table-light">
                        <tr>
                            <th class="px-4">#</th>
                            <th>العطلة</th>
                            <th>النوع</th>
                            <th>نطاق التطبيق</th>
                            <th>الفترة</th>
                            <th>الخصائص</th>
                            <th>الحالة</th>
                            <th class="px-4">الإجراءات</th>
                        </tr>
                    </thead>

                    <tbody id="holidaysTableBody">

                        <tr>
                            <td colspan="8" class="holiday-loading">
                                جاري تحميل البيانات...
                            </td>
                        </tr>

                    </tbody>

                </table>

            </div>

            <div class="p-4 border-top holiday-pagination">

                <div class="text-muted small" id="paginationInfo">
                    لا توجد بيانات.
                </div>

                <div
                    class="holiday-pagination-buttons"
                    id="paginationButtons"
                ></div>

            </div>

        </div>
    </div>

</div>

@if($canManageHolidays)

    <div class="ry-modal" id="holidayFormModal">

        <div class="ry-modal-panel">

            <div class="ry-modal-header">

                <div>
                    <h5 class="mb-1" id="holidayModalTitle">
                        إضافة عطلة
                    </h5>

                    <small class="text-muted">
                        أدخل بيانات العطلة وسياسة تأثيرها على النظام.
                    </small>
                </div>

                <button
                    type="button"
                    class="ry-modal-close btn-close-holiday-modal"
                >
                    ×
                </button>

            </div>

            <form id="holidayForm">

                <div class="ry-modal-body">

                    <div
                        id="holidayFormAlert"
                        class="alert alert-danger d-none"
                    ></div>

                    <input type="hidden" id="holidayId">

                    <div class="row g-3">

                        <div class="col-md-4">
                            <label class="form-label">
                                كود العطلة
                                <span class="required-star">*</span>
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                name="code"
                                id="holidayCode"
                                maxlength="50"
                                dir="ltr"
                                required
                            >

                            <span
                                class="field-error"
                                data-error-for="code"
                            ></span>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">
                                اسم العطلة
                                <span class="required-star">*</span>
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                name="name"
                                id="holidayName"
                                maxlength="255"
                                required
                            >

                            <span
                                class="field-error"
                                data-error-for="name"
                            ></span>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">
                                الاسم بالإنجليزية
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                name="name_en"
                                id="holidayNameEn"
                                maxlength="255"
                                dir="ltr"
                            >

                            <span
                                class="field-error"
                                data-error-for="name_en"
                            ></span>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">
                                نوع العطلة
                                <span class="required-star">*</span>
                            </label>

                            <select
                                class="form-select"
                                name="type"
                                id="holidayType"
                                required
                            ></select>

                            <span
                                class="field-error"
                                data-error-for="type"
                            ></span>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">
                                الفرع
                            </label>

                            <select
                                class="form-select"
                                name="branch_id"
                                id="holidayBranch"
                            >
                                <option value="">
                                    جميع فروع الشركة
                                </option>
                            </select>

                            <div class="form-text">
                                اتركه فارغًا لتطبيق العطلة على جميع الفروع.
                            </div>

                            <span
                                class="field-error"
                                data-error-for="branch_id"
                            ></span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                تاريخ البداية
                                <span class="required-star">*</span>
                            </label>

                            <input
                                type="date"
                                class="form-control"
                                name="start_date"
                                id="holidayStartDate"
                                required
                            >

                            <span
                                class="field-error"
                                data-error-for="start_date"
                            ></span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                تاريخ النهاية
                                <span class="required-star">*</span>
                            </label>

                            <input
                                type="date"
                                class="form-control"
                                name="end_date"
                                id="holidayEndDate"
                                required
                            >

                            <span
                                class="field-error"
                                data-error-for="end_date"
                            ></span>
                        </div>

                        <div class="col-12">
                            <hr class="my-2">
                        </div>

                        <div class="col-lg-4 col-md-6">

                            <div class="holiday-switch-card">

                                <div class="form-check form-switch d-flex justify-content-between align-items-start gap-3 p-0 m-0">

                                    <div>
                                        <label
                                            class="form-check-label fw-semibold"
                                            for="holidayIsPaid"
                                        >
                                            عطلة مدفوعة
                                        </label>

                                        <div class="text-muted small mt-1">
                                            يتم احتساب اليوم ضمن الأجر.
                                        </div>
                                    </div>

                                    <input
                                        class="form-check-input m-0"
                                        type="checkbox"
                                        id="holidayIsPaid"
                                        checked
                                    >

                                </div>

                            </div>

                        </div>

                        <div class="col-lg-4 col-md-6">

                            <div class="holiday-switch-card">

                                <div class="form-check form-switch d-flex justify-content-between align-items-start gap-3 p-0 m-0">

                                    <div>
                                        <label
                                            class="form-check-label fw-semibold"
                                            for="holidayExcludeLeave"
                                        >
                                            استبعادها من الإجازات
                                        </label>

                                        <div class="text-muted small mt-1">
                                            لا تخصم من رصيد إجازة الموظف.
                                        </div>
                                    </div>

                                    <input
                                        class="form-check-input m-0"
                                        type="checkbox"
                                        id="holidayExcludeLeave"
                                        checked
                                    >

                                </div>

                            </div>

                        </div>

                        <div class="col-lg-4 col-md-6">

                            <div class="holiday-switch-card">

                                <div class="form-check form-switch d-flex justify-content-between align-items-start gap-3 p-0 m-0">

                                    <div>
                                        <label
                                            class="form-check-label fw-semibold"
                                            for="holidayAffectsAttendance"
                                        >
                                            تؤثر على الحضور
                                        </label>

                                        <div class="text-muted small mt-1">
                                            لا يعتبر الموظف غائبًا في هذا اليوم.
                                        </div>
                                    </div>

                                    <input
                                        class="form-check-input m-0"
                                        type="checkbox"
                                        id="holidayAffectsAttendance"
                                        checked
                                    >

                                </div>

                            </div>

                        </div>

                        <div class="col-lg-6 col-md-6">

                            <div class="holiday-switch-card">

                                <div class="form-check form-switch d-flex justify-content-between align-items-start gap-3 p-0 m-0">

                                    <div>
                                        <label
                                            class="form-check-label fw-semibold"
                                            for="holidayIsRecurring"
                                        >
                                            تتكرر سنويًا
                                        </label>

                                        <div class="text-muted small mt-1">
                                            تستخدم للعطلات التي تتكرر في نفس التاريخ.
                                        </div>
                                    </div>

                                    <input
                                        class="form-check-input m-0"
                                        type="checkbox"
                                        id="holidayIsRecurring"
                                    >

                                </div>

                            </div>

                        </div>

                        <div class="col-lg-6 col-md-6">

                            <div class="holiday-switch-card">

                                <div class="form-check form-switch d-flex justify-content-between align-items-start gap-3 p-0 m-0">

                                    <div>
                                        <label
                                            class="form-check-label fw-semibold"
                                            for="holidayIsActive"
                                        >
                                            العطلة نشطة
                                        </label>

                                        <div class="text-muted small mt-1">
                                            يسمح للنظام باستخدام العطلة.
                                        </div>
                                    </div>

                                    <input
                                        class="form-check-input m-0"
                                        type="checkbox"
                                        id="holidayIsActive"
                                        checked
                                    >

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                <div class="ry-modal-footer">

                    <button
                        type="button"
                        class="btn btn-light btn-close-holiday-modal"
                    >
                        إلغاء
                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary px-4"
                        id="btnSaveHoliday"
                    >
                        حفظ البيانات
                    </button>

                </div>

            </form>

        </div>

    </div>

    <div class="ry-modal" id="deleteHolidayModal">

        <div class="ry-modal-panel ry-modal-sm">

            <div class="ry-modal-header">

                <div>
                    <h5 class="mb-1 text-danger">
                        أرشفة العطلة
                    </h5>

                    <small class="text-muted">
                        سيتم إخفاء العطلة من السجلات النشطة.
                    </small>
                </div>

                <button
                    type="button"
                    class="ry-modal-close btn-close-delete-modal"
                >
                    ×
                </button>

            </div>

            <div class="ry-modal-body">

                <input type="hidden" id="deleteHolidayId">

                <p class="mb-2">
                    هل تريد أرشفة العطلة التالية؟
                </p>

                <div
                    class="p-3 rounded-3 bg-light fw-bold"
                    id="deleteHolidayName"
                ></div>

            </div>

            <div class="ry-modal-footer">

                <button
                    type="button"
                    class="btn btn-light btn-close-delete-modal"
                >
                    إلغاء
                </button>

                <button
                    type="button"
                    class="btn btn-danger px-4"
                    id="btnConfirmDeleteHoliday"
                >
                    تأكيد الأرشفة
                </button>

            </div>

        </div>

    </div>

@endif

<script>
(function startHolidaysPage() {

    if (!window.jQuery) {
        setTimeout(startHolidaysPage, 50);
        return;
    }

    window.jQuery(function ($) {

        const canManage =
            @json($canManageHolidays);

        const urls = {
            data:
                @json(route('app.holidays.data')),

            options:
                @json(route('app.holidays.options')),

            store:
                @json(route('app.holidays.store')),

            show:
                @json(
                    route(
                        'app.holidays.show',
                        ['holiday' => '__ID__']
                    )
                ),

            update:
                @json(
                    route(
                        'app.holidays.update',
                        ['holiday' => '__ID__']
                    )
                ),

            destroy:
                @json(
                    route(
                        'app.holidays.destroy',
                        ['holiday' => '__ID__']
                    )
                ),
        };

        let currentPage = 1;
        let request = null;

        let holidayTypes = {
            public: 'عطلة رسمية',
            company: 'عطلة الشركة',
            national: 'عطلة وطنية',
            religious: 'عطلة دينية',
            other: 'أخرى'
        };

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN':
                    $('meta[name="csrf-token"]').attr('content'),

                'Accept':
                    'application/json'
            }
        });

        function escapeHtml(value) {

            return $('<div>')
                .text(value ?? '')
                .html();
        }

        function isTrue(value) {

            return (
                value === true ||
                value === 1 ||
                value === '1'
            );
        }

        function showPageAlert(
            message,
            type = 'success'
        ) {
            $('#pageAlert')
                .removeClass(
                    'd-none alert-success alert-danger alert-warning'
                )
                .addClass('alert-' + type)
                .text(message);

            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });

            setTimeout(function () {
                $('#pageAlert').addClass('d-none');
            }, 5000);
        }

        function clearFormErrors() {

            $('.field-error').text('');

            $('#holidayFormAlert')
                .addClass('d-none')
                .html('');
        }

        function displayAjaxError(
            xhr,
            formAlert = null
        ) {
            const response =
                xhr.responseJSON || {};

            const errors =
                response.errors || {};

            let message =
                response.message ||
                'حدث خطأ أثناء تنفيذ العملية.';

            if (
                formAlert &&
                Object.keys(errors).length
            ) {
                $.each(
                    errors,
                    function (field, messages) {

                        $('[data-error-for="' + field + '"]')
                            .text(
                                Array.isArray(messages)
                                    ? messages[0]
                                    : messages
                            );
                    }
                );

                message =
                    'يرجى مراجعة الحقول المطلوبة.';
            }

            if (formAlert) {
                $(formAlert)
                    .removeClass('d-none')
                    .text(message);
            } else {
                showPageAlert(message, 'danger');
            }
        }

        function openModal(selector) {

            $('.ry-modal').hide();

            $(selector).css('display', 'flex');

            $('body').addClass('ry-modal-open');
        }

        function closeModal(selector) {

            $(selector).hide();

            if (!$('.ry-modal:visible').length) {
                $('body').removeClass('ry-modal-open');
            }
        }

        function dateOnly(value) {

            if (!value) {
                return '-';
            }

            return String(value).substring(0, 10);
        }

        function dateRange(item) {

            const start =
                dateOnly(item.start_date);

            const end =
                dateOnly(item.end_date);

            if (start === end) {
                return escapeHtml(start);
            }

            return (
                '<div>' +
                    escapeHtml(start) +
                '</div>' +
                '<small class="text-muted">إلى</small>' +
                '<div>' +
                    escapeHtml(end) +
                '</div>'
            );
        }

        function typeLabel(type) {

            return (
                holidayTypes[type] ||
                type ||
                'غير محدد'
            );
        }

        function statusBadge(item) {

            if (isTrue(item.is_active)) {
                return (
                    '<span class="holiday-badge holiday-badge-success">' +
                        'نشطة' +
                    '</span>'
                );
            }

            return (
                '<span class="holiday-badge holiday-badge-danger">' +
                    'غير نشطة' +
                '</span>'
            );
        }

        function propertyBadges(item) {

            let html =
                '<div class="holiday-property-list">';

            if (isTrue(item.is_paid)) {
                html +=
                    '<span class="holiday-property">مدفوعة</span>';
            }

            if (
                isTrue(
                    item.exclude_from_leave_days
                )
            ) {
                html +=
                    '<span class="holiday-property">لا تخصم من الإجازة</span>';
            }

            if (
                isTrue(
                    item.affects_attendance
                )
            ) {
                html +=
                    '<span class="holiday-property">تؤثر على الحضور</span>';
            }

            if (isTrue(item.is_recurring)) {
                html +=
                    '<span class="holiday-property">سنوية متكررة</span>';
            }

            html += '</div>';

            return html;
        }

        function paginationPayload(response) {

            if (
                response.holidays &&
                response.holidays.data
            ) {
                return response.holidays;
            }

            if (
                response.data &&
                response.data.data
            ) {
                return response.data;
            }

            return response;
        }

        function renderTable(paginator) {

            const rows =
                Array.isArray(paginator.data)
                    ? paginator.data
                    : [];

            $('#holidaysCount')
                .text(
                    (paginator.total || rows.length) +
                    ' عطلة'
                );

            if (!rows.length) {

                $('#holidaysTableBody').html(`
                    <tr>
                        <td colspan="8">
                            <div class="holiday-empty-state">

                                <div class="empty-symbol">
                                    ◫
                                </div>

                                <h6>
                                    لا توجد عطلات
                                </h6>

                                <p class="mb-0">
                                    لم يتم العثور على سجلات مطابقة.
                                </p>

                            </div>
                        </td>
                    </tr>
                `);

                renderPagination(paginator);

                return;
            }

            let html = '';

            $.each(rows, function (index, item) {

                const rowNumber =
                    (paginator.from || 1) + index;

                const branchName =
                    item.branch?.name ||
                    item.branch?.display_name ||
                    'جميع الفروع';

                const branchBadge =
                    item.branch_id
                        ? 'holiday-badge-secondary'
                        : 'holiday-badge-primary';

                let actions = `
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-secondary btn-show-holiday"
                        data-id="${item.id}"
                    >
                        عرض
                    </button>
                `;

                if (canManage) {
                    actions += `
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-primary btn-edit-holiday"
                            data-id="${item.id}"
                        >
                            تعديل
                        </button>

                        <button
                            type="button"
                            class="btn btn-sm btn-outline-danger btn-delete-holiday"
                            data-id="${item.id}"
                            data-name="${escapeHtml(item.name)}"
                        >
                            أرشفة
                        </button>
                    `;
                }

                html += `
                    <tr>

                        <td class="px-4">
                            ${rowNumber}
                        </td>

                        <td class="holiday-name">

                            <div class="fw-bold mb-1">
                                ${escapeHtml(item.name)}
                            </div>

                            <span class="holiday-code">
                                ${escapeHtml(item.code)}
                            </span>

                        </td>

                        <td>
                            <span class="holiday-badge holiday-badge-warning">
                                ${escapeHtml(typeLabel(item.type))}
                            </span>
                        </td>

                        <td>
                            <span class="holiday-badge ${branchBadge}">
                                ${escapeHtml(branchName)}
                            </span>
                        </td>

                        <td>
                            ${dateRange(item)}
                        </td>

                        <td>
                            ${propertyBadges(item)}
                        </td>

                        <td>
                            ${statusBadge(item)}
                        </td>

                        <td class="px-4">

                            <div class="holiday-action-buttons">
                                ${actions}
                            </div>

                        </td>

                    </tr>
                `;
            });

            $('#holidaysTableBody').html(html);

            renderPagination(paginator);
        }

        function renderPagination(paginator) {

            const current =
                Number(paginator.current_page || 1);

            const last =
                Number(paginator.last_page || 1);

            const from =
                paginator.from || 0;

            const to =
                paginator.to || 0;

            const total =
                paginator.total || 0;

            $('#paginationInfo').text(
                total
                    ? 'عرض ' + from +
                      ' إلى ' + to +
                      ' من أصل ' + total
                    : 'لا توجد بيانات.'
            );

            let html = '';

            html += `
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary btn-pagination"
                    data-page="${current - 1}"
                    ${current <= 1 ? 'disabled' : ''}
                >
                    السابق
                </button>
            `;

            let start =
                Math.max(1, current - 2);

            let end =
                Math.min(last, current + 2);

            for (
                let page = start;
                page <= end;
                page++
            ) {
                html += `
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-secondary btn-pagination ${page === current ? 'active' : ''}"
                        data-page="${page}"
                    >
                        ${page}
                    </button>
                `;
            }

            html += `
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary btn-pagination"
                    data-page="${current + 1}"
                    ${current >= last ? 'disabled' : ''}
                >
                    التالي
                </button>
            `;

            $('#paginationButtons').html(html);
        }

        function loadData(page = 1) {

            currentPage = page;

            if (request) {
                request.abort();
            }

            $('#holidaysTableBody').html(`
                <tr>
                    <td colspan="8" class="holiday-loading">
                        جاري تحميل البيانات...
                    </td>
                </tr>
            `);

            request = $.ajax({
                url: urls.data,
                type: 'GET',

                data: {
                    page: page,
                    search: $('#filterSearch').val(),
                    type: $('#filterType').val(),
                    branch_id: $('#filterBranch').val(),
                    year: $('#filterYear').val(),
                    status: $('#filterStatus').val(),
                    per_page: $('#filterPerPage').val()
                },

                success: function (response) {
                    renderTable(
                        paginationPayload(response)
                    );
                },

                error: function (xhr, status) {

                    if (status === 'abort') {
                        return;
                    }

                    $('#holidaysTableBody').html(`
                        <tr>
                            <td colspan="8" class="holiday-loading text-danger">
                                تعذر تحميل بيانات العطلات.
                            </td>
                        </tr>
                    `);

                    displayAjaxError(xhr);
                },

                complete: function () {
                    request = null;
                }
            });
        }

        function addOptions(
            selector,
            items,
            emptyLabel = null
        ) {
            const element =
                $(selector);

            element.empty();

            if (emptyLabel !== null) {
                element.append(
                    $('<option>', {
                        value: '',
                        text: emptyLabel
                    })
                );
            }

            $.each(items, function (key, item) {

                let value;
                let label;

                if (
                    typeof item === 'object' &&
                    item !== null
                ) {
                    value =
                        item.id ?? item.value ?? key;

                    label =
                        item.display_name ??
                        item.name ??
                        item.label ??
                        value;
                } else {
                    value = key;
                    label = item;
                }

                element.append(
                    $('<option>', {
                        value: value,
                        text: label
                    })
                );
            });
        }

        function loadOptions() {

            $.ajax({
                url: urls.options,
                type: 'GET',

                success: function (response) {

                    if (
                        response.types &&
                        Object.keys(response.types).length
                    ) {
                        holidayTypes =
                            response.types;
                    }

                    addOptions(
                        '#filterType',
                        holidayTypes,
                        'جميع الأنواع'
                    );

                    addOptions(
                        '#holidayType',
                        holidayTypes
                    );

                    addOptions(
                        '#filterBranch',
                        response.branches || [],
                        'جميع الفروع'
                    );

                    addOptions(
                        '#holidayBranch',
                        response.branches || [],
                        'جميع فروع الشركة'
                    );

                    const currentYear =
                        new Date().getFullYear();

                    const years =
                        response.years &&
                        response.years.length
                            ? response.years
                            : [
                                currentYear - 1,
                                currentYear,
                                currentYear + 1
                            ];

                    $('#filterYear').empty();

                    $.each(years, function (_, year) {

                        $('#filterYear').append(
                            $('<option>', {
                                value: year,
                                text: year
                            })
                        );
                    });

                    $('#filterYear')
                        .val(currentYear);

                    loadData(1);
                },

                error: function (xhr) {

                    displayAjaxError(xhr);

                    loadData(1);
                }
            });
        }

        function resetHolidayForm() {

            $('#holidayForm')[0].reset();

            $('#holidayId').val('');

            $('#holidayModalTitle')
                .text('إضافة عطلة');

            $('#btnSaveHoliday')
                .text('حفظ البيانات');

            $('#holidayIsPaid')
                .prop('checked', true);

            $('#holidayExcludeLeave')
                .prop('checked', true);

            $('#holidayAffectsAttendance')
                .prop('checked', true);

            $('#holidayIsRecurring')
                .prop('checked', false);

            $('#holidayIsActive')
                .prop('checked', true);

            clearFormErrors();
        }

        function populateHolidayForm(item) {

            $('#holidayId')
                .val(item.id);

            $('#holidayCode')
                .val(item.code || '');

            $('#holidayName')
                .val(item.name || '');

            $('#holidayNameEn')
                .val(item.name_en || '');

            $('#holidayType')
                .val(item.type || 'public');

            $('#holidayBranch')
                .val(item.branch_id || '');

            $('#holidayStartDate')
                .val(dateOnly(item.start_date));

            $('#holidayEndDate')
                .val(dateOnly(item.end_date));

            $('#holidayIsPaid')
                .prop(
                    'checked',
                    isTrue(item.is_paid)
                );

            $('#holidayExcludeLeave')
                .prop(
                    'checked',
                    isTrue(
                        item.exclude_from_leave_days
                    )
                );

            $('#holidayAffectsAttendance')
                .prop(
                    'checked',
                    isTrue(
                        item.affects_attendance
                    )
                );

            $('#holidayIsRecurring')
                .prop(
                    'checked',
                    isTrue(item.is_recurring)
                );

            $('#holidayIsActive')
                .prop(
                    'checked',
                    isTrue(item.is_active)
                );
        }

        function loadHolidayForEdit(id) {

            clearFormErrors();

            $('#btnSaveHoliday')
                .prop('disabled', true)
                .text('جاري التحميل...');

            openModal('#holidayFormModal');

            $.ajax({
                url:
                    urls.show.replace('__ID__', id),

                type: 'GET',

                success: function (response) {

                    const item =
                        response.holiday ||
                        response.data ||
                        response;

                    populateHolidayForm(item);

                    $('#holidayModalTitle')
                        .text('تعديل العطلة');

                    $('#btnSaveHoliday')
                        .prop('disabled', false)
                        .text('حفظ التعديلات');
                },

                error: function (xhr) {

                    closeModal('#holidayFormModal');

                    displayAjaxError(xhr);
                }
            });
        }

        function buildHolidayFormData() {

            const formData =
                new FormData(
                    $('#holidayForm')[0]
                );

            formData.set(
                'is_paid',
                $('#holidayIsPaid').is(':checked')
                    ? '1'
                    : '0'
            );

            formData.set(
                'exclude_from_leave_days',
                $('#holidayExcludeLeave').is(':checked')
                    ? '1'
                    : '0'
            );

            formData.set(
                'affects_attendance',
                $('#holidayAffectsAttendance').is(':checked')
                    ? '1'
                    : '0'
            );

            formData.set(
                'is_recurring',
                $('#holidayIsRecurring').is(':checked')
                    ? '1'
                    : '0'
            );

            formData.set(
                'is_active',
                $('#holidayIsActive').is(':checked')
                    ? '1'
                    : '0'
            );

            return formData;
        }

        $('#btnAddHoliday').on('click', function () {

            resetHolidayForm();

            openModal('#holidayFormModal');
        });

        $('#holidayForm').on('submit', function (event) {

            event.preventDefault();

            clearFormErrors();

            const id =
                $('#holidayId').val();

            const editing =
                Boolean(id);

            const formData =
                buildHolidayFormData();

            let requestUrl =
                urls.store;

            if (editing) {
                requestUrl =
                    urls.update.replace('__ID__', id);

                formData.append(
                    '_method',
                    'PUT'
                );
            }

            const button =
                $('#btnSaveHoliday');

            button
                .prop('disabled', true)
                .text('جاري الحفظ...');

            $.ajax({
                url: requestUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,

                success: function (response) {

                    closeModal('#holidayFormModal');

                    showPageAlert(
                        response.message ||
                        (
                            editing
                                ? 'تم تحديث العطلة بنجاح.'
                                : 'تم إنشاء العطلة بنجاح.'
                        )
                    );

                    loadData(
                        editing
                            ? currentPage
                            : 1
                    );
                },

                error: function (xhr) {

                    displayAjaxError(
                        xhr,
                        '#holidayFormAlert'
                    );
                },

                complete: function () {

                    button
                        .prop('disabled', false)
                        .text(
                            editing
                                ? 'حفظ التعديلات'
                                : 'حفظ البيانات'
                        );
                }
            });
        });

        $(document).on(
            'click',
            '.btn-edit-holiday, .btn-show-holiday',
            function () {

                loadHolidayForEdit(
                    $(this).data('id')
                );
            }
        );

        $(document).on(
            'click',
            '.btn-delete-holiday',
            function () {

                $('#deleteHolidayId')
                    .val(
                        $(this).data('id')
                    );

                $('#deleteHolidayName')
                    .text(
                        $(this).data('name')
                    );

                openModal('#deleteHolidayModal');
            }
        );

        $('#btnConfirmDeleteHoliday')
            .on('click', function () {

                const id =
                    $('#deleteHolidayId').val();

                const button =
                    $(this);

                button
                    .prop('disabled', true)
                    .text('جاري الأرشفة...');

                $.ajax({
                    url:
                        urls.destroy.replace(
                            '__ID__',
                            id
                        ),

                    type: 'DELETE',

                    success: function (response) {

                        closeModal(
                            '#deleteHolidayModal'
                        );

                        showPageAlert(
                            response.message ||
                            'تمت أرشفة العطلة بنجاح.'
                        );

                        loadData(currentPage);
                    },

                    error: function (xhr) {
                        displayAjaxError(xhr);
                    },

                    complete: function () {

                        button
                            .prop('disabled', false)
                            .text('تأكيد الأرشفة');
                    }
                });
            });

        $('#btnSearch').on('click', function () {
            loadData(1);
        });

        $('#filterSearch').on(
            'keydown',
            function (event) {

                if (event.key === 'Enter') {
                    event.preventDefault();
                    loadData(1);
                }
            }
        );

        $(
            '#filterType, ' +
            '#filterBranch, ' +
            '#filterYear, ' +
            '#filterStatus, ' +
            '#filterPerPage'
        ).on('change', function () {
            loadData(1);
        });

        $('#btnReset').on('click', function () {

            const currentYear =
                new Date().getFullYear();

            $('#filterSearch').val('');
            $('#filterType').val('');
            $('#filterBranch').val('');
            $('#filterStatus').val('');
            $('#filterPerPage').val('15');

            if (
                $('#filterYear option[value="' +
                currentYear +
                '"]').length
            ) {
                $('#filterYear').val(currentYear);
            }

            loadData(1);
        });

        $(document).on(
            'click',
            '.btn-pagination',
            function () {

                if ($(this).prop('disabled')) {
                    return;
                }

                loadData(
                    Number($(this).data('page'))
                );
            }
        );

        $('.btn-close-holiday-modal')
            .on('click', function () {

                closeModal('#holidayFormModal');
            });

        $('.btn-close-delete-modal')
            .on('click', function () {

                closeModal('#deleteHolidayModal');
            });

        $('.ry-modal').on(
            'click',
            function (event) {

                if (event.target === this) {
                    closeModal(this);
                }
            }
        );

        $(document).on(
            'keydown',
            function (event) {

                if (event.key === 'Escape') {
                    $('.ry-modal').hide();
                    $('body').removeClass('ry-modal-open');
                }
            }
        );

        $('#holidayStartDate').on(
            'change',
            function () {

                const startDate =
                    $(this).val();

                const endDate =
                    $('#holidayEndDate').val();

                if (
                    startDate &&
                    (
                        !endDate ||
                        endDate < startDate
                    )
                ) {
                    $('#holidayEndDate')
                        .val(startDate);
                }

                $('#holidayEndDate')
                    .attr(
                        'min',
                        startDate || ''
                    );
            }
        );

        loadOptions();

    });

})();
</script>

@endsection