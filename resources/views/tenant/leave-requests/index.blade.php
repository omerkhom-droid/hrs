@extends('layouts.tenant')

@section('title', 'طلبات الإجازات')
@section('page-title', 'طلبات الإجازات')

@section('content')

<style>
    .leave-card {
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

    .leave-table-card .table > :not(caption) > * > * {
        padding: 14px 12px;
        vertical-align: middle;
    }

    .leave-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 11px;
        border-radius: 30px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .leave-status-draft {
        background: #f1f5f9;
        color: #475569;
    }

    .leave-status-pending {
        background: #fef3c7;
        color: #b45309;
    }

    .leave-status-approved {
        background: #dcfce7;
        color: #15803d;
    }

    .leave-status-rejected {
        background: #fee2e2;
        color: #b91c1c;
    }

    .leave-status-cancelled {
        background: #e2e8f0;
        color: #334155;
    }

    .leave-status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: currentColor;
    }

    .leave-empty {
        padding: 60px 20px;
        text-align: center;
        color: #64748b;
    }

    .leave-empty-icon {
        width: 70px;
        height: 70px;
        margin: 0 auto 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 22px;
        background: #eff6ff;
        color: #2563eb;
        font-size: 30px;
    }

    .leave-action-button {
        min-width: 36px;
        height: 34px;
        border-radius: 9px;
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
        color: #0f172a;
        margin-bottom: 15px;
    }

    .leave-calculation {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px dashed #93c5fd;
        border-radius: 13px;
        padding: 13px 15px;
    }

    .field-error {
        display: block;
        color: #dc3545;
        font-size: 12px;
        margin-top: 5px;
    }

    .is-invalid {
        border-color: #dc3545 !important;
    }

    /*
     * Custom jQuery Modal
     */
    .ry-modal {
        position: fixed;
        inset: 0;
        z-index: 2050;
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
        display: flex;
        flex-direction: column;
        overflow: hidden;
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 24px 70px rgba(15, 23, 42, .28);
    }

    .ry-modal-panel > form {
        flex: 1 1 auto;
        min-height: 0;
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
        padding: 22px;
        overflow-x: hidden;
        overflow-y: auto !important;
        overscroll-behavior: contain;
        scrollbar-gutter: stable;
    }

    .ry-modal-body::-webkit-scrollbar {
        width: 8px;
    }

    .ry-modal-body::-webkit-scrollbar-thumb {
        background: #94a3b8;
        border-radius: 20px;
    }

    .ry-modal-close {
        width: 38px;
        height: 38px;
        border: 0;
        border-radius: 12px;
        background: #f1f5f9;
        color: #334155;
        font-size: 22px;
    }

    @media (max-width: 767.98px) {
        .ry-modal {
            padding: 0;
            align-items: flex-end;
        }

        .ry-modal-panel {
            max-height: 96vh;
            border-radius: 20px 20px 0 0;
        }

        .leave-table-card table {
            min-width: 1050px;
        }
    }
</style>

<div id="pageAlert" class="alert d-none mb-4"></div>

<div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-4">
    <div>
        <h4 class="mb-1">إدارة طلبات الإجازات</h4>
        <p class="text-muted mb-0">
            إنشاء ومتابعة وتعديل طلبات إجازات الموظفين.
        </p>
    </div>

    @can('leave.manage')
        <button
            type="button"
            class="btn btn-primary px-4 rounded-3"
            id="btnCreateRequest"
        >
            ＋ إضافة طلب إجازة
        </button>
    @endcan
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card leave-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="leave-stat-icon bg-primary-subtle text-primary">#</div>
                <div>
                    <div class="text-muted small">إجمالي الطلبات</div>
                    <div class="fs-4 fw-bold">{{ $summary['total'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card leave-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="leave-stat-icon bg-warning-subtle text-warning">◴</div>
                <div>
                    <div class="text-muted small">بانتظار الاعتماد</div>
                    <div class="fs-4 fw-bold">{{ $summary['pending'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card leave-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="leave-stat-icon bg-success-subtle text-success">✓</div>
                <div>
                    <div class="text-muted small">الطلبات المعتمدة</div>
                    <div class="fs-4 fw-bold">{{ $summary['approved'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card leave-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="leave-stat-icon bg-info-subtle text-info">☀</div>
                <div>
                    <div class="text-muted small">في إجازة اليوم</div>
                    <div class="fs-4 fw-bold">{{ $summary['on_leave_today'] }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card leave-card mb-4">
    <div class="card-body p-4">
        <div class="row g-3 align-items-end">
            <div class="col-xl-3 col-md-6">
                <label class="form-label">البحث</label>
                <input
                    type="search"
                    class="form-control"
                    id="filterSearch"
                    placeholder="الموظف أو رقم الطلب..."
                >
            </div>

            <div class="col-xl-2 col-md-3">
                <label class="form-label">الحالة</label>
                <select class="form-select" id="filterStatus">
                    <option value="">جميع الحالات</option>
                    <option value="draft">مسودة</option>
                    <option value="pending">بانتظار الاعتماد</option>
                    <option value="approved">معتمد</option>
                    <option value="rejected">مرفوض</option>
                    <option value="cancelled">ملغي</option>
                </select>
            </div>

            <div class="col-xl-2 col-md-3">
                <label class="form-label">نوع الإجازة</label>
                <select class="form-select" id="filterLeaveType">
                    <option value="">جميع الأنواع</option>
                </select>
            </div>

            <div class="col-xl-2 col-md-3">
                <label class="form-label">من تاريخ</label>
                <input type="date" class="form-control" id="filterDateFrom">
            </div>

            <div class="col-xl-2 col-md-3">
                <label class="form-label">إلى تاريخ</label>
                <input type="date" class="form-control" id="filterDateTo">
            </div>

            <div class="col-xl-1 col-md-3">
                <label class="form-label">العدد</label>
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

<div class="card leave-card leave-table-card">
    <div class="card-header bg-white border-0 p-4 pb-2">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h5 class="mb-1">سجل طلبات الإجازات</h5>
                <small class="text-muted" id="recordsSummary">جاري التحميل...</small>
            </div>

            <span class="badge bg-primary-subtle text-primary" id="recordsCount">
                0 طلب
            </span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>الموظف</th>
                    <th>نوع الإجازة</th>
                    <th>الفترة</th>
                    <th>المدة</th>
                    <th>الحالة</th>
                    <th>تاريخ الطلب</th>
                    <th class="text-center">الإجراءات</th>
                </tr>
            </thead>

            <tbody id="requestsTableBody">
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
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

{{-- Create/Edit Modal --}}
<div class="ry-modal" id="requestModal">
    <div class="ry-modal-backdrop" data-close-modal="requestModal"></div>

    <div class="ry-modal-panel">
        <div class="ry-modal-header">
            <div class="d-flex align-items-center justify-content-between gap-3">
                <div>
                    <h5 class="mb-1" id="requestModalTitle">إضافة طلب إجازة</h5>
                    <small class="text-muted">
                        أدخل بيانات الموظف وفترة الإجازة.
                    </small>
                </div>

                <button
                    type="button"
                    class="ry-modal-close"
                    data-close-modal="requestModal"
                >×</button>
            </div>
        </div>

        <form id="requestForm" enctype="multipart/form-data">
            @csrf

            <input type="hidden" id="requestId">
            <input type="hidden" name="submit_now" id="submit_now" value="0">

            <div class="ry-modal-body">
                <div id="formAlert" class="alert alert-danger d-none"></div>

                <div class="leave-section">
                    <div class="leave-section-title">الموظف ونوع الإجازة</div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">
                                الموظف <span class="text-danger">*</span>
                            </label>

                            <select
                                class="form-select"
                                name="employee_id"
                                id="employee_id"
                                required
                            >
                                <option value="">اختر الموظف</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                نوع الإجازة <span class="text-danger">*</span>
                            </label>

                            <select
                                class="form-select"
                                name="leave_type_id"
                                id="leave_type_id"
                                required
                            >
                                <option value="">اختر نوع الإجازة</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">الموظف البديل</label>

                            <select
                                class="form-select"
                                name="replacement_employee_id"
                                id="replacement_employee_id"
                            >
                                <option value="">بدون موظف بديل</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">متطلبات النوع</label>
                            <div class="leave-calculation" id="leaveTypeInfo">
                                اختر نوع الإجازة لعرض متطلباتها.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="leave-section">
                    <div class="leave-section-title">فترة الإجازة</div>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">
                                تاريخ البداية <span class="text-danger">*</span>
                            </label>

                            <input
                                type="date"
                                class="form-control"
                                name="start_date"
                                id="start_date"
                                required
                            >
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">
                                فترة البداية <span class="text-danger">*</span>
                            </label>

                            <select
                                class="form-select"
                                name="start_session"
                                id="start_session"
                            >
                                <option value="full_day">يوم كامل</option>
                                <option value="first_half">النصف الأول</option>
                                <option value="second_half">النصف الثاني</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">
                                تاريخ النهاية <span class="text-danger">*</span>
                            </label>

                            <input
                                type="date"
                                class="form-control"
                                name="end_date"
                                id="end_date"
                                required
                            >
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">
                                فترة النهاية <span class="text-danger">*</span>
                            </label>

                            <select
                                class="form-select"
                                name="end_session"
                                id="end_session"
                            >
                                <option value="full_day">يوم كامل</option>
                                <option value="first_half">النصف الأول</option>
                                <option value="second_half">النصف الثاني</option>
                            </select>
                        </div>

                        <div class="col-md-4 d-none" id="requestedAmountGroup">
                            <label class="form-label">عدد الساعات</label>

                            <input
                                type="number"
                                class="form-control"
                                name="requested_amount"
                                id="requested_amount"
                                min="0.25"
                                max="24"
                                step="0.25"
                            >
                        </div>

                        <div class="col-12">
                            <div class="leave-calculation" id="durationPreview">
                                حدد تاريخ البداية والنهاية لحساب المدة التقريبية.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="leave-section mb-0">
                    <div class="leave-section-title">تفاصيل الطلب</div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">
                                سبب الإجازة <span class="text-danger">*</span>
                            </label>

                            <textarea
                                class="form-control"
                                name="reason"
                                id="reason"
                                rows="3"
                                maxlength="2000"
                                required
                            ></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">ملاحظات تسليم العمل</label>

                            <textarea
                                class="form-control"
                                name="handover_notes"
                                id="handover_notes"
                                rows="3"
                                maxlength="3000"
                            ></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">التواصل أثناء الإجازة</label>

                            <input
                                type="text"
                                class="form-control"
                                name="contact_during_leave"
                                id="contact_during_leave"
                                maxlength="255"
                            >

                            <label class="form-label mt-3">المرفق</label>

                            <input
                                type="file"
                                class="form-control"
                                name="attachment"
                                id="attachment"
                                accept=".pdf,.jpg,.jpeg,.png,.webp"
                            >

                            <small class="text-muted">
                                PDF أو صورة، بحد أقصى 5MB.
                            </small>

                            <div class="mt-2 d-none" id="existingAttachment">
                                <a href="#" target="_blank" id="existingAttachmentLink">
                                    عرض المرفق الحالي
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ry-modal-footer">
                <div class="d-flex align-items-center justify-content-end gap-2 flex-wrap">
                    <button
                        type="button"
                        class="btn btn-light border"
                        data-close-modal="requestModal"
                    >
                        إلغاء
                    </button>

                    <button
                        type="button"
                        class="btn btn-outline-primary"
                        id="btnSaveDraft"
                    >
                        حفظ كمسودة
                    </button>

                    <button
                        type="button"
                        class="btn btn-primary px-4"
                        id="btnSaveAndSubmit"
                    >
                        حفظ وإرسال للاعتماد
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Details Modal --}}
<div class="ry-modal" id="detailsModal">
    <div class="ry-modal-backdrop" data-close-modal="detailsModal"></div>

    <div class="ry-modal-panel" style="max-width: 850px;">
        <div class="ry-modal-header">
            <div class="d-flex align-items-center justify-content-between">
                <h5 class="mb-0">تفاصيل طلب الإجازة</h5>
                <button
                    type="button"
                    class="ry-modal-close"
                    data-close-modal="detailsModal"
                >×</button>
            </div>
        </div>

        <div class="ry-modal-body" id="requestDetails">
            جاري تحميل البيانات...
        </div>

        <div class="ry-modal-footer">
            <div class="d-flex justify-content-end">
                <button
                    type="button"
                    class="btn btn-light border"
                    data-close-modal="detailsModal"
                >
                    إغلاق
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Cancel Modal --}}
<div class="ry-modal" id="cancelModal">
    <div class="ry-modal-backdrop" data-close-modal="cancelModal"></div>

    <div class="ry-modal-panel" style="max-width: 550px;">
        <div class="ry-modal-header">
            <div class="d-flex align-items-center justify-content-between">
                <h5 class="mb-0">إلغاء طلب الإجازة</h5>
                <button
                    type="button"
                    class="ry-modal-close"
                    data-close-modal="cancelModal"
                >×</button>
            </div>
        </div>

        <div class="ry-modal-body">
            <label class="form-label">
                سبب الإلغاء <span class="text-danger">*</span>
            </label>

            <textarea
                class="form-control"
                id="cancelReason"
                rows="4"
                maxlength="2000"
            ></textarea>

            <div class="text-danger small mt-2 d-none" id="cancelError"></div>
        </div>

        <div class="ry-modal-footer">
            <div class="d-flex justify-content-end gap-2">
                <button
                    type="button"
                    class="btn btn-light border"
                    data-close-modal="cancelModal"
                >
                    رجوع
                </button>

                <button type="button" class="btn btn-danger" id="btnConfirmCancel">
                    تأكيد الإلغاء
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Delete Modal --}}
<div class="ry-modal" id="deleteModal">
    <div class="ry-modal-backdrop" data-close-modal="deleteModal"></div>

    <div class="ry-modal-panel" style="max-width: 500px;">
        <div class="ry-modal-header">
            <div class="d-flex align-items-center justify-content-between">
                <h5 class="mb-0">حذف المسودة</h5>
                <button
                    type="button"
                    class="ry-modal-close"
                    data-close-modal="deleteModal"
                >×</button>
            </div>
        </div>

        <div class="ry-modal-body">
            <div class="alert alert-warning mb-0">
                هل تريد حذف مسودة طلب الإجازة؟ لا يمكن التراجع عن هذا الإجراء.
            </div>
        </div>

        <div class="ry-modal-footer">
            <div class="d-flex justify-content-end gap-2">
                <button
                    type="button"
                    class="btn btn-light border"
                    data-close-modal="deleteModal"
                >
                    إلغاء
                </button>

                <button type="button" class="btn btn-danger" id="btnConfirmDelete">
                    حذف المسودة
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function startLeaveRequestsPage() {

    if (typeof window.jQuery === 'undefined') {
        window.setTimeout(
            startLeaveRequestsPage,
            50
        );

        return;
    }

    window.jQuery(function ($) {
        const canManage = @json(auth()->user()->can('leave.manage'));

        const urls = {
            data: @json(route('app.leaves.requests.data')),
            options: @json(route('app.leaves.requests.options')),
            store: @json(route('app.leaves.requests.store')),
            show: @json(route('app.leaves.requests.show', ['leaveRequest' => '__ID__'])),
            update: @json(route('app.leaves.requests.update', ['leaveRequest' => '__ID__'])),
            destroy: @json(route('app.leaves.requests.destroy', ['leaveRequest' => '__ID__'])),
            submit: @json(route('app.leaves.requests.submit', ['leaveRequest' => '__ID__'])),
            cancel: @json(route('app.leaves.requests.cancel', ['leaveRequest' => '__ID__']))
        };

        let currentPage = 1;
        let cancelRequestId = null;
        let deleteRequestId = null;
        let leaveTypes = {};
        let optionsLoaded = false;

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

            const maximumHeight = Math.max(
                320,
                $(window).height() - 32
            );

            const bodyHeight = Math.max(
                180,
                maximumHeight -
                ($header.outerHeight(true) || 0) -
                ($footer.outerHeight(true) || 0)
            );

            $panel.css('max-height', maximumHeight + 'px');

            $body.css({
                'height': bodyHeight + 'px',
                'max-height': bodyHeight + 'px',
                'overflow-y': 'auto'
            });
        }

        function openModal(id) {
            $('#' + id).css('display', 'flex');
            $('body').css('overflow', 'hidden');

            resizeModal(id);

            $('#' + id)
                .find('.ry-modal-body')
                .first()
                .scrollTop(0);
        }

        function closeModal(id) {
            $('#' + id).hide();

            if (!$('.ry-modal:visible').length) {
                $('body').css('overflow', '');
            }
        }

        $(window).on('resize.leaveRequestsModal', function () {
            $('.ry-modal:visible').each(function () {
                resizeModal($(this).attr('id'));
            });
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

        function showAlert(message, type = 'success') {
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
            $('#requestForm .is-invalid').removeClass('is-invalid');
            $('#requestForm .field-error').remove();
        }

        function showFormErrors(xhr) {
            clearFormErrors();

            const response = xhr.responseJSON || {};
            const errors = response.errors || {};

            if (Object.keys(errors).length) {
                $.each(errors, function (field, messages) {
                    const $input = $('#requestForm [name="' + field + '"]').first();

                    $input.addClass('is-invalid');

                    $('<span class="field-error"></span>')
                        .text(messages[0])
                        .insertAfter($input);
                });

                $('#formAlert')
                    .removeClass('d-none')
                    .text('يرجى مراجعة الحقول المطلوبة.');

                return;
            }

            $('#formAlert')
                .removeClass('d-none')
                .text(response.message || 'تعذر حفظ طلب الإجازة.');
        }

        function statusBadge(status, label) {
            return `
                <span class="leave-status leave-status-${escapeHtml(status)}">
                    <span class="leave-status-dot"></span>
                    ${escapeHtml(label)}
                </span>
            `;
        }

        function dateText(value) {
            if (!value) {
                return '-';
            }

            return String(value).substring(0, 10);
        }

        function employeeName(employee) {
            if (!employee) {
                return '-';
            }

            if (employee.name) {
                return employee.name;
            }

            return [
                employee.first_name,
                employee.father_name,
                employee.grandfather_name,
                employee.family_name
            ].filter(Boolean).join(' ');
        }

        function loadOptions() {
            if (optionsLoaded) {
                return $.Deferred().resolve().promise();
            }

            return $.ajax({
                url: urls.options,
                type: 'GET',
                success: function (response) {
                    let employeeOptions = '<option value="">اختر الموظف</option>';
                    let replacementOptions = '<option value="">بدون موظف بديل</option>';
                    let typeOptions = '<option value="">اختر نوع الإجازة</option>';
                    let filterTypeOptions = '<option value="">جميع الأنواع</option>';

                    $.each(response.employees || [], function (_, employee) {
                        const label =
                            escapeHtml(employee.name) +
                            ' - ' +
                            escapeHtml(employee.employee_number);

                        employeeOptions += `
                            <option value="${employee.id}">${label}</option>
                        `;

                        replacementOptions += `
                            <option value="${employee.id}">${label}</option>
                        `;
                    });

                    $.each(response.leave_types || [], function (_, type) {
                        leaveTypes[type.id] = type;

                        typeOptions += `
                            <option value="${type.id}">
                                ${escapeHtml(type.name)}
                            </option>
                        `;

                        filterTypeOptions += `
                            <option value="${type.id}">
                                ${escapeHtml(type.name)}
                            </option>
                        `;
                    });

                    $('#employee_id').html(employeeOptions);
                    $('#replacement_employee_id').html(replacementOptions);
                    $('#leave_type_id').html(typeOptions);
                    $('#filterLeaveType').html(filterTypeOptions);

                    optionsLoaded = true;
                }
            });
        }

        function renderActions(item) {
            let html = `
                <button
                    type="button"
                    class="btn btn-sm btn-light border leave-action-button btn-details"
                    data-id="${item.id}"
                    title="عرض"
                >◉</button>
            `;

            if (!canManage) {
                return html;
            }

            if (item.can_edit) {
                html += `
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-primary leave-action-button btn-edit"
                        data-id="${item.id}"
                        title="تعديل"
                    >✎</button>
                `;
            }

            if (item.can_submit) {
                html += `
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-success leave-action-button btn-submit-request"
                        data-id="${item.id}"
                        title="إرسال للاعتماد"
                    >↑</button>
                `;
            }

            if (item.can_cancel) {
                html += `
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-warning leave-action-button btn-cancel-request"
                        data-id="${item.id}"
                        title="إلغاء"
                    >×</button>
                `;
            }

            if (item.status === 'draft') {
                html += `
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-danger leave-action-button btn-delete-request"
                        data-id="${item.id}"
                        title="حذف"
                    >⌫</button>
                `;
            }

            return html;
        }

        function renderRows(items, from) {
            if (!items.length) {
                $('#requestsTableBody').html(`
                    <tr>
                        <td colspan="8">
                            <div class="leave-empty">
                                <div class="leave-empty-icon">☷</div>
                                <h6>لا توجد طلبات إجازة</h6>
                                <p class="mb-0">غيّر خيارات البحث أو أضف طلبًا جديدًا.</p>
                            </div>
                        </td>
                    </tr>
                `);

                return;
            }

            let html = '';

            $.each(items, function (index, item) {
                html += `
                    <tr>
                        <td>${from + index}</td>

                        <td>
                            <div class="fw-bold">${escapeHtml(item.employee?.name)}</div>
                            <small class="text-muted">
                                ${escapeHtml(item.employee?.employee_number)}
                            </small>
                        </td>

                        <td>
                            <div class="fw-semibold">
                                ${escapeHtml(item.leave_type?.name)}
                            </div>
                            <small class="text-muted" dir="ltr">
                                ${escapeHtml(item.leave_type?.code)}
                            </small>
                        </td>

                        <td>
                            <div>${dateText(item.start_date)}</div>
                            <small class="text-muted">
                                إلى ${dateText(item.end_date)}
                            </small>
                        </td>

                        <td class="fw-semibold">
                            ${escapeHtml(item.duration_label)}
                        </td>

                        <td>
                            ${statusBadge(item.status, item.status_label)}
                        </td>

                        <td>
                            ${dateText(item.requested_at)}
                        </td>

                        <td>
                            <div class="d-flex justify-content-center gap-1">
                                ${renderActions(item)}
                            </div>
                        </td>
                    </tr>
                `;
            });

            $('#requestsTableBody').html(html);
        }

        function renderPagination(response) {
            const current = response.current_page || 1;
            const last = response.last_page || 1;

            $('#recordsCount').text((response.total || 0) + ' طلب');

            $('#recordsSummary').text(
                response.total
                    ? 'عرض ' + response.from + ' إلى ' + response.to +
                        ' من ' + response.total
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
                        class="leave-pagination-button pagination-page
                            ${page === current ? 'active' : ''}"
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

            $('#requestsTableBody').html(`
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
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
                    leave_type_id: $('#filterLeaveType').val(),
                    date_from: $('#filterDateFrom').val(),
                    date_to: $('#filterDateTo').val(),
                    per_page: $('#filterPerPage').val()
                },
                success: function (response) {
                    renderRows(response.data || [], response.from || 1);
                    renderPagination(response);
                },
                error: function (xhr) {
                    $('#requestsTableBody').html(`
                        <tr>
                            <td colspan="8" class="text-center py-5 text-danger">
                                ${escapeHtml(
                                    xhr.responseJSON?.message ||
                                    'تعذر تحميل البيانات.'
                                )}
                            </td>
                        </tr>
                    `);
                }
            });
        }

        function resetForm() {
            $('#requestForm')[0].reset();
            $('#requestId').val('');
            $('#submit_now').val('0');
            $('#existingAttachment').addClass('d-none');
            $('#existingAttachmentLink').attr('href', '#');
            $('#requestedAmountGroup').addClass('d-none');
            $('#leaveTypeInfo').text('اختر نوع الإجازة لعرض متطلباتها.');

            clearFormErrors();
            calculateDuration();
        }

        function updateTypeFields() {
            const type = leaveTypes[$('#leave_type_id').val()];

            if (!type) {
                $('#requestedAmountGroup').addClass('d-none');
                $('#leaveTypeInfo').text('اختر نوع الإجازة لعرض متطلباتها.');
                return;
            }

            $('#requestedAmountGroup').toggleClass(
                'd-none',
                type.unit !== 'hour'
            );

            $('#start_session, #end_session')
                .prop('disabled', !type.allow_half_day || type.unit === 'hour');

            if (!type.allow_half_day || type.unit === 'hour') {
                $('#start_session, #end_session').val('full_day');
            }

            const details = [
                type.unit === 'hour' ? 'بالساعات' : 'بالأيام',
                type.requires_balance ? 'يتطلب رصيدًا' : 'بدون رصيد',
                type.requires_attachment ? 'المرفق مطلوب' : 'المرفق اختياري'
            ];

            $('#leaveTypeInfo').text(details.join(' • '));

            calculateDuration();
        }

        function parseDate(value) {
            if (!value) {
                return null;
            }

            const parts = value.split('-');

            return new Date(
                Date.UTC(
                    parseInt(parts[0]),
                    parseInt(parts[1]) - 1,
                    parseInt(parts[2])
                )
            );
        }

        function calculateDuration() {
            const type = leaveTypes[$('#leave_type_id').val()];

            if (type && type.unit === 'hour') {
                const amount = parseFloat($('#requested_amount').val() || 0);

                $('#durationPreview').text(
                    amount > 0
                        ? 'المدة المطلوبة: ' + amount + ' ساعة'
                        : 'أدخل عدد ساعات الإجازة.'
                );

                return;
            }

            const start = parseDate($('#start_date').val());
            const end = parseDate($('#end_date').val());

            if (!start || !end || end < start) {
                $('#durationPreview').text(
                    'حدد تاريخ البداية والنهاية لحساب المدة التقريبية.'
                );

                return;
            }

            let days = 0;
            let cursor = new Date(start.getTime());

            while (cursor <= end) {
                const day = cursor.getUTCDay();

                if (day !== 5 && day !== 6) {
                    days++;
                }

                cursor.setUTCDate(
                    cursor.getUTCDate() + 1
                );
            }

            if ($('#start_session').val() !== 'full_day' && days > 0) {
                days -= 0.5;
            }

            if (
                $('#end_session').val() !== 'full_day' &&
                days > 0 &&
                $('#start_date').val() !== $('#end_date').val()
            ) {
                days -= 0.5;
            }

            $('#durationPreview').text(
                'المدة التقريبية: ' + Math.max(0, days) +
                ' يوم عمل، وسيتم اعتماد الحساب النهائي من الخادم.'
            );
        }

        $('#leave_type_id').on('change', updateTypeFields);

        $('#start_date, #end_date, #start_session, #end_session, #requested_amount')
            .on('change input', calculateDuration);

        $('#btnCreateRequest').on('click', function () {
            resetForm();
            $('#requestModalTitle').text('إضافة طلب إجازة');

            loadOptions().done(function () {
                openModal('requestModal');
            });
        });

        $('#btnSearch').on('click', function () {
            loadData(1);
        });

        $('#filterSearch').on('keypress', function (event) {
            if (event.which === 13) {
                event.preventDefault();
                loadData(1);
            }
        });

        $('#filterPerPage').on('change', function () {
            loadData(1);
        });

        $('#btnReset').on('click', function () {
            $('#filterSearch').val('');
            $('#filterStatus').val('');
            $('#filterLeaveType').val('');
            $('#filterDateFrom').val('');
            $('#filterDateTo').val('');
            $('#filterPerPage').val('15');

            loadData(1);
        });

        $(document).on('click', '.pagination-page:not(:disabled)', function () {
            loadData(parseInt($(this).data('page')));
        });

        function saveRequest(submitNow) {
            clearFormErrors();

            $('#submit_now').val(submitNow ? '1' : '0');

            const id = $('#requestId').val();
            const formData = new FormData($('#requestForm')[0]);

            if (id) {
                formData.append('_method', 'PUT');
            }

            const $buttons = $('#btnSaveDraft, #btnSaveAndSubmit');

            $buttons.prop('disabled', true);

            $.ajax({
                url: id
                    ? urls.update.replace('__ID__', id)
                    : urls.store,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    closeModal('requestModal');
                    showAlert(response.message || 'تم حفظ طلب الإجازة.');
                    loadData(id ? currentPage : 1);
                },
                error: function (xhr) {
                    showFormErrors(xhr);
                },
                complete: function () {
                    $buttons.prop('disabled', false);
                }
            });
        }

        $('#btnSaveDraft').on('click', function () {
            saveRequest(false);
        });

        $('#btnSaveAndSubmit').on('click', function () {
            saveRequest(true);
        });

        $(document).on('click', '.btn-details', function () {
            const id = $(this).data('id');

            $('#requestDetails').text('جاري تحميل البيانات...');
            openModal('detailsModal');

            $.ajax({
                url: urls.show.replace('__ID__', id),
                type: 'GET',
                success: function (response) {
                    const item = response.leave_request;
                    const attachment = response.attachment_url
                        ? `<a class="btn btn-sm btn-outline-primary"
                              href="${escapeHtml(response.attachment_url)}"
                              target="_blank">عرض المرفق</a>`
                        : '<span class="text-muted">لا يوجد مرفق</span>';

                    $('#requestDetails').html(`
                        <div class="row g-4">
                            <div class="col-md-6">
                                <small class="text-muted">الموظف</small>
                                <div class="fw-bold mt-1">
                                    ${escapeHtml(employeeName(item.employee))}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <small class="text-muted">نوع الإجازة</small>
                                <div class="fw-bold mt-1">
                                    ${escapeHtml(item.leave_type?.name)}
                                </div>
                            </div>

                            <div class="col-md-4">
                                <small class="text-muted">البداية</small>
                                <div class="mt-1">${dateText(item.start_date)}</div>
                            </div>

                            <div class="col-md-4">
                                <small class="text-muted">النهاية</small>
                                <div class="mt-1">${dateText(item.end_date)}</div>
                            </div>

                            <div class="col-md-4">
                                <small class="text-muted">العودة للعمل</small>
                                <div class="mt-1">${dateText(item.return_date)}</div>
                            </div>

                            <div class="col-md-4">
                                <small class="text-muted">المدة</small>
                                <div class="fw-bold mt-1">
                                    ${escapeHtml(item.duration_label)}
                                </div>
                            </div>

                            <div class="col-md-4">
                                <small class="text-muted">الحالة</small>
                                <div class="mt-1">
                                    ${statusBadge(item.status, item.status_label)}
                                </div>
                            </div>

                            <div class="col-md-4">
                                <small class="text-muted">المرفق</small>
                                <div class="mt-1">${attachment}</div>
                            </div>

                            <div class="col-12">
                                <small class="text-muted">سبب الإجازة</small>
                                <div class="mt-1">
                                    ${escapeHtml(item.reason || '-')}
                                </div>
                            </div>

                            ${item.decision_notes ? `
                                <div class="col-12">
                                    <small class="text-muted">ملاحظات القرار</small>
                                    <div class="alert alert-light border mt-2 mb-0">
                                        ${escapeHtml(item.decision_notes)}
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                    `);
                },
                error: function (xhr) {
                    $('#requestDetails').html(`
                        <div class="alert alert-danger mb-0">
                            ${escapeHtml(
                                xhr.responseJSON?.message ||
                                'تعذر تحميل التفاصيل.'
                            )}
                        </div>
                    `);
                }
            });
        });

        $(document).on('click', '.btn-edit', function () {
            const id = $(this).data('id');

            resetForm();
            $('#requestModalTitle').text('تعديل طلب الإجازة');

            $.when(
                loadOptions(),
                $.ajax({
                    url: urls.show.replace('__ID__', id),
                    type: 'GET'
                })
            ).done(function (_, requestResponse) {
                const response = requestResponse[0] || requestResponse;
                const item = response.leave_request;

                $('#requestId').val(item.id);
                $('#employee_id').val(item.employee_id);
                $('#leave_type_id').val(item.leave_type_id);
                $('#replacement_employee_id').val(item.replacement_employee_id);
                $('#start_date').val(dateText(item.start_date));
                $('#end_date').val(dateText(item.end_date));
                $('#start_session').val(item.start_session);
                $('#end_session').val(item.end_session);
                $('#requested_amount').val(item.requested_amount);
                $('#reason').val(item.reason);
                $('#handover_notes').val(item.handover_notes);
                $('#contact_during_leave').val(item.contact_during_leave);

                if (response.attachment_url) {
                    $('#existingAttachment')
                        .removeClass('d-none');

                    $('#existingAttachmentLink')
                        .attr('href', response.attachment_url);
                }

                updateTypeFields();
                openModal('requestModal');
            }).fail(function (xhr) {
                showAlert(
                    xhr.responseJSON?.message ||
                    'تعذر تحميل طلب الإجازة.',
                    'danger'
                );
            });
        });

        $(document).on('click', '.btn-submit-request', function () {
            const id = $(this).data('id');
            const $button = $(this);

            $button.prop('disabled', true);

            $.ajax({
                url: urls.submit.replace('__ID__', id),
                type: 'POST',
                success: function (response) {
                    showAlert(response.message);
                    loadData(currentPage);
                },
                error: function (xhr) {
                    showAlert(
                        xhr.responseJSON?.message ||
                        'تعذر إرسال الطلب.',
                        'danger'
                    );
                },
                complete: function () {
                    $button.prop('disabled', false);
                }
            });
        });

        $(document).on('click', '.btn-cancel-request', function () {
            cancelRequestId = $(this).data('id');
            $('#cancelReason').val('');
            $('#cancelError').addClass('d-none').empty();
            openModal('cancelModal');
        });

        $('#btnConfirmCancel').on('click', function () {
            const reason = $.trim($('#cancelReason').val());

            if (reason.length < 3) {
                $('#cancelError')
                    .removeClass('d-none')
                    .text('أدخل سبب الإلغاء.');

                return;
            }

            const $button = $(this);
            $button.prop('disabled', true);

            $.ajax({
                url: urls.cancel.replace('__ID__', cancelRequestId),
                type: 'POST',
                data: {
                    reason: reason
                },
                success: function (response) {
                    closeModal('cancelModal');
                    showAlert(response.message);
                    loadData(currentPage);
                },
                error: function (xhr) {
                    $('#cancelError')
                        .removeClass('d-none')
                        .text(
                            xhr.responseJSON?.message ||
                            'تعذر إلغاء الطلب.'
                        );
                },
                complete: function () {
                    $button.prop('disabled', false);
                }
            });
        });

        $(document).on('click', '.btn-delete-request', function () {
            deleteRequestId = $(this).data('id');
            openModal('deleteModal');
        });

        $('#btnConfirmDelete').on('click', function () {
            const $button = $(this);
            $button.prop('disabled', true);

            $.ajax({
                url: urls.destroy.replace('__ID__', deleteRequestId),
                type: 'POST',
                data: {
                    _method: 'DELETE'
                },
                success: function (response) {
                    closeModal('deleteModal');
                    showAlert(response.message);
                    loadData(currentPage);
                },
                error: function (xhr) {
                    closeModal('deleteModal');

                    showAlert(
                        xhr.responseJSON?.message ||
                        'تعذر حذف المسودة.',
                        'danger'
                    );
                },
                complete: function () {
                    $button.prop('disabled', false);
                }
            });
        });

        loadOptions();
        loadData();
    });

})();
</script>
@endpush