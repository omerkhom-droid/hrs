@extends('layouts.tenant')

@section('title', 'أرصدة الإجازات')
@section('page-title', 'أرصدة الإجازات')

@section('content')

<style>
    .leave-balances-page {
        --lb-primary: #146ef5;
        --lb-border: #e4e9f1;
        --lb-muted: #667085;
        --lb-soft: #f7f9fc;
        --lb-danger: #dc3545;
        --lb-success: #198754;
    }

    .leave-balances-page .page-card {
        background: #fff;
        border: 1px solid var(--lb-border);
        border-radius: 16px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .04);
    }

    .leave-balances-page .filter-label {
        display: block;
        margin-bottom: 7px;
        color: #344054;
        font-size: 13px;
        font-weight: 600;
    }

    .leave-balances-page .form-control,
    .leave-balances-page .form-select {
        min-height: 43px;
        border-color: #d7dee8;
        border-radius: 10px;
    }

    .leave-balances-page .employee-name {
        color: #101828;
        font-weight: 700;
    }

    .leave-balances-page .employee-number,
    .leave-balances-page .employee-meta {
        color: var(--lb-muted);
        font-size: 12px;
    }

    .leave-balances-page .balance-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        min-width: 320px;
    }

    .leave-balances-page .balance-item {
        min-width: 165px;
        padding: 10px 12px;
        background: var(--lb-soft);
        border: 1px solid var(--lb-border);
        border-right: 4px solid var(--lb-primary);
        border-radius: 10px;
    }

    .leave-balances-page .balance-name {
        margin-bottom: 5px;
        color: #475467;
        font-size: 12px;
        font-weight: 600;
    }

    .leave-balances-page .balance-value {
        color: #101828;
        font-size: 17px;
        font-weight: 800;
    }

    .leave-balances-page .balance-meta {
        margin-top: 4px;
        color: var(--lb-muted);
        font-size: 11px;
    }

    .leave-balances-page .empty-state {
        padding: 45px 15px;
        color: var(--lb-muted);
        text-align: center;
    }

    .leave-balances-page .table > :not(caption) > * > * {
        padding: 14px 12px;
        vertical-align: middle;
    }

    .leave-balances-page .pagination-area {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        flex-wrap: wrap;
        padding: 15px 18px;
        border-top: 1px solid var(--lb-border);
    }

    .leave-balances-page .pagination-buttons {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }

    .leave-balances-page .page-button {
        min-width: 36px;
        height: 36px;
        border: 1px solid var(--lb-border);
        border-radius: 9px;
        background: #fff;
        color: #344054;
    }

    .leave-balances-page .page-button.active {
        border-color: var(--lb-primary);
        background: var(--lb-primary);
        color: #fff;
    }

    .leave-balances-page .page-button:disabled {
        cursor: not-allowed;
        opacity: .45;
    }

    .current-balance-box {
        padding: 16px;
        background: #f8faff;
        border: 1px dashed #b8cdf4;
        border-radius: 12px;
    }

    .current-balance-value {
        color: var(--lb-primary);
        font-size: 24px;
        font-weight: 800;
    }

    .bulk-employees-box {
        max-height: 310px;
        overflow-y: auto;
        border: 1px solid var(--lb-border);
        border-radius: 12px;
        background: #fff;
    }

    .bulk-employee-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 11px 13px;
        border-bottom: 1px solid #eef1f5;
    }

    .bulk-employee-item:last-child {
        border-bottom: 0;
    }

    .bulk-employee-item:hover {
        background: #f8faff;
    }

    .bulk-employee-item .form-check-input {
        flex: 0 0 auto;
        width: 18px;
        height: 18px;
        margin: 0;
    }

    .selected-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 30px;
        height: 26px;
        padding: 0 9px;
        border-radius: 20px;
        background: #eaf2ff;
        color: var(--lb-primary);
        font-size: 12px;
        font-weight: 700;
    }

    /*
    |--------------------------------------------------------------------------
    | jQuery Modal
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
        overflow-x: hidden;
        overflow-y: auto;
        padding: 24px;
        background: rgba(15, 23, 42, .62);
    }

    .ry-modal-panel {
        display: flex;
        flex-direction: column;
        width: min(850px, 100%);
        max-height: calc(100vh - 48px);
        margin: auto;
        overflow: hidden;
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 25px 65px rgba(15, 23, 42, .25);
    }

    .ry-modal-panel.modal-lg {
        width: min(1100px, 100%);
    }

    .ry-modal-header,
    .ry-modal-footer {
        flex: 0 0 auto;
        padding: 18px 22px;
        background: #fff;
    }

    .ry-modal-header {
        border-bottom: 1px solid var(--lb-border);
    }

    .ry-modal-footer {
        border-top: 1px solid var(--lb-border);
    }

    .ry-modal-body {
        flex: 1 1 auto;
        min-height: 0;
        padding: 22px;
        overflow-y: auto;
        overscroll-behavior: contain;
    }

    .ry-modal-close {
        width: 38px;
        height: 38px;
        border: 0;
        border-radius: 10px;
        background: #f1f4f8;
        color: #344054;
        font-size: 24px;
        line-height: 1;
    }

    @media (max-width: 768px) {
        .ry-modal {
            align-items: flex-end;
            padding: 0;
        }

        .ry-modal-panel,
        .ry-modal-panel.modal-lg {
            width: 100%;
            max-height: 94vh;
            margin-top: 6vh;
            border-radius: 18px 18px 0 0;
        }

        .leave-balances-page .balance-list {
            min-width: 250px;
        }
    }
</style>


<div class="leave-balances-page">

    <div
        id="pageAlert"
        class="alert d-none mb-3"
        role="alert"
    ></div>


    {{-- Header --}}

    <div class="page-card p-4 mb-4">

        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">

            <div>
                <h5 class="mb-1">
                    إدارة أرصدة الإجازات
                </h5>

                <div class="text-muted small">
                    عرض الأرصدة وتنفيذ التسويات الفردية والجماعية.
                </div>
            </div>


            @can('leave.manage')

                <div class="d-flex gap-2 flex-wrap">

                    <button
                        type="button"
                        id="openBulkAdjustmentModal"
                        class="btn btn-outline-primary"
                    >
                        + تسوية جماعية
                    </button>

                    <button
                        type="button"
                        id="openAdjustmentModal"
                        class="btn btn-primary"
                    >
                        + تسوية رصيد
                    </button>

                    <button
                        type="button"
                        id="openCarryForwardModal"
                        class="btn btn-outline-warning"
                    >
                        إقفال وترحيل الأرصدة
                    </button>

                </div>

            @endcan

        </div>

    </div>


    {{-- Filters --}}

    <div class="page-card p-4 mb-4">

        <form id="filtersForm">

            <div class="row g-3 align-items-end">

                <div class="col-lg-4 col-md-6">
                    <label class="filter-label">
                        البحث
                    </label>

                    <input
                        type="search"
                        id="filterSearch"
                        class="form-control"
                        placeholder="اسم الموظف أو الرقم الوظيفي"
                    >
                </div>


                <div class="col-lg-3 col-md-6">
                    <label class="filter-label">
                        نوع الإجازة
                    </label>

                    <select
                        id="filterLeaveType"
                        class="form-select"
                    >
                        <option value="">
                            جميع أنواع الإجازات
                        </option>
                    </select>
                </div>


                <div class="col-lg-2 col-md-4">
                    <label class="filter-label">
                        السنة
                    </label>

                    <select
                        id="filterYear"
                        class="form-select"
                    ></select>
                </div>


                <div class="col-lg-1 col-md-4">
                    <label class="filter-label">
                        العدد
                    </label>

                    <select
                        id="filterPerPage"
                        class="form-select"
                    >
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>


                <div class="col-lg-2 col-md-4">

                    <div class="d-flex gap-2">

                        <button
                            type="submit"
                            class="btn btn-primary flex-grow-1"
                        >
                            بحث
                        </button>

                        <button
                            type="button"
                            id="resetFilters"
                            class="btn btn-light border"
                        >
                            إعادة
                        </button>

                    </div>

                </div>

            </div>

        </form>

    </div>


    {{-- Table --}}

    <div class="page-card overflow-hidden">

        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead class="table-light">

                    <tr>
                        <th>#</th>
                        <th>الموظف</th>
                        <th>الإدارة</th>
                        <th>المسمى الوظيفي</th>
                        <th>الأرصدة</th>
                        <th>الإجراءات</th>
                    </tr>

                </thead>

                <tbody id="balancesTableBody">

                    <tr>
                        <td
                            colspan="6"
                            class="text-center py-5 text-muted"
                        >
                            جاري تحميل البيانات...
                        </td>
                    </tr>

                </tbody>

            </table>

        </div>


        <div class="pagination-area">

            <div
                id="paginationInfo"
                class="text-muted small"
            ></div>

            <div
                id="paginationButtons"
                class="pagination-buttons"
            ></div>

        </div>

    </div>

</div>


@can('leave.manage')

{{-- Individual Adjustment Modal --}}

<div
    id="adjustmentModal"
    class="ry-modal"
    aria-hidden="true"
>

    <div
        class="ry-modal-panel"
        role="dialog"
        aria-modal="true"
    >

        <div class="ry-modal-header">

            <div class="d-flex align-items-center justify-content-between gap-3">

                <div>
                    <h5 class="mb-1">
                        تسوية رصيد إجازة
                    </h5>

                    <div class="text-muted small">
                        إضافة أو خصم أو تعيين رصيد موظف.
                    </div>
                </div>

                <button
                    type="button"
                    class="ry-modal-close close-adjustment-modal"
                >
                    ×
                </button>

            </div>

        </div>


        <form id="adjustmentForm">

            <div class="ry-modal-body">

                <div
                    id="adjustmentAlert"
                    class="alert alert-danger d-none"
                ></div>


                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="filter-label">
                            الموظف
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            name="employee_id"
                            id="adjustEmployee"
                            class="form-select"
                            required
                        ></select>

                        <div
                            class="invalid-feedback"
                            data-error-for="employee_id"
                        ></div>
                    </div>


                    <div class="col-md-6">
                        <label class="filter-label">
                            نوع الإجازة
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            name="leave_type_id"
                            id="adjustLeaveType"
                            class="form-select"
                            required
                        ></select>

                        <div
                            class="invalid-feedback"
                            data-error-for="leave_type_id"
                        ></div>
                    </div>


                    <div class="col-md-4">
                        <label class="filter-label">
                            السنة
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            name="year"
                            id="adjustYear"
                            class="form-select"
                            required
                        ></select>

                        <div
                            class="invalid-feedback"
                            data-error-for="year"
                        ></div>
                    </div>


                    <div class="col-md-4">
                        <label class="filter-label">
                            نوع التسوية
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            name="mode"
                            id="adjustMode"
                            class="form-select"
                            required
                        >
                            <option value="add">
                                إضافة إلى الرصيد
                            </option>

                            <option value="deduct">
                                خصم من الرصيد
                            </option>

                            <option value="set">
                                تعيين الرصيد
                            </option>
                        </select>

                        <div
                            class="invalid-feedback"
                            data-error-for="mode"
                        ></div>
                    </div>


                    <div class="col-md-4">
                        <label class="filter-label">
                            عدد الأيام
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="number"
                            name="days"
                            id="adjustDays"
                            class="form-control"
                            min="0"
                            max="999.99"
                            step="0.01"
                            required
                        >

                        <div
                            class="invalid-feedback"
                            data-error-for="days"
                        ></div>
                    </div>


                    <div class="col-12">
                        <div class="current-balance-box">

                            <div class="text-muted small mb-1">
                                الرصيد المتاح حاليًا
                            </div>

                            <div
                                id="currentBalanceValue"
                                class="current-balance-value"
                            >
                                --
                            </div>

                            <div
                                id="currentBalanceDetails"
                                class="text-muted small mt-1"
                            >
                                اختر الموظف ونوع الإجازة.
                            </div>

                        </div>
                    </div>


                    <div class="col-12">

                        <div
                            id="modeDescription"
                            class="alert alert-primary py-2 mb-0"
                        ></div>

                    </div>


                    <div class="col-12">
                        <label class="filter-label">
                            سبب التسوية
                            <span class="text-danger">*</span>
                        </label>

                        <textarea
                            name="reason"
                            id="adjustReason"
                            class="form-control"
                            rows="3"
                            maxlength="500"
                            required
                        ></textarea>

                        <div
                            class="invalid-feedback"
                            data-error-for="reason"
                        ></div>
                    </div>

                </div>

            </div>


            <div class="ry-modal-footer">

                <div class="d-flex justify-content-end gap-2">

                    <button
                        type="button"
                        class="btn btn-light border close-adjustment-modal"
                    >
                        إلغاء
                    </button>

                    <button
                        type="submit"
                        id="saveAdjustmentButton"
                        class="btn btn-primary px-4"
                    >
                        حفظ التسوية
                    </button>

                </div>

            </div>

        </form>

    </div>

</div>


{{-- Bulk Adjustment Modal --}}

<div
    id="bulkAdjustmentModal"
    class="ry-modal"
    aria-hidden="true"
>

    <div
        class="ry-modal-panel modal-lg"
        role="dialog"
        aria-modal="true"
    >

        <div class="ry-modal-header">

            <div class="d-flex align-items-center justify-content-between gap-3">

                <div>
                    <h5 class="mb-1">
                        تسوية أرصدة جماعية
                    </h5>

                    <div class="text-muted small">
                        تنفيذ عملية واحدة على عدة موظفين.
                    </div>
                </div>

                <button
                    type="button"
                    class="ry-modal-close close-bulk-modal"
                >
                    ×
                </button>

            </div>

        </div>


        <form id="bulkAdjustmentForm">

            <div class="ry-modal-body">

                <div
                    id="bulkAdjustmentAlert"
                    class="alert alert-danger d-none"
                ></div>


                <div class="row g-4">

                    <div class="col-lg-6">

                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">

                            <label class="filter-label mb-0">
                                الموظفون
                                <span class="text-danger">*</span>
                            </label>

                            <span class="selected-count">
                                <span id="selectedEmployeesCount">0</span>
                                &nbsp;محدد
                            </span>

                        </div>


                        <input
                            type="search"
                            id="bulkEmployeeSearch"
                            class="form-control mb-2"
                            placeholder="البحث بالاسم أو الرقم الوظيفي"
                        >


                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">

                            <label class="form-check mb-0">

                                <input
                                    type="checkbox"
                                    id="selectAllEmployees"
                                    class="form-check-input"
                                >

                                <span class="form-check-label">
                                    تحديد الموظفين الظاهرين
                                </span>

                            </label>

                            <button
                                type="button"
                                id="clearEmployeeSelection"
                                class="btn btn-sm btn-light border"
                            >
                                إلغاء التحديد
                            </button>

                        </div>


                        <div
                            id="bulkEmployeesList"
                            class="bulk-employees-box"
                        >
                            <div class="empty-state">
                                جاري تحميل الموظفين...
                            </div>
                        </div>

                        <div
                            class="text-danger small mt-2"
                            data-error-for="employee_ids"
                        ></div>

                    </div>


                    <div class="col-lg-6">

                        <div class="row g-3">

                            <div class="col-12">
                                <label class="filter-label">
                                    نوع الإجازة
                                    <span class="text-danger">*</span>
                                </label>

                                <select
                                    name="leave_type_id"
                                    id="bulkLeaveType"
                                    class="form-select"
                                    required
                                ></select>

                                <div
                                    class="invalid-feedback"
                                    data-error-for="leave_type_id"
                                ></div>
                            </div>


                            <div class="col-md-4">
                                <label class="filter-label">
                                    السنة
                                    <span class="text-danger">*</span>
                                </label>

                                <select
                                    name="year"
                                    id="bulkYear"
                                    class="form-select"
                                    required
                                ></select>

                                <div
                                    class="invalid-feedback"
                                    data-error-for="year"
                                ></div>
                            </div>


                            <div class="col-md-4">
                                <label class="filter-label">
                                    العملية
                                    <span class="text-danger">*</span>
                                </label>

                                <select
                                    name="mode"
                                    id="bulkMode"
                                    class="form-select"
                                    required
                                >
                                    <option value="add">
                                        إضافة
                                    </option>

                                    <option value="deduct">
                                        خصم
                                    </option>

                                    <option value="set">
                                        تعيين
                                    </option>
                                </select>

                                <div
                                    class="invalid-feedback"
                                    data-error-for="mode"
                                ></div>
                            </div>


                            <div class="col-md-4">
                                <label class="filter-label">
                                    الأيام
                                    <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="number"
                                    name="days"
                                    id="bulkDays"
                                    class="form-control"
                                    min="0"
                                    max="999.99"
                                    step="0.01"
                                    required
                                >

                                <div
                                    class="invalid-feedback"
                                    data-error-for="days"
                                ></div>
                            </div>


                            <div class="col-12">

                                <div
                                    id="bulkModeDescription"
                                    class="alert alert-primary py-2 mb-0"
                                ></div>

                            </div>


                            <div class="col-12">
                                <label class="filter-label">
                                    سبب التسوية
                                    <span class="text-danger">*</span>
                                </label>

                                <textarea
                                    name="reason"
                                    id="bulkReason"
                                    class="form-control"
                                    rows="4"
                                    maxlength="500"
                                    required
                                ></textarea>

                                <div
                                    class="invalid-feedback"
                                    data-error-for="reason"
                                ></div>
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <div class="ry-modal-footer">

                <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">

                    <div class="text-muted small">
                        إذا فشلت تسوية موظف واحد سيتم التراجع عن العملية كاملة.
                    </div>

                    <div class="d-flex gap-2">

                        <button
                            type="button"
                            class="btn btn-light border close-bulk-modal"
                        >
                            إلغاء
                        </button>

                        <button
                            type="submit"
                            id="saveBulkAdjustmentButton"
                            class="btn btn-primary px-4"
                        >
                            تنفيذ التسوية
                        </button>

                    </div>

                </div>

            </div>

        </form>

    </div>

</div>



{{-- Carry Forward Modal --}}

<div
    id="carryForwardModal"
    class="ry-modal"
    aria-hidden="true"
>

    <div
        class="ry-modal-panel modal-lg"
        role="dialog"
        aria-modal="true"
    >

        <div class="ry-modal-header">

            <div class="d-flex align-items-center justify-content-between gap-3">

                <div>
                    <h5 class="mb-1">
                        إقفال وترحيل أرصدة الإجازات
                    </h5>

                    <div class="text-muted small">
                        إقفال رصيد السنة القديمة وترحيل الجزء المسموح للسنة التالية.
                    </div>
                </div>

                <button
                    type="button"
                    class="ry-modal-close close-carry-modal"
                >
                    ×
                </button>

            </div>

        </div>


        <form id="carryForwardForm">

            <div class="ry-modal-body">

                <div
                    id="carryForwardAlert"
                    class="alert alert-danger d-none"
                ></div>


                <div class="alert alert-warning">

                    <div class="fw-bold mb-1">
                        تنبيه مهم
                    </div>

                    <div class="small">
                        سيتم تصفير الرصيد المتاح في السنة المصدر،
                        وترحيل الجزء المسموح فقط إلى السنة التالية،
                        وإنهاء الجزء غير القابل للترحيل.
                    </div>

                </div>


                <div class="row g-4">

                    <div class="col-lg-6">

                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">

                            <label class="filter-label mb-0">
                                الموظفون
                                <span class="text-danger">*</span>
                            </label>

                            <span class="selected-count">
                                <span id="carrySelectedCount">0</span>
                                &nbsp;محدد
                            </span>

                        </div>


                        <input
                            type="search"
                            id="carryEmployeeSearch"
                            class="form-control mb-2"
                            placeholder="البحث بالاسم أو الرقم الوظيفي"
                        >


                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">

                            <label class="form-check mb-0">

                                <input
                                    type="checkbox"
                                    id="selectAllCarryEmployees"
                                    class="form-check-input"
                                >

                                <span class="form-check-label">
                                    تحديد الموظفين الظاهرين
                                </span>

                            </label>


                            <button
                                type="button"
                                id="clearCarrySelection"
                                class="btn btn-sm btn-light border"
                            >
                                إلغاء التحديد
                            </button>

                        </div>


                        <div
                            id="carryEmployeesList"
                            class="bulk-employees-box"
                        >
                            <div class="empty-state">
                                جاري تحميل الموظفين...
                            </div>
                        </div>


                        <div
                            class="text-danger small mt-2"
                            data-error-for="employee_ids"
                        ></div>

                    </div>


                    <div class="col-lg-6">

                        <div class="row g-3">

                            <div class="col-12">

                                <label class="filter-label">
                                    نوع الإجازة
                                    <span class="text-danger">*</span>
                                </label>

                                <select
                                    name="leave_type_id"
                                    id="carryLeaveType"
                                    class="form-select"
                                    required
                                >
                                    <option value="">
                                        اختر نوع الإجازة
                                    </option>
                                </select>

                                <div
                                    class="invalid-feedback"
                                    data-error-for="leave_type_id"
                                ></div>

                                <div
                                    id="carryMaximumInfo"
                                    class="text-muted small mt-2"
                                ></div>

                            </div>


                            <div class="col-md-6">

                                <label class="filter-label">
                                    السنة المصدر
                                    <span class="text-danger">*</span>
                                </label>

                                <select
                                    name="source_year"
                                    id="carrySourceYear"
                                    class="form-select"
                                    required
                                ></select>

                                <div
                                    class="invalid-feedback"
                                    data-error-for="source_year"
                                ></div>

                            </div>


                            <div class="col-md-6">

                                <label class="filter-label">
                                    السنة المستهدفة
                                    <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="number"
                                    name="target_year"
                                    id="carryTargetYear"
                                    class="form-control"
                                    readonly
                                    required
                                >

                                <div
                                    class="invalid-feedback"
                                    data-error-for="target_year"
                                ></div>

                            </div>


                            <div class="col-12">

                                <label class="filter-label">
                                    سبب الإقفال والترحيل
                                    <span class="text-danger">*</span>
                                </label>

                                <textarea
                                    name="reason"
                                    id="carryReason"
                                    class="form-control"
                                    rows="4"
                                    maxlength="500"
                                    required
                                    placeholder="مثال: إقفال أرصدة نهاية السنة وترحيل المستحق"
                                ></textarea>

                                <div
                                    class="invalid-feedback"
                                    data-error-for="reason"
                                ></div>

                            </div>


                            <div class="col-12">

                                <div class="p-3 border rounded-3 bg-light">

                                    <label class="form-check mb-0">

                                        <input
                                            type="checkbox"
                                            name="confirmed"
                                            value="1"
                                            id="carryConfirmed"
                                            class="form-check-input"
                                            required
                                        >

                                        <span class="form-check-label">
                                            أؤكد إقفال أرصدة السنة المصدر،
                                            وأفهم أن الجزء غير القابل للترحيل سينتهي.
                                        </span>

                                    </label>

                                    <div
                                        class="text-danger small mt-2"
                                        data-error-for="confirmed"
                                    ></div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <div class="ry-modal-footer">

                <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">

                    <div class="text-muted small">
                        إذا وُجد طلب إجازة معلق لأي موظف سيتم إلغاء العملية كاملة.
                    </div>

                    <div class="d-flex gap-2">

                        <button
                            type="button"
                            class="btn btn-light border close-carry-modal"
                        >
                            إلغاء
                        </button>

                        <button
                            type="submit"
                            id="saveCarryForwardButton"
                            class="btn btn-warning px-4"
                        >
                            تنفيذ الإقفال والترحيل
                        </button>

                    </div>

                </div>

            </div>

        </form>

    </div>

</div>
@endcan


{{-- History Modal --}}

<div
    id="historyModal"
    class="ry-modal"
    aria-hidden="true"
>

    <div
        class="ry-modal-panel modal-lg"
        role="dialog"
        aria-modal="true"
    >

        <div class="ry-modal-header">

            <div class="d-flex align-items-center justify-content-between gap-3">

                <div>
                    <h5 class="mb-1">
                        سجل حركات الإجازات
                    </h5>

                    <div
                        id="historyEmployeeName"
                        class="text-muted small"
                    ></div>
                </div>

                <button
                    type="button"
                    class="ry-modal-close close-history-modal"
                >
                    ×
                </button>

            </div>

        </div>


        <div class="ry-modal-body">

            <div
                id="historyAlert"
                class="alert alert-danger d-none"
            ></div>


            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">

                        <tr>
                            <th>#</th>
                            <th>التاريخ</th>
                            <th>الحركة</th>
                            <th>نوع الإجازة</th>
                            <th>القيمة</th>
                            <th>الرصيد بعدها</th>
                            <th>نفذ بواسطة</th>
                            <th>الملاحظات</th>
                        </tr>

                    </thead>

                    <tbody id="historyTableBody">

                        <tr>
                            <td
                                colspan="8"
                                class="text-center py-5 text-muted"
                            >
                                جاري تحميل سجل الحركات...
                            </td>
                        </tr>

                    </tbody>

                </table>

            </div>

        </div>


        <div class="ry-modal-footer">

            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">

                <div
                    id="historyPaginationInfo"
                    class="text-muted small"
                ></div>

                <div class="d-flex align-items-center gap-2">

                    <div
                        id="historyPaginationButtons"
                        class="pagination-buttons"
                    ></div>

                    <button
                        type="button"
                        class="btn btn-light border close-history-modal"
                    >
                        إغلاق
                    </button>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection


@push('scripts')

<script>
(function startLeaveBalancesPage() {

    if (!window.jQuery) {
        setTimeout(
            startLeaveBalancesPage,
            50
        );

        return;
    }


    window.jQuery(function ($) {

        var dataUrl =
            @json(route('app.leave-balances.data'));

        var optionsUrl =
            @json(route('app.leave-balances.options'));

        var storeUrl =
            @json(route('app.leave-balances.store'));

        var bulkStoreUrl =
            @json(route('app.leave-balances.bulk-store'));

        var showUrlTemplate =
            @json(
                route(
                    'app.leave-balances.show',
                    ['employee' => '__EMPLOYEE__']
                )
            );

        var historyUrlTemplate =
            @json(
                route(
                    'app.leave-balances.history',
                    ['employee' => '__EMPLOYEE__']
                )
            );

        var carryForwardUrl =
            @json(
                route(
                    'app.leave-balances.carry-forward'
                )
            );


        var canManage =
            @json(auth()->user()->can('leave.manage'));

        var currentPage = 1;
        var historyCurrentPage = 1;
        var historyEmployeeId = null;

        var optionsLoaded = false;
        var optionsRequest = null;

        var employeesOptions = [];
        var leaveTypesOptions = [];
        var yearsOptions = [];


        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN':
                    $('meta[name="csrf-token"]')
                        .attr('content'),

                'Accept':
                    'application/json'
            }
        });


        /*
        |--------------------------------------------------------------------------
        | Helpers
        |--------------------------------------------------------------------------
        */

        function escapeHtml(value) {
            return $('<div>')
                .text(value ?? '')
                .html();
        }


        function numberText(value) {
            var number = parseFloat(value || 0);

            if (Number.isNaN(number)) {
                number = 0;
            }

            return number.toLocaleString(
                'en-US',
                {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2
                }
            );
        }


        function showPageAlert(
            message,
            type
        ) {
            $('#pageAlert')
                .removeClass(
                    'd-none alert-success alert-danger alert-warning'
                )
                .addClass(
                    'alert-' + (type || 'success')
                )
                .text(message);

            $('html, body').animate(
                {
                    scrollTop: 0
                },
                250
            );
        }


        function clearFormErrors(
            formSelector,
            alertSelector
        ) {
            $(formSelector)
                .find('.is-invalid')
                .removeClass('is-invalid');

            $(formSelector)
                .find('[data-error-for]')
                .text('');

            $(alertSelector)
                .addClass('d-none')
                .text('');
        }


        function showAjaxErrors(
            xhr,
            formSelector,
            alertSelector
        ) {
            var response =
                xhr.responseJSON || {};

            var errors =
                response.errors || {};

            var firstMessage =
                response.message ||
                'تعذر تنفيذ العملية.';

            if (
                xhr.status === 422 &&
                Object.keys(errors).length
            ) {
                $.each(
                    errors,
                    function (field, messages) {
                        var baseField =
                            field.split('.')[0];

                        var message =
                            $.isArray(messages)
                                ? messages[0]
                                : messages;

                        $(formSelector)
                            .find(
                                '[name="' +
                                baseField +
                                '"], [name="' +
                                baseField +
                                '[]"]'
                            )
                            .addClass('is-invalid');

                        $(formSelector)
                            .find(
                                '[data-error-for="' +
                                baseField +
                                '"]'
                            )
                            .text(message);
                    }
                );

                firstMessage =
                    Object.values(errors)[0][0];
            }

            $(alertSelector)
                .removeClass('d-none')
                .text(firstMessage);
        }


        function openModal(selector) {
            $(selector)
                .show()
                .attr(
                    'aria-hidden',
                    'false'
                );

            $('body')
                .addClass('ry-modal-open');
        }


        function closeModal(selector) {
            $(selector)
                .hide()
                .attr(
                    'aria-hidden',
                    'true'
                );

            if (!$('.ry-modal:visible').length) {
                $('body')
                    .removeClass('ry-modal-open');
            }
        }


        function modeDescription(mode) {
            var descriptions = {
                add:
                    'سيتم إضافة عدد الأيام إلى الرصيد المتاح الحالي.',

                deduct:
                    'سيتم خصم عدد الأيام من الرصيد المتاح الحالي.',

                set:
                    'سيتم تعيين الرصيد المتاح ليصبح مساويًا للقيمة المدخلة.'
            };

            return descriptions[mode] ||
                descriptions.add;
        }


        /*
        |--------------------------------------------------------------------------
        | Options
        |--------------------------------------------------------------------------
        */

        function loadOptions() {
            if (optionsLoaded) {
                return $.Deferred()
                    .resolve()
                    .promise();
            }

            if (optionsRequest) {
                return optionsRequest;
            }

            optionsRequest = $.ajax({
                url: optionsUrl,
                type: 'GET',

                success: function (response) {
                    employeesOptions =
                        response.employees || [];

                    leaveTypesOptions =
                        response.leave_types || [];

                    yearsOptions =
                        response.years || [];

                    fillOptions();

                    optionsLoaded = true;
                },

                error: function (xhr) {
                    showPageAlert(
                        xhr.responseJSON?.message ||
                        'تعذر تحميل بيانات أرصدة الإجازات.',
                        'danger'
                    );
                },

                complete: function () {
                    optionsRequest = null;
                }
            });

            return optionsRequest;
        }


        function fillOptions() {
            var filterTypeValue =
                $('#filterLeaveType').val();

            var filterYearValue =
                $('#filterYear').val();


            $('#filterLeaveType').html(
                '<option value="">جميع أنواع الإجازات</option>'
            );

            $('#adjustLeaveType, #bulkLeaveType').html(
                '<option value="">اختر نوع الإجازة</option>'
            );

            $.each(
                leaveTypesOptions,
                function (index, item) {
                    var text =
                        escapeHtml(item.name) +
                        ' (' +
                        escapeHtml(item.code) +
                        ')';

                    $('#filterLeaveType').append(
                        `<option value="${item.id}">
                            ${text}
                        </option>`
                    );

                    $('#adjustLeaveType, #bulkLeaveType').append(
                        `<option value="${item.id}">
                            ${text}
                        </option>`
                    );
                }
            );


            $('#filterYear, #adjustYear, #bulkYear')
                .html('');

            $.each(
                yearsOptions,
                function (index, year) {
                    $('#filterYear, #adjustYear, #bulkYear')
                        .append(
                            `<option value="${year}">
                                ${year}
                            </option>`
                        );
                }
            );


            $('#adjustEmployee').html(
                '<option value="">اختر الموظف</option>'
            );

            $.each(
                employeesOptions,
                function (index, employee) {
                    $('#adjustEmployee').append(
                        `<option value="${employee.id}">
                            ${escapeHtml(employee.name)}
                            - ${escapeHtml(employee.employee_number)}
                        </option>`
                    );
                }
            );


            if (filterTypeValue) {
                $('#filterLeaveType').val(
                    filterTypeValue
                );
            }

            $('#filterYear').val(
                filterYearValue ||
                @json($currentYear)
            );

            $('#carryLeaveType').html(
                '<option value="">اختر نوع الإجازة</option>'
            );


            $.each(
                leaveTypesOptions,
                function (index, item) {
                    var carryAllowed =
                        item.allow_carry_forward === true ||
                        parseInt(
                            item.allow_carry_forward,
                            10
                        ) === 1;


                    if (!carryAllowed) {
                        return;
                    }


                    var maximumText =
                        item.maximum_carry_forward !== null &&
                        item.maximum_carry_forward !== ''
                            ? ' - الحد الأعلى: ' +
                                numberText(
                                    item.maximum_carry_forward
                                ) +
                                ' يوم'
                            : ' - بدون حد أعلى';


                    $('#carryLeaveType').append(`
                        <option
                            value="${item.id}"
                            data-maximum="${escapeHtml(
                                item.maximum_carry_forward ?? ''
                            )}"
                        >
                            ${escapeHtml(item.name)}
                            (${escapeHtml(item.code)})
                            ${maximumText}
                        </option>
                    `);
                }
            );


            $('#carrySourceYear').html('');


            $.each(
                yearsOptions,
                function (index, year) {
                    $('#carrySourceYear').append(`
                        <option value="${year}">
                            ${year}
                        </option>
                    `);
                }
            );


            renderCarryEmployees();
            renderBulkEmployees();
        }




        function renderBulkEmployees() {
            var html = '';

            $.each(
                employeesOptions,
                function (index, employee) {
                    var searchText = (
                        employee.name +
                        ' ' +
                        employee.employee_number +
                        ' ' +
                        (employee.department || '')
                    ).toLowerCase();

                    html += `
                        <label
                            class="bulk-employee-item"
                            data-search="${escapeHtml(searchText)}"
                        >
                            <input
                                type="checkbox"
                                name="employee_ids[]"
                                value="${employee.id}"
                                class="form-check-input bulk-employee-check"
                            >

                            <span>
                                <span class="employee-name">
                                    ${escapeHtml(employee.name)}
                                </span>

                                <span class="employee-number d-block">
                                    ${escapeHtml(employee.employee_number)}
                                    ${employee.department
                                        ? ' - ' + escapeHtml(employee.department)
                                        : ''}
                                </span>
                            </span>
                        </label>
                    `;
                }
            );

            $('#bulkEmployeesList').html(
                html ||
                '<div class="empty-state">لا يوجد موظفون متاحون.</div>'
            );

            updateSelectedCount();
        }


        function renderCarryEmployees() {
            var html = '';


            $.each(
                employeesOptions,
                function (index, employee) {
                    var searchText = (
                        employee.name +
                        ' ' +
                        employee.employee_number +
                        ' ' +
                        (employee.department || '')
                    ).toLowerCase();


                    html += `
                        <label
                            class="bulk-employee-item carry-employee-item"
                            data-search="${escapeHtml(searchText)}"
                        >
                            <input
                                type="checkbox"
                                name="employee_ids[]"
                                value="${employee.id}"
                                class="form-check-input carry-employee-check"
                            >

                            <span>
                                <span class="employee-name">
                                    ${escapeHtml(employee.name)}
                                </span>

                                <span class="employee-number d-block">
                                    ${escapeHtml(employee.employee_number)}

                                    ${employee.department
                                        ? ' - ' +
                                            escapeHtml(
                                                employee.department
                                            )
                                        : ''}
                                </span>
                            </span>
                        </label>
                    `;
                }
            );


            $('#carryEmployeesList').html(
                html ||
                '<div class="empty-state">لا يوجد موظفون متاحون.</div>'
            );


            updateCarrySelectedCount();
        }


        function prepareCarryForwardForm() {
            $('#carryForwardForm')[0].reset();


            clearFormErrors(
                '#carryForwardForm',
                '#carryForwardAlert'
            );


            var sourceYear =
                parseInt(
                    $('#filterYear').val() ||
                    @json($currentYear),
                    10
                );


            $('#carrySourceYear').val(
                sourceYear
            );


            $('#carryTargetYear').val(
                sourceYear + 1
            );


            $('#carryEmployeeSearch').val('');


            $('.carry-employee-item').show();


            $('.carry-employee-check').prop(
                'checked',
                false
            );


            $('#selectAllCarryEmployees').prop(
                'checked',
                false
            );


            $('#carryMaximumInfo').text(
                'اختر نوع الإجازة لمعرفة الحد الأعلى المسموح بترحيله.'
            );


            updateCarrySelectedCount();
        }


        function updateCarrySelectedCount() {
            var count =
                $('.carry-employee-check:checked')
                    .length;


            $('#carrySelectedCount').text(
                count
            );


            var visible =
                $('.carry-employee-item:visible')
                    .find(
                        '.carry-employee-check'
                    );


            var checkedVisible =
                visible.filter(':checked');


            $('#selectAllCarryEmployees').prop(
                'checked',
                visible.length > 0 &&
                visible.length ===
                    checkedVisible.length
            );
        }
        /*
        |--------------------------------------------------------------------------
        | Main Data
        |--------------------------------------------------------------------------
        */

        function loadData(page) {
            currentPage = page || 1;

            $('#balancesTableBody').html(`
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        جاري تحميل البيانات...
                    </td>
                </tr>
            `);

            $.ajax({
                url: dataUrl,
                type: 'GET',

                data: {
                    page:
                        currentPage,

                    search:
                        $('#filterSearch').val(),

                    leave_type_id:
                        $('#filterLeaveType').val(),

                    year:
                        $('#filterYear').val(),

                    per_page:
                        $('#filterPerPage').val()
                },

                success: function (response) {
                    renderTable(response);
                    renderMainPagination(response);
                },

                error: function (xhr) {
                    $('#balancesTableBody').html(`
                        <tr>
                            <td colspan="6">
                                <div class="empty-state text-danger">
                                    ${escapeHtml(
                                        xhr.responseJSON?.message ||
                                        'تعذر تحميل أرصدة الإجازات.'
                                    )}
                                </div>
                            </td>
                        </tr>
                    `);
                }
            });
        }


        function renderBalances(balances) {
            if (!balances || !balances.length) {
                return `
                    <div class="text-muted small">
                        لا يوجد رصيد مسجل
                    </div>
                `;
            }

            var html =
                '<div class="balance-list">';

            $.each(
                balances,
                function (index, balance) {
                    var type =
                        balance.leave_type || {};

                    html += `
                        <div class="balance-item">

                            <div class="balance-name">
                                ${escapeHtml(type.name || 'نوع إجازة')}
                            </div>

                            <div class="balance-value">
                                ${numberText(balance.available_balance)}
                                يوم
                            </div>

                            <div class="balance-meta">
                                مستخدم:
                                ${numberText(balance.used_balance)}

                                |
                                معلق:
                                ${numberText(balance.pending_balance)}
                            </div>

                        </div>
                    `;
                }
            );

            html += '</div>';

            return html;
        }


        function renderTable(response) {
            var rows = '';
            var items = response.data || [];
            var start = response.from || 0;

            if (!items.length) {
                $('#balancesTableBody').html(`
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                لا توجد نتائج مطابقة.
                            </div>
                        </td>
                    </tr>
                `);

                return;
            }

            $.each(
                items,
                function (index, employee) {
                    var actions = `
                        <div class="d-flex gap-1 flex-wrap">

                            <button
                                type="button"
                                class="btn btn-sm btn-outline-secondary btn-history"
                                data-employee-id="${employee.id}"
                                data-employee-name="${escapeHtml(employee.name)}"
                            >
                                السجل
                            </button>
                    `;

                    if (canManage) {
                        actions += `
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-primary btn-adjust"
                                data-employee-id="${employee.id}"
                            >
                                تسوية
                            </button>
                        `;
                    }

                    actions += '</div>';

                    rows += `
                        <tr>

                            <td>
                                ${start + index}
                            </td>

                            <td>
                                <div class="employee-name">
                                    ${escapeHtml(employee.name)}
                                </div>

                                <div class="employee-number">
                                    ${escapeHtml(employee.employee_number)}
                                </div>
                            </td>

                            <td>
                                ${escapeHtml(employee.department || '-')}
                            </td>

                            <td>
                                ${escapeHtml(employee.job_title || '-')}
                            </td>

                            <td>
                                ${renderBalances(employee.balances)}
                            </td>

                            <td>
                                ${actions}
                            </td>

                        </tr>
                    `;
                }
            );

            $('#balancesTableBody').html(rows);
        }


        function renderMainPagination(response) {
            $('#paginationInfo').text(
                response.total
                    ? `عرض ${response.from} إلى ${response.to} من ${response.total}`
                    : 'لا توجد سجلات'
            );

            $('#paginationButtons').html(
                buildPaginationButtons(
                    response.current_page,
                    response.last_page,
                    'main-page-button'
                )
            );
        }


        function buildPaginationButtons(
            current,
            last,
            buttonClass
        ) {
            if (!last || last <= 1) {
                return '';
            }

            var html = `
                <button
                    type="button"
                    class="page-button ${buttonClass}"
                    data-page="${current - 1}"
                    ${current <= 1 ? 'disabled' : ''}
                >
                    ‹
                </button>
            `;

            var start =
                Math.max(1, current - 2);

            var end =
                Math.min(last, current + 2);

            for (
                var page = start;
                page <= end;
                page++
            ) {
                html += `
                    <button
                        type="button"
                        class="page-button ${buttonClass}
                            ${page === current ? 'active' : ''}"
                        data-page="${page}"
                    >
                        ${page}
                    </button>
                `;
            }

            html += `
                <button
                    type="button"
                    class="page-button ${buttonClass}"
                    data-page="${current + 1}"
                    ${current >= last ? 'disabled' : ''}
                >
                    ›
                </button>
            `;

            return html;
        }


        /*
        |--------------------------------------------------------------------------
        | Individual Adjustment
        |--------------------------------------------------------------------------
        */

        function prepareAdjustmentForm(
            employeeId
        ) {
            $('#adjustmentForm')[0].reset();

            clearFormErrors(
                '#adjustmentForm',
                '#adjustmentAlert'
            );

            $('#adjustYear').val(
                $('#filterYear').val() ||
                @json($currentYear)
            );

            $('#adjustMode').val('add');

            if (employeeId) {
                $('#adjustEmployee').val(
                    employeeId
                );
            }

            $('#modeDescription').text(
                modeDescription('add')
            );

            loadCurrentBalance();
        }


        function loadCurrentBalance() {
            var employeeId =
                $('#adjustEmployee').val();

            var leaveTypeId =
                $('#adjustLeaveType').val();

            var year =
                $('#adjustYear').val();

            if (
                !employeeId ||
                !leaveTypeId ||
                !year
            ) {
                $('#currentBalanceValue').text('--');

                $('#currentBalanceDetails').text(
                    'اختر الموظف ونوع الإجازة.'
                );

                return;
            }

            $('#currentBalanceValue').text(
                'جاري التحميل...'
            );

            var url =
                showUrlTemplate.replace(
                    '__EMPLOYEE__',
                    employeeId
                );

            $.ajax({
                url: url,
                type: 'GET',

                data: {
                    year: year
                },

                success: function (response) {
                    var balances =
                        response.employee.balances || [];

                    var matched = null;

                    $.each(
                        balances,
                        function (index, balance) {
                            if (
                                parseInt(
                                    balance.leave_type_id,
                                    10
                                ) ===
                                parseInt(
                                    leaveTypeId,
                                    10
                                )
                            ) {
                                matched = balance;

                                return false;
                            }
                        }
                    );

                    if (!matched) {
                        $('#currentBalanceValue').text(
                            '0 يوم'
                        );

                        $('#currentBalanceDetails').text(
                            'لا يوجد رصيد سابق، وسيتم إنشاء سجل جديد.'
                        );

                        return;
                    }

                    $('#currentBalanceValue').text(
                        numberText(
                            matched.available_balance
                        ) +
                        ' يوم'
                    );

                    $('#currentBalanceDetails').text(
                        'المستخدم: ' +
                        numberText(
                            matched.used_balance
                        ) +
                        ' | المعلق: ' +
                        numberText(
                            matched.pending_balance
                        )
                    );
                },

                error: function () {
                    $('#currentBalanceValue').text('--');

                    $('#currentBalanceDetails').text(
                        'تعذر تحميل الرصيد الحالي.'
                    );
                }
            });
        }


        /*
        |--------------------------------------------------------------------------
        | Bulk Adjustment
        |--------------------------------------------------------------------------
        */

        function prepareBulkForm() {
            $('#bulkAdjustmentForm')[0].reset();

            clearFormErrors(
                '#bulkAdjustmentForm',
                '#bulkAdjustmentAlert'
            );

            $('#bulkYear').val(
                $('#filterYear').val() ||
                @json($currentYear)
            );

            $('#bulkMode').val('add');

            $('#bulkModeDescription').text(
                modeDescription('add')
            );

            $('#bulkEmployeeSearch').val('');

            $('.bulk-employee-item').show();

            $('.bulk-employee-check').prop(
                'checked',
                false
            );

            $('#selectAllEmployees').prop(
                'checked',
                false
            );

            updateSelectedCount();
        }


        function updateSelectedCount() {
            var count =
                $('.bulk-employee-check:checked')
                    .length;

            $('#selectedEmployeesCount').text(
                count
            );

            var visible =
                $('.bulk-employee-item:visible')
                    .find('.bulk-employee-check');

            var checkedVisible =
                visible.filter(':checked');

            $('#selectAllEmployees').prop(
                'checked',
                visible.length > 0 &&
                visible.length === checkedVisible.length
            );
        }


        /*
        |--------------------------------------------------------------------------
        | History
        |--------------------------------------------------------------------------
        */

        function loadHistory(page) {
            historyCurrentPage = page || 1;

            if (!historyEmployeeId) {
                return;
            }

            $('#historyTableBody').html(`
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        جاري تحميل سجل الحركات...
                    </td>
                </tr>
            `);

            var url =
                historyUrlTemplate.replace(
                    '__EMPLOYEE__',
                    historyEmployeeId
                );

            $.ajax({
                url: url,
                type: 'GET',

                data: {
                    page:
                        historyCurrentPage,

                    year:
                        $('#filterYear').val(),

                    leave_type_id:
                        $('#filterLeaveType').val(),

                    per_page:
                        15
                },

                success: function (response) {
                    renderHistory(
                        response.transactions
                    );
                },

                error: function (xhr) {
                    $('#historyTableBody').html(`
                        <tr>
                            <td colspan="8">
                                <div class="empty-state text-danger">
                                    ${escapeHtml(
                                        xhr.responseJSON?.message ||
                                        'تعذر تحميل سجل الحركات.'
                                    )}
                                </div>
                            </td>
                        </tr>
                    `);
                }
            });
        }


        function renderHistory(paginator) {
            var items =
                paginator.data || [];

            var rows = '';

            if (!items.length) {
                $('#historyTableBody').html(`
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                لا توجد حركات مسجلة لهذه السنة.
                            </div>
                        </td>
                    </tr>
                `);

                $('#historyPaginationInfo').text('');
                $('#historyPaginationButtons').html('');

                return;
            }

            $.each(
                items,
                function (index, item) {
                    var amount =
                        parseFloat(
                            item.amount || 0
                        );

                    var amountClass =
                        amount >= 0
                            ? 'text-success'
                            : 'text-danger';

                    rows += `
                        <tr>

                            <td>
                                ${(paginator.from || 1) + index}
                            </td>

                            <td>
                                ${escapeHtml(item.effective_date || '-')}
                            </td>

                            <td>
                                ${escapeHtml(item.type_label)}
                            </td>

                            <td>
                                ${escapeHtml(item.leave_type?.name || '-')}
                            </td>

                            <td class="${amountClass} fw-bold" dir="ltr">
                                ${amount > 0 ? '+' : ''}
                                ${numberText(amount)}
                            </td>

                            <td class="fw-bold">
                                ${numberText(item.balance_after)}
                            </td>

                            <td>
                                ${escapeHtml(item.created_by_name)}
                            </td>

                            <td>
                                ${escapeHtml(item.notes || '-')}
                            </td>

                        </tr>
                    `;
                }
            );

            $('#historyTableBody').html(rows);

            $('#historyPaginationInfo').text(
                `عرض ${paginator.from} إلى ${paginator.to} من ${paginator.total}`
            );

            $('#historyPaginationButtons').html(
                buildPaginationButtons(
                    paginator.current_page,
                    paginator.last_page,
                    'history-page-button'
                )
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Events
        |--------------------------------------------------------------------------
        */

        $('#filtersForm').on(
            'submit',
            function (event) {
                event.preventDefault();
                loadData(1);
            }
        );


        $('#resetFilters').on(
            'click',
            function () {
                $('#filterSearch').val('');
                $('#filterLeaveType').val('');
                $('#filterYear').val(
                    @json($currentYear)
                );
                $('#filterPerPage').val('15');

                loadData(1);
            }
        );


        $(document).on(
            'click',
            '.main-page-button',
            function () {
                if (!$(this).prop('disabled')) {
                    loadData(
                        parseInt(
                            $(this).data('page'),
                            10
                        )
                    );
                }
            }
        );


        $(document).on(
            'click',
            '.history-page-button',
            function () {
                if (!$(this).prop('disabled')) {
                    loadHistory(
                        parseInt(
                            $(this).data('page'),
                            10
                        )
                    );
                }
            }
        );


        $('#openAdjustmentModal').on(
            'click',
            function () {
                loadOptions().done(function () {
                    prepareAdjustmentForm(null);

                    openModal(
                        '#adjustmentModal'
                    );
                });
            }
        );


        $(document).on(
            'click',
            '.btn-adjust',
            function () {
                var employeeId =
                    $(this).data('employee-id');

                loadOptions().done(function () {
                    prepareAdjustmentForm(
                        employeeId
                    );

                    openModal(
                        '#adjustmentModal'
                    );
                });
            }
        );


        $('#adjustEmployee, #adjustLeaveType, #adjustYear')
            .on(
                'change',
                loadCurrentBalance
            );


        $('#adjustMode').on(
            'change',
            function () {
                $('#modeDescription').text(
                    modeDescription(
                        $(this).val()
                    )
                );
            }
        );


        $('#adjustmentForm').on(
            'submit',
            function (event) {
                event.preventDefault();

                clearFormErrors(
                    '#adjustmentForm',
                    '#adjustmentAlert'
                );

                var button =
                    $('#saveAdjustmentButton');

                button
                    .prop('disabled', true)
                    .text('جاري الحفظ...');

                $.ajax({
                    url: storeUrl,
                    type: 'POST',
                    data: $(this).serialize(),

                    success: function (response) {
                        closeModal(
                            '#adjustmentModal'
                        );

                        showPageAlert(
                            response.message,
                            'success'
                        );

                        loadData(currentPage);
                    },

                    error: function (xhr) {
                        showAjaxErrors(
                            xhr,
                            '#adjustmentForm',
                            '#adjustmentAlert'
                        );
                    },

                    complete: function () {
                        button
                            .prop('disabled', false)
                            .text('حفظ التسوية');
                    }
                });
            }
        );


        $('#openBulkAdjustmentModal').on(
            'click',
            function () {
                loadOptions().done(function () {
                    prepareBulkForm();

                    openModal(
                        '#bulkAdjustmentModal'
                    );
                });
            }
        );


        $('#bulkEmployeeSearch').on(
            'input',
            function () {
                var search =
                    $(this)
                        .val()
                        .trim()
                        .toLowerCase();

                $('.bulk-employee-item').each(
                    function () {
                        var text =
                            String(
                                $(this).data('search') ||
                                ''
                            ).toLowerCase();

                        $(this).toggle(
                            text.indexOf(search) !== -1
                        );
                    }
                );

                updateSelectedCount();
            }
        );


        $('#selectAllEmployees').on(
            'change',
            function () {
                var checked =
                    $(this).is(':checked');

                $('.bulk-employee-item:visible')
                    .find('.bulk-employee-check')
                    .prop(
                        'checked',
                        checked
                    );

                updateSelectedCount();
            }
        );


        $('#clearEmployeeSelection').on(
            'click',
            function () {
                $('.bulk-employee-check').prop(
                    'checked',
                    false
                );

                $('#selectAllEmployees').prop(
                    'checked',
                    false
                );

                updateSelectedCount();
            }
        );


        $(document).on(
            'change',
            '.bulk-employee-check',
            updateSelectedCount
        );


        $('#bulkMode').on(
            'change',
            function () {
                $('#bulkModeDescription').text(
                    modeDescription(
                        $(this).val()
                    )
                );
            }
        );


        $('#bulkAdjustmentForm').on(
            'submit',
            function (event) {
                event.preventDefault();

                clearFormErrors(
                    '#bulkAdjustmentForm',
                    '#bulkAdjustmentAlert'
                );

                if (
                    !$('.bulk-employee-check:checked')
                        .length
                ) {
                    $('#bulkAdjustmentAlert')
                        .removeClass('d-none')
                        .text(
                            'يجب اختيار موظف واحد على الأقل.'
                        );

                    return;
                }

                var button =
                    $('#saveBulkAdjustmentButton');

                button
                    .prop('disabled', true)
                    .text('جاري التنفيذ...');

                $.ajax({
                    url: bulkStoreUrl,
                    type: 'POST',
                    data: $(this).serialize(),

                    success: function (response) {
                        closeModal(
                            '#bulkAdjustmentModal'
                        );

                        showPageAlert(
                            response.message,
                            'success'
                        );

                        loadData(1);
                    },

                    error: function (xhr) {
                        showAjaxErrors(
                            xhr,
                            '#bulkAdjustmentForm',
                            '#bulkAdjustmentAlert'
                        );
                    },

                    complete: function () {
                        button
                            .prop('disabled', false)
                            .text('تنفيذ التسوية');
                    }
                });
            }
        );


        $(document).on(
            'click',
            '.btn-history',
            function () {
                historyEmployeeId =
                    $(this).data('employee-id');

                historyCurrentPage = 1;

                $('#historyEmployeeName').text(
                    $(this).data('employee-name')
                );

                $('#historyAlert')
                    .addClass('d-none')
                    .text('');

                openModal(
                    '#historyModal'
                );

                loadHistory(1);
            }
        );


        $('.close-adjustment-modal').on(
            'click',
            function () {
                closeModal(
                    '#adjustmentModal'
                );
            }
        );


        $('.close-bulk-modal').on(
            'click',
            function () {
                closeModal(
                    '#bulkAdjustmentModal'
                );
            }
        );


        $('.close-history-modal').on(
            'click',
            function () {
                closeModal(
                    '#historyModal'
                );
            }
        );


        $('.ry-modal').on(
            'click',
            function (event) {
                if (
                    $(event.target).is(
                        $(this)
                    )
                ) {
                    closeModal(
                        '#' + $(this).attr('id')
                    );
                }
            }
        );


        $(document).on(
            'keydown.leaveBalances',
            function (event) {
                if (event.key === 'Escape') {
                    $('.ry-modal:visible')
                        .last()
                        .hide();

                    if (!$('.ry-modal:visible').length) {
                        $('body')
                            .removeClass(
                                'ry-modal-open'
                            );
                    }
                }
            }
        );


$('#openCarryForwardModal').on(
    'click',
    function () {
        loadOptions().done(function () {
            prepareCarryForwardForm();

            openModal(
                '#carryForwardModal'
            );
        });
    }
);


$('#carrySourceYear').on(
    'change',
    function () {
        var sourceYear =
            parseInt(
                $(this).val(),
                10
            );


        $('#carryTargetYear').val(
            sourceYear + 1
        );
    }
);


$('#carryLeaveType').on(
    'change',
    function () {
        var selected =
            $(this).find(
                'option:selected'
            );


        var maximum =
            selected.data('maximum');


        if (!$(this).val()) {
            $('#carryMaximumInfo').text(
                'اختر نوع الإجازة لمعرفة الحد الأعلى المسموح بترحيله.'
            );

            return;
        }


        if (
            maximum === '' ||
            maximum === undefined ||
            maximum === null
        ) {
            $('#carryMaximumInfo').text(
                'سيتم ترحيل كامل الرصيد المتاح.'
            );

            return;
        }


        $('#carryMaximumInfo').text(
            'الحد الأعلى المسموح بترحيله لكل موظف: ' +
            numberText(maximum) +
            ' يوم.'
        );
    }
);


$('#carryEmployeeSearch').on(
    'input',
    function () {
        var search =
            $(this)
                .val()
                .trim()
                .toLowerCase();


        $('.carry-employee-item').each(
            function () {
                var text =
                    String(
                        $(this).data(
                            'search'
                        ) || ''
                    ).toLowerCase();


                $(this).toggle(
                    text.indexOf(search) !==
                    -1
                );
            }
        );


        updateCarrySelectedCount();
    }
);


$('#selectAllCarryEmployees').on(
    'change',
    function () {
        var checked =
            $(this).is(':checked');


        $('.carry-employee-item:visible')
            .find(
                '.carry-employee-check'
            )
            .prop(
                'checked',
                checked
            );


        updateCarrySelectedCount();
    }
);


$('#clearCarrySelection').on(
    'click',
    function () {
        $('.carry-employee-check').prop(
            'checked',
            false
        );


        $('#selectAllCarryEmployees').prop(
            'checked',
            false
        );


        updateCarrySelectedCount();
    }
);


$(document).on(
    'change',
    '.carry-employee-check',
    updateCarrySelectedCount
);


$('#carryForwardForm').on(
    'submit',
    function (event) {
        event.preventDefault();


        clearFormErrors(
            '#carryForwardForm',
            '#carryForwardAlert'
        );


        if (
            !$('.carry-employee-check:checked')
                .length
        ) {
            $('#carryForwardAlert')
                .removeClass('d-none')
                .text(
                    'يجب اختيار موظف واحد على الأقل.'
                );

            return;
        }


        if (
            !$('#carryConfirmed').is(
                ':checked'
            )
        ) {
            $('#carryForwardAlert')
                .removeClass('d-none')
                .text(
                    'يجب تأكيد إقفال الأرصدة قبل التنفيذ.'
                );

            return;
        }


        var button =
            $('#saveCarryForwardButton');


        button
            .prop(
                'disabled',
                true
            )
            .text(
                'جاري الترحيل...'
            );


        $.ajax({
            url:
                carryForwardUrl,

            type:
                'POST',

            data:
                $(this).serialize(),


            success: function (response) {
                closeModal(
                    '#carryForwardModal'
                );


                showPageAlert(
                    response.message,
                    'success'
                );


                $('#filterYear').val(
                    $('#carryTargetYear').val()
                );


                loadData(1);
            },


            error: function (xhr) {
                showAjaxErrors(
                    xhr,
                    '#carryForwardForm',
                    '#carryForwardAlert'
                );
            },


            complete: function () {
                button
                    .prop(
                        'disabled',
                        false
                    )
                    .text(
                        'تنفيذ الإقفال والترحيل'
                    );
            }
        });
    }
);


$('.close-carry-modal').on(
    'click',
    function () {
        closeModal(
            '#carryForwardModal'
        );
    }
);
        /*
        |--------------------------------------------------------------------------
        | Start
        |--------------------------------------------------------------------------
        */

        loadOptions().always(function () {
            loadData(1);
        });

    });

})();
</script>

@endpush