@extends('layouts.tenant')

@section('title', 'الإدارات والأقسام')
@section('page-title', 'الإدارات والأقسام')

@section('content')

<style>
    .department-stat {
        border: 1px solid #e8edf5;
        border-radius: 16px;
        background: #fff;
    }

    .department-stat .stat-icon {
        width: 46px;
        height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        background: #eaf2ff;
        color: #0d6efd;
        font-size: 1.2rem;
    }

    .department-tabs {
        display: inline-flex;
        gap: 6px;
        padding: 5px;
        border: 1px solid #e5e9f0;
        border-radius: 12px;
        background: #f7f9fc;
    }

    .department-tab {
        border: 0;
        border-radius: 9px;
        padding: 8px 16px;
        color: #667085;
        background: transparent;
    }

    .department-tab.active {
        color: #fff;
        background: #0d6efd;
        box-shadow: 0 4px 12px rgba(13, 110, 253, .2);
    }

    .department-name-cell {
        min-width: 230px;
    }

    .department-code {
        direction: ltr;
        display: inline-block;
        font-family: monospace;
        font-size: .82rem;
    }

    .org-tree,
    .org-tree ul {
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .org-tree ul {
        margin-right: 30px;
        padding-right: 22px;
        border-right: 2px solid #e8edf5;
    }

    .org-tree-node {
        position: relative;
        margin: 12px 0;
    }

    .org-tree ul > .org-tree-node::before {
        content: '';
        position: absolute;
        top: 29px;
        right: -22px;
        width: 20px;
        border-top: 2px solid #e8edf5;
    }

    .org-tree-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 14px 16px;
        border: 1px solid #e5e9f0;
        border-radius: 13px;
        background: #fff;
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    .org-tree-card:hover {
        border-color: #b9d2ff;
        box-shadow: 0 6px 18px rgba(16, 24, 40, .06);
    }

    #departmentModal {
        overflow: hidden !important;
        padding-left: 0 !important;
    }

    #departmentModal .modal-dialog {
        height: calc(100vh - 30px);
        max-height: calc(100vh - 30px);
        margin-top: 15px;
        margin-bottom: 15px;
    }

    #departmentModal .modal-content,
    #departmentModal #departmentForm {
        display: flex;
        flex-direction: column;
        width: 100%;
        height: 100%;
        max-height: 100%;
        min-height: 0;
        overflow: hidden;
    }

    #departmentModal .modal-header,
    #departmentModal .modal-footer {
        flex: 0 0 auto;
        background: #fff;
        z-index: 2;
    }

    #departmentModal .modal-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto !important;
        overflow-x: hidden;
        overscroll-behavior: contain;
        scrollbar-gutter: stable;
    }

    @media (max-width: 767.98px) {
        .department-tabs {
            display: flex;
            width: 100%;
        }

        .department-tab {
            flex: 1;
        }

        .org-tree ul {
            margin-right: 15px;
            padding-right: 15px;
        }

        .org-tree-card {
            align-items: flex-start;
            flex-direction: column;
        }

        #departmentModal .modal-dialog {
            width: calc(100% - 16px);
            height: calc(100vh - 16px);
            max-height: calc(100vh - 16px);
            margin: 8px;
        }
    }
</style>

<div class="container-fluid">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-1">الإدارات والأقسام</h4>
            <div class="text-muted small">
                بناء الهيكل التنظيمي وربط الإدارات والأقسام بفروع الشركة
            </div>
        </div>

        @can('departments.create')
            <button type="button" class="btn btn-primary" id="btnAddDepartment">
                <i class="bi bi-plus-lg"></i>
                إضافة إدارة أو قسم
            </button>
        @endcan
    </div>

    {{-- Summary --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="department-stat p-3 h-100">
                <div class="d-flex align-items-center gap-3">
                    <span class="stat-icon"><i class="bi bi-diagram-3"></i></span>
                    <div>
                        <div class="text-muted small">إجمالي النتائج</div>
                        <div class="fs-5 fw-bold" id="totalDepartments">—</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="department-stat p-3 h-100">
                <div class="d-flex align-items-center gap-3">
                    <span class="stat-icon"><i class="bi bi-building"></i></span>
                    <div>
                        <div class="text-muted small">الإدارات في الصفحة</div>
                        <div class="fs-5 fw-bold" id="pageRootDepartments">—</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="department-stat p-3 h-100">
                <div class="d-flex align-items-center gap-3">
                    <span class="stat-icon"><i class="bi bi-folder2-open"></i></span>
                    <div>
                        <div class="text-muted small">الأقسام في الصفحة</div>
                        <div class="fs-5 fw-bold" id="pageChildDepartments">—</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <form id="departmentFilters">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-4 col-md-6">
                        <label for="filter_search" class="form-label">البحث</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input
                                type="search"
                                class="form-control"
                                id="filter_search"
                                placeholder="الاسم، الاسم الإنجليزي، الكود..."
                                autocomplete="off"
                            >
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <label for="filter_branch_id" class="form-label">الفرع</label>
                        <select class="form-select" id="filter_branch_id">
                            <option value="">جميع الفروع</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">
                                    {{ $branch->name }}
                                    @if($branch->is_main) — الفرع الرئيسي @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-4">
                        <label for="filter_status" class="form-label">الحالة</label>
                        <select class="form-select" id="filter_status">
                            <option value="">جميع الحالات</option>
                            <option value="active">نشط</option>
                            <option value="inactive">غير نشط</option>
                        </select>
                    </div>

                    <div class="col-lg-1 col-md-3">
                        <label for="filter_per_page" class="form-label">العرض</label>
                        <select class="form-select" id="filter_per_page">
                            <option value="10">10</option>
                            <option value="15" selected>15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-5">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">بحث</button>
                            <button type="button" class="btn btn-outline-secondary" id="btnResetFilters" title="إعادة ضبط">
                                <i class="bi bi-arrow-counterclockwise"></i>
                                <span class="d-lg-none">إعادة</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- View Switcher --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
        <div class="department-tabs" role="tablist">
            <button type="button" class="department-tab active" id="tabList">
                <i class="bi bi-list-ul"></i>
                القائمة
            </button>
            <button type="button" class="department-tab" id="tabTree">
                <i class="bi bi-diagram-3"></i>
                الهيكل التنظيمي
            </button>
        </div>

        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnRefreshDepartments">
            <i class="bi bi-arrow-repeat"></i>
            تحديث
        </button>
    </div>

    {{-- List View --}}
    <div id="departmentListPane">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <strong>قائمة الإدارات والأقسام</strong>
                    <span class="badge bg-primary-subtle text-primary" id="departmentCountBadge">0 سجل</span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>الإدارة أو القسم</th>
                            <th>النوع</th>
                            <th>الفرع</th>
                            <th>يتبع إلى</th>
                            <th>الأقسام</th>
                            <th>المسميات</th>
                            <th>الحالة</th>
                            <th class="text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="departmentsTableBody">
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">جاري تحميل البيانات...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap gap-3">
                <small class="text-muted" id="paginationSummary"></small>
                <nav aria-label="صفحات الإدارات">
                    <ul class="pagination pagination-sm mb-0" id="departmentsPagination"></ul>
                </nav>
            </div>
        </div>
    </div>

    {{-- Tree View --}}
    <div id="departmentTreePane" class="d-none">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <div>
                    <strong>الهيكل التنظيمي</strong>
                    <div class="text-muted small mt-1">يعرض الإدارات الرئيسية وما يتبعها من أقسام بمستوياتها المختلفة</div>
                </div>
            </div>
            <div class="card-body p-4" id="departmentTreeContent">
                <div class="text-center text-muted py-5">اضغط على تبويب الهيكل التنظيمي لتحميل الشجرة.</div>
            </div>
        </div>
    </div>
</div>

{{-- Department Modal --}}
<div class="modal fade" id="departmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="departmentForm">
                @csrf
                <input type="hidden" id="department_id">

                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1" id="departmentModalTitle">إضافة إدارة أو قسم</h5>
                        <div class="text-muted small">الحقول التي تحمل علامة (*) إلزامية</div>
                    </div>
                    <button type="button" class="btn-close" data-department-modal-close aria-label="إغلاق"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="alert alert-danger d-none" id="departmentFormErrors"></div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="department_level" class="form-label">نوع السجل <span class="text-danger">*</span></label>
                            <select class="form-select" id="department_level">
                                <option value="root">إدارة رئيسية</option>
                                <option value="child">قسم تابع</option>
                            </select>
                            <div class="form-text">الإدارة الرئيسية مستقلة، أما القسم فيرتبط بإدارة أو قسم أعلى.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="department_branch_id" class="form-label">الفرع</label>
                            <select class="form-select" name="branch_id" id="department_branch_id">
                                <option value="">عام — جميع الفروع</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">
                                        {{ $branch->name }}
                                        @if($branch->is_main) — الفرع الرئيسي @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 d-none" id="parentDepartmentGroup">
                            <label for="department_parent_id" class="form-label">الإدارة أو القسم الأعلى <span class="text-danger">*</span></label>
                            <select class="form-select" name="parent_id" id="department_parent_id" disabled>
                                <option value="">اختر الإدارة أو القسم الأعلى</option>
                            </select>
                            <div class="form-text" id="parentDepartmentHelp">تظهر الخيارات التابعة للفرع المختار فقط.</div>
                        </div>

                        <div class="col-md-4">
                            <label for="department_code" class="form-label">الكود <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control"
                                name="code"
                                id="department_code"
                                maxlength="50"
                                dir="ltr"
                                placeholder="HR"
                                required
                            >
                        </div>

                        <div class="col-md-4">
                            <label for="department_name" class="form-label">الاسم بالعربية <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control"
                                name="name"
                                id="department_name"
                                maxlength="255"
                                placeholder="إدارة الموارد البشرية"
                                required
                            >
                        </div>

                        <div class="col-md-4">
                            <label for="department_name_en" class="form-label">الاسم بالإنجليزية</label>
                            <input
                                type="text"
                                class="form-control"
                                name="name_en"
                                id="department_name_en"
                                maxlength="255"
                                dir="ltr"
                                placeholder="Human Resources"
                            >
                        </div>

                        <div class="col-md-4">
                            <label for="department_sort_order" class="form-label">ترتيب العرض</label>
                            <input
                                type="number"
                                class="form-control"
                                name="sort_order"
                                id="department_sort_order"
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
                                        id="department_is_active"
                                        checked
                                    >
                                    <label class="form-check-label" for="department_is_active">الإدارة أو القسم نشط</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="department_description" class="form-label">الوصف</label>
                            <textarea
                                class="form-control"
                                name="description"
                                id="department_description"
                                rows="4"
                                placeholder="وصف مختصر لاختصاصات الإدارة أو القسم..."
                            ></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-department-modal-close>إلغاء</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveDepartment">
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
        data: @json(route('app.organization.departments.data')),
        options: @json(route('app.organization.departments.options')),
        tree: @json(route('app.organization.departments.tree')),
        store: @json(route('app.organization.departments.store')),
        show: @json(route('app.organization.departments.show', ['department' => '__DEPARTMENT__'])),
        update: @json(route('app.organization.departments.update', ['department' => '__DEPARTMENT__'])),
        destroy: @json(route('app.organization.departments.destroy', ['department' => '__DEPARTMENT__'])),
    };

    const permissions = {
        update: @json(auth()->user()->can('departments.update')),
        delete: @json(auth()->user()->can('departments.delete')),
    };

    const $departmentModal = $('#departmentModal');
    let currentPage = 1;
    let activeView = 'list';
    let searchTimer = null;
    let tableRequest = null;
    let treeRequest = null;

    $departmentModal.appendTo('body');

    function departmentUrl(url, id) {
        return url.replace('__DEPARTMENT__', id);
    }

    function escapeHtml(value) {
        return $('<div>').text(value ?? '').html();
    }

    function showDepartmentModal() {
        $('#departmentModalBackdrop').remove();

        $('<div>', {
            id: 'departmentModalBackdrop',
            class: 'modal-backdrop fade show',
        }).appendTo('body');

        $('body')
            .addClass('modal-open')
            .css('overflow', 'hidden');

        $departmentModal
            .attr({
                role: 'dialog',
                'aria-modal': 'true',
            })
            .removeAttr('aria-hidden')
            .css('display', 'block')
            .addClass('show');
    }

    function hideDepartmentModal() {
        $departmentModal
            .removeClass('show')
            .css('display', 'none')
            .attr('aria-hidden', 'true')
            .removeAttr('aria-modal role');

        $('#departmentModalBackdrop').remove();

        $('body')
            .removeClass('modal-open')
            .css('overflow', '');
    }

    function notifySuccess(message, reloadData = true) {
        if (window.Swal) {
            Swal.fire({
                icon: 'success',
                title: 'تمت العملية',
                text: message,
                timer: 1400,
                showConfirmButton: false,
            }).then(function () {
                if (reloadData) {
                    refreshActiveView();
                }
            });
        } else {
            alert(message);

            if (reloadData) {
                refreshActiveView();
            }
        }
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
            $('#departmentFormErrors')
                .removeClass('d-none')
                .html(escapeHtml(message).replace(/\n/g, '<br>'));

            $('#departmentModal .modal-body').scrollTop(0);
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

    function filters() {
        return {
            search: $.trim($('#filter_search').val()),
            branch_id: $('#filter_branch_id').val(),
            status: $('#filter_status').val(),
            per_page: $('#filter_per_page').val(),
            page: currentPage,
        };
    }

    function resetDepartmentForm() {
        $('#departmentForm').trigger('reset');
        $('#department_id').val('');
        $('#department_level').val('root');
        $('#department_branch_id').val('');
        $('#department_parent_id')
            .empty()
            .append('<option value="">اختر الإدارة أو القسم الأعلى</option>')
            .val('')
            .prop('disabled', true);
        $('#department_sort_order').val(0);
        $('#department_is_active').prop('checked', true);
        $('#parentDepartmentGroup').addClass('d-none');
        $('#departmentFormErrors').addClass('d-none').empty();
        $('#btnSaveDepartment').prop('disabled', false);
        $('#btnSaveDepartment .save-label').text('حفظ البيانات');
    }

    function toggleParentField() {
        const isChild = $('#department_level').val() === 'child';

        $('#parentDepartmentGroup').toggleClass('d-none', !isChild);
        $('#department_parent_id').prop('disabled', !isChild);

        if (!isChild) {
            $('#department_parent_id').val('');
        }
    }

    function loadParentOptions(selectedId = '', done = null) {
        const branchId = $('#department_branch_id').val();
        const excludeId = $('#department_id').val();
        const $select = $('#department_parent_id');

        $select
            .prop('disabled', true)
            .empty()
            .append('<option value="">جاري تحميل الخيارات...</option>');

        $.ajax({
            url: routes.options,
            type: 'GET',
            dataType: 'json',
            data: {
                branch_id: branchId,
                exclude_id: excludeId,
            },
            headers: { Accept: 'application/json' },
            success: function (response) {
                $select
                    .empty()
                    .append('<option value="">اختر الإدارة أو القسم الأعلى</option>');

                $.each(response.departments || [], function (index, item) {
                    const prefix = item.parent_id ? '— ' : '';

                    $('<option>', {
                        value: item.id,
                        text: prefix + item.name + ' (' + item.code + ')',
                    }).appendTo($select);
                });

                $select.val(selectedId ? String(selectedId) : '');
            },
            error: function (xhr) {
                $select
                    .empty()
                    .append('<option value="">تعذر تحميل الإدارات</option>');

                showRequestError(xhr, true);
            },
            complete: function () {
                toggleParentField();

                if ($.isFunction(done)) {
                    done();
                }
            },
        });
    }

    function statusBadge(isActive) {
        return isActive
            ? '<span class="badge bg-success-subtle text-success">نشط</span>'
            : '<span class="badge bg-danger-subtle text-danger">غير نشط</span>';
    }

    function renderActions(item) {
        const buttons = [];

        if (permissions.update) {
            buttons.push(
                '<button type="button" class="btn btn-sm btn-outline-primary btn-edit-department" ' +
                'data-id="' + item.id + '" title="تعديل">' +
                '<i class="bi bi-pencil"></i><span class="visually-hidden">تعديل</span></button>'
            );
        }

        if (permissions.delete) {
            buttons.push(
                '<button type="button" class="btn btn-sm btn-outline-danger btn-archive-department" ' +
                'data-id="' + item.id + '" data-name="' + escapeHtml(item.name) + '" title="أرشفة">' +
                '<i class="bi bi-archive"></i><span class="visually-hidden">أرشفة</span></button>'
            );
        }

        if (!buttons.length) {
            return '<span class="text-muted">—</span>';
        }

        return '<div class="btn-group btn-group-sm">' + buttons.join('') + '</div>';
    }

    function renderTable(response) {
        const rows = response.data || [];
        const $tbody = $('#departmentsTableBody');

        $('#totalDepartments').text(response.total ?? 0);
        $('#departmentCountBadge').text((response.total ?? 0) + ' سجل');
        $('#pageRootDepartments').text(
            rows.filter(function (item) { return !item.parent_id; }).length
        );
        $('#pageChildDepartments').text(
            rows.filter(function (item) { return !!item.parent_id; }).length
        );

        if (!rows.length) {
            $tbody.html(
                '<tr><td colspan="9" class="text-center py-5">' +
                '<div class="text-muted mb-2"><i class="bi bi-inboxes fs-3"></i></div>' +
                '<div class="fw-semibold">لا توجد إدارات أو أقسام مطابقة</div>' +
                '<small class="text-muted">غيّر معايير البحث أو أضف سجلًا جديدًا.</small>' +
                '</td></tr>'
            );

            renderPagination(response);
            return;
        }

        let html = '';

        $.each(rows, function (index, item) {
            const number = (response.from || 1) + index;
            const type = item.parent_id
                ? '<span class="badge bg-info-subtle text-info">قسم</span>'
                : '<span class="badge bg-primary-subtle text-primary">إدارة</span>';
            const branch = item.branch
                ? escapeHtml(item.branch.name)
                : '<span class="text-muted">عام</span>';
            const parent = item.parent
                ? escapeHtml(item.parent.name)
                : '<span class="text-muted">—</span>';

            html += '<tr>' +
                '<td>' + number + '</td>' +
                '<td class="department-name-cell">' +
                    '<div class="fw-semibold">' + escapeHtml(item.name) + '</div>' +
                    (item.name_en
                        ? '<small class="text-muted" dir="ltr">' + escapeHtml(item.name_en) + '</small><br>'
                        : '') +
                    '<span class="department-code text-muted">' + escapeHtml(item.code) + '</span>' +
                '</td>' +
                '<td>' + type + '</td>' +
                '<td>' + branch + '</td>' +
                '<td>' + parent + '</td>' +
                '<td><span class="badge bg-light text-dark border">' + (item.children_count || 0) + '</span></td>' +
                '<td><span class="badge bg-light text-dark border">' + (item.job_titles_count || 0) + '</span></td>' +
                '<td>' + statusBadge(item.is_active) + '</td>' +
                '<td class="text-center">' + renderActions(item) + '</td>' +
            '</tr>';
        });

        $tbody.html(html);
        renderPagination(response);
    }

    function renderPagination(response) {
        const current = Number(response.current_page || 1);
        const last = Number(response.last_page || 1);
        const $pagination = $('#departmentsPagination');

        $('#paginationSummary').text(
            response.total
                ? 'عرض ' + response.from + ' إلى ' + response.to + ' من أصل ' + response.total
                : 'لا توجد نتائج'
        );

        $pagination.empty();

        if (last <= 1) {
            return;
        }

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

    function loadDepartments(page = 1) {
        currentPage = page;

        if (tableRequest) {
            tableRequest.abort();
        }

        $('#departmentsTableBody').html(
            '<tr><td colspan="9" class="text-center text-muted py-5">' +
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

                $('#departmentsTableBody').html(
                    '<tr><td colspan="9" class="text-center text-danger py-5">تعذر تحميل البيانات.</td></tr>'
                );
                showRequestError(xhr);
            },
            complete: function () {
                tableRequest = null;
            },
        });
    }

    function renderTreeNodes(nodes, inheritedBranchName = '') {
        if (!nodes || !nodes.length) return '';

        let html = '<ul class="org-tree">';

        $.each(nodes, function (index, node) {
            const children = node.children_recursive || [];
            const branchName = node.branch?.name
                ? escapeHtml(node.branch.name)
                : (inheritedBranchName || 'عام — جميع الفروع');

            html += '<li class="org-tree-node">' +
                '<div class="org-tree-card">' +
                    '<div class="d-flex align-items-center gap-3">' +
                        '<span class="stat-icon"><i class="bi bi-' + (node.parent_id ? 'folder2' : 'building') + '"></i></span>' +
                        '<div>' +
                            '<div class="d-flex align-items-center gap-2 flex-wrap">' +
                                '<span class="fw-semibold">' + escapeHtml(node.name) + '</span>' +
                                '<span class="badge bg-light text-dark border department-code">' + escapeHtml(node.code) + '</span>' +
                                statusBadge(node.is_active) +
                            '</div>' +
                            '<small class="text-muted">' + branchName + ' · ' + children.length + ' قسم مباشر</small>' +
                        '</div>' +
                    '</div>' +
                    (permissions.update
                        ? '<button type="button" class="btn btn-sm btn-outline-primary btn-edit-department" data-id="' + node.id + '">' +
                          '<i class="bi bi-pencil"></i> تعديل</button>'
                        : '') +
                '</div>' +
                (children.length ? renderTreeNodes(children, branchName) : '') +
            '</li>';
        });

        html += '</ul>';
        return html;
    }

    function loadDepartmentTree() {
        if (treeRequest) {
            treeRequest.abort();
        }

        $('#departmentTreeContent').html(
            '<div class="text-center text-muted py-5">' +
            '<span class="spinner-border spinner-border-sm ms-2"></span>جاري بناء الهيكل التنظيمي...</div>'
        );

        treeRequest = $.ajax({
            url: routes.tree,
            type: 'GET',
            dataType: 'json',
            data: { branch_id: $('#filter_branch_id').val() },
            headers: { Accept: 'application/json' },
            success: function (response) {
                const departments = response.departments || [];

                $('#departmentTreeContent').html(
                    departments.length
                        ? renderTreeNodes(departments)
                        : '<div class="text-center text-muted py-5">لا يوجد هيكل تنظيمي لهذا الفرع.</div>'
                );
            },
            error: function (xhr, status) {
                if (status === 'abort') return;

                $('#departmentTreeContent').html(
                    '<div class="text-center text-danger py-5">تعذر تحميل الهيكل التنظيمي.</div>'
                );
                showRequestError(xhr);
            },
            complete: function () {
                treeRequest = null;
            },
        });
    }

    function refreshActiveView() {
        if (activeView === 'tree') {
            loadDepartmentTree();
        } else {
            loadDepartments(currentPage);
        }
    }

    $(document).on('click', '[data-department-modal-close]', hideDepartmentModal);

    $(document).on('keydown', function (event) {
        if (event.key === 'Escape' && $departmentModal.hasClass('show')) {
            hideDepartmentModal();
        }
    });

    $('#departmentFilters').on('submit', function (event) {
        event.preventDefault();

        if (activeView === 'tree') {
            loadDepartmentTree();
        } else {
            loadDepartments(1);
        }
    });

    $('#filter_search').on('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            if (activeView === 'list') loadDepartments(1);
        }, 450);
    });

    $('#filter_branch_id, #filter_status, #filter_per_page').on('change', function () {
        if (activeView === 'tree') {
            loadDepartmentTree();
        } else {
            loadDepartments(1);
        }
    });

    $('#btnResetFilters').on('click', function () {
        $('#departmentFilters').trigger('reset');
        currentPage = 1;
        refreshActiveView();
    });

    $('#btnRefreshDepartments').on('click', refreshActiveView);

    $(document).on('click', '#departmentsPagination .page-link', function () {
        const page = Number($(this).data('page'));
        if (page > 0) loadDepartments(page);
    });

    $('#tabList').on('click', function () {
        activeView = 'list';
        $('.department-tab').removeClass('active');
        $(this).addClass('active');
        $('#departmentTreePane').addClass('d-none');
        $('#departmentListPane').removeClass('d-none');
        loadDepartments(currentPage);
    });

    $('#tabTree').on('click', function () {
        activeView = 'tree';
        $('.department-tab').removeClass('active');
        $(this).addClass('active');
        $('#departmentListPane').addClass('d-none');
        $('#departmentTreePane').removeClass('d-none');
        loadDepartmentTree();
    });

    $('#department_level').on('change', function () {
        toggleParentField();

        if ($(this).val() === 'child') {
            loadParentOptions();
        }
    });

    $('#department_branch_id').on('change', function () {
        if ($('#department_level').val() === 'child') {
            loadParentOptions();
        }
    });

    $('#btnAddDepartment').on('click', function () {
        resetDepartmentForm();
        $('#departmentModalTitle').text('إضافة إدارة أو قسم');
        showDepartmentModal();
        window.setTimeout(function () { $('#department_code').trigger('focus'); }, 100);
    });

    $(document).on('click', '.btn-edit-department', function () {
        const id = $(this).data('id');
        const $button = $(this);

        $button.prop('disabled', true);
        resetDepartmentForm();

        $.ajax({
            url: departmentUrl(routes.show, id),
            type: 'GET',
            dataType: 'json',
            headers: { Accept: 'application/json' },
            success: function (response) {
                const item = response.department;

                $('#department_id').val(item.id);
                $('#department_level').val(item.parent_id ? 'child' : 'root');
                $('#department_branch_id').val(item.branch_id || '');
                $('#department_code').val(item.code || '');
                $('#department_name').val(item.name || '');
                $('#department_name_en').val(item.name_en || '');
                $('#department_sort_order').val(item.sort_order ?? 0);
                $('#department_is_active').prop('checked', Boolean(item.is_active));
                $('#department_description').val(item.description || '');
                $('#departmentModalTitle').text('تعديل: ' + item.name);

                toggleParentField();

                if (item.parent_id) {
                    loadParentOptions(item.parent_id, showDepartmentModal);
                } else {
                    showDepartmentModal();
                }
            },
            error: function (xhr) {
                showRequestError(xhr);
            },
            complete: function () {
                $button.prop('disabled', false);
            },
        });
    });

    $('#departmentForm').on('submit', function (event) {
        event.preventDefault();

        $('#departmentFormErrors').addClass('d-none').empty();

        if (
            $('#department_level').val() === 'child' &&
            !$('#department_parent_id').val()
        ) {
            $('#departmentFormErrors')
                .removeClass('d-none')
                .text('يجب اختيار الإدارة أو القسم الأعلى.');
            return;
        }

        const id = $('#department_id').val();
        const isEdit = id !== '';
        const data = $(this).serializeArray();

        if ($('#department_level').val() === 'root') {
            data.push({ name: 'parent_id', value: '' });
        }

        if (isEdit) {
            data.push({ name: '_method', value: 'PUT' });
        }

        const $button = $('#btnSaveDepartment');

        $button.prop('disabled', true);
        $button.find('.save-label').text('جاري الحفظ...');

        $.ajax({
            url: isEdit ? departmentUrl(routes.update, id) : routes.store,
            type: 'POST',
            data: $.param(data),
            dataType: 'json',
            headers: { Accept: 'application/json' },
            success: function (response) {
                hideDepartmentModal();
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

    $(document).on('click', '.btn-archive-department', function () {
        const id = $(this).data('id');
        const name = $(this).data('name');

        function executeArchive() {
            $.ajax({
                url: departmentUrl(routes.destroy, id),
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
                title: 'أرشفة الإدارة أو القسم؟',
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

    loadDepartments();
});
</script>

@endpush