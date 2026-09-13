@extends('layouts.tenant')

@section('title', 'تشغيل الرواتب')
@section('page-title', 'تشغيل الرواتب')

@section('content')

@php
    $canProcessPayroll = auth()->user()->can('payroll.process');
    $canApprovePayroll = auth()->user()->can('payroll.approve');
    $canManagePayroll = auth()->user()->can('payroll.manage');
@endphp

<style>
    #payrollRunsPage {
        direction: rtl;
    }

    .pr-card {
        background: #fff;
        border: 1px solid #e7ebf2;
        border-radius: 16px;
        box-shadow: 0 5px 20px rgba(15, 23, 42, .05);
    }

    .pr-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }

    .pr-title {
        margin: 0 0 5px;
        color: #172033;
        font-size: 22px;
        font-weight: 800;
    }

    .pr-description {
        margin: 0;
        color: #7a8497;
        font-size: 13px;
    }

    .pr-alert {
        display: none;
        margin-bottom: 16px;
        padding: 13px 15px;
        border: 1px solid transparent;
        border-radius: 10px;
        font-size: 14px;
    }

    .pr-alert-success {
        background: #e9f9f0;
        border-color: #bcebd0;
        color: #147949;
    }

    .pr-alert-danger {
        background: #fff0f1;
        border-color: #ffc5cb;
        color: #bd2e3d;
    }

    .pr-btn {
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
        text-decoration: none;
        white-space: nowrap;
        transition: .18s ease;
    }

    .pr-btn:hover {
        transform: translateY(-1px);
    }

    .pr-btn:disabled {
        opacity: .6;
        cursor: not-allowed;
        transform: none;
    }

    .pr-btn-primary {
        background: #1677ff;
        border-color: #1677ff;
        color: #fff;
    }

    .pr-btn-success {
        background: #168653;
        border-color: #168653;
        color: #fff;
    }

    .pr-btn-warning {
        background: #fff7e2;
        border-color: #edc15c;
        color: #976500;
    }

    .pr-btn-danger {
        background: #fff0f1;
        border-color: #f1a4ad;
        color: #bd3040;
    }

    .pr-btn-light {
        background: #f8fafc;
        border-color: #dfe5ed;
        color: #3c4659;
    }

    .pr-btn-outline {
        background: #fff;
        border-color: #1677ff;
        color: #1677ff;
    }

    .pr-btn-sm {
        min-height: 31px;
        padding: 5px 8px;
        font-size: 11px;
    }

    .pr-filters {
        padding: 18px;
        margin-bottom: 20px;
    }

    .pr-filter-grid {
        display: grid;
        grid-template-columns: minmax(210px, 2fr) repeat(4, minmax(130px, 1fr)) auto;
        gap: 12px;
        align-items: end;
    }

    .pr-field label {
        display: block;
        margin-bottom: 6px;
        color: #455066;
        font-size: 12px;
        font-weight: 700;
    }

    .pr-control {
        width: 100%;
        height: 41px;
        padding: 8px 11px;
        border: 1px solid #dce3ed;
        border-radius: 8px;
        background: #fff;
        color: #2c364a;
        outline: none;
    }

    textarea.pr-control {
        height: auto;
        min-height: 90px;
        resize: vertical;
    }

    .pr-control:focus {
        border-color: #1677ff;
        box-shadow: 0 0 0 3px rgba(22, 119, 255, .1);
    }

    .pr-control.is-invalid {
        border-color: #dc3545;
    }

    .pr-error {
        min-height: 18px;
        margin-top: 5px;
        color: #dc3545;
        font-size: 11px;
    }

    .pr-table-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 17px 19px;
        border-bottom: 1px solid #e7ebf2;
    }

    .pr-table-title {
        margin: 0;
        color: #263147;
        font-size: 17px;
        font-weight: 800;
    }

    .pr-count {
        padding: 4px 10px;
        border-radius: 20px;
        background: #eaf2ff;
        color: #1677ff;
        font-size: 12px;
        font-weight: 800;
    }

    .pr-table-responsive {
        width: 100%;
        overflow-x: auto;
    }

    .pr-table {
        width: 100%;
        min-width: 1250px;
        border-collapse: collapse;
    }

    .pr-table th {
        padding: 12px;
        border-bottom: 1px solid #dfe5ed;
        background: #f8fafc;
        color: #475269;
        font-size: 12px;
        font-weight: 800;
        text-align: right;
        white-space: nowrap;
    }

    .pr-table td {
        padding: 13px 12px;
        border-bottom: 1px solid #edf1f5;
        color: #344054;
        font-size: 12px;
        vertical-align: middle;
    }

    .pr-table tbody tr:hover {
        background: #fafcff;
    }

    .pr-code {
        direction: ltr;
        display: inline-block;
        color: #1677ff;
        font-family: monospace;
        font-weight: 800;
    }

    .pr-name {
        margin-bottom: 4px;
        color: #182236;
        font-weight: 800;
    }

    .pr-muted {
        color: #8892a5;
        font-size: 11px;
    }

    .pr-status {
        display: inline-flex;
        min-width: 75px;
        justify-content: center;
        padding: 5px 9px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 800;
    }

    .pr-status-draft,
    .pr-status-pending {
        background: #edf1f6;
        color: #576477;
    }

    .pr-status-calculating {
        background: #e9f1ff;
        color: #176bd4;
    }

    .pr-status-calculated {
        background: #e8f8f0;
        color: #13804b;
    }

    .pr-status-review {
        background: #fff4d7;
        color: #946200;
    }

    .pr-status-approved {
        background: #e7f0ff;
        color: #155fc1;
    }

    .pr-status-paid {
        background: #e2f8f2;
        color: #087b62;
    }

    .pr-status-cancelled,
    .pr-status-exception {
        background: #ffecef;
        color: #c33142;
    }

    .pr-actions {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }

    .pr-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
        padding: 15px 18px;
        border-top: 1px solid #e7ebf2;
    }

    .pr-page-buttons {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }

    .pr-page-btn {
        min-width: 34px;
        height: 34px;
        border: 1px solid #dfe5ed;
        border-radius: 7px;
        background: #fff;
        color: #344054;
        cursor: pointer;
    }

    .pr-page-btn.active {
        background: #1677ff;
        border-color: #1677ff;
        color: #fff;
    }

    .pr-page-btn:disabled {
        opacity: .5;
        cursor: not-allowed;
    }

    .pr-loading,
    .pr-empty {
        padding: 45px 20px !important;
        text-align: center;
        color: #718096;
    }

    .pr-modal {
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

    .pr-modal-panel {
        display: flex;
        flex-direction: column;
        width: 100%;
        max-width: 1000px;
        max-height: calc(100vh - 40px);
        margin: auto;
        overflow: hidden;
        background: #fff;
        border-radius: 17px;
        box-shadow: 0 25px 80px rgba(15, 23, 42, .3);
    }

    .pr-modal-sm {
        max-width: 560px;
    }

    .pr-modal-lg {
        max-width: 1250px;
    }

    .pr-modal-header {
        flex: 0 0 auto;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 15px;
        padding: 17px 20px;
        border-bottom: 1px solid #e7ebf2;
    }

    .pr-modal-title {
        margin: 0 0 5px;
        color: #182236;
        font-size: 18px;
        font-weight: 800;
    }

    .pr-modal-subtitle {
        margin: 0;
        color: #818b9d;
        font-size: 12px;
    }

    .pr-modal-close {
        width: 35px;
        height: 35px;
        border: 0;
        border-radius: 8px;
        background: #f1f4f8;
        color: #445067;
        font-size: 21px;
        cursor: pointer;
    }

    .pr-modal-body {
        flex: 1 1 auto;
        min-height: 0;
        padding: 20px;
        overflow-x: hidden;
        overflow-y: auto;
        overscroll-behavior: contain;
    }

    .pr-modal-footer {
        flex: 0 0 auto;
        display: flex;
        gap: 8px;
        padding: 14px 20px;
        border-top: 1px solid #e7ebf2;
    }

    body.pr-modal-open {
        overflow: hidden !important;
    }

    .pr-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .pr-full {
        grid-column: 1 / -1;
    }

    .pr-switch-box {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        padding: 13px;
        border: 1px solid #dfe5ed;
        border-radius: 10px;
        background: #f9fbfd;
    }

    .pr-employee-section {
        display: none;
        margin-top: 14px;
        padding: 14px;
        border: 1px solid #dfe5ed;
        border-radius: 12px;
    }

    .pr-employee-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }

    .pr-employee-list {
        max-height: 310px;
        overflow-y: auto;
        border: 1px solid #e3e8ef;
        border-radius: 9px;
    }

    .pr-employee-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 11px 13px;
        border-bottom: 1px solid #edf1f5;
    }

    .pr-employee-row:last-child {
        border-bottom: 0;
    }

    .pr-employee-row:hover {
        background: #f8fbff;
    }

    .pr-employee-info {
        display: flex;
        align-items: center;
        gap: 9px;
    }

    .pr-summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 11px;
        margin-bottom: 18px;
    }

    .pr-summary-box {
        min-height: 83px;
        padding: 13px;
        border: 1px solid #e5eaf1;
        border-radius: 11px;
        background: #f9fbfd;
    }

    .pr-summary-label {
        margin-bottom: 7px;
        color: #80899b;
        font-size: 11px;
    }

    .pr-summary-value {
        color: #243047;
        font-size: 14px;
        font-weight: 800;
        overflow-wrap: anywhere;
    }

    .pr-confirm-message {
        padding: 14px;
        border: 1px solid #efd99f;
        border-radius: 10px;
        background: #fff9e8;
        color: #705616;
        font-size: 13px;
        line-height: 1.8;
    }

    .pr-component-earning {
        color: #168653;
        font-weight: 800;
    }

    .pr-component-deduction {
        color: #c33142;
        font-weight: 800;
    }

    @media (max-width: 1200px) {
        .pr-filter-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .pr-summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767px) {
        .pr-filter-grid,
        .pr-form-grid,
        .pr-summary-grid {
            grid-template-columns: 1fr;
        }

        .pr-full {
            grid-column: auto;
        }

        .pr-modal {
            padding: 8px;
        }

        .pr-modal-panel {
            max-height: calc(100vh - 16px);
        }

        .pr-header .pr-btn {
            width: 100%;
        }
    }
</style>


<div id="payrollRunsPage">

    <div
        id="prSuccessAlert"
        class="pr-alert pr-alert-success"
    ></div>

    <div
        id="prErrorAlert"
        class="pr-alert pr-alert-danger"
    ></div>

    <div class="pr-header">

        <div>
            <h2 class="pr-title">
                تشغيل الرواتب
            </h2>

            <p class="pr-description">
                حساب الرواتب ومراجعتها واعتمادها وتسجيل عمليات الدفع.
            </p>
        </div>

        @if($canProcessPayroll)
            <button
                type="button"
                id="btnCreatePayrollRun"
                class="pr-btn pr-btn-primary"
            >
                <span>+</span>
                إنشاء تشغيل رواتب
            </button>
        @endif

    </div>


    <div class="pr-card pr-filters">

        <form id="payrollRunsFilterForm">

            <div class="pr-filter-grid">

                <div class="pr-field">
                    <label for="runSearch">
                        البحث
                    </label>

                    <input
                        type="search"
                        id="runSearch"
                        class="pr-control"
                        placeholder="رقم التشغيل أو اسم الفترة..."
                        autocomplete="off"
                    >
                </div>

                <div class="pr-field">
                    <label for="runPeriodFilter">
                        فترة الرواتب
                    </label>

                    <select
                        id="runPeriodFilter"
                        class="pr-control"
                    >
                        <option value="">جميع الفترات</option>
                    </select>
                </div>

                <div class="pr-field">
                    <label for="runTypeFilter">
                        النوع
                    </label>

                    <select
                        id="runTypeFilter"
                        class="pr-control"
                    >
                        <option value="">جميع الأنواع</option>
                        <option value="regular">تشغيل عادي</option>
                        <option value="off_cycle">خارج الدورة</option>
                        <option value="final_settlement">تسوية نهائية</option>
                    </select>
                </div>

                <div class="pr-field">
                    <label for="runStatusFilter">
                        الحالة
                    </label>

                    <select
                        id="runStatusFilter"
                        class="pr-control"
                    >
                        <option value="">جميع الحالات</option>
                        <option value="draft">مسودة</option>
                        <option value="calculating">جاري الحساب</option>
                        <option value="calculated">تم الحساب</option>
                        <option value="review">قيد المراجعة</option>
                        <option value="approved">معتمد</option>
                        <option value="paid">مدفوع</option>
                        <option value="cancelled">ملغى</option>
                    </select>
                </div>

                <div class="pr-field">
                    <label for="runPerPage">
                        عدد السجلات
                    </label>

                    <select
                        id="runPerPage"
                        class="pr-control"
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
                        class="pr-btn pr-btn-primary"
                    >
                        بحث
                    </button>

                    <button
                        type="button"
                        id="btnResetRunFilters"
                        class="pr-btn pr-btn-light"
                    >
                        إعادة
                    </button>

                </div>

            </div>

        </form>

    </div>


    <div class="pr-card">

        <div class="pr-table-header">

            <h3 class="pr-table-title">
                قائمة تشغيلات الرواتب
            </h3>

            <span
                id="payrollRunsCount"
                class="pr-count"
            >
                0 تشغيل
            </span>

        </div>

        <div class="pr-table-responsive">

            <table class="pr-table">

                <thead>
                    <tr>
                        <th>#</th>
                        <th>رقم التشغيل</th>
                        <th>الفترة</th>
                        <th>النوع</th>
                        <th>الحالة</th>
                        <th>الموظفون</th>
                        <th>الاستحقاقات</th>
                        <th>الخصومات</th>
                        <th>صافي الرواتب</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>

                <tbody id="payrollRunsTableBody">

                    <tr>
                        <td
                            colspan="10"
                            class="pr-loading"
                        >
                            جاري تحميل البيانات...
                        </td>
                    </tr>

                </tbody>

            </table>

        </div>

        <div class="pr-pagination">

            <div
                id="runsPaginationInfo"
                class="pr-muted"
            ></div>

            <div
                id="runsPaginationButtons"
                class="pr-page-buttons"
            ></div>

        </div>

    </div>

</div>


{{-- إنشاء تشغيل الرواتب --}}
<div
    id="createPayrollRunModal"
    class="pr-modal"
>

    <div class="pr-modal-panel">

        <div class="pr-modal-header">

            <div>
                <h3 class="pr-modal-title">
                    إنشاء تشغيل رواتب
                </h3>

                <p class="pr-modal-subtitle">
                    اختر الفترة ونوع التشغيل والموظفين المطلوب احتسابهم.
                </p>
            </div>

            <button
                type="button"
                class="pr-modal-close btn-close-pr-modal"
            >
                ×
            </button>

        </div>

        <form id="createPayrollRunForm">

            <div class="pr-modal-body">

                <div
                    id="createRunError"
                    class="pr-alert pr-alert-danger"
                ></div>

                <div class="pr-form-grid">

                    <div class="pr-field">

                        <label for="createRunPeriod">
                            فترة الرواتب
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            id="createRunPeriod"
                            name="payroll_period_id"
                            class="pr-control"
                        >
                            <option value="">
                                اختر الفترة
                            </option>
                        </select>

                        <div
                            class="pr-error"
                            data-error="payroll_period_id"
                        ></div>

                    </div>

                    <div class="pr-field">

                        <label for="createRunType">
                            نوع التشغيل
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            id="createRunType"
                            name="type"
                            class="pr-control"
                        >
                            <option value="regular">
                                تشغيل عادي
                            </option>

                            <option value="off_cycle">
                                تشغيل خارج الدورة
                            </option>

                            <option value="final_settlement">
                                تسوية نهائية
                            </option>
                        </select>

                        <div
                            class="pr-error"
                            data-error="type"
                        ></div>

                    </div>

                    <div class="pr-field pr-full">

                        <label for="createRunCurrency">
                            العملة
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            id="createRunCurrency"
                            name="currency_code"
                            class="pr-control"
                            maxlength="3"
                            dir="ltr"
                            placeholder="SAR"
                        >

                        <div
                            class="pr-error"
                            data-error="currency_code"
                        ></div>

                    </div>

                    <div class="pr-field pr-full">

                        <div class="pr-switch-box">

                            <div>
                                <div class="fw-bold mb-1">
                                    تحديد موظفين معينين
                                </div>

                                <div class="pr-muted">
                                    عند إيقاف الخيار سيتم احتساب جميع الموظفين المؤهلين.
                                </div>
                            </div>

                            <input
                                type="checkbox"
                                id="useSpecificEmployees"
                                class="form-check-input"
                            >

                        </div>

                        <div
                            class="pr-error"
                            data-error="employee_ids"
                        ></div>

                    </div>

                    <div
                        id="employeesSelectionSection"
                        class="pr-employee-section pr-full"
                    >

                        <div class="pr-employee-toolbar">

                            <div class="pr-field flex-grow-1">

                                <input
                                    type="search"
                                    id="employeeSelectionSearch"
                                    class="pr-control"
                                    placeholder="البحث عن موظف..."
                                    autocomplete="off"
                                >

                            </div>

                            <button
                                type="button"
                                id="btnSelectVisibleEmployees"
                                class="pr-btn pr-btn-outline"
                            >
                                تحديد الظاهرين
                            </button>

                            <button
                                type="button"
                                id="btnClearEmployees"
                                class="pr-btn pr-btn-light"
                            >
                                إلغاء التحديد
                            </button>

                        </div>

                        <div class="mb-2">

                            <span
                                id="selectedEmployeesCount"
                                class="pr-count"
                            >
                                0 موظف محدد
                            </span>

                        </div>

                        <div
                            id="employeesSelectionList"
                            class="pr-employee-list"
                        ></div>

                    </div>

                    <div class="pr-field pr-full">

                        <label for="createRunNotes">
                            ملاحظات
                        </label>

                        <textarea
                            id="createRunNotes"
                            name="notes"
                            class="pr-control"
                            maxlength="2000"
                            placeholder="ملاحظات اختيارية..."
                        ></textarea>

                        <div
                            class="pr-error"
                            data-error="notes"
                        ></div>

                    </div>

                </div>

            </div>

            <div class="pr-modal-footer">

                <button
                    type="submit"
                    id="btnSavePayrollRun"
                    class="pr-btn pr-btn-primary"
                >
                    إنشاء التشغيل
                </button>

                <button
                    type="button"
                    class="pr-btn pr-btn-light btn-close-pr-modal"
                >
                    إلغاء
                </button>

            </div>

        </form>

    </div>

</div>


{{-- تفاصيل التشغيل --}}
<div
    id="payrollRunDetailsModal"
    class="pr-modal"
>

    <div class="pr-modal-panel pr-modal-lg">

        <div class="pr-modal-header">

            <div>
                <h3 class="pr-modal-title">
                    تفاصيل تشغيل الرواتب
                </h3>

                <p
                    id="runDetailsSubtitle"
                    class="pr-modal-subtitle"
                ></p>
            </div>

            <button
                type="button"
                class="pr-modal-close btn-close-pr-modal"
            >
                ×
            </button>

        </div>

        <div class="pr-modal-body">

            <div
                id="runDetailsLoading"
                class="pr-loading"
            >
                جاري تحميل البيانات...
            </div>

            <div
                id="runDetailsContent"
                style="display: none;"
            >

                <div
                    id="runSummaryGrid"
                    class="pr-summary-grid"
                ></div>

                <div class="pr-card">

                    <div class="pr-table-header">

                        <h4 class="pr-table-title">
                            رواتب الموظفين
                        </h4>

                        <div class="d-flex gap-2 flex-wrap">

                            <input
                                type="search"
                                id="runItemsSearch"
                                class="pr-control"
                                style="width: 220px;"
                                placeholder="البحث عن موظف..."
                            >

                            <select
                                id="runItemsStatus"
                                class="pr-control"
                                style="width: 160px;"
                            >
                                <option value="">
                                    جميع الحالات
                                </option>

                                <option value="calculated">
                                    تم الحساب
                                </option>

                                <option value="exception">
                                    يحتاج مراجعة
                                </option>

                                <option value="approved">
                                    معتمد
                                </option>

                                <option value="paid">
                                    مدفوع
                                </option>
                            </select>

                        </div>

                    </div>

                    <div class="pr-table-responsive">

                        <table
                            class="pr-table"
                            style="min-width: 1050px;"
                        >

                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>الموظف</th>
                                    <th>الأساسي</th>
                                    <th>الاستحقاقات</th>
                                    <th>الخصومات</th>
                                    <th>الصافي</th>
                                    <th>الغياب</th>
                                    <th>الإضافي</th>
                                    <th>الحالة</th>
                                    <th>الإجراء</th>
                                </tr>
                            </thead>

                            <tbody id="runItemsTableBody"></tbody>

                        </table>

                    </div>

                    <div class="pr-pagination">

                        <div
                            id="itemsPaginationInfo"
                            class="pr-muted"
                        ></div>

                        <div
                            id="itemsPaginationButtons"
                            class="pr-page-buttons"
                        ></div>

                    </div>

                </div>

            </div>

        </div>

        <div class="pr-modal-footer">

            <button
                type="button"
                class="pr-btn pr-btn-light btn-close-pr-modal"
            >
                إغلاق
            </button>

        </div>

    </div>

</div>


{{-- تفاصيل راتب الموظف --}}
<div
    id="payrollItemDetailsModal"
    class="pr-modal"
>

    <div class="pr-modal-panel">

        <div class="pr-modal-header">

            <div>
                <h3 class="pr-modal-title">
                    تفاصيل راتب الموظف
                </h3>

                <p
                    id="itemDetailsSubtitle"
                    class="pr-modal-subtitle"
                ></p>
            </div>

            <button
                type="button"
                class="pr-modal-close btn-close-pr-modal"
            >
                ×
            </button>

        </div>

        <div
            id="payrollItemDetailsBody"
            class="pr-modal-body"
        >
            جاري تحميل البيانات...
        </div>

        <div class="pr-modal-footer">

            <button
                type="button"
                class="pr-btn pr-btn-light btn-close-pr-modal"
            >
                إغلاق
            </button>

        </div>

    </div>

</div>


{{-- تأكيد العمليات --}}
<div
    id="payrollRunConfirmModal"
    class="pr-modal"
>

    <div class="pr-modal-panel pr-modal-sm">

        <div class="pr-modal-header">

            <div>
                <h3
                    id="runConfirmTitle"
                    class="pr-modal-title"
                >
                    تأكيد العملية
                </h3>

                <p class="pr-modal-subtitle">
                    راجع العملية قبل التنفيذ.
                </p>
            </div>

            <button
                type="button"
                class="pr-modal-close btn-close-pr-modal"
            >
                ×
            </button>

        </div>

        <form id="payrollRunConfirmForm">

            <div class="pr-modal-body">

                <div
                    id="runConfirmError"
                    class="pr-alert pr-alert-danger"
                ></div>

                <div
                    id="runConfirmMessage"
                    class="pr-confirm-message"
                ></div>

                <div
                    id="runConfirmReasonSection"
                    class="pr-field mt-3"
                    style="display: none;"
                >

                    <label for="runConfirmReason">
                        السبب
                        <span class="text-danger">*</span>
                    </label>

                    <textarea
                        id="runConfirmReason"
                        class="pr-control"
                        maxlength="1000"
                        placeholder="اكتب سبب العملية..."
                    ></textarea>

                    <div
                        id="runConfirmReasonError"
                        class="pr-error"
                    ></div>

                </div>

            </div>

            <div class="pr-modal-footer">

                <button
                    type="submit"
                    id="btnExecuteRunAction"
                    class="pr-btn pr-btn-primary"
                >
                    تنفيذ
                </button>

                <button
                    type="button"
                    class="pr-btn pr-btn-light btn-close-pr-modal"
                >
                    إلغاء
                </button>

            </div>

        </form>

    </div>

</div>


@php
    $payrollRunRoutes = [
        'data' => route(
            'app.payroll.runs.data'
        ),

        'options' => route(
            'app.payroll.runs.options'
        ),

        'store' => route(
            'app.payroll.runs.store'
        ),

        'show' => route(
            'app.payroll.runs.show',
            [
                'payrollRun' => '__RUN__',
            ]
        ),

        'itemsData' => route(
            'app.payroll.runs.items.data',
            [
                'payrollRun' => '__RUN__',
            ]
        ),

        'itemShow' => route(
            'app.payroll.runs.items.show',
            [
                'payrollRun' => '__RUN__',
                'payrollItem' => '__ITEM__',
            ]
        ),

        'calculate' => route(
            'app.payroll.runs.calculate',
            [
                'payrollRun' => '__RUN__',
            ]
        ),

        'submitReview' => route(
            'app.payroll.runs.submit-review',
            [
                'payrollRun' => '__RUN__',
            ]
        ),

        'returnCalculation' => route(
            'app.payroll.runs.return-calculation',
            [
                'payrollRun' => '__RUN__',
            ]
        ),

        'approve' => route(
            'app.payroll.runs.approve',
            [
                'payrollRun' => '__RUN__',
            ]
        ),

        'markPaid' => route(
            'app.payroll.runs.mark-paid',
            [
                'payrollRun' => '__RUN__',
            ]
        ),

        'cancel' => route(
            'app.payroll.runs.cancel',
            [
                'payrollRun' => '__RUN__',
            ]
        ),

        'destroy' => route(
            'app.payroll.runs.destroy',
            [
                'payrollRun' => '__RUN__',
            ]
        ),
    ];
@endphp

<script>
(function bootPayrollRunsPage() {

    if (!window.jQuery) {
        window.setTimeout(
            bootPayrollRunsPage,
            50
        );

        return;
    }

    (function ($) {

        const $page =
            $('#payrollRunsPage');

        if ($page.data('initialized')) {
            return;
        }

        $page.data(
            'initialized',
            true
        );


        const routes = {{ Illuminate\Support\Js::from($payrollRunRoutes) }};

        let runPage = 1;
        let activeRunId = null;
        let runRequest = null;
        let searchTimer = null;
        let itemSearchTimer = null;

        let confirmAction = {
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


        function runUrl(
            template,
            runId
        ) {
            return template.replace(
                '__RUN__',
                encodeURIComponent(runId)
            );
        }


        function itemUrl(
            template,
            runId,
            itemId
        ) {
            return template
                .replace(
                    '__RUN__',
                    encodeURIComponent(runId)
                )
                .replace(
                    '__ITEM__',
                    encodeURIComponent(itemId)
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
                <span class="pr-status pr-status-${escapeHtml(status)}">
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
                    ? $('#prSuccessAlert')
                    : $('#prErrorAlert');

            $('#prSuccessAlert, #prErrorAlert')
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
                .addClass('pr-modal-open');

            $modal
                .stop(true, true)
                .show()
                .css(
                    'display',
                    'flex'
                );

            $modal.scrollTop(0);

            $modal
                .find('.pr-modal-body')
                .scrollTop(0);
        }


        function hideModal($modal) {

            $modal
                .stop(true, true)
                .hide();

            if (
                $('.pr-modal:visible')
                    .length === 0
            ) {
                $('body')
                    .removeClass(
                        'pr-modal-open'
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


        function renderRunActions(run) {

            const buttons = [];

            buttons.push(`
                <button
                    type="button"
                    class="pr-btn pr-btn-sm pr-btn-light btn-run-details"
                    data-id="${run.id}"
                >
                    عرض
                </button>
            `);

            if (run.can_calculate) {
                buttons.push(`
                    <button
                        type="button"
                        class="pr-btn pr-btn-sm pr-btn-primary btn-run-calculate"
                        data-id="${run.id}"
                        data-number="${escapeHtml(run.run_number)}"
                    >
                        حساب
                    </button>
                `);
            }

            if (run.can_submit_review) {
                buttons.push(`
                    <button
                        type="button"
                        class="pr-btn pr-btn-sm pr-btn-warning btn-run-review"
                        data-id="${run.id}"
                        data-number="${escapeHtml(run.run_number)}"
                    >
                        للمراجعة
                    </button>
                `);
            }

            if (run.can_return_to_calculation) {
                buttons.push(`
                    <button
                        type="button"
                        class="pr-btn pr-btn-sm pr-btn-outline btn-run-return"
                        data-id="${run.id}"
                        data-number="${escapeHtml(run.run_number)}"
                    >
                        إعادة
                    </button>
                `);
            }

            if (run.can_approve) {
                buttons.push(`
                    <button
                        type="button"
                        class="pr-btn pr-btn-sm pr-btn-success btn-run-approve"
                        data-id="${run.id}"
                        data-number="${escapeHtml(run.run_number)}"
                    >
                        اعتماد
                    </button>
                `);
            }

            if (run.can_mark_paid) {
                buttons.push(`
                    <button
                        type="button"
                        class="pr-btn pr-btn-sm pr-btn-success btn-run-paid"
                        data-id="${run.id}"
                        data-number="${escapeHtml(run.run_number)}"
                    >
                        دفع
                    </button>
                `);
            }

            if (run.can_cancel) {
                buttons.push(`
                    <button
                        type="button"
                        class="pr-btn pr-btn-sm pr-btn-warning btn-run-cancel"
                        data-id="${run.id}"
                        data-number="${escapeHtml(run.run_number)}"
                    >
                        إلغاء
                    </button>
                `);
            }

            if (run.can_delete) {
                buttons.push(`
                    <button
                        type="button"
                        class="pr-btn pr-btn-sm pr-btn-danger btn-run-delete"
                        data-id="${run.id}"
                        data-number="${escapeHtml(run.run_number)}"
                    >
                        حذف
                    </button>
                `);
            }

            return `
                <div class="pr-actions">
                    ${buttons.join('')}
                </div>
            `;
        }


        function renderRuns(
            rows,
            meta
        ) {
            const $body =
                $('#payrollRunsTableBody');

            if (!rows.length) {
                $body.html(`
                    <tr>
                        <td
                            colspan="10"
                            class="pr-empty"
                        >
                            لا توجد تشغيلات رواتب.
                        </td>
                    </tr>
                `);

                return;
            }

            const start =
                Number(meta.from || 1);

            const html =
                $.map(
                    rows,
                    function (run, index) {

                        const exceptions =
                            Number(
                                run.exception_items_count || 0
                            );

                        return `
                            <tr>

                                <td>
                                    ${start + index}
                                </td>

                                <td>
                                    <span class="pr-code">
                                        ${escapeHtml(run.run_number)}
                                    </span>
                                </td>

                                <td>
                                    <div class="pr-name">
                                        ${escapeHtml(run.period?.name || '-')}
                                    </div>

                                    <div class="pr-muted">
                                        ${escapeHtml(run.period?.code || '')}
                                    </div>
                                </td>

                                <td>
                                    ${escapeHtml(run.type_label)}
                                </td>

                                <td>
                                    ${statusBadge(
                                        run.status,
                                        run.status_label
                                    )}
                                </td>

                                <td>
                                    <div class="fw-bold">
                                        ${escapeHtml(run.employee_count)}
                                    </div>

                                    ${
                                        exceptions > 0
                                            ? `
                                                <div class="text-danger small">
                                                    ${exceptions} خطأ
                                                </div>
                                            `
                                            : ''
                                    }
                                </td>

                                <td>
                                    ${money(run.total_earnings)}
                                    ${escapeHtml(run.currency_code)}
                                </td>

                                <td>
                                    ${money(run.total_deductions)}
                                    ${escapeHtml(run.currency_code)}
                                </td>

                                <td>
                                    <strong>
                                        ${money(run.total_net_salary)}
                                        ${escapeHtml(run.currency_code)}
                                    </strong>
                                </td>

                                <td>
                                    ${renderRunActions(run)}
                                </td>

                            </tr>
                        `;
                    }
                ).join('');

            $body.html(html);
        }


        function renderPagination(
            meta,
            $container,
            buttonClass
        ) {
            const current =
                Number(meta.current_page || 1);

            const last =
                Number(meta.last_page || 1);

            const buttons = [];

            buttons.push(`
                <button
                    type="button"
                    class="pr-page-btn ${buttonClass}"
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
                        class="pr-page-btn ${buttonClass} ${page === current ? 'active' : ''}"
                        data-page="${page}"
                    >
                        ${page}
                    </button>
                `);
            }

            buttons.push(`
                <button
                    type="button"
                    class="pr-page-btn ${buttonClass}"
                    data-page="${current + 1}"
                    ${current >= last ? 'disabled' : ''}
                >
                    التالي
                </button>
            `);

            $container.html(
                buttons.join('')
            );
        }


        function loadRuns(page) {

            page =
                Number(page || 1);

            if (runRequest) {
                runRequest.abort();
            }

            $('#payrollRunsTableBody')
                .html(`
                    <tr>
                        <td
                            colspan="10"
                            class="pr-loading"
                        >
                            جاري تحميل البيانات...
                        </td>
                    </tr>
                `);

            runRequest =
                $.ajax({

                    url: routes.data,

                    type: 'GET',

                    data: {
                        page: page,

                        search:
                            $('#runSearch')
                                .val()
                                .trim(),

                        payroll_period_id:
                            $('#runPeriodFilter')
                                .val(),

                        type:
                            $('#runTypeFilter')
                                .val(),

                        status:
                            $('#runStatusFilter')
                                .val(),

                        per_page:
                            $('#runPerPage')
                                .val()
                    },

                    success: function (response) {

                        const rows =
                            extractRows(response);

                        const meta =
                            extractMeta(response);

                        runPage =
                            Number(
                                meta.current_page || 1
                            );

                        renderRuns(
                            rows,
                            meta
                        );

                        $('#payrollRunsCount')
                            .text(
                                Number(meta.total || 0) +
                                ' تشغيل'
                            );

                        $('#runsPaginationInfo')
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

                        renderPagination(
                            meta,
                            $('#runsPaginationButtons'),
                            'btn-runs-page'
                        );
                    },

                    error: function (
                        xhr,
                        status
                    ) {
                        if (status === 'abort') {
                            return;
                        }

                        $('#payrollRunsTableBody')
                            .html(`
                                <tr>
                                    <td
                                        colspan="10"
                                        class="pr-empty"
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
                        runRequest = null;
                    }

                });
        }


        function loadOptions() {

            $.ajax({

                url: routes.options,

                type: 'GET',

                success: function (response) {

                    const periods =
                        response.periods || [];

                    const employees =
                        response.employees || [];

                    const filterOptions = [
                        '<option value="">جميع الفترات</option>'
                    ];

                    const createOptions = [
                        '<option value="">اختر الفترة</option>'
                    ];

                    $.each(
                        periods,
                        function (_, period) {

                            const label =
                                escapeHtml(period.name) +
                                ' - ' +
                                escapeHtml(period.code);

                            filterOptions.push(`
                                <option value="${period.id}">
                                    ${label}
                                </option>
                            `);

                            if (period.can_create_run) {
                                createOptions.push(`
                                    <option value="${period.id}">
                                        ${label}
                                    </option>
                                `);
                            }
                        }
                    );

                    $('#runPeriodFilter')
                        .html(
                            filterOptions.join('')
                        );

                    $('#createRunPeriod')
                        .html(
                            createOptions.join('')
                        );

                    $('#createRunCurrency')
                        .val(
                            response.default_currency ||
                            'SAR'
                        );

                    renderEmployeeOptions(
                        employees
                    );
                },

                error: function (xhr) {
                    showMessage(
                        'error',
                        firstError(xhr)
                    );
                }

            });
        }


        function renderEmployeeOptions(
            employees
        ) {
            const html =
                $.map(
                    employees,
                    function (employee) {

                        const search =
                            (
                                employee.name +
                                ' ' +
                                employee.employee_number
                            ).toLowerCase();

                        return `
                            <label
                                class="pr-employee-row"
                                data-search="${escapeHtml(search)}"
                            >
                                <div class="pr-employee-info">

                                    <input
                                        type="checkbox"
                                        class="form-check-input employee-selector"
                                        value="${employee.id}"
                                    >

                                    <div>
                                        <div class="fw-bold">
                                            ${escapeHtml(employee.name)}
                                        </div>

                                        <div class="pr-muted">
                                            ${escapeHtml(employee.employee_number)}
                                        </div>
                                    </div>

                                </div>
                            </label>
                        `;
                    }
                ).join('');

            $('#employeesSelectionList')
                .html(
                    html ||
                    '<div class="pr-empty">لا يوجد موظفون مؤهلون.</div>'
                );
        }


        function updateSelectedEmployeesCount() {

            const count =
                $('.employee-selector:checked')
                    .length;

            $('#selectedEmployeesCount')
                .text(
                    count +
                    ' موظف محدد'
                );
        }


        function clearCreateErrors() {

            $('#createPayrollRunForm')
                .find('.pr-control')
                .removeClass('is-invalid');

            $('#createPayrollRunForm')
                .find('[data-error]')
                .text('');

            $('#createRunError')
                .hide()
                .text('');
        }


        function showCreateErrors(xhr) {

            clearCreateErrors();

            const response =
                xhr.responseJSON || {};

            if (!response.errors) {
                $('#createRunError')
                    .text(
                        response.message ||
                        'تعذر إنشاء التشغيل.'
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


        function openCreateRunModal() {

            $('#createPayrollRunForm')[0]
                .reset();

            clearCreateErrors();

            $('#useSpecificEmployees')
                .prop('checked', false);

            $('#employeesSelectionSection')
                .hide();

            $('.employee-selector')
                .prop('checked', false);

            $('#employeeSelectionSearch')
                .val('');

            $('.pr-employee-row')
                .show();

            updateSelectedEmployeesCount();

            showModal(
                $('#createPayrollRunModal')
            );
        }


        function savePayrollRun() {

            clearCreateErrors();

            const specific =
                $('#useSpecificEmployees')
                    .prop('checked');

            const employeeIds =
                $('.employee-selector:checked')
                    .map(function () {
                        return $(this).val();
                    })
                    .get();

            if (
                specific &&
                employeeIds.length === 0
            ) {
                $('[data-error="employee_ids"]')
                    .text(
                        'اختر موظفًا واحدًا على الأقل.'
                    );

                return;
            }

            const data = {
                payroll_period_id:
                    $('#createRunPeriod')
                        .val(),

                type:
                    $('#createRunType')
                        .val(),

                currency_code:
                    $('#createRunCurrency')
                        .val()
                        .trim()
                        .toUpperCase(),

                notes:
                    $('#createRunNotes')
                        .val()
                        .trim()
            };

            if (specific) {
                data.employee_ids =
                    employeeIds;
            }

            const $button =
                $('#btnSavePayrollRun');

            $button
                .prop('disabled', true)
                .text('جاري الإنشاء...');

            $.ajax({

                url: routes.store,

                type: 'POST',

                data: data,

                success: function (response) {

                    hideModal(
                        $('#createPayrollRunModal')
                    );

                    showMessage(
                        'success',
                        response.message ||
                        'تم إنشاء التشغيل بنجاح.'
                    );

                    loadOptions();
                    loadRuns(1);
                },

                error: function (xhr) {
                    showCreateErrors(xhr);
                },

                complete: function () {

                    $button
                        .prop('disabled', false)
                        .text('إنشاء التشغيل');
                }

            });
        }


        function renderRunSummary(run) {

            $('#runDetailsSubtitle')
                .text(
                    run.run_number +
                    ' - ' +
                    (
                        run.period?.name ||
                        ''
                    )
                );

            $('#runSummaryGrid')
                .html(`

                    <div class="pr-summary-box">
                        <div class="pr-summary-label">
                            رقم التشغيل
                        </div>

                        <div class="pr-summary-value pr-code">
                            ${escapeHtml(run.run_number)}
                        </div>
                    </div>

                    <div class="pr-summary-box">
                        <div class="pr-summary-label">
                            الحالة
                        </div>

                        <div class="pr-summary-value">
                            ${statusBadge(
                                run.status,
                                run.status_label
                            )}
                        </div>
                    </div>

                    <div class="pr-summary-box">
                        <div class="pr-summary-label">
                            الموظفون
                        </div>

                        <div class="pr-summary-value">
                            ${escapeHtml(run.employee_count)}
                        </div>
                    </div>

                    <div class="pr-summary-box">
                        <div class="pr-summary-label">
                            أخطاء الحساب
                        </div>

                        <div class="pr-summary-value">
                            ${escapeHtml(run.exception_items_count)}
                        </div>
                    </div>

                    <div class="pr-summary-box">
                        <div class="pr-summary-label">
                            إجمالي الأساسي
                        </div>

                        <div class="pr-summary-value">
                            ${money(run.total_basic_salary)}
                            ${escapeHtml(run.currency_code)}
                        </div>
                    </div>

                    <div class="pr-summary-box">
                        <div class="pr-summary-label">
                            إجمالي الاستحقاقات
                        </div>

                        <div class="pr-summary-value text-success">
                            ${money(run.total_earnings)}
                            ${escapeHtml(run.currency_code)}
                        </div>
                    </div>

                    <div class="pr-summary-box">
                        <div class="pr-summary-label">
                            إجمالي الخصومات
                        </div>

                        <div class="pr-summary-value text-danger">
                            ${money(run.total_deductions)}
                            ${escapeHtml(run.currency_code)}
                        </div>
                    </div>

                    <div class="pr-summary-box">
                        <div class="pr-summary-label">
                            صافي الرواتب
                        </div>

                        <div class="pr-summary-value">
                            ${money(run.total_net_salary)}
                            ${escapeHtml(run.currency_code)}
                        </div>
                    </div>

                `);
        }


        function showRunDetails(runId) {

            activeRunId = runId;

            $('#runDetailsLoading')
                .show();

            $('#runDetailsContent')
                .hide();

            showModal(
                $('#payrollRunDetailsModal')
            );

            $.ajax({

                url:
                    runUrl(
                        routes.show,
                        runId
                    ),

                type: 'GET',

                success: function (response) {

                    const run =
                        response.payroll_run;

                    renderRunSummary(run);

                    $('#runDetailsLoading')
                        .hide();

                    $('#runDetailsContent')
                        .show();

                    loadRunItems(1);
                },

                error: function (xhr) {

                    hideModal(
                        $('#payrollRunDetailsModal')
                    );

                    showMessage(
                        'error',
                        firstError(xhr)
                    );
                }

            });
        }


        function renderRunItems(
            rows,
            meta
        ) {
            const $body =
                $('#runItemsTableBody');

            if (!rows.length) {
                $body.html(`
                    <tr>
                        <td
                            colspan="10"
                            class="pr-empty"
                        >
                            لا توجد رواتب موظفين.
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

                        return `
                            <tr>

                                <td>
                                    ${start + index}
                                </td>

                                <td>
                                    <div class="pr-name">
                                        ${escapeHtml(item.employee?.name || '-')}
                                    </div>

                                    <div class="pr-muted">
                                        ${escapeHtml(item.employee?.employee_number || '')}
                                    </div>
                                </td>

                                <td>
                                    ${money(item.basic_salary)}
                                    ${escapeHtml(item.currency_code)}
                                </td>

                                <td>
                                    ${money(item.total_earnings)}
                                </td>

                                <td>
                                    ${money(item.total_deductions)}
                                </td>

                                <td>
                                    <strong>
                                        ${money(item.net_salary)}
                                    </strong>
                                </td>

                                <td>
                                    ${escapeHtml(item.absent_days)}
                                </td>

                                <td>
                                    ${escapeHtml(item.overtime_minutes)}
                                    دقيقة
                                </td>

                                <td>
                                    ${statusBadge(
                                        item.status,
                                        item.status_label
                                    )}
                                </td>

                                <td>
                                    <button
                                        type="button"
                                        class="pr-btn pr-btn-sm pr-btn-light btn-item-details"
                                        data-run-id="${activeRunId}"
                                        data-item-id="${item.id}"
                                    >
                                        التفاصيل
                                    </button>
                                </td>

                            </tr>
                        `;
                    }
                ).join('')
            );
        }


        function loadRunItems(page) {

            if (!activeRunId) {
                return;
            }

            $('#runItemsTableBody')
                .html(`
                    <tr>
                        <td
                            colspan="10"
                            class="pr-loading"
                        >
                            جاري تحميل الرواتب...
                        </td>
                    </tr>
                `);

            $.ajax({

                url:
                    runUrl(
                        routes.itemsData,
                        activeRunId
                    ),

                type: 'GET',

                data: {
                    page: page || 1,

                    search:
                        $('#runItemsSearch')
                            .val()
                            .trim(),

                    status:
                        $('#runItemsStatus')
                            .val(),

                    per_page: 15
                },

                success: function (response) {

                    const rows =
                        extractRows(response);

                    const meta =
                        extractMeta(response);

                    renderRunItems(
                        rows,
                        meta
                    );

                    $('#itemsPaginationInfo')
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

                    renderPagination(
                        meta,
                        $('#itemsPaginationButtons'),
                        'btn-items-page'
                    );
                },

                error: function (xhr) {

                    $('#runItemsTableBody')
                        .html(`
                            <tr>
                                <td
                                    colspan="10"
                                    class="pr-empty"
                                >
                                    تعذر تحميل رواتب الموظفين.
                                </td>
                            </tr>
                        `);

                    showMessage(
                        'error',
                        firstError(xhr)
                    );
                }

            });
        }


        function showItemDetails(
            runId,
            itemId
        ) {
            $('#payrollItemDetailsBody')
                .html(
                    '<div class="pr-loading">جاري تحميل البيانات...</div>'
                );

            showModal(
                $('#payrollItemDetailsModal')
            );

            $.ajax({

                url:
                    itemUrl(
                        routes.itemShow,
                        runId,
                        itemId
                    ),

                type: 'GET',

                success: function (response) {

                    const item =
                        response.payroll_item;

                    $('#itemDetailsSubtitle')
                        .text(
                            (
                                item.employee?.name ||
                                ''
                            ) +
                            ' - ' +
                            (
                                item.employee?.employee_number ||
                                ''
                            )
                        );

                    const errors =
                        Array.isArray(item.errors)
                            ? item.errors
                            : [];

                    const components =
                        item.components || [];

                    const componentRows =
                        $.map(
                            components,
                            function (component) {

                                const amountClass =
                                    component.type === 'earning'
                                        ? 'pr-component-earning'
                                        : 'pr-component-deduction';

                                return `
                                    <tr>
                                        <td>
                                            <span class="pr-code">
                                                ${escapeHtml(component.component_code)}
                                            </span>
                                        </td>

                                        <td>
                                            ${escapeHtml(component.component_name)}
                                        </td>

                                        <td>
                                            ${escapeHtml(component.type_label)}
                                        </td>

                                        <td>
                                            ${escapeHtml(component.source_label)}
                                        </td>

                                        <td>
                                            ${component.percentage !== null ? escapeHtml(component.percentage) + '%' : '-'}
                                        </td>

                                        <td class="${amountClass}">
                                            ${money(component.amount)}
                                            ${escapeHtml(item.currency_code)}
                                        </td>
                                    </tr>
                                `;
                            }
                        ).join('');

                    $('#payrollItemDetailsBody')
                        .html(`

                            ${
                                errors.length
                                    ? `
                                        <div class="pr-alert pr-alert-danger" style="display:block;">
                                            ${$.map(
                                                errors,
                                                function (error) {
                                                    return '<div>• ' + escapeHtml(error) + '</div>';
                                                }
                                            ).join('')}
                                        </div>
                                    `
                                    : ''
                            }

                            <div class="pr-summary-grid">

                                <div class="pr-summary-box">
                                    <div class="pr-summary-label">
                                        الراتب الأساسي
                                    </div>

                                    <div class="pr-summary-value">
                                        ${money(item.basic_salary)}
                                        ${escapeHtml(item.currency_code)}
                                    </div>
                                </div>

                                <div class="pr-summary-box">
                                    <div class="pr-summary-label">
                                        الاستحقاقات
                                    </div>

                                    <div class="pr-summary-value text-success">
                                        ${money(item.total_earnings)}
                                    </div>
                                </div>

                                <div class="pr-summary-box">
                                    <div class="pr-summary-label">
                                        الخصومات
                                    </div>

                                    <div class="pr-summary-value text-danger">
                                        ${money(item.total_deductions)}
                                    </div>
                                </div>

                                <div class="pr-summary-box">
                                    <div class="pr-summary-label">
                                        صافي الراتب
                                    </div>

                                    <div class="pr-summary-value">
                                        ${money(item.net_salary)}
                                    </div>
                                </div>

                                <div class="pr-summary-box">
                                    <div class="pr-summary-label">
                                        أيام العمل المجدولة
                                    </div>

                                    <div class="pr-summary-value">
                                        ${escapeHtml(item.scheduled_work_days)}
                                    </div>
                                </div>

                                <div class="pr-summary-box">
                                    <div class="pr-summary-label">
                                        أيام العمل الفعلية
                                    </div>

                                    <div class="pr-summary-value">
                                        ${escapeHtml(item.actual_work_days)}
                                    </div>
                                </div>

                                <div class="pr-summary-box">
                                    <div class="pr-summary-label">
                                        أيام الغياب
                                    </div>

                                    <div class="pr-summary-value">
                                        ${escapeHtml(item.absent_days)}
                                    </div>
                                </div>

                                <div class="pr-summary-box">
                                    <div class="pr-summary-label">
                                        دقائق العمل الإضافي
                                    </div>

                                    <div class="pr-summary-value">
                                        ${escapeHtml(item.overtime_minutes)}
                                    </div>
                                </div>

                            </div>

                            <h4 class="pr-table-title mb-3">
                                مكونات الراتب
                            </h4>

                            <div class="pr-table-responsive">

                                <table
                                    class="pr-table"
                                    style="min-width:750px;"
                                >
                                    <thead>
                                        <tr>
                                            <th>الكود</th>
                                            <th>المكون</th>
                                            <th>النوع</th>
                                            <th>المصدر</th>
                                            <th>النسبة</th>
                                            <th>المبلغ</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        ${
                                            componentRows ||
                                            `
                                                <tr>
                                                    <td
                                                        colspan="6"
                                                        class="pr-empty"
                                                    >
                                                        لا توجد مكونات.
                                                    </td>
                                                </tr>
                                            `
                                        }
                                    </tbody>
                                </table>

                            </div>

                        `);
                },

                error: function (xhr) {

                    hideModal(
                        $('#payrollItemDetailsModal')
                    );

                    showMessage(
                        'error',
                        firstError(xhr)
                    );
                }

            });
        }


        function openConfirm(options) {

            confirmAction =
                $.extend(
                    {
                        url: null,
                        method: 'POST',
                        requiresReason: false,
                        successMessage: null,
                        buttonClass: 'pr-btn-primary'
                    },
                    options
                );

            $('#runConfirmTitle')
                .text(
                    confirmAction.title ||
                    'تأكيد العملية'
                );

            $('#runConfirmMessage')
                .text(
                    confirmAction.message
                );

            $('#runConfirmReason')
                .val('')
                .removeClass('is-invalid');

            $('#runConfirmReasonError')
                .text('');

            $('#runConfirmError')
                .hide()
                .text('');

            $('#runConfirmReasonSection')
                .toggle(
                    Boolean(
                        confirmAction.requiresReason
                    )
                );

            $('#btnExecuteRunAction')
                .removeClass(
                    'pr-btn-primary ' +
                    'pr-btn-success ' +
                    'pr-btn-warning ' +
                    'pr-btn-danger'
                )
                .addClass(
                    confirmAction.buttonClass
                )
                .text(
                    confirmAction.buttonText ||
                    'تنفيذ'
                );

            showModal(
                $('#payrollRunConfirmModal')
            );
        }


        function executeRunAction() {

            const reason =
                $('#runConfirmReason')
                    .val()
                    .trim();

            $('#runConfirmReason')
                .removeClass('is-invalid');

            $('#runConfirmReasonError')
                .text('');

            if (
                confirmAction.requiresReason &&
                !reason
            ) {
                $('#runConfirmReason')
                    .addClass('is-invalid');

                $('#runConfirmReasonError')
                    .text(
                        'يرجى كتابة سبب العملية.'
                    );

                return;
            }

            const $button =
                $('#btnExecuteRunAction');

            const oldText =
                $button.text();

            $button
                .prop('disabled', true)
                .text('جاري التنفيذ...');

            $.ajax({

                url: confirmAction.url,

                type: confirmAction.method,

                data: {
                    reason: reason
                },

                success: function (response) {

                    hideModal(
                        $('#payrollRunConfirmModal')
                    );

                    showMessage(
                        'success',
                        response.message ||
                        confirmAction.successMessage ||
                        'تم تنفيذ العملية.'
                    );

                    loadRuns(runPage);
                    loadOptions();

                    if (
                        activeRunId &&
                        $('#payrollRunDetailsModal')
                            .is(':visible')
                    ) {
                        showRunDetails(
                            activeRunId
                        );
                    }
                },

                error: function (xhr) {

                    $('#runConfirmError')
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


        $('#payrollRunsFilterForm')
            .on('submit', function (event) {

                event.preventDefault();
                loadRuns(1);
            });


        $('#runSearch')
            .on('input', function () {

                window.clearTimeout(
                    searchTimer
                );

                searchTimer =
                    window.setTimeout(
                        function () {
                            loadRuns(1);
                        },
                        400
                    );
            });


        $(
            '#runPeriodFilter, ' +
            '#runTypeFilter, ' +
            '#runStatusFilter, ' +
            '#runPerPage'
        ).on('change', function () {
            loadRuns(1);
        });


        $('#btnResetRunFilters')
            .on('click', function () {

                $('#payrollRunsFilterForm')[0]
                    .reset();

                loadRuns(1);
            });


        $('#btnCreatePayrollRun')
            .on('click', function () {
                openCreateRunModal();
            });


        $('#useSpecificEmployees')
            .on('change', function () {

                $('#employeesSelectionSection')
                    .toggle(
                        $(this).prop('checked')
                    );
            });


        $('#employeeSelectionSearch')
            .on('input', function () {

                const search =
                    $(this)
                        .val()
                        .trim()
                        .toLowerCase();

                $('.pr-employee-row')
                    .each(function () {

                        const text =
                            String(
                                $(this).data('search') ||
                                ''
                            ).toLowerCase();

                        $(this).toggle(
                            text.includes(search)
                        );
                    });
            });


        $('#btnSelectVisibleEmployees')
            .on('click', function () {

                $('.pr-employee-row:visible')
                    .find('.employee-selector')
                    .prop('checked', true);

                updateSelectedEmployeesCount();
            });


        $('#btnClearEmployees')
            .on('click', function () {

                $('.employee-selector')
                    .prop('checked', false);

                updateSelectedEmployeesCount();
            });


        $(document)
            .on(
                'change',
                '.employee-selector',
                updateSelectedEmployeesCount
            );


        $('#createPayrollRunForm')
            .on('submit', function (event) {

                event.preventDefault();
                savePayrollRun();
            });


        $(document)
            .on(
                'click',
                '.btn-runs-page',
                function () {

                    if (!$(this).prop('disabled')) {
                        loadRuns(
                            $(this).data('page')
                        );
                    }
                }
            );


        $(document)
            .on(
                'click',
                '.btn-items-page',
                function () {

                    if (!$(this).prop('disabled')) {
                        loadRunItems(
                            $(this).data('page')
                        );
                    }
                }
            );


        $(document)
            .on(
                'click',
                '.btn-run-details',
                function () {
                    showRunDetails(
                        $(this).data('id')
                    );
                }
            );


        $(document)
            .on(
                'click',
                '.btn-item-details',
                function () {
                    showItemDetails(
                        $(this).data('run-id'),
                        $(this).data('item-id')
                    );
                }
            );


        $('#runItemsSearch')
            .on('input', function () {

                window.clearTimeout(
                    itemSearchTimer
                );

                itemSearchTimer =
                    window.setTimeout(
                        function () {
                            loadRunItems(1);
                        },
                        400
                    );
            });


        $('#runItemsStatus')
            .on('change', function () {
                loadRunItems(1);
            });


        $(document)
            .on(
                'click',
                '.btn-run-calculate',
                function () {

                    const id =
                        $(this).data('id');

                    const number =
                        $(this).data('number');

                    openConfirm({
                        title: 'حساب تشغيل الرواتب',

                        message:
                            'سيتم حساب جميع رواتب التشغيل ' +
                            number +
                            '. وسيتم استبدال أي حساب سابق.',

                        url:
                            runUrl(
                                routes.calculate,
                                id
                            ),

                        buttonText:
                            'بدء الحساب',

                        buttonClass:
                            'pr-btn-primary'
                    });
                }
            );


        $(document)
            .on(
                'click',
                '.btn-run-review',
                function () {

                    openConfirm({
                        title: 'إرسال للمراجعة',

                        message:
                            'سيتم إرسال التشغيل ' +
                            $(this).data('number') +
                            ' إلى مسؤول اعتماد الرواتب.',

                        url:
                            runUrl(
                                routes.submitReview,
                                $(this).data('id')
                            ),

                        buttonText:
                            'إرسال للمراجعة',

                        buttonClass:
                            'pr-btn-warning'
                    });
                }
            );


        $(document)
            .on(
                'click',
                '.btn-run-return',
                function () {

                    openConfirm({
                        title: 'إعادة التشغيل للحساب',

                        message:
                            'سيتم إعادة التشغيل إلى مرحلة الحساب والتعديل.',

                        url:
                            runUrl(
                                routes.returnCalculation,
                                $(this).data('id')
                            ),

                        requiresReason: true,

                        buttonText:
                            'إعادة للحساب',

                        buttonClass:
                            'pr-btn-warning'
                    });
                }
            );


        $(document)
            .on(
                'click',
                '.btn-run-approve',
                function () {

                    openConfirm({
                        title: 'اعتماد الرواتب',

                        message:
                            'سيتم اعتماد التشغيل ' +
                            $(this).data('number') +
                            ' وقفل فترة الرواتب.',

                        url:
                            runUrl(
                                routes.approve,
                                $(this).data('id')
                            ),

                        buttonText:
                            'اعتماد الرواتب',

                        buttonClass:
                            'pr-btn-success'
                    });
                }
            );


        $(document)
            .on(
                'click',
                '.btn-run-paid',
                function () {

                    openConfirm({
                        title: 'تسجيل دفع الرواتب',

                        message:
                            'سيتم تسجيل جميع رواتب التشغيل كمدفوعة. تأكد من إتمام عملية التحويل البنكي أولًا.',

                        url:
                            runUrl(
                                routes.markPaid,
                                $(this).data('id')
                            ),

                        buttonText:
                            'تسجيل الدفع',

                        buttonClass:
                            'pr-btn-success'
                    });
                }
            );


        $(document)
            .on(
                'click',
                '.btn-run-cancel',
                function () {

                    openConfirm({
                        title: 'إلغاء تشغيل الرواتب',

                        message:
                            'سيتم إلغاء التشغيل وتحرير التسويات المرتبطة به.',

                        url:
                            runUrl(
                                routes.cancel,
                                $(this).data('id')
                            ),

                        requiresReason: true,

                        buttonText:
                            'إلغاء التشغيل',

                        buttonClass:
                            'pr-btn-danger'
                    });
                }
            );


        $(document)
            .on(
                'click',
                '.btn-run-delete',
                function () {

                    openConfirm({
                        title: 'حذف تشغيل الرواتب',

                        message:
                            'سيتم حذف مسودة التشغيل نهائيًا.',

                        url:
                            runUrl(
                                routes.destroy,
                                $(this).data('id')
                            ),

                        method: 'DELETE',

                        buttonText:
                            'حذف التشغيل',

                        buttonClass:
                            'pr-btn-danger'
                    });
                }
            );


        $('#payrollRunConfirmForm')
            .on('submit', function (event) {

                event.preventDefault();
                executeRunAction();
            });


        $('.btn-close-pr-modal')
            .on('click', function () {

                hideModal(
                    $(this)
                        .closest('.pr-modal')
                );
            });


        $('.pr-modal')
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
                        $('.pr-modal:visible')
                            .last();

                    if ($modal.length) {
                        hideModal($modal);
                    }
                }
            });


        loadOptions();
        loadRuns(1);

    })(window.jQuery);

})();
</script>

@endsection