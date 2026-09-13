@extends('layouts.tenant')

@section('title', 'إجازاتي')
@section('page-title', 'إجازاتي')

@section('content')

<style>
    .self-leave-card {
        border: 0;
        border-radius: 18px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .06);
    }

    .self-leave-stat-icon {
        width: 48px;
        height: 48px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 15px;
        font-size: 20px;
        font-weight: 700;
    }

    .balance-card {
        height: 100%;
        padding: 17px;
        border: 1px solid #dbeafe;
        border-radius: 16px;
        background: linear-gradient(
            135deg,
            #f8fbff,
            #eff6ff
        );
    }

    .balance-value {
        color: #1d4ed8;
        font-size: 25px;
        font-weight: 800;
    }

    .self-leave-table .table > :not(caption) > * > * {
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
        color: #475569;
        background: #f1f5f9;
    }

    .leave-status-pending {
        color: #b45309;
        background: #fef3c7;
    }

    .leave-status-approved {
        color: #15803d;
        background: #dcfce7;
    }

    .leave-status-rejected {
        color: #b91c1c;
        background: #fee2e2;
    }

    .leave-status-cancelled {
        color: #334155;
        background: #e2e8f0;
    }

    .leave-status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: currentColor;
    }

    .self-leave-empty {
        padding: 55px 20px;
        text-align: center;
        color: #64748b;
    }

    .self-leave-empty-icon {
        width: 70px;
        height: 70px;
        margin: 0 auto 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 22px;
        color: #2563eb;
        background: #eff6ff;
        font-size: 30px;
    }

    .self-leave-action {
        min-width: 36px;
        height: 34px;
        border-radius: 9px;
    }

    .leave-info-box {
        padding: 13px 15px;
        border: 1px dashed #93c5fd;
        border-radius: 13px;
        color: #1d4ed8;
        background: #eff6ff;
    }

    .leave-section {
        padding: 18px;
        margin-bottom: 18px;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
    }

    .leave-section-title {
        margin-bottom: 15px;
        color: #0f172a;
        font-size: 15px;
        font-weight: 700;
    }

    .detail-item {
        height: 100%;
        padding: 14px;
        border: 1px solid #e2e8f0;
        border-radius: 13px;
        background: #f8fafc;
    }

    .detail-label {
        margin-bottom: 5px;
        color: #64748b;
        font-size: 12px;
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

    .leave-pagination {
        min-width: 38px;
        height: 36px;
        border: 1px solid #dbe2ea;
        background: #fff;
        border-radius: 10px;
        color: #334155;
    }

    .leave-pagination.active {
        color: #fff;
        border-color: #0d6efd;
        background: #0d6efd;
    }

    .leave-pagination:disabled {
        opacity: .45;
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
        width: min(100%, 950px);
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
        color: #334155;
        background: #f1f5f9;
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

        .self-leave-table table {
            min-width: 950px;
        }
    }
</style>

<div id="pageAlert" class="alert d-none mb-4"></div>

@if(!$employee)
    <div class="alert alert-danger mb-4">
        لا يوجد ملف موظف مرتبط بحسابك. يرجى التواصل مع مسؤول الموارد البشرية.
    </div>
@endif

<div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-4">
    <div>
        <h4 class="mb-1">إجازاتي</h4>
        <p class="text-muted mb-0">
            تقديم طلب إجازة ومتابعة حالة الطلب والرصيد المتاح.
        </p>
    </div>

    <button
        type="button"
        class="btn btn-primary px-4"
        id="btnCreateLeave"
        {{ !$employee ? 'disabled' : '' }}
    >
        ＋ طلب إجازة جديد
    </button>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card self-leave-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="self-leave-stat-icon bg-primary-subtle text-primary">#</div>
                <div>
                    <div class="text-muted small">إجمالي طلباتي</div>
                    <div class="fs-4 fw-bold">{{ $summary['total'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card self-leave-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="self-leave-stat-icon bg-warning-subtle text-warning">◴</div>
                <div>
                    <div class="text-muted small">بانتظار الاعتماد</div>
                    <div class="fs-4 fw-bold">{{ $summary['pending'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card self-leave-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="self-leave-stat-icon bg-success-subtle text-success">✓</div>
                <div>
                    <div class="text-muted small">طلبات معتمدة</div>
                    <div class="fs-4 fw-bold">{{ $summary['approved'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card self-leave-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="self-leave-stat-icon bg-info-subtle text-info">☀</div>
                <div>
                    <div class="text-muted small">أيام السنة الحالية</div>
                    <div class="fs-4 fw-bold">{{ $summary['current_year_days'] }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card self-leave-card mb-4">
    <div class="card-header bg-white border-0 p-4 pb-2">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h5 class="mb-1">أرصدة الإجازات</h5>
                <small class="text-muted">
                    الأرصدة المتاحة خلال السنة الحالية.
                </small>
            </div>

            <button
                type="button"
                class="btn btn-sm btn-light border"
                id="btnRefreshBalances"
                {{ !$employee ? 'disabled' : '' }}
            >
                تحديث
            </button>
        </div>
    </div>

    <div class="card-body p-4">
        <div class="row g-3" id="balancesContainer">
            @if($employee)
                <div class="col-12 text-center text-muted py-4">
                    جاري تحميل الأرصدة...
                </div>
            @else
                <div class="col-12 text-center text-muted py-4">
                    لا يمكن تحميل الأرصدة لعدم ارتباط الحساب بموظف.
                </div>
            @endif
        </div>
    </div>
</div>

<div class="card self-leave-card self-leave-table">
    <div class="card-header bg-white border-0 p-4 pb-2">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h5 class="mb-1">سجل طلباتي</h5>
                <small class="text-muted" id="recordsSummary">
                    جاري التحميل...
                </small>
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
                    <th>نوع الإجازة</th>
                    <th>الفترة</th>
                    <th>المدة</th>
                    <th>الحالة</th>
                    <th>ملاحظات القرار</th>
                    <th class="text-center">الإجراءات</th>
                </tr>
            </thead>

            <tbody id="requestsTableBody">
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
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
<div class="ry-modal" id="leaveModal">
    <div class="ry-modal-backdrop" data-close-modal="leaveModal"></div>

    <div class="ry-modal-panel">
        <div class="ry-modal-header">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-1" id="leaveModalTitle">طلب إجازة جديد</h5>
                    <small class="text-muted">
                        سيتم إرسال الطلب إلى المسؤول المختص للاعتماد.
                    </small>
                </div>

                <button
                    type="button"
                    class="ry-modal-close"
                    data-close-modal="leaveModal"
                >×</button>
            </div>
        </div>

        <form id="leaveForm" enctype="multipart/form-data">
            @csrf

            <input type="hidden" id="leaveRequestId">
            <input type="hidden" name="submit_now" id="submit_now" value="0">

            <div class="ry-modal-body">
                <div id="formAlert" class="alert alert-danger d-none"></div>

                <div class="leave-section">
                    <div class="leave-section-title">نوع الإجازة</div>

                    <div class="row g-3">
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

                        <div class="col-12">
                            <div class="leave-info-box" id="selectedTypeInfo">
                                اختر نوع الإجازة لعرض الرصيد والمتطلبات.
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
                            <label class="form-label">فترة البداية</label>

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
                            <label class="form-label">فترة النهاية</label>

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

                        <div class="col-md-4 d-none" id="hoursGroup">
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
                            <div class="leave-info-box" id="durationPreview">
                                حدد فترة الإجازة لحساب المدة التقريبية.
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

                            <label class="form-label mt-3">
                                المستند المرفق
                            </label>

                            <input
                                type="file"
                                class="form-control"
                                name="attachment"
                                id="attachment"
                                accept=".pdf,.jpg,.jpeg,.png,.webp"
                            >

                            <small class="text-muted">
                                PDF أو صورة بحد أقصى 5MB.
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
                <div class="d-flex justify-content-end gap-2 flex-wrap">
                    <button
                        type="button"
                        class="btn btn-light border"
                        data-close-modal="leaveModal"
                    >
                        إلغاء
                    </button>

                    <button type="button" class="btn btn-outline-primary" id="btnSaveDraft">
                        حفظ كمسودة
                    </button>

                    <button type="button" class="btn btn-primary" id="btnSubmitLeave">
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

    <div class="ry-modal-panel" style="max-width: 820px;">
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

    <div class="ry-modal-panel" style="max-width: 540px;">
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
            <div id="cancelAlert" class="alert alert-danger d-none"></div>

            <label class="form-label">
                سبب الإلغاء <span class="text-danger">*</span>
            </label>

            <textarea
                class="form-control"
                id="cancelReason"
                rows="4"
                maxlength="2000"
            ></textarea>
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
                هل تريد حذف هذه المسودة؟ لا يمكن التراجع عن الحذف.
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
(function startSelfServiceLeavePage() {

    if (typeof window.jQuery === 'undefined') {
        window.setTimeout(
            startSelfServiceLeavePage,
            50
        );

        return;
    }

    window.jQuery(function ($) {
        const employeeLinked = @json((bool) $employee);

        const urls = {
            data: @json(route('app.self-service.leave.data')),
            options: @json(route('app.self-service.leave.options')),
            store: @json(route('app.self-service.leave.store')),
            show: @json(route('app.self-service.leave.show', ['leaveRequest' => '__ID__'])),
            update: @json(route('app.self-service.leave.update', ['leaveRequest' => '__ID__'])),
            destroy: @json(route('app.self-service.leave.destroy', ['leaveRequest' => '__ID__'])),
            submit: @json(route('app.self-service.leave.submit', ['leaveRequest' => '__ID__'])),
            cancel: @json(route('app.self-service.leave.cancel', ['leaveRequest' => '__ID__']))
        };

        let currentPage = 1;
        let cancelId = null;
        let deleteId = null;
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

        function dateText(value) {
            if (!value) {
                return '-';
            }

            return String(value).substring(0, 10);
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

        $(window).on('resize.selfLeaveModal', function () {
            $('.ry-modal:visible').each(function () {
                resizeModal($(this).attr('id'));
            });
        });

        $(document).on('click', '[data-close-modal]', function () {
            closeModal($(this).data('close-modal'));
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

        function statusBadge(status, label) {
            return `
                <span class="leave-status leave-status-${escapeHtml(status)}">
                    <span class="leave-status-dot"></span>
                    ${escapeHtml(label)}
                </span>
            `;
        }

        function clearErrors() {
            $('#formAlert').addClass('d-none').empty();
            $('#leaveForm .is-invalid').removeClass('is-invalid');
            $('#leaveForm .field-error').remove();
        }

        function showFormErrors(xhr) {
            clearErrors();

            const response = xhr.responseJSON || {};
            const errors = response.errors || {};

            if (Object.keys(errors).length) {
                $.each(errors, function (field, messages) {
                    const $input = $('#leaveForm [name="' + field + '"]').first();

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
                .text(response.message || 'تعذر حفظ الطلب.');
        }

        function loadOptions(force = false) {
            if (optionsLoaded && !force) {
                return $.Deferred().resolve().promise();
            }

            return $.ajax({
                url: urls.options,
                type: 'GET',
                success: function (response) {
                    let typeOptions =
                        '<option value="">اختر نوع الإجازة</option>';

                    let replacementOptions =
                        '<option value="">بدون موظف بديل</option>';

                    leaveTypes = {};

                    $('#balancesContainer').empty();

                    $.each(response.leave_types || [], function (_, type) {
                        leaveTypes[type.id] = type;

                        typeOptions += `
                            <option value="${type.id}">
                                ${escapeHtml(type.name)}
                            </option>
                        `;

                        const balanceValue =
                            type.requires_balance
                                ? escapeHtml(type.available_balance ?? 0)
                                : 'حسب الاعتماد';

                        const unit =
                            type.unit === 'hour'
                                ? 'ساعة'
                                : 'يوم';

                        $('#balancesContainer').append(`
                            <div class="col-xl-3 col-md-6">
                                <div class="balance-card">
                                    <div class="d-flex align-items-start justify-content-between gap-2">
                                        <div>
                                            <div class="fw-bold mb-1">
                                                ${escapeHtml(type.name)}
                                            </div>

                                            <small class="text-muted" dir="ltr">
                                                ${escapeHtml(type.code)}
                                            </small>
                                        </div>

                                        <span class="badge bg-white text-primary border">
                                            ${escapeHtml(type.payment_type_label)}
                                        </span>
                                    </div>

                                    <div class="mt-3">
                                        <span class="balance-value">
                                            ${balanceValue}
                                        </span>

                                        <small class="text-muted">
                                            ${type.requires_balance ? unit : ''}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        `);
                    });

                    if (!(response.leave_types || []).length) {
                        $('#balancesContainer').html(`
                            <div class="col-12 text-center text-muted py-4">
                                لا توجد أنواع إجازات متاحة.
                            </div>
                        `);
                    }

                    $.each(
                        response.replacement_employees || [],
                        function (_, employee) {
                            replacementOptions += `
                                <option value="${employee.id}">
                                    ${escapeHtml(employee.name)}
                                    -
                                    ${escapeHtml(employee.employee_number)}
                                </option>
                            `;
                        }
                    );

                    $('#leave_type_id').html(typeOptions);

                    $('#replacement_employee_id')
                        .html(replacementOptions);

                    optionsLoaded = true;
                },
                error: function (xhr) {
                    $('#balancesContainer').html(`
                        <div class="col-12">
                            <div class="alert alert-danger mb-0">
                                ${escapeHtml(
                                    xhr.responseJSON?.message ||
                                    'تعذر تحميل أرصدة الإجازات.'
                                )}
                            </div>
                        </div>
                    `);
                }
            });
        }

        function renderActions(item) {
            let html = `
                <button
                    type="button"
                    class="btn btn-sm btn-light border self-leave-action btn-details"
                    data-id="${item.id}"
                    title="عرض"
                >◉</button>
            `;

            if (item.can_edit) {
                html += `
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-primary self-leave-action btn-edit"
                        data-id="${item.id}"
                        title="تعديل"
                    >✎</button>
                `;
            }

            if (item.can_submit) {
                html += `
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-success self-leave-action btn-send"
                        data-id="${item.id}"
                        title="إرسال للاعتماد"
                    >↑</button>
                `;
            }

            if (item.can_cancel) {
                html += `
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-warning self-leave-action btn-cancel"
                        data-id="${item.id}"
                        title="إلغاء"
                    >×</button>
                `;
            }

            if (item.status === 'draft') {
                html += `
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-danger self-leave-action btn-delete"
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
                        <td colspan="7">
                            <div class="self-leave-empty">
                                <div class="self-leave-empty-icon">☷</div>
                                <h6>لا توجد طلبات إجازة</h6>
                                <p class="mb-0">
                                    يمكنك تقديم أول طلب إجازة من الزر أعلاه.
                                </p>
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
                            <div class="fw-bold">
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
                            ${item.decision_notes
                                ? escapeHtml(item.decision_notes)
                                : '<span class="text-muted">-</span>'
                            }
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
                    ? 'عرض ' + response.from + ' إلى ' +
                        response.to + ' من ' + response.total
                    : 'لا توجد طلبات'
            );

            $('#paginationInfo').text(
                'الصفحة ' + current + ' من ' + last
            );

            let html = `
                <button
                    class="leave-pagination pagination-page"
                    data-page="${current - 1}"
                    ${current <= 1 ? 'disabled' : ''}
                >‹</button>
            `;

            const start = Math.max(1, current - 2);
            const end = Math.min(last, current + 2);

            for (let page = start; page <= end; page++) {
                html += `
                    <button
                        class="leave-pagination pagination-page
                            ${page === current ? 'active' : ''}"
                        data-page="${page}"
                    >${page}</button>
                `;
            }

            html += `
                <button
                    class="leave-pagination pagination-page"
                    data-page="${current + 1}"
                    ${current >= last ? 'disabled' : ''}
                >›</button>
            `;

            $('#paginationLinks').html(html);
        }

        function loadData(page = 1) {
            if (!employeeLinked) {
                $('#requestsTableBody').html(`
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            لا يوجد ملف موظف مرتبط بالحساب.
                        </td>
                    </tr>
                `);

                return;
            }

            currentPage = page;

            $.ajax({
                url: urls.data,
                type: 'GET',
                data: {
                    page: page
                },
                success: function (response) {
                    renderRows(response.data || [], response.from || 1);
                    renderPagination(response);
                },
                error: function (xhr) {
                    $('#requestsTableBody').html(`
                        <tr>
                            <td colspan="7" class="text-center py-5 text-danger">
                                ${escapeHtml(
                                    xhr.responseJSON?.message ||
                                    'تعذر تحميل طلبات الإجازة.'
                                )}
                            </td>
                        </tr>
                    `);
                }
            });
        }

        function resetForm() {
            $('#leaveForm')[0].reset();
            $('#leaveRequestId').val('');
            $('#submit_now').val('0');
            $('#hoursGroup').addClass('d-none');
            $('#existingAttachment').addClass('d-none');
            $('#existingAttachmentLink').attr('href', '#');
            $('#selectedTypeInfo').text(
                'اختر نوع الإجازة لعرض الرصيد والمتطلبات.'
            );

            clearErrors();
            calculateDuration();
        }

        function updateTypeInformation() {
            const type =
                leaveTypes[$('#leave_type_id').val()];

            if (!type) {
                $('#hoursGroup').addClass('d-none');

                $('#selectedTypeInfo').text(
                    'اختر نوع الإجازة لعرض الرصيد والمتطلبات.'
                );

                return;
            }

            $('#hoursGroup').toggleClass(
                'd-none',
                type.unit !== 'hour'
            );

            $('#start_session, #end_session')
                .prop(
                    'disabled',
                    !type.allow_half_day ||
                    type.unit === 'hour'
                );

            if (
                !type.allow_half_day ||
                type.unit === 'hour'
            ) {
                $('#start_session, #end_session')
                    .val('full_day');
            }

            const information = [
                type.requires_balance
                    ? 'الرصيد المتاح: ' +
                        (type.available_balance ?? 0) +
                        ' ' +
                        (type.unit === 'hour' ? 'ساعة' : 'يوم')
                    : 'لا يحتاج رصيدًا',

                type.requires_attachment
                    ? 'المرفق إلزامي'
                    : 'المرفق اختياري',

                'الإشعار المسبق: ' +
                    type.minimum_notice_days +
                    ' يوم'
            ];

            $('#selectedTypeInfo').text(
                information.join(' • ')
            );

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
            const type =
                leaveTypes[$('#leave_type_id').val()];

            if (type && type.unit === 'hour') {
                const amount =
                    parseFloat(
                        $('#requested_amount').val() || 0
                    );

                $('#durationPreview').text(
                    amount > 0
                        ? 'المدة المطلوبة: ' + amount + ' ساعة'
                        : 'أدخل عدد ساعات الإجازة.'
                );

                return;
            }

            const start =
                parseDate($('#start_date').val());

            const end =
                parseDate($('#end_date').val());

            if (!start || !end || end < start) {
                $('#durationPreview').text(
                    'حدد فترة الإجازة لحساب المدة التقريبية.'
                );

                return;
            }

            let days = 0;
            let cursor =
                new Date(start.getTime());

            while (cursor <= end) {
                const day =
                    cursor.getUTCDay();

                if (day !== 5 && day !== 6) {
                    days++;
                }

                cursor.setUTCDate(
                    cursor.getUTCDate() + 1
                );
            }

            if (
                $('#start_session').val() !==
                    'full_day' &&
                days > 0
            ) {
                days -= 0.5;
            }

            if (
                $('#end_session').val() !==
                    'full_day' &&
                $('#start_date').val() !==
                    $('#end_date').val() &&
                days > 0
            ) {
                days -= 0.5;
            }

            $('#durationPreview').text(
                'المدة التقريبية: ' +
                Math.max(0, days) +
                ' يوم عمل.'
            );
        }

        $('#leave_type_id').on(
            'change',
            updateTypeInformation
        );

        $('#start_date, #end_date, #start_session, #end_session, #requested_amount')
            .on(
                'change input',
                calculateDuration
            );

        $('#btnCreateLeave').on('click', function () {
            resetForm();

            $('#leaveModalTitle').text(
                'طلب إجازة جديد'
            );

            loadOptions().done(function () {
                openModal('leaveModal');
            });
        });

        function saveLeave(submitNow) {
            clearErrors();

            $('#submit_now').val(
                submitNow ? '1' : '0'
            );

            const id =
                $('#leaveRequestId').val();

            const formData =
                new FormData(
                    $('#leaveForm')[0]
                );

            if (id) {
                formData.append(
                    '_method',
                    'PUT'
                );
            }

            const $buttons =
                $('#btnSaveDraft, #btnSubmitLeave');

            $buttons.prop('disabled', true);

            $.ajax({
                url: id
                    ? urls.update.replace(
                        '__ID__',
                        id
                    )
                    : urls.store,

                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,

                success: function (response) {
                    closeModal('leaveModal');
                    showAlert(response.message);

                    optionsLoaded = false;
                    loadOptions(true);
                    loadData(id ? currentPage : 1);
                },

                error: function (xhr) {
                    showFormErrors(xhr);
                },

                complete: function () {
                    $buttons.prop(
                        'disabled',
                        false
                    );
                }
            });
        }

        $('#btnSaveDraft').on('click', function () {
            saveLeave(false);
        });

        $('#btnSubmitLeave').on('click', function () {
            saveLeave(true);
        });

        $(document).on('click', '.btn-details', function () {
            const id =
                $(this).data('id');

            $('#requestDetails').text(
                'جاري تحميل البيانات...'
            );

            openModal('detailsModal');

            $.ajax({
                url: urls.show.replace(
                    '__ID__',
                    id
                ),

                type: 'GET',

                success: function (response) {
                    const item =
                        response.leave_request;

                    const attachment =
                        response.attachment_url
                            ? `<a
                                   href="${escapeHtml(response.attachment_url)}"
                                   target="_blank"
                                   class="btn btn-sm btn-outline-primary"
                               >عرض المرفق</a>`
                            : '<span class="text-muted">لا يوجد</span>';

                    $('#requestDetails').html(`
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="detail-item">
                                    <div class="detail-label">نوع الإجازة</div>
                                    <div class="fw-bold">
                                        ${escapeHtml(item.leave_type?.name)}
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="detail-item">
                                    <div class="detail-label">الحالة</div>
                                    <div>
                                        ${statusBadge(item.status, item.status_label)}
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="detail-item">
                                    <div class="detail-label">البداية</div>
                                    <div>${dateText(item.start_date)}</div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="detail-item">
                                    <div class="detail-label">النهاية</div>
                                    <div>${dateText(item.end_date)}</div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="detail-item">
                                    <div class="detail-label">العودة للعمل</div>
                                    <div>${dateText(item.return_date)}</div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="detail-item">
                                    <div class="detail-label">المدة</div>
                                    <div class="fw-bold">
                                        ${escapeHtml(item.duration_label)}
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="detail-item">
                                    <div class="detail-label">المرفق</div>
                                    <div>${attachment}</div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="detail-item">
                                    <div class="detail-label">سبب الإجازة</div>
                                    <div>${escapeHtml(item.reason || '-')}</div>
                                </div>
                            </div>

                            ${item.decision_notes ? `
                                <div class="col-12">
                                    <div class="alert alert-light border mb-0">
                                        <strong>ملاحظات القرار:</strong>
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
            const id =
                $(this).data('id');

            resetForm();

            $('#leaveModalTitle').text(
                'تعديل طلب الإجازة'
            );

            $.when(
                loadOptions(),

                $.ajax({
                    url: urls.show.replace(
                        '__ID__',
                        id
                    ),
                    type: 'GET'
                })
            ).done(function (_, requestResponse) {
                const response =
                    requestResponse[0] ||
                    requestResponse;

                const item =
                    response.leave_request;

                $('#leaveRequestId').val(item.id);
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
                        .attr(
                            'href',
                            response.attachment_url
                        );
                }

                updateTypeInformation();
                openModal('leaveModal');
            });
        });

        $(document).on('click', '.btn-send', function () {
            const id =
                $(this).data('id');

            const $button =
                $(this);

            $button.prop('disabled', true);

            $.ajax({
                url: urls.submit.replace(
                    '__ID__',
                    id
                ),

                type: 'POST',

                success: function (response) {
                    showAlert(response.message);
                    optionsLoaded = false;
                    loadOptions(true);
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
                    $button.prop(
                        'disabled',
                        false
                    );
                }
            });
        });

        $(document).on('click', '.btn-cancel', function () {
            cancelId =
                $(this).data('id');

            $('#cancelReason').val('');
            $('#cancelAlert').addClass('d-none');

            openModal('cancelModal');
        });

        $('#btnConfirmCancel').on('click', function () {
            const reason =
                $.trim(
                    $('#cancelReason').val()
                );

            if (reason.length < 3) {
                $('#cancelAlert')
                    .removeClass('d-none')
                    .text(
                        'سبب الإلغاء مطلوب.'
                    );

                return;
            }

            const $button =
                $(this);

            $button.prop('disabled', true);

            $.ajax({
                url: urls.cancel.replace(
                    '__ID__',
                    cancelId
                ),

                type: 'POST',

                data: {
                    reason: reason
                },

                success: function (response) {
                    closeModal('cancelModal');
                    showAlert(response.message);

                    optionsLoaded = false;
                    loadOptions(true);
                    loadData(currentPage);
                },

                error: function (xhr) {
                    $('#cancelAlert')
                        .removeClass('d-none')
                        .text(
                            xhr.responseJSON?.message ||
                            'تعذر إلغاء الطلب.'
                        );
                },

                complete: function () {
                    $button.prop(
                        'disabled',
                        false
                    );
                }
            });
        });

        $(document).on('click', '.btn-delete', function () {
            deleteId =
                $(this).data('id');

            openModal('deleteModal');
        });

        $('#btnConfirmDelete').on('click', function () {
            const $button =
                $(this);

            $button.prop('disabled', true);

            $.ajax({
                url: urls.destroy.replace(
                    '__ID__',
                    deleteId
                ),

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
                    $button.prop(
                        'disabled',
                        false
                    );
                }
            });
        });

        $(document).on(
            'click',
            '.pagination-page:not(:disabled)',
            function () {
                loadData(
                    parseInt(
                        $(this).data('page')
                    )
                );
            }
        );

        $('#btnRefreshBalances').on('click', function () {
            optionsLoaded = false;
            loadOptions(true);
        });

        if (employeeLinked) {
            loadOptions();
            loadData();
        }
    });

})();
</script>
@endpush