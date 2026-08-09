@extends('layouts.tenant')

@section('title', 'الأدوار والصلاحيات')
@section('page-title', 'الأدوار والصلاحيات')

@section('content')

@php
    $permissionGroupLabels = [
        'dashboard' => 'لوحة التحكم',
        'users' => 'المستخدمون',
        'roles' => 'الأدوار والصلاحيات',
        'organization' => 'الهيكل التنظيمي',
        'employees' => 'الموظفون',
        'contracts' => 'العقود',
        'documents' => 'المستندات',
        'attendance' => 'الحضور والانصراف',
        'leave' => 'الإجازات',
        'payroll' => 'الرواتب',
        'recruitment' => 'التوظيف',
        'performance' => 'الأداء',
        'training' => 'التدريب',
        'reports' => 'التقارير',
        'audit' => 'سجل العمليات',
        'settings' => 'الإعدادات',
        'self_service' => 'الخدمة الذاتية',
    ];

    $permissionLabels = [
        'dashboard.view' => 'عرض لوحة التحكم',

        'users.view' => 'عرض المستخدمين',
        'users.create' => 'إضافة المستخدمين',
        'users.update' => 'تعديل المستخدمين',
        'users.deactivate' => 'تفعيل وتعطيل المستخدمين',

        'roles.view' => 'عرض الأدوار',
        'roles.manage' => 'إدارة الأدوار والصلاحيات',

        'organization.view' => 'عرض الهيكل التنظيمي',
        'organization.manage' => 'إدارة الهيكل التنظيمي',

        'employees.view' => 'عرض الموظفين',
        'employees.create' => 'إضافة الموظفين',
        'employees.update' => 'تعديل الموظفين',
        'employees.archive' => 'أرشفة الموظفين',
        'employees.import' => 'استيراد الموظفين',
        'employees.export' => 'تصدير الموظفين',

        'contracts.view' => 'عرض العقود',
        'contracts.create' => 'إضافة العقود',
        'contracts.update' => 'تعديل العقود',
        'contracts.end' => 'إنهاء العقود',

        'documents.view' => 'عرض المستندات',
        'documents.manage' => 'إدارة المستندات',

        'attendance.view' => 'عرض الحضور',
        'attendance.manage' => 'إدارة الحضور',
        'attendance.approve' => 'اعتماد الحضور',

        'leave.view' => 'عرض الإجازات',
        'leave.manage' => 'إدارة الإجازات',
        'leave.approve' => 'اعتماد الإجازات',

        'payroll.view' => 'عرض الرواتب',
        'payroll.manage' => 'إدارة الرواتب',
        'payroll.process' => 'معالجة مسير الرواتب',
        'payroll.approve' => 'اعتماد مسير الرواتب',

        'recruitment.view' => 'عرض التوظيف',
        'recruitment.manage' => 'إدارة التوظيف',

        'performance.view' => 'عرض تقييم الأداء',
        'performance.manage' => 'إدارة تقييم الأداء',

        'training.view' => 'عرض التدريب',
        'training.manage' => 'إدارة التدريب',

        'reports.view' => 'عرض التقارير',
        'reports.export' => 'تصدير التقارير',

        'audit.view' => 'عرض سجل العمليات',

        'settings.view' => 'عرض الإعدادات',
        'settings.update' => 'تعديل الإعدادات',

        'self_service.profile' => 'الملف الشخصي',
        'self_service.leave' => 'طلبات الإجازة الشخصية',
        'self_service.attendance' => 'الحضور الشخصي',
    ];

    $groupedPermissions = $permissions->groupBy(
        fn ($permission) =>
            \Illuminate\Support\Str::before(
                $permission->name,
                '.'
            )
    );
@endphp

<style>

    #roleModal {
        overflow: hidden !important;
        padding-left: 0 !important;
    }

    #roleModal .modal-dialog {
        height: calc(100vh - 30px);
        max-height: calc(100vh - 30px);
        margin-top: 15px;
        margin-bottom: 15px;
    }

    #roleModal .modal-content {
        display: flex;
        flex-direction: column;
        height: 100%;
        max-height: 100%;
        overflow: hidden;
    }

    #roleModal #roleForm {
        display: flex;
        flex-direction: column;
        width: 100%;
        height: 100%;
        min-height: 0;
        overflow: hidden;
    }

    #roleModal .modal-header,
    #roleModal .modal-footer {
        flex: 0 0 auto;
        background: #fff;
        z-index: 2;
    }

    #roleModal .modal-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto !important;
        overflow-x: hidden;
        overscroll-behavior: contain;
        scrollbar-gutter: stable;
    }

    @media (max-width: 576px) {

        #roleModal .modal-dialog {
            width: calc(100% - 16px);
            height: calc(100vh - 16px);
            max-height: calc(100vh - 16px);
            margin: 8px;
        }

    }

</style>
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h4 class="mb-1">
                الأدوار والصلاحيات
            </h4>

            <div class="text-muted small">
                تحديد ما يستطيع كل مستخدم الوصول إليه داخل الشركة
            </div>
        </div>

        @can('roles.manage')

            <button
                type="button"
                class="btn btn-primary"
                id="btnAddRole"
            >
                <i class="bi bi-plus-lg"></i>
                إضافة دور
            </button>

        @endcan

    </div>


    {{-- Search --}}
    <div class="card border-0 shadow-sm mb-4">

        <div class="card-body">

            <form
                method="GET"
                action="{{ route('app.roles.index') }}"
                id="rolesSearchForm"
            >

                <div class="row g-3 align-items-end">

                    <div class="col-md-8">

                        <label class="form-label">
                            البحث عن دور
                        </label>

                        <div class="input-group">

                            <span class="input-group-text">
                                <i class="bi bi-search"></i>
                            </span>

                            <input
                                type="search"
                                name="q"
                                id="roleSearch"
                                class="form-control"
                                value="{{ $search }}"
                                placeholder="اسم الدور..."
                            >

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >
                                بحث
                            </button>

                        </div>

                    </div>


                    <div class="col-md-4">

                        <a
                            href="{{ route('app.roles.index') }}"
                            class="btn btn-outline-secondary"
                        >
                            <i class="bi bi-arrow-counterclockwise"></i>
                            مسح البحث
                        </a>

                    </div>

                </div>

            </form>

        </div>

    </div>


    {{-- Roles --}}
    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white py-3">

            <div class="d-flex justify-content-between align-items-center">

                <strong>
                    قائمة الأدوار
                </strong>

                <span class="badge bg-primary-subtle text-primary">
                    {{ $roles->total() }} دور
                </span>

            </div>

        </div>


        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead class="table-light">

                    <tr>
                        <th>#</th>
                        <th>الدور</th>
                        <th>النوع</th>
                        <th>عدد المستخدمين</th>
                        <th>عدد الصلاحيات</th>
                        <th class="text-center">الإجراءات</th>
                    </tr>

                </thead>

                <tbody>

                @forelse($roles as $role)

                    @php
                        $isSystemRole = in_array(
                            $role->name,
                            $systemRoles,
                            true
                        );

                        $isOwnerRole =
                            $role->name === 'tenant_owner';
                    @endphp

                    <tr>

                        <td>
                            {{ $role->id }}
                        </td>


                        <td>

                            <div class="fw-semibold">

                                {{
                                    $roleLabels[$role->name]
                                    ?? $role->name
                                }}

                            </div>

                            <small class="text-muted" dir="ltr">
                                {{ $role->name }}
                            </small>

                        </td>


                        <td>

                            @if($isOwnerRole)

                                <span class="badge bg-warning-subtle text-warning">
                                    محمي
                                </span>

                            @elseif($isSystemRole)

                                <span class="badge bg-primary-subtle text-primary">
                                    أساسي
                                </span>

                            @else

                                <span class="badge bg-light text-dark border">
                                    مخصص
                                </span>

                            @endif

                        </td>


                        <td>

                            <span class="badge bg-light text-dark border">
                                {{ $role->users_count }}
                            </span>

                        </td>


                        <td>

                            <span class="badge bg-light text-dark border">
                                {{ $role->permissions_count }}
                            </span>

                        </td>


                        <td class="text-center">

                            @can('roles.manage')

                                @if($isOwnerRole)

                                    <span
                                        class="text-muted"
                                        title="دور مالك الحساب محمي"
                                    >
                                        <i class="bi bi-lock-fill"></i>
                                    </span>

                                @else

                                    <div class="btn-group btn-group-sm">

                                        <button
                                            type="button"
                                            class="btn btn-outline-primary btn-edit-role"
                                            data-id="{{ $role->id }}"
                                            title="تعديل"
                                        >
                                            <i class="bi bi-pencil"></i>
                                        </button>


                                        @if(!$isSystemRole)

                                            <button
                                                type="button"
                                                class="btn btn-outline-danger btn-delete-role"
                                                data-id="{{ $role->id }}"
                                                data-name="{{ $role->name }}"
                                                title="حذف"
                                            >
                                                <i class="bi bi-trash"></i>
                                            </button>

                                        @endif

                                    </div>

                                @endif

                            @endcan

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="6"
                            class="text-center text-muted py-5"
                        >
                            لا توجد أدوار.
                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>


        @if($roles->hasPages())

            <div class="card-footer bg-white">
                {{ $roles->links() }}
            </div>

        @endif

    </div>

</div>


{{-- Role Modal --}}
<div
    class="modal fade"
    id="roleModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-xl modal-dialog-scrollable">

        <div class="modal-content">

            <form id="roleForm">

                @csrf

                <input
                    type="hidden"
                    id="role_id"
                >


                <div class="modal-header">

                    <h5
                        class="modal-title"
                        id="roleModalTitle"
                    >
                        إضافة دور
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-role-modal-close
                    ></button>

                </div>


                <div class="modal-body">

                    <div
                        class="alert alert-danger d-none"
                        id="roleFormErrors"
                    ></div>


                    <div class="mb-4">

                        <label class="form-label">
                            اسم الدور
                        </label>

                        <input
                            type="text"
                            name="name"
                            id="role_name"
                            class="form-control"
                            maxlength="100"
                            required
                        >

                        <small
                            class="text-muted"
                            id="roleNameHelp"
                        >
                            يمكن كتابة اسم الدور بالعربية أو الإنجليزية.
                        </small>

                    </div>


                    <div class="d-flex justify-content-between align-items-center mb-3">

                        <div>

                            <h6 class="mb-1">
                                الصلاحيات
                            </h6>

                            <small class="text-muted">
                                تم اختيار
                                <strong id="selectedPermissionCount">0</strong>
                                من
                                {{ $permissions->count() }}
                            </small>

                        </div>

                        <button
                            type="button"
                            class="btn btn-sm btn-outline-primary"
                            id="btnToggleAllPermissions"
                        >
                            تحديد الكل
                        </button>

                    </div>


                    <div class="row g-3">

                        @foreach(
                            $permissionGroupLabels
                            as $groupKey => $groupLabel
                        )

                            @php
                                $groupPermissions =
                                    $groupedPermissions->get(
                                        $groupKey,
                                        collect()
                                    );
                            @endphp

                            @if($groupPermissions->isNotEmpty())

                                <div class="col-lg-6">

                                    <div class="card h-100 border">

                                        <div class="card-header bg-light">

                                            <div class="form-check">

                                                <input
                                                    type="checkbox"
                                                    class="form-check-input group-checkbox"
                                                    data-group="{{ $groupKey }}"
                                                    id="group_{{ $groupKey }}"
                                                >

                                                <label
                                                    class="form-check-label fw-semibold"
                                                    for="group_{{ $groupKey }}"
                                                >
                                                    {{ $groupLabel }}
                                                </label>

                                            </div>

                                        </div>


                                        <div class="card-body">

                                            <div class="row g-2">

                                                @foreach(
                                                    $groupPermissions
                                                    as $permission
                                                )

                                                    <div class="col-md-6">

                                                        <label
                                                            class="border rounded p-2 w-100"
                                                            for="permission_{{ $permission->id }}"
                                                            style="cursor:pointer;"
                                                        >

                                                            <div class="form-check">

                                                                <input
                                                                    type="checkbox"
                                                                    name="permissions[]"
                                                                    value="{{ $permission->id }}"
                                                                    id="permission_{{ $permission->id }}"
                                                                    class="form-check-input permission-checkbox"
                                                                    data-group="{{ $groupKey }}"
                                                                >

                                                                <span class="form-check-label small">

                                                                    {{
                                                                        $permissionLabels[$permission->name]
                                                                        ?? $permission->name
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

                            @endif

                        @endforeach

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-light"
                        data-role-modal-close
                    >
                        إلغاء
                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary"
                        id="btnSaveRole"
                    >
                        حفظ
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

    const csrfToken = @json(csrf_token());

    const routes = {
        store: @json(route('app.roles.store')),

        show: @json(
            route(
                'app.roles.show',
                ['role' => '__ROLE__']
            )
        ),

        update: @json(
            route(
                'app.roles.update',
                ['role' => '__ROLE__']
            )
        ),

        destroy: @json(
            route(
                'app.roles.destroy',
                ['role' => '__ROLE__']
            )
        ),
    };


    const $roleModal = $('#roleModal');

    $roleModal.appendTo('body');


    function showRoleModal() {

        $('#roleModalBackdrop').remove();

        $('<div>', {
            id: 'roleModalBackdrop',
            class: 'modal-backdrop fade show',
        }).appendTo('body');

        $('body')
            .addClass('modal-open')
            .css('overflow', 'hidden');

        $roleModal
            .attr({
                role: 'dialog',
                'aria-modal': 'true',
            })
            .removeAttr('aria-hidden')
            .css('display', 'block')
            .addClass('show');
    }


    function hideRoleModal() {

        $roleModal
            .removeClass('show')
            .css('display', 'none')
            .attr('aria-hidden', 'true')
            .removeAttr('aria-modal role');

        $('#roleModalBackdrop').remove();

        $('body')
            .removeClass('modal-open')
            .css('overflow', '');
    }


    $(document).on(
        'click',
        '[data-role-modal-close]',
        function () {
            hideRoleModal();
        }
    );


    $(document).on(
        'keydown',
        function (event) {

            if (
                event.key === 'Escape' &&
                $roleModal.hasClass('show')
            ) {
                hideRoleModal();
            }
        }
    );


    function roleUrl(url, id) {
        return url.replace('__ROLE__', id);
    }


    function resetForm() {

        $('#roleForm').trigger('reset');

        $('#role_id').val('');

        $('#role_name')
            .prop('disabled', false);

        $('#roleNameHelp').text(
            'يمكن كتابة اسم الدور بالعربية أو الإنجليزية.'
        );

        $('#roleFormErrors')
            .addClass('d-none')
            .empty();

        $('.permission-checkbox')
            .prop('checked', false);

        $('.group-checkbox')
            .prop('checked', false)
            .prop('indeterminate', false);

        updateCounters();
    }


    function updateCounters() {

        const selected =
            $('.permission-checkbox:checked').length;

        const total =
            $('.permission-checkbox').length;

        $('#selectedPermissionCount')
            .text(selected);


        $('.group-checkbox').each(function () {

            const group =
                $(this).data('group');

            const items =
                $('.permission-checkbox[data-group="' + group + '"]');

            const checked =
                items.filter(':checked').length;

            $(this)
                .prop(
                    'checked',
                    items.length > 0 &&
                    checked === items.length
                )
                .prop(
                    'indeterminate',
                    checked > 0 &&
                    checked < items.length
                );
        });


        $('#btnToggleAllPermissions')
            .text(
                total > 0 && selected === total
                    ? 'إلغاء تحديد الكل'
                    : 'تحديد الكل'
            );
    }


    function errorMessage(xhr) {

        let message = 'حدث خطأ غير متوقع.';

        if (xhr.responseJSON?.errors) {

            const messages = [];

            $.each(
                xhr.responseJSON.errors,
                function (key, errors) {
                    messages.push(...errors);
                }
            );

            message = messages.join('\n');

        } else if (xhr.responseJSON?.message) {

            message = xhr.responseJSON.message;
        }


        $('#roleFormErrors')
            .removeClass('d-none')
            .text(message);


        if (window.Swal) {

            Swal.fire({
                icon: 'error',
                title: 'تعذر تنفيذ العملية',
                text: message,
            });

        } else {

            alert(message);
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

        } else {

            alert(message);

            window.location.reload();
        }
    }


    {{-- Search --}}

    let searchTimer = null;

    $('#roleSearch').on('input', function () {

        clearTimeout(searchTimer);

        searchTimer = setTimeout(
            function () {
                $('#rolesSearchForm')
                    .trigger('submit');
            },
            500
        );
    });


    {{-- Permission selection --}}

    $(document).on(
        'change',
        '.permission-checkbox',
        updateCounters
    );


    $(document).on(
        'change',
        '.group-checkbox',
        function () {

            const group =
                $(this).data('group');

            const checked =
                $(this).is(':checked');

            $('.permission-checkbox[data-group="' + group + '"]')
                .prop('checked', checked);

            updateCounters();
        }
    );


    $('#btnToggleAllPermissions').on(
        'click',
        function () {

            const allSelected =
                $('.permission-checkbox').length > 0 &&
                $('.permission-checkbox:checked').length
                ===
                $('.permission-checkbox').length;

            $('.permission-checkbox')
                .prop('checked', !allSelected);

            updateCounters();
        }
    );


    {{-- Add --}}

    $('#btnAddRole').on('click', function () {

        resetForm();

        $('#roleModalTitle')
            .text('إضافة دور');

        showRoleModal();
    });


    {{-- Edit --}}

    $(document).on(
        'click',
        '.btn-edit-role',
        function () {

            const id = $(this).data('id');

            resetForm();


            $.ajax({
                url: roleUrl(routes.show, id),
                type: 'GET',
                dataType: 'json',

                success: function (response) {

                    const role = response.role;

                    $('#role_id').val(role.id);

                    $('#role_name')
                        .val(role.name)
                        .prop(
                            'disabled',
                            role.is_system
                        );

                    if (role.is_system) {

                        $('#roleNameHelp').text(
                            'اسم الدور الأساسي ثابت، ويمكن تعديل صلاحياته فقط.'
                        );
                    }


                    const selectedPermissions =
                        (role.permissions || [])
                            .map(String);


                    $('.permission-checkbox')
                        .each(function () {

                            $(this).prop(
                                'checked',
                                selectedPermissions.includes(
                                    String($(this).val())
                                )
                            );
                        });


                    updateCounters();

                    $('#roleModalTitle')
                        .text(
                            'تعديل الدور: ' +
                            role.label
                        );

                    showRoleModal();
                },

                error: errorMessage,
            });
        }
    );


    {{-- Save --}}

    $('#roleForm').on(
        'submit',
        function (event) {

            event.preventDefault();


            if (
                $('.permission-checkbox:checked')
                    .length === 0
            ) {
                $('#roleFormErrors')
                    .removeClass('d-none')
                    .text(
                        'يجب اختيار صلاحية واحدة على الأقل.'
                    );

                return;
            }


            const id = $('#role_id').val();

            const isEdit = id !== '';

            const url = isEdit
                ? roleUrl(routes.update, id)
                : routes.store;

            const data =
                $(this).serializeArray();


            if (isEdit) {
                data.push({
                    name: '_method',
                    value: 'PUT',
                });
            }


            const button = $('#btnSaveRole');

            button
                .prop('disabled', true)
                .text('جاري الحفظ...');


            $.ajax({
                url: url,
                type: 'POST',
                data: $.param(data),
                dataType: 'json',

                headers: {
                    Accept: 'application/json',
                },

                success: function (response) {

                    hideRoleModal();

                    successMessage(
                        response.message
                    );
                },

                error: errorMessage,

                complete: function () {

                    button
                        .prop('disabled', false)
                        .text('حفظ');
                },
            });
        }
    );


    {{-- Delete --}}

    $(document).on(
        'click',
        '.btn-delete-role',
        function () {

            const id = $(this).data('id');

            const name = $(this).data('name');


            const executeDelete = function () {

                $.ajax({
                    url: roleUrl(
                        routes.destroy,
                        id
                    ),

                    type: 'DELETE',

                    data: {
                        _token: csrfToken,
                    },

                    dataType: 'json',

                    headers: {
                        Accept: 'application/json',
                    },

                    success: function (response) {

                        successMessage(
                            response.message
                        );
                    },

                    error: errorMessage,
                });
            };


            if (window.Swal) {

                Swal.fire({
                    icon: 'warning',
                    title: 'حذف الدور؟',
                    text:
                        'سيتم حذف الدور "' +
                        name +
                        '" نهائيًا.',

                    showCancelButton: true,
                    confirmButtonText: 'نعم، حذف',
                    cancelButtonText: 'إلغاء',
                    confirmButtonColor: '#dc3545',

                }).then(function (result) {

                    if (result.isConfirmed) {
                        executeDelete();
                    }
                });

            } else if (
                confirm(
                    'هل تريد حذف الدور ' +
                    name +
                    '؟'
                )
            ) {
                executeDelete();
            }
        }
    );

});

</script>

@endpush