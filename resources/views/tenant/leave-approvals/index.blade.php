@extends('layouts.tenant')

@section('title', 'اعتماد الإجازات')
@section('page-title', 'اعتماد الإجازات')

@section('content')

<style>
    .leave-approval-page .approval-card {
        border: 1px solid #e5eaf1;
        border-radius: 18px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .05);
    }

    .leave-approval-page .stat-icon {
        display: flex;
        width: 48px;
        height: 48px;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        font-size: 20px;
        font-weight: 800;
    }

    .leave-approval-page .employee-name {
        color: #101828;
        font-weight: 700;
    }

    .leave-approval-page .request-reason {
        max-width: 260px;
        overflow: hidden;
        color: #667085;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .leave-approval-page .approval-level {
        display: inline-flex;
        padding: 5px 10px;
        border-radius: 30px;
        color: #b54708;
        background: #fffaeb;
        font-size: 12px;
        font-weight: 700;
    }

    .leave-approval-page .empty-state {
        padding: 55px 20px;
        color: #667085;
        text-align: center;
    }

    .leave-approval-page .table > :not(caption) > * > * {
        padding: 14px 12px;
        vertical-align: middle;
    }

    .approval-pagination {
        min-width: 37px;
        height: 36px;
        border: 1px solid #d9e0e9;
        border-radius: 9px;
        color: #344054;
        background: #fff;
    }

    .approval-pagination.active {
        border-color: #0d6efd;
        color: #fff;
        background: #0d6efd;
    }

    .approval-pagination:disabled {
        opacity: .45;
    }

    .ry-modal {
        position: fixed;
        inset: 0;
        z-index: 3000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 18px;
        background: rgba(15, 23, 42, .65);
    }

    .ry-modal-panel {
        display: flex;
        width: min(720px, 100%);
        max-height: calc(100vh - 36px);
        flex-direction: column;
        overflow: hidden;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 25px 80px rgba(15, 23, 42, .3);
    }

    .ry-modal-header,
    .ry-modal-footer {
        flex: 0 0 auto;
        padding: 18px 20px;
        background: #fff;
    }

    .ry-modal-header {
        border-bottom: 1px solid #e5eaf1;
    }

    .ry-modal-footer {
        border-top: 1px solid #e5eaf1;
    }

    .ry-modal-body {
        flex: 1 1 auto;
        min-height: 0;
        padding: 20px;
        overflow-x: hidden;
        overflow-y: auto !important;
        overscroll-behavior: contain;
        scrollbar-gutter: stable;
        -webkit-overflow-scrolling: touch;
    }

    .ry-modal-close {
        width: 38px;
        height: 38px;
        border: 0;
        border-radius: 10px;
        color: #344054;
        background: #f2f4f7;
        font-size: 22px;
    }

    body.ry-modal-open {
        overflow: hidden !important;
    }

    .detail-box {
        height: 100%;
        padding: 13px;
        border: 1px solid #e5eaf1;
        border-radius: 12px;
        background: #f8fafc;
    }

    .detail-label {
        margin-bottom: 5px;
        color: #667085;
        font-size: 12px;
    }

    @media (max-width: 767.98px) {
        .ry-modal {
            align-items: flex-end;
            padding: 0;
        }

        .ry-modal-panel {
            max-height: 95vh;
            border-radius: 18px 18px 0 0;
        }

        .leave-approval-page table {
            min-width: 1100px;
        }
    }
</style>

<div class="leave-approval-page">

    <div id="pageAlert" class="alert d-none mb-4"></div>

    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-4">
        <div>
            <h4 class="mb-1">اعتماد طلبات الإجازة</h4>
            <p class="text-muted mb-0">
                مراجعة طلبات الموظفين المعلقة واتخاذ قرار الاعتماد أو الرفض.
            </p>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <button type="button" id="bulkApproveButton" class="btn btn-success" disabled>
                اعتماد المحدد
            </button>

            <button type="button" id="bulkRejectButton" class="btn btn-outline-danger" disabled>
                رفض المحدد
            </button>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card approval-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon bg-warning-subtle text-warning">◴</div>
                    <div>
                        <div class="text-muted small">بانتظار الاعتماد</div>
                        <div class="fs-4 fw-bold">{{ $summary['pending'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card approval-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon bg-primary-subtle text-primary">＋</div>
                    <div>
                        <div class="text-muted small">طلبات اليوم</div>
                        <div class="fs-4 fw-bold">{{ $summary['requested_today'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card approval-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon bg-success-subtle text-success">✓</div>
                    <div>
                        <div class="text-muted small">معتمد هذا الشهر</div>
                        <div class="fs-4 fw-bold">{{ $summary['approved_this_month'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card approval-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon bg-danger-subtle text-danger">×</div>
                    <div>
                        <div class="text-muted small">مرفوض هذا الشهر</div>
                        <div class="fs-4 fw-bold">{{ $summary['rejected_this_month'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card approval-card mb-4">
        <div class="card-body p-4">
            <form id="filtersForm">
                <div class="row g-3">
                    <div class="col-lg-7 col-md-6">
                        <label class="form-label">البحث</label>
                        <input
                            type="search"
                            id="filterSearch"
                            class="form-control"
                            placeholder="اسم الموظف أو الرقم الوظيفي أو نوع الإجازة"
                        >
                    </div>

                    <div class="col-lg-2 col-md-3">
                        <label class="form-label">عدد السجلات</label>
                        <select id="filterPerPage" class="form-select">
                            <option value="10">10</option>
                            <option value="15" selected>15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">بحث</button>
                        <button type="button" id="resetFilters" class="btn btn-light border">إعادة</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card approval-card overflow-hidden">
        <div class="card-header bg-white border-0 p-4 pb-2">
            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                <div>
                    <h5 class="mb-1">الطلبات المعلقة</h5>
                    <small id="recordsSummary" class="text-muted">جاري التحميل...</small>
                </div>

                <span id="selectedCount" class="badge bg-primary-subtle text-primary px-3 py-2">
                    0 محدد
                </span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:45px;">
                            <input type="checkbox" id="selectAll" class="form-check-input">
                        </th>
                        <th>#</th>
                        <th>الموظف</th>
                        <th>نوع الإجازة</th>
                        <th>الفترة</th>
                        <th>المدة</th>
                        <th>السبب</th>
                        <th>مرحلة الاعتماد</th>
                        <th class="text-center">الإجراءات</th>
                    </tr>
                </thead>

                <tbody id="approvalTableBody">
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            جاري تحميل الطلبات...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="card-footer bg-white border-0 p-4">
            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                <small id="paginationInfo" class="text-muted"></small>
                <div id="paginationLinks" class="d-flex gap-2"></div>
            </div>
        </div>
    </div>
</div>

{{-- Details modal --}}
<div id="detailsModal" class="ry-modal" aria-hidden="true">
    <div class="ry-modal-panel">
        <div class="ry-modal-header">
            <div class="d-flex align-items-center justify-content-between gap-3">
                <h5 class="mb-0">تفاصيل طلب الإجازة</h5>
                <button type="button" class="ry-modal-close close-modal" data-modal="detailsModal">×</button>
            </div>
        </div>

        <div id="requestDetails" class="ry-modal-body"></div>

        <div class="ry-modal-footer">
            <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-light border close-modal" data-modal="detailsModal">
                    إغلاق
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Decision modal --}}
<div id="decisionModal" class="ry-modal" aria-hidden="true">
    <div class="ry-modal-panel" style="max-width:580px;">
        <div class="ry-modal-header">
            <div class="d-flex align-items-center justify-content-between gap-3">
                <div>
                    <h5 id="decisionTitle" class="mb-1">اعتماد الطلب</h5>
                    <small id="decisionSubtitle" class="text-muted"></small>
                </div>

                <button type="button" class="ry-modal-close close-modal" data-modal="decisionModal">×</button>
            </div>
        </div>

        <div class="ry-modal-body">
            <div id="decisionAlert" class="alert alert-danger d-none"></div>

            <label class="form-label" for="decisionNotes">ملاحظات القرار</label>

            <textarea
                id="decisionNotes"
                class="form-control"
                rows="5"
                maxlength="2000"
                placeholder="اكتب ملاحظات القرار..."
            ></textarea>

            <small id="notesHelp" class="text-muted d-block mt-2">
                الملاحظات اختيارية عند الاعتماد.
            </small>
        </div>

        <div class="ry-modal-footer">
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-light border close-modal" data-modal="decisionModal">
                    إلغاء
                </button>

                <button type="button" id="confirmDecisionButton" class="btn btn-success">
                    تأكيد الاعتماد
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function startLeaveApprovalPage() {
    if (typeof window.jQuery === 'undefined') {
        window.setTimeout(startLeaveApprovalPage, 50);
        return;
    }

    window.jQuery(function ($) {
        var urls = {
            data: @json(route('app.leaves.approvals.data')),
            approve: @json(route('app.leaves.approvals.approve', ['leaveRequest' => '__ID__'])),
            reject: @json(route('app.leaves.approvals.reject', ['leaveRequest' => '__ID__'])),
            bulkApprove: @json(route('app.leaves.approvals.bulk-approve')),
            bulkReject: @json(route('app.leaves.approvals.bulk-reject'))
        };

        var currentPage = 1;
        var rowsCache = {};
        var selectedIds = {};
        var decisionType = 'approve';
        var decisionId = null;
        var decisionBulk = false;

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            }
        });

        function escapeHtml(value) {
            return $('<div>').text(value === null || value === undefined ? '' : value).html();
        }

        function dateText(value) {
            return value ? String(value).substring(0, 10) : '-';
        }

        function employeeName(employee) {
            if (!employee) {
                return '-';
            }

            if (employee.name || employee.full_name) {
                return employee.name || employee.full_name;
            }

            return $.grep([
                employee.first_name,
                employee.father_name,
                employee.grandfather_name,
                employee.family_name
            ], function (value) {
                return value;
            }).join(' ');
        }

        function showAlert(message, type) {
            $('#pageAlert')
                .removeClass('d-none alert-success alert-danger alert-warning')
                .addClass('alert-' + (type || 'success'))
                .text(message);

            $('html, body').animate({ scrollTop: 0 }, 200);
        }

        function openModal(id) {
            $('#' + id)
                .css('display', 'flex')
                .attr('aria-hidden', 'false')
                .find('.ry-modal-body')
                .first()
                .scrollTop(0);

            $('body').addClass('ry-modal-open');
        }

        function closeModal(id) {
            $('#' + id).hide().attr('aria-hidden', 'true');

            if (!$('.ry-modal:visible').length) {
                $('body').removeClass('ry-modal-open');
            }
        }

        function selectedArray() {
            var ids = [];

            $.each(selectedIds, function (id, selected) {
                if (selected) {
                    ids.push(parseInt(id, 10));
                }
            });

            return ids;
        }

        function updateSelectionControls() {
            var count = selectedArray().length;

            $('#selectedCount').text(count + ' محدد');
            $('#bulkApproveButton, #bulkRejectButton').prop('disabled', count === 0);

            var visible = $('.request-checkbox').length;
            var checked = $('.request-checkbox:checked').length;

            $('#selectAll').prop('checked', visible > 0 && visible === checked);
        }

        function renderRows(response) {
            var items = response.data || [];
            var html = '';
            rowsCache = {};

            if (!items.length) {
                $('#approvalTableBody').html(`
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <h6>لا توجد طلبات بانتظار الاعتماد</h6>
                                <p class="mb-0">تمت معالجة جميع الطلبات الحالية.</p>
                            </div>
                        </td>
                    </tr>
                `);

                $('#selectAll').prop('checked', false);
                return;
            }

            $.each(items, function (index, item) {
                rowsCache[item.id] = item;

                var employee = item.employee || {};
                var leaveType = item.leave_type || {};
                var level = item.current_approval_level || item.approval_level || 1;
                var totalLevels = item.approval_levels || item.total_approval_levels || 1;

                html += `
                    <tr>
                        <td>
                            <input
                                type="checkbox"
                                class="form-check-input request-checkbox"
                                data-id="${escapeHtml(item.id)}"
                                ${selectedIds[item.id] ? 'checked' : ''}
                            >
                        </td>

                        <td>${(response.from || 1) + index}</td>

                        <td>
                            <div class="employee-name">${escapeHtml(employeeName(employee))}</div>
                            <small class="text-muted">${escapeHtml(employee.employee_number || '-')}</small>
                        </td>

                        <td>
                            <div class="fw-semibold">${escapeHtml(leaveType.name || '-')}</div>
                            <small class="text-muted" dir="ltr">${escapeHtml(leaveType.code || '')}</small>
                        </td>

                        <td>
                            <div>${dateText(item.start_date)}</div>
                            <small class="text-muted">إلى ${dateText(item.end_date)}</small>
                        </td>

                        <td class="fw-semibold">${escapeHtml(item.duration_label || item.requested_amount || '-')}</td>

                        <td>
                            <div class="request-reason" title="${escapeHtml(item.reason || '')}">
                                ${escapeHtml(item.reason || '-')}
                            </div>
                        </td>

                        <td>
                            <span class="approval-level">${escapeHtml(level)} من ${escapeHtml(totalLevels)}</span>
                        </td>

                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <button type="button" class="btn btn-sm btn-light border btn-details" data-id="${item.id}">عرض</button>
                                <button type="button" class="btn btn-sm btn-outline-success btn-approve" data-id="${item.id}">اعتماد</button>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-reject" data-id="${item.id}">رفض</button>
                            </div>
                        </td>
                    </tr>
                `;
            });

            $('#approvalTableBody').html(html);
            updateSelectionControls();
        }

        function renderPagination(response) {
            var current = parseInt(response.current_page || 1, 10);
            var last = parseInt(response.last_page || 1, 10);
            var html = '';

            $('#recordsSummary').text(
                response.total
                    ? 'عرض ' + response.from + ' إلى ' + response.to + ' من ' + response.total
                    : 'لا توجد طلبات'
            );

            $('#paginationInfo').text('الصفحة ' + current + ' من ' + last);

            html += `<button type="button" class="approval-pagination page-button" data-page="${current - 1}" ${current <= 1 ? 'disabled' : ''}>‹</button>`;

            var start = Math.max(1, current - 2);
            var end = Math.min(last, current + 2);

            for (var page = start; page <= end; page++) {
                html += `<button type="button" class="approval-pagination page-button ${page === current ? 'active' : ''}" data-page="${page}">${page}</button>`;
            }

            html += `<button type="button" class="approval-pagination page-button" data-page="${current + 1}" ${current >= last ? 'disabled' : ''}>›</button>`;

            $('#paginationLinks').html(html);
        }

        function loadData(page) {
            currentPage = page || 1;

            $('#approvalTableBody').html(`
                <tr>
                    <td colspan="9" class="text-center py-5 text-muted">جاري تحميل الطلبات...</td>
                </tr>
            `);

            $.ajax({
                url: urls.data,
                type: 'GET',
                data: {
                    page: currentPage,
                    search: $('#filterSearch').val(),
                    per_page: $('#filterPerPage').val()
                },
                success: function (response) {
                    renderRows(response);
                    renderPagination(response);
                },
                error: function (xhr) {
                    $('#approvalTableBody').html(`
                        <tr>
                            <td colspan="9" class="text-center py-5 text-danger">
                                ${escapeHtml(xhr.responseJSON?.message || 'تعذر تحميل طلبات الاعتماد.')}
                            </td>
                        </tr>
                    `);
                }
            });
        }

        function showDetails(id) {
            var item = rowsCache[id];

            if (!item) {
                return;
            }

            var employee = item.employee || {};
            var leaveType = item.leave_type || {};

            $('#requestDetails').html(`
                <div class="row g-3">
                    <div class="col-md-6"><div class="detail-box"><div class="detail-label">الموظف</div><div class="fw-bold">${escapeHtml(employeeName(employee))}</div></div></div>
                    <div class="col-md-6"><div class="detail-box"><div class="detail-label">الرقم الوظيفي</div><div>${escapeHtml(employee.employee_number || '-')}</div></div></div>
                    <div class="col-md-6"><div class="detail-box"><div class="detail-label">نوع الإجازة</div><div class="fw-bold">${escapeHtml(leaveType.name || '-')}</div></div></div>
                    <div class="col-md-6"><div class="detail-box"><div class="detail-label">المدة</div><div class="fw-bold">${escapeHtml(item.duration_label || item.requested_amount || '-')}</div></div></div>
                    <div class="col-md-4"><div class="detail-box"><div class="detail-label">البداية</div><div>${dateText(item.start_date)}</div></div></div>
                    <div class="col-md-4"><div class="detail-box"><div class="detail-label">النهاية</div><div>${dateText(item.end_date)}</div></div></div>
                    <div class="col-md-4"><div class="detail-box"><div class="detail-label">تاريخ الطلب</div><div>${dateText(item.requested_at)}</div></div></div>
                    <div class="col-12"><div class="detail-box"><div class="detail-label">سبب الإجازة</div><div>${escapeHtml(item.reason || '-')}</div></div></div>
                    ${item.handover_notes ? `<div class="col-12"><div class="detail-box"><div class="detail-label">ملاحظات تسليم العمل</div><div>${escapeHtml(item.handover_notes)}</div></div></div>` : ''}
                    ${item.has_attachment ? `<div class="col-12"><div class="alert alert-info mb-0">يوجد مستند مرفق بالطلب.</div></div>` : ''}
                </div>
            `);

            openModal('detailsModal');
        }

        function prepareDecision(type, id, bulk) {
            decisionType = type;
            decisionId = id || null;
            decisionBulk = bulk === true;

            var rejecting = type === 'reject';
            var count = decisionBulk ? selectedArray().length : 1;

            $('#decisionNotes').val('');
            $('#decisionAlert').addClass('d-none').text('');
            $('#decisionTitle').text(rejecting ? 'رفض طلب الإجازة' : 'اعتماد طلب الإجازة');
            $('#decisionSubtitle').text(decisionBulk ? 'سيتم تطبيق القرار على ' + count + ' طلب.' : 'سيتم تطبيق القرار على الطلب المحدد.');
            $('#notesHelp').text(rejecting ? 'سبب الرفض مطلوب.' : 'الملاحظات اختيارية عند الاعتماد.');

            $('#confirmDecisionButton')
                .toggleClass('btn-success', !rejecting)
                .toggleClass('btn-danger', rejecting)
                .text(rejecting ? 'تأكيد الرفض' : 'تأكيد الاعتماد');

            openModal('decisionModal');
        }

        function executeDecision() {
            var notes = $.trim($('#decisionNotes').val());
            var rejecting = decisionType === 'reject';

            if (rejecting && notes.length < 3) {
                $('#decisionAlert').removeClass('d-none').text('يجب كتابة سبب الرفض.');
                return;
            }

            var url;
            var data = { notes: notes };

            if (decisionBulk) {
                data.request_ids = selectedArray();
                url = rejecting ? urls.bulkReject : urls.bulkApprove;
            } else {
                url = (rejecting ? urls.reject : urls.approve).replace('__ID__', decisionId);
            }

            var $button = $('#confirmDecisionButton');
            $button.prop('disabled', true).text('جاري التنفيذ...');

            $.ajax({
                url: url,
                type: 'POST',
                data: data,
                success: function (response) {
                    closeModal('decisionModal');
                    selectedIds = {};
                    updateSelectionControls();
                    showAlert(response.message || 'تم تنفيذ القرار بنجاح.');
                    loadData(currentPage);
                },
                error: function (xhr) {
                    var response = xhr.responseJSON || {};
                    var errors = response.errors || {};
                    var message = response.message || 'تعذر تنفيذ القرار.';

                    if (errors.notes && errors.notes[0]) {
                        message = errors.notes[0];
                    }

                    $('#decisionAlert').removeClass('d-none').text(message);
                },
                complete: function () {
                    $button.prop('disabled', false).text(rejecting ? 'تأكيد الرفض' : 'تأكيد الاعتماد');
                }
            });
        }

        $('#filtersForm').on('submit', function (event) {
            event.preventDefault();
            selectedIds = {};
            loadData(1);
        });

        $('#resetFilters').on('click', function () {
            $('#filterSearch').val('');
            $('#filterPerPage').val('15');
            selectedIds = {};
            loadData(1);
        });

        $(document).on('click', '.page-button:not(:disabled)', function () {
            loadData(parseInt($(this).data('page'), 10));
        });

        $('#selectAll').on('change', function () {
            var checked = $(this).is(':checked');

            $('.request-checkbox').each(function () {
                var id = $(this).data('id');
                $(this).prop('checked', checked);
                selectedIds[id] = checked;
            });

            updateSelectionControls();
        });

        $(document).on('change', '.request-checkbox', function () {
            selectedIds[$(this).data('id')] = $(this).is(':checked');
            updateSelectionControls();
        });

        $(document).on('click', '.btn-details', function () {
            showDetails($(this).data('id'));
        });

        $(document).on('click', '.btn-approve', function () {
            prepareDecision('approve', $(this).data('id'), false);
        });

        $(document).on('click', '.btn-reject', function () {
            prepareDecision('reject', $(this).data('id'), false);
        });

        $('#bulkApproveButton').on('click', function () {
            prepareDecision('approve', null, true);
        });

        $('#bulkRejectButton').on('click', function () {
            prepareDecision('reject', null, true);
        });

        $('#confirmDecisionButton').on('click', executeDecision);

        $('.close-modal').on('click', function () {
            closeModal($(this).data('modal'));
        });

        $('.ry-modal').on('click', function (event) {
            if ($(event.target).is(this)) {
                closeModal($(this).attr('id'));
            }
        });

        $(document).on('keydown.leaveApproval', function (event) {
            if (event.key === 'Escape') {
                $('.ry-modal:visible').each(function () {
                    closeModal($(this).attr('id'));
                });
            }
        });

        loadData(1);
    });
})();
</script>
@endpush