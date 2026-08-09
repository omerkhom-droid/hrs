@extends('layouts.system')

@section('title', 'العملاء')
@section('page-title', 'إدارة العملاء')

@section('content')

<style>
    #tenantModal .modal-dialog {
        max-height: calc(100vh - 30px);
    }

    #tenantModal .modal-content {
        max-height: calc(100vh - 30px);
        overflow: hidden;
    }

    #tenantModal .modal-body {
        overflow-y: auto;
        overscroll-behavior: contain;
    }

    .tenant-form-section {
        padding: 20px;
        background: #ffffff;
        border: 1px solid #e9ecef;
        border-radius: 16px;
    }

    .tenant-form-section-title {
        font-weight: 700;
        margin-bottom: 18px;
    }
</style>


<div class="container-fluid p-0">

    {{-- رأس الصفحة --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

        <div>
            <h4 class="mb-1">عملاء المنصة</h4>

            <div class="text-muted">
                إدارة العملاء والمستخدمين والاشتراكات
            </div>
        </div>

        <button
            type="button"
            class="btn btn-primary px-4"
            id="btnAddTenant">

            + إضافة عميل

        </button>

    </div>


    {{-- البحث والفلاتر --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">

        <div class="card-body">

            <form id="tenantSearchForm">

                <div class="row g-3">

                    <div class="col-lg-5">

                        <label class="form-label">
                            البحث
                        </label>

                        <input
                            type="search"
                            id="tenantSearch"
                            class="form-control"
                            placeholder="الاسم، الكود، البريد، الجوال...">

                    </div>


                    <div class="col-lg-3">

                        <label class="form-label">
                            الحالة
                        </label>

                        <select
                            id="tenantStatusFilter"
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

                        <select
                            id="tenantPerPage"
                            class="form-select">

                            <option value="10">10</option>
                            <option value="15" selected>15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>

                        </select>

                    </div>


                    <div class="col-lg-2 d-flex align-items-end gap-2">

                        <button
                            type="submit"
                            class="btn btn-primary flex-grow-1">

                            بحث

                        </button>

                        <button
                            type="button"
                            id="btnResetFilters"
                            class="btn btn-light border">

                            إعادة

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>


    {{-- جدول العملاء --}}
    <div class="card border-0 shadow-sm rounded-4">

        <div class="card-header bg-white border-0 py-3 px-4">

            <div class="d-flex justify-content-between align-items-center">

                <h6 class="mb-0">
                    قائمة العملاء
                </h6>

                <span
                    class="badge bg-primary-subtle text-primary"
                    id="totalTenants">

                    0 عميل

                </span>

            </div>

        </div>


        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead class="table-light">

                <tr>
                    <th class="text-center">#</th>
                    <th>الكود</th>
                    <th>اسم العميل</th>
                    <th>الباقة</th>
                    <th class="text-center">المستخدمون</th>
                    <th class="text-center">الاشتراك</th>
                    <th>تاريخ النهاية</th>
                    <th class="text-center">الحالة</th>
                    <th class="text-center">الإجراءات</th>
                </tr>

                </thead>

                <tbody id="tenantsTableBody">

                <tr>
                    <td
                        colspan="9"
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
                    class="text-muted small"
                    id="paginationInfo">
                </div>

                <nav>
                    <ul
                        class="pagination pagination-sm mb-0"
                        id="pagination">
                    </ul>
                </nav>

            </div>

        </div>

    </div>

</div>


{{-- مودال العميل --}}
<div
    class="modal"
    id="tenantModal"
    tabindex="-1"
    aria-hidden="true"
    dir="rtl">

    <div class="modal-dialog modal-xl modal-dialog-centered">

        <div class="modal-content border-0 rounded-4 shadow">

            <form id="tenantForm">

                @csrf

                <div class="modal-header">

                    <h5
                        class="modal-title"
                        id="tenantModalTitle">

                        إضافة عميل جديد

                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-close-tenant-modal>
                    </button>

                </div>


                <div class="modal-body bg-light p-4">

                    <input
                        type="hidden"
                        id="tenantId">


                    <div
                        class="alert alert-danger d-none"
                        id="tenantFormErrors">
                    </div>


                    {{-- بيانات العميل --}}
                    <div class="tenant-form-section mb-4">

                        <div class="tenant-form-section-title">
                            بيانات العميل
                        </div>


                        <div class="row g-3">

                            <div class="col-md-4">

                                <label class="form-label">
                                    كود العميل
                                    <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="text"
                                    name="code"
                                    id="tenantCode"
                                    class="form-control"
                                    dir="ltr"
                                    placeholder="TEN001"
                                    required>

                            </div>


                            <div class="col-md-8">

                                <label class="form-label">
                                    اسم العميل
                                    <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="text"
                                    name="name"
                                    id="tenantName"
                                    class="form-control"
                                    required>

                            </div>


                            <div class="col-md-6">

                                <label class="form-label">
                                    مسؤول التواصل
                                </label>

                                <input
                                    type="text"
                                    name="contact_name"
                                    id="tenantContactName"
                                    class="form-control">

                            </div>


                            <div class="col-md-6">

                                <label class="form-label">
                                    بريد المنشأة
                                    <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="email"
                                    name="email"
                                    id="tenantEmail"
                                    class="form-control"
                                    dir="ltr"
                                    required>

                            </div>


                            <div class="col-md-6">

                                <label class="form-label">
                                    رقم الجوال
                                </label>

                                <input
                                    type="text"
                                    name="phone"
                                    id="tenantPhone"
                                    class="form-control"
                                    dir="ltr">

                            </div>


                            <div class="col-md-6 editing-only d-none">

                                <label class="form-label">
                                    حالة العميل
                                </label>

                                <select
                                    name="status"
                                    id="tenantStatus"
                                    class="form-select"
                                    disabled>

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


                            <div class="col-md-3">

                                <label class="form-label">
                                    الدولة
                                </label>

                                <select
                                    name="country_code"
                                    id="tenantCountryCode"
                                    class="form-select">

                                    <option value="SA">السعودية</option>
                                    <option value="AE">الإمارات</option>
                                    <option value="BH">البحرين</option>
                                    <option value="KW">الكويت</option>
                                    <option value="OM">عُمان</option>
                                    <option value="QA">قطر</option>

                                </select>

                            </div>


                            <div class="col-md-3">

                                <label class="form-label">
                                    العملة
                                </label>

                                <select
                                    name="currency_code"
                                    id="tenantCurrencyCode"
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

                                <select
                                    name="locale"
                                    id="tenantLocale"
                                    class="form-select">

                                    <option value="ar">العربية</option>
                                    <option value="en">English</option>

                                </select>

                            </div>


                            <div class="col-md-3">

                                <label class="form-label">
                                    المنطقة الزمنية
                                </label>

                                <select
                                    name="timezone"
                                    id="tenantTimezone"
                                    class="form-select">

                                    <option value="Asia/Riyadh">الرياض</option>
                                    <option value="Asia/Dubai">دبي</option>
                                    <option value="Asia/Kuwait">الكويت</option>
                                    <option value="Asia/Bahrain">البحرين</option>
                                    <option value="Asia/Qatar">قطر</option>
                                    <option value="Asia/Muscat">مسقط</option>

                                </select>

                            </div>

                        </div>

                    </div>


                    {{-- يظهر عند إنشاء العميل فقط --}}
                    <div class="creation-only">

                        {{-- الاشتراك --}}
                        <div class="tenant-form-section mb-4">

                            <div class="tenant-form-section-title">
                                بيانات الاشتراك الأول
                            </div>


                            <div class="row g-3">

                                <div class="col-md-6">

                                    <label class="form-label">
                                        الباقة
                                        <span class="text-danger">*</span>
                                    </label>

                                    <select
                                        name="plan_id"
                                        id="tenantPlanId"
                                        class="form-select"
                                        required>

                                        <option value="">
                                            اختر الباقة
                                        </option>

                                        @foreach($plans as $plan)

                                            <option
                                                value="{{ $plan->id }}"
                                                data-trial-days="{{ $plan->trial_days }}"
                                                data-monthly-price="{{ $plan->monthly_price }}"
                                                data-yearly-price="{{ $plan->yearly_price }}"
                                                data-currency="{{ $plan->currency_code }}">

                                                {{ $plan->name }}

                                                -
                                                {{ number_format($plan->monthly_price, 2) }}
                                                {{ $plan->currency_code }}

                                            </option>

                                        @endforeach

                                    </select>

                                </div>


                                <div class="col-md-3">

                                    <label class="form-label">
                                        دورة الفوترة
                                    </label>

                                    <select
                                        name="billing_cycle"
                                        id="tenantBillingCycle"
                                        class="form-select"
                                        required>

                                        <option value="monthly">
                                            شهري
                                        </option>

                                        <option value="yearly">
                                            سنوي
                                        </option>

                                    </select>

                                </div>


                                <div class="col-md-3">

                                    <label class="form-label">
                                        تاريخ البداية
                                    </label>

                                    <input
                                        type="date"
                                        name="starts_at"
                                        id="tenantStartsAt"
                                        class="form-control"
                                        value="{{ $defaultStartDate }}"
                                        required>

                                </div>


                                <div class="col-md-6">

                                    <input
                                        type="hidden"
                                        name="use_trial"
                                        value="0">

                                    <div class="form-check form-switch mt-2">

                                        <input
                                            type="checkbox"
                                            name="use_trial"
                                            id="tenantUseTrial"
                                            class="form-check-input"
                                            value="1">

                                        <label
                                            class="form-check-label"
                                            for="tenantUseTrial"
                                            id="tenantTrialLabel">

                                            استخدام الفترة التجريبية

                                        </label>

                                    </div>

                                </div>


                                <div class="col-md-6">

                                    <input
                                        type="hidden"
                                        name="auto_renew"
                                        value="0">

                                    <div class="form-check form-switch mt-2">

                                        <input
                                            type="checkbox"
                                            name="auto_renew"
                                            id="tenantAutoRenew"
                                            class="form-check-input"
                                            value="1"
                                            checked>

                                        <label
                                            class="form-check-label"
                                            for="tenantAutoRenew">

                                            التجديد التلقائي

                                        </label>

                                    </div>

                                </div>

                            </div>

                        </div>


                        {{-- مدير العميل --}}
                        <div class="tenant-form-section">

                            <div class="tenant-form-section-title">
                                مستخدم مدير العميل
                            </div>


                            <div class="row g-3">

                                <div class="col-md-6">

                                    <label class="form-label">
                                        اسم المدير
                                        <span class="text-danger">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        name="admin_name"
                                        id="tenantAdminName"
                                        class="form-control"
                                        required>

                                </div>


                                <div class="col-md-6">

                                    <label class="form-label">
                                        بريد تسجيل الدخول
                                        <span class="text-danger">*</span>
                                    </label>

                                    <input
                                        type="email"
                                        name="admin_email"
                                        id="tenantAdminEmail"
                                        class="form-control"
                                        dir="ltr"
                                        autocomplete="off"
                                        required>

                                </div>


                                <div class="col-md-6">

                                    <label class="form-label">
                                        كلمة المرور
                                        <span class="text-danger">*</span>
                                    </label>

                                    <input
                                        type="password"
                                        name="password"
                                        id="tenantPassword"
                                        class="form-control"
                                        minlength="10"
                                        autocomplete="new-password"
                                        required>

                                    <div class="form-text">
                                        10 أحرف على الأقل وتحتوي على أحرف وأرقام.
                                    </div>

                                </div>


                                <div class="col-md-6">

                                    <label class="form-label">
                                        تأكيد كلمة المرور
                                        <span class="text-danger">*</span>
                                    </label>

                                    <input
                                        type="password"
                                        name="password_confirmation"
                                        id="tenantPasswordConfirmation"
                                        class="form-control"
                                        minlength="10"
                                        autocomplete="new-password"
                                        required>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-light"
                        data-close-tenant-modal>

                        إلغاء

                    </button>

                    <button
                        type="submit"
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
$(function () {

    var currentPage = 1;
    var editingTenantId = null;
    var searchTimer = null;

    var urls = {
        data: @json(route('system.tenants.data')),

        store: @json(route('system.tenants.store')),

        show: @json(
            route(
                'system.tenants.show',
                ['tenant' => '__TENANT__']
            )
        ),

        destroy: @json(
            route(
                'system.tenants.destroy',
                ['tenant' => '__TENANT__']
            )
        )
    };


    /*
    |--------------------------------------------------------------------------
    | إعداد Ajax
    |--------------------------------------------------------------------------
    */

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN':
                $('meta[name="csrf-token"]').attr('content'),

            'Accept':
                'application/json'
        }
    });


    function tenantUrl(
        template,
        tenantId
    ) {
        return template.replace(
            '__TENANT__',
            tenantId
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
    | فتح وإغلاق المودال باستخدام jQuery
    |--------------------------------------------------------------------------
    */

    function openTenantModal()
    {
        $('#tenantModal')
            .css('display', 'block')
            .addClass('show')
            .attr(
                'aria-modal',
                'true'
            )
            .removeAttr(
                'aria-hidden'
            );

        $('body')
            .addClass('modal-open')
            .css(
                'overflow',
                'hidden'
            );

        if (
            !$('#tenantModalBackdrop').length
        ) {
            $('<div>', {
                id:
                    'tenantModalBackdrop',

                class:
                    'modal-backdrop fade show'
            }).appendTo('body');
        }
    }


    function closeTenantModal()
    {
        $('#tenantModal')
            .removeClass('show')
            .css(
                'display',
                'none'
            )
            .attr(
                'aria-hidden',
                'true'
            )
            .removeAttr(
                'aria-modal'
            );

        $('#tenantModalBackdrop')
            .remove();

        $('body')
            .removeClass('modal-open')
            .css(
                'overflow',
                ''
            );
    }


    $(document).on(
        'click',
        '[data-close-tenant-modal], #tenantModalBackdrop',
        function () {
            closeTenantModal();
        }
    );


    $(document).on(
        'keyup',
        function (event) {

            if (
                event.key === 'Escape' &&
                $('#tenantModal').hasClass('show')
            ) {
                closeTenantModal();
            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | تنظيف النموذج
    |--------------------------------------------------------------------------
    */

    function resetTenantForm()
    {
        $('#tenantForm')
            .trigger('reset');

        $('#tenantId')
            .val('');

        $('#tenantCode')
            .prop(
                'readonly',
                false
            )
            .val('');

        $('#tenantCountryCode')
            .val('SA');

        $('#tenantCurrencyCode')
            .val('SAR');

        $('#tenantLocale')
            .val('ar');

        $('#tenantTimezone')
            .val('Asia/Riyadh');

        $('#tenantStartsAt')
            .val(
                @json($defaultStartDate)
            );

        $('#tenantAutoRenew')
            .prop(
                'checked',
                true
            );

        $('#tenantUseTrial')
            .prop(
                'checked',
                false
            )
            .prop(
                'disabled',
                false
            );

        clearErrors();
    }


    /*
    |--------------------------------------------------------------------------
    | أخطاء التحقق
    |--------------------------------------------------------------------------
    */

    function clearErrors()
    {
        $('#tenantFormErrors')
            .addClass('d-none')
            .empty();

        $('#tenantForm')
            .find('.is-invalid')
            .removeClass(
                'is-invalid'
            );
    }


    function displayErrors(errors)
    {
        var box =
            $('#tenantFormErrors');

        box.empty();

        $.each(
            errors,
            function (
                field,
                messages
            ) {
                $('#tenantForm')
                    .find(
                        '[name="' +
                        field +
                        '"]'
                    )
                    .addClass(
                        'is-invalid'
                    );

                $.each(
                    messages,
                    function (
                        index,
                        message
                    ) {
                        $('<div>')
                            .text(message)
                            .appendTo(box);
                    }
                );
            }
        );

        box.removeClass(
            'd-none'
        );

        $('#tenantModal .modal-body')
            .stop(true)
            .animate(
                {
                    scrollTop: 0
                },
                200
            );
    }


    function showAjaxError(
        xhr,
        fallback
    ) {
        Swal.fire({
            icon:
                'error',

            title:
                'تعذر تنفيذ العملية',

            text:
                xhr.responseJSON?.message
                ?? fallback
        });
    }


    /*
    |--------------------------------------------------------------------------
    | حالات العميل والاشتراك
    |--------------------------------------------------------------------------
    */

    function tenantStatusBadge(status)
    {
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


    function subscriptionBadge(status)
    {
        if (status === 'active') {
            return `
                <span class="badge bg-success-subtle text-success px-3 py-2">
                    فعال
                </span>
            `;
        }

        if (status === 'trial') {
            return `
                <span class="badge bg-info-subtle text-info px-3 py-2">
                    تجريبي
                </span>
            `;
        }

        if (status === 'suspended') {
            return `
                <span class="badge bg-warning-subtle text-warning px-3 py-2">
                    معلق
                </span>
            `;
        }

        if (status === 'scheduled') {
            return `
                <span class="badge bg-primary-subtle text-primary px-3 py-2">
                    مجدول
                </span>
            `;
        }

        return `
            <span class="badge bg-secondary-subtle text-secondary px-3 py-2">
                لا يوجد
            </span>
        `;
    }


    function dateText(value)
    {
        if (!value) {
            return '-';
        }

        return escapeHtml(
            String(value)
                .split('T')[0]
                .split(' ')[0]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | تحميل العملاء
    |--------------------------------------------------------------------------
    */

    function loadTenants(page)
    {
        currentPage =
            page || 1;

        $('#tenantsTableBody')
            .html(`
                <tr>
                    <td
                        colspan="9"
                        class="text-center py-5 text-muted">

                        جاري تحميل البيانات...

                    </td>
                </tr>
            `);


        $.ajax({
            url:
                urls.data,

            type:
                'GET',

            data: {
                page:
                    currentPage,

                search:
                    $('#tenantSearch').val(),

                status:
                    $('#tenantStatusFilter').val(),

                per_page:
                    $('#tenantPerPage').val()
            },

            success: function (response) {

                if (
                    response.data.length === 0 &&
                    currentPage > 1
                ) {
                    loadTenants(
                        currentPage - 1
                    );

                    return;
                }

                renderTable(response);
                renderPagination(response);

                $('#totalTenants')
                    .text(
                        response.total +
                        ' عميل'
                    );
            },

            error: function (xhr) {

                $('#tenantsTableBody')
                    .html(`
                        <tr>
                            <td
                                colspan="9"
                                class="text-center py-5 text-danger">

                                تعذر تحميل بيانات العملاء.

                            </td>
                        </tr>
                    `);

                showAjaxError(
                    xhr,
                    'تعذر تحميل بيانات العملاء.'
                );
            }
        });
    }


    /*
    |--------------------------------------------------------------------------
    | عرض الجدول
    |--------------------------------------------------------------------------
    */

    function renderTable(response)
    {
        if (!response.data.length) {

            $('#tenantsTableBody')
                .html(`
                    <tr>
                        <td
                            colspan="9"
                            class="text-center py-5 text-muted">

                            لا توجد بيانات مطابقة.

                        </td>
                    </tr>
                `);

            return;
        }


        var html = '';


        $.each(
            response.data,
            function (
                index,
                tenant
            ) {
                var rowNumber =
                    (
                        response.from || 1
                    ) + index;


                html += `

                    <tr>

                        <td class="text-center text-muted">
                            ${rowNumber}
                        </td>


                        <td dir="ltr">
                            ${escapeHtml(tenant.code)}
                        </td>


                        <td>

                            <div class="fw-semibold">
                                ${escapeHtml(tenant.name)}
                            </div>

                            <small class="text-muted">
                                ${escapeHtml(tenant.contact_name || '')}
                            </small>

                        </td>


                        <td>
                            ${escapeHtml(tenant.plan_name || '-')}
                        </td>


                        <td class="text-center">
                            ${escapeHtml(tenant.users_count || 0)}
                        </td>


                        <td class="text-center">
                            ${subscriptionBadge(tenant.subscription_status)}
                        </td>


                        <td dir="ltr">
                            ${dateText(tenant.ends_at)}
                        </td>


                        <td class="text-center">
                            ${tenantStatusBadge(tenant.status)}
                        </td>


                        <td class="text-center">

                            <div class="btn-group btn-group-sm">

                                <button
                                    type="button"
                                    class="btn btn-light border btn-edit-tenant"
                                    data-id="${tenant.id}">

                                    تعديل

                                </button>

                                <button
                                    type="button"
                                    class="btn btn-light border text-danger btn-delete-tenant"
                                    data-id="${tenant.id}"
                                    data-name="${escapeHtml(tenant.name)}">

                                    أرشفة

                                </button>

                            </div>

                        </td>

                    </tr>

                `;
            }
        );


        $('#tenantsTableBody')
            .html(html);
    }


    /*
    |--------------------------------------------------------------------------
    | الصفحات
    |--------------------------------------------------------------------------
    */

    function renderPagination(response)
    {
        var html = '';

        var current =
            response.current_page;

        var last =
            response.last_page;


        if (last > 1) {

            html += `
                <li class="page-item ${current === 1 ? 'disabled' : ''}">

                    <button
                        type="button"
                        class="page-link page-button"
                        data-page="${current - 1}">

                        السابق

                    </button>

                </li>
            `;


            var start =
                Math.max(
                    1,
                    current - 2
                );

            var end =
                Math.min(
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
                            class="page-link page-button"
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
                        class="page-link page-button"
                        data-page="${current + 1}">

                        التالي

                    </button>

                </li>
            `;
        }


        $('#pagination')
            .html(html);


        if (response.total > 0) {

            $('#paginationInfo')
                .text(
                    'عرض ' +
                    response.from +
                    ' إلى ' +
                    response.to +
                    ' من ' +
                    response.total
                );

        } else {

            $('#paginationInfo')
                .text(
                    'لا توجد سجلات'
                );

        }
    }


    /*
    |--------------------------------------------------------------------------
    | إضافة عميل
    |--------------------------------------------------------------------------
    */

    $('#btnAddTenant').on(
        'click',
        function () {

            editingTenantId =
                null;

            resetTenantForm();

            $('#tenantModalTitle')
                .text(
                    'إضافة عميل جديد'
                );

            $('#btnSaveTenant')
                .text(
                    'إنشاء العميل والحساب والاشتراك'
                );

            $('.creation-only')
                .removeClass('d-none')
                .find(':input')
                .prop(
                    'disabled',
                    false
                );

            $('.editing-only')
                .addClass('d-none')
                .find(':input')
                .prop(
                    'disabled',
                    true
                );

            openTenantModal();
        }
    );


    /*
    |--------------------------------------------------------------------------
    | تغيير الباقة
    |--------------------------------------------------------------------------
    */

    $('#tenantPlanId').on(
        'change',
        function () {

            var trialDays =
                Number(
                    $(this)
                        .find(
                            'option:selected'
                        )
                        .data(
                            'trial-days'
                        ) || 0
                );


            $('#tenantUseTrial')
                .prop(
                    'disabled',
                    trialDays < 1
                )
                .prop(
                    'checked',
                    false
                );


            $('#tenantTrialLabel')
                .text(
                    trialDays > 0
                        ? 'بدء فترة تجريبية لمدة ' +
                            trialDays +
                            ' يوم'
                        : 'الباقة لا تحتوي على فترة تجريبية'
                );
        }
    );


    /*
    |--------------------------------------------------------------------------
    | تعديل العميل
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'click',
        '.btn-edit-tenant',
        function () {

            var id =
                $(this).data('id');


            clearErrors();


            $.ajax({
                url:
                    tenantUrl(
                        urls.show,
                        id
                    ),

                type:
                    'GET',

                success: function (response) {

                    var tenant =
                        response.tenant;


                    editingTenantId =
                        id;


                    resetTenantForm();


                    $('#tenantId')
                        .val(
                            tenant.id
                        );

                    $('#tenantCode')
                        .val(
                            tenant.code || ''
                        )
                        .prop(
                            'readonly',
                            true
                        );

                    $('#tenantName')
                        .val(
                            tenant.name || ''
                        );

                    $('#tenantContactName')
                        .val(
                            tenant.contact_name || ''
                        );

                    $('#tenantEmail')
                        .val(
                            tenant.email || ''
                        );

                    $('#tenantPhone')
                        .val(
                            tenant.phone || ''
                        );

                    $('#tenantCountryCode')
                        .val(
                            tenant.country_code || 'SA'
                        );

                    $('#tenantCurrencyCode')
                        .val(
                            tenant.currency_code || 'SAR'
                        );

                    $('#tenantLocale')
                        .val(
                            tenant.locale || 'ar'
                        );

                    $('#tenantTimezone')
                        .val(
                            tenant.timezone || 'Asia/Riyadh'
                        );

                    $('#tenantStatus')
                        .val(
                            tenant.status || 'active'
                        );


                    $('#tenantModalTitle')
                        .text(
                            'تعديل بيانات العميل'
                        );

                    $('#btnSaveTenant')
                        .text(
                            'حفظ التعديلات'
                        );


                    $('.creation-only')
                        .addClass('d-none')
                        .find(':input')
                        .prop(
                            'disabled',
                            true
                        );

                    $('.editing-only')
                        .removeClass('d-none')
                        .find(':input')
                        .prop(
                            'disabled',
                            false
                        );


                    openTenantModal();
                },

                error: function (xhr) {

                    showAjaxError(
                        xhr,
                        'تعذر تحميل بيانات العميل.'
                    );
                }
            });
        }
    );


    /*
    |--------------------------------------------------------------------------
    | حفظ العميل
    |--------------------------------------------------------------------------
    */

    $('#tenantForm').on(
        'submit',
        function (event) {

            event.preventDefault();

            clearErrors();


            var isCreating =
                !editingTenantId;


            var url =
                isCreating
                    ? urls.store
                    : tenantUrl(
                        urls.show,
                        editingTenantId
                    );


            var data =
                $(this).serialize();


            if (!isCreating) {
                data += '&_method=PUT';
            }


            var button =
                $('#btnSaveTenant');


            var originalText =
                button.text();


            button
                .prop(
                    'disabled',
                    true
                )
                .text(
                    'جاري الحفظ...'
                );


            $.ajax({
                url:
                    url,

                type:
                    'POST',

                data:
                    data,

                success: function (response) {

                    closeTenantModal();

                    loadTenants(
                        isCreating
                            ? 1
                            : currentPage
                    );


                    if (
                        isCreating &&
                        response.owner
                    ) {
                        Swal.fire({
                            icon:
                                'success',

                            title:
                                'تم إنشاء حساب العميل',

                            html:
                                '<div class="text-end">' +

                                '<div class="mb-3">' +
                                escapeHtml(
                                    response.message
                                ) +
                                '</div>' +

                                '<div>' +
                                '<strong>بريد الدخول:</strong> ' +
                                '<span dir="ltr">' +
                                escapeHtml(
                                    response.owner.email
                                ) +
                                '</span>' +
                                '</div>' +

                                '<div class="mt-2">' +
                                '<strong>رابط الدخول:</strong> ' +
                                '<a href="' +
                                escapeHtml(
                                    response.login_url
                                ) +
                                '" target="_blank">' +
                                'فتح صفحة الدخول' +
                                '</a>' +
                                '</div>' +

                                '<div class="small text-muted mt-3">' +
                                'كلمة المرور هي التي أدخلتها في النموذج.' +
                                '</div>' +

                                '</div>',

                            confirmButtonText:
                                'حسنًا'
                        });

                    } else {

                        Swal.fire({
                            icon:
                                'success',

                            title:
                                'تم',

                            text:
                                response.message,

                            timer:
                                1800,

                            showConfirmButton:
                                false
                        });

                    }
                },

                error: function (xhr) {

                    if (
                        xhr.status === 422 &&
                        xhr.responseJSON?.errors
                    ) {
                        displayErrors(
                            xhr.responseJSON.errors
                        );

                        return;
                    }

                    showAjaxError(
                        xhr,
                        'حدث خطأ أثناء حفظ البيانات.'
                    );
                },

                complete: function () {

                    button
                        .prop(
                            'disabled',
                            false
                        )
                        .text(
                            originalText
                        );
                }
            });
        }
    );


    /*
    |--------------------------------------------------------------------------
    | أرشفة العميل
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'click',
        '.btn-delete-tenant',
        function () {

            var id =
                $(this).data('id');

            var name =
                $(this).data('name');


            Swal.fire({
                icon:
                    'warning',

                title:
                    'أرشفة العميل؟',

                html:
                    'هل أنت متأكد من أرشفة العميل؟' +
                    '<br><strong>' +
                    escapeHtml(name) +
                    '</strong>',

                showCancelButton:
                    true,

                confirmButtonText:
                    'نعم، أرشفة',

                cancelButtonText:
                    'إلغاء',

                confirmButtonColor:
                    '#dc3545'

            }).then(function (result) {

                if (!result.isConfirmed) {
                    return;
                }


                $.ajax({
                    url:
                        tenantUrl(
                            urls.destroy,
                            id
                        ),

                    type:
                        'DELETE',

                    success: function (response) {

                        loadTenants(
                            currentPage
                        );

                        Swal.fire({
                            icon:
                                'success',

                            title:
                                'تم',

                            text:
                                response.message,

                            timer:
                                1500,

                            showConfirmButton:
                                false
                        });
                    },

                    error: function (xhr) {

                        showAjaxError(
                            xhr,
                            'تعذر أرشفة العميل.'
                        );
                    }
                });
            });
        }
    );


    /*
    |--------------------------------------------------------------------------
    | البحث
    |--------------------------------------------------------------------------
    */

    $('#tenantSearchForm').on(
        'submit',
        function (event) {

            event.preventDefault();

            loadTenants(1);
        }
    );


    $('#tenantSearch').on(
        'input',
        function () {

            clearTimeout(
                searchTimer
            );

            searchTimer =
                setTimeout(
                    function () {
                        loadTenants(1);
                    },
                    350
                );
        }
    );


    $('#tenantStatusFilter, #tenantPerPage').on(
        'change',
        function () {

            loadTenants(1);
        }
    );


    $('#btnResetFilters').on(
        'click',
        function () {

            $('#tenantSearch')
                .val('');

            $('#tenantStatusFilter')
                .val('');

            $('#tenantPerPage')
                .val('15');

            loadTenants(1);
        }
    );


    /*
    |--------------------------------------------------------------------------
    | التنقل بين الصفحات
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
                Number(
                    $(this).data('page')
                )
            );
        }
    );


    /*
    |--------------------------------------------------------------------------
    | التحميل الأول
    |--------------------------------------------------------------------------
    */

    loadTenants(1);

});
</script>

@endpush