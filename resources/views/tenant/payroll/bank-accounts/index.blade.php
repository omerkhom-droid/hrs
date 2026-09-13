@extends('layouts.tenant')

@section('title', 'الحسابات البنكية للموظفين')
@section('page-title', 'الحسابات البنكية للموظفين')

@section('content')

@php
    $routes = [
        'data' => route('app.payroll.bank-accounts.data'),
        'options' => route('app.payroll.bank-accounts.options'),
        'store' => route('app.payroll.bank-accounts.store'),
        'show' => route('app.payroll.bank-accounts.show', ['bankAccount' => '__ID__']),
        'update' => route('app.payroll.bank-accounts.update', ['bankAccount' => '__ID__']),
        'verify' => route('app.payroll.bank-accounts.verify', ['bankAccount' => '__ID__']),
        'unverify' => route('app.payroll.bank-accounts.unverify', ['bankAccount' => '__ID__']),
        'destroy' => route('app.payroll.bank-accounts.destroy', ['bankAccount' => '__ID__']),
    ];
@endphp

<style>
    .bank-accounts-page .content-card {
        border: 0;
        border-radius: 18px;
        box-shadow: 0 8px 30px rgba(15, 23, 42, .06);
    }

    .bank-accounts-page .form-control,
    .bank-accounts-page .form-select {
        min-height: 43px;
        border-radius: 10px;
        border-color: #dce2ea;
    }

    .bank-accounts-page .table > :not(caption) > * > * {
        padding: 14px 12px;
        vertical-align: middle;
    }

    .bank-accounts-page .employee-cell {
        min-width: 220px;
    }

    .bank-accounts-page .bank-cell {
        min-width: 170px;
    }

    .bank-accounts-page .account-cell {
        min-width: 190px;
    }

    .bank-accounts-page .custom-modal {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 1080;
        background: rgba(15, 23, 42, .58);
        padding: 24px;
        overflow-y: auto;
    }

    .bank-accounts-page .custom-modal-dialog {
        width: min(900px, 100%);
        margin: 0 auto;
        min-height: calc(100vh - 48px);
        display: flex;
        align-items: center;
    }

    .bank-accounts-page .custom-modal-content {
        background: #fff;
        width: 100%;
        max-height: calc(100vh - 48px);
        display: flex;
        flex-direction: column;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 25px 70px rgba(15, 23, 42, .25);
    }

    .bank-accounts-page .custom-modal-header,
    .bank-accounts-page .custom-modal-footer {
        padding: 18px 22px;
        flex: 0 0 auto;
    }

    .bank-accounts-page .custom-modal-header {
        border-bottom: 1px solid #e8edf3;
    }

    .bank-accounts-page .custom-modal-footer {
        border-top: 1px solid #e8edf3;
    }

    .bank-accounts-page .custom-modal-body {
        padding: 22px;
        overflow-y: auto;
        overscroll-behavior: contain;
    }

    .bank-accounts-page .close-modal {
        width: 40px;
        height: 40px;
        border: 0;
        border-radius: 11px;
        background: #f1f5f9;
        color: #334155;
    }

    .bank-accounts-page .sensitive-field {
        position: relative;
    }

    .bank-accounts-page .sensitive-field input {
        padding-left: 48px;
    }

    .bank-accounts-page .toggle-sensitive {
        position: absolute;
        left: 5px;
        top: 5px;
        width: 35px;
        height: 33px;
        border: 0;
        border-radius: 8px;
        background: #eef3fb;
        color: #46617f;
    }

    .bank-accounts-page .field-error {
        color: #dc3545;
        display: block;
        font-size: .8rem;
        margin-top: 5px;
    }

    .bank-accounts-page .pagination-button {
        min-width: 38px;
    }

    body.custom-modal-open {
        overflow: hidden;
    }

    @media (max-width: 767.98px) {
        .bank-accounts-page .custom-modal {
            padding: 8px;
        }

        .bank-accounts-page .custom-modal-dialog {
            min-height: calc(100vh - 16px);
        }

        .bank-accounts-page .custom-modal-content {
            max-height: calc(100vh - 16px);
        }
    }
</style>

<div class="bank-accounts-page">

    <div id="pageAlert" class="alert d-none mb-4"></div>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

        <div>
            <h4 class="mb-1">الحسابات البنكية للموظفين</h4>

            <p class="text-muted mb-0">
                إدارة حسابات تحويل الرواتب والتحقق من جاهزية بيانات الموظفين.
            </p>
        </div>

        <button
            type="button"
            id="openCreateButton"
            class="btn btn-primary px-4"
        >
            <i class="bi bi-plus-lg ms-1"></i>
            إضافة حساب بنكي
        </button>

    </div>


    <div class="card content-card mb-4">

        <div class="card-body p-4">

            <form id="searchForm">

                <div class="row g-3 align-items-end">

                    <div class="col-lg-4">
                        <label class="form-label">البحث</label>

                        <input
                            type="search"
                            id="searchInput"
                            class="form-control"
                            placeholder="اسم الموظف أو الرقم الوظيفي أو البنك"
                        >
                    </div>

                    <div class="col-lg-3">
                        <label class="form-label">حالة التوثيق</label>

                        <select id="verifiedFilter" class="form-select">
                            <option value="">جميع الحالات</option>
                            <option value="1">موثّق</option>
                            <option value="0">غير موثّق</option>
                        </select>
                    </div>

                    <div class="col-lg-3">
                        <label class="form-label">الحالة</label>

                        <select id="activeFilter" class="form-select">
                            <option value="">الكل</option>
                            <option value="1">نشط</option>
                            <option value="0">غير نشط</option>
                        </select>
                    </div>

                    <div class="col-lg-2">
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary flex-fill" type="submit">
                                بحث
                            </button>

                            <button
                                class="btn btn-light border"
                                type="button"
                                id="resetSearchButton"
                            >
                                إعادة
                            </button>
                        </div>
                    </div>

                </div>

            </form>

        </div>

    </div>


    <div class="card content-card">

        <div class="card-header bg-white border-0 p-4 pb-2">

            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">قائمة الحسابات</h5>

                <span
                    id="recordsCount"
                    class="badge bg-primary-subtle text-primary"
                >
                    0 حساب
                </span>
            </div>

        </div>

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover mb-0">

                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>الموظف</th>
                            <th>البنك</th>
                            <th>الحساب</th>
                            <th>طريقة الدفع</th>
                            <th>الحالة</th>
                            <th>التوثيق</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>

                    <tbody id="accountsTableBody">

                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                جارٍ تحميل البيانات...
                            </td>
                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

        <div class="card-footer bg-white border-0 p-4">

            <div
                id="pagination"
                class="d-flex justify-content-between align-items-center flex-wrap gap-3"
            ></div>

        </div>

    </div>


    {{-- نموذج الإضافة والتعديل --}}
    <div id="accountModal" class="custom-modal">

        <div class="custom-modal-dialog">

            <div class="custom-modal-content">

                <div class="custom-modal-header">

                    <div class="d-flex justify-content-between align-items-center gap-3">

                        <div>
                            <h5 id="modalTitle" class="mb-1">
                                إضافة حساب بنكي
                            </h5>

                            <div class="text-muted small">
                                أدخل بيانات حساب تحويل راتب الموظف.
                            </div>
                        </div>

                        <button
                            type="button"
                            class="close-modal"
                            aria-label="إغلاق"
                        >
                            <i class="bi bi-x-lg"></i>
                        </button>

                    </div>

                </div>

                <form id="accountForm" autocomplete="off">

                    @csrf

                    <input type="hidden" id="bankAccountId">

                    <div class="custom-modal-body">

                        <div id="modalAlert" class="alert alert-danger d-none"></div>

                        <div class="row g-3">

                            <div class="col-md-8">
                                <label class="form-label">
                                    الموظف <span class="text-danger">*</span>
                                </label>

                                <select
                                    name="employee_id"
                                    id="employeeId"
                                    class="form-select"
                                    required
                                >
                                    <option value="">اختر الموظف</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">
                                    طريقة الدفع <span class="text-danger">*</span>
                                </label>

                                <select
                                    name="payment_method"
                                    id="paymentMethod"
                                    class="form-select"
                                    required
                                >
                                    <option value="bank_transfer">تحويل بنكي</option>
                                    <option value="cash">نقدي</option>
                                    <option value="cheque">شيك</option>
                                    <option value="wallet">محفظة إلكترونية</option>
                                </select>
                            </div>

                            <div class="col-md-6 bank-fields">
                                <label class="form-label">
                                    اسم البنك <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="text"
                                    name="bank_name"
                                    id="bankName"
                                    class="form-control"
                                    maxlength="150"
                                >
                            </div>

                            <div class="col-md-3 bank-fields">
                                <label class="form-label">رمز البنك</label>

                                <input
                                    type="text"
                                    name="bank_code"
                                    id="bankCode"
                                    class="form-control text-uppercase"
                                    maxlength="50"
                                    dir="ltr"
                                >
                            </div>

                            <div class="col-md-3 bank-fields">
                                <label class="form-label">رمز الفرع</label>

                                <input
                                    type="text"
                                    name="bank_branch_code"
                                    id="bankBranchCode"
                                    class="form-control text-uppercase"
                                    maxlength="50"
                                    dir="ltr"
                                >
                            </div>

                            <div class="col-md-8 bank-fields">
                                <label class="form-label">
                                    اسم صاحب الحساب <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="text"
                                    name="account_holder_name"
                                    id="accountHolderName"
                                    class="form-control"
                                    maxlength="255"
                                >
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">
                                    العملة <span class="text-danger">*</span>
                                </label>

                                <select
                                    name="currency_code"
                                    id="currencyCode"
                                    class="form-select"
                                >
                                    <option value="SAR">SAR</option>
                                </select>
                            </div>

                            <div class="col-md-6 bank-fields">
                                <label class="form-label">
                                    رقم الحساب
                                </label>

                                <div class="sensitive-field">
                                    <input
                                        type="password"
                                        name="account_number"
                                        id="accountNumber"
                                        class="form-control"
                                        maxlength="100"
                                        dir="ltr"
                                        autocomplete="new-password"
                                    >

                                    <button
                                        type="button"
                                        class="toggle-sensitive"
                                        data-target="#accountNumber"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>

                                <div id="accountNumberHint" class="form-text"></div>
                            </div>

                            <div class="col-md-6 bank-fields">
                                <label class="form-label">
                                    الآيبان <span class="text-danger">*</span>
                                </label>

                                <div class="sensitive-field">
                                    <input
                                        type="password"
                                        name="iban"
                                        id="iban"
                                        class="form-control text-uppercase"
                                        maxlength="34"
                                        dir="ltr"
                                        autocomplete="new-password"
                                        placeholder="SA0000000000000000000000"
                                    >

                                    <button
                                        type="button"
                                        class="toggle-sensitive"
                                        data-target="#iban"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>

                                <div id="ibanHint" class="form-text"></div>
                            </div>

                            <div class="col-md-6 bank-fields">
                                <label class="form-label">
                                    رمز SWIFT
                                </label>

                                <input
                                    type="text"
                                    name="swift_code"
                                    id="swiftCode"
                                    class="form-control text-uppercase"
                                    maxlength="20"
                                    dir="ltr"
                                >
                            </div>

                            <div class="col-md-6">
                                <div class="border rounded-3 p-3 h-100">

                                    <div class="form-check form-switch mb-2">
                                        <input
                                            type="checkbox"
                                            name="is_primary"
                                            id="isPrimary"
                                            class="form-check-input"
                                            value="1"
                                        >

                                        <label
                                            class="form-check-label"
                                            for="isPrimary"
                                        >
                                            الحساب الرئيسي للموظف
                                        </label>
                                    </div>

                                    <div class="form-check form-switch">
                                        <input
                                            type="checkbox"
                                            name="is_active"
                                            id="isActive"
                                            class="form-check-input"
                                            value="1"
                                            checked
                                        >

                                        <label
                                            class="form-check-label"
                                            for="isActive"
                                        >
                                            الحساب نشط
                                        </label>
                                    </div>

                                </div>
                            </div>

                        </div>

                    </div>

                    <div class="custom-modal-footer">

                        <div class="d-flex justify-content-end gap-2">

                            <button
                                type="button"
                                class="btn btn-light border close-modal"
                                style="width: auto; height: auto;"
                            >
                                إلغاء
                            </button>

                            <button
                                type="submit"
                                id="saveAccountButton"
                                class="btn btn-primary px-4"
                            >
                                <span class="save-text">حفظ البيانات</span>

                                <span class="save-loading d-none">
                                    <span class="spinner-border spinner-border-sm ms-1"></span>
                                    جارٍ الحفظ...
                                </span>
                            </button>

                        </div>

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>
@endsection


@push('scripts')
<script>
$(function () {
    'use strict';

    var routes = @json($routes);
    var currentPage = 1;
    var employeesLoaded = false;

    function routeUrl(template, id) {
        return template.replace('__ID__', id);
    }

    function escapeHtml(value) {
        return $('<div>').text(value ?? '').html();
    }

    function showPageAlert(type, message) {
        $('#pageAlert')
            .removeClass('d-none alert-success alert-danger alert-warning')
            .addClass('alert-' + type)
            .text(message);

        $('html, body').animate({
            scrollTop: 0
        }, 250);
    }

    function hidePageAlert() {
        $('#pageAlert')
            .addClass('d-none')
            .removeClass('alert-success alert-danger alert-warning')
            .text('');
    }

    function openModal() {
        $('#accountModal').show();
        $('body').addClass('custom-modal-open');
        $('#accountModal .custom-modal-body').scrollTop(0);
    }

    function closeModal() {
        $('#accountModal').hide();
        $('body').removeClass('custom-modal-open');
        clearErrors();
    }

    function clearErrors() {
        $('.field-error').remove();
        $('.is-invalid').removeClass('is-invalid');
        $('#modalAlert').addClass('d-none').text('');
    }

    function showErrors(errors) {
        $.each(errors || {}, function (field, messages) {
            var input = $('[name="' + field + '"]').first();

            if (!input.length) {
                return;
            }

            input.addClass('is-invalid');

            $('<span>', {
                class: 'field-error',
                text: Array.isArray(messages) ? messages[0] : messages
            }).insertAfter(
                input.closest('.sensitive-field').length
                    ? input.closest('.sensitive-field')
                    : input
            );
        });
    }

    function setSaving(saving) {
        $('#saveAccountButton').prop('disabled', saving);
        $('#saveAccountButton .save-text').toggleClass('d-none', saving);
        $('#saveAccountButton .save-loading').toggleClass('d-none', !saving);
    }

    function checkedValue(value) {
        return value === true || value === 1 || value === '1';
    }

    function resetForm() {
        $('#accountForm')[0].reset();
        $('#bankAccountId').val('');
        $('#employeeId').prop('disabled', false);
        $('#paymentMethod').val('bank_transfer');
        $('#currencyCode').val('SAR');
        $('#isActive').prop('checked', true);
        $('#isPrimary').prop('checked', false);
        $('#accountNumber').val('').attr('type', 'password');
        $('#iban').val('').attr('type', 'password');
        $('#accountNumberHint').text('');
        $('#ibanHint').text('');
        $('#modalTitle').text('إضافة حساب بنكي');
        clearErrors();
        toggleBankFields();
    }

    function toggleBankFields() {
        var bankTransfer = $('#paymentMethod').val() === 'bank_transfer';

        $('.bank-fields').toggle(bankTransfer);

        $('#bankName, #accountHolderName, #iban')
            .prop('required', bankTransfer);
    }

    function loadOptions(callback) {
        if (employeesLoaded) {
            if (typeof callback === 'function') {
                callback();
            }

            return;
        }

        $.ajax({
            url: routes.options,
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                var employees =
                    response.employees
                    || response.data?.employees
                    || [];

                var currencies =
                    response.currencies
                    || response.data?.currencies
                    || ['SAR'];

                $('#employeeId').html(
                    '<option value="">اختر الموظف</option>'
                );

                $.each(employees, function (index, employee) {
                    var name =
                        employee.display_name
                        || employee.full_name
                        || employee.name
                        || '';

                    var number =
                        employee.employee_number
                        ? ' - ' + employee.employee_number
                        : '';

                    $('<option>', {
                        value: employee.id,
                        text: name + number
                    }).appendTo('#employeeId');
                });

                $('#currencyCode').empty();

                $.each(currencies, function (index, currency) {
                    var code = typeof currency === 'string'
                        ? currency
                        : currency.code;

                    $('<option>', {
                        value: code,
                        text: code
                    }).appendTo('#currencyCode');
                });

                employeesLoaded = true;

                if (typeof callback === 'function') {
                    callback();
                }
            },
            error: function (xhr) {
                showPageAlert(
                    'danger',
                    xhr.responseJSON?.message
                        || 'تعذر تحميل قائمة الموظفين.'
                );
            }
        });
    }

    function paymentMethodLabel(method) {
        var labels = {
            bank_transfer: 'تحويل بنكي',
            cash: 'نقدي',
            cheque: 'شيك',
            wallet: 'محفظة'
        };

        return labels[method] || method || '—';
    }

    function renderRows(records, firstItem) {
        var body = $('#accountsTableBody');
        body.empty();

        if (!records.length) {
            body.html(
                '<tr>' +
                    '<td colspan="8" class="text-center py-5 text-muted">' +
                        'لا توجد حسابات بنكية.' +
                    '</td>' +
                '</tr>'
            );

            return;
        }

        $.each(records, function (index, account) {
            var employee = account.employee || {};

            var employeeName =
                employee.display_name
                || employee.full_name
                || employee.name
                || account.employee_name
                || '—';

            var employeeNumber =
                employee.employee_number
                || account.employee_number
                || '';

            var verifiedBadge = checkedValue(account.is_verified)
                ? '<span class="badge bg-success-subtle text-success">موثّق</span>'
                : '<span class="badge bg-warning-subtle text-warning">غير موثّق</span>';

            var activeBadge = checkedValue(account.is_active)
                ? '<span class="badge bg-success-subtle text-success">نشط</span>'
                : '<span class="badge bg-secondary-subtle text-secondary">غير نشط</span>';

            var primaryBadge = checkedValue(account.is_primary)
                ? '<span class="badge bg-primary-subtle text-primary me-1">رئيسي</span>'
                : '';

            var verifyButton = checkedValue(account.is_verified)
                ? '<button type="button" class="btn btn-sm btn-outline-warning btn-unverify" ' +
                    'data-id="' + account.id + '">إلغاء التوثيق</button>'
                : '<button type="button" class="btn btn-sm btn-outline-success btn-verify" ' +
                    'data-id="' + account.id + '">توثيق</button>';

            var row =
                '<tr>' +
                    '<td>' + (firstItem + index) + '</td>' +

                    '<td class="employee-cell">' +
                        '<div class="fw-semibold">' + escapeHtml(employeeName) + '</div>' +
                        '<div class="text-muted small">' + escapeHtml(employeeNumber) + '</div>' +
                    '</td>' +

                    '<td class="bank-cell">' +
                        '<div class="fw-semibold">' + escapeHtml(account.bank_name || '—') + '</div>' +
                        '<div class="text-muted small" dir="ltr">' +
                            escapeHtml(account.bank_code || '') +
                        '</div>' +
                    '</td>' +

                    '<td class="account-cell">' +
                        '<div dir="ltr">' +
                            escapeHtml(account.masked_iban || account.iban_masked || '—') +
                        '</div>' +
                        '<div class="mt-1">' + primaryBadge + '</div>' +
                    '</td>' +

                    '<td>' + escapeHtml(
                        account.payment_method_label
                        || paymentMethodLabel(account.payment_method)
                    ) + '</td>' +

                    '<td>' + activeBadge + '</td>' +
                    '<td>' + verifiedBadge + '</td>' +

                    '<td>' +
                        '<div class="d-flex flex-wrap gap-1">' +
                            '<button type="button" class="btn btn-sm btn-outline-primary btn-edit" ' +
                                'data-id="' + account.id + '">تعديل</button>' +
                            verifyButton +
                            '<button type="button" class="btn btn-sm btn-outline-danger btn-delete" ' +
                                'data-id="' + account.id + '">حذف</button>' +
                        '</div>' +
                    '</td>' +
                '</tr>';

            body.append(row);
        });
    }

    function renderPagination(meta) {
        var container = $('#pagination');
        container.empty();

        if (!meta || !meta.last_page) {
            return;
        }

        var info =
            '<div class="text-muted small">' +
                'عرض ' + (meta.from || 0) +
                ' إلى ' + (meta.to || 0) +
                ' من ' + (meta.total || 0) +
            '</div>';

        container.append(info);

        if (meta.last_page <= 1) {
            return;
        }

        var buttons = $('<div>', {
            class: 'd-flex gap-1'
        });

        $('<button>', {
            type: 'button',
            class: 'btn btn-sm btn-light border pagination-button',
            text: '‹',
            disabled: meta.current_page <= 1
        })
            .data('page', meta.current_page - 1)
            .appendTo(buttons);

        for (
            var page = Math.max(1, meta.current_page - 2);
            page <= Math.min(meta.last_page, meta.current_page + 2);
            page++
        ) {
            $('<button>', {
                type: 'button',
                class:
                    'btn btn-sm pagination-button ' +
                    (page === meta.current_page
                        ? 'btn-primary'
                        : 'btn-light border'),
                text: page
            })
                .data('page', page)
                .appendTo(buttons);
        }

        $('<button>', {
            type: 'button',
            class: 'btn btn-sm btn-light border pagination-button',
            text: '›',
            disabled: meta.current_page >= meta.last_page
        })
            .data('page', meta.current_page + 1)
            .appendTo(buttons);

        container.append(buttons);
    }

    function loadAccounts(page) {
        currentPage = page || 1;

        $('#accountsTableBody').html(
            '<tr>' +
                '<td colspan="8" class="text-center py-5 text-muted">' +
                    '<span class="spinner-border spinner-border-sm ms-2"></span>' +
                    'جارٍ تحميل البيانات...' +
                '</td>' +
            '</tr>'
        );

        $.ajax({
            url: routes.data,
            type: 'GET',
            dataType: 'json',
            data: {
                page: currentPage,
                search: $('#searchInput').val(),
                is_verified: $('#verifiedFilter').val(),
                is_active: $('#activeFilter').val()
            },
            success: function (response) {
                var paginator = response.data?.data
                    ? response.data
                    : response;

                var records = paginator.data || [];
                var firstItem = paginator.from || 1;

                renderRows(records, firstItem);
                renderPagination(paginator);

                $('#recordsCount').text(
                    (paginator.total || records.length) + ' حساب'
                );
            },
            error: function (xhr) {
                $('#accountsTableBody').html(
                    '<tr>' +
                        '<td colspan="8" class="text-center py-5 text-danger">' +
                            escapeHtml(
                                xhr.responseJSON?.message
                                || 'تعذر تحميل البيانات.'
                            ) +
                        '</td>' +
                    '</tr>'
                );
            }
        });
    }

    function openEdit(id) {
        loadOptions(function () {
            $.ajax({
                url: routeUrl(routes.show, id),
                type: 'GET',
                dataType: 'json',
                success: function (response) {
                    var account =
                        response.bank_account
                        || response.account
                        || response.data
                        || response;

                    resetForm();

                    $('#modalTitle').text('تعديل الحساب البنكي');
                    $('#bankAccountId').val(account.id);
                    $('#employeeId')
                        .val(account.employee_id)
                        .prop('disabled', true);

                    $('#paymentMethod').val(
                        account.payment_method || 'bank_transfer'
                    );

                    $('#bankName').val(account.bank_name || '');
                    $('#bankCode').val(account.bank_code || '');
                    $('#bankBranchCode').val(account.bank_branch_code || '');
                    $('#accountHolderName').val(account.account_holder_name || '');
                    $('#currencyCode').val(account.currency_code || 'SAR');
                    $('#swiftCode').val(account.swift_code || account.swift || '');
                    $('#isPrimary').prop('checked', checkedValue(account.is_primary));
                    $('#isActive').prop('checked', checkedValue(account.is_active));

                    $('#accountNumber').val('');
                    $('#iban').val('');

                    $('#accountNumberHint').text(
                        account.masked_account_number
                            ? 'المحفوظ: ' + account.masked_account_number +
                                ' — اترك الحقل فارغًا للاحتفاظ به.'
                            : ''
                    );

                    $('#ibanHint').text(
                        account.masked_iban
                            ? 'المحفوظ: ' + account.masked_iban +
                                ' — اترك الحقل فارغًا للاحتفاظ به.'
                            : ''
                    );

                    toggleBankFields();
                    openModal();
                },
                error: function (xhr) {
                    showPageAlert(
                        'danger',
                        xhr.responseJSON?.message
                            || 'تعذر تحميل بيانات الحساب.'
                    );
                }
            });
        });
    }

    function changeVerification(id, verify) {
        var message = verify
            ? 'هل تريد توثيق هذا الحساب البنكي؟'
            : 'هل تريد إلغاء توثيق هذا الحساب؟';

        if (!confirm(message)) {
            return;
        }

        $.ajax({
            url: routeUrl(
                verify ? routes.verify : routes.unverify,
                id
            ),
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json',
            success: function (response) {
                showPageAlert(
                    'success',
                    response.message || 'تم تحديث حالة التوثيق.'
                );

                loadAccounts(currentPage);
            },
            error: function (xhr) {
                showPageAlert(
                    'danger',
                    xhr.responseJSON?.message
                        || 'تعذر تحديث حالة التوثيق.'
                );
            }
        });
    }

    $('#openCreateButton').on('click', function () {
        loadOptions(function () {
            resetForm();
            openModal();
        });
    });

    $('.close-modal').on('click', function () {
        closeModal();
    });

    $('#accountModal').on('click', function (event) {
        if (event.target === this) {
            closeModal();
        }
    });

    $(document).on('keydown', function (event) {
        if (event.key === 'Escape' && $('#accountModal').is(':visible')) {
            closeModal();
        }
    });

    $('#paymentMethod').on('change', function () {
        toggleBankFields();
    });

    $('#iban, #bankCode, #bankBranchCode, #swiftCode').on('input', function () {
        this.value = this.value
            .toUpperCase()
            .replace(/\s+/g, '');
    });

    $('.toggle-sensitive').on('click', function () {
        var target = $($(this).data('target'));
        var icon = $(this).find('i');

        if (target.attr('type') === 'password') {
            target.attr('type', 'text');
            icon.removeClass('bi-eye').addClass('bi-eye-slash');
        } else {
            target.attr('type', 'password');
            icon.removeClass('bi-eye-slash').addClass('bi-eye');
        }
    });

    $('#searchForm').on('submit', function (event) {
        event.preventDefault();
        loadAccounts(1);
    });

    $('#resetSearchButton').on('click', function () {
        $('#searchInput').val('');
        $('#verifiedFilter').val('');
        $('#activeFilter').val('');
        loadAccounts(1);
    });

    $('#pagination').on('click', 'button:not(:disabled)', function () {
        loadAccounts($(this).data('page'));
    });

    $('#accountsTableBody').on('click', '.btn-edit', function () {
        openEdit($(this).data('id'));
    });

    $('#accountsTableBody').on('click', '.btn-verify', function () {
        changeVerification($(this).data('id'), true);
    });

    $('#accountsTableBody').on('click', '.btn-unverify', function () {
        changeVerification($(this).data('id'), false);
    });

    $('#accountsTableBody').on('click', '.btn-delete', function () {
        var id = $(this).data('id');

        if (!confirm('هل تريد حذف هذا الحساب البنكي؟')) {
            return;
        }

        $.ajax({
            url: routeUrl(routes.destroy, id),
            type: 'POST',
            dataType: 'json',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                _method: 'DELETE'
            },
            success: function (response) {
                showPageAlert(
                    'success',
                    response.message || 'تم حذف الحساب البنكي.'
                );

                loadAccounts(currentPage);
            },
            error: function (xhr) {
                showPageAlert(
                    'danger',
                    xhr.responseJSON?.message
                        || 'تعذر حذف الحساب البنكي.'
                );
            }
        });
    });

    $('#accountForm').on('submit', function (event) {
        event.preventDefault();

        clearErrors();
        setSaving(true);

        var id = $('#bankAccountId').val();
        var formData = new FormData(this);

        if (id) {
            formData.set('_method', 'PUT');
            formData.set('employee_id', $('#employeeId').val());
        }

        if (!$('#isPrimary').is(':checked')) {
            formData.set('is_primary', '0');
        }

        if (!$('#isActive').is(':checked')) {
            formData.set('is_active', '0');
        }

        $.ajax({
            url: id
                ? routeUrl(routes.update, id)
                : routes.store,
            type: 'POST',
            data: formData,
            dataType: 'json',
            processData: false,
            contentType: false,
            success: function (response) {
                closeModal();

                showPageAlert(
                    'success',
                    response.message
                        || 'تم حفظ الحساب البنكي بنجاح.'
                );

                loadAccounts(id ? currentPage : 1);
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    showErrors(xhr.responseJSON?.errors || {});

                    $('#modalAlert')
                        .removeClass('d-none')
                        .text(
                            xhr.responseJSON?.message
                            || 'يرجى مراجعة البيانات المدخلة.'
                        );

                    $('#accountModal .custom-modal-body').animate({
                        scrollTop: 0
                    }, 200);

                    return;
                }

                $('#modalAlert')
                    .removeClass('d-none')
                    .text(
                        xhr.responseJSON?.message
                        || 'حدث خطأ أثناء حفظ الحساب.'
                    );
            },
            complete: function () {
                setSaving(false);
            }
        });
    });

    loadAccounts(1);
});
</script>
@endpush