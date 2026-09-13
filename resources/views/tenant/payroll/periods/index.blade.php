@extends('layouts.tenant')

@section('title', 'فترات الرواتب')
@section('page-title', 'فترات الرواتب')

@section('content')

<style>
    #payrollPeriodsPage {
        direction: rtl;
    }

    .payroll-page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }

    .payroll-page-title {
        margin: 0 0 6px;
        font-size: 22px;
        font-weight: 700;
        color: #172033;
    }

    .payroll-page-description {
        margin: 0;
        color: #778198;
        font-size: 14px;
    }

    .payroll-card {
        background: #ffffff;
        border: 1px solid #e8edf5;
        border-radius: 16px;
        box-shadow: 0 4px 18px rgba(24, 39, 75, 0.05);
    }

    .payroll-filter-card {
        padding: 20px;
        margin-bottom: 20px;
    }

    .payroll-filter-grid {
        display: grid;
        grid-template-columns: minmax(230px, 2fr) repeat(4, minmax(130px, 1fr)) auto;
        gap: 14px;
        align-items: end;
    }

    .payroll-field {
        min-width: 0;
    }

    .payroll-field label {
        display: block;
        margin-bottom: 7px;
        color: #3b4559;
        font-size: 13px;
        font-weight: 600;
    }

    .payroll-control {
        width: 100%;
        height: 42px;
        padding: 8px 12px;
        border: 1px solid #dce3ed;
        border-radius: 9px;
        background: #ffffff;
        color: #273146;
        outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    textarea.payroll-control {
        height: auto;
        min-height: 95px;
        resize: vertical;
    }

    .payroll-control:focus {
        border-color: #1677ff;
        box-shadow: 0 0 0 3px rgba(22, 119, 255, 0.1);
    }

    .payroll-control.is-invalid {
        border-color: #dc3545;
    }

    .payroll-invalid-feedback {
        min-height: 18px;
        margin-top: 5px;
        color: #dc3545;
        font-size: 12px;
    }

    .payroll-filter-actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .payroll-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 38px;
        padding: 8px 14px;
        border: 1px solid transparent;
        border-radius: 8px;
        background: #ffffff;
        color: #354058;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.18s ease;
        white-space: nowrap;
    }

    .payroll-btn:hover {
        transform: translateY(-1px);
    }

    .payroll-btn:disabled {
        opacity: 0.65;
        cursor: not-allowed;
        transform: none;
    }

    .payroll-btn-primary {
        background: #1677ff;
        border-color: #1677ff;
        color: #ffffff;
    }

    .payroll-btn-primary:hover {
        background: #0969e8;
        color: #ffffff;
    }

    .payroll-btn-light {
        background: #f7f9fc;
        border-color: #dfe5ee;
        color: #3b4559;
    }

    .payroll-btn-success {
        background: #198754;
        border-color: #198754;
        color: #ffffff;
    }

    .payroll-btn-warning {
        background: #fff8e6;
        border-color: #f2c15d;
        color: #9a6700;
    }

    .payroll-btn-danger {
        background: #fff1f2;
        border-color: #f3a5ad;
        color: #c52f3e;
    }

    .payroll-btn-outline {
        background: #ffffff;
        border-color: #1677ff;
        color: #1677ff;
    }

    .payroll-btn-sm {
        min-height: 32px;
        padding: 5px 9px;
        font-size: 12px;
    }

    .payroll-table-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        padding: 18px 20px;
        border-bottom: 1px solid #e8edf5;
    }

    .payroll-table-title {
        margin: 0;
        font-size: 17px;
        font-weight: 700;
        color: #1e293b;
    }

    .payroll-total-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 20px;
        background: #eaf2ff;
        color: #1677ff;
        font-size: 12px;
        font-weight: 700;
    }

    .payroll-table-responsive {
        width: 100%;
        overflow-x: auto;
    }

    .payroll-table {
        width: 100%;
        min-width: 1120px;
        margin: 0;
        border-collapse: collapse;
    }

    .payroll-table th {
        padding: 13px 14px;
        border-bottom: 1px solid #dfe5ee;
        background: #f8fafc;
        color: #3a4355;
        font-size: 13px;
        font-weight: 700;
        text-align: right;
        white-space: nowrap;
    }

    .payroll-table td {
        padding: 14px;
        border-bottom: 1px solid #edf1f6;
        color: #344054;
        font-size: 13px;
        vertical-align: middle;
    }

    .payroll-table tbody tr:hover {
        background: #fafcff;
    }

    .payroll-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .payroll-code {
        direction: ltr;
        display: inline-block;
        font-family: monospace;
        color: #1677ff;
        font-weight: 700;
    }

    .payroll-period-name {
        color: #172033;
        font-weight: 700;
        margin-bottom: 4px;
    }

    .payroll-muted {
        color: #8a94a6;
        font-size: 12px;
    }

    .payroll-status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 74px;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
    }

    .status-draft {
        background: #eef2f7;
        color: #526071;
    }

    .status-open {
        background: #e7f8ef;
        color: #12834b;
    }

    .status-processing,
    .status-review {
        background: #fff4d9;
        color: #9b6800;
    }

    .status-approved {
        background: #e8f1ff;
        color: #1769d2;
    }

    .status-paid,
    .status-closed {
        background: #e4f7f2;
        color: #087b64;
    }

    .status-cancelled {
        background: #ffecef;
        color: #c93545;
    }

    .payroll-lock-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 9px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
    }

    .payroll-locked {
        background: #fff0f1;
        color: #c63242;
    }

    .payroll-unlocked {
        background: #edf9f2;
        color: #168552;
    }

    .payroll-actions {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }

    .payroll-empty {
        padding: 50px 20px !important;
        text-align: center;
    }

    .payroll-empty-title {
        margin-bottom: 7px;
        color: #344054;
        font-weight: 700;
    }

    .payroll-loading {
        padding: 50px 20px !important;
        text-align: center;
        color: #667085;
    }

    .payroll-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        padding: 16px 20px;
        border-top: 1px solid #e8edf5;
    }

    .payroll-pagination-info {
        color: #667085;
        font-size: 13px;
    }

    .payroll-pagination-buttons {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }

    .payroll-page-btn {
        min-width: 34px;
        height: 34px;
        padding: 4px 9px;
        border: 1px solid #dfe5ee;
        border-radius: 7px;
        background: #ffffff;
        color: #344054;
        cursor: pointer;
    }

    .payroll-page-btn.active {
        background: #1677ff;
        border-color: #1677ff;
        color: #ffffff;
    }

    .payroll-page-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .payroll-alert {
        display: none;
        margin-bottom: 18px;
        padding: 13px 16px;
        border: 1px solid transparent;
        border-radius: 10px;
        font-size: 14px;
    }

    .payroll-alert-success {
        background: #e9f9f0;
        border-color: #b9ebcf;
        color: #16784b;
    }

    .payroll-alert-danger {
        background: #fff0f1;
        border-color: #ffc8ce;
        color: #bb2939;
    }

    .payroll-modal-overlay {
        position: fixed;
        inset: 0;
        z-index: 1060;
        display: none;
        align-items: flex-start;
        justify-content: center;
        padding: 24px 14px;
        overflow-x: hidden;
        overflow-y: auto;
        background: rgba(15, 23, 42, 0.62);
    }

    .payroll-modal-panel {
        display: flex;
        flex-direction: column;
        width: 100%;
        max-width: 850px;
        max-height: calc(100vh - 48px);
        margin: auto;
        overflow: hidden;
        background: #ffffff;
        border-radius: 17px;
        box-shadow: 0 24px 80px rgba(15, 23, 42, 0.3);
    }

    .payroll-modal-panel-sm {
        max-width: 550px;
    }

    .payroll-modal-header {
        flex: 0 0 auto;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        padding: 18px 20px;
        border-bottom: 1px solid #e7ebf1;
    }

    .payroll-modal-title {
        margin: 0 0 5px;
        color: #172033;
        font-size: 18px;
        font-weight: 700;
    }

    .payroll-modal-subtitle {
        margin: 0;
        color: #7b8495;
        font-size: 12px;
    }

    .payroll-modal-close {
        flex: 0 0 auto;
        width: 36px;
        height: 36px;
        border: 0;
        border-radius: 9px;
        background: #f2f5f9;
        color: #445067;
        font-size: 22px;
        line-height: 1;
        cursor: pointer;
    }

    .payroll-modal-body {
        flex: 1 1 auto;
        min-height: 0;
        padding: 20px;
        overflow-x: hidden;
        overflow-y: auto;
        overscroll-behavior: contain;
    }

    .payroll-modal-footer {
        flex: 0 0 auto;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 9px;
        padding: 15px 20px;
        border-top: 1px solid #e7ebf1;
        background: #ffffff;
    }

    .payroll-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 15px;
    }

    .payroll-form-full {
        grid-column: 1 / -1;
    }

    .payroll-details-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }

    .payroll-detail-box {
        min-height: 82px;
        padding: 14px;
        border: 1px solid #e5eaf1;
        border-radius: 11px;
        background: #f9fbfd;
    }

    .payroll-detail-label {
        margin-bottom: 7px;
        color: #7e889b;
        font-size: 12px;
    }

    .payroll-detail-value {
        color: #263147;
        font-size: 14px;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .payroll-section-title {
        margin: 0 0 12px;
        color: #263147;
        font-size: 15px;
        font-weight: 700;
    }

    .payroll-runs-table {
        width: 100%;
        min-width: 720px;
        border-collapse: collapse;
    }

    .payroll-runs-table th,
    .payroll-runs-table td {
        padding: 11px;
        border-bottom: 1px solid #e8edf3;
        text-align: right;
        font-size: 12px;
    }

    .payroll-runs-table th {
        background: #f8fafc;
        color: #596579;
    }

    .payroll-confirm-message {
        padding: 14px;
        border: 1px solid #f3dfaa;
        border-radius: 10px;
        background: #fff9e9;
        color: #705719;
        line-height: 1.8;
        font-size: 14px;
    }

    body.payroll-modal-open {
        overflow: hidden !important;
    }

    @media (max-width: 1200px) {
        .payroll-filter-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 767px) {
        .payroll-filter-grid,
        .payroll-form-grid,
        .payroll-details-grid {
            grid-template-columns: 1fr;
        }

        .payroll-form-full {
            grid-column: auto;
        }

        .payroll-page-header {
            align-items: stretch;
        }

        .payroll-page-header .payroll-btn {
            width: 100%;
        }

        .payroll-modal-overlay {
            padding: 10px;
        }

        .payroll-modal-panel {
            max-height: calc(100vh - 20px);
        }

        .payroll-modal-body {
            padding: 15px;
        }
    }
</style>

@php
    $canManagePayroll = auth()->user()->can('payroll.manage');
    $canApprovePayroll = auth()->user()->can('payroll.approve');
@endphp

<div id="payrollPeriodsPage">

    <div
        id="payrollSuccessAlert"
        class="payroll-alert payroll-alert-success"
    ></div>

    <div
        id="payrollErrorAlert"
        class="payroll-alert payroll-alert-danger"
    ></div>

    <div class="payroll-page-header">

        <div>
            <h2 class="payroll-page-title">
                فترات الرواتب
            </h2>

            <p class="payroll-page-description">
                إنشاء دورات الرواتب ومتابعة فتحها وإغلاقها واعتمادها.
            </p>
        </div>

        @if($canManagePayroll)
            <button
                type="button"
                id="btnCreatePayrollPeriod"
                class="payroll-btn payroll-btn-primary"
            >
                <span>+</span>
                إضافة فترة رواتب
            </button>
        @endif

    </div>

    <div class="payroll-card payroll-filter-card">

        <form id="payrollPeriodFilterForm">

            <div class="payroll-filter-grid">

                <div class="payroll-field">
                    <label for="periodSearch">
                        البحث
                    </label>

                    <input
                        type="search"
                        id="periodSearch"
                        class="payroll-control"
                        placeholder="اسم الفترة أو الكود..."
                        autocomplete="off"
                    >
                </div>

                <div class="payroll-field">
                    <label for="periodYearFilter">
                        السنة
                    </label>

                    <select
                        id="periodYearFilter"
                        class="payroll-control"
                    >
                        <option value="">جميع السنوات</option>

                        @for($year = now()->year + 2; $year >= now()->year - 5; $year--)
                            <option value="{{ $year }}">
                                {{ $year }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div class="payroll-field">
                    <label for="periodMonthFilter">
                        الشهر
                    </label>

                    <select
                        id="periodMonthFilter"
                        class="payroll-control"
                    >
                        <option value="">جميع الأشهر</option>
                        <option value="1">يناير</option>
                        <option value="2">فبراير</option>
                        <option value="3">مارس</option>
                        <option value="4">أبريل</option>
                        <option value="5">مايو</option>
                        <option value="6">يونيو</option>
                        <option value="7">يوليو</option>
                        <option value="8">أغسطس</option>
                        <option value="9">سبتمبر</option>
                        <option value="10">أكتوبر</option>
                        <option value="11">نوفمبر</option>
                        <option value="12">ديسمبر</option>
                    </select>
                </div>

                <div class="payroll-field">
                    <label for="periodStatusFilter">
                        الحالة
                    </label>

                    <select
                        id="periodStatusFilter"
                        class="payroll-control"
                    >
                        <option value="">جميع الحالات</option>
                        <option value="draft">مسودة</option>
                        <option value="open">مفتوحة</option>
                        <option value="processing">قيد المعالجة</option>
                        <option value="review">قيد المراجعة</option>
                        <option value="approved">معتمدة</option>
                        <option value="paid">مدفوعة</option>
                        <option value="closed">مغلقة</option>
                        <option value="cancelled">ملغاة</option>
                    </select>
                </div>

                <div class="payroll-field">
                    <label for="periodLockedFilter">
                        القفل
                    </label>

                    <select
                        id="periodLockedFilter"
                        class="payroll-control"
                    >
                        <option value="">الكل</option>
                        <option value="0">غير مقفلة</option>
                        <option value="1">مقفلة</option>
                    </select>
                </div>

                <div class="payroll-filter-actions">

                    <button
                        type="submit"
                        class="payroll-btn payroll-btn-primary"
                    >
                        بحث
                    </button>

                    <button
                        type="button"
                        id="btnResetPayrollFilters"
                        class="payroll-btn payroll-btn-light"
                    >
                        إعادة
                    </button>

                </div>

            </div>

        </form>

    </div>

    <div class="payroll-card">

        <div class="payroll-table-header">

            <h3 class="payroll-table-title">
                قائمة فترات الرواتب
            </h3>

            <span
                id="payrollPeriodsTotal"
                class="payroll-total-badge"
            >
                0 فترة
            </span>

        </div>

        <div class="payroll-table-responsive">

            <table class="payroll-table">

                <thead>
                    <tr>
                        <th>#</th>
                        <th>الفترة</th>
                        <th>الشهر</th>
                        <th>مدة الفترة</th>
                        <th>تاريخ الصرف</th>
                        <th>الحالة</th>
                        <th>القفل</th>
                        <th>التشغيلات</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>

                <tbody id="payrollPeriodsTableBody">

                    <tr>
                        <td
                            colspan="9"
                            class="payroll-loading"
                        >
                            جاري تحميل البيانات...
                        </td>
                    </tr>

                </tbody>

            </table>

        </div>

        <div class="payroll-pagination">

            <div
                id="payrollPaginationInfo"
                class="payroll-pagination-info"
            ></div>

            <div
                id="payrollPaginationButtons"
                class="payroll-pagination-buttons"
            ></div>

        </div>

    </div>

</div>


{{-- نافذة إضافة وتعديل الفترة --}}
<div
    id="payrollPeriodFormModal"
    class="payroll-modal-overlay"
>

    <div class="payroll-modal-panel">

        <div class="payroll-modal-header">

            <div>
                <h3
                    id="payrollPeriodFormTitle"
                    class="payroll-modal-title"
                >
                    إضافة فترة رواتب
                </h3>

                <p class="payroll-modal-subtitle">
                    أدخل بيانات دورة الرواتب والتواريخ الخاصة بها.
                </p>
            </div>

            <button
                type="button"
                class="payroll-modal-close btn-close-payroll-modal"
            >
                ×
            </button>

        </div>

        <form id="payrollPeriodForm">

            <div class="payroll-modal-body">

                <input
                    type="hidden"
                    id="payrollPeriodId"
                >

                <div
                    id="payrollPeriodFormError"
                    class="payroll-alert payroll-alert-danger"
                ></div>

                <div class="payroll-form-grid">

                    <div class="payroll-field payroll-form-full">

                        <label for="payrollPeriodName">
                            اسم الفترة
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            id="payrollPeriodName"
                            name="name"
                            class="payroll-control"
                            maxlength="150"
                            placeholder="مثال: رواتب شهر أغسطس 2026"
                        >

                        <div
                            class="payroll-invalid-feedback"
                            data-error="name"
                        ></div>

                    </div>

                    <div class="payroll-field">

                        <label for="payrollPeriodYear">
                            السنة
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            id="payrollPeriodYear"
                            name="year"
                            class="payroll-control"
                        >
                            @for($year = now()->year + 3; $year >= now()->year - 3; $year--)
                                <option value="{{ $year }}">
                                    {{ $year }}
                                </option>
                            @endfor
                        </select>

                        <div
                            class="payroll-invalid-feedback"
                            data-error="year"
                        ></div>

                    </div>

                    <div class="payroll-field">

                        <label for="payrollPeriodMonth">
                            الشهر
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            id="payrollPeriodMonth"
                            name="month"
                            class="payroll-control"
                        >
                            <option value="">اختر الشهر</option>
                            <option value="1">يناير</option>
                            <option value="2">فبراير</option>
                            <option value="3">مارس</option>
                            <option value="4">أبريل</option>
                            <option value="5">مايو</option>
                            <option value="6">يونيو</option>
                            <option value="7">يوليو</option>
                            <option value="8">أغسطس</option>
                            <option value="9">سبتمبر</option>
                            <option value="10">أكتوبر</option>
                            <option value="11">نوفمبر</option>
                            <option value="12">ديسمبر</option>
                        </select>

                        <div
                            class="payroll-invalid-feedback"
                            data-error="month"
                        ></div>

                    </div>

                    <div class="payroll-field">

                        <label for="payrollPeriodStartDate">
                            تاريخ البداية
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="date"
                            id="payrollPeriodStartDate"
                            name="start_date"
                            class="payroll-control"
                        >

                        <div
                            class="payroll-invalid-feedback"
                            data-error="start_date"
                        ></div>

                    </div>

                    <div class="payroll-field">

                        <label for="payrollPeriodEndDate">
                            تاريخ النهاية
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="date"
                            id="payrollPeriodEndDate"
                            name="end_date"
                            class="payroll-control"
                        >

                        <div
                            class="payroll-invalid-feedback"
                            data-error="end_date"
                        ></div>

                    </div>

                    <div class="payroll-field payroll-form-full">

                        <label for="payrollPeriodPaymentDate">
                            تاريخ صرف الرواتب
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="date"
                            id="payrollPeriodPaymentDate"
                            name="payment_date"
                            class="payroll-control"
                        >

                        <div
                            class="payroll-invalid-feedback"
                            data-error="payment_date"
                        ></div>

                    </div>

                </div>

            </div>

            <div class="payroll-modal-footer">

                <button
                    type="submit"
                    id="btnSavePayrollPeriod"
                    class="payroll-btn payroll-btn-primary"
                >
                    حفظ البيانات
                </button>

                <button
                    type="button"
                    class="payroll-btn payroll-btn-light btn-close-payroll-modal"
                >
                    إلغاء
                </button>

            </div>

        </form>

    </div>

</div>


{{-- نافذة التفاصيل --}}
<div
    id="payrollPeriodDetailsModal"
    class="payroll-modal-overlay"
>

    <div class="payroll-modal-panel">

        <div class="payroll-modal-header">

            <div>
                <h3 class="payroll-modal-title">
                    تفاصيل فترة الرواتب
                </h3>

                <p class="payroll-modal-subtitle">
                    بيانات الفترة وتشغيلات الرواتب المرتبطة بها.
                </p>
            </div>

            <button
                type="button"
                class="payroll-modal-close btn-close-payroll-modal"
            >
                ×
            </button>

        </div>

        <div
            id="payrollPeriodDetailsBody"
            class="payroll-modal-body"
        >
            جاري تحميل البيانات...
        </div>

        <div class="payroll-modal-footer">

            <button
                type="button"
                class="payroll-btn payroll-btn-light btn-close-payroll-modal"
            >
                إغلاق
            </button>

        </div>

    </div>

</div>


{{-- نافذة تأكيد العمليات --}}
<div
    id="payrollPeriodConfirmModal"
    class="payroll-modal-overlay"
>

    <div class="payroll-modal-panel payroll-modal-panel-sm">

        <div class="payroll-modal-header">

            <div>
                <h3
                    id="payrollConfirmTitle"
                    class="payroll-modal-title"
                >
                    تأكيد العملية
                </h3>

                <p class="payroll-modal-subtitle">
                    يرجى مراجعة العملية قبل تنفيذها.
                </p>
            </div>

            <button
                type="button"
                class="payroll-modal-close btn-close-payroll-modal"
            >
                ×
            </button>

        </div>

        <form id="payrollConfirmForm">

            <div class="payroll-modal-body">

                <div
                    id="payrollConfirmError"
                    class="payroll-alert payroll-alert-danger"
                ></div>

                <div
                    id="payrollConfirmMessage"
                    class="payroll-confirm-message"
                ></div>

                <div
                    id="payrollConfirmReasonWrapper"
                    class="payroll-field mt-3"
                    style="display: none;"
                >

                    <label for="payrollConfirmReason">
                        السبب
                        <span
                            id="payrollConfirmReasonRequired"
                            class="text-danger"
                        >*</span>
                    </label>

                    <textarea
                        id="payrollConfirmReason"
                        class="payroll-control"
                        maxlength="1000"
                        placeholder="اكتب سبب تنفيذ العملية..."
                    ></textarea>

                    <div
                        id="payrollConfirmReasonError"
                        class="payroll-invalid-feedback"
                    ></div>

                </div>

            </div>

            <div class="payroll-modal-footer">

                <button
                    type="submit"
                    id="btnExecutePayrollAction"
                    class="payroll-btn payroll-btn-primary"
                >
                    تنفيذ العملية
                </button>

                <button
                    type="button"
                    class="payroll-btn payroll-btn-light btn-close-payroll-modal"
                >
                    إلغاء
                </button>

            </div>

        </form>

    </div>

</div>


<script>
(function bootPayrollPeriodsPage() {

    if (!window.jQuery) {
        window.setTimeout(
            bootPayrollPeriodsPage,
            50
        );

        return;
    }

    (function ($) {

        const $page = $('#payrollPeriodsPage');

        if ($page.data('initialized')) {
            return;
        }

        $page.data('initialized', true);


        const canManagePayroll =
            @json($canManagePayroll);

        const canApprovePayroll =
            @json($canApprovePayroll);


        const routes = {

            data:
                @json(
                    route(
                        'app.payroll.periods.data'
                    )
                ),

            store:
                @json(
                    route(
                        'app.payroll.periods.store'
                    )
                ),

            show:
                @json(
                    route(
                        'app.payroll.periods.show',
                        ['payrollPeriod' => '__ID__']
                    )
                ),

            update:
                @json(
                    route(
                        'app.payroll.periods.update',
                        ['payrollPeriod' => '__ID__']
                    )
                ),

            open:
                @json(
                    route(
                        'app.payroll.periods.open',
                        ['payrollPeriod' => '__ID__']
                    )
                ),

            lock:
                @json(
                    route(
                        'app.payroll.periods.lock',
                        ['payrollPeriod' => '__ID__']
                    )
                ),

            unlock:
                @json(
                    route(
                        'app.payroll.periods.unlock',
                        ['payrollPeriod' => '__ID__']
                    )
                ),

            cancel:
                @json(
                    route(
                        'app.payroll.periods.cancel',
                        ['payrollPeriod' => '__ID__']
                    )
                ),

            close:
                @json(
                    route(
                        'app.payroll.periods.close',
                        ['payrollPeriod' => '__ID__']
                    )
                ),

            destroy:
                @json(
                    route(
                        'app.payroll.periods.destroy',
                        ['payrollPeriod' => '__ID__']
                    )
                )

        };


        const monthNames = {
            1: 'يناير',
            2: 'فبراير',
            3: 'مارس',
            4: 'أبريل',
            5: 'مايو',
            6: 'يونيو',
            7: 'يوليو',
            8: 'أغسطس',
            9: 'سبتمبر',
            10: 'أكتوبر',
            11: 'نوفمبر',
            12: 'ديسمبر'
        };


        const statusLabels = {
            draft: 'مسودة',
            open: 'مفتوحة',
            processing: 'قيد المعالجة',
            review: 'قيد المراجعة',
            approved: 'معتمدة',
            paid: 'مدفوعة',
            closed: 'مغلقة',
            cancelled: 'ملغاة'
        };


        let currentPage = 1;
        let currentRequest = null;
        let searchTimer = null;

        let confirmOptions = {
            url: null,
            method: 'POST',
            requiresReason: false,
            successMessage: null
        };


        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN':
                    $('meta[name="csrf-token"]')
                        .attr('content'),

                'Accept':
                    'application/json'
            }
        });


        function routeUrl(template, id) {
            return template.replace(
                '__ID__',
                encodeURIComponent(id)
            );
        }


        function escapeHtml(value) {

            return $('<div>')
                .text(
                    value === null ||
                    value === undefined
                        ? ''
                        : String(value)
                )
                .html();
        }


        function safeValue(value, fallback) {

            if (
                value === null ||
                value === undefined ||
                value === ''
            ) {
                return fallback || '-';
            }

            return value;
        }


        function dateText(value) {

            if (!value) {
                return '-';
            }

            return escapeHtml(
                String(value).substring(0, 10)
            );
        }


        function money(value) {

            const amount = Number(value || 0);

            return new Intl.NumberFormat(
                'ar-SA',
                {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }
            ).format(amount);
        }


        function statusBadge(status, label) {

            const safeStatus =
                statusLabels[status]
                    ? status
                    : 'draft';

            const text =
                label ||
                statusLabels[status] ||
                status ||
                'غير محدد';

            return `
                <span class="payroll-status status-${safeStatus}">
                    ${escapeHtml(text)}
                </span>
            `;
        }


        function showPageMessage(type, message) {

            const $success =
                $('#payrollSuccessAlert');

            const $error =
                $('#payrollErrorAlert');

            $success.hide();
            $error.hide();

            if (!message) {
                return;
            }

            const $target =
                type === 'success'
                    ? $success
                    : $error;

            $target
                .text(message)
                .stop(true, true)
                .show();

            window.setTimeout(function () {
                $target.fadeOut(200);
            }, 5000);
        }


        function showModal($modal) {

            $('body')
                .addClass('payroll-modal-open');

            $modal
                .stop(true, true)
                .show()
                .css('display', 'flex');

            $modal.scrollTop(0);

            $modal
                .find('.payroll-modal-body')
                .scrollTop(0);
        }


        function hideModal($modal) {

            $modal
                .stop(true, true)
                .hide();

            if (
                $('.payroll-modal-overlay:visible')
                    .length === 0
            ) {
                $('body')
                    .removeClass('payroll-modal-open');
            }
        }


        function clearFormErrors() {

            $('#payrollPeriodForm')
                .find('.payroll-control')
                .removeClass('is-invalid');

            $('#payrollPeriodForm')
                .find('[data-error]')
                .text('');

            $('#payrollPeriodFormError')
                .hide()
                .text('');
        }


        function displayFormErrors(xhr) {

            clearFormErrors();

            const response =
                xhr.responseJSON || {};

            const errors =
                response.errors || {};

            let hasFieldErrors = false;

            $.each(
                errors,
                function (field, messages) {

                    hasFieldErrors = true;

                    const rootField =
                        field.split('.')[0];

                    const $input =
                        $('[name="' + rootField + '"]');

                    $input.addClass('is-invalid');

                    $('[data-error="' + rootField + '"]')
                        .text(
                            Array.isArray(messages)
                                ? messages[0]
                                : messages
                        );
                }
            );

            if (!hasFieldErrors) {

                $('#payrollPeriodFormError')
                    .text(
                        response.message ||
                        'تعذر حفظ البيانات.'
                    )
                    .show();
            }
        }


        function showGeneralError(xhr) {

            const response =
                xhr.responseJSON || {};

            let message =
                response.message ||
                'حدث خطأ أثناء تنفيذ العملية.';

            if (response.errors) {

                const firstKey =
                    Object.keys(response.errors)[0];

                if (firstKey) {
                    const fieldError =
                        response.errors[firstKey];

                    message =
                        Array.isArray(fieldError)
                            ? fieldError[0]
                            : fieldError;
                }
            }

            showPageMessage(
                'error',
                message
            );
        }


        function extractRows(response) {

            if (
                response &&
                Array.isArray(response.data)
            ) {
                return response.data;
            }

            if (
                response &&
                response.data &&
                Array.isArray(response.data.data)
            ) {
                return response.data.data;
            }

            if (
                response &&
                response.periods &&
                Array.isArray(response.periods.data)
            ) {
                return response.periods.data;
            }

            if (
                response &&
                Array.isArray(response.periods)
            ) {
                return response.periods;
            }

            return [];
        }


        function extractMeta(response) {

            if (response.meta) {
                return response.meta;
            }

            if (
                response.data &&
                response.data.meta
            ) {
                return response.data.meta;
            }

            if (
                response.periods &&
                !Array.isArray(response.periods)
            ) {
                return response.periods;
            }

            return response;
        }


        function renderActions(item) {

            const buttons = [];

            buttons.push(`
                <button
                    type="button"
                    class="payroll-btn payroll-btn-sm payroll-btn-light btn-period-details"
                    data-id="${escapeHtml(item.id)}"
                >
                    عرض
                </button>
            `);


            if (
                canManagePayroll &&
                item.can_edit
            ) {
                buttons.push(`
                    <button
                        type="button"
                        class="payroll-btn payroll-btn-sm payroll-btn-outline btn-period-edit"
                        data-id="${escapeHtml(item.id)}"
                    >
                        تعديل
                    </button>
                `);
            }


            if (
                canManagePayroll &&
                item.can_open
            ) {
                buttons.push(`
                    <button
                        type="button"
                        class="payroll-btn payroll-btn-sm payroll-btn-success btn-period-open"
                        data-id="${escapeHtml(item.id)}"
                        data-name="${escapeHtml(item.name)}"
                    >
                        فتح
                    </button>
                `);
            }


            if (
                canApprovePayroll &&
                item.can_lock
            ) {
                buttons.push(`
                    <button
                        type="button"
                        class="payroll-btn payroll-btn-sm payroll-btn-warning btn-period-lock"
                        data-id="${escapeHtml(item.id)}"
                        data-name="${escapeHtml(item.name)}"
                    >
                        قفل
                    </button>
                `);
            }


            if (
                canApprovePayroll &&
                item.can_unlock
            ) {
                buttons.push(`
                    <button
                        type="button"
                        class="payroll-btn payroll-btn-sm payroll-btn-outline btn-period-unlock"
                        data-id="${escapeHtml(item.id)}"
                        data-name="${escapeHtml(item.name)}"
                    >
                        إلغاء القفل
                    </button>
                `);
            }


            if (
                canApprovePayroll &&
                item.can_close
            ) {
                buttons.push(`
                    <button
                        type="button"
                        class="payroll-btn payroll-btn-sm payroll-btn-success btn-period-close"
                        data-id="${escapeHtml(item.id)}"
                        data-name="${escapeHtml(item.name)}"
                    >
                        إغلاق
                    </button>
                `);
            }


            if (
                canManagePayroll &&
                item.can_cancel
            ) {
                buttons.push(`
                    <button
                        type="button"
                        class="payroll-btn payroll-btn-sm payroll-btn-warning btn-period-cancel"
                        data-id="${escapeHtml(item.id)}"
                        data-name="${escapeHtml(item.name)}"
                    >
                        إلغاء
                    </button>
                `);
            }


            if (
                canManagePayroll &&
                item.can_delete
            ) {
                buttons.push(`
                    <button
                        type="button"
                        class="payroll-btn payroll-btn-sm payroll-btn-danger btn-period-delete"
                        data-id="${escapeHtml(item.id)}"
                        data-name="${escapeHtml(item.name)}"
                    >
                        حذف
                    </button>
                `);
            }


            return `
                <div class="payroll-actions">
                    ${buttons.join('')}
                </div>
            `;
        }


        function renderRows(rows, meta) {

            const $tbody =
                $('#payrollPeriodsTableBody');

            if (!rows.length) {

                $tbody.html(`
                    <tr>
                        <td
                            colspan="9"
                            class="payroll-empty"
                        >
                            <div class="payroll-empty-title">
                                لا توجد فترات رواتب
                            </div>

                            <div class="payroll-muted">
                                غيّر معايير البحث أو أضف فترة جديدة.
                            </div>
                        </td>
                    </tr>
                `);

                return;
            }

            const firstNumber =
                Number(meta.from || 1);

            const html =
                $.map(
                    rows,
                    function (item, index) {

                        const runsCount =
                            item.runs_count ??
                            item.payroll_runs_count ??
                            0;

                        const locked =
                            item.is_locked === true ||
                            Number(item.is_locked) === 1;

                        const lockBadge =
                            locked
                                ? `
                                    <span class="payroll-lock-badge payroll-locked">
                                        مقفلة
                                    </span>
                                `
                                : `
                                    <span class="payroll-lock-badge payroll-unlocked">
                                        غير مقفلة
                                    </span>
                                `;

                        return `
                            <tr>

                                <td>
                                    ${firstNumber + index}
                                </td>

                                <td>
                                    <div class="payroll-period-name">
                                        ${escapeHtml(item.name)}
                                    </div>

                                    <span class="payroll-code">
                                        ${escapeHtml(item.code)}
                                    </span>
                                </td>

                                <td>
                                    <div>
                                        ${escapeHtml(
                                            monthNames[item.month] ||
                                            item.month_name ||
                                            '-'
                                        )}
                                    </div>

                                    <div class="payroll-muted">
                                        ${escapeHtml(item.year)}
                                    </div>
                                </td>

                                <td>
                                    <div>
                                        ${dateText(item.start_date)}
                                    </div>

                                    <div class="payroll-muted">
                                        إلى ${dateText(item.end_date)}
                                    </div>
                                </td>

                                <td>
                                    ${dateText(item.payment_date)}
                                </td>

                                <td>
                                    ${statusBadge(
                                        item.status,
                                        item.status_label
                                    )}
                                </td>

                                <td>
                                    ${lockBadge}
                                </td>

                                <td>
                                    <strong>
                                        ${escapeHtml(runsCount)}
                                    </strong>

                                    <span class="payroll-muted">
                                        تشغيل
                                    </span>
                                </td>

                                <td>
                                    ${renderActions(item)}
                                </td>

                            </tr>
                        `;
                    }
                ).join('');

            $tbody.html(html);
        }


        function renderPagination(meta) {

            const current =
                Number(
                    meta.current_page || 1
                );

            const last =
                Number(
                    meta.last_page || 1
                );

            const total =
                Number(
                    meta.total || 0
                );

            const from =
                meta.from || 0;

            const to =
                meta.to || 0;

            currentPage = current;

            $('#payrollPeriodsTotal')
                .text(total + ' فترة');

            $('#payrollPaginationInfo')
                .text(
                    total
                        ? 'عرض ' +
                          from +
                          ' إلى ' +
                          to +
                          ' من ' +
                          total
                        : 'لا توجد نتائج'
                );


            const buttons = [];

            buttons.push(`
                <button
                    type="button"
                    class="payroll-page-btn btn-payroll-page"
                    data-page="${current - 1}"
                    ${current <= 1 ? 'disabled' : ''}
                >
                    السابق
                </button>
            `);


            let start =
                Math.max(
                    1,
                    current - 2
                );

            let end =
                Math.min(
                    last,
                    current + 2
                );


            if (start > 1) {

                buttons.push(`
                    <button
                        type="button"
                        class="payroll-page-btn btn-payroll-page"
                        data-page="1"
                    >
                        1
                    </button>
                `);

                if (start > 2) {
                    buttons.push(`
                        <button
                            type="button"
                            class="payroll-page-btn"
                            disabled
                        >
                            ...
                        </button>
                    `);
                }
            }


            for (
                let page = start;
                page <= end;
                page++
            ) {
                buttons.push(`
                    <button
                        type="button"
                        class="payroll-page-btn btn-payroll-page ${page === current ? 'active' : ''}"
                        data-page="${page}"
                    >
                        ${page}
                    </button>
                `);
            }


            if (end < last) {

                if (end < last - 1) {
                    buttons.push(`
                        <button
                            type="button"
                            class="payroll-page-btn"
                            disabled
                        >
                            ...
                        </button>
                    `);
                }

                buttons.push(`
                    <button
                        type="button"
                        class="payroll-page-btn btn-payroll-page"
                        data-page="${last}"
                    >
                        ${last}
                    </button>
                `);
            }


            buttons.push(`
                <button
                    type="button"
                    class="payroll-page-btn btn-payroll-page"
                    data-page="${current + 1}"
                    ${current >= last ? 'disabled' : ''}
                >
                    التالي
                </button>
            `);


            $('#payrollPaginationButtons')
                .html(buttons.join(''));
        }


        function loadPayrollPeriods(page) {

            page =
                Number(page || 1);

            if (currentRequest) {
                currentRequest.abort();
            }

            $('#payrollPeriodsTableBody')
                .html(`
                    <tr>
                        <td
                            colspan="9"
                            class="payroll-loading"
                        >
                            جاري تحميل البيانات...
                        </td>
                    </tr>
                `);

            currentRequest =
                $.ajax({

                    url: routes.data,

                    type: 'GET',

                    data: {
                        page: page,
                        search:
                            $('#periodSearch')
                                .val()
                                .trim(),

                        year:
                            $('#periodYearFilter')
                                .val(),

                        month:
                            $('#periodMonthFilter')
                                .val(),

                        status:
                            $('#periodStatusFilter')
                                .val(),

                        is_locked:
                            $('#periodLockedFilter')
                                .val(),

                        per_page: 15
                    },

                    success: function (response) {

                        const rows =
                            extractRows(response);

                        const meta =
                            extractMeta(response);

                        renderRows(
                            rows,
                            meta
                        );

                        renderPagination(meta);
                    },

                    error: function (xhr, status) {

                        if (status === 'abort') {
                            return;
                        }

                        $('#payrollPeriodsTableBody')
                            .html(`
                                <tr>
                                    <td
                                        colspan="9"
                                        class="payroll-empty"
                                    >
                                        <div class="payroll-empty-title">
                                            تعذر تحميل البيانات
                                        </div>

                                        <div class="payroll-muted">
                                            أعد المحاولة مرة أخرى.
                                        </div>
                                    </td>
                                </tr>
                            `);

                        showGeneralError(xhr);
                    },

                    complete: function () {
                        currentRequest = null;
                    }

                });
        }


        function setSuggestedDates() {

            const year =
                Number(
                    $('#payrollPeriodYear').val()
                );

            const month =
                Number(
                    $('#payrollPeriodMonth').val()
                );

            if (!year || !month) {
                return;
            }

            const firstDay =
                year +
                '-' +
                String(month).padStart(2, '0') +
                '-01';

            const lastDate =
                new Date(
                    year,
                    month,
                    0
                );

            const lastDay =
                year +
                '-' +
                String(month).padStart(2, '0') +
                '-' +
                String(
                    lastDate.getDate()
                ).padStart(2, '0');

            $('#payrollPeriodStartDate')
                .val(firstDay);

            $('#payrollPeriodEndDate')
                .val(lastDay);

            $('#payrollPeriodPaymentDate')
                .val(lastDay);

            if (
                !$('#payrollPeriodName')
                    .val()
                    .trim()
            ) {
                $('#payrollPeriodName')
                    .val(
                        'رواتب شهر ' +
                        monthNames[month] +
                        ' ' +
                        year
                    );
            }
        }


        function resetPayrollPeriodForm() {

            $('#payrollPeriodForm')[0]
                .reset();

            $('#payrollPeriodId')
                .val('');

            $('#payrollPeriodFormTitle')
                .text('إضافة فترة رواتب');

            $('#payrollPeriodYear')
                .val(
                    @json(now()->year)
                );

            $('#payrollPeriodMonth')
                .val(
                    @json(now()->month)
                );

            clearFormErrors();
            setSuggestedDates();
        }


        function extractPeriod(response) {

            return (
                response.payroll_period ||
                response.period ||
                response.data ||
                response
            );
        }


        function openEditModal(id) {

            clearFormErrors();

            $('#payrollPeriodFormTitle')
                .text('تعديل فترة الرواتب');

            $('#btnSavePayrollPeriod')
                .prop('disabled', true)
                .text('جاري التحميل...');

            showModal(
                $('#payrollPeriodFormModal')
            );

            $.ajax({

                url:
                    routeUrl(
                        routes.show,
                        id
                    ),

                type: 'GET',

                success: function (response) {

                    const item =
                        extractPeriod(response);

                    $('#payrollPeriodId')
                        .val(item.id);

                    $('#payrollPeriodName')
                        .val(item.name || '');

                    $('#payrollPeriodYear')
                        .val(item.year || '');

                    $('#payrollPeriodMonth')
                        .val(item.month || '');

                    $('#payrollPeriodStartDate')
                        .val(
                            item.start_date
                                ? String(item.start_date)
                                    .substring(0, 10)
                                : ''
                        );

                    $('#payrollPeriodEndDate')
                        .val(
                            item.end_date
                                ? String(item.end_date)
                                    .substring(0, 10)
                                : ''
                        );

                    $('#payrollPeriodPaymentDate')
                        .val(
                            item.payment_date
                                ? String(item.payment_date)
                                    .substring(0, 10)
                                : ''
                        );
                },

                error: function (xhr) {

                    hideModal(
                        $('#payrollPeriodFormModal')
                    );

                    showGeneralError(xhr);
                },

                complete: function () {

                    $('#btnSavePayrollPeriod')
                        .prop('disabled', false)
                        .text('حفظ البيانات');
                }

            });
        }


        function savePayrollPeriod() {

            clearFormErrors();

            const id =
                $('#payrollPeriodId').val();

            const editing =
                Boolean(id);

            const url =
                editing
                    ? routeUrl(
                        routes.update,
                        id
                    )
                    : routes.store;

            const data = {
                name:
                    $('#payrollPeriodName')
                        .val()
                        .trim(),

                year:
                    $('#payrollPeriodYear')
                        .val(),

                month:
                    $('#payrollPeriodMonth')
                        .val(),

                start_date:
                    $('#payrollPeriodStartDate')
                        .val(),

                end_date:
                    $('#payrollPeriodEndDate')
                        .val(),

                payment_date:
                    $('#payrollPeriodPaymentDate')
                        .val()
            };

            if (editing) {
                data._method = 'PUT';
            }

            const $button =
                $('#btnSavePayrollPeriod');

            $button
                .prop('disabled', true)
                .text('جاري الحفظ...');

            $.ajax({

                url: url,

                type: 'POST',

                data: data,

                success: function (response) {

                    hideModal(
                        $('#payrollPeriodFormModal')
                    );

                    showPageMessage(
                        'success',
                        response.message ||
                        (
                            editing
                                ? 'تم تحديث فترة الرواتب بنجاح.'
                                : 'تم إنشاء فترة الرواتب بنجاح.'
                        )
                    );

                    loadPayrollPeriods(
                        editing
                            ? currentPage
                            : 1
                    );
                },

                error: function (xhr) {
                    displayFormErrors(xhr);
                },

                complete: function () {

                    $button
                        .prop('disabled', false)
                        .text('حفظ البيانات');
                }

            });
        }


        function renderRuns(runs) {

            if (
                !Array.isArray(runs) ||
                !runs.length
            ) {
                return `
                    <div class="payroll-empty">
                        <div class="payroll-empty-title">
                            لا توجد تشغيلات رواتب
                        </div>

                        <div class="payroll-muted">
                            لم يتم إنشاء تشغيل رواتب لهذه الفترة بعد.
                        </div>
                    </div>
                `;
            }

            const rows =
                $.map(
                    runs,
                    function (run) {

                        return `
                            <tr>
                                <td>
                                    <span class="payroll-code">
                                        ${escapeHtml(
                                            run.run_number ||
                                            run.code ||
                                            '-'
                                        )}
                                    </span>
                                </td>

                                <td>
                                    ${escapeHtml(
                                        run.type_label ||
                                        run.type ||
                                        '-'
                                    )}
                                </td>

                                <td>
                                    ${statusBadge(
                                        run.status,
                                        run.status_label
                                    )}
                                </td>

                                <td>
                                    ${escapeHtml(
                                        run.employee_count || 0
                                    )}
                                </td>

                                <td>
                                    ${money(
                                        run.total_net_salary
                                    )}

                                    ${escapeHtml(
                                        run.currency_code || ''
                                    )}
                                </td>
                            </tr>
                        `;
                    }
                ).join('');

            return `
                <div class="payroll-table-responsive">
                    <table class="payroll-runs-table">
                        <thead>
                            <tr>
                                <th>رقم التشغيل</th>
                                <th>النوع</th>
                                <th>الحالة</th>
                                <th>الموظفون</th>
                                <th>صافي الرواتب</th>
                            </tr>
                        </thead>

                        <tbody>
                            ${rows}
                        </tbody>
                    </table>
                </div>
            `;
        }


        function showPeriodDetails(id) {

            const $body =
                $('#payrollPeriodDetailsBody');

            $body.html(
                'جاري تحميل البيانات...'
            );

            showModal(
                $('#payrollPeriodDetailsModal')
            );

            $.ajax({

                url:
                    routeUrl(
                        routes.show,
                        id
                    ),

                type: 'GET',

                success: function (response) {

                    const item =
                        extractPeriod(response);

                    const runs =
                        item.runs ||
                        response.runs ||
                        [];

                    const locked =
                        item.is_locked === true ||
                        Number(item.is_locked) === 1;

                    $body.html(`

                        <div class="payroll-details-grid">

                            <div class="payroll-detail-box">
                                <div class="payroll-detail-label">
                                    اسم الفترة
                                </div>

                                <div class="payroll-detail-value">
                                    ${escapeHtml(item.name)}
                                </div>
                            </div>

                            <div class="payroll-detail-box">
                                <div class="payroll-detail-label">
                                    كود الفترة
                                </div>

                                <div
                                    class="payroll-detail-value payroll-code"
                                >
                                    ${escapeHtml(item.code)}
                                </div>
                            </div>

                            <div class="payroll-detail-box">
                                <div class="payroll-detail-label">
                                    الشهر والسنة
                                </div>

                                <div class="payroll-detail-value">
                                    ${escapeHtml(
                                        monthNames[item.month] ||
                                        item.month_name ||
                                        '-'
                                    )}
                                    ${escapeHtml(item.year)}
                                </div>
                            </div>

                            <div class="payroll-detail-box">
                                <div class="payroll-detail-label">
                                    تاريخ البداية
                                </div>

                                <div class="payroll-detail-value">
                                    ${dateText(item.start_date)}
                                </div>
                            </div>

                            <div class="payroll-detail-box">
                                <div class="payroll-detail-label">
                                    تاريخ النهاية
                                </div>

                                <div class="payroll-detail-value">
                                    ${dateText(item.end_date)}
                                </div>
                            </div>

                            <div class="payroll-detail-box">
                                <div class="payroll-detail-label">
                                    تاريخ الصرف
                                </div>

                                <div class="payroll-detail-value">
                                    ${dateText(item.payment_date)}
                                </div>
                            </div>

                            <div class="payroll-detail-box">
                                <div class="payroll-detail-label">
                                    الحالة
                                </div>

                                <div class="payroll-detail-value">
                                    ${statusBadge(
                                        item.status,
                                        item.status_label
                                    )}
                                </div>
                            </div>

                            <div class="payroll-detail-box">
                                <div class="payroll-detail-label">
                                    حالة القفل
                                </div>

                                <div class="payroll-detail-value">
                                    ${
                                        locked
                                            ? 'الفترة مقفلة'
                                            : 'الفترة غير مقفلة'
                                    }
                                </div>
                            </div>

                            <div class="payroll-detail-box">
                                <div class="payroll-detail-label">
                                    تاريخ القفل
                                </div>

                                <div class="payroll-detail-value">
                                    ${dateText(item.locked_at)}
                                </div>
                            </div>

                        </div>

                        <h4 class="payroll-section-title">
                            تشغيلات الرواتب
                        </h4>

                        ${renderRuns(runs)}

                    `);
                },

                error: function (xhr) {

                    hideModal(
                        $('#payrollPeriodDetailsModal')
                    );

                    showGeneralError(xhr);
                }

            });
        }


        function openConfirmModal(options) {

            confirmOptions = $.extend(
                {
                    url: null,
                    method: 'POST',
                    requiresReason: false,
                    showReason: false,
                    successMessage: null,
                    buttonClass:
                        'payroll-btn-primary'
                },
                options
            );

            $('#payrollConfirmTitle')
                .text(
                    confirmOptions.title ||
                    'تأكيد العملية'
                );

            $('#payrollConfirmMessage')
                .text(
                    confirmOptions.message ||
                    'هل أنت متأكد من تنفيذ هذه العملية؟'
                );

            $('#payrollConfirmReason')
                .val('')
                .removeClass('is-invalid');

            $('#payrollConfirmReasonError')
                .text('');

            $('#payrollConfirmError')
                .hide()
                .text('');

            $('#payrollConfirmReasonRequired')
                .toggle(
                    Boolean(
                        confirmOptions.requiresReason
                    )
                );

            $('#payrollConfirmReasonWrapper')
                .toggle(
                    Boolean(
                        confirmOptions.showReason
                    )
                );

            $('#btnExecutePayrollAction')
                .removeClass(
                    'payroll-btn-primary ' +
                    'payroll-btn-danger ' +
                    'payroll-btn-warning ' +
                    'payroll-btn-success'
                )
                .addClass(
                    confirmOptions.buttonClass
                )
                .text(
                    confirmOptions.buttonText ||
                    'تنفيذ العملية'
                );

            showModal(
                $('#payrollPeriodConfirmModal')
            );
        }


        function executeConfirmedAction() {

            const reason =
                $('#payrollConfirmReason')
                    .val()
                    .trim();

            $('#payrollConfirmReason')
                .removeClass('is-invalid');

            $('#payrollConfirmReasonError')
                .text('');

            $('#payrollConfirmError')
                .hide()
                .text('');

            if (
                confirmOptions.requiresReason &&
                !reason
            ) {
                $('#payrollConfirmReason')
                    .addClass('is-invalid');

                $('#payrollConfirmReasonError')
                    .text(
                        'يرجى كتابة سبب تنفيذ العملية.'
                    );

                return;
            }

            const $button =
                $('#btnExecutePayrollAction');

            const oldText =
                $button.text();

            $button
                .prop('disabled', true)
                .text('جاري التنفيذ...');

            $.ajax({

                url: confirmOptions.url,

                type: confirmOptions.method,

                data: {
                    reason: reason
                },

                success: function (response) {

                    hideModal(
                        $('#payrollPeriodConfirmModal')
                    );

                    showPageMessage(
                        'success',
                        response.message ||
                        confirmOptions.successMessage ||
                        'تم تنفيذ العملية بنجاح.'
                    );

                    loadPayrollPeriods(
                        currentPage
                    );
                },

                error: function (xhr) {

                    const response =
                        xhr.responseJSON || {};

                    let message =
                        response.message ||
                        'تعذر تنفيذ العملية.';

                    if (
                        response.errors &&
                        response.errors.reason
                    ) {
                        const error =
                            response.errors.reason;

                        $('#payrollConfirmReason')
                            .addClass('is-invalid');

                        $('#payrollConfirmReasonError')
                            .text(
                                Array.isArray(error)
                                    ? error[0]
                                    : error
                            );

                        return;
                    }

                    $('#payrollConfirmError')
                        .text(message)
                        .show();
                },

                complete: function () {

                    $button
                        .prop('disabled', false)
                        .text(oldText);
                }

            });
        }


        $('#payrollPeriodFilterForm')
            .on('submit', function (event) {

                event.preventDefault();

                loadPayrollPeriods(1);
            });


        $('#periodSearch')
            .on('input', function () {

                window.clearTimeout(
                    searchTimer
                );

                searchTimer =
                    window.setTimeout(
                        function () {
                            loadPayrollPeriods(1);
                        },
                        450
                    );
            });


        $(
            '#periodYearFilter, ' +
            '#periodMonthFilter, ' +
            '#periodStatusFilter, ' +
            '#periodLockedFilter'
        ).on('change', function () {
            loadPayrollPeriods(1);
        });


        $('#btnResetPayrollFilters')
            .on('click', function () {

                $('#payrollPeriodFilterForm')[0]
                    .reset();

                $('#periodSearch')
                    .val('');

                loadPayrollPeriods(1);
            });


        $('#btnCreatePayrollPeriod')
            .on('click', function () {

                resetPayrollPeriodForm();

                showModal(
                    $('#payrollPeriodFormModal')
                );
            });


        $(
            '#payrollPeriodYear, ' +
            '#payrollPeriodMonth'
        ).on('change', function () {

            if (
                !$('#payrollPeriodId')
                    .val()
            ) {
                setSuggestedDates();
            }
        });


        $('#payrollPeriodForm')
            .on('submit', function (event) {

                event.preventDefault();

                savePayrollPeriod();
            });


        $(document)
            .on(
                'click',
                '.btn-payroll-page',
                function () {

                    if (
                        $(this)
                            .prop('disabled')
                    ) {
                        return;
                    }

                    loadPayrollPeriods(
                        $(this).data('page')
                    );
                }
            );


        $(document)
            .on(
                'click',
                '.btn-period-details',
                function () {

                    showPeriodDetails(
                        $(this).data('id')
                    );
                }
            );


        $(document)
            .on(
                'click',
                '.btn-period-edit',
                function () {

                    openEditModal(
                        $(this).data('id')
                    );
                }
            );


        $(document)
            .on(
                'click',
                '.btn-period-open',
                function () {

                    const id =
                        $(this).data('id');

                    const name =
                        $(this).data('name');

                    openConfirmModal({

                        title:
                            'فتح فترة الرواتب',

                        message:
                            'سيتم فتح الفترة "' +
                            name +
                            '" والسماح بإنشاء تشغيلات الرواتب عليها.',

                        url:
                            routeUrl(
                                routes.open,
                                id
                            ),

                        method:
                            'POST',

                        buttonText:
                            'فتح الفترة',

                        buttonClass:
                            'payroll-btn-success',

                        successMessage:
                            'تم فتح فترة الرواتب بنجاح.'
                    });
                }
            );


        $(document)
            .on(
                'click',
                '.btn-period-lock',
                function () {

                    const id =
                        $(this).data('id');

                    const name =
                        $(this).data('name');

                    openConfirmModal({

                        title:
                            'قفل فترة الرواتب',

                        message:
                            'سيتم قفل الفترة "' +
                            name +
                            '" ومنع تعديل بياناتها.',

                        url:
                            routeUrl(
                                routes.lock,
                                id
                            ),

                        method:
                            'POST',

                        buttonText:
                            'قفل الفترة',

                        buttonClass:
                            'payroll-btn-warning',

                        successMessage:
                            'تم قفل فترة الرواتب بنجاح.'
                    });
                }
            );


        $(document)
            .on(
                'click',
                '.btn-period-unlock',
                function () {

                    const id =
                        $(this).data('id');

                    const name =
                        $(this).data('name');

                    openConfirmModal({

                        title:
                            'إلغاء قفل الفترة',

                        message:
                            'سيتم إلغاء قفل الفترة "' +
                            name +
                            '". اكتب سبب إلغاء القفل.',

                        url:
                            routeUrl(
                                routes.unlock,
                                id
                            ),

                        method:
                            'POST',

                        showReason:
                            true,

                        requiresReason:
                            true,

                        buttonText:
                            'إلغاء القفل',

                        buttonClass:
                            'payroll-btn-warning',

                        successMessage:
                            'تم إلغاء قفل الفترة بنجاح.'
                    });
                }
            );


        $(document)
            .on(
                'click',
                '.btn-period-close',
                function () {

                    const id =
                        $(this).data('id');

                    const name =
                        $(this).data('name');

                    openConfirmModal({

                        title:
                            'إغلاق فترة الرواتب',

                        message:
                            'سيتم إغلاق الفترة "' +
                            name +
                            '". لن تقبل الفترة تشغيلات أو تعديلات جديدة.',

                        url:
                            routeUrl(
                                routes.close,
                                id
                            ),

                        method:
                            'POST',

                        buttonText:
                            'إغلاق الفترة',

                        buttonClass:
                            'payroll-btn-success',

                        successMessage:
                            'تم إغلاق فترة الرواتب بنجاح.'
                    });
                }
            );


        $(document)
            .on(
                'click',
                '.btn-period-cancel',
                function () {

                    const id =
                        $(this).data('id');

                    const name =
                        $(this).data('name');

                    openConfirmModal({

                        title:
                            'إلغاء فترة الرواتب',

                        message:
                            'سيتم إلغاء الفترة "' +
                            name +
                            '". اكتب سبب الإلغاء للمتابعة.',

                        url:
                            routeUrl(
                                routes.cancel,
                                id
                            ),

                        method:
                            'POST',

                        showReason:
                            true,

                        requiresReason:
                            true,

                        buttonText:
                            'إلغاء الفترة',

                        buttonClass:
                            'payroll-btn-danger',

                        successMessage:
                            'تم إلغاء فترة الرواتب.'
                    });
                }
            );


        $(document)
            .on(
                'click',
                '.btn-period-delete',
                function () {

                    const id =
                        $(this).data('id');

                    const name =
                        $(this).data('name');

                    openConfirmModal({

                        title:
                            'حذف فترة الرواتب',

                        message:
                            'هل تريد حذف الفترة "' +
                            name +
                            '"؟ لا يمكن حذف فترة مرتبطة بتشغيلات رواتب.',

                        url:
                            routeUrl(
                                routes.destroy,
                                id
                            ),

                        method:
                            'DELETE',

                        buttonText:
                            'حذف الفترة',

                        buttonClass:
                            'payroll-btn-danger',

                        successMessage:
                            'تم حذف فترة الرواتب بنجاح.'
                    });
                }
            );


        $('#payrollConfirmForm')
            .on('submit', function (event) {

                event.preventDefault();

                executeConfirmedAction();
            });


        $('.btn-close-payroll-modal')
            .on('click', function () {

                hideModal(
                    $(this)
                        .closest(
                            '.payroll-modal-overlay'
                        )
                );
            });


        $('.payroll-modal-overlay')
            .on('click', function (event) {

                if (
                    event.target === this
                ) {
                    hideModal(
                        $(this)
                    );
                }
            });


        $(document)
            .on('keydown', function (event) {

                if (event.key === 'Escape') {

                    const $modal =
                        $('.payroll-modal-overlay:visible')
                            .last();

                    if ($modal.length) {
                        hideModal($modal);
                    }
                }
            });


        loadPayrollPeriods(1);

    })(window.jQuery);

})();
</script>

@endsection