@extends('layouts.tenant')

@section('title', 'تسويات الرواتب')
@section('page-title', 'تسويات الرواتب')

@section('content')

@php
    $canManagePayroll = auth()->user()->can('payroll.manage');
    $canApprovePayroll = auth()->user()->can('payroll.approve');

    $adjustmentRoutes = [
        'data' => route(
            'app.payroll.adjustments.data'
        ),

        'options' => route(
            'app.payroll.adjustments.options'
        ),

        'store' => route(
            'app.payroll.adjustments.store'
        ),

        'show' => route(
            'app.payroll.adjustments.show',
            [
                'payrollAdjustment' => '__ID__',
            ]
        ),

        'update' => route(
            'app.payroll.adjustments.update',
            [
                'payrollAdjustment' => '__ID__',
            ]
        ),

        'submit' => route(
            'app.payroll.adjustments.submit',
            [
                'payrollAdjustment' => '__ID__',
            ]
        ),

        'approve' => route(
            'app.payroll.adjustments.approve',
            [
                'payrollAdjustment' => '__ID__',
            ]
        ),

        'reject' => route(
            'app.payroll.adjustments.reject',
            [
                'payrollAdjustment' => '__ID__',
            ]
        ),

        'returnDraft' => route(
            'app.payroll.adjustments.return-draft',
            [
                'payrollAdjustment' => '__ID__',
            ]
        ),

        'cancel' => route(
            'app.payroll.adjustments.cancel',
            [
                'payrollAdjustment' => '__ID__',
            ]
        ),

        'destroy' => route(
            'app.payroll.adjustments.destroy',
            [
                'payrollAdjustment' => '__ID__',
            ]
        ),
    ];
@endphp

<style>
    #payrollAdjustmentsPage {
        direction: rtl;
    }

    .pa-card {
        background: #fff;
        border: 1px solid #e7ebf2;
        border-radius: 16px;
        box-shadow: 0 5px 20px rgba(15, 23, 42, .05);
    }

    .pa-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }

    .pa-title {
        margin: 0 0 5px;
        color: #172033;
        font-size: 22px;
        font-weight: 800;
    }

    .pa-description {
        margin: 0;
        color: #7b8598;
        font-size: 13px;
    }

    .pa-alert {
        display: none;
        margin-bottom: 16px;
        padding: 13px 15px;
        border: 1px solid transparent;
        border-radius: 10px;
        font-size: 14px;
    }

    .pa-alert-success {
        background: #e8f9f0;
        border-color: #b9ebce;
        color: #14794a;
    }

    .pa-alert-danger {
        background: #fff0f1;
        border-color: #ffc4ca;
        color: #bd2e3e;
    }

    .pa-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        min-height: 37px;
        padding: 7px 13px;
        border: 1px solid transparent;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        white-space: nowrap;
        text-decoration: none;
        transition: .18s ease;
    }

    .pa-btn:hover {
        transform: translateY(-1px);
    }

    .pa-btn:disabled {
        opacity: .6;
        cursor: not-allowed;
        transform: none;
    }

    .pa-btn-primary {
        background: #1677ff;
        border-color: #1677ff;
        color: #fff;
    }

    .pa-btn-success {
        background: #168653;
        border-color: #168653;
        color: #fff;
    }

    .pa-btn-warning {
        background: #fff7e2;
        border-color: #edc15c;
        color: #946200;
    }

    .pa-btn-danger {
        background: #fff0f1;
        border-color: #efa1aa;
        color: #bd3040;
    }

    .pa-btn-light {
        background: #f8fafc;
        border-color: #dfe5ed;
        color: #3e485c;
    }

    .pa-btn-outline {
        background: #fff;
        border-color: #1677ff;
        color: #1677ff;
    }

    .pa-btn-sm {
        min-height: 31px;
        padding: 5px 8px;
        font-size: 11px;
    }

    .pa-filters {
        padding: 18px;
        margin-bottom: 20px;
    }

    .pa-filter-grid {
        display: grid;
        grid-template-columns: minmax(210px, 2fr) repeat(4, minmax(130px, 1fr)) auto;
        gap: 12px;
        align-items: end;
    }

    .pa-field label {
        display: block;
        margin-bottom: 6px;
        color: #465168;
        font-size: 12px;
        font-weight: 700;
    }

    .pa-control {
        width: 100%;
        height: 41px;
        padding: 8px 11px;
        border: 1px solid #dce3ed;
        border-radius: 8px;
        background: #fff;
        color: #2d374b;
        outline: none;
    }

    textarea.pa-control {
        height: auto;
        min-height: 95px;
        resize: vertical;
    }

    .pa-control:focus {
        border-color: #1677ff;
        box-shadow: 0 0 0 3px rgba(22, 119, 255, .1);
    }

    .pa-control.is-invalid {
        border-color: #dc3545;
    }

    .pa-error {
        min-height: 18px;
        margin-top: 5px;
        color: #dc3545;
        font-size: 11px;
    }

    .pa-table-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
        padding: 17px 19px;
        border-bottom: 1px solid #e7ebf2;
    }

    .pa-table-title {
        margin: 0;
        color: #263147;
        font-size: 17px;
        font-weight: 800;
    }

    .pa-count {
        padding: 4px 10px;
        border-radius: 20px;
        background: #eaf2ff;
        color: #1677ff;
        font-size: 12px;
        font-weight: 800;
    }

    .pa-table-responsive {
        width: 100%;
        overflow-x: auto;
    }

    .pa-table {
        width: 100%;
        min-width: 1250px;
        border-collapse: collapse;
    }

    .pa-table th {
        padding: 12px;
        border-bottom: 1px solid #dfe5ed;
        background: #f8fafc;
        color: #475269;
        font-size: 12px;
        font-weight: 800;
        text-align: right;
        white-space: nowrap;
    }

    .pa-table td {
        padding: 13px 12px;
        border-bottom: 1px solid #edf1f5;
        color: #344054;
        font-size: 12px;
        vertical-align: middle;
    }

    .pa-table tbody tr:hover {
        background: #fafcff;
    }

    .pa-name {
        margin-bottom: 4px;
        color: #172033;
        font-weight: 800;
    }

    .pa-code {
        direction: ltr;
        display: inline-block;
        color: #1677ff;
        font-family: monospace;
        font-weight: 800;
    }

    .pa-muted {
        color: #8791a4;
        font-size: 11px;
    }

    .pa-amount-earning {
        color: #13804c;
        font-weight: 800;
    }

    .pa-amount-deduction {
        color: #c33142;
        font-weight: 800;
    }

    .pa-status {
        display: inline-flex;
        justify-content: center;
        min-width: 85px;
        padding: 5px 9px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 800;
    }

    .pa-status-draft {
        background: #edf1f6;
        color: #586477;
    }

    .pa-status-pending {
        background: #fff4d7;
        color: #956300;
    }

    .pa-status-approved {
        background: #e7f0ff;
        color: #155fc1;
    }

    .pa-status-applied {
        background: #e2f8f2;
        color: #087b62;
    }

    .pa-status-rejected,
    .pa-status-cancelled {
        background: #ffecef;
        color: #c33142;
    }

    .pa-actions {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }

    .pa-loading,
    .pa-empty {
        padding: 45px 20px !important;
        text-align: center;
        color: #718096;
    }

    .pa-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
        padding: 15px 18px;
        border-top: 1px solid #e7ebf2;
    }

    .pa-page-buttons {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }

    .pa-page-btn {
        min-width: 34px;
        height: 34px;
        border: 1px solid #dfe5ed;
        border-radius: 7px;
        background: #fff;
        color: #344054;
        cursor: pointer;
    }

    .pa-page-btn.active {
        background: #1677ff;
        border-color: #1677ff;
        color: #fff;
    }

    .pa-page-btn:disabled {
        opacity: .5;
        cursor: not-allowed;
    }

    .pa-modal {
        position: fixed;
        inset: 0;
        z-index: 1080;
        display: none;
        align-items: flex-start;
        justify-content: center;
        padding: 20px 12px;
        overflow-x: hidden;
        overflow-y: auto;
        background: rgba(15, 23, 42, .65);
    }

    .pa-modal-panel {
        display: flex;
        flex-direction: column;
        width: 100%;
        max-width: 900px;
        max-height: calc(100vh - 40px);
        margin: auto;
        overflow: hidden;
        background: #fff;
        border-radius: 17px;
        box-shadow: 0 25px 80px rgba(15, 23, 42, .3);
    }

    .pa-modal-panel-sm {
        max-width: 560px;
    }

    .pa-modal-header {
        flex: 0 0 auto;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 15px;
        padding: 17px 20px;
        border-bottom: 1px solid #e7ebf2;
    }

    .pa-modal-title {
        margin: 0 0 5px;
        color: #172033;
        font-size: 18px;
        font-weight: 800;
    }

    .pa-modal-subtitle {
        margin: 0;
        color: #818b9d;
        font-size: 12px;
    }

    .pa-modal-close {
        width: 35px;
        height: 35px;
        border: 0;
        border-radius: 8px;
        background: #f1f4f8;
        color: #445067;
        font-size: 21px;
        cursor: pointer;
    }

    .pa-modal-body {
        flex: 1 1 auto;
        min-height: 0;
        padding: 20px;
        overflow-x: hidden;
        overflow-y: auto;
        overscroll-behavior: contain;
    }

    .pa-modal-footer {
        flex: 0 0 auto;
        display: flex;
        gap: 8px;
        padding: 14px 20px;
        border-top: 1px solid #e7ebf2;
    }

    body.pa-modal-open {
        overflow: hidden !important;
    }

    .pa-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .pa-full {
        grid-column: 1 / -1;
    }

    .pa-summary-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 11px;
        margin-bottom: 18px;
    }

    .pa-summary-box {
        min-height: 82px;
        padding: 13px;
        border: 1px solid #e5eaf1;
        border-radius: 11px;
        background: #f9fbfd;
    }

    .pa-summary-label {
        margin-bottom: 7px;
        color: #80899b;
        font-size: 11px;
    }

    .pa-summary-value {
        color: #243047;
        font-size: 14px;
        font-weight: 800;
        overflow-wrap: anywhere;
    }

    .pa-confirm-message {
        padding: 14px;
        border: 1px solid #efd99f;
        border-radius: 10px;
        background: #fff9e8;
        color: #705616;
        font-size: 13px;
        line-height: 1.8;
    }

    .pa-history-item {
        padding: 11px 13px;
        border-right: 3px solid #1677ff;
        border-radius: 7px;
        background: #f8fafc;
        margin-bottom: 8px;
    }

    @media (max-width: 1100px) {
        .pa-filter-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 767px) {
        .pa-filter-grid,
        .pa-form-grid,
        .pa-summary-grid {
            grid-template-columns: 1fr;
        }

        .pa-full {
            grid-column: auto;
        }

        .pa-modal {
            padding: 8px;
        }

        .pa-modal-panel {
            max-height: calc(100vh - 16px);
        }

        .pa-header .pa-btn {
            width: 100%;
        }
    }
</style>


<div id="payrollAdjustmentsPage">

    <div
        id="paSuccessAlert"
        class="pa-alert pa-alert-success"
    ></div>

    <div
        id="paErrorAlert"
        class="pa-alert pa-alert-danger"
    ></div>

    <div class="pa-header">

        <div>
            <h2 class="pa-title">
                تسويات الرواتب
            </h2>

            <p class="pa-description">
                إضافة المكافآت والخصومات اليدوية وإرسالها للاعتماد قبل تشغيل الرواتب.
            </p>
        </div>

        @if($canManagePayroll)
            <button
                type="button"
                id="btnCreateAdjustment"
                class="pa-btn pa-btn-primary"
            >
                <span>+</span>
                إضافة تسوية
            </button>
        @endif

    </div>


    <div class="pa-card pa-filters">

        <form id="adjustmentsFilterForm">

            <div class="pa-filter-grid">

                <div class="pa-field">
                    <label for="adjustmentSearch">
                        البحث
                    </label>

                    <input
                        type="search"
                        id="adjustmentSearch"
                        class="pa-control"
                        placeholder="رقم التسوية أو الموظف..."
                        autocomplete="off"
                    >
                </div>

                <div class="pa-field">
                    <label for="adjustmentPeriodFilter">
                        فترة الرواتب
                    </label>

                    <select
                        id="adjustmentPeriodFilter"
                        class="pa-control"
                    >
                        <option value="">
                            جميع الفترات
                        </option>
                    </select>
                </div>

                <div class="pa-field">
                    <label for="adjustmentTypeFilter">
                        النوع
                    </label>

                    <select
                        id="adjustmentTypeFilter"
                        class="pa-control"
                    >
                        <option value="">
                            جميع الأنواع
                        </option>

                        <option value="earning">
                            استحقاق
                        </option>

                        <option value="deduction">
                            خصم
                        </option>
                    </select>
                </div>

                <div class="pa-field">
                    <label for="adjustmentStatusFilter">
                        الحالة
                    </label>

                    <select
                        id="adjustmentStatusFilter"
                        class="pa-control"
                    >
                        <option value="">
                            جميع الحالات
                        </option>

                        <option value="draft">
                            مسودة
                        </option>

                        <option value="pending">
                            بانتظار الاعتماد
                        </option>

                        <option value="approved">
                            معتمدة
                        </option>

                        <option value="applied">
                            مطبقة
                        </option>

                        <option value="rejected">
                            مرفوضة
                        </option>

                        <option value="cancelled">
                            ملغاة
                        </option>
                    </select>
                </div>

                <div class="pa-field">
                    <label for="adjustmentPerPage">
                        عدد السجلات
                    </label>

                    <select
                        id="adjustmentPerPage"
                        class="pa-control"
                    >
                        <option value="10">10</option>
                        <option value="15" selected>15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>

                <div class="d-flex gap-2">

                    <button
                        type="submit"
                        class="pa-btn pa-btn-primary"
                    >
                        بحث
                    </button>

                    <button
                        type="button"
                        id="btnResetAdjustmentFilters"
                        class="pa-btn pa-btn-light"
                    >
                        إعادة
                    </button>

                </div>

            </div>

        </form>

    </div>


    <div class="pa-card">

        <div class="pa-table-header">

            <h3 class="pa-table-title">
                قائمة تسويات الرواتب
            </h3>

            <span
                id="adjustmentsCount"
                class="pa-count"
            >
                0 تسوية
            </span>

        </div>

        <div class="pa-table-responsive">

            <table class="pa-table">

                <thead>
                    <tr>
                        <th>#</th>
                        <th>رقم التسوية</th>
                        <th>الموظف</th>
                        <th>النوع</th>
                        <th>المكون</th>
                        <th>المبلغ</th>
                        <th>تاريخ الاستحقاق</th>
                        <th>فترة الرواتب</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>

                <tbody id="adjustmentsTableBody">

                    <tr>
                        <td
                            colspan="10"
                            class="pa-loading"
                        >
                            جاري تحميل البيانات...
                        </td>
                    </tr>

                </tbody>

            </table>

        </div>

        <div class="pa-pagination">

            <div
                id="adjustmentsPaginationInfo"
                class="pa-muted"
            ></div>

            <div
                id="adjustmentsPaginationButtons"
                class="pa-page-buttons"
            ></div>

        </div>

    </div>

</div>


{{-- إضافة وتعديل التسوية --}}
<div
    id="adjustmentFormModal"
    class="pa-modal"
>

    <div class="pa-modal-panel">

        <div class="pa-modal-header">

            <div>
                <h3
                    id="adjustmentFormTitle"
                    class="pa-modal-title"
                >
                    إضافة تسوية راتب
                </h3>

                <p class="pa-modal-subtitle">
                    أدخل تفاصيل الاستحقاق أو الخصم.
                </p>
            </div>

            <button
                type="button"
                class="pa-modal-close btn-close-pa-modal"
            >
                ×
            </button>

        </div>

        <form id="adjustmentForm">

            <div class="pa-modal-body">

                <input
                    type="hidden"
                    id="adjustmentId"
                >

                <div
                    id="adjustmentFormError"
                    class="pa-alert pa-alert-danger"
                ></div>

                <div class="pa-form-grid">

                    <div class="pa-field pa-full">

                        <label for="adjustmentEmployee">
                            الموظف
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            id="adjustmentEmployee"
                            name="employee_id"
                            class="pa-control"
                        >
                            <option value="">
                                اختر الموظف
                            </option>
                        </select>

                        <div
                            class="pa-error"
                            data-error="employee_id"
                        ></div>

                    </div>

                    <div class="pa-field">

                        <label for="adjustmentType">
                            نوع التسوية
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            id="adjustmentType"
                            name="type"
                            class="pa-control"
                        >
                            <option value="earning">
                                استحقاق
                            </option>

                            <option value="deduction">
                                خصم
                            </option>
                        </select>

                        <div
                            class="pa-error"
                            data-error="type"
                        ></div>

                    </div>

                    <div class="pa-field">

                        <label for="adjustmentComponent">
                            مكون الراتب
                        </label>

                        <select
                            id="adjustmentComponent"
                            name="salary_component_id"
                            class="pa-control"
                        >
                            <option value="">
                                بدون مكون محدد
                            </option>
                        </select>

                        <div
                            class="pa-error"
                            data-error="salary_component_id"
                        ></div>

                    </div>

                    <div class="pa-field">

                        <label for="adjustmentAmount">
                            المبلغ
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="number"
                            id="adjustmentAmount"
                            name="amount"
                            class="pa-control"
                            min="0.01"
                            step="0.01"
                            dir="ltr"
                        >

                        <div
                            class="pa-error"
                            data-error="amount"
                        ></div>

                    </div>

                    <div class="pa-field">

                        <label for="adjustmentCurrency">
                            العملة
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            id="adjustmentCurrency"
                            name="currency_code"
                            class="pa-control"
                            maxlength="3"
                            dir="ltr"
                        >

                        <div
                            class="pa-error"
                            data-error="currency_code"
                        ></div>

                    </div>

                    <div class="pa-field">

                        <label for="adjustmentPeriod">
                            فترة الرواتب
                        </label>

                        <select
                            id="adjustmentPeriod"
                            name="payroll_period_id"
                            class="pa-control"
                        >
                            <option value="">
                                بدون فترة محددة
                            </option>
                        </select>

                        <div
                            class="pa-error"
                            data-error="payroll_period_id"
                        ></div>

                    </div>

                    <div class="pa-field">

                        <label for="adjustmentEffectiveDate">
                            تاريخ الاستحقاق
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="date"
                            id="adjustmentEffectiveDate"
                            name="effective_date"
                            class="pa-control"
                        >

                        <div
                            class="pa-error"
                            data-error="effective_date"
                        ></div>

                    </div>

                    <div class="pa-field pa-full">

                        <label for="adjustmentReason">
                            سبب التسوية
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            id="adjustmentReason"
                            name="reason"
                            class="pa-control"
                            maxlength="500"
                            placeholder="مثال: مكافأة أداء شهرية"
                        >

                        <div
                            class="pa-error"
                            data-error="reason"
                        ></div>

                    </div>

                    <div class="pa-field pa-full">

                        <label for="adjustmentNotes">
                            ملاحظات
                        </label>

                        <textarea
                            id="adjustmentNotes"
                            name="notes"
                            class="pa-control"
                            maxlength="2000"
                        ></textarea>

                        <div
                            class="pa-error"
                            data-error="notes"
                        ></div>

                    </div>

                </div>

            </div>

            <div class="pa-modal-footer">

                <button
                    type="submit"
                    id="btnSaveAdjustment"
                    class="pa-btn pa-btn-primary"
                >
                    حفظ التسوية
                </button>

                <button
                    type="button"
                    class="pa-btn pa-btn-light btn-close-pa-modal"
                >
                    إلغاء
                </button>

            </div>

        </form>

    </div>

</div>


{{-- تفاصيل التسوية --}}
<div
    id="adjustmentDetailsModal"
    class="pa-modal"
>

    <div class="pa-modal-panel">

        <div class="pa-modal-header">

            <div>
                <h3 class="pa-modal-title">
                    تفاصيل تسوية الراتب
                </h3>

                <p
                    id="adjustmentDetailsSubtitle"
                    class="pa-modal-subtitle"
                ></p>
            </div>

            <button
                type="button"
                class="pa-modal-close btn-close-pa-modal"
            >
                ×
            </button>

        </div>

        <div
            id="adjustmentDetailsBody"
            class="pa-modal-body"
        >
            جاري تحميل البيانات...
        </div>

        <div class="pa-modal-footer">

            <button
                type="button"
                class="pa-btn pa-btn-light btn-close-pa-modal"
            >
                إغلاق
            </button>

        </div>

    </div>

</div>


{{-- تأكيد العمليات --}}
<div
    id="adjustmentConfirmModal"
    class="pa-modal"
>

    <div class="pa-modal-panel pa-modal-panel-sm">

        <div class="pa-modal-header">

            <div>
                <h3
                    id="adjustmentConfirmTitle"
                    class="pa-modal-title"
                >
                    تأكيد العملية
                </h3>

                <p class="pa-modal-subtitle">
                    راجع العملية قبل التنفيذ.
                </p>
            </div>

            <button
                type="button"
                class="pa-modal-close btn-close-pa-modal"
            >
                ×
            </button>

        </div>

        <form id="adjustmentConfirmForm">

            <div class="pa-modal-body">

                <div
                    id="adjustmentConfirmError"
                    class="pa-alert pa-alert-danger"
                ></div>

                <div
                    id="adjustmentConfirmMessage"
                    class="pa-confirm-message"
                ></div>

                <div
                    id="adjustmentConfirmReasonSection"
                    class="pa-field mt-3"
                    style="display: none;"
                >

                    <label for="adjustmentConfirmReason">
                        السبب
                        <span class="text-danger">*</span>
                    </label>

                    <textarea
                        id="adjustmentConfirmReason"
                        class="pa-control"
                        maxlength="1000"
                    ></textarea>

                    <div
                        id="adjustmentConfirmReasonError"
                        class="pa-error"
                    ></div>

                </div>

            </div>

            <div class="pa-modal-footer">

                <button
                    type="submit"
                    id="btnExecuteAdjustmentAction"
                    class="pa-btn pa-btn-primary"
                >
                    تنفيذ
                </button>

                <button
                    type="button"
                    class="pa-btn pa-btn-light btn-close-pa-modal"
                >
                    إلغاء
                </button>

            </div>

        </form>

    </div>

</div>


<script>
(function bootPayrollAdjustmentsPage() {

    if (!window.jQuery) {
        window.setTimeout(
            bootPayrollAdjustmentsPage,
            50
        );

        return;
    }

    (function ($) {

        const $page =
            $('#payrollAdjustmentsPage');

        if ($page.data('initialized')) {
            return;
        }

        $page.data(
            'initialized',
            true
        );

        const routes =
            {{ Illuminate\Support\Js::from($adjustmentRoutes) }};

        let currentPage = 1;
        let searchTimer = null;
        let currentRequest = null;

        let availableEmployees = [];
        let availablePeriods = [];
        let availableComponents = [];
        let defaultCurrency = 'SAR';

        let confirmOptions = {
            url: null,
            method: 'POST',
            requiresReason: false
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


        function routeUrl(
            template,
            id
        ) {
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


        function money(value) {

            return new Intl.NumberFormat(
                'ar-SA',
                {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }
            ).format(
                Number(value || 0)
            );
        }


        function dateText(value) {

            if (!value) {
                return '-';
            }

            return escapeHtml(
                String(value)
                    .substring(0, 10)
            );
        }


        function statusBadge(
            status,
            label
        ) {
            return `
                <span class="pa-status pa-status-${escapeHtml(status)}">
                    ${escapeHtml(label || status)}
                </span>
            `;
        }


        function showMessage(
            type,
            message
        ) {
            const $target =
                type === 'success'
                    ? $('#paSuccessAlert')
                    : $('#paErrorAlert');

            $('#paSuccessAlert, #paErrorAlert')
                .hide();

            $target
                .text(message)
                .stop(true, true)
                .show();

            window.setTimeout(
                function () {
                    $target.fadeOut(200);
                },
                5000
            );
        }


        function showModal($modal) {

            $('body')
                .addClass(
                    'pa-modal-open'
                );

            $modal
                .stop(true, true)
                .show()
                .css(
                    'display',
                    'flex'
                );

            $modal.scrollTop(0);

            $modal
                .find('.pa-modal-body')
                .scrollTop(0);
        }


        function hideModal($modal) {

            $modal
                .stop(true, true)
                .hide();

            if (
                $('.pa-modal:visible')
                    .length === 0
            ) {
                $('body')
                    .removeClass(
                        'pa-modal-open'
                    );
            }
        }


        function firstError(xhr) {

            const response =
                xhr.responseJSON || {};

            if (response.errors) {
                const key =
                    Object.keys(
                        response.errors
                    )[0];

                if (key) {
                    const error =
                        response.errors[key];

                    return Array.isArray(error)
                        ? error[0]
                        : error;
                }
            }

            return response.message ||
                'حدث خطأ أثناء تنفيذ العملية.';
        }


        function extractRows(response) {

            if (Array.isArray(response.data)) {
                return response.data;
            }

            if (
                response.data &&
                Array.isArray(response.data.data)
            ) {
                return response.data.data;
            }

            return [];
        }


        function extractMeta(response) {

            return response.meta ||
                response.data?.meta ||
                response;
        }


        function renderActions(item) {

            const buttons = [];

            buttons.push(`
                <button
                    type="button"
                    class="pa-btn pa-btn-sm pa-btn-light btn-adjustment-details"
                    data-id="${item.id}"
                >
                    عرض
                </button>
            `);

            if (item.can_edit) {
                buttons.push(`
                    <button
                        type="button"
                        class="pa-btn pa-btn-sm pa-btn-outline btn-adjustment-edit"
                        data-id="${item.id}"
                    >
                        تعديل
                    </button>
                `);
            }

            if (item.can_submit) {
                buttons.push(`
                    <button
                        type="button"
                        class="pa-btn pa-btn-sm pa-btn-warning btn-adjustment-submit"
                        data-id="${item.id}"
                        data-number="${escapeHtml(item.adjustment_number)}"
                    >
                        إرسال
                    </button>
                `);
            }

            if (item.can_approve) {
                buttons.push(`
                    <button
                        type="button"
                        class="pa-btn pa-btn-sm pa-btn-success btn-adjustment-approve"
                        data-id="${item.id}"
                        data-number="${escapeHtml(item.adjustment_number)}"
                    >
                        اعتماد
                    </button>
                `);
            }

            if (item.can_reject) {
                buttons.push(`
                    <button
                        type="button"
                        class="pa-btn pa-btn-sm pa-btn-danger btn-adjustment-reject"
                        data-id="${item.id}"
                        data-number="${escapeHtml(item.adjustment_number)}"
                    >
                        رفض
                    </button>
                `);
            }

            if (item.can_return_to_draft) {
                buttons.push(`
                    <button
                        type="button"
                        class="pa-btn pa-btn-sm pa-btn-outline btn-adjustment-return"
                        data-id="${item.id}"
                    >
                        إعادة لمسودة
                    </button>
                `);
            }

            if (item.can_cancel) {
                buttons.push(`
                    <button
                        type="button"
                        class="pa-btn pa-btn-sm pa-btn-warning btn-adjustment-cancel"
                        data-id="${item.id}"
                        data-number="${escapeHtml(item.adjustment_number)}"
                    >
                        إلغاء
                    </button>
                `);
            }

            if (item.can_delete) {
                buttons.push(`
                    <button
                        type="button"
                        class="pa-btn pa-btn-sm pa-btn-danger btn-adjustment-delete"
                        data-id="${item.id}"
                        data-number="${escapeHtml(item.adjustment_number)}"
                    >
                        حذف
                    </button>
                `);
            }

            return `
                <div class="pa-actions">
                    ${buttons.join('')}
                </div>
            `;
        }


        function renderRows(
            rows,
            meta
        ) {
            const $body =
                $('#adjustmentsTableBody');

            if (!rows.length) {
                $body.html(`
                    <tr>
                        <td
                            colspan="10"
                            class="pa-empty"
                        >
                            لا توجد تسويات رواتب.
                        </td>
                    </tr>
                `);

                return;
            }

            const start =
                Number(meta.from || 1);

            $body.html(
                $.map(
                    rows,
                    function (item, index) {

                        const amountClass =
                            item.type === 'earning'
                                ? 'pa-amount-earning'
                                : 'pa-amount-deduction';

                        const sign =
                            item.type === 'earning'
                                ? '+'
                                : '-';

                        return `
                            <tr>

                                <td>
                                    ${start + index}
                                </td>

                                <td>
                                    <span class="pa-code">
                                        ${escapeHtml(item.adjustment_number)}
                                    </span>
                                </td>

                                <td>
                                    <div class="pa-name">
                                        ${escapeHtml(item.employee?.name || '-')}
                                    </div>

                                    <div class="pa-muted">
                                        ${escapeHtml(item.employee?.employee_number || '')}
                                    </div>
                                </td>

                                <td>
                                    ${escapeHtml(item.type_label)}
                                </td>

                                <td>
                                    <div class="pa-name">
                                        ${escapeHtml(item.salary_component?.name || 'تسوية عامة')}
                                    </div>

                                    <div class="pa-muted">
                                        ${escapeHtml(item.salary_component?.code || '')}
                                    </div>
                                </td>

                                <td class="${amountClass}">
                                    ${sign}
                                    ${money(item.amount)}
                                    ${escapeHtml(item.currency_code)}
                                </td>

                                <td>
                                    ${dateText(item.effective_date)}
                                </td>

                                <td>
                                    <div class="pa-name">
                                        ${escapeHtml(item.period?.name || 'غير محددة')}
                                    </div>

                                    <div class="pa-muted">
                                        ${escapeHtml(item.period?.code || '')}
                                    </div>
                                </td>

                                <td>
                                    ${statusBadge(
                                        item.status,
                                        item.status_label
                                    )}
                                </td>

                                <td>
                                    ${renderActions(item)}
                                </td>

                            </tr>
                        `;
                    }
                ).join('')
            );
        }


        function renderPagination(meta) {

            const current =
                Number(meta.current_page || 1);

            const last =
                Number(meta.last_page || 1);

            const buttons = [];

            buttons.push(`
                <button
                    type="button"
                    class="pa-page-btn btn-adjustments-page"
                    data-page="${current - 1}"
                    ${current <= 1 ? 'disabled' : ''}
                >
                    السابق
                </button>
            `);

            const start =
                Math.max(
                    1,
                    current - 2
                );

            const end =
                Math.min(
                    last,
                    current + 2
                );

            for (
                let page = start;
                page <= end;
                page++
            ) {
                buttons.push(`
                    <button
                        type="button"
                        class="pa-page-btn btn-adjustments-page ${page === current ? 'active' : ''}"
                        data-page="${page}"
                    >
                        ${page}
                    </button>
                `);
            }

            buttons.push(`
                <button
                    type="button"
                    class="pa-page-btn btn-adjustments-page"
                    data-page="${current + 1}"
                    ${current >= last ? 'disabled' : ''}
                >
                    التالي
                </button>
            `);

            $('#adjustmentsPaginationButtons')
                .html(
                    buttons.join('')
                );
        }


        function loadAdjustments(page) {

            page =
                Number(page || 1);

            if (currentRequest) {
                currentRequest.abort();
            }

            $('#adjustmentsTableBody')
                .html(`
                    <tr>
                        <td
                            colspan="10"
                            class="pa-loading"
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
                            $('#adjustmentSearch')
                                .val()
                                .trim(),

                        payroll_period_id:
                            $('#adjustmentPeriodFilter')
                                .val(),

                        type:
                            $('#adjustmentTypeFilter')
                                .val(),

                        status:
                            $('#adjustmentStatusFilter')
                                .val(),

                        per_page:
                            $('#adjustmentPerPage')
                                .val()
                    },

                    success: function (response) {

                        const rows =
                            extractRows(response);

                        const meta =
                            extractMeta(response);

                        currentPage =
                            Number(
                                meta.current_page || 1
                            );

                        renderRows(
                            rows,
                            meta
                        );

                        $('#adjustmentsCount')
                            .text(
                                Number(meta.total || 0) +
                                ' تسوية'
                            );

                        $('#adjustmentsPaginationInfo')
                            .text(
                                meta.total
                                    ? 'عرض ' +
                                      meta.from +
                                      ' إلى ' +
                                      meta.to +
                                      ' من ' +
                                      meta.total
                                    : 'لا توجد نتائج'
                            );

                        renderPagination(meta);
                    },

                    error: function (
                        xhr,
                        status
                    ) {
                        if (status === 'abort') {
                            return;
                        }

                        $('#adjustmentsTableBody')
                            .html(`
                                <tr>
                                    <td
                                        colspan="10"
                                        class="pa-empty"
                                    >
                                        تعذر تحميل البيانات.
                                    </td>
                                </tr>
                            `);

                        showMessage(
                            'error',
                            firstError(xhr)
                        );
                    },

                    complete: function () {
                        currentRequest = null;
                    }

                });
        }


        function loadOptions() {

            $.ajax({

                url: routes.options,

                type: 'GET',

                success: function (response) {

                    availableEmployees =
                        response.employees || [];

                    availablePeriods =
                        response.periods || [];

                    availableComponents =
                        response.components || [];

                    defaultCurrency =
                        response.default_currency ||
                        'SAR';

                    renderOptions();
                },

                error: function (xhr) {
                    showMessage(
                        'error',
                        firstError(xhr)
                    );
                }

            });
        }


        function renderOptions() {

            const employeeOptions = [
                '<option value="">اختر الموظف</option>'
            ];

            $.each(
                availableEmployees,
                function (_, employee) {
                    employeeOptions.push(`
                        <option value="${employee.id}">
                            ${escapeHtml(employee.name)}
                            -
                            ${escapeHtml(employee.employee_number)}
                        </option>
                    `);
                }
            );

            $('#adjustmentEmployee')
                .html(
                    employeeOptions.join('')
                );

            const periodFilter = [
                '<option value="">جميع الفترات</option>'
            ];

            const periodForm = [
                '<option value="">بدون فترة محددة</option>'
            ];

            $.each(
                availablePeriods,
                function (_, period) {

                    const label =
                        escapeHtml(period.name) +
                        ' - ' +
                        escapeHtml(period.code);

                    periodFilter.push(`
                        <option value="${period.id}">
                            ${label}
                        </option>
                    `);

                    periodForm.push(`
                        <option
                            value="${period.id}"
                            data-start-date="${escapeHtml(period.start_date)}"
                            data-end-date="${escapeHtml(period.end_date)}"
                        >
                            ${label}
                        </option>
                    `);
                }
            );

            $('#adjustmentPeriodFilter')
                .html(
                    periodFilter.join('')
                );

            $('#adjustmentPeriod')
                .html(
                    periodForm.join('')
                );

            $('#adjustmentCurrency')
                .val(defaultCurrency);

            renderComponentOptions();
        }


        function renderComponentOptions(
            selectedId
        ) {
            const type =
                $('#adjustmentType').val();

            const options = [
                '<option value="">بدون مكون محدد</option>'
            ];

            $.each(
                availableComponents,
                function (_, component) {

                    if (
                        component.type !== type
                    ) {
                        return;
                    }

                    options.push(`
                        <option
                            value="${component.id}"
                            ${Number(selectedId) === Number(component.id) ? 'selected' : ''}
                        >
                            ${escapeHtml(component.name)}
                            -
                            ${escapeHtml(component.code)}
                        </option>
                    `);
                }
            );

            $('#adjustmentComponent')
                .html(
                    options.join('')
                );
        }


        function clearFormErrors() {

            $('#adjustmentForm')
                .find('.pa-control')
                .removeClass('is-invalid');

            $('#adjustmentForm')
                .find('[data-error]')
                .text('');

            $('#adjustmentFormError')
                .hide()
                .text('');
        }


        function showFormErrors(xhr) {

            clearFormErrors();

            const response =
                xhr.responseJSON || {};

            if (!response.errors) {
                $('#adjustmentFormError')
                    .text(
                        response.message ||
                        'تعذر حفظ التسوية.'
                    )
                    .show();

                return;
            }

            $.each(
                response.errors,
                function (field, errors) {

                    const root =
                        field.split('.')[0];

                    $('[name="' + root + '"]')
                        .addClass('is-invalid');

                    $('[data-error="' + root + '"]')
                        .text(
                            Array.isArray(errors)
                                ? errors[0]
                                : errors
                        );
                }
            );
        }


        function resetForm() {

            $('#adjustmentForm')[0]
                .reset();

            $('#adjustmentId')
                .val('');

            $('#adjustmentFormTitle')
                .text(
                    'إضافة تسوية راتب'
                );

            $('#adjustmentType')
                .val('earning');

            $('#adjustmentCurrency')
                .val(defaultCurrency);

            $('#adjustmentEffectiveDate')
                .val(
                    new Date()
                        .toISOString()
                        .substring(0, 10)
                );

            clearFormErrors();
            renderComponentOptions();
        }


        function openEdit(id) {

            resetForm();

            $('#adjustmentFormTitle')
                .text(
                    'تعديل تسوية الراتب'
                );

            const $button =
                $('#btnSaveAdjustment');

            $button
                .prop('disabled', true)
                .text('جاري التحميل...');

            showModal(
                $('#adjustmentFormModal')
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
                        response.adjustment;

                    $('#adjustmentId')
                        .val(item.id);

                    $('#adjustmentEmployee')
                        .val(item.employee_id);

                    $('#adjustmentType')
                        .val(item.type);

                    renderComponentOptions(
                        item.salary_component_id
                    );

                    $('#adjustmentPeriod')
                        .val(
                            item.payroll_period_id ||
                            ''
                        );

                    $('#adjustmentAmount')
                        .val(item.amount);

                    $('#adjustmentCurrency')
                        .val(item.currency_code);

                    $('#adjustmentEffectiveDate')
                        .val(item.effective_date);

                    $('#adjustmentReason')
                        .val(item.reason);

                    $('#adjustmentNotes')
                        .val(item.notes || '');
                },

                error: function (xhr) {

                    hideModal(
                        $('#adjustmentFormModal')
                    );

                    showMessage(
                        'error',
                        firstError(xhr)
                    );
                },

                complete: function () {

                    $button
                        .prop('disabled', false)
                        .text('حفظ التسوية');
                }

            });
        }


        function saveAdjustment() {

            clearFormErrors();

            const id =
                $('#adjustmentId').val();

            const editing =
                Boolean(id);

            const data = {
                employee_id:
                    $('#adjustmentEmployee')
                        .val(),

                payroll_period_id:
                    $('#adjustmentPeriod')
                        .val(),

                salary_component_id:
                    $('#adjustmentComponent')
                        .val(),

                type:
                    $('#adjustmentType')
                        .val(),

                amount:
                    $('#adjustmentAmount')
                        .val(),

                currency_code:
                    $('#adjustmentCurrency')
                        .val()
                        .trim()
                        .toUpperCase(),

                effective_date:
                    $('#adjustmentEffectiveDate')
                        .val(),

                reason:
                    $('#adjustmentReason')
                        .val()
                        .trim(),

                notes:
                    $('#adjustmentNotes')
                        .val()
                        .trim()
            };

            if (editing) {
                data._method = 'PUT';
            }

            const $button =
                $('#btnSaveAdjustment');

            $button
                .prop('disabled', true)
                .text('جاري الحفظ...');

            $.ajax({

                url:
                    editing
                        ? routeUrl(
                            routes.update,
                            id
                        )
                        : routes.store,

                type: 'POST',

                data: data,

                success: function (response) {

                    hideModal(
                        $('#adjustmentFormModal')
                    );

                    showMessage(
                        'success',
                        response.message ||
                        'تم حفظ التسوية.'
                    );

                    loadAdjustments(
                        editing
                            ? currentPage
                            : 1
                    );
                },

                error: function (xhr) {
                    showFormErrors(xhr);
                },

                complete: function () {

                    $button
                        .prop('disabled', false)
                        .text('حفظ التسوية');
                }

            });
        }


        function showDetails(id) {

            $('#adjustmentDetailsBody')
                .html(
                    '<div class="pa-loading">جاري تحميل البيانات...</div>'
                );

            showModal(
                $('#adjustmentDetailsModal')
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
                        response.adjustment;

                    $('#adjustmentDetailsSubtitle')
                        .text(
                            item.adjustment_number
                        );

                    const history =
                        item.metadata?.history ||
                        [];

                    const historyHtml =
                        $.map(
                            history,
                            function (event) {
                                return `
                                    <div class="pa-history-item">
                                        <div class="fw-bold">
                                            ${escapeHtml(event.event)}
                                        </div>

                                        <div class="pa-muted">
                                            ${escapeHtml(event.performed_by_name || '')}
                                            -
                                            ${escapeHtml(event.performed_at || '')}
                                        </div>

                                        ${
                                            event.reason
                                                ? `
                                                    <div class="mt-1">
                                                        ${escapeHtml(event.reason)}
                                                    </div>
                                                `
                                                : ''
                                        }
                                    </div>
                                `;
                            }
                        ).join('');

                    $('#adjustmentDetailsBody')
                        .html(`

                            <div class="pa-summary-grid">

                                <div class="pa-summary-box">
                                    <div class="pa-summary-label">
                                        رقم التسوية
                                    </div>

                                    <div class="pa-summary-value pa-code">
                                        ${escapeHtml(item.adjustment_number)}
                                    </div>
                                </div>

                                <div class="pa-summary-box">
                                    <div class="pa-summary-label">
                                        الموظف
                                    </div>

                                    <div class="pa-summary-value">
                                        ${escapeHtml(item.employee?.name || '-')}
                                    </div>
                                </div>

                                <div class="pa-summary-box">
                                    <div class="pa-summary-label">
                                        الحالة
                                    </div>

                                    <div class="pa-summary-value">
                                        ${statusBadge(
                                            item.status,
                                            item.status_label
                                        )}
                                    </div>
                                </div>

                                <div class="pa-summary-box">
                                    <div class="pa-summary-label">
                                        نوع التسوية
                                    </div>

                                    <div class="pa-summary-value">
                                        ${escapeHtml(item.type_label)}
                                    </div>
                                </div>

                                <div class="pa-summary-box">
                                    <div class="pa-summary-label">
                                        المبلغ
                                    </div>

                                    <div class="pa-summary-value">
                                        ${money(item.amount)}
                                        ${escapeHtml(item.currency_code)}
                                    </div>
                                </div>

                                <div class="pa-summary-box">
                                    <div class="pa-summary-label">
                                        تاريخ الاستحقاق
                                    </div>

                                    <div class="pa-summary-value">
                                        ${dateText(item.effective_date)}
                                    </div>
                                </div>

                                <div class="pa-summary-box">
                                    <div class="pa-summary-label">
                                        مكون الراتب
                                    </div>

                                    <div class="pa-summary-value">
                                        ${escapeHtml(item.salary_component?.name || 'تسوية عامة')}
                                    </div>
                                </div>

                                <div class="pa-summary-box">
                                    <div class="pa-summary-label">
                                        فترة الرواتب
                                    </div>

                                    <div class="pa-summary-value">
                                        ${escapeHtml(item.period?.name || 'غير محددة')}
                                    </div>
                                </div>

                                <div class="pa-summary-box">
                                    <div class="pa-summary-label">
                                        تشغيل الرواتب
                                    </div>

                                    <div class="pa-summary-value">
                                        ${escapeHtml(item.applied_run?.run_number || '-')}
                                    </div>
                                </div>

                            </div>

                            <div class="pa-card p-3 mb-3">
                                <div class="pa-summary-label">
                                    سبب التسوية
                                </div>

                                <div class="fw-bold">
                                    ${escapeHtml(item.reason)}
                                </div>
                            </div>

                            <div class="pa-card p-3 mb-3">
                                <div class="pa-summary-label">
                                    الملاحظات
                                </div>

                                <div>
                                    ${escapeHtml(item.notes || '-')}
                                </div>
                            </div>

                            <h4 class="pa-table-title mb-3">
                                سجل العمليات
                            </h4>

                            ${
                                historyHtml ||
                                '<div class="pa-muted">لا يوجد سجل عمليات.</div>'
                            }

                        `);
                },

                error: function (xhr) {

                    hideModal(
                        $('#adjustmentDetailsModal')
                    );

                    showMessage(
                        'error',
                        firstError(xhr)
                    );
                }

            });
        }


        function openConfirm(options) {

            confirmOptions =
                $.extend(
                    {
                        url: null,
                        method: 'POST',
                        requiresReason: false,
                        buttonClass: 'pa-btn-primary'
                    },
                    options
                );

            $('#adjustmentConfirmTitle')
                .text(
                    confirmOptions.title ||
                    'تأكيد العملية'
                );

            $('#adjustmentConfirmMessage')
                .text(
                    confirmOptions.message
                );

            $('#adjustmentConfirmReason')
                .val('')
                .removeClass('is-invalid');

            $('#adjustmentConfirmReasonError')
                .text('');

            $('#adjustmentConfirmError')
                .hide()
                .text('');

            $('#adjustmentConfirmReasonSection')
                .toggle(
                    Boolean(
                        confirmOptions.requiresReason
                    )
                );

            $('#btnExecuteAdjustmentAction')
                .removeClass(
                    'pa-btn-primary ' +
                    'pa-btn-success ' +
                    'pa-btn-warning ' +
                    'pa-btn-danger'
                )
                .addClass(
                    confirmOptions.buttonClass
                )
                .text(
                    confirmOptions.buttonText ||
                    'تنفيذ'
                );

            showModal(
                $('#adjustmentConfirmModal')
            );
        }


        function executeConfirmAction() {

            const reason =
                $('#adjustmentConfirmReason')
                    .val()
                    .trim();

            $('#adjustmentConfirmReason')
                .removeClass('is-invalid');

            $('#adjustmentConfirmReasonError')
                .text('');

            if (
                confirmOptions.requiresReason &&
                !reason
            ) {
                $('#adjustmentConfirmReason')
                    .addClass('is-invalid');

                $('#adjustmentConfirmReasonError')
                    .text(
                        'يرجى كتابة سبب العملية.'
                    );

                return;
            }

            const $button =
                $('#btnExecuteAdjustmentAction');

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
                        $('#adjustmentConfirmModal')
                    );

                    showMessage(
                        'success',
                        response.message ||
                        'تم تنفيذ العملية.'
                    );

                    loadAdjustments(
                        currentPage
                    );

                    loadOptions();
                },

                error: function (xhr) {

                    $('#adjustmentConfirmError')
                        .text(
                            firstError(xhr)
                        )
                        .show();
                },

                complete: function () {

                    $button
                        .prop('disabled', false)
                        .text(oldText);
                }

            });
        }


        $('#adjustmentsFilterForm')
            .on('submit', function (event) {

                event.preventDefault();
                loadAdjustments(1);
            });


        $('#adjustmentSearch')
            .on('input', function () {

                window.clearTimeout(
                    searchTimer
                );

                searchTimer =
                    window.setTimeout(
                        function () {
                            loadAdjustments(1);
                        },
                        400
                    );
            });


        $(
            '#adjustmentPeriodFilter, ' +
            '#adjustmentTypeFilter, ' +
            '#adjustmentStatusFilter, ' +
            '#adjustmentPerPage'
        ).on('change', function () {
            loadAdjustments(1);
        });


        $('#btnResetAdjustmentFilters')
            .on('click', function () {

                $('#adjustmentsFilterForm')[0]
                    .reset();

                loadAdjustments(1);
            });


        $('#btnCreateAdjustment')
            .on('click', function () {

                resetForm();

                showModal(
                    $('#adjustmentFormModal')
                );
            });


        $('#adjustmentType')
            .on('change', function () {
                renderComponentOptions();
            });


        $('#adjustmentPeriod')
            .on('change', function () {

                const $option =
                    $(this)
                        .find('option:selected');

                const startDate =
                    $option.data('start-date');

                if (
                    startDate &&
                    !$('#adjustmentId').val()
                ) {
                    $('#adjustmentEffectiveDate')
                        .val(startDate);
                }
            });


        $('#adjustmentForm')
            .on('submit', function (event) {

                event.preventDefault();
                saveAdjustment();
            });


        $(document)
            .on(
                'click',
                '.btn-adjustments-page',
                function () {

                    if (!$(this).prop('disabled')) {
                        loadAdjustments(
                            $(this).data('page')
                        );
                    }
                }
            );


        $(document)
            .on(
                'click',
                '.btn-adjustment-details',
                function () {
                    showDetails(
                        $(this).data('id')
                    );
                }
            );


        $(document)
            .on(
                'click',
                '.btn-adjustment-edit',
                function () {
                    openEdit(
                        $(this).data('id')
                    );
                }
            );


        $(document)
            .on(
                'click',
                '.btn-adjustment-submit',
                function () {

                    openConfirm({
                        title:
                            'إرسال التسوية للاعتماد',

                        message:
                            'سيتم إرسال التسوية ' +
                            $(this).data('number') +
                            ' إلى مسؤول اعتماد الرواتب.',

                        url:
                            routeUrl(
                                routes.submit,
                                $(this).data('id')
                            ),

                        buttonText:
                            'إرسال للاعتماد',

                        buttonClass:
                            'pa-btn-warning'
                    });
                }
            );


        $(document)
            .on(
                'click',
                '.btn-adjustment-approve',
                function () {

                    openConfirm({
                        title:
                            'اعتماد تسوية الراتب',

                        message:
                            'ستصبح التسوية جاهزة للتطبيق على تشغيل الرواتب.',

                        url:
                            routeUrl(
                                routes.approve,
                                $(this).data('id')
                            ),

                        buttonText:
                            'اعتماد التسوية',

                        buttonClass:
                            'pa-btn-success'
                    });
                }
            );


        $(document)
            .on(
                'click',
                '.btn-adjustment-reject',
                function () {

                    openConfirm({
                        title:
                            'رفض تسوية الراتب',

                        message:
                            'اكتب سبب رفض التسوية.',

                        url:
                            routeUrl(
                                routes.reject,
                                $(this).data('id')
                            ),

                        requiresReason:
                            true,

                        buttonText:
                            'رفض التسوية',

                        buttonClass:
                            'pa-btn-danger'
                    });
                }
            );


        $(document)
            .on(
                'click',
                '.btn-adjustment-return',
                function () {

                    openConfirm({
                        title:
                            'إعادة التسوية إلى مسودة',

                        message:
                            'ستتم إعادة التسوية المرفوضة إلى المسودة لتعديلها.',

                        url:
                            routeUrl(
                                routes.returnDraft,
                                $(this).data('id')
                            ),

                        buttonText:
                            'إعادة لمسودة',

                        buttonClass:
                            'pa-btn-warning'
                    });
                }
            );


        $(document)
            .on(
                'click',
                '.btn-adjustment-cancel',
                function () {

                    openConfirm({
                        title:
                            'إلغاء تسوية الراتب',

                        message:
                            'اكتب سبب إلغاء التسوية.',

                        url:
                            routeUrl(
                                routes.cancel,
                                $(this).data('id')
                            ),

                        requiresReason:
                            true,

                        buttonText:
                            'إلغاء التسوية',

                        buttonClass:
                            'pa-btn-danger'
                    });
                }
            );


        $(document)
            .on(
                'click',
                '.btn-adjustment-delete',
                function () {

                    openConfirm({
                        title:
                            'حذف تسوية الراتب',

                        message:
                            'سيتم حذف مسودة التسوية نهائيًا.',

                        url:
                            routeUrl(
                                routes.destroy,
                                $(this).data('id')
                            ),

                        method:
                            'DELETE',

                        buttonText:
                            'حذف التسوية',

                        buttonClass:
                            'pa-btn-danger'
                    });
                }
            );


        $('#adjustmentConfirmForm')
            .on('submit', function (event) {

                event.preventDefault();
                executeConfirmAction();
            });


        $('.btn-close-pa-modal')
            .on('click', function () {

                hideModal(
                    $(this)
                        .closest('.pa-modal')
                );
            });


        $('.pa-modal')
            .on('click', function (event) {

                if (event.target === this) {
                    hideModal(
                        $(this)
                    );
                }
            });


        $(document)
            .on('keydown', function (event) {

                if (event.key === 'Escape') {
                    const $modal =
                        $('.pa-modal:visible')
                            .last();

                    if ($modal.length) {
                        hideModal($modal);
                    }
                }
            });


        loadOptions();
        loadAdjustments(1);

    })(window.jQuery);

})();
</script>

@endsection