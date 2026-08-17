@extends('layouts.tenant')

@section('title', 'مواقع العمل')
@section('page-title', 'مواقع العمل')

@section('content')

@php
    $tenantTimezone = auth()->user()->tenant?->timezone
        ?? config('app.timezone', 'Asia/Riyadh');

    $typeLabels = [
        'office' => 'مكتب',
        'site' => 'موقع عمل',
        'warehouse' => 'مستودع',
        'remote' => 'عمل عن بُعد',
        'other' => 'أخرى',
    ];
@endphp

<style>
    .work-location-stat {
        height: 100%;
        padding: 18px;
        border: 1px solid #e8edf5;
        border-radius: 16px;
        background: #fff;
    }

    .work-location-stat-icon {
        width: 46px;
        height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 46px;
        border-radius: 14px;
        color: #0d6efd;
        background: #eaf2ff;
        font-size: 1.2rem;
    }

    .work-location-name-cell {
        min-width: 230px;
    }

    .work-location-code {
        direction: ltr;
        display: inline-block;
        font-family: monospace;
        font-size: .82rem;
    }

    .coordinates-box {
        border: 1px dashed #cfd8e6;
        border-radius: 12px;
        background: #f9fbfe;
    }

    #workLocationModal {
        overflow: hidden !important;
        padding-left: 0 !important;
    }

    #workLocationModal .modal-dialog {
        height: calc(100vh - 30px);
        max-height: calc(100vh - 30px);
        margin-top: 15px;
        margin-bottom: 15px;
    }

    #workLocationModal .modal-content,
    #workLocationModal #workLocationForm {
        display: flex;
        flex-direction: column;
        width: 100%;
        height: 100%;
        max-height: 100%;
        min-height: 0;
        overflow: hidden;
    }

    #workLocationModal .modal-header,
    #workLocationModal .modal-footer {
        flex: 0 0 auto;
        background: #fff;
        z-index: 2;
    }

    #workLocationModal .modal-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto !important;
        overflow-x: hidden;
        overscroll-behavior: contain;
        scrollbar-gutter: stable;
    }

    @media (max-width: 767.98px) {
        #workLocationModal .modal-dialog {
            width: calc(100% - 16px);
            height: calc(100vh - 16px);
            max-height: calc(100vh - 16px);
            margin: 8px;
        }
    }
</style>

<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-1">مواقع العمل</h4>
            <div class="text-muted small">
                إدارة المكاتب والمواقع والمستودعات ونطاقات تسجيل الحضور
            </div>
        </div>

        @can('work_locations.create')
            <button type="button" class="btn btn-primary" id="btnAddWorkLocation">
                <i class="bi bi-plus-lg"></i>
                إضافة موقع عمل
            </button>
        @endcan
    </div>

    {{-- Summary --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="work-location-stat">
                <div class="d-flex align-items-center gap-3">
                    <span class="work-location-stat-icon"><i class="bi bi-geo-alt"></i></span>
                    <div>
                        <div class="text-muted small">إجمالي النتائج</div>
                        <div class="fs-5 fw-bold" id="totalWorkLocations">—</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="work-location-stat">
                <div class="d-flex align-items-center gap-3">
                    <span class="work-location-stat-icon"><i class="bi bi-check-circle"></i></span>
                    <div>
                        <div class="text-muted small">النشطة في الصفحة</div>
                        <div class="fs-5 fw-bold" id="pageActiveWorkLocations">—</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="work-location-stat">
                <div class="d-flex align-items-center gap-3">
                    <span class="work-location-stat-icon"><i class="bi bi-crosshair"></i></span>
                    <div>
                        <div class="text-muted small">بإحداثيات في الصفحة</div>
                        <div class="fs-5 fw-bold" id="pageGeocodedWorkLocations">—</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <form id="workLocationFilters">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-3 col-md-6">
                        <label for="filter_search" class="form-label">البحث</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input
                                type="search"
                                class="form-control"
                                id="filter_search"
                                placeholder="الاسم، الكود، المدينة..."
                                autocomplete="off"
                            >
                        </div>
                    </div>

                    <div class="col-xl-2 col-md-6">
                        <label for="filter_branch_id" class="form-label">الفرع</label>
                        <select class="form-select" id="filter_branch_id">
                            <option value="">جميع الفروع</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">
                                    {{ $branch->name }}
                                    @if($branch->is_main) — الرئيسي @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-md-4">
                        <label for="filter_type" class="form-label">نوع الموقع</label>
                        <select class="form-select" id="filter_type">
                            <option value="">جميع الأنواع</option>
                            @foreach($typeLabels as $type => $label)
                                <option value="{{ $type }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-md-4">
                        <label for="filter_status" class="form-label">الحالة</label>
                        <select class="form-select" id="filter_status">
                            <option value="">جميع الحالات</option>
                            <option value="active">نشط</option>
                            <option value="inactive">غير نشط</option>
                        </select>
                    </div>

                    <div class="col-xl-1 col-md-4">
                        <label for="filter_per_page" class="form-label">العرض</label>
                        <select class="form-select" id="filter_per_page">
                            <option value="10">10</option>
                            <option value="15" selected>15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>

                    <div class="col-xl-2 col-md-12">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">بحث</button>
                            <button type="button" class="btn btn-outline-secondary" id="btnResetWorkLocationFilters" title="إعادة ضبط">
                                <i class="bi bi-arrow-counterclockwise"></i>
                                <span class="d-xl-none">إعادة</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <strong>قائمة مواقع العمل</strong>

                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-subtle text-primary" id="workLocationCountBadge">0 سجل</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnRefreshWorkLocations">
                        <i class="bi bi-arrow-repeat"></i>
                        تحديث
                    </button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>موقع العمل</th>
                        <th>النوع</th>
                        <th>الفرع</th>
                        <th>المدينة</th>
                        <th>نطاق الحضور</th>
                        <th>الإحداثيات</th>
                        <th>الحالة</th>
                        <th class="text-center">الإجراءات</th>
                    </tr>
                </thead>
                <tbody id="workLocationsTableBody">
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">جاري تحميل البيانات...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap gap-3">
            <small class="text-muted" id="workLocationPaginationSummary"></small>
            <nav aria-label="صفحات مواقع العمل">
                <ul class="pagination pagination-sm mb-0" id="workLocationPagination"></ul>
            </nav>
        </div>
    </div>
</div>

{{-- Work Location Modal --}}
<div class="modal fade" id="workLocationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form id="workLocationForm">
                @csrf
                <input type="hidden" id="work_location_id">

                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1" id="workLocationModalTitle">إضافة موقع عمل</h5>
                        <div class="text-muted small">الحقول التي تحمل علامة (*) إلزامية</div>
                    </div>
                    <button type="button" class="btn-close" data-work-location-modal-close aria-label="إغلاق"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="alert alert-danger d-none" id="workLocationFormErrors"></div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="work_location_branch_id" class="form-label">الفرع</label>
                            <select class="form-select" name="branch_id" id="work_location_branch_id">
                                <option value="">عام — غير مرتبط بفرع</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">
                                        {{ $branch->name }}
                                        @if($branch->is_main) — الفرع الرئيسي @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="work_location_type" class="form-label">نوع الموقع <span class="text-danger">*</span></label>
                            <select class="form-select" name="type" id="work_location_type" required>
                                @foreach($typeLabels as $type => $label)
                                    <option value="{{ $type }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="work_location_code" class="form-label">الكود <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control"
                                name="code"
                                id="work_location_code"
                                maxlength="50"
                                dir="ltr"
                                placeholder="HQ-OFFICE"
                                required
                            >
                        </div>

                        <div class="col-md-6">
                            <label for="work_location_name" class="form-label">الاسم بالعربية <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control"
                                name="name"
                                id="work_location_name"
                                maxlength="255"
                                placeholder="المكتب الرئيسي"
                                required
                            >
                        </div>

                        <div class="col-md-6">
                            <label for="work_location_name_en" class="form-label">الاسم بالإنجليزية</label>
                            <input
                                type="text"
                                class="form-control"
                                name="name_en"
                                id="work_location_name_en"
                                maxlength="255"
                                dir="ltr"
                                placeholder="Head Office"
                            >
                        </div>

                        <div class="col-md-2">
                            <label for="work_location_country_code" class="form-label">رمز الدولة <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control"
                                name="country_code"
                                id="work_location_country_code"
                                value="SA"
                                maxlength="2"
                                dir="ltr"
                                required
                            >
                        </div>

                        <div class="col-md-4">
                            <label for="work_location_city" class="form-label">المدينة</label>
                            <input
                                type="text"
                                class="form-control"
                                name="city"
                                id="work_location_city"
                                maxlength="255"
                                placeholder="الرياض"
                            >
                        </div>

                        <div class="col-md-6">
                            <label for="work_location_timezone" class="form-label">المنطقة الزمنية <span class="text-danger">*</span></label>
                            <select class="form-select" name="timezone" id="work_location_timezone" required>
                                @foreach($timezones as $timezone)
                                    <option
                                        value="{{ $timezone }}"
                                        @selected($timezone === $tenantTimezone)
                                    >
                                        {{ $timezone }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="work_location_address" class="form-label">العنوان</label>
                            <textarea
                                class="form-control"
                                name="address"
                                id="work_location_address"
                                rows="3"
                                maxlength="5000"
                                placeholder="الحي، الشارع، رقم المبنى..."
                            ></textarea>
                        </div>

                        <div class="col-12">
                            <div class="coordinates-box p-3">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                    <div>
                                        <div class="fw-semibold">إعدادات الموقع الجغرافي والحضور</div>
                                        <div class="text-muted small">يجب إدخال خط العرض وخط الطول معًا لتفعيل التحقق الجغرافي.</div>
                                    </div>

                                    <a
                                        href="#"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="btn btn-sm btn-outline-primary d-none"
                                        id="coordinatesPreviewLink"
                                    >
                                        <i class="bi bi-map"></i>
                                        عرض على الخريطة
                                    </a>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="work_location_latitude" class="form-label">خط العرض</label>
                                        <input
                                            type="number"
                                            class="form-control"
                                            name="latitude"
                                            id="work_location_latitude"
                                            min="-90"
                                            max="90"
                                            step="any"
                                            dir="ltr"
                                            placeholder="24.7135517"
                                        >
                                    </div>

                                    <div class="col-md-4">
                                        <label for="work_location_longitude" class="form-label">خط الطول</label>
                                        <input
                                            type="number"
                                            class="form-control"
                                            name="longitude"
                                            id="work_location_longitude"
                                            min="-180"
                                            max="180"
                                            step="any"
                                            dir="ltr"
                                            placeholder="46.6752957"
                                        >
                                    </div>

                                    <div class="col-md-4">
                                        <label for="work_location_attendance_radius" class="form-label">نطاق الحضور بالمتر <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input
                                                type="number"
                                                class="form-control"
                                                name="attendance_radius"
                                                id="work_location_attendance_radius"
                                                min="0"
                                                max="100000"
                                                value="100"
                                                required
                                            >
                                            <span class="input-group-text">متر</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="border rounded-3 p-3">
                                <div class="form-check form-switch mb-0">
                                    <input type="hidden" name="is_active" value="0">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="is_active"
                                        value="1"
                                        id="work_location_is_active"
                                        checked
                                    >
                                    <label class="form-check-label" for="work_location_is_active">موقع العمل نشط ومتاح للاستخدام</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-work-location-modal-close>إلغاء</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveWorkLocation">
                        <span class="save-label">حفظ البيانات</span>
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
    'use strict';

    const csrfToken = @json(csrf_token());

    const routes = {
        data: @json(route('app.organization.work-locations.data')),
        store: @json(route('app.organization.work-locations.store')),
        show: @json(route('app.organization.work-locations.show', ['workLocation' => '__WORK_LOCATION__'])),
        update: @json(route('app.organization.work-locations.update', ['workLocation' => '__WORK_LOCATION__'])),
        destroy: @json(route('app.organization.work-locations.destroy', ['workLocation' => '__WORK_LOCATION__'])),
    };

    const typeLabels = @json($typeLabels);
    const defaultTimezone = @json($tenantTimezone);

    const permissions = {
        update: @json(auth()->user()->can('work_locations.update')),
        delete: @json(auth()->user()->can('work_locations.delete')),
    };

    const $workLocationModal = $('#workLocationModal');
    let currentPage = 1;
    let searchTimer = null;
    let tableRequest = null;

    $workLocationModal.appendTo('body');

    function workLocationUrl(url, id) {
        return url.replace('__WORK_LOCATION__', id);
    }

    function escapeHtml(value) {
        return $('<div>').text(value ?? '').html();
    }

    function showWorkLocationModal() {
        $('#workLocationModalBackdrop').remove();

        $('<div>', {
            id: 'workLocationModalBackdrop',
            class: 'modal-backdrop fade show',
        }).appendTo('body');

        $('body')
            .addClass('modal-open')
            .css('overflow', 'hidden');

        $workLocationModal
            .attr({
                role: 'dialog',
                'aria-modal': 'true',
            })
            .removeAttr('aria-hidden')
            .css('display', 'block')
            .addClass('show');
    }

    function hideWorkLocationModal() {
        $workLocationModal
            .removeClass('show')
            .css('display', 'none')
            .attr('aria-hidden', 'true')
            .removeAttr('aria-modal role');

        $('#workLocationModalBackdrop').remove();

        $('body')
            .removeClass('modal-open')
            .css('overflow', '');
    }

    function getErrorMessage(xhr) {
        if (xhr.responseJSON?.errors) {
            const messages = [];

            $.each(xhr.responseJSON.errors, function (field, fieldErrors) {
                $.each(fieldErrors, function (index, message) {
                    messages.push(message);
                });
            });

            return messages.join('\n');
        }

        return xhr.responseJSON?.message || 'حدث خطأ غير متوقع، يرجى المحاولة مرة أخرى.';
    }

    function showRequestError(xhr, insideModal = false) {
        const message = getErrorMessage(xhr);

        if (insideModal) {
            $('#workLocationFormErrors')
                .removeClass('d-none')
                .html(escapeHtml(message).replace(/\n/g, '<br>'));

            $('#workLocationModal .modal-body').scrollTop(0);
        }

        if (window.Swal) {
            Swal.fire({
                icon: 'error',
                title: 'تعذر تنفيذ العملية',
                text: message,
            });
        } else if (!insideModal) {
            alert(message);
        }
    }

    function notifySuccess(message) {
        if (window.Swal) {
            Swal.fire({
                icon: 'success',
                title: 'تمت العملية',
                text: message,
                timer: 1400,
                showConfirmButton: false,
            }).then(function () {
                loadWorkLocations(currentPage);
            });
        } else {
            alert(message);
            loadWorkLocations(currentPage);
        }
    }

    function filters() {
        return {
            search: $.trim($('#filter_search').val()),
            branch_id: $('#filter_branch_id').val(),
            type: $('#filter_type').val(),
            status: $('#filter_status').val(),
            per_page: $('#filter_per_page').val(),
            page: currentPage,
        };
    }

    function resetWorkLocationForm() {
        $('#workLocationForm').trigger('reset');
        $('#work_location_id').val('');
        $('#work_location_branch_id').val('');
        $('#work_location_type').val('office');
        $('#work_location_country_code').val('SA');
        $('#work_location_timezone').val(defaultTimezone);
        $('#work_location_attendance_radius').val(100);
        $('#work_location_is_active').prop('checked', true);
        $('#workLocationFormErrors').addClass('d-none').empty();
        $('#btnSaveWorkLocation').prop('disabled', false);
        $('#btnSaveWorkLocation .save-label').text('حفظ البيانات');
        updateCoordinatesPreview();
    }

    function updateCoordinatesPreview() {
        const latitude = $.trim($('#work_location_latitude').val());
        const longitude = $.trim($('#work_location_longitude').val());
        const valid = latitude !== '' && longitude !== '';

        $('#coordinatesPreviewLink')
            .toggleClass('d-none', !valid)
            .attr(
                'href',
                valid
                    ? 'https://www.google.com/maps?q=' + encodeURIComponent(latitude + ',' + longitude)
                    : '#'
            );
    }

    function statusBadge(isActive) {
        return isActive
            ? '<span class="badge bg-success-subtle text-success">نشط</span>'
            : '<span class="badge bg-danger-subtle text-danger">غير نشط</span>';
    }

    function typeBadge(type) {
        const classes = {
            office: 'bg-primary-subtle text-primary',
            site: 'bg-success-subtle text-success',
            warehouse: 'bg-warning-subtle text-warning',
            remote: 'bg-info-subtle text-info',
            other: 'bg-secondary-subtle text-secondary',
        };

        return '<span class="badge ' + (classes[type] || classes.other) + '">' +
            escapeHtml(typeLabels[type] || 'غير محدد') +
            '</span>';
    }

    function hasCoordinates(item) {
        return item.latitude !== null
            && item.latitude !== ''
            && item.longitude !== null
            && item.longitude !== '';
    }

    function coordinatesLink(item) {
        if (!hasCoordinates(item)) {
            return '<span class="text-muted">غير محددة</span>';
        }

        const coordinates = String(item.latitude) + ',' + String(item.longitude);

        return '<a href="https://www.google.com/maps?q=' + encodeURIComponent(coordinates) + '" ' +
            'target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary">' +
            '<i class="bi bi-map"></i> عرض</a>';
    }

    function renderActions(item) {
        const buttons = [];

        if (permissions.update) {
            buttons.push(
                '<button type="button" class="btn btn-sm btn-outline-primary btn-edit-work-location" ' +
                'data-id="' + item.id + '" title="تعديل">' +
                '<i class="bi bi-pencil"></i><span class="visually-hidden">تعديل</span></button>'
            );
        }

        if (permissions.delete) {
            buttons.push(
                '<button type="button" class="btn btn-sm btn-outline-danger btn-archive-work-location" ' +
                'data-id="' + item.id + '" data-name="' + escapeHtml(item.name) + '" title="أرشفة">' +
                '<i class="bi bi-archive"></i><span class="visually-hidden">أرشفة</span></button>'
            );
        }

        return buttons.length
            ? '<div class="btn-group btn-group-sm">' + buttons.join('') + '</div>'
            : '<span class="text-muted">—</span>';
    }

    function renderTable(response) {
        const rows = response.data || [];
        const $tbody = $('#workLocationsTableBody');

        $('#totalWorkLocations').text(response.total ?? 0);
        $('#workLocationCountBadge').text((response.total ?? 0) + ' سجل');
        $('#pageActiveWorkLocations').text(
            rows.filter(function (item) { return Boolean(item.is_active); }).length
        );
        $('#pageGeocodedWorkLocations').text(
            rows.filter(hasCoordinates).length
        );

        if (!rows.length) {
            $tbody.html(
                '<tr><td colspan="9" class="text-center py-5">' +
                '<div class="text-muted mb-2"><i class="bi bi-geo-alt fs-3"></i></div>' +
                '<div class="fw-semibold">لا توجد مواقع عمل مطابقة</div>' +
                '<small class="text-muted">غيّر معايير البحث أو أضف موقع عمل جديدًا.</small>' +
                '</td></tr>'
            );

            renderPagination(response);
            return;
        }

        let html = '';

        $.each(rows, function (index, item) {
            const number = (response.from || 1) + index;
            const branch = item.branch
                ? '<div class="fw-semibold">' + escapeHtml(item.branch.name) + '</div>' +
                  '<small class="text-muted work-location-code">' + escapeHtml(item.branch.code) + '</small>'
                : '<span class="text-muted">عام</span>';

            html += '<tr>' +
                '<td>' + number + '</td>' +
                '<td class="work-location-name-cell">' +
                    '<div class="fw-semibold">' + escapeHtml(item.name) + '</div>' +
                    (item.name_en
                        ? '<small class="text-muted" dir="ltr">' + escapeHtml(item.name_en) + '</small><br>'
                        : '') +
                    '<span class="work-location-code text-muted">' + escapeHtml(item.code) + '</span>' +
                '</td>' +
                '<td>' + typeBadge(item.type) + '</td>' +
                '<td>' + branch + '</td>' +
                '<td>' + (item.city ? escapeHtml(item.city) : '<span class="text-muted">—</span>') + '</td>' +
                '<td><span class="badge bg-light text-dark border">' + (item.attendance_radius ?? 0) + ' متر</span></td>' +
                '<td>' + coordinatesLink(item) + '</td>' +
                '<td>' + statusBadge(item.is_active) + '</td>' +
                '<td class="text-center">' + renderActions(item) + '</td>' +
            '</tr>';
        });

        $tbody.html(html);
        renderPagination(response);
    }

    function renderPagination(response) {
        const current = Number(response.current_page || 1);
        const last = Number(response.last_page || 1);
        const $pagination = $('#workLocationPagination');

        $('#workLocationPaginationSummary').text(
            response.total
                ? 'عرض ' + response.from + ' إلى ' + response.to + ' من أصل ' + response.total
                : 'لا توجد نتائج'
        );

        $pagination.empty();

        if (last <= 1) return;

        function addPage(label, page, disabled = false, active = false) {
            const $item = $('<li>', {
                class: 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : ''),
            });

            $('<button>', {
                type: 'button',
                class: 'page-link',
                text: label,
                'data-page': page,
                disabled: disabled,
            }).appendTo($item);

            $item.appendTo($pagination);
        }

        addPage('السابق', current - 1, current === 1);

        const start = Math.max(1, current - 2);
        const end = Math.min(last, current + 2);

        if (start > 1) {
            addPage('1', 1, false, current === 1);
            if (start > 2) addPage('…', current, true);
        }

        for (let page = start; page <= end; page += 1) {
            addPage(String(page), page, false, page === current);
        }

        if (end < last) {
            if (end < last - 1) addPage('…', current, true);
            addPage(String(last), last, false, current === last);
        }

        addPage('التالي', current + 1, current === last);
    }

    function loadWorkLocations(page = 1) {
        currentPage = page;

        if (tableRequest) {
            tableRequest.abort();
        }

        $('#workLocationsTableBody').html(
            '<tr><td colspan="9" class="text-center text-muted py-5">' +
            '<span class="spinner-border spinner-border-sm ms-2"></span>جاري تحميل البيانات...</td></tr>'
        );

        tableRequest = $.ajax({
            url: routes.data,
            type: 'GET',
            data: filters(),
            dataType: 'json',
            headers: { Accept: 'application/json' },
            success: renderTable,
            error: function (xhr, status) {
                if (status === 'abort') return;

                $('#workLocationsTableBody').html(
                    '<tr><td colspan="9" class="text-center text-danger py-5">تعذر تحميل البيانات.</td></tr>'
                );
                showRequestError(xhr);
            },
            complete: function () {
                tableRequest = null;
            },
        });
    }

    $(document).on('click', '[data-work-location-modal-close]', hideWorkLocationModal);

    $(document).on('keydown', function (event) {
        if (event.key === 'Escape' && $workLocationModal.hasClass('show')) {
            hideWorkLocationModal();
        }
    });

    $('#workLocationFilters').on('submit', function (event) {
        event.preventDefault();
        loadWorkLocations(1);
    });

    $('#filter_search').on('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            loadWorkLocations(1);
        }, 450);
    });

    $('#filter_branch_id, #filter_type, #filter_status, #filter_per_page').on('change', function () {
        loadWorkLocations(1);
    });

    $('#btnResetWorkLocationFilters').on('click', function () {
        $('#workLocationFilters').trigger('reset');
        loadWorkLocations(1);
    });

    $('#btnRefreshWorkLocations').on('click', function () {
        loadWorkLocations(currentPage);
    });

    $(document).on('click', '#workLocationPagination .page-link', function () {
        const page = Number($(this).data('page'));
        if (page > 0) loadWorkLocations(page);
    });

    $('#work_location_latitude, #work_location_longitude').on('input', updateCoordinatesPreview);

    $('#work_location_country_code, #work_location_code').on('input', function () {
        $(this).val(String($(this).val()).toUpperCase());
    });

    $('#btnAddWorkLocation').on('click', function () {
        resetWorkLocationForm();
        $('#workLocationModalTitle').text('إضافة موقع عمل');
        showWorkLocationModal();
        window.setTimeout(function () { $('#work_location_code').trigger('focus'); }, 100);
    });

    $(document).on('click', '.btn-edit-work-location', function () {
        const id = $(this).data('id');
        const $button = $(this);

        $button.prop('disabled', true);
        resetWorkLocationForm();

        $.ajax({
            url: workLocationUrl(routes.show, id),
            type: 'GET',
            dataType: 'json',
            headers: { Accept: 'application/json' },
            success: function (response) {
                const item = response.work_location;

                $('#work_location_id').val(item.id);
                $('#work_location_branch_id').val(item.branch_id || '');
                $('#work_location_type').val(item.type || 'office');
                $('#work_location_code').val(item.code || '');
                $('#work_location_name').val(item.name || '');
                $('#work_location_name_en').val(item.name_en || '');
                $('#work_location_country_code').val(item.country_code || 'SA');
                $('#work_location_city').val(item.city || '');
                $('#work_location_timezone').val(item.timezone || defaultTimezone);
                $('#work_location_address').val(item.address || '');
                $('#work_location_latitude').val(item.latitude || '');
                $('#work_location_longitude').val(item.longitude || '');
                $('#work_location_attendance_radius').val(item.attendance_radius ?? 100);
                $('#work_location_is_active').prop('checked', Boolean(item.is_active));
                $('#workLocationModalTitle').text('تعديل: ' + item.name);

                updateCoordinatesPreview();
                showWorkLocationModal();
            },
            error: function (xhr) {
                showRequestError(xhr);
            },
            complete: function () {
                $button.prop('disabled', false);
            },
        });
    });

    $('#workLocationForm').on('submit', function (event) {
        event.preventDefault();

        $('#workLocationFormErrors').addClass('d-none').empty();

        const latitude = $.trim($('#work_location_latitude').val());
        const longitude = $.trim($('#work_location_longitude').val());

        if ((latitude === '') !== (longitude === '')) {
            $('#workLocationFormErrors')
                .removeClass('d-none')
                .text('يجب إدخال خط العرض وخط الطول معًا.');
            return;
        }

        const id = $('#work_location_id').val();
        const isEdit = id !== '';
        const data = $(this).serializeArray();

        if (isEdit) {
            data.push({ name: '_method', value: 'PUT' });
        }

        const $button = $('#btnSaveWorkLocation');

        $button.prop('disabled', true);
        $button.find('.save-label').text('جاري الحفظ...');

        $.ajax({
            url: isEdit ? workLocationUrl(routes.update, id) : routes.store,
            type: 'POST',
            data: $.param(data),
            dataType: 'json',
            headers: { Accept: 'application/json' },
            success: function (response) {
                hideWorkLocationModal();
                notifySuccess(response.message);
            },
            error: function (xhr) {
                showRequestError(xhr, true);
            },
            complete: function () {
                $button.prop('disabled', false);
                $button.find('.save-label').text('حفظ البيانات');
            },
        });
    });

    $(document).on('click', '.btn-archive-work-location', function () {
        const id = $(this).data('id');
        const name = $(this).data('name');

        function executeArchive() {
            $.ajax({
                url: workLocationUrl(routes.destroy, id),
                type: 'DELETE',
                data: { _token: csrfToken },
                dataType: 'json',
                headers: { Accept: 'application/json' },
                success: function (response) {
                    notifySuccess(response.message);
                },
                error: function (xhr) {
                    showRequestError(xhr);
                },
            });
        }

        if (window.Swal) {
            Swal.fire({
                icon: 'warning',
                title: 'أرشفة موقع العمل؟',
                text: 'سيتم أرشفة "' + name + '" ولن يظهر ضمن المواقع النشطة.',
                showCancelButton: true,
                confirmButtonText: 'نعم، أرشفة',
                cancelButtonText: 'إلغاء',
                confirmButtonColor: '#dc3545',
            }).then(function (result) {
                if (result.isConfirmed) executeArchive();
            });
        } else if (confirm('هل تريد أرشفة "' + name + '"؟')) {
            executeArchive();
        }
    });

    loadWorkLocations();
});
</script>

@endpush