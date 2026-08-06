@extends('layouts.system')

@section('title', 'العملاء')
@section('page-title', 'إدارة العملاء')

@section('content')

<div class="container-fluid p-0">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

        <div>
            <h4 class="mb-1">عملاء المنصة</h4>

            <div class="text-muted">
                إدارة حسابات العملاء في منصة رؤية يوم
            </div>
        </div>

        <button type="button"
                class="btn btn-primary px-4"
                id="btnAddTenant">

            + إضافة عميل

        </button>

    </div>


    {{-- Filters --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">

        <div class="card-body">

            <div class="row g-3">

                <div class="col-lg-5">

                    <label class="form-label">
                        البحث
                    </label>

                    <input type="text"
                           id="tenantSearch"
                           class="form-control"
                           placeholder="الاسم، الكود، البريد، الجوال...">

                </div>


                <div class="col-lg-3">

                    <label class="form-label">
                        الحالة
                    </label>

                    <select id="tenantStatus"
                            class="form-select">

                        <option value="">
                            جميع الحالات
                        </option>

                        <option value="active">
                            نشط
                        </option>

                        <option value="suspended">
                            موقوف
                        </option>

                        <option value="inactive">
                            غير نشط
                        </option>

                    </select>

                </div>


                <div class="col-lg-2">

                    <label class="form-label">
                        عدد السجلات
                    </label>

                    <select id="perPage"
                            class="form-select">

                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>

                    </select>

                </div>


                <div class="col-lg-2 d-flex align-items-end">

                    <button type="button"
                            id="btnResetFilters"
                            class="btn btn-light border w-100">

                        إعادة تعيين

                    </button>

                </div>

            </div>

        </div>

    </div>


    {{-- Table --}}
    <div class="card border-0 shadow-sm rounded-4">

        <div class="card-header bg-white border-0 py-3 px-4">

            <div class="d-flex justify-content-between align-items-center">

                <h6 class="mb-0">
                    قائمة العملاء
                </h6>

                <span class="badge bg-primary-subtle text-primary"
                      id="totalTenants">

                    0 عميل

                </span>

            </div>

        </div>


        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead class="table-light">

                <tr>
                    <th class="text-center" width="70">#</th>
                    <th>الكود</th>
                    <th>اسم العميل</th>
                    <th>مسؤول التواصل</th>
                    <th>بيانات التواصل</th>
                    <th class="text-center">الحالة</th>
                    <th class="text-center" width="180">الإجراءات</th>
                </tr>

                </thead>

                <tbody id="tenantsTableBody">

                <tr>
                    <td colspan="7"
                        class="text-center py-5 text-muted">

                        جاري تحميل البيانات...

                    </td>
                </tr>

                </tbody>

            </table>

        </div>


        <div class="card-footer bg-white border-0 px-4 py-3">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                <div class="text-muted small"
                     id="paginationInfo">
                </div>

                <nav>
                    <ul class="pagination pagination-sm mb-0"
                        id="pagination">
                    </ul>
                </nav>

            </div>

        </div>

    </div>

</div>


{{-- Tenant Modal --}}
<div class="modal fade"
     id="tenantModal"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content border-0 rounded-4">

            <form id="tenantForm">

                <div class="modal-header">

                    <h5 class="modal-title"
                        id="tenantModalTitle">

                        إضافة عميل جديد

                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal">
                    </button>

                </div>


                <div class="modal-body p-4">

                    <input type="hidden"
                           id="tenantId">


                    <div class="mb-4">

                        <h6 class="mb-3">
                            المعلومات الأساسية
                        </h6>


                        <div class="row g-3">

                            <div class="col-md-4">

                                <label class="form-label">
                                    كود العميل
                                    <span class="text-danger">*</span>
                                </label>

                                <input type="text"
                                       name="code"
                                       id="code"
                                       class="form-control"
                                       dir="ltr"
                                       placeholder="TEN001">

                                <div class="form-text">
                                    لا يمكن تغييره بعد إنشاء العميل.
                                </div>

                            </div>


                            <div class="col-md-8">

                                <label class="form-label">
                                    اسم العميل
                                    <span class="text-danger">*</span>
                                </label>

                                <input type="text"
                                       name="name"
                                       id="name"
                                       class="form-control"
                                       placeholder="اسم المنشأة أو العميل">

                            </div>


                            <div class="col-md-6">

                                <label class="form-label">
                                    مسؤول التواصل
                                </label>

                                <input type="text"
                                       name="contact_name"
                                       id="contact_name"
                                       class="form-control">

                            </div>


                            <div class="col-md-6">

                                <label class="form-label">
                                    الحالة
                                </label>

                                <select name="status"
                                        id="status"
                                        class="form-select">

                                    <option value="active">
                                        نشط
                                    </option>

                                    <option value="suspended">
                                        موقوف
                                    </option>

                                    <option value="inactive">
                                        غير نشط
                                    </option>

                                </select>

                            </div>

                        </div>

                    </div>


                    <hr>


                    <div class="my-4">

                        <h6 class="mb-3">
                            بيانات التواصل
                        </h6>


                        <div class="row g-3">

                            <div class="col-md-6">

                                <label class="form-label">
                                    البريد الإلكتروني
                                </label>

                                <input type="email"
                                       name="email"
                                       id="email"
                                       class="form-control"
                                       dir="ltr">

                            </div>


                            <div class="col-md-6">

                                <label class="form-label">
                                    رقم الجوال
                                </label>

                                <input type="text"
                                       name="phone"
                                       id="phone"
                                       class="form-control"
                                       dir="ltr">

                            </div>

                        </div>

                    </div>


                    <hr>


                    <div class="mt-4">

                        <h6 class="mb-3">
                            إعدادات الحساب
                        </h6>


                        <div class="row g-3">

                            <div class="col-md-3">

                                <label class="form-label">
                                    الدولة
                                </label>

                                <select name="country_code"
                                        id="country_code"
                                        class="form-select">

                                    <option value="SA">
                                        السعودية
                                    </option>

                                    <option value="AE">
                                        الإمارات
                                    </option>

                                    <option value="BH">
                                        البحرين
                                    </option>

                                    <option value="KW">
                                        الكويت
                                    </option>

                                    <option value="OM">
                                        عُمان
                                    </option>

                                    <option value="QA">
                                        قطر
                                    </option>

                                </select>

                            </div>


                            <div class="col-md-3">

                                <label class="form-label">
                                    العملة
                                </label>

                                <select name="currency_code"
                                        id="currency_code"
                                        class="form-select">

                                    <option value="SAR">SAR</option>
                                    <option value="AED">AED</option>
                                    <option value="BHD">BHD</option>
                                    <option value="KWD">KWD</option>
                                    <option value="OMR">OMR</option>
                                    <option value="QAR">QAR</option>

                                </select>

                            </div>


                            <div class="col-md-3">

                                <label class="form-label">
                                    اللغة
                                </label>

                                <select name="locale"
                                        id="locale"
                                        class="form-select">

                                    <option value="ar">
                                        العربية
                                    </option>

                                    <option value="en">
                                        English
                                    </option>

                                </select>

                            </div>


                            <div class="col-md-3">

                                <label class="form-label">
                                    المنطقة الزمنية
                                </label>

                                <select name="timezone"
                                        id="timezone"
                                        class="form-select">

                                    <option value="Asia/Riyadh">
                                        الرياض
                                    </option>

                                    <option value="Asia/Dubai">
                                        دبي
                                    </option>

                                    <option value="Asia/Kuwait">
                                        الكويت
                                    </option>

                                    <option value="Asia/Bahrain">
                                        البحرين
                                    </option>

                                    <option value="Asia/Qatar">
                                        قطر
                                    </option>

                                    <option value="Asia/Muscat">
                                        مسقط
                                    </option>

                                </select>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="modal-footer">

                    <button type="button"
                            class="btn btn-light"
                            data-bs-dismiss="modal">

                        إلغاء

                    </button>

                    <button type="submit"
                            class="btn btn-primary px-4"
                            id="btnSaveTenant">

                        حفظ العميل

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@endsection


@push('scripts')

<script>

document.addEventListener('DOMContentLoaded', function () {

    const $ = window.jQuery;
    const bootstrap = window.bootstrap;
    const Swal = window.Swal;

    if (!$) {
        console.error('jQuery is not loaded');
        return;
    }

    if (!bootstrap) {
        console.error('Bootstrap JS is not loaded');
        return;
    }

    
    let currentPage = 1;
    let searchTimer = null;

    const tenantModal = bootstrap.Modal.getOrCreateInstance(
        document.getElementById('tenantModal')
    );


    /*
    |--------------------------------------------------------------------------
    | CSRF
    |--------------------------------------------------------------------------
    */

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN':
                $('meta[name="csrf-token"]').attr('content'),

            'Accept': 'application/json'
        }
    });


    /*
    |--------------------------------------------------------------------------
    | Escape HTML
    |--------------------------------------------------------------------------
    */

    function escapeHtml(value) {
        return $('<div>')
            .text(value ?? '')
            .html();
    }


    /*
    |--------------------------------------------------------------------------
    | Status
    |--------------------------------------------------------------------------
    */

    function statusBadge(status) {

        if (status === 'active') {
            return `
                <span class="badge bg-success-subtle text-success px-3 py-2">
                    نشط
                </span>
            `;
        }

        if (status === 'suspended') {
            return `
                <span class="badge bg-warning-subtle text-warning px-3 py-2">
                    موقوف
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
    | Load Tenants
    |--------------------------------------------------------------------------
    */

    function loadTenants(page = 1) {

        currentPage = page;

        $('#tenantsTableBody').html(`
            <tr>
                <td colspan="7"
                    class="text-center py-5 text-muted">
                    جاري تحميل البيانات...
                </td>
            </tr>
        `);


        $.ajax({

            url: @json(route('system.tenants.data')),

            type: 'GET',

            data: {
                page: page,
                search: $('#tenantSearch').val(),
                status: $('#tenantStatus').val(),
                per_page: $('#perPage').val()
            },

            success: function (response) {

                /*
                 * إذا حذفنا آخر عنصر في الصفحة.
                 */
                if (
                    response.data.length === 0 &&
                    page > 1
                ) {
                    loadTenants(page - 1);
                    return;
                }


                renderTable(response);
                renderPagination(response);

                $('#totalTenants').text(
                    response.total + ' عميل'
                );
            },

            error: function () {

                $('#tenantsTableBody').html(`
                    <tr>
                        <td colspan="7"
                            class="text-center py-5 text-danger">

                            تعذر تحميل بيانات العملاء.

                        </td>
                    </tr>
                `);

            }

        });

    }


    /*
    |--------------------------------------------------------------------------
    | Render Table
    |--------------------------------------------------------------------------
    */

    function renderTable(response) {

        if (!response.data.length) {

            $('#tenantsTableBody').html(`
                <tr>
                    <td colspan="7"
                        class="text-center py-5">

                        <div class="text-muted">
                            لا توجد بيانات مطابقة.
                        </div>

                    </td>
                </tr>
            `);

            return;
        }


        let html = '';

        response.data.forEach(function (tenant, index) {

            const rowNumber =
                (response.current_page - 1)
                * response.per_page
                + index
                + 1;


            const contact = tenant.contact_name
                ? escapeHtml(tenant.contact_name)
                : '-';


            const email = tenant.email
                ? escapeHtml(tenant.email)
                : '-';


            const phone = tenant.phone
                ? escapeHtml(tenant.phone)
                : '-';


            html += `

                <tr>

                    <td class="text-center text-muted">
                        ${rowNumber}
                    </td>


                    <td>
                        <span class="fw-semibold"
                              dir="ltr">

                            ${escapeHtml(tenant.code)}

                        </span>
                    </td>


                    <td>

                        <div class="fw-semibold">
                            ${escapeHtml(tenant.name)}
                        </div>

                        <small class="text-muted">
                            ${escapeHtml(tenant.currency_code)}
                        </small>

                    </td>


                    <td>
                        ${contact}
                    </td>


                    <td>

                        <div class="small">
                            ${email}
                        </div>

                        <div class="small text-muted"
                             dir="ltr">
                            ${phone}
                        </div>

                    </td>


                    <td class="text-center">
                        ${statusBadge(tenant.status)}
                    </td>


                    <td class="text-center">

                        <div class="btn-group btn-group-sm">

                            <button type="button"
                                    class="btn btn-light border btn-edit-tenant"
                                    data-id="${tenant.id}">

                                تعديل

                            </button>


                            <button type="button"
                                    class="btn btn-light border text-danger btn-delete-tenant"
                                    data-id="${tenant.id}"
                                    data-name="${escapeHtml(tenant.name)}">

                                حذف

                            </button>

                        </div>

                    </td>

                </tr>

            `;

        });


        $('#tenantsTableBody').html(html);
    }


    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    function renderPagination(response) {

        let html = '';

        const current = response.current_page;
        const last = response.last_page;


        if (last > 1) {

            html += `

                <li class="page-item ${current === 1 ? 'disabled' : ''}">

                    <button type="button"
                            class="page-link page-button"
                            data-page="${current - 1}">

                        السابق

                    </button>

                </li>

            `;


            let start = Math.max(1, current - 2);
            let end = Math.min(last, current + 2);


            for (let page = start; page <= end; page++) {

                html += `

                    <li class="page-item ${page === current ? 'active' : ''}">

                        <button type="button"
                                class="page-link page-button"
                                data-page="${page}">

                            ${page}

                        </button>

                    </li>

                `;

            }


            html += `

                <li class="page-item ${current === last ? 'disabled' : ''}">

                    <button type="button"
                            class="page-link page-button"
                            data-page="${current + 1}">

                        التالي

                    </button>

                </li>

            `;

        }


        $('#pagination').html(html);


        if (response.total > 0) {

            $('#paginationInfo').text(

                'عرض '
                + response.from
                + ' إلى '
                + response.to
                + ' من '
                + response.total

            );

        } else {

            $('#paginationInfo').text(
                'لا توجد سجلات'
            );

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Add Tenant
    |--------------------------------------------------------------------------
    */

    $('#btnAddTenant').on('click', function () {

        $('#tenantForm')[0].reset();

        $('#tenantId').val('');

        $('#code')
            .prop('disabled', false)
            .val('');

        $('#status').val('active');
        $('#country_code').val('SA');
        $('#currency_code').val('SAR');
        $('#locale').val('ar');
        $('#timezone').val('Asia/Riyadh');

        $('#tenantModalTitle').text(
            'إضافة عميل جديد'
        );

        $('#btnSaveTenant').text(
            'حفظ العميل'
        );

        tenantModal.show();

    });


    /*
    |--------------------------------------------------------------------------
    | Edit Tenant
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'click',
        '.btn-edit-tenant',
        function () {

            const id = $(this).data('id');

            const url =
                @json(route('system.tenants.show', ['tenant' => '__ID__']))
                    .replace('__ID__', id);


            $.ajax({

                url: url,

                type: 'GET',

                success: function (response) {

                    const tenant = response.tenant;

                    $('#tenantId').val(tenant.id);

                    $('#code')
                        .val(tenant.code)
                        .prop('disabled', true);

                    $('#name').val(tenant.name);
                    $('#contact_name').val(tenant.contact_name);
                    $('#email').val(tenant.email);
                    $('#phone').val(tenant.phone);

                    $('#status').val(tenant.status);

                    $('#country_code')
                        .val(tenant.country_code);

                    $('#currency_code')
                        .val(tenant.currency_code);

                    $('#locale')
                        .val(tenant.locale);

                    $('#timezone')
                        .val(tenant.timezone);


                    $('#tenantModalTitle').text(
                        'تعديل بيانات العميل'
                    );

                    $('#btnSaveTenant').text(
                        'حفظ التعديلات'
                    );

                    tenantModal.show();

                },

                error: function () {

                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: 'تعذر تحميل بيانات العميل.'
                    });

                }

            });

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Save
    |--------------------------------------------------------------------------
    */

    $('#tenantForm').on(
        'submit',
        function (event) {

            event.preventDefault();


            const id = $('#tenantId').val();

            let url;
            let method;


            if (id) {

                url =
                    @json(route('system.tenants.update', ['tenant' => '__ID__']))
                        .replace('__ID__', id);

                method = 'PUT';

            } else {

                url = @json(route('system.tenants.store'));

                method = 'POST';

            }


            const button = $('#btnSaveTenant');

            const originalText = button.text();

            button
                .prop('disabled', true)
                .text('جاري الحفظ...');


            $.ajax({

                url: url,

                type: method,

                data: $('#tenantForm').serialize(),

                success: function (response) {

                    tenantModal.hide();

                    Swal.fire({
                        icon: 'success',
                        title: 'تم بنجاح',
                        text: response.message,
                        timer: 1800,
                        showConfirmButton: false
                    });

                    loadTenants(currentPage);

                },

                error: function (xhr) {

                    showAjaxError(xhr);

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
        '.btn-delete-tenant',
        function () {

            const id = $(this).data('id');
            const name = $(this).data('name');


            Swal.fire({

                icon: 'warning',

                title: 'حذف العميل؟',

                html:
                    'هل أنت متأكد من حذف العميل<br><strong>'
                    + name
                    + '</strong>؟',

                showCancelButton: true,

                confirmButtonText: 'نعم، حذف',

                cancelButtonText: 'إلغاء',

                confirmButtonColor: '#dc3545'

            }).then(function (result) {

                if (!result.isConfirmed) {
                    return;
                }


                const url =
                    @json(route('system.tenants.destroy', ['tenant' => '__ID__']))
                        .replace('__ID__', id);


                $.ajax({

                    url: url,

                    type: 'DELETE',

                    success: function (response) {

                        Swal.fire({
                            icon: 'success',
                            title: 'تم الحذف',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        });

                        loadTenants(currentPage);

                    },

                    error: function (xhr) {

                        showAjaxError(xhr);

                    }

                });

            });

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

    $('#tenantSearch').on('input', function () {

        clearTimeout(searchTimer);

        searchTimer = setTimeout(function () {
            loadTenants(1);
        }, 350);

    });


    $('#tenantStatus, #perPage').on(
        'change',
        function () {
            loadTenants(1);
        }
    );


    $('#btnResetFilters').on(
        'click',
        function () {

            $('#tenantSearch').val('');
            $('#tenantStatus').val('');
            $('#perPage').val('15');

            loadTenants(1);

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Pagination Click
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'click',
        '.page-button',
        function () {

            if (
                $(this)
                    .closest('.page-item')
                    .hasClass('disabled')
            ) {
                return;
            }

            loadTenants(
                $(this).data('page')
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Ajax Errors
    |--------------------------------------------------------------------------
    */

    function showAjaxError(xhr) {

        if (
            xhr.status === 401 ||
            xhr.status === 419
        ) {
            window.location.reload();
            return;
        }


        let message =
            xhr.responseJSON?.message
            ?? 'حدث خطأ غير متوقع.';


        const errors =
            xhr.responseJSON?.errors;


        if (errors) {

            const firstError =
                Object.values(errors)[0];

            if (firstError?.length) {
                message = firstError[0];
            }

        }


        Swal.fire({
            icon: 'error',
            title: 'تعذر تنفيذ العملية',
            text: message
        });

    }


    /*
    |--------------------------------------------------------------------------
    | Initial Load
    |--------------------------------------------------------------------------
    */

    loadTenants();

});

</script>

@endpush