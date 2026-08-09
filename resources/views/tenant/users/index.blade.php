@extends('layouts.tenant')

@section('title', 'المستخدمون والصلاحيات')

@section('content')

@php
    $roleLabels = [
        'tenant_owner' => 'مالك الحساب',
        'hr_manager' => 'مدير الموارد البشرية',
        'hr_officer' => 'موظف الموارد البشرية',
        'payroll_manager' => 'مدير الرواتب',
        'manager' => 'مدير',
        'employee' => 'موظف',
    ];
@endphp

<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h4 class="mb-1">المستخدمون</h4>

            <div class="text-muted small">
                إدارة مستخدمي النظام وأدوارهم
            </div>
        </div>

        @can('users.create')
            <button
                type="button"
                class="btn btn-primary"
                id="btnAddUser"
            >
                <i class="bi bi-person-plus"></i>
                إضافة مستخدم
            </button>
        @endcan

    </div>


    @if($roles->isEmpty())

        <div class="alert alert-warning">
            لم يتم إنشاء الأدوار لهذا الحساب بعد.
            شغّل AccessControlSeeder أولاً.
        </div>

    @endif

    <div class="card border-0 shadow-sm mb-4">

        <div class="card-body">

            <form
                method="GET"
                action="{{ route('app.users.index') }}"
                id="usersFilterForm"
            >

                <div class="row g-3">

                    <div class="col-lg-5">

                        <label class="form-label">
                            البحث
                        </label>

                        <div class="input-group">

                            <span class="input-group-text">
                                <i class="bi bi-search"></i>
                            </span>

                            <input
                                type="search"
                                name="q"
                                id="userSearch"
                                class="form-control"
                                value="{{ $search ?? request('q') }}"
                                placeholder="الاسم أو البريد الإلكتروني..."
                            >

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >
                                <i class="bi bi-search"></i>
                                بحث
                            </button>

                        </div>

                    </div>


                    <div class="col-lg-3">

                        <label class="form-label">
                            الحالة
                        </label>

                        <select
                            name="status"
                            id="statusFilter"
                            class="form-select"
                        >

                            <option value="">
                                جميع الحالات
                            </option>

                            <option
                                value="active"
                                @selected(($status ?? request('status')) === 'active')
                            >
                                فعال
                            </option>

                            <option
                                value="inactive"
                                @selected(($status ?? request('status')) === 'inactive')
                            >
                                معطل
                            </option>

                        </select>

                    </div>


                    <div class="col-lg-3">

                        <label class="form-label">
                            الدور
                        </label>

                        <select
                            name="role"
                            id="roleFilter"
                            class="form-select"
                        >

                            <option value="">
                                جميع الأدوار
                            </option>

                            @foreach($roles as $role)

                                <option
                                    value="{{ $role->id }}"
                                    @selected(
                                        (string) ($roleId ?? request('role'))
                                        ===
                                        (string) $role->id
                                    )
                                >
                                    {{
                                        $roleLabels[$role->name]
                                        ?? $role->name
                                    }}
                                </option>

                            @endforeach

                        </select>

                    </div>


                    <div class="col-lg-1 d-flex align-items-end">

                        <a
                            href="{{ route('app.users.index') }}"
                            class="btn btn-outline-secondary w-100"
                            title="مسح البحث"
                        >
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>

                    </div>

                </div>

            </form>

        </div>

    </div>

    {{-- Users Table --}}
    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white py-3">

            <div class="d-flex justify-content-between align-items-center">

                <strong>
                    قائمة المستخدمين
                </strong>

                <span class="badge bg-primary-subtle text-primary">
                    {{ $users->total() }} مستخدم
                </span>

            </div>

        </div>


        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead class="table-light">

                    <tr>
                        <th>#</th>
                        <th>المستخدم</th>
                        <th>البريد</th>
                        <th>الأدوار</th>
                        <th>الحالة</th>
                        <th>آخر دخول</th>
                        <th class="text-center">الإجراءات</th>
                    </tr>

                </thead>

                <tbody>

                @forelse($users as $user)

                    <tr>

                        <td>
                            {{ $user->id }}
                        </td>


                        <td>

                            <div class="d-flex align-items-center gap-2">

                                <div
                                    class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold"
                                    style="width:40px;height:40px;"
                                >
                                    {{ mb_substr($user->name, 0, 1) }}
                                </div>

                                <div>

                                    <div class="fw-semibold">
                                        {{ $user->name }}
                                    </div>

                                    @if(auth()->id() === $user->id)
                                        <small class="text-primary">
                                            حسابك الحالي
                                        </small>
                                    @endif

                                </div>

                            </div>

                        </td>


                        <td dir="ltr">
                            {{ $user->email }}
                        </td>


                        <td>

                            @forelse($user->roles as $role)

                                <span class="badge bg-light text-dark border me-1">
                                    {{ $roleLabels[$role->name] ?? $role->name }}
                                </span>

                            @empty

                                <span class="text-danger small">
                                    بدون دور
                                </span>

                            @endforelse

                        </td>


                        <td>

                            @if($user->is_active)

                                <span class="badge bg-success-subtle text-success">
                                    فعال
                                </span>

                            @else

                                <span class="badge bg-danger-subtle text-danger">
                                    معطل
                                </span>

                            @endif

                        </td>


                        <td>

                            @if($user->last_login_at)

                                {{ $user->last_login_at->format('Y-m-d H:i') }}

                            @else

                                <span class="text-muted">
                                    لم يسجل الدخول
                                </span>

                            @endif

                        </td>


                        <td class="text-center">

                            <div class="btn-group btn-group-sm">

                                @can('users.update')

                                    <button
                                        type="button"
                                        class="btn btn-outline-primary btn-edit-user"
                                        data-id="{{ $user->id }}"
                                        title="تعديل"
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                @endcan


                                @can('users.deactivate')

                                    @if(auth()->id() !== $user->id)

                                        <button
                                            type="button"
                                            class="btn
                                                {{ $user->is_active
                                                    ? 'btn-outline-danger'
                                                    : 'btn-outline-success'
                                                }}
                                                btn-status-user"
                                            data-id="{{ $user->id }}"
                                            data-active="{{ $user->is_active ? 1 : 0 }}"
                                            title="{{ $user->is_active ? 'تعطيل' : 'تفعيل' }}"
                                        >

                                            @if($user->is_active)

                                                <i class="bi bi-person-x"></i>

                                            @else

                                                <i class="bi bi-person-check"></i>

                                            @endif

                                        </button>

                                    @endif

                                @endcan

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="7"
                            class="text-center py-5 text-muted"
                        >
                            لا يوجد مستخدمون.
                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>


        @if($users->hasPages())

            <div class="card-footer bg-white">
                {{ $users->links() }}
            </div>

        @endif

    </div>

</div>


{{-- User Modal --}}
<div
    class="modal fade"
    id="userModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content">

            <form id="userForm">

                @csrf

                <input
                    type="hidden"
                    id="user_id"
                >


                <div class="modal-header">

                    <h5
                        class="modal-title"
                        id="userModalTitle"
                    >
                        إضافة مستخدم
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>

                </div>


                <div class="modal-body">

                    <div
                        class="alert alert-danger d-none"
                        id="formErrors"
                    ></div>


                    <div class="row g-3">

                        <div class="col-md-6">

                            <label class="form-label">
                                اسم المستخدم
                            </label>

                            <input
                                type="text"
                                name="name"
                                id="name"
                                class="form-control"
                                maxlength="150"
                                required
                            >

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                البريد الإلكتروني
                            </label>

                            <input
                                type="email"
                                name="email"
                                id="email"
                                class="form-control"
                                dir="ltr"
                                required
                            >

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                كلمة المرور
                            </label>

                            <input
                                type="password"
                                name="password"
                                id="password"
                                class="form-control"
                                autocomplete="new-password"
                            >

                            <small
                                class="text-muted"
                                id="passwordHelp"
                            >
                                10 أحرف على الأقل
                            </small>

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                تأكيد كلمة المرور
                            </label>

                            <input
                                type="password"
                                name="password_confirmation"
                                id="password_confirmation"
                                class="form-control"
                                autocomplete="new-password"
                            >

                        </div>


                        <div class="col-12">

                            <hr>

                            <label class="form-label fw-semibold mb-3">
                                الأدوار
                            </label>


                            <div class="row g-2">

                                @foreach($roles as $role)

                                    <div class="col-md-4">

                                        <label
                                            class="border rounded p-3 w-100 role-option"
                                            style="cursor:pointer;"
                                        >

                                            <div class="form-check">

                                                <input
                                                    type="checkbox"
                                                    class="form-check-input role-checkbox"
                                                    name="roles[]"
                                                    value="{{ $role->id }}"
                                                    id="role_{{ $role->id }}"
                                                >

                                                <span class="form-check-label">

                                                    {{
                                                        $roleLabels[$role->name]
                                                        ?? $role->name
                                                    }}

                                                </span>

                                            </div>

                                        </label>

                                    </div>

                                @endforeach

                            </div>

                        </div>

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal"
                    >
                        إلغاء
                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary"
                        id="btnSaveUser"
                    >
                        حفظ
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<script>

document.addEventListener('DOMContentLoaded', function () {

    if (typeof window.jQuery === 'undefined') {
        console.error('Tenant users: jQuery is not loaded.');
        return;
    }

    if (typeof window.bootstrap === 'undefined') {
        console.error('Tenant users: Bootstrap JavaScript is not loaded.');
        return;
    }

    const $ = window.jQuery;

    const csrfToken = @json(csrf_token());

    const routes = {

        store:
            @json(route('app.users.store')),

        show:
            @json(
                route(
                    'app.users.show',
                    ['user' => '__USER__']
                )
            ),

        update:
            @json(
                route(
                    'app.users.update',
                    ['user' => '__USER__']
                )
            ),

        status:
            @json(
                route(
                    'app.users.status',
                    ['user' => '__USER__']
                )
            ),
    };


    const modalElement =
        document.getElementById('userModal');

    const userModal =
        new bootstrap.Modal(modalElement);


    /*
    |--------------------------------------------------------------------------
    | Search / Filters
    |--------------------------------------------------------------------------
    */

    let searchTimer = null;

    $('#userSearch').on(
        'input',
        function () {

            clearTimeout(searchTimer);

            searchTimer = setTimeout(
                function () {

                    const form =
                        $('#usersFilterForm').get(0);

                    if (form) {
                        form.submit();
                    }
                },
                500
            );
        }
    );


    $('#statusFilter, #roleFilter').on(
        'change',
        function () {

            const form =
                $('#usersFilterForm').get(0);

            if (form) {
                form.submit();
            }
        }
    );


    function userUrl(url, id) {

        return url.replace(
            '__USER__',
            id
        );
    }


    function resetForm() {

        $('#userForm')[0].reset();

        $('#user_id').val('');

        $('#formErrors')
            .addClass('d-none')
            .empty();

        $('.role-checkbox')
            .prop('checked', false);

        $('#password')
            .prop('required', false);

        $('#password_confirmation')
            .prop('required', false);
    }


    function showError(xhr) {

        let message =
            'حدث خطأ غير متوقع.';


        if (xhr.responseJSON) {

            if (xhr.responseJSON.errors) {

                const messages = [];

                $.each(
                    xhr.responseJSON.errors,
                    function (key, errors) {

                        $.each(
                            errors,
                            function (index, error) {

                                messages.push(error);

                            }
                        );
                    }
                );

                message =
                    messages.join('\n');

            } else if (xhr.responseJSON.message) {

                message =
                    xhr.responseJSON.message;
            }
        }


        $('#formErrors')
            .removeClass('d-none')
            .text(message);


        if (window.Swal) {

            Swal.fire({
                icon: 'error',
                title: 'تعذر تنفيذ العملية',
                text: message,
            });
        }
    }


    function successMessage(message) {

        if (window.Swal) {

            Swal.fire({
                icon: 'success',
                title: 'تم',
                text: message,
                timer: 1200,
                showConfirmButton: false,

            }).then(function () {

                window.location.reload();

            });

            return;
        }

        alert(message);

        window.location.reload();
    }


    /*
    |--------------------------------------------------------------------------
    | Add
    |--------------------------------------------------------------------------
    */

    $('#btnAddUser').on(
        'click',
        function () {

            resetForm();

            $('#userModalTitle')
                .text('إضافة مستخدم');

            $('#passwordHelp')
                .text('10 أحرف على الأقل');

            $('#password')
                .prop('required', true);

            $('#password_confirmation')
                .prop('required', true);

            userModal.show();
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'click',
        '.btn-edit-user',
        function () {

            const id =
                $(this).data('id');

            resetForm();

            $('#userModalTitle')
                .text('تعديل المستخدم');

            $('#passwordHelp')
                .text(
                    'اترك كلمة المرور فارغة إذا لم ترغب بتغييرها'
                );


            $.ajax({

                url:
                    userUrl(
                        routes.show,
                        id
                    ),

                type: 'GET',

                dataType: 'json',

                success: function (response) {

                    const user =
                        response.user;

                    $('#user_id')
                        .val(user.id);

                    $('#name')
                        .val(user.name);

                    $('#email')
                        .val(user.email);


                    const userRoles =
                        (user.roles || [])
                        .map(String);


                    $('.role-checkbox')
                        .each(function () {

                            const selected =
                                userRoles.includes(
                                    String(
                                        $(this).val()
                                    )
                                );

                            $(this)
                                .prop(
                                    'checked',
                                    selected
                                );
                        });


                    userModal.show();
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

    $('#userForm').on(
        'submit',
        function (event) {

            event.preventDefault();


            if (
                $('.role-checkbox:checked')
                    .length === 0
            ) {

                $('#formErrors')
                    .removeClass('d-none')
                    .text(
                        'يجب اختيار دور واحد على الأقل.'
                    );

                return;
            }


            const id =
                $('#user_id').val();

            const isEdit =
                id !== '';


            let url =
                routes.store;


            const formData =
                $(this).serializeArray();


            if (isEdit) {

                url =
                    userUrl(
                        routes.update,
                        id
                    );

                formData.push({
                    name: '_method',
                    value: 'PUT',
                });
            }


            const button =
                $('#btnSaveUser');

            button
                .prop('disabled', true)
                .text('جاري الحفظ...');


            $('#formErrors')
                .addClass('d-none')
                .empty();


            $.ajax({

                url: url,

                type: 'POST',

                data: $.param(formData),

                dataType: 'json',

                headers: {
                    'Accept':
                        'application/json'
                },

                success: function (response) {

                    userModal.hide();

                    successMessage(
                        response.message
                    );
                },

                error: function (xhr) {

                    showError(xhr);
                },

                complete: function () {

                    button
                        .prop('disabled', false)
                        .text('حفظ');
                }

            });
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Activate / Deactivate
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'click',
        '.btn-status-user',
        function () {

            const button =
                $(this);

            const id =
                button.data('id');

            const currentlyActive =
                Number(
                    button.data('active')
                ) === 1;

            const newStatus =
                currentlyActive ? 0 : 1;


            const execute =
                function () {

                    $.ajax({

                        url:
                            userUrl(
                                routes.status,
                                id
                            ),

                        type: 'PATCH',

                        data: {
                            _token:
                                csrfToken,

                            is_active:
                                newStatus,
                        },

                        dataType: 'json',

                        headers: {
                            'Accept':
                                'application/json'
                        },

                        success:
                            function (response) {

                                successMessage(
                                    response.message
                                );
                            },

                        error:
                            function (xhr) {

                                showError(xhr);
                            }

                    });
                };


            if (window.Swal) {

                Swal.fire({

                    icon: 'warning',

                    title:
                        currentlyActive
                            ? 'تعطيل المستخدم؟'
                            : 'تفعيل المستخدم؟',

                    text:
                        currentlyActive
                            ? 'لن يتمكن المستخدم من تسجيل الدخول.'
                            : 'سيتمكن المستخدم من تسجيل الدخول مجددًا.',

                    showCancelButton: true,

                    confirmButtonText:
                        currentlyActive
                            ? 'نعم، تعطيل'
                            : 'نعم، تفعيل',

                    cancelButtonText:
                        'إلغاء',

                    confirmButtonColor:
                        currentlyActive
                            ? '#dc3545'
                            : '#198754',

                }).then(function (result) {

                    if (result.isConfirmed) {
                        execute();
                    }

                });

                return;
            }


            if (
                confirm(
                    currentlyActive
                        ? 'هل تريد تعطيل المستخدم؟'
                        : 'هل تريد تفعيل المستخدم؟'
                )
            ) {
                execute();
            }
        }
    );

});

</script>

@endsection