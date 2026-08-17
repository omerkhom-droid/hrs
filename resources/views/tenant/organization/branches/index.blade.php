@extends('layouts.tenant')

@section('title', 'الفروع')
@section('page-title', 'إدارة الفروع')

@section('content')

<style>
    #branchModal {
        overflow: hidden !important;
    }

    #branchModal .modal-dialog {
        width: calc(100% - 30px);
        max-width: 900px;
        height: calc(100vh - 30px);
        margin: 15px auto;
    }

    #branchModal .modal-content {
        height: 100%;
        overflow: hidden;
    }

    #branchModal #branchForm {
        display: flex;
        flex-direction: column;
        height: 100%;
        min-height: 0;
    }

    #branchModal .modal-header,
    #branchModal .modal-footer {
        flex-shrink: 0;
        background: #fff;
    }

    #branchModal .modal-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto !important;
        overflow-x: hidden;
    }

    @media (max-width: 576px) {
        #branchModal .modal-dialog {
            width: calc(100% - 10px);
            height: calc(100vh - 10px);
            margin: 5px auto;
        }
    }
</style>


<div class="container-fluid p-0" dir="rtl">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

        <div>
            <h4 class="mb-1">الفروع</h4>

            <div class="text-muted">
                إدارة فروع الشركة ومواقعها وبيانات التواصل
            </div>
        </div>


        @can('branches.create')

            <button
                type="button"
                class="btn btn-primary px-4"
                id="btnAddBranch">

                + إضافة فرع

            </button>

        @endcan

    </div>


    {{-- الفلاتر --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">

        <div class="card-body">

            <form id="branchSearchForm">

                <div class="row g-3">

                    <div class="col-lg-6">

                        <label class="form-label">
                            البحث
                        </label>

                        <input
                            type="search"
                            id="branchSearch"
                            class="form-control"
                            placeholder="اسم الفرع، الكود، المدينة، البريد...">

                    </div>


                    <div class="col-lg-3">

                        <label class="form-label">
                            الحالة
                        </label>

                        <select
                            id="branchStatusFilter"
                            class="form-select">

                            <option value="">
                                جميع الحالات
                            </option>

                            <option value="active">
                                نشط
                            </option>

                            <option value="inactive">
                                غير نشط
                            </option>

                        </select>

                    </div>


                    <div class="col-lg-3 d-flex align-items-end gap-2">

                        <button
                            type="submit"
                            class="btn btn-primary flex-grow-1">

                            بحث

                        </button>

                        <button
                            type="button"
                            class="btn btn-light border"
                            id="btnResetBranchFilters">

                            إعادة

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>


    {{-- الجدول --}}
    <div class="card border-0 shadow-sm rounded-4">

        <div class="card-header bg-white border-0 px-4 py-3">

            <div class="d-flex justify-content-between align-items-center">

                <h6 class="mb-0">
                    قائمة الفروع
                </h6>


                <div class="d-flex align-items-center gap-3">

                    <span
                        class="badge bg-primary-subtle text-primary"
                        id="totalBranches">

                        0 فرع

                    </span>


                    <select
                        id="branchPerPage"
                        class="form-select form-select-sm"
                        style="width: auto">

                        <option value="10">10</option>
                        <option value="15" selected>15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>

                    </select>

                </div>

            </div>

        </div>


        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead class="table-light">

                <tr>
                    <th class="text-center">#</th>
                    <th>الكود</th>
                    <th>الفرع</th>
                    <th>الموقع</th>
                    <th>التواصل</th>
                    <th class="text-center">الارتباطات</th>
                    <th class="text-center">الحالة</th>
                    <th class="text-center">الإجراءات</th>
                </tr>

                </thead>

                <tbody id="branchesTableBody">

                <tr>
                    <td
                        colspan="8"
                        class="text-center py-5 text-muted">

                        جاري تحميل البيانات...

                    </td>
                </tr>

                </tbody>

            </table>

        </div>


        <div class="card-footer bg-white border-0 px-4 py-3">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                <div
                    class="small text-muted"
                    id="branchPaginationInfo">
                </div>

                <ul
                    class="pagination pagination-sm mb-0"
                    id="branchPagination">
                </ul>

            </div>

        </div>

    </div>

</div>


{{-- مودال الفرع --}}
<div
    class="modal"
    id="branchModal"
    tabindex="-1"
    aria-hidden="true"
    dir="rtl">

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content border-0 rounded-4 shadow">

            <form id="branchForm">

                @csrf

                <input
                    type="hidden"
                    id="branchId">


                <div class="modal-header">

                    <h5
                        class="modal-title"
                        id="branchModalTitle">

                        إضافة فرع

                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-close-branch-modal>
                    </button>

                </div>


                <div class="modal-body bg-light p-4">

                    <div
                        class="alert alert-danger d-none"
                        id="branchFormErrors">
                    </div>


                    <div class="card border-0 rounded-4 mb-4">

                        <div class="card-body">

                            <h6 class="mb-3">
                                المعلومات الأساسية
                            </h6>


                            <div class="row g-3">

                                <div class="col-md-4">

                                    <label class="form-label">
                                        كود الفرع
                                        <span class="text-danger">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        name="code"
                                        id="branchCode"
                                        class="form-control"
                                        dir="ltr"
                                        placeholder="BR001"
                                        required>

                                </div>


                                <div class="col-md-8">

                                    <label class="form-label">
                                        اسم الفرع
                                        <span class="text-danger">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        name="name"
                                        id="branchName"
                                        class="form-control"
                                        required>

                                </div>


                                <div class="col-md-6">

                                    <label class="form-label">
                                        الاسم بالإنجليزية
                                    </label>

                                    <input
                                        type="text"
                                        name="name_en"
                                        id="branchNameEn"
                                        class="form-control"
                                        dir="ltr">

                                </div>


                                <div class="col-md-3">

                                    <input
                                        type="hidden"
                                        name="is_main"
                                        value="0">

                                    <div class="form-check form-switch mt-4 pt-2">

                                        <input
                                            type="checkbox"
                                            name="is_main"
                                            id="branchIsMain"
                                            class="form-check-input"
                                            value="1">

                                        <label
                                            class="form-check-label"
                                            for="branchIsMain">

                                            الفرع الرئيسي

                                        </label>

                                    </div>

                                </div>


                                <div class="col-md-3">

                                    <input
                                        type="hidden"
                                        name="is_active"
                                        value="0">

                                    <div class="form-check form-switch mt-4 pt-2">

                                        <input
                                            type="checkbox"
                                            name="is_active"
                                            id="branchIsActive"
                                            class="form-check-input"
                                            value="1"
                                            checked>

                                        <label
                                            class="form-check-label"
                                            for="branchIsActive">

                                            فرع نشط

                                        </label>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    <div class="card border-0 rounded-4 mb-4">

                        <div class="card-body">

                            <h6 class="mb-3">
                                بيانات الموقع
                            </h6>


                            <div class="row g-3">

                                <div class="col-md-4">

                                    <label class="form-label">
                                        الدولة
                                    </label>

                                    <select
                                        name="country_code"
                                        id="branchCountryCode"
                                        class="form-select">

                                        <option value="SA">السعودية</option>
                                        <option value="AE">الإمارات</option>
                                        <option value="BH">البحرين</option>
                                        <option value="KW">الكويت</option>
                                        <option value="OM">عُمان</option>
                                        <option value="QA">قطر</option>

                                    </select>

                                </div>


                                <div class="col-md-4">

                                    <label class="form-label">
                                        المدينة
                                    </label>

                                    <input
                                        type="text"
                                        name="city"
                                        id="branchCity"
                                        class="form-control">

                                </div>


                                <div class="col-md-4">

                                    <label class="form-label">
                                        المنطقة الزمنية
                                    </label>

                                    <select
                                        name="timezone"
                                        id="branchTimezone"
                                        class="form-select">

                                        <option value="Asia/Riyadh">الرياض</option>
                                        <option value="Asia/Dubai">دبي</option>
                                        <option value="Asia/Kuwait">الكويت</option>
                                        <option value="Asia/Bahrain">البحرين</option>
                                        <option value="Asia/Qatar">قطر</option>
                                        <option value="Asia/Muscat">مسقط</option>

                                    </select>

                                </div>


                                <div class="col-12">

                                    <label class="form-label">
                                        العنوان
                                    </label>

                                    <textarea
                                        name="address"
                                        id="branchAddress"
                                        class="form-control"
                                        rows="3"></textarea>

                                </div>

                            </div>

                        </div>

                    </div>


                    <div class="card border-0 rounded-4">

                        <div class="card-body">

                            <h6 class="mb-3">
                                بيانات التواصل
                            </h6>


                            <div class="row g-3">

                                <div class="col-md-6">

                                    <label class="form-label">
                                        البريد الإلكتروني
                                    </label>

                                    <input
                                        type="email"
                                        name="email"
                                        id="branchEmail"
                                        class="form-control"
                                        dir="ltr">

                                </div>


                                <div class="col-md-6">

                                    <label class="form-label">
                                        رقم التواصل
                                    </label>

                                    <input
                                        type="text"
                                        name="phone"
                                        id="branchPhone"
                                        class="form-control"
                                        dir="ltr">

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-light"
                        data-close-branch-modal>

                        إلغاء

                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary px-4"
                        id="btnSaveBranch">

                        حفظ الفرع

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@endsection


@push('scripts')

<script>
$(function () {

    var currentPage = 1;
    var editingBranchId = null;
    var searchTimer = null;

    var permissions = {
        update: @json(auth()->user()->can('branches.update')),
        delete: @json(auth()->user()->can('branches.delete'))
    };

    var urls = {
        data: @json(route('app.organization.branches.data')),
        store: @json(route('app.organization.branches.store')),

        show: @json(
            route(
                'app.organization.branches.show',
                ['branch' => '__BRANCH__']
            )
        ),

        destroy: @json(
            route(
                'app.organization.branches.destroy',
                ['branch' => '__BRANCH__']
            )
        )
    };


    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN':
                $('meta[name="csrf-token"]').attr('content'),

            'Accept':
                'application/json'
        }
    });


    function branchUrl(template, id)
    {
        return template.replace(
            '__BRANCH__',
            id
        );
    }


    function escapeHtml(value)
    {
        return $('<div>')
            .text(
                value == null
                    ? ''
                    : value
            )
            .html();
    }


    /*
    |--------------------------------------------------------------------------
    | Modal
    |--------------------------------------------------------------------------
    */

    function openBranchModal()
    {
        $('#branchModal')
            .css('display', 'block')
            .addClass('show')
            .attr('aria-modal', 'true')
            .removeAttr('aria-hidden');

        $('body')
            .addClass('modal-open')
            .css('overflow', 'hidden');

        if (!$('#branchModalBackdrop').length) {
            $('<div>', {
                id: 'branchModalBackdrop',
                class: 'modal-backdrop fade show'
            }).appendTo('body');
        }
    }


    function closeBranchModal()
    {
        $('#branchModal')
            .removeClass('show')
            .css('display', 'none')
            .attr('aria-hidden', 'true')
            .removeAttr('aria-modal');

        $('#branchModalBackdrop').remove();

        $('body')
            .removeClass('modal-open')
            .css('overflow', '');
    }


    $(document).on(
        'click',
        '[data-close-branch-modal], #branchModalBackdrop',
        function () {
            closeBranchModal();
        }
    );


    $(document).on(
        'keyup',
        function (event) {
            if (
                event.key === 'Escape' &&
                $('#branchModal').hasClass('show')
            ) {
                closeBranchModal();
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Form
    |--------------------------------------------------------------------------
    */

    function resetBranchForm()
    {
        $('#branchForm').trigger('reset');

        $('#branchId').val('');
        $('#branchCountryCode').val('SA');
        $('#branchTimezone').val('Asia/Riyadh');

        $('#branchIsMain').prop('checked', false);
        $('#branchIsActive').prop('checked', true);

        clearErrors();
    }


    function clearErrors()
    {
        $('#branchFormErrors')
            .addClass('d-none')
            .empty();

        $('#branchForm .is-invalid')
            .removeClass('is-invalid');
    }


    function displayErrors(errors)
    {
        var box = $('#branchFormErrors');

        box.empty();

        $.each(errors, function (field, messages) {
            $('#branchForm')
                .find('[name="' + field + '"]')
                .addClass('is-invalid');

            $.each(messages, function (index, message) {
                $('<div>')
                    .text(message)
                    .appendTo(box);
            });
        });

        box.removeClass('d-none');

        $('#branchModal .modal-body')
            .stop(true)
            .animate(
                {
                    scrollTop: 0
                },
                200
            );
    }


    function showAjaxError(xhr, fallback)
    {
        Swal.fire({
            icon: 'error',
            title: 'تعذر تنفيذ العملية',
            text:
                xhr.responseJSON &&
                xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : fallback
        });
    }


    /*
    |--------------------------------------------------------------------------
    | Badges
    |--------------------------------------------------------------------------
    */

    function statusBadge(isActive)
    {
        if (isActive) {
            return `
                <span class="badge bg-success-subtle text-success px-3 py-2">
                    نشط
                </span>
            `;
        }

        return `
            <span class="badge bg-secondary-subtle text-secondary px-3 py-2">
                غير نشط
            </span>
        `;
    }


    /*
    |--------------------------------------------------------------------------
    | Load Data
    |--------------------------------------------------------------------------
    */

    function loadBranches(page)
    {
        currentPage = page || 1;

        $('#branchesTableBody').html(`
            <tr>
                <td
                    colspan="8"
                    class="text-center py-5 text-muted">

                    جاري تحميل البيانات...

                </td>
            </tr>
        `);


        $.ajax({
            url: urls.data,
            type: 'GET',

            data: {
                page: currentPage,
                search: $('#branchSearch').val(),
                status: $('#branchStatusFilter').val(),
                per_page: $('#branchPerPage').val()
            },

            success: function (response) {
                if (
                    !response.data.length &&
                    currentPage > 1
                ) {
                    loadBranches(currentPage - 1);

                    return;
                }

                renderTable(response);
                renderPagination(response);

                $('#totalBranches').text(
                    response.total + ' فرع'
                );
            },

            error: function (xhr) {
                $('#branchesTableBody').html(`
                    <tr>
                        <td
                            colspan="8"
                            class="text-center py-5 text-danger">

                            تعذر تحميل الفروع.

                        </td>
                    </tr>
                `);

                showAjaxError(
                    xhr,
                    'تعذر تحميل بيانات الفروع.'
                );
            }
        });
    }


    /*
    |--------------------------------------------------------------------------
    | Render Table
    |--------------------------------------------------------------------------
    */

    function renderTable(response)
    {
        if (!response.data.length) {
            $('#branchesTableBody').html(`
                <tr>
                    <td
                        colspan="8"
                        class="text-center py-5 text-muted">

                        لا توجد فروع مطابقة.

                    </td>
                </tr>
            `);

            return;
        }


        var html = '';


        $.each(response.data, function (index, branch) {
            var rowNumber =
                (response.from || 1) + index;

            var mainBadge =
                branch.is_main
                    ? `
                        <span class="badge bg-primary-subtle text-primary me-2">
                            رئيسي
                        </span>
                    `
                    : '';

            var city =
                branch.city
                    ? escapeHtml(branch.city)
                    : '-';

            var contact =
                branch.email || branch.phone
                    ? `
                        <div class="small">
                            ${escapeHtml(branch.email || '')}
                        </div>

                        <div class="small text-muted" dir="ltr">
                            ${escapeHtml(branch.phone || '')}
                        </div>
                    `
                    : '-';

            var actions = '';


            if (permissions.update) {
                actions += `
                    <button
                        type="button"
                        class="btn btn-sm btn-light border btn-edit-branch"
                        data-id="${branch.id}">

                        تعديل

                    </button>
                `;
            }


            if (
                permissions.delete &&
                !branch.is_main
            ) {
                actions += `
                    <button
                        type="button"
                        class="btn btn-sm btn-light border text-danger btn-delete-branch"
                        data-id="${branch.id}"
                        data-name="${escapeHtml(branch.name)}">

                        أرشفة

                    </button>
                `;
            }


            if (!actions) {
                actions = `
                    <span class="text-muted">
                        -
                    </span>
                `;
            }


            html += `
                <tr>

                    <td class="text-center text-muted">
                        ${rowNumber}
                    </td>

                    <td dir="ltr">
                        ${escapeHtml(branch.code)}
                    </td>

                    <td>
                        <div class="fw-semibold">
                            ${escapeHtml(branch.name)}
                            ${mainBadge}
                        </div>

                        <small class="text-muted">
                            ${escapeHtml(branch.name_en || '')}
                        </small>
                    </td>

                    <td>
                        <div>${city}</div>

                        <small class="text-muted">
                            ${escapeHtml(branch.country_code || '')}
                        </small>
                    </td>

                    <td>
                        ${contact}
                    </td>

                    <td class="text-center">
                        <div class="small">
                            الإدارات:
                            ${branch.departments_count || 0}
                        </div>

                        <div class="small text-muted">
                            المواقع:
                            ${branch.work_locations_count || 0}
                        </div>
                    </td>

                    <td class="text-center">
                        ${statusBadge(branch.is_active)}
                    </td>

                    <td class="text-center">
                        <div class="d-flex justify-content-center gap-2">
                            ${actions}
                        </div>
                    </td>

                </tr>
            `;
        });


        $('#branchesTableBody').html(html);
    }


    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    function renderPagination(response)
    {
        var current = response.current_page;
        var last = response.last_page;
        var html = '';


        if (last > 1) {
            html += `
                <li class="page-item ${current === 1 ? 'disabled' : ''}">
                    <button
                        type="button"
                        class="page-link branch-page-button"
                        data-page="${current - 1}">

                        السابق

                    </button>
                </li>
            `;


            var start = Math.max(
                1,
                current - 2
            );

            var end = Math.min(
                last,
                current + 2
            );


            for (
                var page = start;
                page <= end;
                page++
            ) {
                html += `
                    <li class="page-item ${page === current ? 'active' : ''}">
                        <button
                            type="button"
                            class="page-link branch-page-button"
                            data-page="${page}">

                            ${page}

                        </button>
                    </li>
                `;
            }


            html += `
                <li class="page-item ${current === last ? 'disabled' : ''}">
                    <button
                        type="button"
                        class="page-link branch-page-button"
                        data-page="${current + 1}">

                        التالي

                    </button>
                </li>
            `;
        }


        $('#branchPagination').html(html);


        if (response.total > 0) {
            $('#branchPaginationInfo').text(
                'عرض ' +
                response.from +
                ' إلى ' +
                response.to +
                ' من ' +
                response.total
            );
        } else {
            $('#branchPaginationInfo').text(
                'لا توجد سجلات'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Add
    |--------------------------------------------------------------------------
    */

    $('#btnAddBranch').on(
        'click',
        function () {
            editingBranchId = null;

            resetBranchForm();

            $('#branchModalTitle').text(
                'إضافة فرع جديد'
            );

            $('#btnSaveBranch').text(
                'حفظ الفرع'
            );

            openBranchModal();
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'click',
        '.btn-edit-branch',
        function () {
            var id = $(this).data('id');

            clearErrors();


            $.ajax({
                url: branchUrl(
                    urls.show,
                    id
                ),

                type: 'GET',

                success: function (response) {
                    var branch = response.branch;

                    editingBranchId = branch.id;

                    resetBranchForm();

                    $('#branchId').val(branch.id);
                    $('#branchCode').val(branch.code || '');
                    $('#branchName').val(branch.name || '');
                    $('#branchNameEn').val(branch.name_en || '');
                    $('#branchEmail').val(branch.email || '');
                    $('#branchPhone').val(branch.phone || '');
                    $('#branchCountryCode').val(branch.country_code || 'SA');
                    $('#branchCity').val(branch.city || '');
                    $('#branchAddress').val(branch.address || '');
                    $('#branchTimezone').val(branch.timezone || 'Asia/Riyadh');

                    $('#branchIsMain').prop(
                        'checked',
                        Boolean(branch.is_main)
                    );

                    $('#branchIsActive').prop(
                        'checked',
                        Boolean(branch.is_active)
                    );

                    $('#branchModalTitle').text(
                        'تعديل بيانات الفرع'
                    );

                    $('#btnSaveBranch').text(
                        'حفظ التعديلات'
                    );

                    openBranchModal();
                },

                error: function (xhr) {
                    showAjaxError(
                        xhr,
                        'تعذر تحميل بيانات الفرع.'
                    );
                }
            });
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Save
    |--------------------------------------------------------------------------
    */

    $('#branchForm').on(
        'submit',
        function (event) {
            event.preventDefault();

            clearErrors();


            var isCreating =
                !editingBranchId;

            var url =
                isCreating
                    ? urls.store
                    : branchUrl(
                        urls.show,
                        editingBranchId
                    );

            var data =
                $(this).serialize();


            if (!isCreating) {
                data += '&_method=PUT';
            }


            var button =
                $('#btnSaveBranch');

            var originalText =
                button.text();


            button
                .prop('disabled', true)
                .text('جاري الحفظ...');


            $.ajax({
                url: url,
                type: 'POST',
                data: data,

                success: function (response) {
                    closeBranchModal();

                    loadBranches(
                        isCreating
                            ? 1
                            : currentPage
                    );

                    Swal.fire({
                        icon: 'success',
                        title: 'تم',
                        text: response.message,
                        timer: 1600,
                        showConfirmButton: false
                    });
                },

                error: function (xhr) {
                    if (
                        xhr.status === 422 &&
                        xhr.responseJSON &&
                        xhr.responseJSON.errors
                    ) {
                        displayErrors(
                            xhr.responseJSON.errors
                        );

                        return;
                    }

                    showAjaxError(
                        xhr,
                        'تعذر حفظ بيانات الفرع.'
                    );
                },

                complete: function () {
                    button
                        .prop('disabled', false)
                        .text(originalText);
                }
            });
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'click',
        '.btn-delete-branch',
        function () {
            var id = $(this).data('id');
            var name = $(this).data('name');


            Swal.fire({
                icon: 'warning',
                title: 'أرشفة الفرع؟',

                html:
                    'هل أنت متأكد من أرشفة الفرع؟' +
                    '<br><strong>' +
                    escapeHtml(name) +
                    '</strong>',

                showCancelButton: true,
                confirmButtonText: 'نعم، أرشفة',
                cancelButtonText: 'إلغاء',
                confirmButtonColor: '#dc3545'

            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }


                $.ajax({
                    url: branchUrl(
                        urls.destroy,
                        id
                    ),

                    type: 'DELETE',

                    success: function (response) {
                        loadBranches(currentPage);

                        Swal.fire({
                            icon: 'success',
                            title: 'تم',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    },

                    error: function (xhr) {
                        showAjaxError(
                            xhr,
                            'تعذر أرشفة الفرع.'
                        );
                    }
                });
            });
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    */

    $('#branchSearchForm').on(
        'submit',
        function (event) {
            event.preventDefault();

            loadBranches(1);
        }
    );


    $('#branchSearch').on(
        'input',
        function () {
            clearTimeout(searchTimer);

            searchTimer = setTimeout(
                function () {
                    loadBranches(1);
                },
                350
            );
        }
    );


    $('#branchStatusFilter, #branchPerPage').on(
        'change',
        function () {
            loadBranches(1);
        }
    );


    $('#btnResetBranchFilters').on(
        'click',
        function () {
            $('#branchSearch').val('');
            $('#branchStatusFilter').val('');
            $('#branchPerPage').val('15');

            loadBranches(1);
        }
    );


    $(document).on(
        'click',
        '.branch-page-button',
        function () {
            if (
                $(this)
                    .closest('.page-item')
                    .hasClass('disabled')
            ) {
                return;
            }

            loadBranches(
                Number(
                    $(this).data('page')
                )
            );
        }
    );


    loadBranches(1);

});
</script>

@endpush