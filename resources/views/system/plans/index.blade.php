@extends('layouts.system')

@section('title', 'الباقات')
@section('page-title', 'إدارة الباقات')

@section('content')

<div class="container-fluid p-0">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

        <div>
            <h4 class="mb-1">باقات النظام</h4>
            <div class="text-muted">
                إدارة أسعار وحدود الباقات في منصة رؤية يوم
            </div>
        </div>

        <button type="button"
                class="btn btn-primary px-4"
                id="btnAddPlan">

            + إضافة باقة

        </button>

    </div>


    {{-- Filters --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">

        <div class="card-body">

            <div class="row g-3">

                <div class="col-lg-5">

                    <label class="form-label">البحث</label>

                    <input type="text"
                           id="planSearch"
                           class="form-control"
                           placeholder="اسم أو كود الباقة...">

                </div>


                <div class="col-lg-3">

                    <label class="form-label">الحالة</label>

                    <select id="planStatus"
                            class="form-select">

                        <option value="">جميع الحالات</option>
                        <option value="1">نشطة</option>
                        <option value="0">غير نشطة</option>

                    </select>

                </div>


                <div class="col-lg-2">

                    <label class="form-label">عدد السجلات</label>

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

            <div class="d-flex justify-content-between">

                <h6 class="mb-0">قائمة الباقات</h6>

                <span class="badge bg-primary-subtle text-primary"
                      id="totalPlans">
                    0 باقة
                </span>

            </div>

        </div>


        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead class="table-light">

                <tr>
                    <th class="text-center">#</th>
                    <th>الباقة</th>
                    <th>السعر الشهري</th>
                    <th>السعر السنوي</th>
                    <th>الفترة التجريبية</th>
                    <th>الحدود</th>
                    <th class="text-center">الحالة</th>
                    <th class="text-center">الإجراءات</th>
                </tr>

                </thead>

                <tbody id="plansTableBody">

                <tr>
                    <td colspan="8"
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

                <ul class="pagination pagination-sm mb-0"
                    id="pagination">
                </ul>

            </div>

        </div>

    </div>

</div>


{{-- Modal --}}
<div class="modal fade"
     id="planModal"
     tabindex="-1">

    <div class="modal-dialog modal-xl modal-dialog-centered">

        <div class="modal-content border-0 rounded-4">

            <form id="planForm">

                <div class="modal-header">

                    <h5 class="modal-title"
                        id="planModalTitle">
                        إضافة باقة
                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal">
                    </button>

                </div>


                <div class="modal-body p-4">

                    <input type="hidden"
                           id="planId">


                    {{-- Basic --}}
                    <h6 class="mb-3">معلومات الباقة</h6>

                    <div class="row g-3">

                        <div class="col-md-4">

                            <label class="form-label">
                                كود الباقة *
                            </label>

                            <input type="text"
                                   name="code"
                                   id="code"
                                   class="form-control"
                                   dir="ltr"
                                   placeholder="PRO">

                        </div>


                        <div class="col-md-5">

                            <label class="form-label">
                                اسم الباقة *
                            </label>

                            <input type="text"
                                   name="name"
                                   id="name"
                                   class="form-control"
                                   placeholder="الاحترافية">

                        </div>


                        <div class="col-md-3">

                            <label class="form-label">
                                الحالة
                            </label>

                            <select name="is_active"
                                    id="is_active"
                                    class="form-select">

                                <option value="1">نشطة</option>
                                <option value="0">غير نشطة</option>

                            </select>

                        </div>


                        <div class="col-12">

                            <label class="form-label">
                                الوصف
                            </label>

                            <textarea name="description"
                                      id="description"
                                      rows="2"
                                      class="form-control"></textarea>

                        </div>

                    </div>


                    <hr class="my-4">


                    {{-- Prices --}}
                    <h6 class="mb-3">التسعير</h6>

                    <div class="row g-3">

                        <div class="col-md-3">

                            <label class="form-label">
                                السعر الشهري *
                            </label>

                            <input type="number"
                                   name="monthly_price"
                                   id="monthly_price"
                                   class="form-control"
                                   min="0"
                                   step="0.01"
                                   value="0">

                        </div>


                        <div class="col-md-3">

                            <label class="form-label">
                                السعر السنوي *
                            </label>

                            <input type="number"
                                   name="yearly_price"
                                   id="yearly_price"
                                   class="form-control"
                                   min="0"
                                   step="0.01"
                                   value="0">

                        </div>


                        <div class="col-md-2">

                            <label class="form-label">
                                العملة
                            </label>

                            <select name="currency_code"
                                    id="currency_code"
                                    class="form-select">

                                <option value="SAR">SAR</option>
                                <option value="AED">AED</option>
                                <option value="KWD">KWD</option>
                                <option value="BHD">BHD</option>
                                <option value="OMR">OMR</option>
                                <option value="QAR">QAR</option>

                            </select>

                        </div>


                        <div class="col-md-2">

                            <label class="form-label">
                                أيام التجربة
                            </label>

                            <input type="number"
                                   name="trial_days"
                                   id="trial_days"
                                   class="form-control"
                                   min="0"
                                   value="15">

                        </div>


                        <div class="col-md-2">

                            <label class="form-label">
                                ترتيب العرض
                            </label>

                            <input type="number"
                                   name="sort_order"
                                   id="sort_order"
                                   class="form-control"
                                   min="0"
                                   value="0">

                        </div>

                    </div>


                    <hr class="my-4">


                    {{-- Limits --}}
                    <h6 class="mb-2">حدود الباقة</h6>

                    <div class="text-muted small mb-3">
                        اترك الحقل فارغًا إذا كان الاستخدام غير محدود.
                    </div>


                    <div class="row g-3">

                        <div class="col-md-4">

                            <label class="form-label">
                                الحد الأقصى للمستخدمين
                            </label>

                            <input type="number"
                                   name="max_users"
                                   id="max_users"
                                   class="form-control"
                                   min="1"
                                   placeholder="غير محدود">

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                الحد الأقصى للموظفين
                            </label>

                            <input type="number"
                                   name="max_employees"
                                   id="max_employees"
                                   class="form-control"
                                   min="1"
                                   placeholder="غير محدود">

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                الحد الأقصى للفروع
                            </label>

                            <input type="number"
                                   name="max_branches"
                                   id="max_branches"
                                   class="form-control"
                                   min="1"
                                   placeholder="غير محدود">

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
                            id="btnSavePlan">
                        حفظ الباقة
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

    let currentPage = 1;
    let searchTimer = null;

    const planModal =
        bootstrap.Modal.getOrCreateInstance(
            document.getElementById('planModal')
        );


    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN':
                $('meta[name="csrf-token"]').attr('content'),

            'Accept': 'application/json'
        }
    });


    function escapeHtml(value) {
        return $('<div>').text(value ?? '').html();
    }


    function money(value) {
        return Number(value ?? 0)
            .toLocaleString('ar-SA', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
    }


    function limit(value) {

        if (
            value === null ||
            value === ''
        ) {
            return 'غير محدود';
        }

        return value;
    }


    function statusBadge(active) {

        if (active) {
            return `
                <span class="badge bg-success-subtle text-success px-3 py-2">
                    نشطة
                </span>
            `;
        }

        return `
            <span class="badge bg-secondary-subtle text-secondary px-3 py-2">
                غير نشطة
            </span>
        `;
    }


    /*
    |--------------------------------------------------------------------------
    | Load
    |--------------------------------------------------------------------------
    */

    function loadPlans(page = 1) {

        currentPage = page;

        $('#plansTableBody').html(`
            <tr>
                <td colspan="8"
                    class="text-center py-5 text-muted">
                    جاري تحميل البيانات...
                </td>
            </tr>
        `);


        $.ajax({

            url: @json(route('system.plans.data')),

            type: 'GET',

            data: {
                page: page,
                search: $('#planSearch').val(),
                status: $('#planStatus').val(),
                per_page: $('#perPage').val()
            },

            success: function (response) {

                if (
                    response.data.length === 0 &&
                    page > 1
                ) {
                    loadPlans(page - 1);
                    return;
                }

                renderTable(response);
                renderPagination(response);

                $('#totalPlans').text(
                    response.total + ' باقة'
                );
            },

            error: function () {

                $('#plansTableBody').html(`
                    <tr>
                        <td colspan="8"
                            class="text-center text-danger py-5">

                            تعذر تحميل الباقات.

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

            $('#plansTableBody').html(`
                <tr>
                    <td colspan="8"
                        class="text-center text-muted py-5">

                        لا توجد باقات.

                    </td>
                </tr>
            `);

            return;
        }


        let html = '';


        response.data.forEach(function (plan, index) {

            const number =
                ((response.current_page - 1)
                    * response.per_page)
                + index
                + 1;


            html += `

                <tr>

                    <td class="text-center text-muted">
                        ${number}
                    </td>


                    <td>

                        <div class="fw-semibold">
                            ${escapeHtml(plan.name)}
                        </div>

                        <small class="text-muted"
                               dir="ltr">
                            ${escapeHtml(plan.code)}
                        </small>

                    </td>


                    <td>

                        <strong>
                            ${money(plan.monthly_price)}
                        </strong>

                        <small class="text-muted">
                            ${escapeHtml(plan.currency_code)}
                        </small>

                    </td>


                    <td>

                        <strong>
                            ${money(plan.yearly_price)}
                        </strong>

                        <small class="text-muted">
                            ${escapeHtml(plan.currency_code)}
                        </small>

                    </td>


                    <td>
                        ${plan.trial_days} يوم
                    </td>


                    <td class="small">

                        <div>
                            المستخدمون:
                            <strong>${limit(plan.max_users)}</strong>
                        </div>

                        <div>
                            الموظفون:
                            <strong>${limit(plan.max_employees)}</strong>
                        </div>

                        <div>
                            الفروع:
                            <strong>${limit(plan.max_branches)}</strong>
                        </div>

                    </td>


                    <td class="text-center">
                        ${statusBadge(plan.is_active)}
                    </td>


                    <td class="text-center">

                        <div class="btn-group btn-group-sm">
                            <a href="${
                                @json(route('system.plans.features.edit', ['plan' => '__ID__']))
                                    .replace('__ID__', plan.id)
                            }"
                               class="btn btn-light border text-primary">

                                الخصائص

                            </a>
                            <button type="button"
                                    class="btn btn-light border btn-edit-plan"
                                    data-id="${plan.id}">
                                تعديل
                            </button>

                            <button type="button"
                                    class="btn btn-light border text-danger btn-delete-plan"
                                    data-id="${plan.id}"
                                    data-name="${escapeHtml(plan.name)}">
                                حذف
                            </button>

                        </div>

                    </td>

                </tr>

            `;

        });


        $('#plansTableBody').html(html);
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

                    <button class="page-link plan-page"
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

                        <button class="page-link plan-page"
                                data-page="${page}">
                            ${page}
                        </button>

                    </li>

                `;

            }


            html += `
                <li class="page-item ${current === last ? 'disabled' : ''}">

                    <button class="page-link plan-page"
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
    | New
    |--------------------------------------------------------------------------
    */

    $('#btnAddPlan').on('click', function () {

        $('#planForm')[0].reset();

        $('#planId').val('');

        $('#code')
            .prop('disabled', false)
            .val('');

        $('#monthly_price').val('0');
        $('#yearly_price').val('0');
        $('#currency_code').val('SAR');
        $('#trial_days').val('15');
        $('#sort_order').val('0');
        $('#is_active').val('1');

        $('#planModalTitle').text(
            'إضافة باقة جديدة'
        );

        $('#btnSavePlan').text(
            'حفظ الباقة'
        );

        planModal.show();

    });


    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'click',
        '.btn-edit-plan',
        function () {

            const id = $(this).data('id');

            const url =
                @json(route('system.plans.show', ['plan' => '__ID__']))
                    .replace('__ID__', id);


            $.ajax({

                url: url,

                type: 'GET',

                success: function (response) {

                    const plan = response.plan;

                    $('#planId').val(plan.id);

                    $('#code')
                        .val(plan.code)
                        .prop('disabled', true);

                    $('#name').val(plan.name);
                    $('#description').val(plan.description);

                    $('#monthly_price')
                        .val(plan.monthly_price);

                    $('#yearly_price')
                        .val(plan.yearly_price);

                    $('#currency_code')
                        .val(plan.currency_code);

                    $('#trial_days')
                        .val(plan.trial_days);

                    $('#max_users')
                        .val(plan.max_users);

                    $('#max_employees')
                        .val(plan.max_employees);

                    $('#max_branches')
                        .val(plan.max_branches);

                    $('#is_active')
                        .val(plan.is_active ? '1' : '0');

                    $('#sort_order')
                        .val(plan.sort_order);


                    $('#planModalTitle').text(
                        'تعديل الباقة'
                    );

                    $('#btnSavePlan').text(
                        'حفظ التعديلات'
                    );

                    planModal.show();

                },

                error: function (xhr) {
                    showError(xhr);
                }

            });

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Save
    |--------------------------------------------------------------------------
    */

    $('#planForm').on(
        'submit',
        function (event) {

            event.preventDefault();

            const id = $('#planId').val();

            let url;
            let method;


            if (id) {

                url =
                    @json(route('system.plans.update', ['plan' => '__ID__']))
                        .replace('__ID__', id);

                method = 'PUT';

            } else {

                url =
                    @json(route('system.plans.store'));

                method = 'POST';

            }


            const button = $('#btnSavePlan');
            const oldText = button.text();

            button
                .prop('disabled', true)
                .text('جاري الحفظ...');


            $.ajax({

                url: url,
                type: method,
                data: $('#planForm').serialize(),

                success: function (response) {

                    planModal.hide();

                    Swal.fire({
                        icon: 'success',
                        title: 'تم بنجاح',
                        text: response.message,
                        timer: 1600,
                        showConfirmButton: false
                    });

                    loadPlans(currentPage);

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
    | Delete
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'click',
        '.btn-delete-plan',
        function () {

            const id = $(this).data('id');
            const name = $(this).data('name');


            Swal.fire({

                icon: 'warning',

                title: 'حذف الباقة؟',

                html:
                    `هل أنت متأكد من حذف <strong>${name}</strong>؟`,

                showCancelButton: true,

                confirmButtonText: 'نعم، حذف',

                cancelButtonText: 'إلغاء',

                confirmButtonColor: '#dc3545'

            }).then(function (result) {

                if (!result.isConfirmed) {
                    return;
                }


                const url =
                    @json(route('system.plans.destroy', ['plan' => '__ID__']))
                        .replace('__ID__', id);


                $.ajax({

                    url: url,

                    type: 'DELETE',

                    success: function (response) {

                        Swal.fire({
                            icon: 'success',
                            title: 'تم',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        });

                        loadPlans(currentPage);
                    },

                    error: function (xhr) {
                        showError(xhr);
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

    $('#planSearch').on('input', function () {

        clearTimeout(searchTimer);

        searchTimer = setTimeout(function () {
            loadPlans(1);
        }, 350);

    });


    $('#planStatus, #perPage').on(
        'change',
        function () {
            loadPlans(1);
        }
    );


    $('#btnResetFilters').on(
        'click',
        function () {

            $('#planSearch').val('');
            $('#planStatus').val('');
            $('#perPage').val('15');

            loadPlans(1);
        }
    );


    $(document).on(
        'click',
        '.plan-page',
        function () {

            if (
                $(this)
                    .closest('.page-item')
                    .hasClass('disabled')
            ) {
                return;
            }

            loadPlans(
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
    | Start
    |--------------------------------------------------------------------------
    */

    loadPlans();

});

</script>

@endpush