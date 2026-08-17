@extends('layouts.tenant')

@section('title', 'المسميات الوظيفية')
@section('page-title', 'المسميات الوظيفية')

@section('content')

<style>
    .job-title-stat {
        height: 100%;
        padding: 18px;
        border: 1px solid #e8edf5;
        border-radius: 16px;
        background: #fff;
    }

    .job-title-stat-icon {
        width: 46px;
        height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 46px;
        border-radius: 14px;
        color: #0d6efd;
        background: #eaf2ff;
        font-size: 1.2rem;
    }

    .job-title-name-cell {
        min-width: 240px;
    }

    .job-title-code {
        direction: ltr;
        display: inline-block;
        font-family: monospace;
        font-size: .82rem;
    }

    #jobTitleModal {
        overflow: hidden !important;
        padding-left: 0 !important;
    }

    #jobTitleModal .modal-dialog {
        height: calc(100vh - 30px);
        max-height: calc(100vh - 30px);
        margin-top: 15px;
        margin-bottom: 15px;
    }

    #jobTitleModal .modal-content,
    #jobTitleModal #jobTitleForm {
        display: flex;
        flex-direction: column;
        width: 100%;
        height: 100%;
        max-height: 100%;
        min-height: 0;
        overflow: hidden;
    }

    #jobTitleModal .modal-header,
    #jobTitleModal .modal-footer {
        flex: 0 0 auto;
        background: #fff;
        z-index: 2;
    }

    #jobTitleModal .modal-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto !important;
        overflow-x: hidden;
        overscroll-behavior: contain;
        scrollbar-gutter: stable;
    }

    @media (max-width: 767.98px) {
        #jobTitleModal .modal-dialog {
            width: calc(100% - 16px);
            height: calc(100vh - 16px);
            max-height: calc(100vh - 16px);
            margin: 8px;
        }
    }
</style>

<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-1">المسميات الوظيفية</h4>
            <div class="text-muted small">
                تعريف المسميات الوظيفية وربطها بالإدارات والأقسام
            </div>
        </div>

        @can('job_titles.create')
            <button type="button" class="btn btn-primary" id="btnAddJobTitle">
                <i class="bi bi-plus-lg"></i>
                إضافة مسمى وظيفي
            </button>
        @endcan
    </div>

    {{-- Summary --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="job-title-stat">
                <div class="d-flex align-items-center gap-3">
                    <span class="job-title-stat-icon"><i class="bi bi-briefcase"></i></span>
                    <div>
                        <div class="text-muted small">إجمالي النتائج</div>
                        <div class="fs-5 fw-bold" id="totalJobTitles">—</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="job-title-stat">
                <div class="d-flex align-items-center gap-3">
                    <span class="job-title-stat-icon"><i class="bi bi-check-circle"></i></span>
                    <div>
                        <div class="text-muted small">النشطة في الصفحة</div>
                        <div class="fs-5 fw-bold" id="pageActiveJobTitles">—</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="job-title-stat">
                <div class="d-flex align-items-center gap-3">
                    <span class="job-title-stat-icon"><i class="bi bi-pause-circle"></i></span>
                    <div>
                        <div class="text-muted small">غير النشطة في الصفحة</div>
                        <div class="fs-5 fw-bold" id="pageInactiveJobTitles">—</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <form id="jobTitleFilters">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-3 col-md-6">
                        <label for="filter_search" class="form-label">البحث</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input
                                type="search"
                                class="form-control"
                                id="filter_search"
                                placeholder="الاسم أو الكود..."
                                autocomplete="off"
                            >
                        </div>
                    </div>

                    <div class="col-xl-2 col-md-6">
                        <label for="filter_branch_id" class="form-label">الفرع</label>
                        <select class="form-select" id="filter_branch_id">
                            <option value="">جميع الفروع</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">
                                    {{ $branch->name }}
                                    @if($branch->is_main) — الرئيسي @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <label for="filter_department_id" class="form-label">الإدارة أو القسم</label>
                        <select class="form-select" id="filter_department_id">
                            <option value="">جميع الإدارات والأقسام</option>
                            @foreach($departments as $department)
                                <option
                                    value="{{ $department->id }}"
                                    data-branch-id="{{ $department->branch_id }}"
                                >
                                    @if($department->parent_id) — @endif
                                    {{ $department->name }} ({{ $department->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-md-3">
                        <label for="filter_status" class="form-label">الحالة</label>
                        <select class="form-select" id="filter_status">
                            <option value="">جميع الحالات</option>
                            <option value="active">نشط</option>
                            <option value="inactive">غير نشط</option>
                        </select>
                    </div>

                    <div class="col-xl-1 col-md-3">
                        <label for="filter_per_page" class="form-label">العرض</label>
                        <select class="form-select" id="filter_per_page">
                            <option value="10">10</option>
                            <option value="15" selected>15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>

                    <div class="col-xl-1 col-md-6">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1" title="بحث">
                                <i class="bi bi-search"></i>
                                <span class="d-xl-none">بحث</span>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="btnResetJobTitleFilters" title="إعادة ضبط">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <strong>قائمة المسميات الوظيفية</strong>

                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-subtle text-primary" id="jobTitleCountBadge">0 سجل</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnRefreshJobTitles">
                        <i class="bi bi-arrow-repeat"></i>
                        تحديث
                    </button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>المسمى الوظيفي</th>
                        <th>الإدارة أو القسم</th>
                        <th>الفرع</th>
                        <th>ترتيب العرض</th>
                        <th>الحالة</th>
                        <th>تاريخ الإنشاء</th>
                        <th class="text-center">الإجراءات</th>
                    </tr>
                </thead>
                <tbody id="jobTitlesTableBody">
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">جاري تحميل البيانات...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap gap-3">
            <small class="text-muted" id="jobTitlePaginationSummary"></small>
            <nav aria-label="صفحات المسميات الوظيفية">
                <ul class="pagination pagination-sm mb-0" id="jobTitlePagination"></ul>
            </nav>
        </div>
    </div>
</div>

{{-- Job Title Modal --}}
<div class="modal fade" id="jobTitleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="jobTitleForm">
                @csrf
                <input type="hidden" id="job_title_id">

                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1" id="jobTitleModalTitle">إضافة مسمى وظيفي</h5>
                        <div class="text-muted small">الحقول التي تحمل علامة (*) إلزامية</div>
                    </div>
                    <button type="button" class="btn-close" data-job-title-modal-close aria-label="إغلاق"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="alert alert-danger d-none" id="jobTitleFormErrors"></div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="job_title_branch_id" class="form-label">الفرع</label>
                            <select class="form-select" id="job_title_branch_id">
                                <option value="">عام — جميع الفروع</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">
                                        {{ $branch->name }}
                                        @if($branch->is_main) — الفرع الرئيسي @endif
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">يُستخدم لتصفية قائمة الإدارات، ولا يتم حفظه مباشرة في المسمى.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="job_title_department_id" class="form-label">الإدارة أو القسم</label>
                            <select class="form-select" name="department_id" id="job_title_department_id">
                                <option value="">بدون إدارة محددة</option>
                                @foreach($departments as $department)
                                    <option
                                        value="{{ $department->id }}"
                                        data-branch-id="{{ $department->branch_id }}"
                                    >
                                        @if($department->parent_id) — @endif
                                        {{ $department->name }} ({{ $department->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="job_title_code" class="form-label">الكود <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control"
                                name="code"
                                id="job_title_code"
                                maxlength="50"
                                dir="ltr"
                                placeholder="HR-MGR"
                                required
                            >
                            <div class="form-text">حروف إنجليزية وأرقام وشرطة أو نقطة.</div>
                        </div>

                        <div class="col-md-4">
                            <label for="job_title_name" class="form-label">الاسم بالعربية <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control"
                                name="name"
                                id="job_title_name"
                                maxlength="255"
                                placeholder="مدير الموارد البشرية"
                                required
                            >
                        </div>

                        <div class="col-md-4">
                            <label for="job_title_name_en" class="form-label">الاسم بالإنجليزية</label>
                            <input
                                type="text"
                                class="form-control"
                                name="name_en"
                                id="job_title_name_en"
                                maxlength="255"
                                dir="ltr"
                                placeholder="HR Manager"
                            >
                        </div>

                        <div class="col-md-4">
                            <label for="job_title_sort_order" class="form-label">ترتيب العرض</label>
                            <input
                                type="number"
                                class="form-control"
                                name="sort_order"
                                id="job_title_sort_order"
                                min="0"
                                max="999999"
                                value="0"
                            >
                        </div>

                        <div class="col-md-8 d-flex align-items-end">
                            <div class="border rounded-3 p-3 w-100">
                                <div class="form-check form-switch mb-0">
                                    <input type="hidden" name="is_active" value="0">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="is_active"
                                        value="1"
                                        id="job_title_is_active"
                                        checked
                                    >
                                    <label class="form-check-label" for="job_title_is_active">المسمى الوظيفي نشط</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="job_title_description" class="form-label">الوصف</label>
                            <textarea
                                class="form-control"
                                name="description"
                                id="job_title_description"
                                rows="4"
                                maxlength="5000"
                                placeholder="وصف مختصر للمسمى الوظيفي ومسؤولياته العامة..."
                            ></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-job-title-modal-close>إلغاء</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveJobTitle">
                        <span class="save-label">حفظ البيانات</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')

<script>
jQuery(function ($) {
    'use strict';

    const csrfToken = @json(csrf_token());

    const routes = {
        data: @json(route('app.organization.job-titles.data')),
        store: @json(route('app.organization.job-titles.store')),
        show: @json(route('app.organization.job-titles.show', ['jobTitle' => '__JOB_TITLE__'])),
        update: @json(route('app.organization.job-titles.update', ['jobTitle' => '__JOB_TITLE__'])),
        destroy: @json(route('app.organization.job-titles.destroy', ['jobTitle' => '__JOB_TITLE__'])),
    };

    const permissions = {
        update: @json(auth()->user()->can('job_titles.update')),
        delete: @json(auth()->user()->can('job_titles.delete')),
    };

    const $jobTitleModal = $('#jobTitleModal');
    let currentPage = 1;
    let searchTimer = null;
    let tableRequest = null;

    $jobTitleModal.appendTo('body');

    function jobTitleUrl(url, id) {
        return url.replace('__JOB_TITLE__', id);
    }

    function escapeHtml(value) {
        return $('<div>').text(value ?? '').html();
    }

    function showJobTitleModal() {
        $('#jobTitleModalBackdrop').remove();

        $('<div>', {
            id: 'jobTitleModalBackdrop',
            class: 'modal-backdrop fade show',
        }).appendTo('body');

        $('body')
            .addClass('modal-open')
            .css('overflow', 'hidden');

        $jobTitleModal
            .attr({
                role: 'dialog',
                'aria-modal': 'true',
            })
            .removeAttr('aria-hidden')
            .css('display', 'block')
            .addClass('show');
    }

    function hideJobTitleModal() {
        $jobTitleModal
            .removeClass('show')
            .css('display', 'none')
            .attr('aria-hidden', 'true')
            .removeAttr('aria-modal role');

        $('#jobTitleModalBackdrop').remove();

        $('body')
            .removeClass('modal-open')
            .css('overflow', '');
    }

    function getErrorMessage(xhr) {
        if (xhr.responseJSON?.errors) {
            const messages = [];

            $.each(xhr.responseJSON.errors, function (field, fieldErrors) {
                $.each(fieldErrors, function (index, message) {
                    messages.push(message);
                });
            });

            return messages.join('\n');
        }

        return xhr.responseJSON?.message || 'حدث خطأ غير متوقع، يرجى المحاولة مرة أخرى.';
    }

    function showRequestError(xhr, insideModal = false) {
        const message = getErrorMessage(xhr);

        if (insideModal) {
            $('#jobTitleFormErrors')
                .removeClass('d-none')
                .html(escapeHtml(message).replace(/\n/g, '<br>'));

            $('#jobTitleModal .modal-body').scrollTop(0);
        }

        if (window.Swal) {
            Swal.fire({
                icon: 'error',
                title: 'تعذر تنفيذ العملية',
                text: message,
            });
        } else if (!insideModal) {
            alert(message);
        }
    }

    function notifySuccess(message) {
        if (window.Swal) {
            Swal.fire({
                icon: 'success',
                title: 'تمت العملية',
                text: message,
                timer: 1400,
                showConfirmButton: false,
            }).then(function () {
                loadJobTitles(currentPage);
            });
        } else {
            alert(message);
            loadJobTitles(currentPage);
        }
    }

    function resetJobTitleForm() {
        $('#jobTitleForm').trigger('reset');
        $('#job_title_id').val('');
        $('#job_title_branch_id').val('');
        $('#job_title_department_id').val('');
        $('#job_title_sort_order').val(0);
        $('#job_title_is_active').prop('checked', true);
        $('#jobTitleFormErrors').addClass('d-none').empty();
        $('#btnSaveJobTitle').prop('disabled', false);
        $('#btnSaveJobTitle .save-label').text('حفظ البيانات');
        filterDepartmentOptions('#job_title_department_id', '', true);
    }

    function filters() {
        return {
            search: $.trim($('#filter_search').val()),
            branch_id: $('#filter_branch_id').val(),
            department_id: $('#filter_department_id').val(),
            status: $('#filter_status').val(),
            per_page: $('#filter_per_page').val(),
            page: currentPage,
        };
    }

    function filterDepartmentOptions(selectSelector, branchId, includeGlobal) {
        const $select = $(selectSelector);

        $select.find('option').each(function () {
            const $option = $(this);
            const value = String($option.val() || '');
            const optionBranchId = String($option.data('branch-id') || '');

            if (value === '') {
                $option.prop('hidden', false);
                return;
            }

            const visible = branchId === ''
                || optionBranchId === String(branchId)
                || (includeGlobal && optionBranchId === '');

            $option.prop('hidden', !visible);
        });

        const $selected = $select.find('option:selected');

        if ($selected.prop('hidden')) {
            $select.val('');
        }
    }

    function statusBadge(isActive) {
        return isActive
            ? '<span class="badge bg-success-subtle text-success">نشط</span>'
            : '<span class="badge bg-danger-subtle text-danger">غير نشط</span>';
    }

    function formatDate(value) {
        if (!value) return '—';

        const date = new Date(value);

        if (Number.isNaN(date.getTime())) return escapeHtml(value);

        return new Intl.DateTimeFormat('ar-SA-u-ca-gregory', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
        }).format(date);
    }

    function renderActions(item) {
        const buttons = [];

        if (permissions.update) {
            buttons.push(
                '<button type="button" class="btn btn-sm btn-outline-primary btn-edit-job-title" ' +
                'data-id="' + item.id + '" title="تعديل">' +
                '<i class="bi bi-pencil"></i><span class="visually-hidden">تعديل</span></button>'
            );
        }

        if (permissions.delete) {
            buttons.push(
                '<button type="button" class="btn btn-sm btn-outline-danger btn-archive-job-title" ' +
                'data-id="' + item.id + '" data-name="' + escapeHtml(item.name) + '" title="أرشفة">' +
                '<i class="bi bi-archive"></i><span class="visually-hidden">أرشفة</span></button>'
            );
        }

        return buttons.length
            ? '<div class="btn-group btn-group-sm">' + buttons.join('') + '</div>'
            : '<span class="text-muted">—</span>';
    }

    function renderTable(response) {
        const rows = response.data || [];
        const $tbody = $('#jobTitlesTableBody');

        $('#totalJobTitles').text(response.total ?? 0);
        $('#jobTitleCountBadge').text((response.total ?? 0) + ' سجل');
        $('#pageActiveJobTitles').text(
            rows.filter(function (item) { return Boolean(item.is_active); }).length
        );
        $('#pageInactiveJobTitles').text(
            rows.filter(function (item) { return !item.is_active; }).length
        );

        if (!rows.length) {
            $tbody.html(
                '<tr><td colspan="8" class="text-center py-5">' +
                '<div class="text-muted mb-2"><i class="bi bi-briefcase fs-3"></i></div>' +
                '<div class="fw-semibold">لا توجد مسميات وظيفية مطابقة</div>' +
                '<small class="text-muted">غيّر معايير البحث أو أضف مسمى وظيفيًا جديدًا.</small>' +
                '</td></tr>'
            );

            renderPagination(response);
            return;
        }

        let html = '';

        $.each(rows, function (index, item) {
            const number = (response.from || 1) + index;
            const department = item.department
                ? '<div class="fw-semibold">' + escapeHtml(item.department.name) + '</div>' +
                  '<small class="text-muted job-title-code">' + escapeHtml(item.department.code) + '</small>'
                : '<span class="text-muted">غير محدد</span>';
            const branch = item.department?.branch
                ? escapeHtml(item.department.branch.name)
                : '<span class="text-muted">عام</span>';

            html += '<tr>' +
                '<td>' + number + '</td>' +
                '<td class="job-title-name-cell">' +
                    '<div class="fw-semibold">' + escapeHtml(item.name) + '</div>' +
                    (item.name_en
                        ? '<small class="text-muted" dir="ltr">' + escapeHtml(item.name_en) + '</small><br>'
                        : '') +
                    '<span class="job-title-code text-muted">' + escapeHtml(item.code) + '</span>' +
                '</td>' +
                '<td>' + department + '</td>' +
                '<td>' + branch + '</td>' +
                '<td>' + (item.sort_order ?? 0) + '</td>' +
                '<td>' + statusBadge(item.is_active) + '</td>' +
                '<td>' + formatDate(item.created_at) + '</td>' +
                '<td class="text-center">' + renderActions(item) + '</td>' +
            '</tr>';
        });

        $tbody.html(html);
        renderPagination(response);
    }

    function renderPagination(response) {
        const current = Number(response.current_page || 1);
        const last = Number(response.last_page || 1);
        const $pagination = $('#jobTitlePagination');

        $('#jobTitlePaginationSummary').text(
            response.total
                ? 'عرض ' + response.from + ' إلى ' + response.to + ' من أصل ' + response.total
                : 'لا توجد نتائج'
        );

        $pagination.empty();

        if (last <= 1) return;

        function addPage(label, page, disabled = false, active = false) {
            const $item = $('<li>', {
                class: 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : ''),
            });

            $('<button>', {
                type: 'button',
                class: 'page-link',
                text: label,
                'data-page': page,
                disabled: disabled,
            }).appendTo($item);

            $item.appendTo($pagination);
        }

        addPage('السابق', current - 1, current === 1);

        const start = Math.max(1, current - 2);
        const end = Math.min(last, current + 2);

        if (start > 1) {
            addPage('1', 1, false, current === 1);
            if (start > 2) addPage('…', current, true);
        }

        for (let page = start; page <= end; page += 1) {
            addPage(String(page), page, false, page === current);
        }

        if (end < last) {
            if (end < last - 1) addPage('…', current, true);
            addPage(String(last), last, false, current === last);
        }

        addPage('التالي', current + 1, current === last);
    }

    function loadJobTitles(page = 1) {
        currentPage = page;

        if (tableRequest) {
            tableRequest.abort();
        }

        $('#jobTitlesTableBody').html(
            '<tr><td colspan="8" class="text-center text-muted py-5">' +
            '<span class="spinner-border spinner-border-sm ms-2"></span>جاري تحميل البيانات...</td></tr>'
        );

        tableRequest = $.ajax({
            url: routes.data,
            type: 'GET',
            data: filters(),
            dataType: 'json',
            headers: { Accept: 'application/json' },
            success: renderTable,
            error: function (xhr, status) {
                if (status === 'abort') return;

                $('#jobTitlesTableBody').html(
                    '<tr><td colspan="8" class="text-center text-danger py-5">تعذر تحميل البيانات.</td></tr>'
                );
                showRequestError(xhr);
            },
            complete: function () {
                tableRequest = null;
            },
        });
    }

    $(document).on('click', '[data-job-title-modal-close]', hideJobTitleModal);

    $(document).on('keydown', function (event) {
        if (event.key === 'Escape' && $jobTitleModal.hasClass('show')) {
            hideJobTitleModal();
        }
    });

    $('#jobTitleFilters').on('submit', function (event) {
        event.preventDefault();
        loadJobTitles(1);
    });

    $('#filter_search').on('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            loadJobTitles(1);
        }, 450);
    });

    $('#filter_branch_id').on('change', function () {
        filterDepartmentOptions('#filter_department_id', $(this).val(), false);
        loadJobTitles(1);
    });

    $('#filter_department_id, #filter_status, #filter_per_page').on('change', function () {
        loadJobTitles(1);
    });

    $('#btnResetJobTitleFilters').on('click', function () {
        $('#jobTitleFilters').trigger('reset');
        filterDepartmentOptions('#filter_department_id', '', false);
        loadJobTitles(1);
    });

    $('#btnRefreshJobTitles').on('click', function () {
        loadJobTitles(currentPage);
    });

    $(document).on('click', '#jobTitlePagination .page-link', function () {
        const page = Number($(this).data('page'));
        if (page > 0) loadJobTitles(page);
    });

    $('#job_title_branch_id').on('change', function () {
        filterDepartmentOptions('#job_title_department_id', $(this).val(), true);
    });

    $('#btnAddJobTitle').on('click', function () {
        resetJobTitleForm();
        $('#jobTitleModalTitle').text('إضافة مسمى وظيفي');
        showJobTitleModal();
        window.setTimeout(function () { $('#job_title_code').trigger('focus'); }, 100);
    });

    $(document).on('click', '.btn-edit-job-title', function () {
        const id = $(this).data('id');
        const $button = $(this);

        $button.prop('disabled', true);
        resetJobTitleForm();

        $.ajax({
            url: jobTitleUrl(routes.show, id),
            type: 'GET',
            dataType: 'json',
            headers: { Accept: 'application/json' },
            success: function (response) {
                const item = response.job_title;
                const branchId = item.department?.branch_id || '';

                $('#job_title_id').val(item.id);
                $('#job_title_branch_id').val(branchId);
                filterDepartmentOptions('#job_title_department_id', branchId, true);

                if (
                    item.department_id &&
                    !$('#job_title_department_id option[value="' + item.department_id + '"]').length
                ) {
                    $('<option>', {
                        value: item.department_id,
                        text: item.department?.name || 'الإدارة الحالية',
                        'data-branch-id': branchId,
                    }).appendTo('#job_title_department_id');
                }

                $('#job_title_department_id').val(item.department_id || '');
                $('#job_title_code').val(item.code || '');
                $('#job_title_name').val(item.name || '');
                $('#job_title_name_en').val(item.name_en || '');
                $('#job_title_sort_order').val(item.sort_order ?? 0);
                $('#job_title_is_active').prop('checked', Boolean(item.is_active));
                $('#job_title_description').val(item.description || '');
                $('#jobTitleModalTitle').text('تعديل: ' + item.name);

                showJobTitleModal();
            },
            error: function (xhr) {
                showRequestError(xhr);
            },
            complete: function () {
                $button.prop('disabled', false);
            },
        });
    });

    $('#jobTitleForm').on('submit', function (event) {
        event.preventDefault();

        $('#jobTitleFormErrors').addClass('d-none').empty();

        const id = $('#job_title_id').val();
        const isEdit = id !== '';
        const data = $(this).serializeArray();

        if (isEdit) {
            data.push({ name: '_method', value: 'PUT' });
        }

        const $button = $('#btnSaveJobTitle');

        $button.prop('disabled', true);
        $button.find('.save-label').text('جاري الحفظ...');

        $.ajax({
            url: isEdit ? jobTitleUrl(routes.update, id) : routes.store,
            type: 'POST',
            data: $.param(data),
            dataType: 'json',
            headers: { Accept: 'application/json' },
            success: function (response) {
                hideJobTitleModal();
                notifySuccess(response.message);
            },
            error: function (xhr) {
                showRequestError(xhr, true);
            },
            complete: function () {
                $button.prop('disabled', false);
                $button.find('.save-label').text('حفظ البيانات');
            },
        });
    });

    $(document).on('click', '.btn-archive-job-title', function () {
        const id = $(this).data('id');
        const name = $(this).data('name');

        function executeArchive() {
            $.ajax({
                url: jobTitleUrl(routes.destroy, id),
                type: 'DELETE',
                data: { _token: csrfToken },
                dataType: 'json',
                headers: { Accept: 'application/json' },
                success: function (response) {
                    notifySuccess(response.message);
                },
                error: function (xhr) {
                    showRequestError(xhr);
                },
            });
        }

        if (window.Swal) {
            Swal.fire({
                icon: 'warning',
                title: 'أرشفة المسمى الوظيفي؟',
                text: 'سيتم أرشفة "' + name + '" ولن يظهر ضمن القائمة النشطة.',
                showCancelButton: true,
                confirmButtonText: 'نعم، أرشفة',
                cancelButtonText: 'إلغاء',
                confirmButtonColor: '#dc3545',
            }).then(function (result) {
                if (result.isConfirmed) executeArchive();
            });
        } else if (confirm('هل تريد أرشفة "' + name + '"؟')) {
            executeArchive();
        }
    });

    loadJobTitles();
});
</script>

@endpush