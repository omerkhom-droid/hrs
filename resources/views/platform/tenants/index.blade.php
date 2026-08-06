@extends('layouts.app')

@section('content')

<div class="container-fluid px-4" dir="rtl">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h3 class="mb-1">إدارة العملاء</h3>

            <div class="text-muted">
                إدارة الشركات المشتركة في منصة رؤية يوم
            </div>
        </div>

        <button
            type="button"
            class="btn btn-primary"
            id="addTenantBtn">
            + إضافة عميل
        </button>

    </div>


    <div class="card border-0 shadow-sm">

        <div class="card-body">

            <div class="table-responsive">

                <table
                    id="tenantsTable"
                    class="table table-hover align-middle w-100">

                    <thead class="table-light">

                        <tr>
                            <th>#</th>
                            <th>العميل</th>
                            <th>المعرّف</th>
                            <th>الخطة</th>
                            <th>المستخدمون</th>
                            <th>الاشتراك</th>
                            <th>ينتهي في</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>

                    </thead>

                    <tbody></tbody>

                </table>

            </div>

        </div>

    </div>

</div>


{{-- Modal --}}
<div
    class="modal fade"
    id="tenantModal"
    tabindex="-1"
    aria-hidden="true"
    dir="rtl">

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content">

            <form id="tenantForm">

                @csrf

                <div class="modal-header">

                    <h5
                        class="modal-title"
                        id="tenantModalTitle">
                        إضافة عميل
                    </h5>

                    <button
                        type="button"
                        class="btn-close ms-0"
                        data-bs-dismiss="modal">
                    </button>

                </div>


                <div class="modal-body">

                    <div
                        class="alert alert-danger d-none"
                        id="formErrors">
                    </div>


                    {{-- بيانات العميل --}}
                    <h6 class="mb-3">
                        بيانات العميل
                    </h6>

                    <div class="row g-3">

                        <div class="col-md-6">

                            <label class="form-label">
                                اسم العميل
                                <span class="text-danger">*</span>
                            </label>

                            <input
                                type="text"
                                name="name"
                                id="name"
                                class="form-control"
                                required>

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                المعرّف بالإنجليزية
                                <span class="text-danger">*</span>
                            </label>

                            <input
                                type="text"
                                name="slug"
                                id="slug"
                                class="form-control"
                                dir="ltr"
                                placeholder="example-company"
                                required>

                            <small class="text-muted">
                                أحرف إنجليزية صغيرة وأرقام و -
                            </small>

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                بريد المنشأة
                                <span class="text-danger">*</span>
                            </label>

                            <input
                                type="email"
                                name="email"
                                id="email"
                                class="form-control"
                                dir="ltr"
                                required>

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                رقم التواصل
                            </label>

                            <input
                                type="text"
                                name="phone"
                                id="phone"
                                class="form-control"
                                dir="ltr">

                        </div>

                    </div>


                    {{-- فقط عند إنشاء العميل --}}
                    <div class="creation-only">

                        <hr class="my-4">


                        {{-- الاشتراك --}}
                        <h6 class="mb-3">
                            بيانات الاشتراك
                        </h6>

                        <div class="row g-3">

                            <div class="col-md-6">

                                <label class="form-label">
                                    الخطة
                                    <span class="text-danger">*</span>
                                </label>

                                <select
                                    name="plan_id"
                                    id="plan_id"
                                    class="form-select"
                                    required>

                                    <option value="">
                                        اختر الخطة
                                    </option>

                                    @foreach($plans as $plan)

                                        <option value="{{ $plan->id }}">

                                            {{ $plan->name_ar }}

                                            @if($plan->monthly_price > 0)
                                                -
                                                {{ number_format($plan->monthly_price, 2) }}
                                                ريال
                                            @endif

                                        </option>

                                    @endforeach

                                </select>

                            </div>


                            <div class="col-md-6">

                                <label class="form-label">
                                    دورة الفوترة
                                </label>

                                <select
                                    name="billing_cycle"
                                    id="billing_cycle"
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

                        </div>


                        <hr class="my-4">


                        {{-- مدير الحساب --}}
                        <h6 class="mb-3">
                            مدير حساب العميل
                        </h6>

                        <div class="row g-3">

                            <div class="col-md-6">

                                <label class="form-label">
                                    اسم المدير
                                    <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="text"
                                    name="admin_name"
                                    id="admin_name"
                                    class="form-control"
                                    required>

                            </div>


                            <div class="col-md-6">

                                <label class="form-label">
                                    بريد المدير
                                    <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="email"
                                    name="admin_email"
                                    id="admin_email"
                                    class="form-control"
                                    dir="ltr"
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
                                    id="password"
                                    class="form-control"
                                    minlength="10"
                                    required>

                            </div>


                            <div class="col-md-6">

                                <label class="form-label">
                                    تأكيد كلمة المرور
                                    <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="password"
                                    name="password_confirmation"
                                    id="password_confirmation"
                                    class="form-control"
                                    minlength="10"
                                    required>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal">
                        إلغاء
                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary"
                        id="saveTenantBtn">
                        حفظ
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<script>

window.addEventListener('load', function () {

    /*
    |--------------------------------------------------------------------------
    | Libraries
    |--------------------------------------------------------------------------
    */

    const $ = window.jQuery;
    const bootstrap = window.bootstrap;
    const Swal = window.Swal;

    if (!$) {
        console.error('jQuery غير محمل');
        return;
    }

    if (!bootstrap) {
        console.error('Bootstrap غير محمل');
        return;
    }

    if (!$.fn || !$.fn.DataTable) {
        console.error('DataTables غير محمل');
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | CSRF
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


    /*
    |--------------------------------------------------------------------------
    | Variables
    |--------------------------------------------------------------------------
    */

    let editingTenant = null;


    const tenantModal =
        bootstrap.Modal.getOrCreateInstance(
            document.getElementById('tenantModal')
        );


    /*
    |--------------------------------------------------------------------------
    | URLs
    |--------------------------------------------------------------------------
    */

    const dataUrl =
        @json(route('platform.tenants.data'));

    const storeUrl =
        @json(route('platform.tenants.store'));

    const showUrl =
        @json(
            route(
                'platform.tenants.show',
                ['tenant' => '__TENANT__']
            )
        );

    const statusUrl =
        @json(
            route(
                'platform.tenants.status',
                ['tenant' => '__TENANT__']
            )
        );

    const deleteUrl =
        @json(
            route(
                'platform.tenants.destroy',
                ['tenant' => '__TENANT__']
            )
        );


    /*
    |--------------------------------------------------------------------------
    | DataTable
    |--------------------------------------------------------------------------
    */

    const table =
        $('#tenantsTable').DataTable({

            processing: true,

            serverSide: true,

            responsive: true,

            autoWidth: false,

            ajax: {

                url: dataUrl,

                type: 'GET',

                error: function (xhr) {

                    console.error(
                        'DataTable Error:',
                        xhr.responseText
                    );

                }

            },


            order: [
                [0, 'desc']
            ],


            columns: [

                {
                    data: 'id',
                    name: 'id'
                },

                {
                    data: 'name',
                    name: 'name'
                },

                {
                    data: 'slug',
                    name: 'slug'
                },

                {
                    data: 'plan_name',
                    name: 'plan_name',
                    orderable: false,
                    searchable: false
                },

                {
                    data: 'users_count',
                    name: 'users_count',
                    orderable: false,
                    searchable: false
                },

                {
                    data: 'subscription_status',
                    orderable: false,
                    searchable: false
                },

                {
                    data: 'ends_at',
                    orderable: false,
                    searchable: false
                },

                {
                    data: 'status',
                    name: 'status'
                },

                {
                    data: 'actions',
                    orderable: false,
                    searchable: false
                }

            ],


            language: {

                processing:
                    'جاري التحميل...',

                search:
                    'بحث:',

                lengthMenu:
                    'عرض _MENU_ سجل',

                info:
                    'عرض _START_ إلى _END_ من أصل _TOTAL_',

                infoEmpty:
                    'لا توجد بيانات',

                zeroRecords:
                    'لا توجد نتائج مطابقة',

                emptyTable:
                    'لا يوجد عملاء',

                paginate: {

                    first:
                        'الأول',

                    last:
                        'الأخير',

                    next:
                        'التالي',

                    previous:
                        'السابق'
                }
            }

        });


    /*
    |--------------------------------------------------------------------------
    | إضافة عميل
    |--------------------------------------------------------------------------
    */

    $('#addTenantBtn').on(
        'click',
        function () {

            editingTenant = null;

            $('#tenantForm')[0].reset();

            clearErrors();


            $('#tenantModalTitle').text(
                'إضافة عميل جديد'
            );


            $('.creation-only')
                .removeClass('d-none')
                .find(':input')
                .prop('disabled', false);


            tenantModal.show();

        }
    );


    /*
    |--------------------------------------------------------------------------
    | تعديل العميل
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'click',
        '.editTenant',
        function () {

            const id =
                $(this).data('id');


            clearErrors();


            $.ajax({

                url:
                    showUrl.replace(
                        '__TENANT__',
                        id
                    ),

                type:
                    'GET'

            })

            .done(function (response) {

                const tenant =
                    response.data;


                editingTenant =
                    id;


                $('#tenantForm')[0]
                    .reset();


                $('#name')
                    .val(tenant.name ?? '');

                $('#slug')
                    .val(tenant.slug ?? '');

                $('#email')
                    .val(tenant.email ?? '');

                $('#phone')
                    .val(tenant.phone ?? '');


                $('#tenantModalTitle')
                    .text(
                        'تعديل بيانات العميل'
                    );


                $('.creation-only')
                    .addClass('d-none')
                    .find(':input')
                    .prop(
                        'disabled',
                        true
                    );


                tenantModal.show();

            })

            .fail(function (xhr) {

                showAjaxError(
                    xhr,
                    'تعذر تحميل بيانات العميل'
                );

            });

        }
    );


    /*
    |--------------------------------------------------------------------------
    | حفظ / تعديل
    |--------------------------------------------------------------------------
    */

    $('#tenantForm').on(
        'submit',
        function (event) {

            event.preventDefault();


            clearErrors();


            const form =
                this;


            const button =
                $('#saveTenantBtn');


            const originalText =
                button.text();


            button
                .prop(
                    'disabled',
                    true
                )
                .text(
                    'جاري الحفظ...'
                );


            const formData =
                new FormData(form);


            let url =
                storeUrl;


            if (editingTenant) {

                url =
                    showUrl.replace(
                        '__TENANT__',
                        editingTenant
                    );


                formData.append(
                    '_method',
                    'PUT'
                );

            }


            $.ajax({

                url:
                    url,

                type:
                    'POST',

                data:
                    formData,

                processData:
                    false,

                contentType:
                    false

            })

            .done(function (response) {

                tenantModal.hide();


                table.ajax.reload(
                    null,
                    false
                );


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

            })

            .fail(function (xhr) {

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
                    'حدث خطأ أثناء حفظ البيانات'
                );

            })

            .always(function () {

                button
                    .prop(
                        'disabled',
                        false
                    )
                    .text(
                        originalText
                    );

            });

        }
    );


    /*
    |--------------------------------------------------------------------------
    | تفعيل / إيقاف
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'click',
        '.toggleTenant',
        function () {

            const id =
                $(this).data('id');


            Swal.fire({

                icon:
                    'question',

                title:
                    'تغيير حالة العميل',

                text:
                    'هل تريد تغيير حالة هذا العميل؟',

                showCancelButton:
                    true,

                confirmButtonText:
                    'نعم',

                cancelButtonText:
                    'إلغاء'

            }).then(function (result) {

                if (!result.isConfirmed) {
                    return;
                }


                $.ajax({

                    url:
                        statusUrl.replace(
                            '__TENANT__',
                            id
                        ),

                    type:
                        'PATCH'

                })

                .done(function (response) {

                    table.ajax.reload(
                        null,
                        false
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

                })

                .fail(function (xhr) {

                    showAjaxError(
                        xhr,
                        'تعذر تغيير حالة العميل'
                    );

                });

            });

        }
    );


    /*
    |--------------------------------------------------------------------------
    | أرشفة
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'click',
        '.deleteTenant',
        function () {

            const id =
                $(this).data('id');


            Swal.fire({

                icon:
                    'warning',

                title:
                    'أرشفة العميل',

                text:
                    'هل أنت متأكد من أرشفة هذا العميل؟',

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
                        deleteUrl.replace(
                            '__TENANT__',
                            id
                        ),

                    type:
                        'DELETE'

                })

                .done(function (response) {

                    table.ajax.reload(
                        null,
                        false
                    );


                    Swal.fire({

                        icon:
                            'success',

                        title:
                            'تم',

                        text:
                            response.message

                    });

                })

                .fail(function (xhr) {

                    showAjaxError(
                        xhr,
                        'تعذر أرشفة العميل'
                    );

                });

            });

        }
    );


    /*
    |--------------------------------------------------------------------------
    | تنظيف الأخطاء
    |--------------------------------------------------------------------------
    */

    function clearErrors()
    {
        $('#formErrors')
            .addClass('d-none')
            .empty();


        $('#tenantForm')
            .find('.is-invalid')
            .removeClass(
                'is-invalid'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Validation Errors
    |--------------------------------------------------------------------------
    */

    function displayErrors(errors)
    {
        const box =
            $('#formErrors');


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
                            .text(
                                message
                            )
                            .appendTo(
                                box
                            );

                    }
                );

            }
        );


        box.removeClass(
            'd-none'
        );


        const modalBody =
            document.querySelector(
                '#tenantModal .modal-body'
            );


        if (modalBody) {

            modalBody.scrollTop =
                0;

        }
    }


    /*
    |--------------------------------------------------------------------------
    | AJAX Error
    |--------------------------------------------------------------------------
    */

    function showAjaxError(
        xhr,
        fallback
    ) {

        console.error(
            'AJAX Error:',
            xhr.status,
            xhr.responseText
        );


        Swal.fire({

            icon:
                'error',

            title:
                'خطأ',

            text:
                xhr.responseJSON?.message
                ?? fallback

        });

    }

});
</script>

@endsection