@extends('layouts.tenant')

@section('title', 'تسجيل حضوري')
@section('page-title', 'تسجيل حضوري')

@section('content')
<style>
    .self-attendance {
        --sa-primary: #2563eb;
        --sa-dark: #0f172a;
        --sa-muted: #64748b;
        --sa-border: #e2e8f0;
        --sa-success: #059669;
        --sa-warning: #d97706;
        --sa-danger: #dc2626;
    }

    .sa-card {
        background: #fff;
        border: 1px solid var(--sa-border);
        border-radius: 18px;
        box-shadow: 0 8px 30px rgba(15, 23, 42, .05);
    }

    .sa-hero {
        position: relative;
        padding: 28px;
        overflow: hidden;
        color: #fff;
        background: linear-gradient(135deg, #0f2d63, #2563eb);
        border-radius: 22px;
    }

    .sa-hero::after {
        position: absolute;
        width: 240px;
        height: 240px;
        top: -110px;
        left: -70px;
        content: '';
        border: 45px solid rgba(255, 255, 255, .08);
        border-radius: 50%;
    }

    .sa-clock {
        position: relative;
        z-index: 1;
        font-size: clamp(36px, 5vw, 58px);
        font-weight: 800;
        letter-spacing: 1px;
        direction: ltr;
    }

    .sa-date {
        position: relative;
        z-index: 1;
        color: rgba(255, 255, 255, .78);
    }

    .sa-status-chip {
        position: relative;
        z-index: 1;
        padding: 9px 14px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 255, 255, .14);
        border: 1px solid rgba(255, 255, 255, .2);
        border-radius: 999px;
        font-size: 13px;
    }

    .sa-section-title {
        color: var(--sa-dark);
        font-size: 15px;
        font-weight: 800;
    }

    .sa-info-icon {
        width: 46px;
        height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--sa-primary);
        background: #eff6ff;
        border-radius: 14px;
        font-size: 21px;
    }

    .sa-label {
        color: var(--sa-muted);
        font-size: 12px;
    }

    .sa-value {
        margin-top: 4px;
        color: var(--sa-dark);
        font-weight: 800;
    }

    .sa-actions {
        padding: 24px;
    }

    .sa-punch-button {
        min-height: 58px;
        font-size: 16px;
        font-weight: 800;
        border-radius: 14px;
    }

    .sa-location-state {
        min-height: 48px;
        padding: 11px 14px;
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--sa-muted);
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 12px;
        font-size: 13px;
    }

    .sa-location-state.ready {
        color: #047857;
        background: #ecfdf5;
        border-color: #a7f3d0;
    }

    .sa-location-state.error {
        color: #b91c1c;
        background: #fef2f2;
        border-color: #fecaca;
    }

    .sa-photo-box {
        padding: 14px;
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 14px;
    }

    .sa-photo-preview {
        width: 82px;
        height: 82px;
        display: none;
        object-fit: cover;
        border-radius: 12px;
    }

    .sa-message {
        display: none;
        padding: 14px 16px;
        border-radius: 13px;
        font-size: 14px;
    }

    .sa-message.success {
        color: #065f46;
        background: #d1fae5;
        border: 1px solid #a7f3d0;
    }

    .sa-message.error {
        color: #991b1b;
        background: #fee2e2;
        border: 1px solid #fecaca;
    }

    .sa-message.info {
        color: #1e40af;
        background: #dbeafe;
        border: 1px solid #bfdbfe;
    }

    .sa-stat {
        padding: 16px;
        background: #f8fafc;
        border-radius: 13px;
    }

    .sa-table th {
        padding: 13px 14px;
        color: var(--sa-muted);
        background: #f8fafc;
        border-bottom-width: 1px;
        font-size: 12px;
        white-space: nowrap;
    }

    .sa-table td {
        padding: 14px;
        vertical-align: middle;
        white-space: nowrap;
    }

    .sa-badge {
        padding: 6px 10px;
        display: inline-block;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
    }

    .sa-badge.success { color: #047857; background: #d1fae5; }
    .sa-badge.warning { color: #b45309; background: #fef3c7; }
    .sa-badge.danger { color: #b91c1c; background: #fee2e2; }
    .sa-badge.neutral { color: #475569; background: #e2e8f0; }

    @media (max-width: 767px) {
        .sa-hero,
        .sa-actions {
            padding: 20px;
        }

        .sa-punch-button {
            width: 100%;
        }
    }
</style>

<div class="self-attendance">
    <div id="pageMessage" class="sa-message mb-3"></div>

    <div class="row g-4">
        <div class="col-xl-7">
            <div class="sa-hero h-100">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div>
                        <div class="small text-white-50 mb-2">الوقت الحالي</div>
                        <div class="sa-clock" id="liveClock">--:--:--</div>
                        <div class="sa-date mt-2" id="liveDate">جاري تحميل التاريخ...</div>
                    </div>

                    <div class="sa-status-chip">
                        <i class="bi bi-circle-fill small"></i>
                        <span id="todayStatus">جاري التحقق...</span>
                    </div>
                </div>

                <div class="row g-3 mt-4">
                    <div class="col-6 col-md-3">
                        <div class="small text-white-50">الحضور</div>
                        <div class="fw-bold mt-1" id="todayCheckIn">--:--</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="small text-white-50">الانصراف</div>
                        <div class="fw-bold mt-1" id="todayCheckOut">--:--</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="small text-white-50">ساعات العمل</div>
                        <div class="fw-bold mt-1" id="todayWork">00:00</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="small text-white-50">التأخير</div>
                        <div class="fw-bold mt-1" id="todayLate">0 دقيقة</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="sa-card sa-actions h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="sa-section-title">إجراء البصمة</div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnRefreshToday">
                        <i class="bi bi-arrow-clockwise"></i>
                        تحديث
                    </button>
                </div>

                <div id="locationState" class="sa-location-state mb-3">
                    <i class="bi bi-geo-alt"></i>
                    <span>لم يتم تحديد موقعك بعد.</span>
                </div>

                <button type="button" class="btn btn-outline-primary w-100 mb-3" id="btnGetLocation">
                    <i class="bi bi-crosshair ms-1"></i>
                    تحديد موقعي الآن
                </button>

                <div class="sa-photo-box mb-3" id="photoBox">
                    <div class="d-flex align-items-center gap-3">
                        <img src="" alt="معاينة الصورة" id="photoPreview" class="sa-photo-preview" />
                        <div class="flex-grow-1">
                            <label for="attendancePhoto" class="form-label fw-semibold mb-1">
                                صورة إثبات الحضور
                                <span class="text-danger" id="photoRequiredMark">*</span>
                            </label>
                            <input
                                type="file"
                                class="form-control"
                                id="attendancePhoto"
                                accept="image/jpeg,image/png,image/webp"
                                capture="user"
                            />
                            <div class="text-muted small mt-1">الحد الأعلى 5 MB.</div>
                        </div>
                    </div>
                </div>

                <div class="row g-2">
                    <div class="col-md-6">
                        <button type="button" class="btn btn-success sa-punch-button w-100" id="btnCheckIn" disabled>
                            <i class="bi bi-box-arrow-in-left ms-1"></i>
                            تسجيل الحضور
                        </button>
                    </div>
                    <div class="col-md-6">
                        <button type="button" class="btn btn-danger sa-punch-button w-100" id="btnCheckOut" disabled>
                            <i class="bi bi-box-arrow-right ms-1"></i>
                            تسجيل الانصراف
                        </button>
                    </div>
                </div>

                <div class="text-muted small mt-3">
                    <i class="bi bi-shield-check ms-1"></i>
                    يتم التحقق من الوقت والموقع وسياسة الشركة داخل الخادم.
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-lg-6">
            <div class="sa-card p-4 h-100">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <span class="sa-info-icon"><i class="bi bi-clock-history"></i></span>
                    <div>
                        <div class="sa-section-title">وردية اليوم</div>
                        <div class="text-muted small" id="shiftCode">لم يتم التحميل</div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-6">
                        <div class="sa-stat">
                            <div class="sa-label">اسم الوردية</div>
                            <div class="sa-value" id="shiftName">-</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="sa-stat">
                            <div class="sa-label">وقت الدوام</div>
                            <div class="sa-value" id="shiftTime" dir="ltr">--:-- - --:--</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="sa-card p-4 h-100">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <span class="sa-info-icon"><i class="bi bi-geo-alt"></i></span>
                    <div class="flex-grow-1">
                        <div class="sa-section-title">موقع العمل</div>
                        <div class="text-muted small" id="locationCode">لم يتم التحميل</div>
                    </div>
                    <a href="#" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary" id="locationMapLink" style="display:none">
                        عرض الخريطة
                    </a>
                </div>

                <div class="row g-3">
                    <div class="col-7">
                        <div class="sa-stat">
                            <div class="sa-label">الموقع</div>
                            <div class="sa-value" id="locationName">-</div>
                        </div>
                    </div>
                    <div class="col-5">
                        <div class="sa-stat">
                            <div class="sa-label">النطاق المسموح</div>
                            <div class="sa-value" id="locationRadius">-</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="sa-card mt-4 overflow-hidden">
        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap p-4 border-bottom">
            <div>
                <div class="sa-section-title">سجل حضوري</div>
                <div class="text-muted small mt-1">آخر عمليات الحضور والانصراف الخاصة بك.</div>
            </div>
            <span class="badge bg-primary-subtle text-primary" id="historyCount">0 سجل</span>
        </div>

        <div class="table-responsive">
            <table class="table sa-table mb-0">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>الوردية</th>
                        <th>الحضور</th>
                        <th>الانصراف</th>
                        <th>الحالة</th>
                        <th>العمل</th>
                        <th>التأخير</th>
                        <th>الاعتماد</th>
                    </tr>
                </thead>
                <tbody id="historyBody">
                    <tr><td colspan="8" class="text-center text-muted py-5">جاري تحميل السجل...</td></tr>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center gap-3 p-3 border-top">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="historyPrevious" disabled>السابق</button>
            <span class="text-muted small" id="historyPage">صفحة 1 من 1</span>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="historyNext" disabled>التالي</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
jQuery(function ($) {
    'use strict';

    const urls = {
        today: @json(route('app.attendance.self-service.today')),
        history: @json(route('app.attendance.self-service.history')),
        checkIn: @json(route('app.attendance.self-service.check-in')),
        checkOut: @json(route('app.attendance.self-service.check-out'))
    };

    let todayData = null;
    let currentPosition = null;
    let historyPage = 1;
    let historyLastPage = 1;
    let submitting = false;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        }
    });

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function pad(value) {
        return String(value).padStart(2, '0');
    }

    function updateClock() {
        const now = new Date();
        $('#liveClock').text(
            pad(now.getHours()) + ':' +
            pad(now.getMinutes()) + ':' +
            pad(now.getSeconds())
        );

        $('#liveDate').text(
            now.toLocaleDateString('ar-SA', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            })
        );
    }

    function showMessage(type, message) {
        $('#pageMessage')
            .removeClass('success error info')
            .addClass(type)
            .text(message)
            .stop(true, true)
            .show();

        $('html, body').stop(true).animate({ scrollTop: 0 }, 250);
    }

    function hideMessage() {
        $('#pageMessage').hide().text('');
    }

    function responseError(xhr) {
        if (xhr.responseJSON && xhr.responseJSON.message) {
            return xhr.responseJSON.message;
        }

        if (xhr.responseJSON && xhr.responseJSON.errors) {
            const messages = [];

            $.each(xhr.responseJSON.errors, function (_, errors) {
                $.each(errors, function (_, message) {
                    messages.push(message);
                });
            });

            if (messages.length) {
                return messages.join(' ');
            }
        }

        return 'تعذر تنفيذ العملية. حاول مرة أخرى.';
    }

    function formatTime(value, timezone) {
        if (!value) {
            return '--:--';
        }

        try {
            return new Date(value).toLocaleTimeString('ar-SA', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false,
                timeZone: timezone || undefined
            });
        } catch (_) {
            return '--:--';
        }
    }

    function statusClass(status) {
        if (status === 'present') return 'success';
        if (status === 'late') return 'warning';
        if (status === 'absent') return 'danger';
        return 'neutral';
    }

    function renderToday(data) {
        todayData = data;
        const record = data.record;
        const shift = data.shift;
        const location = data.work_location;
        const policy = data.policy || {};

        $('#todayCheckIn').text(formatTime(record && record.check_in_at, data.timezone));
        $('#todayCheckOut').text(formatTime(record && record.check_out_at, data.timezone));
        $('#todayWork').text(record ? record.work_duration_label : '00:00');
        $('#todayLate').text((record ? record.late_minutes : 0) + ' دقيقة');

        if (record) {
            $('#todayStatus').text(record.status_label);
        } else if (!data.web_allowed) {
            $('#todayStatus').text('التسجيل عبر الويب غير مسموح');
        } else if (!data.is_work_day) {
            $('#todayStatus').text('ليس يوم عمل');
        } else if (!data.inside_check_in_window) {
            $('#todayStatus').text('خارج وقت البصمة');
        } else {
            $('#todayStatus').text('لم تسجل الحضور');
        }

        $('#btnCheckIn').prop('disabled', !data.can_check_in || submitting);
        $('#btnCheckOut').prop('disabled', !data.can_check_out || submitting);

        if (shift) {
            $('#shiftName').text(shift.name);
            $('#shiftCode').text(shift.code);
            $('#shiftTime').text(shift.start_time + ' - ' + shift.end_time);
        } else {
            $('#shiftName').text('لا توجد وردية');
            $('#shiftCode').text('-');
            $('#shiftTime').text('--:-- - --:--');
        }

        if (location) {
            $('#locationName').text(location.name);
            $('#locationCode').text(location.code || '-');
            $('#locationRadius').text(location.attendance_radius + ' متر');

            if (location.latitude != null && location.longitude != null) {
                $('#locationMapLink')
                    .attr(
                        'href',
                        'https://www.google.com/maps?q=' +
                        encodeURIComponent(location.latitude + ',' + location.longitude)
                    )
                    .show();
            } else {
                $('#locationMapLink').hide();
            }
        } else {
            $('#locationName').text('غير محدد');
            $('#locationCode').text('-');
            $('#locationRadius').text('-');
            $('#locationMapLink').hide();
        }

        if (policy.require_photo) {
            $('#photoRequiredMark').show();
            $('#photoBox').show();
        } else {
            $('#photoRequiredMark').hide();
            $('#photoBox').show();
        }

        if (policy.require_geofence && !currentPosition) {
            setLocationState('default', 'يجب تحديد موقعك لإتمام البصمة.');
        }
    }

    function loadToday(showLoading) {
        if (showLoading !== false) {
            $('#btnRefreshToday').prop('disabled', true);
        }

        $.ajax({
            url: urls.today,
            type: 'GET',
            dataType: 'json'
        }).done(function (response) {
            renderToday(response.data);
        }).fail(function (xhr) {
            showMessage('error', responseError(xhr));
            $('#btnCheckIn, #btnCheckOut').prop('disabled', true);
        }).always(function () {
            $('#btnRefreshToday').prop('disabled', false);
        });
    }

    function setLocationState(type, message) {
        const box = $('#locationState');
        box.removeClass('ready error');

        if (type === 'ready') box.addClass('ready');
        if (type === 'error') box.addClass('error');

        box.find('span').text(message);
    }

    function acquireLocation(callback) {
        if (!navigator.geolocation) {
            setLocationState('error', 'المتصفح لا يدعم تحديد الموقع الجغرافي.');
            showMessage('error', 'المتصفح لا يدعم تحديد الموقع الجغرافي.');
            return;
        }

        $('#btnGetLocation').prop('disabled', true).text('جاري تحديد الموقع...');
        setLocationState('default', 'جاري قراءة موقعك بدقة عالية...');

        function finishLocationButton() {
            $('#btnGetLocation')
                .prop('disabled', false)
                .html('<i class="bi bi-crosshair ms-1"></i> تحديد موقعي الآن');
        }

        navigator.geolocation.getCurrentPosition(
            function (position) {
                currentPosition = {
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy
                };

                setLocationState(
                    'ready',
                    'تم تحديد الموقع — دقة تقريبية ' +
                    Math.round(position.coords.accuracy) + ' متر.'
                );

                finishLocationButton();

                if (typeof callback === 'function') {
                    callback();
                }
            },
            function (error) {
                let message = 'تعذر تحديد الموقع.';

                if (error.code === 1) message = 'تم رفض إذن الموقع من المتصفح.';
                if (error.code === 2) message = 'الموقع غير متاح حاليًا.';
                if (error.code === 3) message = 'انتهت مهلة تحديد الموقع.';

                setLocationState('error', message);
                showMessage('error', message + ' تأكد من تفعيل GPS واستخدام HTTPS أو localhost.');
                finishLocationButton();
            },
            {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0
            }
        );
    }

    function selectedPhoto() {
        const input = $('#attendancePhoto')[0];
        return input && input.files && input.files.length
            ? input.files[0]
            : null;
    }

    function validateBeforePunch(action) {
        if (!todayData) {
            showMessage('error', 'بيانات الدوام غير جاهزة بعد.');
            return false;
        }

        if (action === 'check-in' && !todayData.can_check_in) {
            showMessage('error', 'تسجيل الحضور غير متاح في الوقت الحالي.');
            return false;
        }

        if (action === 'check-out' && !todayData.can_check_out) {
            showMessage('error', 'تسجيل الانصراف غير متاح في الوقت الحالي.');
            return false;
        }

        if (todayData.policy.require_photo && !selectedPhoto()) {
            showMessage('error', 'يجب التقاط صورة إثبات قبل تنفيذ البصمة.');
            return false;
        }

        return true;
    }

    function postPunch(action) {
        if (!validateBeforePunch(action) || submitting) {
            return;
        }

        if (todayData.policy.require_geofence && !currentPosition) {
            acquireLocation(function () {
                postPunch(action);
            });
            return;
        }

        const formData = new FormData();
        const photo = selectedPhoto();

        if (currentPosition) {
            formData.append('latitude', currentPosition.latitude);
            formData.append('longitude', currentPosition.longitude);
            formData.append('accuracy', currentPosition.accuracy);
        }

        if (photo) {
            formData.append('photo', photo);
        }

        submitting = true;
        hideMessage();
        $('#btnCheckIn, #btnCheckOut').prop('disabled', true);

        $.ajax({
            url: action === 'check-in' ? urls.checkIn : urls.checkOut,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json'
        }).done(function (response) {
            showMessage('success', response.message);
            $('#attendancePhoto').val('');
            $('#photoPreview').attr('src', '').hide();
            loadToday(false);
            loadHistory(1);
        }).fail(function (xhr) {
            showMessage('error', responseError(xhr));
        }).always(function () {
            submitting = false;
            loadToday(false);
        });
    }

    function historyRow(record) {
        return '<tr>' +
            '<td>' + escapeHtml(record.attendance_date) + '</td>' +
            '<td>' + escapeHtml(record.shift ? record.shift.name : '-') + '</td>' +
            '<td>' + escapeHtml(formatTime(record.check_in_at, record.timezone)) + '</td>' +
            '<td>' + escapeHtml(formatTime(record.check_out_at, record.timezone)) + '</td>' +
            '<td><span class="sa-badge ' + statusClass(record.status) + '">' +
                escapeHtml(record.status_label) + '</span></td>' +
            '<td dir="ltr">' + escapeHtml(record.work_duration_label) + '</td>' +
            '<td>' + escapeHtml(record.late_minutes) + ' دقيقة</td>' +
            '<td>' + escapeHtml(record.approval_status_label) + '</td>' +
        '</tr>';
    }

    function loadHistory(page) {
        $('#historyBody').html(
            '<tr><td colspan="8" class="text-center text-muted py-5">جاري تحميل السجل...</td></tr>'
        );

        $.ajax({
            url: urls.history,
            type: 'GET',
            dataType: 'json',
            data: { page: page, per_page: 10 }
        }).done(function (response) {
            const rows = response.data || [];
            const meta = response.meta || {};
            historyPage = meta.current_page || 1;
            historyLastPage = meta.last_page || 1;

            if (!rows.length) {
                $('#historyBody').html(
                    '<tr><td colspan="8" class="text-center text-muted py-5">لا توجد سجلات حضور بعد.</td></tr>'
                );
            } else {
                $('#historyBody').html(
                    $.map(rows, historyRow).join('')
                );
            }

            $('#historyCount').text((meta.total || 0) + ' سجل');
            $('#historyPage').text('صفحة ' + historyPage + ' من ' + historyLastPage);
            $('#historyPrevious').prop('disabled', historyPage <= 1);
            $('#historyNext').prop('disabled', historyPage >= historyLastPage);
        }).fail(function (xhr) {
            $('#historyBody').html(
                '<tr><td colspan="8" class="text-center text-danger py-5">' +
                escapeHtml(responseError(xhr)) + '</td></tr>'
            );
        });
    }

    $('#btnGetLocation').on('click', function () {
        acquireLocation();
    });

    $('#btnRefreshToday').on('click', function () {
        hideMessage();
        loadToday();
        loadHistory(historyPage);
    });

    $('#btnCheckIn').on('click', function () {
        postPunch('check-in');
    });

    $('#btnCheckOut').on('click', function () {
        postPunch('check-out');
    });

    $('#attendancePhoto').on('change', function () {
        const file = selectedPhoto();

        if (!file) {
            $('#photoPreview').attr('src', '').hide();
            return;
        }

        const reader = new FileReader();
        reader.onload = function (event) {
            $('#photoPreview').attr('src', event.target.result).show();
        };
        reader.readAsDataURL(file);
    });

    $('#historyPrevious').on('click', function () {
        if (historyPage > 1) loadHistory(historyPage - 1);
    });

    $('#historyNext').on('click', function () {
        if (historyPage < historyLastPage) loadHistory(historyPage + 1);
    });

    updateClock();
    setInterval(updateClock, 1000);
    loadToday();
    loadHistory(1);
});
</script>
@endpush
