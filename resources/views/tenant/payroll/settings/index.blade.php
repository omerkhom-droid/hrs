@extends('layouts.tenant')

@section('title', 'إعدادات الرواتب البنكية')
@section('page-title', 'إعدادات الرواتب البنكية')

@section('content')

@php
    $settingsDataUrl = route('app.payroll.settings.data');
    $settingsStoreUrl = route('app.payroll.settings.store');
@endphp

<style>
    .banking-settings-page .settings-card {
        border: 0;
        border-radius: 18px;
        box-shadow: 0 8px 30px rgba(15, 23, 42, 0.06);
    }

    .banking-settings-page .section-title {
        font-size: 1rem;
        font-weight: 700;
        color: #172033;
        margin-bottom: 4px;
    }

    .banking-settings-page .section-description {
        color: #7c8799;
        font-size: .85rem;
        margin-bottom: 0;
    }

    .banking-settings-page .section-header {
        border-bottom: 1px solid #edf0f5;
        padding-bottom: 16px;
        margin-bottom: 20px;
    }

    .banking-settings-page .form-label {
        font-weight: 600;
        font-size: .88rem;
        color: #364152;
    }

    .banking-settings-page .form-control,
    .banking-settings-page .form-select {
        min-height: 44px;
        border-radius: 10px;
        border-color: #dce2ea;
    }

    .banking-settings-page .form-control:focus,
    .banking-settings-page .form-select:focus {
        border-color: #6ea8fe;
        box-shadow: 0 0 0 .2rem rgba(13, 110, 253, .10);
    }

    .banking-settings-page .sensitive-field {
        position: relative;
    }

    .banking-settings-page .sensitive-field .form-control {
        padding-left: 48px;
    }

    .banking-settings-page .toggle-sensitive {
        position: absolute;
        left: 5px;
        top: 5px;
        width: 36px;
        height: 34px;
        border: 0;
        border-radius: 8px;
        background: #eef3fb;
        color: #46617f;
    }

    .banking-settings-page .readiness-box {
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 14px;
        padding: 16px;
    }

    .banking-settings-page .readiness-list {
        margin: 12px 0 0;
        padding-right: 18px;
        color: #b42318;
        font-size: .88rem;
    }

    .banking-settings-page .save-bar {
        position: sticky;
        bottom: 12px;
        z-index: 20;
        border-radius: 16px;
        background: rgba(255, 255, 255, .96);
        box-shadow: 0 8px 30px rgba(15, 23, 42, .12);
        backdrop-filter: blur(8px);
    }

    .banking-settings-page .field-error {
        display: block;
        margin-top: 5px;
        color: #dc3545;
        font-size: .8rem;
    }

    .banking-settings-page .is-loading {
        opacity: .65;
        pointer-events: none;
    }
</style>

<div class="banking-settings-page">

    <div id="pageAlert" class="alert d-none mb-4"></div>

    <div class="row g-4">

        <div class="col-xl-8">

            <form id="payrollSettingsForm" autocomplete="off">

                @csrf

                <div class="card settings-card mb-4">

                    <div class="card-body p-4">

                        <div class="section-header">
                            <div class="section-title">
                                بيانات المنشأة
                            </div>

                            <p class="section-description">
                                البيانات المستخدمة في ملفات الرواتب وحماية الأجور.
                            </p>
                        </div>

                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label">
                                    اسم المنشأة
                                </label>

                                <input
                                    type="text"
                                    name="establishment_name"
                                    id="establishmentName"
                                    class="form-control"
                                    maxlength="255"
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    رقم المنشأة
                                </label>

                                <input
                                    type="text"
                                    name="establishment_number"
                                    id="establishmentNumber"
                                    class="form-control"
                                    maxlength="100"
                                    dir="ltr"
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    الرقم الموحد
                                </label>

                                <input
                                    type="text"
                                    name="unified_number"
                                    id="unifiedNumber"
                                    class="form-control"
                                    maxlength="100"
                                    dir="ltr"
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    رقم السجل التجاري
                                </label>

                                <input
                                    type="text"
                                    name="commercial_registration_number"
                                    id="commercialRegistrationNumber"
                                    class="form-control"
                                    maxlength="100"
                                    dir="ltr"
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    معرف المنشأة في حماية الأجور
                                </label>

                                <input
                                    type="text"
                                    name="wps_employer_id"
                                    id="wpsEmployerId"
                                    class="form-control"
                                    maxlength="100"
                                    dir="ltr"
                                >
                            </div>

                        </div>

                    </div>

                </div>


                <div class="card settings-card mb-4">

                    <div class="card-body p-4">

                        <div class="section-header">
                            <div class="section-title">
                                الحساب البنكي للشركة
                            </div>

                            <p class="section-description">
                                حساب الشركة الذي تُحوّل منه رواتب الموظفين.
                            </p>
                        </div>

                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label">
                                    اسم البنك
                                </label>

                                <input
                                    type="text"
                                    name="payroll_bank_name"
                                    id="payrollBankName"
                                    class="form-control"
                                    maxlength="150"
                                >
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">
                                    رمز البنك
                                </label>

                                <input
                                    type="text"
                                    name="payroll_bank_code"
                                    id="payrollBankCode"
                                    class="form-control"
                                    maxlength="50"
                                    dir="ltr"
                                >
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">
                                    رمز SWIFT
                                </label>

                                <input
                                    type="text"
                                    name="payroll_bank_swift"
                                    id="payrollBankSwift"
                                    class="form-control text-uppercase"
                                    maxlength="20"
                                    dir="ltr"
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    اسم صاحب الحساب
                                </label>

                                <input
                                    type="text"
                                    name="payroll_account_holder_name"
                                    id="payrollAccountHolderName"
                                    class="form-control"
                                    maxlength="255"
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    رقم الحساب البنكي
                                </label>

                                <div class="sensitive-field">
                                    <input
                                        type="password"
                                        name="payroll_account_number"
                                        id="payrollAccountNumber"
                                        class="form-control"
                                        maxlength="100"
                                        dir="ltr"
                                        autocomplete="new-password"
                                    >

                                    <button
                                        type="button"
                                        class="toggle-sensitive"
                                        data-target="#payrollAccountNumber"
                                        title="إظهار أو إخفاء"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>

                                <div id="accountNumberHint" class="form-text"></div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">
                                    رقم الآيبان
                                </label>

                                <div class="sensitive-field">
                                    <input
                                        type="password"
                                        name="payroll_iban"
                                        id="payrollIban"
                                        class="form-control text-uppercase"
                                        maxlength="34"
                                        dir="ltr"
                                        autocomplete="new-password"
                                        placeholder="SA0000000000000000000000"
                                    >

                                    <button
                                        type="button"
                                        class="toggle-sensitive"
                                        data-target="#payrollIban"
                                        title="إظهار أو إخفاء"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>

                                <div id="ibanHint" class="form-text">
                                    اترك الحقل فارغًا أثناء التعديل للاحتفاظ بالآيبان المحفوظ.
                                </div>
                            </div>

                        </div>

                    </div>

                </div>


                <div class="card settings-card mb-4">

                    <div class="card-body p-4">

                        <div class="section-header">
                            <div class="section-title">
                                إعدادات التحويل وحماية الأجور
                            </div>

                            <p class="section-description">
                                التحكم في تجهيز ملفات تحويل الرواتب.
                            </p>
                        </div>

                        <div class="row g-3">

                            <div class="col-md-4">
                                <label class="form-label">
                                    صيغة الملف الافتراضية
                                </label>

                                <select
                                    name="default_file_format"
                                    id="defaultFileFormat"
                                    class="form-select"
                                >
                                    <option value="csv">CSV</option>
                                    <option value="txt">TXT</option>
                                    <option value="sif">SIF</option>
                                    <option value="bank_csv">
                                        ملف البنك CSV
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">
                                    يوم صرف الراتب
                                </label>

                                <input
                                    type="number"
                                    name="salary_payment_day"
                                    id="salaryPaymentDay"
                                    class="form-control"
                                    min="1"
                                    max="31"
                                    value="27"
                                >
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">
                                    بادئة مرجع التحويل
                                </label>

                                <input
                                    type="text"
                                    name="payment_reference_prefix"
                                    id="paymentReferencePrefix"
                                    class="form-control text-uppercase"
                                    maxlength="30"
                                    dir="ltr"
                                    placeholder="PAY"
                                >
                            </div>

                            <div class="col-md-6">
                                <div class="border rounded-3 p-3 h-100">
                                    <div class="form-check form-switch">
                                        <input
                                            type="checkbox"
                                            name="wps_enabled"
                                            id="wpsEnabled"
                                            class="form-check-input"
                                            value="1"
                                        >

                                        <label
                                            class="form-check-label fw-semibold"
                                            for="wpsEnabled"
                                        >
                                            تفعيل حماية الأجور
                                        </label>
                                    </div>

                                    <div class="text-muted small mt-2">
                                        عند التفعيل يجب استكمال بيانات المنشأة والحساب البنكي.
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="border rounded-3 p-3 h-100">
                                    <div class="form-check form-switch">
                                        <input
                                            type="checkbox"
                                            name="require_verified_bank_account"
                                            id="requireVerifiedBankAccount"
                                            class="form-check-input"
                                            value="1"
                                        >

                                        <label
                                            class="form-check-label fw-semibold"
                                            for="requireVerifiedBankAccount"
                                        >
                                            اشتراط حساب بنكي موثّق
                                        </label>
                                    </div>

                                    <div class="text-muted small mt-2">
                                        يمنع تجهيز الموظف للتحويل قبل توثيق حسابه البنكي.
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>

                </div>


                <div class="save-bar p-3 d-flex align-items-center justify-content-between gap-3 flex-wrap">

                    <div class="text-muted small">
                        يتم تشفير رقم الحساب والآيبان داخل قاعدة البيانات.
                    </div>

                    <button
                        type="submit"
                        id="saveSettingsButton"
                        class="btn btn-primary px-4"
                    >
                        <span class="button-text">
                            حفظ الإعدادات
                        </span>

                        <span class="button-loading d-none">
                            <span class="spinner-border spinner-border-sm ms-1"></span>
                            جارٍ الحفظ...
                        </span>
                    </button>

                </div>

            </form>

        </div>


        <div class="col-xl-4">

            <div class="card settings-card position-sticky" style="top: 20px;">

                <div class="card-body p-4">

                    <div class="section-title">
                        جاهزية التحويل البنكي
                    </div>

                    <p class="section-description">
                        فحص البيانات المطلوبة قبل تجهيز ملفات الرواتب.
                    </p>

                    <div class="readiness-box mt-4">

                        <div class="d-flex align-items-center gap-3">

                            <div
                                id="readinessIcon"
                                class="rounded-circle d-flex align-items-center justify-content-center bg-secondary-subtle text-secondary"
                                style="width: 46px; height: 46px;"
                            >
                                <i class="bi bi-hourglass-split"></i>
                            </div>

                            <div>
                                <div id="readinessTitle" class="fw-bold">
                                    جارٍ فحص الإعدادات
                                </div>

                                <div id="readinessDescription" class="text-muted small">
                                    يرجى الانتظار...
                                </div>
                            </div>

                        </div>

                        <ul id="readinessIssues" class="readiness-list d-none"></ul>

                    </div>

                    <div class="mt-4">

                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">حماية الأجور</span>
                            <span id="wpsStatus" class="badge bg-secondary-subtle text-secondary">
                                غير محدد
                            </span>
                        </div>

                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">الحساب البنكي</span>
                            <span id="bankAccountStatus" class="badge bg-secondary-subtle text-secondary">
                                غير محدد
                            </span>
                        </div>

                        <div class="d-flex justify-content-between py-2">
                            <span class="text-muted">الآيبان</span>
                            <span id="maskedIban" dir="ltr">—</span>
                        </div>

                    </div>

                    <div class="alert alert-warning small mt-4 mb-0">
                        صيغة ملف البنك النهائية تعتمد على مواصفات البنك المتعاقد معه.
                        لا تعتمد صيغة SIF قبل الحصول على نموذج البنك الرسمي.
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>
@endsection


@push('scripts')
<script>
$(function () {
    'use strict';

    var settingsDataUrl = @json($settingsDataUrl);
    var settingsStoreUrl = @json($settingsStoreUrl);

    function showAlert(type, message) {
        $('#pageAlert')
            .removeClass('d-none alert-success alert-danger alert-warning alert-info')
            .addClass('alert-' + type)
            .text(message);

        $('html, body').animate({
            scrollTop: 0
        }, 250);
    }

    function hideAlert() {
        $('#pageAlert')
            .addClass('d-none')
            .removeClass('alert-success alert-danger alert-warning alert-info')
            .text('');
    }

    function setLoading(isLoading) {
        $('#payrollSettingsForm').toggleClass('is-loading', isLoading);

        $('#saveSettingsButton').prop('disabled', isLoading);
        $('#saveSettingsButton .button-text').toggleClass('d-none', isLoading);
        $('#saveSettingsButton .button-loading').toggleClass('d-none', !isLoading);
    }

    function clearValidationErrors() {
        $('.field-error').remove();
        $('.is-invalid').removeClass('is-invalid');
    }

    function showValidationErrors(errors) {
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

    function checkedValue(value) {
        return value === true
            || value === 1
            || value === '1';
    }

    function fillForm(settings) {
        settings = settings || {};

        $('#establishmentName').val(settings.establishment_name || '');
        $('#establishmentNumber').val(settings.establishment_number || '');
        $('#unifiedNumber').val(settings.unified_number || '');
        $('#commercialRegistrationNumber').val(
            settings.commercial_registration_number || ''
        );
        $('#wpsEmployerId').val(settings.wps_employer_id || '');

        $('#payrollBankName').val(settings.payroll_bank_name || '');
        $('#payrollBankCode').val(settings.payroll_bank_code || '');
        $('#payrollBankSwift').val(settings.swift_code || '');
        $('#payrollAccountHolderName').val(
            settings.payroll_account_holder_name || ''
        );

        $('#payrollAccountNumber').val('');
        $('#payrollIban').val('');

        $('#defaultFileFormat').val(
            settings.default_file_format || 'csv'
        );

        $('#salaryPaymentDay').val(
            settings.salary_payment_day || 27
        );

        $('#paymentReferencePrefix').val(
            settings.payment_reference_prefix || 'PAY'
        );

        $('#wpsEnabled').prop(
            'checked',
            checkedValue(settings.wps_enabled)
        );

        $('#requireVerifiedBankAccount').prop(
            'checked',
            checkedValue(settings.require_verified_bank_account)
        );

        var maskedAccount =
            settings.masked_payroll_account_number
            || settings.masked_account_number
            || '';

        var maskedIban =
            settings.masked_payroll_iban
            || settings.masked_iban
            || '';

        $('#accountNumberHint').text(
            maskedAccount
                ? 'الحساب المحفوظ: ' + maskedAccount + ' — اترك الحقل فارغًا للاحتفاظ به.'
                : 'لم تتم إضافة رقم حساب بعد.'
        );

        $('#ibanHint').text(
            maskedIban
                ? 'الآيبان المحفوظ: ' + maskedIban + ' — اترك الحقل فارغًا للاحتفاظ به.'
                : 'أدخل الآيبان الخاص بحساب رواتب الشركة.'
        );

        $('#maskedIban').text(maskedIban || '—');

        updateSummary(settings);
    }

    function updateSummary(settings) {
        var wpsEnabled = checkedValue(settings.wps_enabled);

        $('#wpsStatus')
            .removeClass(
                'bg-secondary-subtle text-secondary ' +
                'bg-success-subtle text-success ' +
                'bg-warning-subtle text-warning'
            )
            .addClass(
                wpsEnabled
                    ? 'bg-success-subtle text-success'
                    : 'bg-warning-subtle text-warning'
            )
            .text(wpsEnabled ? 'مفعّل' : 'غير مفعّل');

        var hasBankAccount =
            checkedValue(settings.has_account_number)
            || checkedValue(settings.has_payroll_iban);

        $('#bankAccountStatus')
            .removeClass(
                'bg-secondary-subtle text-secondary ' +
                'bg-success-subtle text-success ' +
                'bg-danger-subtle text-danger'
            )
            .addClass(
                hasBankAccount
                    ? 'bg-success-subtle text-success'
                    : 'bg-danger-subtle text-danger'
            )
            .text(hasBankAccount ? 'مسجل' : 'غير مكتمل');

        var ready = checkedValue(settings.ready_for_bank_file);

        var issues =
            settings.readiness_issues
            || settings.issues
            || [];

        renderReadiness(ready, issues, wpsEnabled);
    }

    function renderReadiness(ready, issues, wpsEnabled) {
        var icon = $('#readinessIcon');
        var list = $('#readinessIssues');

        icon.removeClass(
            'bg-secondary-subtle text-secondary ' +
            'bg-success-subtle text-success ' +
            'bg-danger-subtle text-danger ' +
            'bg-warning-subtle text-warning'
        );

        if (!wpsEnabled) {
            icon
                .addClass('bg-warning-subtle text-warning')
                .html('<i class="bi bi-pause-circle"></i>');

            $('#readinessTitle').text('حماية الأجور غير مفعّلة');
            $('#readinessDescription').text(
                'يمكن حفظ البيانات الآن وتفعيلها لاحقًا.'
            );
        } else if (ready) {
            icon
                .addClass('bg-success-subtle text-success')
                .html('<i class="bi bi-check-circle"></i>');

            $('#readinessTitle').text('الإعدادات جاهزة');
            $('#readinessDescription').text(
                'يمكن الانتقال إلى حسابات الموظفين البنكية.'
            );
        } else {
            icon
                .addClass('bg-danger-subtle text-danger')
                .html('<i class="bi bi-exclamation-triangle"></i>');

            $('#readinessTitle').text('الإعدادات غير مكتملة');
            $('#readinessDescription').text(
                'أكمل البيانات التالية قبل تجهيز التحويلات.'
            );
        }

        list.empty();

        if (Array.isArray(issues) && issues.length) {
            $.each(issues, function (index, issue) {
                $('<li>', {
                    text: issue
                }).appendTo(list);
            });

            list.removeClass('d-none');
        } else {
            list.addClass('d-none');
        }
    }

    function loadSettings() {
        hideAlert();

        $.ajax({
            url: settingsDataUrl,
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                var settings =
                    response.setting
                    || response.data
                    || response
                    || {};

                fillForm(settings);
            },
            error: function (xhr) {
                showAlert(
                    'danger',
                    xhr.responseJSON?.message
                        || 'تعذر تحميل إعدادات الرواتب البنكية.'
                );
            }
        });
    }

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

    $('#payrollIban').on('input', function () {
        this.value = this.value
            .toUpperCase()
            .replace(/\s+/g, '');
    });

    $('#payrollBankSwift, #paymentReferencePrefix').on('input', function () {
        this.value = this.value.toUpperCase();
    });

    $('#payrollSettingsForm').on('submit', function (event) {
        event.preventDefault();

        hideAlert();
        clearValidationErrors();
        setLoading(true);

        var formData = new FormData(this);

        if (!$('#wpsEnabled').is(':checked')) {
            formData.set('wps_enabled', '0');
        }

        if (!$('#requireVerifiedBankAccount').is(':checked')) {
            formData.set('require_verified_bank_account', '0');
        }

        $.ajax({
            url: settingsStoreUrl,
            type: 'POST',
            data: formData,
            dataType: 'json',
            processData: false,
            contentType: false,
            success: function (response) {
                showAlert(
                    'success',
                    response.message
                        || 'تم حفظ إعدادات الرواتب البنكية بنجاح.'
                );

                var settings =
                    response.setting
                    || response.data
                    || null;

                if (settings) {
                    fillForm(settings);
                } else {
                    loadSettings();
                }
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    showValidationErrors(
                        xhr.responseJSON?.errors || {}
                    );

                    showAlert(
                        'danger',
                        xhr.responseJSON?.message
                            || 'يرجى مراجعة البيانات المدخلة.'
                    );

                    return;
                }

                showAlert(
                    'danger',
                    xhr.responseJSON?.message
                        || 'حدث خطأ أثناء حفظ الإعدادات.'
                );
            },
            complete: function () {
                setLoading(false);
            }
        });
    });

    loadSettings();
});
</script>
@endpush