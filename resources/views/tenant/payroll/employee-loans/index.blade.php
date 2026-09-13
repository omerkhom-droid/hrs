@extends('layouts.tenant')

@section('title', 'إدارة السلف')
@section('page-title', 'إدارة السلف')

@section('content')

<style>
    .loan-page { direction: rtl; }
    .loan-table th, .loan-table td { white-space: nowrap; vertical-align: middle; }
    .loan-empty, .loan-loading { padding: 55px 20px !important; text-align: center; }
    .loan-empty { color: #64748b; }
    .loan-loading { color: #2563eb; }
    .loan-alert { display: none; }
    .loan-pagination { display: flex; align-items: center; justify-content: space-between; gap: 15px; flex-wrap: wrap; }
    .loan-page-buttons { display: flex; gap: 5px; direction: ltr; }
    .loan-actions { display: flex; gap: 5px; flex-wrap: wrap; }
    .loan-money { direction: ltr; display: inline-block; font-weight: 700; }
    .loan-summary-card { border: 1px solid #e2e8f0; border-radius: 14px; padding: 15px; height: 100%; background: #f8fafc; }
    .loan-summary-card small { color: #64748b; display: block; margin-bottom: 7px; }
    .loan-summary-card strong { font-size: 1.05rem; }
    body.ry-modal-open { overflow: hidden !important; }
    .ry-modal { position: fixed; inset: 0; z-index: 99999; display: none; align-items: center; justify-content: center; padding: 24px; overflow-y: auto; background: rgba(15, 23, 42, .68); }
    .ry-modal-panel { width: min(980px, 100%); max-height: calc(100vh - 48px); display: flex; flex-direction: column; overflow: hidden; background: #fff; border-radius: 20px; box-shadow: 0 25px 70px rgba(15, 23, 42, .28); }
    .ry-modal-panel.ry-modal-small { width: min(520px, 100%); }
    .ry-modal-header, .ry-modal-footer { flex: 0 0 auto; padding: 18px 22px; background: #fff; }
    .ry-modal-header { border-bottom: 1px solid #e9ecef; }
    .ry-modal-footer { border-top: 1px solid #e9ecef; }
    .ry-modal-body { flex: 1 1 auto; min-height: 0; padding: 22px; overflow: auto; overscroll-behavior: contain; }
    .ry-modal-close { width: 40px; height: 40px; border: 0; border-radius: 12px; background: #f1f5f9; color: #334155; font-size: 24px; line-height: 1; }
    .installments-table th, .installments-table td { white-space: nowrap; vertical-align: middle; }
    @media (max-width: 767.98px) {
        .ry-modal { padding: 8px; align-items: stretch; }
        .ry-modal-panel { max-height: calc(100vh - 16px); border-radius: 14px; }
        .ry-modal-header, .ry-modal-footer, .ry-modal-body { padding: 15px; }
    }
</style>

<div class="loan-page">
    <div id="pageAlert" class="alert loan-alert mb-4" role="alert"></div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                <div>
                    <h5 class="mb-2">إدارة السلف</h5>
                    <p class="text-muted mb-0">إنشاء ومتابعة واعتماد سلف الموظفين وجدولة أقساطها.</p>
                </div>
                @can('loans.manage')
                    <button type="button" class="btn btn-primary px-4" id="btnCreateLoan">+ إضافة سلفة</button>
                @endcan
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <form id="filterForm">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-4 col-md-6">
                        <label class="form-label">البحث</label>
                        <input type="text" class="form-control" id="filterSearch" placeholder="رقم الطلب أو اسم الموظف">
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">الموظف</label>
                        <select class="form-select" id="filterEmployee"><option value="">جميع الموظفين</option></select>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label">الحالة</label>
                        <select class="form-select" id="filterStatus">
                            <option value="">جميع الحالات</option>
                            <option value="draft">مسودة</option>
                            <option value="submitted">بانتظار الاعتماد</option>
                            <option value="approved">معتمدة</option>
                            <option value="active">قيد السداد</option>
                            <option value="completed">مكتملة</option>
                            <option value="rejected">مرفوضة</option>
                            <option value="cancelled">ملغاة</option>
                        </select>
                    </div>
                    <div class="col-lg-1 col-md-3">
                        <label class="form-label">العدد</label>
                        <select class="form-select" id="filterPerPage">
                            <option value="10">10</option><option value="20" selected>20</option><option value="50">50</option><option value="100">100</option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-5">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">بحث</button>
                            <button type="button" class="btn btn-light border" id="btnResetFilters">إعادة</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 p-4">
            <div class="d-flex align-items-center justify-content-between gap-3">
                <div><h6 class="mb-1">قائمة السلف</h6><small class="text-muted" id="recordsSummary">جاري التحميل...</small></div>
                <span class="badge bg-primary-subtle text-primary px-3 py-2" id="recordsCount">0 سلفة</span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover loan-table mb-0">
                <thead class="table-light">
                    <tr><th>#</th><th>رقم الطلب</th><th>الموظف</th><th>نوع السلفة</th><th>المطلوب</th><th>المعتمد</th><th>المتبقي</th><th>الأقساط</th><th>الحالة</th><th>الإجراءات</th></tr>
                </thead>
                <tbody id="loansTableBody"><tr><td colspan="10" class="loan-loading">جاري تحميل البيانات...</td></tr></tbody>
            </table>
        </div>
        <div class="card-footer bg-white border-0 p-4">
            <div class="loan-pagination"><div class="text-muted small" id="paginationSummary"></div><div class="loan-page-buttons" id="paginationButtons"></div></div>
        </div>
    </div>
</div>

<div class="ry-modal" id="loanModal" aria-hidden="true">
    <div class="ry-modal-panel" role="dialog" aria-modal="true">
        <div class="ry-modal-header">
            <div class="d-flex align-items-center justify-content-between gap-3">
                <div><h5 class="mb-1" id="loanModalTitle">إضافة سلفة</h5><small class="text-muted">احفظ الطلب كمسودة ثم أرسله للاعتماد.</small></div>
                <button type="button" class="ry-modal-close btn-close-loan">×</button>
            </div>
        </div>
        <form id="loanForm">
            <div class="ry-modal-body">
                <div id="formAlert" class="alert alert-danger d-none"></div>
                <input type="hidden" id="loanUuid">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">الموظف <span class="text-danger">*</span></label>
                        <select class="form-select" id="loanEmployee" required><option value="">اختر الموظف</option></select>
                        <div class="invalid-feedback" data-error-for="employee_id"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">نوع السلفة <span class="text-danger">*</span></label>
                        <select class="form-select" id="loanType" required><option value="">اختر النوع</option></select>
                        <div class="invalid-feedback" data-error-for="loan_type"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المبلغ المطلوب <span class="text-danger">*</span></label>
                        <div class="input-group"><input type="number" class="form-control" id="requestedAmount" min="1" step="0.01" required><span class="input-group-text">ر.س</span></div>
                        <div class="invalid-feedback" data-error-for="requested_amount"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">عدد الأقساط <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="installmentsCount" min="1" max="60" value="1" required>
                        <div class="invalid-feedback" data-error-for="installments_count"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ أول قسط</label>
                        <input type="date" class="form-control" id="firstInstallmentDate">
                        <div class="invalid-feedback" data-error-for="first_installment_date"></div>
                    </div>
                    <div class="col-12">
                        <div class="alert alert-primary py-2 mb-0" id="installmentPreview">القسط التقديري: 0.00 ر.س</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">سبب السلفة <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="loanReason" rows="3" maxlength="2000" required></textarea>
                        <div class="invalid-feedback" data-error-for="reason"></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظات الموظف</label>
                        <textarea class="form-control" id="employeeNotes" rows="2" maxlength="3000"></textarea>
                        <div class="invalid-feedback" data-error-for="employee_notes"></div>
                    </div>
                </div>
            </div>
            <div class="ry-modal-footer">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light border btn-close-loan">إلغاء</button>
                    <button type="submit" class="btn btn-primary px-4" id="btnSaveLoan">حفظ المسودة</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="ry-modal" id="detailsModal" aria-hidden="true">
    <div class="ry-modal-panel" role="dialog" aria-modal="true">
        <div class="ry-modal-header">
            <div class="d-flex align-items-center justify-content-between gap-3">
                <div><h5 class="mb-1">تفاصيل السلفة</h5><small class="text-muted" id="detailsNumber"></small></div>
                <button type="button" class="ry-modal-close btn-close-details">×</button>
            </div>
        </div>
        <div class="ry-modal-body" id="detailsBody"></div>
        <div class="ry-modal-footer"><div class="d-flex justify-content-end"><button type="button" class="btn btn-light border btn-close-details">إغلاق</button></div></div>
    </div>
</div>

<div class="ry-modal" id="confirmModal" aria-hidden="true">
    <div class="ry-modal-panel ry-modal-small" role="dialog" aria-modal="true">
        <div class="ry-modal-header"><div class="d-flex justify-content-between align-items-center"><h5 class="mb-0" id="confirmTitle">تأكيد</h5><button type="button" class="ry-modal-close btn-close-confirm">×</button></div></div>
        <div class="ry-modal-body"><p class="mb-0" id="confirmMessage"></p></div>
        <div class="ry-modal-footer"><div class="d-flex justify-content-end gap-2"><button type="button" class="btn btn-light border btn-close-confirm">إلغاء</button><button type="button" class="btn btn-danger" id="btnConfirmAction">تأكيد</button></div></div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const urls = {
        data: @json(route('app.payroll.employee-loans.data')),
        options: @json(route('app.payroll.employee-loans.options')),
        store: @json(route('app.payroll.employee-loans.store')),
        show: @json(route('app.payroll.employee-loans.show', '__UUID__')),
        update: @json(route('app.payroll.employee-loans.update', '__UUID__')),
        destroy: @json(route('app.payroll.employee-loans.destroy', '__UUID__')),
        submit: @json(route('app.payroll.employee-loans.submit', '__UUID__')),
        cancel: @json(route('app.payroll.employee-loans.cancel', '__UUID__'))
    };
    const permissions = {
        manage: @json(auth()->user()->can('loans.manage')),
        approve: @json(auth()->user()->can('loans.approve'))
    };
    const state = { page: 1, options: null, pendingAction: null };

    function csrfToken() { return $('meta[name="csrf-token"]').attr('content'); }
    function routeUrl(template, uuid) { return template.replace('__UUID__', encodeURIComponent(uuid)); }
    function escapeHtml(value) { return $('<div>').text(value ?? '').html(); }
    function money(value) { return Number(value ?? 0).toLocaleString('ar-SA', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function openModal($modal) { $modal.css('display', 'flex').attr('aria-hidden', 'false'); $('body').addClass('ry-modal-open'); }
    function closeModal($modal) { $modal.hide().attr('aria-hidden', 'true'); if (!$('.ry-modal:visible').length) $('body').removeClass('ry-modal-open'); }
    function showPageAlert(message, type = 'success') { $('#pageAlert').removeClass('alert-success alert-danger alert-warning alert-info').addClass('alert-' + type).text(message).stop(true, true).fadeIn(); window.scrollTo({ top: 0, behavior: 'smooth' }); }
    function statusBadge(item) {
        const colors = { draft: 'secondary', submitted: 'warning', approved: 'primary', active: 'info', completed: 'success', rejected: 'danger', cancelled: 'dark' };
        return '<span class="badge bg-' + (colors[item.status] ?? 'secondary') + '-subtle text-' + (colors[item.status] ?? 'secondary') + '">' + escapeHtml(item.status_label) + '</span>';
    }
    function clearErrors() { $('#formAlert').addClass('d-none').empty(); $('#loanForm .is-invalid').removeClass('is-invalid'); $('[data-error-for]').empty(); }
    function ajaxError(xhr, form = false) {
        const response = xhr.responseJSON ?? {};
        const message = response.message ?? 'تعذر تنفيذ العملية.';
        if (form && response.errors) {
            Object.entries(response.errors).forEach(([field, messages]) => {
                const $error = $('[data-error-for="' + field + '"]');
                $error.text(Array.isArray(messages) ? messages[0] : messages);
                $error.closest('.col-12, .col-md-4, .col-md-6').find('input,select,textarea').first().addClass('is-invalid');
            });
            $('#formAlert').removeClass('d-none').text(message);
        } else showPageAlert(message, 'danger');
    }

    function loadOptions() {
        return $.getJSON(urls.options).done(function (response) {
            state.options = response.data ?? response;
            const employees = state.options.employees ?? [];
            const types = state.options.loan_types ?? [];
            $('#filterEmployee').html('<option value="">جميع الموظفين</option>');
            $('#loanEmployee').html('<option value="">اختر الموظف</option>');
            employees.forEach(function (employee) {
                const label = escapeHtml(employee.employee_number ? employee.employee_number + ' - ' + employee.name : employee.name);
                $('#filterEmployee').append('<option value="' + employee.id + '">' + label + '</option>');
                $('#loanEmployee').append('<option value="' + employee.id + '">' + label + '</option>');
            });
            $('#loanType').html('<option value="">اختر النوع</option>');
            types.forEach(type => $('#loanType').append('<option value="' + escapeHtml(type.value) + '">' + escapeHtml(type.label) + '</option>'));
        }).fail(xhr => ajaxError(xhr));
    }

    function loadData(page = 1) {
        state.page = page;
        $('#loansTableBody').html('<tr><td colspan="10" class="loan-loading">جاري تحميل البيانات...</td></tr>');
        $.getJSON(urls.data, {
            page: page,
            search: $('#filterSearch').val(),
            employee_id: $('#filterEmployee').val(),
            status: $('#filterStatus').val(),
            per_page: $('#filterPerPage').val()
        }).done(function (response) {
            renderRows(response.data ?? []);
            renderPagination(response.meta ?? {});
        }).fail(function (xhr) {
            $('#loansTableBody').html('<tr><td colspan="10" class="loan-empty text-danger">تعذر تحميل البيانات.</td></tr>');
            ajaxError(xhr);
        });
    }

    function renderRows(items) {
        if (!items.length) {
            $('#loansTableBody').html('<tr><td colspan="10" class="loan-empty">لا توجد سلف مطابقة.</td></tr>');
            $('#recordsCount').text('0 سلفة');
            return;
        }
        const start = (state.page - 1) * Number($('#filterPerPage').val());
        const rows = items.map(function (item, index) {
            let actions = '<button class="btn btn-sm btn-outline-primary btn-view" data-uuid="' + item.uuid + '">عرض</button>';
            if (permissions.manage && item.can_edit) actions += '<button class="btn btn-sm btn-outline-secondary btn-edit" data-uuid="' + item.uuid + '">تعديل</button>';
            if (permissions.manage && item.can_submit) actions += '<button class="btn btn-sm btn-success btn-submit-loan" data-uuid="' + item.uuid + '" data-number="' + escapeHtml(item.request_number) + '">إرسال</button>';
            if (permissions.manage && item.can_cancel) actions += '<button class="btn btn-sm btn-outline-danger btn-cancel-loan" data-uuid="' + item.uuid + '" data-number="' + escapeHtml(item.request_number) + '">إلغاء</button>';
            if (permissions.manage && item.status === 'draft') actions += '<button class="btn btn-sm btn-danger btn-delete-loan" data-uuid="' + item.uuid + '" data-number="' + escapeHtml(item.request_number) + '">حذف</button>';
            return '<tr>' +
                '<td>' + (start + index + 1) + '</td>' +
                '<td><span dir="ltr">' + escapeHtml(item.request_number) + '</span></td>' +
                '<td><strong>' + escapeHtml(item.employee?.name) + '</strong><small class="d-block text-muted">' + escapeHtml(item.employee?.employee_number) + '</small></td>' +
                '<td>' + escapeHtml(item.loan_type_label) + '</td>' +
                '<td><span class="loan-money">' + money(item.requested_amount) + '</span></td>' +
                '<td><span class="loan-money">' + (item.approved_amount == null ? '—' : money(item.approved_amount)) + '</span></td>' +
                '<td><span class="loan-money">' + money(item.remaining_amount) + '</span></td>' +
                '<td>' + escapeHtml(item.installments_count) + '</td>' +
                '<td>' + statusBadge(item) + '</td>' +
                '<td><div class="loan-actions">' + actions + '</div></td></tr>';
        });
        $('#loansTableBody').html(rows.join(''));
    }

    function renderPagination(meta) {
        const total = Number(meta.total ?? 0), current = Number(meta.current_page ?? 1), last = Number(meta.last_page ?? 1), perPage = Number(meta.per_page ?? 20);
        $('#recordsCount').text(total + ' سلفة');
        $('#recordsSummary').text(total ? 'عرض ' + (((current - 1) * perPage) + 1) + ' إلى ' + Math.min(current * perPage, total) + ' من ' + total : 'لا توجد سجلات');
        $('#paginationSummary').text('الصفحة ' + current + ' من ' + last);
        let buttons = '<button class="btn btn-sm btn-light border page-button" data-page="' + (current - 1) + '" ' + (current <= 1 ? 'disabled' : '') + '>السابق</button>';
        const from = Math.max(1, current - 2), to = Math.min(last, current + 2);
        for (let page = from; page <= to; page++) buttons += '<button class="btn btn-sm ' + (page === current ? 'btn-primary' : 'btn-light border') + ' page-button" data-page="' + page + '">' + page + '</button>';
        buttons += '<button class="btn btn-sm btn-light border page-button" data-page="' + (current + 1) + '" ' + (current >= last ? 'disabled' : '') + '>التالي</button>';
        $('#paginationButtons').html(buttons);
    }

    function resetForm() {
        $('#loanForm')[0].reset(); $('#loanUuid').val(''); $('#installmentsCount').val(1); clearErrors(); updateInstallmentPreview();
        $('#loanModalTitle').text('إضافة سلفة'); $('#btnSaveLoan').text('حفظ المسودة'); $('#loanEmployee').prop('disabled', false);
    }
    function updateInstallmentPreview() {
        const amount = Number($('#requestedAmount').val() || 0), count = Math.max(1, Number($('#installmentsCount').val() || 1));
        $('#installmentPreview').text('القسط التقديري: ' + money(amount / count) + ' ر.س');
    }
    function formPayload() {
        return {
            _token: csrfToken(), employee_id: $('#loanEmployee').val(), loan_type: $('#loanType').val(),
            requested_amount: $('#requestedAmount').val(), installments_count: $('#installmentsCount').val(),
            first_installment_date: $('#firstInstallmentDate').val() || null,
            reason: $('#loanReason').val(), employee_notes: $('#employeeNotes').val() || null
        };
    }

    function openConfirm(title, message, buttonText, buttonClass, callback) {
        state.pendingAction = callback; $('#confirmTitle').text(title); $('#confirmMessage').text(message);
        $('#btnConfirmAction').removeClass('btn-danger btn-success btn-primary btn-warning').addClass(buttonClass).text(buttonText);
        openModal($('#confirmModal'));
    }

    function detailsHtml(item) {
        let installments = '<div class="text-muted py-3">لم يتم إنشاء الأقساط بعد.</div>';
        if (item.installments?.length) {
            installments = '<div class="table-responsive"><table class="table installments-table"><thead class="table-light"><tr><th>القسط</th><th>تاريخ الاستحقاق</th><th>المبلغ</th><th>المسدد</th><th>المتبقي</th><th>الحالة</th></tr></thead><tbody>' +
                item.installments.map(row => '<tr><td>' + row.installment_number + '</td><td>' + escapeHtml(row.due_date) + '</td><td>' + money(row.amount) + '</td><td>' + money(row.paid_amount) + '</td><td>' + money(row.remaining_amount) + '</td><td>' + escapeHtml(row.status_label) + '</td></tr>').join('') + '</tbody></table></div>';
        }
        return '<div class="row g-3 mb-4">' +
            '<div class="col-md-4"><div class="loan-summary-card"><small>الموظف</small><strong>' + escapeHtml(item.employee?.name) + '</strong></div></div>' +
            '<div class="col-md-4"><div class="loan-summary-card"><small>نوع السلفة</small><strong>' + escapeHtml(item.loan_type_label) + '</strong></div></div>' +
            '<div class="col-md-4"><div class="loan-summary-card"><small>الحالة</small><strong>' + escapeHtml(item.status_label) + '</strong></div></div>' +
            '<div class="col-md-3"><div class="loan-summary-card"><small>المبلغ المطلوب</small><strong>' + money(item.requested_amount) + ' ر.س</strong></div></div>' +
            '<div class="col-md-3"><div class="loan-summary-card"><small>المبلغ المعتمد</small><strong>' + (item.approved_amount == null ? '—' : money(item.approved_amount) + ' ر.س') + '</strong></div></div>' +
            '<div class="col-md-3"><div class="loan-summary-card"><small>المسدد</small><strong>' + money(item.paid_amount) + ' ر.س</strong></div></div>' +
            '<div class="col-md-3"><div class="loan-summary-card"><small>المتبقي</small><strong>' + money(item.remaining_amount) + ' ر.س</strong></div></div></div>' +
            '<div class="mb-3"><h6>سبب السلفة</h6><div class="p-3 bg-light rounded-3">' + escapeHtml(item.reason) + '</div></div>' +
            (item.approval_notes ? '<div class="mb-3"><h6>ملاحظات الاعتماد</h6><div class="p-3 bg-light rounded-3">' + escapeHtml(item.approval_notes) + '</div></div>' : '') +
            (item.rejection_reason ? '<div class="alert alert-danger">سبب الرفض: ' + escapeHtml(item.rejection_reason) + '</div>' : '') +
            (item.cancellation_reason ? '<div class="alert alert-secondary">سبب الإلغاء: ' + escapeHtml(item.cancellation_reason) + '</div>' : '') +
            '<h6 class="mt-4 mb-3">جدول الأقساط</h6>' + installments;
    }

    $(function () {
        loadOptions(); loadData(1);
        $('#filterForm').on('submit', function (e) { e.preventDefault(); loadData(1); });
        $('#btnResetFilters').on('click', function () { $('#filterForm')[0].reset(); $('#filterPerPage').val('20'); loadData(1); });
        $(document).on('click', '.page-button:not(:disabled)', function () { loadData(Number($(this).data('page'))); });
        $('#btnCreateLoan').on('click', function () { resetForm(); openModal($('#loanModal')); });
        $('#requestedAmount, #installmentsCount').on('input', updateInstallmentPreview);
        $('.btn-close-loan').on('click', () => closeModal($('#loanModal')));
        $('.btn-close-details').on('click', () => closeModal($('#detailsModal')));
        $('.btn-close-confirm').on('click', function () { state.pendingAction = null; closeModal($('#confirmModal')); });
        $('.ry-modal').on('click', function (e) { if (e.target === this) closeModal($(this)); });

        $('#loanForm').on('submit', function (e) {
            e.preventDefault(); clearErrors();
            const uuid = $('#loanUuid').val(), isEdit = uuid !== '', payload = formPayload();
            if (isEdit) payload._method = 'PUT';
            const $button = $('#btnSaveLoan').prop('disabled', true).text('جاري الحفظ...');
            $.ajax({ url: isEdit ? routeUrl(urls.update, uuid) : urls.store, method: 'POST', data: payload })
                .done(function (response) { closeModal($('#loanModal')); showPageAlert(response.message ?? 'تم حفظ السلفة بنجاح.'); loadData(isEdit ? state.page : 1); })
                .fail(xhr => ajaxError(xhr, true))
                .always(() => $button.prop('disabled', false).text(isEdit ? 'حفظ التعديلات' : 'حفظ المسودة'));
        });

        $(document).on('click', '.btn-edit', function () {
            const uuid = $(this).data('uuid'); resetForm(); $('#loanModalTitle').text('تعديل مسودة السلفة'); $('#btnSaveLoan').text('حفظ التعديلات');
            $.getJSON(routeUrl(urls.show, uuid)).done(function (response) {
                const item = response.data ?? response; $('#loanUuid').val(item.uuid); $('#loanEmployee').val(item.employee?.id).prop('disabled', false);
                $('#loanType').val(item.loan_type); $('#requestedAmount').val(item.requested_amount); $('#installmentsCount').val(item.installments_count);
                $('#firstInstallmentDate').val(item.first_installment_date ?? ''); $('#loanReason').val(item.reason); $('#employeeNotes').val(item.employee_notes ?? '');
                updateInstallmentPreview(); openModal($('#loanModal'));
            }).fail(xhr => ajaxError(xhr));
        });

        $(document).on('click', '.btn-view', function () {
            $.getJSON(routeUrl(urls.show, $(this).data('uuid'))).done(function (response) {
                const item = response.data ?? response; $('#detailsNumber').text(item.request_number); $('#detailsBody').html(detailsHtml(item)); openModal($('#detailsModal'));
            }).fail(xhr => ajaxError(xhr));
        });

        $(document).on('click', '.btn-submit-loan', function () {
            const uuid = $(this).data('uuid'), number = $(this).data('number');
            openConfirm('إرسال السلفة', 'هل تريد إرسال الطلب "' + number + '" للاعتماد؟ لن يمكن تعديله بعد الإرسال.', 'إرسال', 'btn-success', () =>
                $.post(routeUrl(urls.submit, uuid), { _token: csrfToken() }).done(function (response) { closeModal($('#confirmModal')); showPageAlert(response.message); loadData(state.page); }).fail(xhr => ajaxError(xhr))
            );
        });

        $(document).on('click', '.btn-cancel-loan', function () {
            const uuid = $(this).data('uuid'), number = $(this).data('number');
            openConfirm('إلغاء السلفة', 'هل تريد إلغاء السلفة "' + number + '"؟', 'إلغاء السلفة', 'btn-danger', () =>
                $.post(routeUrl(urls.cancel, uuid), { _token: csrfToken(), cancellation_reason: 'ألغيت من إدارة السلف' }).done(function (response) { closeModal($('#confirmModal')); showPageAlert(response.message); loadData(state.page); }).fail(xhr => ajaxError(xhr))
            );
        });

        $(document).on('click', '.btn-delete-loan', function () {
            const uuid = $(this).data('uuid'), number = $(this).data('number');
            openConfirm('حذف المسودة', 'هل تريد حذف مسودة السلفة "' + number + '" نهائيًا؟', 'حذف', 'btn-danger', () =>
                $.ajax({ url: routeUrl(urls.destroy, uuid), method: 'POST', data: { _token: csrfToken(), _method: 'DELETE' } }).done(function (response) { closeModal($('#confirmModal')); showPageAlert(response.message); loadData(state.page); }).fail(xhr => ajaxError(xhr))
            );
        });

        $('#btnConfirmAction').on('click', function () {
            if (typeof state.pendingAction !== 'function') return;
            const callback = state.pendingAction; state.pendingAction = null;
            const $button = $(this), original = $button.text(); $button.prop('disabled', true).text('جاري التنفيذ...');
            const request = callback(); if (request?.always) request.always(() => $button.prop('disabled', false).text(original)); else $button.prop('disabled', false).text(original);
        });
    });
})();
</script>
@endpush
