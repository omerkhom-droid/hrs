@extends('layouts.system')

@section('title', 'الاشتراكات')
@section('page-title', 'إدارة الاشتراكات')

@section('content')

<div class="container-fluid p-0">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

        <div>
            <h4 class="mb-1">الاشتراكات</h4>

            <div class="text-muted">
                إدارة اشتراكات عملاء منصة رؤية يوم
            </div>
        </div>

        <button type="button"
                class="btn btn-primary px-4"
                id="btnAddSubscription">

            + اشتراك جديد

        </button>

    </div>


    {{-- Filters --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">

        <div class="card-body">

            <div class="row g-3">

                <div class="col-lg-4">

                    <label class="form-label">البحث</label>

                    <input type="text"
                           id="subscriptionSearch"
                           class="form-control"
                           placeholder="العميل أو الباقة...">

                </div>


                <div class="col-lg-3">

                    <label class="form-label">العميل</label>

                    <select id="filterTenant"
                            class="form-select">

                        <option value="">جميع العملاء</option>

                        @foreach($tenants as $tenant)

                            <option value="{{ $tenant->id }}">
                                {{ $tenant->name }}
                            </option>

                        @endforeach

                    </select>

                </div>


                <div class="col-lg-2">

                    <label class="form-label">الحالة</label>

                    <select id="filterStatus"
                            class="form-select">

                        <option value="">الكل</option>
                        <option value="trial">تجريبي</option>
                        <option value="scheduled">مجدول</option>
                        <option value="active">فعال</option>
                        <option value="suspended">موقوف</option>
                        <option value="expired">منتهي</option>
                        <option value="cancelled">ملغي</option>

                    </select>

                </div>


                <div class="col-lg-1">

                    <label class="form-label">عرض</label>

                    <select id="perPage"
                            class="form-select">

                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>

                    </select>

                </div>


                <div class="col-lg-2 d-flex align-items-end">

                    <button type="button"
                            class="btn btn-light border w-100"
                            id="resetFilters">

                        إعادة تعيين

                    </button>

                </div>

            </div>

        </div>

    </div>


    {{-- Table --}}
    <div class="card border-0 shadow-sm rounded-4">

        <div class="card-header bg-white border-0 px-4 py-3">

            <div class="d-flex justify-content-between">

                <h6 class="mb-0">
                    سجل الاشتراكات
                </h6>

                <span id="totalSubscriptions"
                      class="badge bg-primary-subtle text-primary">

                    0 اشتراك

                </span>

            </div>

        </div>


        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead class="table-light">

                <tr>
                    <th class="text-center">#</th>
                    <th>العميل</th>
                    <th>الباقة</th>
                    <th>نوع الاشتراك</th>
                    <th>القيمة</th>
                    <th>تاريخ البداية</th>
                    <th>تاريخ النهاية</th>
                    <th class="text-center">الحالة</th>
                    <th class="text-center">الإجراءات</th>
                </tr>

                </thead>

                <tbody id="subscriptionsBody">

                <tr>
                    <td colspan="9"
                        class="text-center text-muted py-5">

                        جاري تحميل الاشتراكات...

                    </td>
                </tr>

                </tbody>

            </table>

        </div>


        <div class="card-footer bg-white border-0 px-4 py-3">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                <div id="paginationInfo"
                     class="text-muted small">
                </div>

                <ul id="pagination"
                    class="pagination pagination-sm mb-0">
                </ul>

            </div>

        </div>

    </div>

</div>


{{-- Create Subscription Modal --}}
<div class="modal fade"
     id="subscriptionModal"
     tabindex="-1">

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content border-0 rounded-4">

            <form id="subscriptionForm">

                <div class="modal-header">

                    <h5 class="modal-title">
                        إنشاء اشتراك جديد
                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal">
                    </button>

                </div>


                <div class="modal-body p-4">

                    <div class="row g-3">

                        {{-- Tenant --}}
                        <div class="col-md-6">

                            <label class="form-label">
                                العميل *
                            </label>

                            <select name="tenant_id"
                                    id="tenant_id"
                                    class="form-select"
                                    required>

                                <option value="">
                                    اختر العميل
                                </option>

                                @foreach($tenants as $tenant)

                                    <option value="{{ $tenant->id }}">
                                        {{ $tenant->name }}
                                        ({{ $tenant->code }})
                                    </option>

                                @endforeach

                            </select>

                        </div>


                        {{-- Plan --}}
                        <div class="col-md-6">

                            <label class="form-label">
                                الباقة *
                            </label>

                            <select name="plan_id"
                                    id="plan_id"
                                    class="form-select"
                                    required>

                                <option value="">
                                    اختر الباقة
                                </option>

                                @foreach($plans as $plan)

                                    <option value="{{ $plan->id }}">
                                        {{ $plan->name }}
                                    </option>

                                @endforeach

                            </select>

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                دورة الفوترة
                            </label>

                            <select name="billing_cycle"
                                    id="billing_cycle"
                                    class="form-select">

                                <option value="monthly">
                                    شهري
                                </option>

                                <option value="yearly">
                                    سنوي
                                </option>

                            </select>

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                تاريخ البداية
                            </label>

                            <input type="date"
                                   name="starts_at"
                                   id="starts_at"
                                   class="form-control"
                                   value="{{ now()->format('Y-m-d') }}">

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                الفترة التجريبية
                            </label>

                            <select name="use_trial"
                                    id="use_trial"
                                    class="form-select">

                                <option value="0">
                                    بدون تجربة
                                </option>

                                <option value="1">
                                    استخدام التجربة
                                </option>

                            </select>

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                التجديد التلقائي
                            </label>

                            <select name="auto_renew"
                                    id="auto_renew"
                                    class="form-select">

                                <option value="0">
                                    لا
                                </option>

                                <option value="1">
                                    نعم
                                </option>

                            </select>

                        </div>

                    </div>


                    {{-- Preview --}}
                    <div class="mt-4 p-4 rounded-4"
                         style="background:#f5f7fb;"
                         id="planPreview">

                        <div class="text-muted text-center">
                            اختر الباقة لعرض تفاصيل الاشتراك
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
                            id="btnSaveSubscription">

                        إنشاء الاشتراك

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


{{-- Details Modal --}}
<div class="modal fade"
     id="subscriptionDetailsModal"
     tabindex="-1">

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content border-0 rounded-4">

            <div class="modal-header">

                <h5 class="modal-title">
                    تفاصيل الاشتراك
                </h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                </button>

            </div>

            <div class="modal-body p-4"
                 id="subscriptionDetails">

                جاري التحميل...

            </div>

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

    const plans = @json($plans->keyBy('id'));
    const subscriptionRoutes = {

        convertTrial:
            @json(route(
                'system.subscriptions.convert-trial',
                ['subscription' => '__ID__']
            )),

        renew:
            @json(route(
                'system.subscriptions.renew',
                ['subscription' => '__ID__']
            )),

        changePlan:
            @json(route(
                'system.subscriptions.change-plan',
                ['subscription' => '__ID__']
            )),

        suspend:
            @json(route(
                'system.subscriptions.suspend',
                ['subscription' => '__ID__']
            )),

        resume:
            @json(route(
                'system.subscriptions.resume',
                ['subscription' => '__ID__']
            )),

        cancel:
            @json(route(
                'system.subscriptions.cancel',
                ['subscription' => '__ID__']
            ))
    };


    function subscriptionUrl(url, id) {

        return url.replace(
            '__ID__',
            id
        );

    }

    let currentPage = 1;
    let searchTimer = null;


    const createModal =
        bootstrap.Modal.getOrCreateInstance(
            document.getElementById('subscriptionModal')
        );


    const detailsModal =
        bootstrap.Modal.getOrCreateInstance(
            document.getElementById('subscriptionDetailsModal')
        );


    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN':
                $('meta[name="csrf-token"]').attr('content'),

            'Accept': 'application/json'
        }
    });


    function escapeHtml(value) {
        return $('<div>')
            .text(value ?? '')
            .html();
    }


    function money(value) {

        return Number(value ?? 0)
            .toLocaleString('ar-SA', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

    }


    function dateText(value) {

        if (!value) {
            return '-';
        }

        return new Date(value)
            .toLocaleDateString('ar-SA');

    }


    function statusBadge(status) {

        const statuses = {

            trial: [
                'تجريبي',
                'bg-info-subtle text-info'
            ],

            scheduled: [
                'مجدول',
                'bg-primary-subtle text-primary'
            ],

            active: [
                'فعال',
                'bg-success-subtle text-success'
            ],

            suspended: [
                'موقوف',
                'bg-warning-subtle text-warning'
            ],

            expired: [
                'منتهي',
                'bg-secondary-subtle text-secondary'
            ],

            cancelled: [
                'ملغي',
                'bg-danger-subtle text-danger'
            ]

        };


        const data =
            statuses[status] ??
            [status, 'bg-light text-dark'];


        return `
            <span class="badge ${data[1]} px-3 py-2">
                ${data[0]}
            </span>
        `;
    }


    function actionButtons(subscription) {

        let actions = '';

        if (subscription.status === 'trial') {

            actions += `
                <button class="dropdown-item btn-convert-trial"
                        data-id="${subscription.id}"
                        data-cycle="${subscription.billing_cycle}">
                    تحويل إلى مدفوع
                </button>

                <button class="dropdown-item btn-change-plan"
                        data-id="${subscription.id}"
                        data-plan-id="${subscription.plan_id}">
                    تغيير الباقة
                </button>
            `;
        }


        if (subscription.status === 'active') {

            actions += `
                <button class="dropdown-item btn-renew"
                        data-id="${subscription.id}"
                        data-cycle="${subscription.billing_cycle}">
                    تجديد الاشتراك
                </button>

                <button class="dropdown-item btn-change-plan"
                        data-id="${subscription.id}"
                        data-plan-id="${subscription.plan_id}">
                    تغيير الباقة
                </button>

                <button class="dropdown-item text-warning btn-suspend"
                        data-id="${subscription.id}">
                    تعليق الاشتراك
                </button>
            `;
        }


        if (subscription.status === 'suspended') {

            actions += `
                <button class="dropdown-item text-success btn-resume"
                        data-id="${subscription.id}">
                    إعادة التفعيل
                </button>

                <button class="dropdown-item btn-change-plan"
                        data-id="${subscription.id}"
                        data-plan-id="${subscription.plan_id}">
                    تغيير الباقة
                </button>
            `;
        }


        if (subscription.status === 'expired') {

            actions += `
                <button class="dropdown-item btn-renew"
                        data-id="${subscription.id}"
                        data-cycle="${subscription.billing_cycle}">
                    تجديد الاشتراك
                </button>
            `;
        }


        if (
            ['trial', 'active', 'suspended', 'scheduled']
                .includes(subscription.status)
        ) {

            actions += `
                <div class="dropdown-divider"></div>

                <button class="dropdown-item text-danger btn-cancel"
                        data-id="${subscription.id}">
                    إلغاء الاشتراك
                </button>
            `;
        }


        return `

            <div class="btn-group btn-group-sm">

                <button type="button"
                        class="btn btn-light border btn-details"
                        data-id="${subscription.id}">
                    التفاصيل
                </button>

                ${
                    actions
                        ? `
                            <button type="button"
                                    class="btn btn-light border dropdown-toggle dropdown-toggle-split"
                                    data-bs-toggle="dropdown">
                            </button>

                            <div class="dropdown-menu dropdown-menu-start">
                                ${actions}
                            </div>
                        `
                        : ''
                }

            </div>

        `;
    }

    /*
    |--------------------------------------------------------------------------
    | Plan Preview
    |--------------------------------------------------------------------------
    */

    function updatePlanPreview() {

        const planId =
            $('#plan_id').val();

        if (!planId || !plans[planId]) {

            $('#planPreview').html(`
                <div class="text-muted text-center">
                    اختر الباقة لعرض تفاصيل الاشتراك
                </div>
            `);

            return;
        }


        const plan = plans[planId];

        const cycle =
            $('#billing_cycle').val();

        const useTrial =
            $('#use_trial').val() === '1';


        let price;

        if (useTrial && plan.trial_days > 0) {
            price = 0;
        } else {
            price =
                cycle === 'yearly'
                    ? plan.yearly_price
                    : plan.monthly_price;
        }


        $('#planPreview').html(`

            <div class="row g-3">

                <div class="col-md-3">

                    <small class="text-muted">
                        الباقة
                    </small>

                    <div class="fw-bold mt-1">
                        ${escapeHtml(plan.name)}
                    </div>

                </div>


                <div class="col-md-3">

                    <small class="text-muted">
                        قيمة الاشتراك
                    </small>

                    <div class="fw-bold text-primary mt-1">
                        ${money(price)}
                        ${escapeHtml(plan.currency_code)}
                    </div>

                </div>


                <div class="col-md-3">

                    <small class="text-muted">
                        الفترة التجريبية
                    </small>

                    <div class="fw-bold mt-1">
                        ${plan.trial_days} يوم
                    </div>

                </div>


                <div class="col-md-3">

                    <small class="text-muted">
                        المستخدمون
                    </small>

                    <div class="fw-bold mt-1">
                        ${plan.max_users ?? 'غير محدود'}
                    </div>

                </div>

            </div>

        `);

    }


    $('#plan_id, #billing_cycle, #use_trial')
        .on('change', updatePlanPreview);


    /*
    |--------------------------------------------------------------------------
    | Load
    |--------------------------------------------------------------------------
    */

    function loadSubscriptions(page = 1) {

        currentPage = page;


        $('#subscriptionsBody').html(`

            <tr>
                <td colspan="9"
                    class="text-center py-5 text-muted">

                    جاري تحميل الاشتراكات...

                </td>
            </tr>

        `);


        $.ajax({

            url:
                @json(route('system.subscriptions.data')),

            type: 'GET',

            data: {

                page: page,

                search:
                    $('#subscriptionSearch').val(),

                tenant_id:
                    $('#filterTenant').val(),

                status:
                    $('#filterStatus').val(),

                per_page:
                    $('#perPage').val()

            },


            success: function (response) {

                if (
                    response.data.length === 0 &&
                    page > 1
                ) {

                    loadSubscriptions(page - 1);

                    return;
                }


                renderTable(response);

                renderPagination(response);

                $('#totalSubscriptions').text(
                    response.total + ' اشتراك'
                );

            },


            error: function () {

                $('#subscriptionsBody').html(`

                    <tr>
                        <td colspan="9"
                            class="text-center text-danger py-5">

                            تعذر تحميل الاشتراكات.

                        </td>
                    </tr>

                `);

            }

        });

    }


    /*
    |--------------------------------------------------------------------------
    | Table
    |--------------------------------------------------------------------------
    */

    function renderTable(response) {

        if (!response.data.length) {

            $('#subscriptionsBody').html(`

                <tr>
                    <td colspan="9"
                        class="text-center text-muted py-5">

                        لا توجد اشتراكات.

                    </td>
                </tr>

            `);

            return;
        }


        let html = '';


        response.data.forEach(
            function (subscription, index) {

                const number =
                    ((response.current_page - 1)
                        * response.per_page)
                    + index
                    + 1;


                const cycle =
                    subscription.billing_cycle === 'yearly'
                        ? 'سنوي'
                        : 'شهري';


                html += `

                    <tr>

                        <td class="text-center text-muted">
                            ${number}
                        </td>


                        <td>

                            <div class="fw-semibold">
                                ${escapeHtml(subscription.tenant?.name)}
                            </div>

                            <small class="text-muted"
                                   dir="ltr">
                                ${escapeHtml(subscription.tenant?.code)}
                            </small>

                        </td>


                        <td>

                            <div class="fw-semibold">
                                ${escapeHtml(subscription.plan?.name)}
                            </div>

                            <small class="text-muted">
                                ${cycle}
                            </small>

                        </td>


                        <td>

                            ${
                                subscription.status === 'trial'
                                    ? '<span class="text-info">تجربة مجانية</span>'
                                    : cycle
                            }

                        </td>


                        <td>

                            <strong>
                                ${money(subscription.price)}
                            </strong>

                            <small class="text-muted">
                                ${escapeHtml(subscription.currency_code)}
                            </small>

                        </td>


                        <td>
                            ${dateText(subscription.starts_at)}
                        </td>


                        <td>
                            ${dateText(subscription.ends_at)}
                        </td>


                        <td class="text-center">
                            ${statusBadge(subscription.status)}
                        </td>


                        <td class="text-center">

                        <td class="text-center">

                            ${actionButtons(subscription)}

                        </td>

                        </td>

                    </tr>

                `;

            }
        );


        $('#subscriptionsBody').html(html);
    }


    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    function renderPagination(response) {

        let html = '';

        const current =
            response.current_page;

        const last =
            response.last_page;


        if (last > 1) {

            html += `

                <li class="page-item ${current === 1 ? 'disabled' : ''}">

                    <button class="page-link subscription-page"
                            data-page="${current - 1}">

                        السابق

                    </button>

                </li>

            `;


            const start =
                Math.max(1, current - 2);

            const end =
                Math.min(last, current + 2);


            for (
                let page = start;
                page <= end;
                page++
            ) {

                html += `

                    <li class="page-item ${page === current ? 'active' : ''}">

                        <button class="page-link subscription-page"
                                data-page="${page}">

                            ${page}

                        </button>

                    </li>

                `;

            }


            html += `

                <li class="page-item ${current === last ? 'disabled' : ''}">

                    <button class="page-link subscription-page"
                            data-page="${current + 1}">

                        التالي

                    </button>

                </li>

            `;
        }


        $('#pagination').html(html);


        if (response.total) {

            $('#paginationInfo').text(

                `عرض ${response.from} إلى ${response.to} من ${response.total}`

            );

        } else {

            $('#paginationInfo').text(
                'لا توجد سجلات'
            );

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Open Create
    |--------------------------------------------------------------------------
    */

    $('#btnAddSubscription').on(
        'click',
        function () {

            $('#subscriptionForm')[0].reset();

            $('#starts_at')
                .val(
                    new Date()
                        .toISOString()
                        .slice(0, 10)
                );

            $('#use_trial').val('0');
            $('#auto_renew').val('0');
            $('#billing_cycle').val('monthly');

            updatePlanPreview();

            createModal.show();

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Create Subscription
    |--------------------------------------------------------------------------
    */

    $('#subscriptionForm').on(
        'submit',
        function (event) {

            event.preventDefault();


            const button =
                $('#btnSaveSubscription');

            const oldText =
                button.text();


            button
                .prop('disabled', true)
                .text('جاري الإنشاء...');


            $.ajax({

                url:
                    @json(route('system.subscriptions.store')),

                type: 'POST',

                data:
                    $(this).serialize(),


                success: function (response) {

                    createModal.hide();


                    Swal.fire({

                        icon: 'success',

                        title: 'تم بنجاح',

                        text: response.message,

                        timer: 1700,

                        showConfirmButton: false

                    });


                    loadSubscriptions(1);

                },


                error: function (xhr) {

                    showError(xhr);

                },


                complete: function () {

                    button
                        .prop('disabled', false)
                        .text(oldText);

                }

            });

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Details
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'click',
        '.btn-details',
        function () {

            const id =
                $(this).data('id');


            const url =

                @json(
                    route(
                        'system.subscriptions.show',
                        ['subscription' => '__ID__']
                    )
                )
                .replace('__ID__', id);


            $('#subscriptionDetails').html(
                'جاري تحميل البيانات...'
            );


            detailsModal.show();


            $.ajax({

                url: url,

                type: 'GET',


                success: function (response) {

                    const item =
                        response.subscription;


                    const cycle =
                        item.billing_cycle === 'yearly'
                            ? 'سنوي'
                            : 'شهري';


                    $('#subscriptionDetails').html(`

                        <div class="row g-4">

                            <div class="col-md-6">

                                <small class="text-muted">
                                    العميل
                                </small>

                                <div class="fw-bold mt-1">
                                    ${escapeHtml(item.tenant?.name)}
                                </div>

                            </div>


                            <div class="col-md-6">

                                <small class="text-muted">
                                    الباقة
                                </small>

                                <div class="fw-bold mt-1">
                                    ${escapeHtml(item.plan?.name)}
                                </div>

                            </div>


                            <div class="col-md-4">

                                <small class="text-muted">
                                    الحالة
                                </small>

                                <div class="mt-2">
                                    ${statusBadge(item.status)}
                                </div>

                            </div>


                            <div class="col-md-4">

                                <small class="text-muted">
                                    دورة الفوترة
                                </small>

                                <div class="fw-bold mt-1">
                                    ${cycle}
                                </div>

                            </div>


                            <div class="col-md-4">

                                <small class="text-muted">
                                    القيمة
                                </small>

                                <div class="fw-bold mt-1">

                                    ${money(item.price)}
                                    ${escapeHtml(item.currency_code)}

                                </div>

                            </div>


                            <div class="col-md-4">

                                <small class="text-muted">
                                    البداية
                                </small>

                                <div class="mt-1">
                                    ${dateText(item.starts_at)}
                                </div>

                            </div>


                            <div class="col-md-4">

                                <small class="text-muted">
                                    نهاية التجربة
                                </small>

                                <div class="mt-1">
                                    ${dateText(item.trial_ends_at)}
                                </div>

                            </div>


                            <div class="col-md-4">

                                <small class="text-muted">
                                    النهاية
                                </small>

                                <div class="mt-1">
                                    ${dateText(item.ends_at)}
                                </div>

                            </div>


                            ${item.cancellation_reason ? `

                                <div class="col-12">

                                    <div class="p-3 rounded-3 bg-danger-subtle">

                                        <small class="text-danger">
                                            سبب الإلغاء
                                        </small>

                                        <div class="mt-2 fw-semibold">
                                            ${escapeHtml(item.cancellation_reason)}
                                        </div>

                                        ${
                                            item.cancelled_at
                                                ? `
                                                    <small class="text-muted d-block mt-2">
                                                        تاريخ الإلغاء:
                                                        ${dateText(item.cancelled_at)}
                                                    </small>
                                                `
                                                : ''
                                        }

                                    </div>

                                </div>

                            ` : ''}

                            <div class="col-12">

                                <hr>

                                <small class="text-muted">
                                    رقم الاشتراك
                                </small>

                                <div class="mt-1"
                                     dir="ltr">

                                    ${escapeHtml(item.uuid)}

                                </div>

                            </div>

                        </div>

                    `);

                },


                error: function (xhr) {

                    detailsModal.hide();

                    showError(xhr);

                }

            });

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    */

    $('#subscriptionSearch').on(
        'input',
        function () {

            clearTimeout(searchTimer);

            searchTimer =
                setTimeout(
                    function () {
                        loadSubscriptions(1);
                    },
                    350
                );

        }
    );


    $('#filterTenant, #filterStatus, #perPage')
        .on('change', function () {

            loadSubscriptions(1);

        });


    $('#resetFilters').on(
        'click',
        function () {

            $('#subscriptionSearch').val('');
            $('#filterTenant').val('');
            $('#filterStatus').val('');
            $('#perPage').val('15');

            loadSubscriptions(1);

        }
    );


    $(document).on(
        'click',
        '.subscription-page',
        function () {

            if (
                $(this)
                    .closest('.page-item')
                    .hasClass('disabled')
            ) {
                return;
            }


            loadSubscriptions(
                $(this).data('page')
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Errors
    |--------------------------------------------------------------------------
    */

    function showError(xhr) {

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

            const first =
                Object.values(errors)[0];

            if (first?.length) {
                message = first[0];
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
    | Billing Cycle
    |--------------------------------------------------------------------------
    */

    async function askBillingCycle(defaultValue = 'monthly') {

        const result = await Swal.fire({

            title: 'دورة الفوترة',

            input: 'select',

            inputOptions: {
                monthly: 'شهري',
                yearly: 'سنوي'
            },

            inputValue: defaultValue,

            showCancelButton: true,

            confirmButtonText: 'متابعة',

            cancelButtonText: 'إلغاء',

            inputValidator: function (value) {

                if (!value) {
                    return 'اختر دورة الفوترة';
                }

            }

        });


        if (!result.isConfirmed) {
            return null;
        }


        return result.value;
    }


    /*
    |--------------------------------------------------------------------------
    | Convert Trial
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'click',
        '.btn-convert-trial',
        async function () {

            const id = $(this).data('id');

            const billingCycle =
                await askBillingCycle(
                    $(this).data('cycle')
                );


            if (!billingCycle) {
                return;
            }


            Swal.fire({

                icon: 'question',

                title: 'تحويل الاشتراك؟',

                text:
                    'سيتم إنهاء الفترة التجريبية وبدء اشتراك مدفوع جديد.',

                showCancelButton: true,

                confirmButtonText:
                    'نعم، تحويل إلى مدفوع',

                cancelButtonText:
                    'إلغاء'

            }).then(function (result) {

                if (!result.isConfirmed) {
                    return;
                }


                $.ajax({

                    url:
                        subscriptionUrl(
                            subscriptionRoutes.convertTrial,
                            id
                        ),

                    type: 'POST',

                    data: {
                        billing_cycle:
                            billingCycle
                    },

                    success: function (response) {

                        actionSuccess(
                            response.message
                        );

                    },

                    error: showError

                });

            });

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Renew
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'click',
        '.btn-renew',
        async function () {

            const id = $(this).data('id');

            const billingCycle =
                await askBillingCycle(
                    $(this).data('cycle')
                );


            if (!billingCycle) {
                return;
            }


            Swal.fire({

                icon: 'question',

                title: 'تجديد الاشتراك؟',

                text:
                    'سيتم إنشاء فترة اشتراك جديدة مع الاحتفاظ بالسجل السابق.',

                showCancelButton: true,

                confirmButtonText:
                    'تجديد',

                cancelButtonText:
                    'إلغاء'

            }).then(function (result) {

                if (!result.isConfirmed) {
                    return;
                }


                $.ajax({

                    url:
                        subscriptionUrl(
                            subscriptionRoutes.renew,
                            id
                        ),

                    type: 'POST',

                    data: {
                        billing_cycle:
                            billingCycle
                    },

                    success: function (response) {

                        actionSuccess(
                            response.message
                        );

                    },

                    error: showError

                });

            });

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Change Plan
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'click',
        '.btn-change-plan',
        function () {

            const id =
                $(this).data('id');

            const currentPlan =
                Number(
                    $(this).data('plan-id')
                );


            let options = `
                <option value="">
                    اختر الباقة
                </option>
            `;


            Object.values(plans).forEach(
                function (plan) {

                    if (
                        Number(plan.id) ===
                        currentPlan
                    ) {
                        return;
                    }


                    options += `
                        <option value="${plan.id}">
                            ${escapeHtml(plan.name)}
                        </option>
                    `;

                }
            );


            Swal.fire({

                title: 'تغيير الباقة',

                html: `

                    <div class="text-end">

                        <label class="form-label">
                            الباقة الجديدة
                        </label>

                        <select id="changePlanId"
                                class="form-select mb-3">

                            ${options}

                        </select>


                        <label class="form-label">
                            دورة الفوترة
                        </label>

                        <select id="changePlanCycle"
                                class="form-select">

                            <option value="monthly">
                                شهري
                            </option>

                            <option value="yearly">
                                سنوي
                            </option>

                        </select>

                    </div>

                `,

                showCancelButton: true,

                confirmButtonText:
                    'تغيير الباقة',

                cancelButtonText:
                    'إلغاء',

                preConfirm: function () {

                    const planId =
                        $('#changePlanId').val();

                    const cycle =
                        $('#changePlanCycle').val();


                    if (!planId) {

                        Swal.showValidationMessage(
                            'اختر الباقة الجديدة'
                        );

                        return false;
                    }


                    return {
                        plan_id: planId,
                        billing_cycle: cycle
                    };
                }

            }).then(function (result) {

                if (!result.isConfirmed) {
                    return;
                }


                $.ajax({

                    url:
                        subscriptionUrl(
                            subscriptionRoutes.changePlan,
                            id
                        ),

                    type: 'POST',

                    data: result.value,

                    success: function (response) {

                        actionSuccess(
                            response.message
                        );

                    },

                    error: showError

                });

            });

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Suspend
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'click',
        '.btn-suspend',
        function () {

            const id =
                $(this).data('id');


            Swal.fire({

                icon: 'warning',

                title: 'تعليق الاشتراك؟',

                text:
                    'سيتم منع العميل من استخدام النظام حتى إعادة التفعيل.',

                showCancelButton: true,

                confirmButtonText:
                    'تعليق',

                cancelButtonText:
                    'إلغاء',

                confirmButtonColor:
                    '#f59e0b'

            }).then(function (result) {

                if (!result.isConfirmed) {
                    return;
                }


                $.ajax({

                    url:
                        subscriptionUrl(
                            subscriptionRoutes.suspend,
                            id
                        ),

                    type: 'POST',

                    success: function (response) {

                        actionSuccess(
                            response.message
                        );

                    },

                    error: showError

                });

            });

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Resume
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'click',
        '.btn-resume',
        function () {

            const id =
                $(this).data('id');


            Swal.fire({

                icon: 'question',

                title: 'إعادة تفعيل الاشتراك؟',

                showCancelButton: true,

                confirmButtonText:
                    'إعادة التفعيل',

                cancelButtonText:
                    'إلغاء'

            }).then(function (result) {

                if (!result.isConfirmed) {
                    return;
                }


                $.ajax({

                    url:
                        subscriptionUrl(
                            subscriptionRoutes.resume,
                            id
                        ),

                    type: 'POST',

                    success: function (response) {

                        actionSuccess(
                            response.message
                        );

                    },

                    error: showError

                });

            });

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Cancel
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'click',
        '.btn-cancel',
        function () {

            const id =
                $(this).data('id');


            Swal.fire({

                icon: 'warning',

                title: 'إلغاء الاشتراك',

                input: 'textarea',

                inputLabel: 'سبب الإلغاء',

                inputPlaceholder:
                    'اكتب سبب إلغاء الاشتراك...',

                inputAttributes: {
                    maxlength: 1000
                },

                showCancelButton: true,

                confirmButtonText:
                    'إلغاء الاشتراك',

                cancelButtonText:
                    'رجوع',

                confirmButtonColor:
                    '#dc3545',

                inputValidator: function (value) {

                    if (!value?.trim()) {

                        return 'سبب الإلغاء مطلوب';

                    }

                }

            }).then(function (result) {

                if (!result.isConfirmed) {
                    return;
                }


                $.ajax({

                    url:
                        subscriptionUrl(
                            subscriptionRoutes.cancel,
                            id
                        ),

                    type: 'POST',

                    data: {
                        cancellation_reason:
                            result.value
                    },

                    success: function (response) {

                        actionSuccess(
                            response.message
                        );

                    },

                    error: showError

                });

            });

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Action Success
    |--------------------------------------------------------------------------
    */

    function actionSuccess(message) {

        Swal.fire({

            icon: 'success',

            title: 'تم بنجاح',

            text: message,

            timer: 1700,

            showConfirmButton: false

        });


        loadSubscriptions(
            currentPage
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Start
    |--------------------------------------------------------------------------
    */

    loadSubscriptions();

});

</script>

@endpush