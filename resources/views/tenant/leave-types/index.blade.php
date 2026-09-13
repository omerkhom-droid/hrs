@extends('layouts.tenant')

@section('title', 'أنواع الإجازات')
@section('page-title', 'أنواع الإجازات')

@section('content')

<style>
    .leave-stat-card {
        border: 0;
        border-radius: 18px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .06);
    }

    .leave-stat-icon {
        width: 48px;
        height: 48px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 15px;
        font-size: 20px;
        font-weight: 700;
    }

    .leave-filter-card,
    .leave-table-card {
        border: 0;
        border-radius: 18px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .06);
    }

    .leave-table-card .table > :not(caption) > * > * {
        padding: 14px 12px;
        vertical-align: middle;
    }

    .leave-empty {
        padding: 60px 20px;
        text-align: center;
        color: #64748b;
    }

    .leave-empty-icon {
        width: 70px;
        height: 70px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 22px;
        background: #eff6ff;
        color: #2563eb;
        font-size: 30px;
        margin-bottom: 15px;
    }

    .leave-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 11px;
        border-radius: 30px;
        font-size: 12px;
        font-weight: 700;
    }

    .leave-status-active {
        background: #dcfce7;
        color: #15803d;
    }

    .leave-status-inactive {
        background: #f1f5f9;
        color: #475569;
    }

    .leave-status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: currentColor;
    }

    .leave-action-button {
        min-width: 38px;
        height: 36px;
        border-radius: 10px;
    }

    .leave-pagination-button {
        min-width: 38px;
        height: 36px;
        border: 1px solid #dbe2ea;
        background: #fff;
        border-radius: 10px;
        color: #334155;
    }

    .leave-pagination-button.active {
        background: #0d6efd;
        border-color: #0d6efd;
        color: #fff;
    }

    .leave-pagination-button:disabled {
        opacity: .45;
        cursor: not-allowed;
    }

    /*
     * Custom jQuery modal.
     * لا يعتمد على Bootstrap JavaScript.
     */
    .ry-modal {
        position: fixed;
        inset: 0;
        z-index: 2000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 16px;
        overflow: hidden;
    }

    .ry-modal-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, .65);
    }

    .ry-modal-panel {
        position: relative;
        z-index: 2;
        width: min(100%, 1050px);
        max-height: calc(100vh - 32px);
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 24px 70px rgba(15, 23, 42, .25);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .ry-modal-header,
    .ry-modal-footer {
        flex: 0 0 auto;
        padding: 18px 22px;
        background: #fff;
    }

    .ry-modal-header {
        border-bottom: 1px solid #e5e7eb;
    }

    .ry-modal-footer {
        border-top: 1px solid #e5e7eb;
    }

    .ry-modal-body {
        flex: 1 1 auto;
        width: 100%;
        min-height: 0;
        overflow-x: hidden;
        overflow-y: auto !important;
        overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch;
        scrollbar-gutter: stable;
        padding: 22px;
    }

    .ry-modal-close {
        width: 38px;
        height: 38px;
        border: 0;
        border-radius: 12px;
        background: #f1f5f9;
        color: #334155;
        font-size: 22px;
        line-height: 1;
    }

    .leave-section {
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 18px;
        margin-bottom: 18px;
    }

    .leave-section-title {
        font-size: 15px;
        font-weight: 700;
        margin-bottom: 15px;
        color: #0f172a;
    }

    .leave-switch-item {
        border: 1px solid #e2e8f0;
        border-radius: 13px;
        padding: 13px 14px;
        height: 100%;
        background: #fff;
    }

    .field-error {
        display: block;
        margin-top: 5px;
        color: #dc3545;
        font-size: 12px;
    }

    .is-invalid {
        border-color: #dc3545 !important;
    }

    @media (max-width: 767.98px) {
        .ry-modal {
            padding: 0;
            align-items: flex-end;
        }

        .ry-modal-panel {
            width: 100%;
            max-height: 96vh;
            border-radius: 20px 20px 0 0;
        }

        .leave-table-card .table {
            min-width: 950px;
        }
    }
</style>

<div id="pageAlert" class="alert d-none mb-4"></div>

<div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-4">
    <div>
        <h4 class="mb-1">إعدادات أنواع الإجازات</h4>
        <p class="text-muted mb-0">
            تعريف الإجازات والاستحقاقات وسياسات الخصم والترحيل.
        </p>
    </div>

    @can('leave.manage')
        <button
            type="button"
            class="btn btn-primary px-4 rounded-3"
            id="btnCreateLeaveType"
        >
            <span class="ms-1">＋</span>
            إضافة نوع إجازة
        </button>
    @endcan
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card leave-stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="leave-stat-icon bg-primary-subtle text-primary">
                    #
                </div>

                <div>
                    <div class="text-muted small mb-1">إجمالي الأنواع</div>
                    <div class="fs-4 fw-bold">{{ $summary['total'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card leave-stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="leave-stat-icon bg-success-subtle text-success">
                    ✓
                </div>

                <div>
                    <div class="text-muted small mb-1">الأنواع النشطة</div>
                    <div class="fs-4 fw-bold">{{ $summary['active'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card leave-stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="leave-stat-icon bg-warning-subtle text-warning">
                    ◴
                </div>

                <div>
                    <div class="text-muted small mb-1">تحتاج رصيدًا</div>
                    <div class="fs-4 fw-bold">{{ $summary['requires_balance'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card leave-stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="leave-stat-icon bg-info-subtle text-info">
                    ﷼
                </div>

                <div>
                    <div class="text-muted small mb-1">مدفوعة الأجر</div>
                    <div class="fs-4 fw-bold">{{ $summary['paid'] }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card leave-filter-card mb-4">
    <div class="card-body p-4">
        <div class="row g-3 align-items-end">
            <div class="col-xl-4 col-md-6">
                <label class="form-label">البحث</label>
                <input
                    type="search"
                    class="form-control"
                    id="filterSearch"
                    placeholder="الاسم أو الكود..."
                >
            </div>

            <div class="col-xl-2 col-md-3">
                <label class="form-label">الحالة</label>
                <select class="form-select" id="filterStatus">
                    <option value="">جميع الحالات</option>
                    <option value="active">نشط</option>
                    <option value="inactive">غير نشط</option>
                </select>
            </div>

            <div class="col-xl-2 col-md-3">
                <label class="form-label">الوحدة</label>
                <select class="form-select" id="filterUnit">
                    <option value="">جميع الوحدات</option>
                    <option value="day">يوم</option>
                    <option value="hour">ساعة</option>
                </select>
            </div>

            <div class="col-xl-2 col-md-3">
                <label class="form-label">نوع الدفع</label>
                <select class="form-select" id="filterPaymentType">
                    <option value="">الكل</option>
                    <option value="paid">مدفوعة</option>
                    <option value="unpaid">غير مدفوعة</option>
                    <option value="partially_paid">مدفوعة جزئيًا</option>
                </select>
            </div>

            <div class="col-xl-2 col-md-3">
                <label class="form-label">عدد السجلات</label>
                <select class="form-select" id="filterPerPage">
                    <option value="10">10</option>
                    <option value="15" selected>15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>

            <div class="col-12 d-flex gap-2">
                <button type="button" class="btn btn-primary px-4" id="btnSearch">
                    بحث
                </button>

                <button type="button" class="btn btn-light border px-4" id="btnReset">
                    إعادة
                </button>
            </div>
        </div>
    </div>
</div>

<div class="card leave-table-card">
    <div class="card-header bg-white border-0 p-4 pb-2">
        <div class="d-flex align-items-center justify-content-between gap-3">
            <div>
                <h5 class="mb-1">قائمة أنواع الإجازات</h5>
                <small class="text-muted" id="recordsSummary">جاري التحميل...</small>
            </div>

            <span class="badge bg-primary-subtle text-primary" id="recordsCount">
                0 نوع
            </span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>نوع الإجازة</th>
                    <th>الاستحقاق</th>
                    <th>نوع الدفع</th>
                    <th>طريقة الاستحقاق</th>
                    <th>الرصيد</th>
                    <th>الحالة</th>
                    <th>الاستخدام</th>
                    <th class="text-center">الإجراءات</th>
                </tr>
            </thead>

            <tbody id="leaveTypesTableBody">
                <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
                        جاري تحميل البيانات...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="card-footer bg-white border-0 p-4">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
            <small class="text-muted" id="paginationInfo"></small>
            <div class="d-flex gap-2" id="paginationLinks"></div>
        </div>
    </div>
</div>

{{-- Add/Edit Modal --}}
<div class="ry-modal" id="leaveTypeModal">
    <div class="ry-modal-backdrop" data-close-modal="leaveTypeModal"></div>

    <div class="ry-modal-panel">
        <div class="ry-modal-header">
            <div class="d-flex align-items-center justify-content-between gap-3">
                <div>
                    <h5 class="mb-1" id="leaveTypeModalTitle">إضافة نوع إجازة</h5>
                    <small class="text-muted">
                        أدخل سياسة نوع الإجازة والاستحقاق الخاص بها.
                    </small>
                </div>

                <button
                    type="button"
                    class="ry-modal-close"
                    data-close-modal="leaveTypeModal"
                >
                    ×
                </button>
            </div>
        </div>

        <form id="leaveTypeForm">
            @csrf

            <input type="hidden" id="leaveTypeId">

            <div class="ry-modal-body">
                <div id="formAlert" class="alert alert-danger d-none"></div>

                <div class="leave-section">
                    <div class="leave-section-title">البيانات الأساسية</div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">
                                الكود <span class="text-danger">*</span>
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                name="code"
                                id="code"
                                maxlength="50"
                                dir="ltr"
                                required
                            >
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">
                                الاسم العربي <span class="text-danger">*</span>
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                name="name"
                                id="name"
                                maxlength="255"
                                required
                            >
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">الاسم الإنجليزي</label>

                            <input
                                type="text"
                                class="form-control"
                                name="name_en"
                                id="name_en"
                                maxlength="255"
                                dir="ltr"
                            >
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">
                                وحدة الاحتساب <span class="text-danger">*</span>
                            </label>

                            <select class="form-select" name="unit" id="unit" required>
                                <option value="day">يوم</option>
                                <option value="hour">ساعة</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">
                                نوع الدفع <span class="text-danger">*</span>
                            </label>

                            <select
                                class="form-select"
                                name="payment_type"
                                id="payment_type"
                                required
                            >
                                <option value="paid">مدفوعة الأجر</option>
                                <option value="unpaid">غير مدفوعة الأجر</option>
                                <option value="partially_paid">مدفوعة جزئيًا</option>
                            </select>
                        </div>

                        <div class="col-md-4" id="paidPercentageGroup">
                            <label class="form-label">نسبة الأجر</label>

                            <div class="input-group">
                                <input
                                    type="number"
                                    class="form-control"
                                    name="paid_percentage"
                                    id="paid_percentage"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    value="100"
                                >
                                <span class="input-group-text">%</span>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">الفئة المستفيدة</label>

                            <select class="form-select" name="gender" id="gender">
                                <option value="all">الجميع</option>
                                <option value="male">الذكور فقط</option>
                                <option value="female">الإناث فقط</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">ترتيب العرض</label>

                            <input
                                type="number"
                                class="form-control"
                                name="sort_order"
                                id="sort_order"
                                min="0"
                                value="0"
                            >
                        </div>
                    </div>
                </div>

                <div class="leave-section" id="balanceSection">
                    <div class="leave-section-title">الاستحقاق والرصيد</div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">الاستحقاق الافتراضي</label>

                            <input
                                type="number"
                                class="form-control"
                                name="default_entitlement"
                                id="default_entitlement"
                                min="0"
                                step="0.01"
                                value="0"
                            >
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">طريقة الاستحقاق</label>

                            <select
                                class="form-select"
                                name="accrual_method"
                                id="accrual_method"
                            >
                                <option value="none">بدون تراكم</option>
                                <option value="annual">سنوي</option>
                                <option value="monthly">شهري</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">الإشعار المسبق</label>

                            <div class="input-group">
                                <input
                                    type="number"
                                    class="form-control"
                                    name="minimum_notice_days"
                                    id="minimum_notice_days"
                                    min="0"
                                    value="0"
                                >
                                <span class="input-group-text">يوم</span>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">الحد الأعلى للإجازة المتصلة</label>

                            <div class="input-group">
                                <input
                                    type="number"
                                    class="form-control"
                                    name="maximum_consecutive_days"
                                    id="maximum_consecutive_days"
                                    min="1"
                                >
                                <span class="input-group-text">يوم</span>
                            </div>
                        </div>

                        <div class="col-md-4" id="carryForwardMaximumGroup">
                            <label class="form-label">الحد الأعلى للترحيل</label>

                            <input
                                type="number"
                                class="form-control"
                                name="maximum_carry_forward"
                                id="maximum_carry_forward"
                                min="0"
                                step="0.01"
                                value="0"
                            >
                        </div>
                    </div>
                </div>

                <div class="leave-section mb-0">
                    <div class="leave-section-title">خيارات السياسة</div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="leave-switch-item">
                                <div class="form-check form-switch">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="requires_balance"
                                        id="requires_balance"
                                        value="1"
                                        checked
                                    >
                                    <label class="form-check-label" for="requires_balance">
                                        يتطلب رصيدًا
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="leave-switch-item">
                                <div class="form-check form-switch">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="allow_negative_balance"
                                        id="allow_negative_balance"
                                        value="1"
                                    >
                                    <label class="form-check-label" for="allow_negative_balance">
                                        السماح بالرصيد السالب
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="leave-switch-item">
                                <div class="form-check form-switch">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="allow_during_probation"
                                        id="allow_during_probation"
                                        value="1"
                                    >
                                    <label class="form-check-label" for="allow_during_probation">
                                        السماح أثناء التجربة
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="leave-switch-item">
                                <div class="form-check form-switch">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="allow_half_day"
                                        id="allow_half_day"
                                        value="1"
                                        checked
                                    >
                                    <label class="form-check-label" for="allow_half_day">
                                        السماح بنصف يوم
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="leave-switch-item">
                                <div class="form-check form-switch">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="requires_attachment"
                                        id="requires_attachment"
                                        value="1"
                                    >
                                    <label class="form-check-label" for="requires_attachment">
                                        المرفق إلزامي
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="leave-switch-item">
                                <div class="form-check form-switch">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="allow_carry_forward"
                                        id="allow_carry_forward"
                                        value="1"
                                    >
                                    <label class="form-check-label" for="allow_carry_forward">
                                        السماح بترحيل الرصيد
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="leave-switch-item">
                                <div class="form-check form-switch">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="is_active"
                                        id="is_active"
                                        value="1"
                                        checked
                                    >
                                    <label class="form-check-label" for="is_active">
                                        نوع الإجازة نشط
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ry-modal-footer">
                <div class="d-flex align-items-center justify-content-end gap-2">
                    <button
                        type="button"
                        class="btn btn-light border"
                        data-close-modal="leaveTypeModal"
                    >
                        إلغاء
                    </button>

                    <button type="submit" class="btn btn-primary px-4" id="btnSaveLeaveType">
                        حفظ البيانات
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Disable Confirmation Modal --}}
<div class="ry-modal" id="disableLeaveTypeModal">
    <div class="ry-modal-backdrop" data-close-modal="disableLeaveTypeModal"></div>

    <div class="ry-modal-panel" style="max-width: 500px;">
        <div class="ry-modal-header">
            <div class="d-flex align-items-center justify-content-between">
                <h5 class="mb-0">تعطيل نوع الإجازة</h5>

                <button
                    type="button"
                    class="ry-modal-close"
                    data-close-modal="disableLeaveTypeModal"
                >
                    ×
                </button>
            </div>
        </div>

        <div class="ry-modal-body">
            <p class="mb-2">
                هل تريد تعطيل نوع الإجازة:
                <strong id="disableLeaveTypeName"></strong>؟
            </p>

            <div class="alert alert-warning mb-0">
                سيتم الاحتفاظ بالطلبات والأرصدة السابقة ولن يظهر النوع في الطلبات الجديدة.
            </div>
        </div>

        <div class="ry-modal-footer">
            <div class="d-flex justify-content-end gap-2">
                <button
                    type="button"
                    class="btn btn-light border"
                    data-close-modal="disableLeaveTypeModal"
                >
                    إلغاء
                </button>

                <button type="button" class="btn btn-danger" id="btnConfirmDisable">
                    تأكيد التعطيل
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(function () {
    const canManage = @json(auth()->user()->can('leave.manage'));

    const urls = {
        data: @json(route('app.leave-types.data')),
        store: @json(route('app.leave-types.store')),
        show: @json(route('app.leave-types.show', ['leaveType' => '__ID__'])),
        update: @json(route('app.leave-types.update', ['leaveType' => '__ID__'])),
        destroy: @json(route('app.leave-types.destroy', ['leaveType' => '__ID__']))
    };

    let currentPage = 1;
    let disableId = null;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        }
    });

    function escapeHtml(value) {
        return $('<div>').text(value ?? '').html();
    }

    function resizeModal(id) {
        const $modal = $('#' + id);
        const $panel = $modal.find('.ry-modal-panel').first();
        const $header = $panel.children('.ry-modal-header').first();

        const $body = $panel
            .find('> form > .ry-modal-body, > .ry-modal-body')
            .first();

        const $footer = $panel
            .find('> form > .ry-modal-footer, > .ry-modal-footer')
            .first();

        const viewportHeight = $(window).height();
        const maximumPanelHeight = Math.max(
            320,
            viewportHeight - 32
        );

        const headerHeight =
            $header.length
                ? $header.outerHeight(true)
                : 0;

        const footerHeight =
            $footer.length
                ? $footer.outerHeight(true)
                : 0;

        const bodyHeight = Math.max(
            180,
            maximumPanelHeight -
            headerHeight -
            footerHeight
        );

        $panel.css({
            'height': 'auto',
            'max-height': maximumPanelHeight + 'px'
        });

        $body.css({
            'height': bodyHeight + 'px',
            'max-height': bodyHeight + 'px',
            'overflow-y': 'auto',
            'overflow-x': 'hidden'
        });
    }

    function openModal(id) {
        const $modal = $('#' + id);

        $modal.css({
            'display': 'flex'
        });

        $('body').css({
            'overflow': 'hidden'
        });

        resizeModal(id);

        $modal
            .find('.ry-modal-body')
            .first()
            .scrollTop(0);
    }

    function closeModal(id) {
        $('#' + id).hide();

        if (!$('.ry-modal:visible').length) {
            $('body').css({
                'overflow': ''
            });
        }
    }

    $(window).on(
        'resize.leaveTypesModal',
        function () {
            $('.ry-modal:visible').each(
                function () {
                    resizeModal(
                        $(this).attr('id')
                    );
                }
            );
        }
    );
    
    function showPageAlert(message, type = 'success') {
        $('#pageAlert')
            .removeClass('d-none alert-success alert-danger alert-warning')
            .addClass('alert-' + type)
            .text(message);

        $('html, body').animate({
            scrollTop: 0
        }, 250);
    }

    function clearFormErrors() {
        $('#formAlert').addClass('d-none').empty();
        $('#leaveTypeForm .is-invalid').removeClass('is-invalid');
        $('#leaveTypeForm .field-error').remove();
    }

    function showFormErrors(xhr) {
        clearFormErrors();

        const response = xhr.responseJSON || {};
        const errors = response.errors || {};

        if (Object.keys(errors).length) {
            $.each(errors, function (field, messages) {
                const input = $('#leaveTypeForm [name="' + field + '"]').first();

                input.addClass('is-invalid');

                $('<span class="field-error"></span>')
                    .text(messages[0])
                    .insertAfter(input.closest('.input-group').length
                        ? input.closest('.input-group')
                        : input
                    );
            });

            $('#formAlert')
                .removeClass('d-none')
                .text('يرجى مراجعة الحقول المطلوبة.');

            return;
        }

        $('#formAlert')
            .removeClass('d-none')
            .text(response.message || 'تعذر حفظ البيانات.');
    }

    function paymentLabel(type, percentage) {
        if (type === 'paid') {
            return '<span class="badge bg-success-subtle text-success">مدفوعة</span>';
        }

        if (type === 'unpaid') {
            return '<span class="badge bg-danger-subtle text-danger">غير مدفوعة</span>';
        }

        return '<span class="badge bg-warning-subtle text-warning">جزئي ' +
            escapeHtml(percentage) + '%</span>';
    }

    function accrualLabel(method) {
        const labels = {
            none: 'بدون تراكم',
            annual: 'سنوي',
            monthly: 'شهري'
        };

        return labels[method] || '-';
    }

    function statusLabel(active) {
        return active
            ? '<span class="leave-status leave-status-active"><span class="leave-status-dot"></span>نشط</span>'
            : '<span class="leave-status leave-status-inactive"><span class="leave-status-dot"></span>غير نشط</span>';
    }

    function renderRows(items, from) {
        if (!items.length) {
            $('#leaveTypesTableBody').html(`
                <tr>
                    <td colspan="9">
                        <div class="leave-empty">
                            <div class="leave-empty-icon">☷</div>
                            <h6>لا توجد أنواع إجازات</h6>
                            <p class="mb-0">غيّر خيارات البحث أو أضف نوع إجازة جديدًا.</p>
                        </div>
                    </td>
                </tr>
            `);

            return;
        }

        let html = '';

        $.each(items, function (index, item) {
            let actions = `
                <button
                    type="button"
                    class="btn btn-sm btn-light border leave-action-button btn-edit"
                    data-id="${item.id}"
                    title="تعديل"
                >✎</button>
            `;

            if (canManage && item.is_active) {
                actions += `
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-danger leave-action-button btn-disable"
                        data-id="${item.id}"
                        data-name="${escapeHtml(item.name)}"
                        title="تعطيل"
                    >×</button>
                `;
            }

            if (!canManage) {
                actions = '<span class="text-muted">—</span>';
            }

            html += `
                <tr>
                    <td>${from + index}</td>

                    <td>
                        <div class="fw-bold">${escapeHtml(item.name)}</div>
                        <div class="small text-muted" dir="ltr">
                            ${escapeHtml(item.code)}
                            ${item.name_en ? ' · ' + escapeHtml(item.name_en) : ''}
                        </div>
                    </td>

                    <td>
                        <div class="fw-semibold">
                            ${escapeHtml(item.default_entitlement)}
                            ${item.unit === 'hour' ? 'ساعة' : 'يوم'}
                        </div>
                    </td>

                    <td>${paymentLabel(item.payment_type, item.paid_percentage)}</td>

                    <td>${escapeHtml(accrualLabel(item.accrual_method))}</td>

                    <td>
                        ${item.requires_balance
                            ? '<span class="badge bg-primary-subtle text-primary">يتطلب رصيدًا</span>'
                            : '<span class="text-muted">بدون رصيد</span>'
                        }
                    </td>

                    <td>${statusLabel(item.is_active)}</td>

                    <td>
                        <div>${escapeHtml(item.requests_count)} طلب</div>
                        <small class="text-muted">${escapeHtml(item.balances_count)} رصيد</small>
                    </td>

                    <td>
                        <div class="d-flex justify-content-center gap-2">
                            ${actions}
                        </div>
                    </td>
                </tr>
            `;
        });

        $('#leaveTypesTableBody').html(html);
    }

    function renderPagination(response) {
        const current = response.current_page || 1;
        const last = response.last_page || 1;

        $('#recordsCount').text((response.total || 0) + ' نوع');

        $('#recordsSummary').text(
            response.total
                ? 'عرض ' + response.from + ' إلى ' + response.to + ' من ' + response.total
                : 'لا توجد سجلات'
        );

        $('#paginationInfo').text(
            'الصفحة ' + current + ' من ' + last
        );

        let html = `
            <button
                class="leave-pagination-button pagination-page"
                data-page="${current - 1}"
                ${current <= 1 ? 'disabled' : ''}
            >‹</button>
        `;

        const start = Math.max(1, current - 2);
        const end = Math.min(last, current + 2);

        for (let page = start; page <= end; page++) {
            html += `
                <button
                    class="leave-pagination-button pagination-page ${page === current ? 'active' : ''}"
                    data-page="${page}"
                >${page}</button>
            `;
        }

        html += `
            <button
                class="leave-pagination-button pagination-page"
                data-page="${current + 1}"
                ${current >= last ? 'disabled' : ''}
            >›</button>
        `;

        $('#paginationLinks').html(html);
    }

    function loadData(page = 1) {
        currentPage = page;

        $('#leaveTypesTableBody').html(`
            <tr>
                <td colspan="9" class="text-center py-5 text-muted">
                    جاري تحميل البيانات...
                </td>
            </tr>
        `);

        $.ajax({
            url: urls.data,
            type: 'GET',
            data: {
                page: page,
                search: $('#filterSearch').val(),
                status: $('#filterStatus').val(),
                unit: $('#filterUnit').val(),
                payment_type: $('#filterPaymentType').val(),
                per_page: $('#filterPerPage').val()
            },
            success: function (response) {
                renderRows(response.data || [], response.from || 1);
                renderPagination(response);
            },
            error: function (xhr) {
                $('#leaveTypesTableBody').html(`
                    <tr>
                        <td colspan="9" class="text-center py-5 text-danger">
                            ${escapeHtml(xhr.responseJSON?.message || 'تعذر تحميل البيانات.')}
                        </td>
                    </tr>
                `);
            }
        });
    }

    function resetForm() {
        $('#leaveTypeForm')[0].reset();
        $('#leaveTypeId').val('');
        $('#paid_percentage').val(100);
        $('#default_entitlement').val(0);
        $('#minimum_notice_days').val(0);
        $('#maximum_carry_forward').val(0);
        $('#sort_order').val(0);
        $('#requires_balance').prop('checked', true);
        $('#allow_half_day').prop('checked', true);
        $('#is_active').prop('checked', true);

        clearFormErrors();
        updateConditionalFields();
    }

    function updateConditionalFields() {
        const paymentType = $('#payment_type').val();
        const requiresBalance = $('#requires_balance').is(':checked');
        const allowCarry = $('#allow_carry_forward').is(':checked');
        const unit = $('#unit').val();

        $('#paidPercentageGroup').toggle(paymentType === 'partially_paid');

        if (paymentType === 'paid') {
            $('#paid_percentage').val(100);
        }

        if (paymentType === 'unpaid') {
            $('#paid_percentage').val(0);
        }

        $('#balanceSection').toggle(requiresBalance);

        $('#allow_negative_balance')
            .prop('disabled', !requiresBalance)
            .prop('checked', requiresBalance && $('#allow_negative_balance').is(':checked'));

        $('#allow_carry_forward')
            .prop('disabled', !requiresBalance);

        $('#carryForwardMaximumGroup')
            .toggle(requiresBalance && allowCarry);

        $('#allow_half_day')
            .prop('disabled', unit === 'hour');

        if (unit === 'hour') {
            $('#allow_half_day').prop('checked', false);
        }
    }

    $('#btnCreateLeaveType').on('click', function () {
        resetForm();
        $('#leaveTypeModalTitle').text('إضافة نوع إجازة');
        openModal('leaveTypeModal');
    });

    $(document).on('click', '[data-close-modal]', function () {
        closeModal($(this).data('close-modal'));
    });

    $(document).on('keyup', function (event) {
        if (event.key === 'Escape') {
            $('.ry-modal:visible').each(function () {
                closeModal($(this).attr('id'));
            });
        }
    });

    $('#payment_type, #unit').on('change', updateConditionalFields);
    $('#requires_balance, #allow_carry_forward').on('change', updateConditionalFields);

    $('#btnSearch').on('click', function () {
        loadData(1);
    });

    $('#filterSearch').on('keypress', function (event) {
        if (event.which === 13) {
            event.preventDefault();
            loadData(1);
        }
    });

    $('#btnReset').on('click', function () {
        $('#filterSearch').val('');
        $('#filterStatus').val('');
        $('#filterUnit').val('');
        $('#filterPaymentType').val('');
        $('#filterPerPage').val('15');

        loadData(1);
    });

    $('#filterPerPage').on('change', function () {
        loadData(1);
    });

    $(document).on('click', '.pagination-page:not(:disabled)', function () {
        loadData(parseInt($(this).data('page')));
    });

    $(document).on('click', '.btn-edit', function () {
        const id = $(this).data('id');

        resetForm();
        $('#leaveTypeModalTitle').text('تعديل نوع الإجازة');
        openModal('leaveTypeModal');

        $.ajax({
            url: urls.show.replace('__ID__', id),
            type: 'GET',
            success: function (response) {
                const item = response.leave_type;

                $('#leaveTypeId').val(item.id);
                $('#code').val(item.code);
                $('#name').val(item.name);
                $('#name_en').val(item.name_en);
                $('#unit').val(item.unit);
                $('#payment_type').val(item.payment_type);
                $('#paid_percentage').val(item.paid_percentage);
                $('#default_entitlement').val(item.default_entitlement);
                $('#accrual_method').val(item.accrual_method);
                $('#minimum_notice_days').val(item.minimum_notice_days);
                $('#maximum_consecutive_days').val(item.maximum_consecutive_days);
                $('#maximum_carry_forward').val(item.maximum_carry_forward);
                $('#gender').val(item.gender);
                $('#sort_order').val(item.sort_order);

                $('#requires_balance').prop('checked', Boolean(item.requires_balance));
                $('#allow_negative_balance').prop('checked', Boolean(item.allow_negative_balance));
                $('#allow_during_probation').prop('checked', Boolean(item.allow_during_probation));
                $('#allow_half_day').prop('checked', Boolean(item.allow_half_day));
                $('#requires_attachment').prop('checked', Boolean(item.requires_attachment));
                $('#allow_carry_forward').prop('checked', Boolean(item.allow_carry_forward));
                $('#is_active').prop('checked', Boolean(item.is_active));

                updateConditionalFields();
            },
            error: function (xhr) {
                closeModal('leaveTypeModal');
                showPageAlert(
                    xhr.responseJSON?.message || 'تعذر تحميل بيانات نوع الإجازة.',
                    'danger'
                );
            }
        });
    });

    $('#leaveTypeForm').on('submit', function (event) {
        event.preventDefault();

        clearFormErrors();

        const id = $('#leaveTypeId').val();
        const isEdit = id !== '';
        const button = $('#btnSaveLeaveType');
        const originalText = button.text();

        let formData = $(this).serializeArray();

        if (isEdit) {
            formData.push({
                name: '_method',
                value: 'PUT'
            });
        }

        button.prop('disabled', true).text('جاري الحفظ...');

        $.ajax({
            url: isEdit
                ? urls.update.replace('__ID__', id)
                : urls.store,
            type: 'POST',
            data: $.param(formData),
            success: function (response) {
                closeModal('leaveTypeModal');
                showPageAlert(response.message || 'تم حفظ البيانات.');
                loadData(isEdit ? currentPage : 1);
            },
            error: function (xhr) {
                showFormErrors(xhr);
            },
            complete: function () {
                button.prop('disabled', false).text(originalText);
            }
        });
    });

    $(document).on('click', '.btn-disable', function () {
        disableId = $(this).data('id');

        $('#disableLeaveTypeName').text($(this).data('name'));
        openModal('disableLeaveTypeModal');
    });

    $('#btnConfirmDisable').on('click', function () {
        if (!disableId) {
            return;
        }

        const button = $(this);
        const originalText = button.text();

        button.prop('disabled', true).text('جاري التعطيل...');

        $.ajax({
            url: urls.destroy.replace('__ID__', disableId),
            type: 'POST',
            data: {
                _method: 'DELETE'
            },
            success: function (response) {
                closeModal('disableLeaveTypeModal');
                showPageAlert(response.message || 'تم تعطيل نوع الإجازة.');
                disableId = null;
                loadData(currentPage);
            },
            error: function (xhr) {
                showPageAlert(
                    xhr.responseJSON?.message || 'تعذر تعطيل نوع الإجازة.',
                    'danger'
                );
            },
            complete: function () {
                button.prop('disabled', false).text(originalText);
            }
        });
    });

    loadData();
});
</script>

@endpush