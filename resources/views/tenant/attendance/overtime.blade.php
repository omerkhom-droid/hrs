@extends('layouts.tenant')

@section('title', 'العمل الإضافي')
@section('page-title', 'العمل الإضافي')

@section('content')
    @include('tenant.attendance._styles')

    <style>
        .overtime-page .ot-reason {
            max-width: 260px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .overtime-page .ot-time-arrow {
            color: #94a3b8;
            font-weight: 700;
        }

        .overtime-page .ot-note {
            padding: 14px;
            border: 1px solid #dbeafe;
            border-radius: 12px;
            color: #1e40af;
            background: #eff6ff;
            font-size: 13px;
        }

        .overtime-page .ot-employee-picker {
            border: 1px solid #dbe3ef;
            border-radius: 14px;
            background: #f8fafc;
            overflow: hidden;
        }

        .overtime-page .ot-employee-picker-toolbar {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            background: #fff;
        }

        .overtime-page .ot-employee-list {
            max-height: 250px;
            overflow-y: auto;
            overscroll-behavior: contain;
            padding: 8px;
        }

        .overtime-page .ot-employee-option {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 10px;
            margin: 0;
            border-radius: 10px;
            cursor: pointer;
        }

        .overtime-page .ot-employee-option:hover {
            background: #eef4ff;
        }

        .overtime-page .ot-employee-option input {
            margin-top: 4px;
        }

        .overtime-page .ot-selected-count {
            min-width: 115px;
            text-align: center;
        }
    </style>

    <div class="attendance-page overtime-page">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-4">
            <div>
                <h4 class="mb-1">طلبات العمل الإضافي</h4>
                <p class="text-muted mb-0">
                    الطلب لا ينشئ وردية جديدة؛ تتم مقارنة المدة المطلوبة بالعمل الفعلي بعد الانصراف.
                </p>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                @can('attendance.view')
                    <a href="{{ route('app.attendance.index') }}" class="btn btn-outline-primary">
                        سجلات الدوام
                    </a>
                @endcan

                @if(
                    auth()->user()->can('self_service.attendance') ||
                    auth()->user()->can('attendance.manage')
                )
                    <button type="button" class="btn btn-primary" id="btnCreateOvertime">
                        + طلب عمل إضافي
                    </button>
                @endif
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-2">
                <div class="att-card att-summary d-flex justify-content-between align-items-center">
                    <div>
                        <div class="att-summary-label">إجمالي الطلبات</div>
                        <div class="att-summary-value" id="sumTotal">0</div>
                    </div>
                    <span class="att-summary-icon att-info">#</span>
                </div>
            </div>

            <div class="col-6 col-xl-2">
                <div class="att-card att-summary d-flex justify-content-between align-items-center">
                    <div>
                        <div class="att-summary-label">بانتظار الاعتماد</div>
                        <div class="att-summary-value text-warning" id="sumPending">0</div>
                    </div>
                    <span class="att-summary-icon att-warning">!</span>
                </div>
            </div>

            <div class="col-6 col-xl-2">
                <div class="att-card att-summary d-flex justify-content-between align-items-center">
                    <div>
                        <div class="att-summary-label">معتمد مسبقًا</div>
                        <div class="att-summary-value text-primary" id="sumApproved">0</div>
                    </div>
                    <span class="att-summary-icon att-info">✓</span>
                </div>
            </div>

            <div class="col-6 col-xl-2">
                <div class="att-card att-summary d-flex justify-content-between align-items-center">
                    <div>
                        <div class="att-summary-label">مكتمل</div>
                        <div class="att-summary-value text-success" id="sumCompleted">0</div>
                    </div>
                    <span class="att-summary-icon att-success">✓</span>
                </div>
            </div>

            <div class="col-6 col-xl-2">
                <div class="att-card att-summary d-flex justify-content-between align-items-center">
                    <div>
                        <div class="att-summary-label">الفعلي</div>
                        <div class="att-summary-value" id="sumActual">00:00</div>
                    </div>
                    <span class="att-summary-icon att-neutral">◷</span>
                </div>
            </div>

            <div class="col-6 col-xl-2">
                <div class="att-card att-summary d-flex justify-content-between align-items-center">
                    <div>
                        <div class="att-summary-label">المعتمد</div>
                        <div class="att-summary-value text-success" id="sumApprovedMinutes">00:00</div>
                    </div>
                    <span class="att-summary-icon att-success">✓</span>
                </div>
            </div>
        </div>

        <div class="att-card p-3 p-lg-4 mb-4">
            <form id="overtimeFilterForm">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-3">
                        <label class="filter-label" for="filterSearch">البحث</label>
                        <input
                            type="search"
                            class="form-control"
                            id="filterSearch"
                            placeholder="اسم أو رقم الموظف أو سبب الطلب"
                        />
                    </div>

                    <div class="col-md-6 col-lg-2">
                        <label class="filter-label" for="filterDateFrom">من تاريخ</label>
                        <input type="date" class="form-control" id="filterDateFrom" />
                    </div>

                    <div class="col-md-6 col-lg-2">
                        <label class="filter-label" for="filterDateTo">إلى تاريخ</label>
                        <input type="date" class="form-control" id="filterDateTo" />
                    </div>

                    @if($canViewAll)
                        <div class="col-md-6 col-lg-2">
                            <label class="filter-label" for="filterEmployee">الموظف</label>
                            <select class="form-select" id="filterEmployee">
                                <option value="">كل الموظفين</option>
                            </select>
                        </div>
                    @endif

                    <div class="col-md-6 col-lg-2">
                        <label class="filter-label" for="filterStatus">الحالة</label>
                        <select class="form-select" id="filterStatus">
                            <option value="">كل الحالات</option>
                            <option value="pending">بانتظار الاعتماد</option>
                            <option value="approved">معتمد</option>
                            <option value="completed">مكتمل</option>
                            <option value="rejected">مرفوض</option>
                            <option value="cancelled">ملغي</option>
                        </select>
                    </div>

                    <div class="col-md-6 col-lg-2">
                        <label class="filter-label" for="filterPerPage">عدد السجلات</label>
                        <select class="form-select" id="filterPerPage">
                            <option value="15">15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>

                    <div class="col-lg-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">بحث</button>
                        <button type="button" class="btn btn-outline-secondary" id="btnResetFilters">
                            إعادة
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="att-card overflow-hidden">
            <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                <h6 class="mb-0">سجل طلبات العمل الإضافي</h6>
                <span class="badge bg-primary-subtle text-primary" id="overtimeCount">0 طلب</span>
            </div>

            @if($canApprove)
                <div class="d-none align-items-center justify-content-between gap-3 flex-wrap p-3 border-bottom bg-light" id="overtimeBulkBar">
                    <div class="fw-bold text-primary" id="bulkSelectedCount">تم تحديد 0 طلب</div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-success" id="btnBulkApprove">
                            اعتماد المحدد
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" id="btnBulkReject">
                            رفض المحدد
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnClearBulkSelection">
                            إلغاء التحديد
                        </button>
                    </div>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            @if($canApprove)
                                <th style="width: 42px;">
                                    <input type="checkbox" class="form-check-input" id="selectAllPendingOvertime" title="تحديد الطلبات الظاهرة" />
                                </th>
                            @endif
                            <th>#</th>
                            <th>الموظف</th>
                            <th>التاريخ والنوع</th>
                            <th>الوقت المخطط</th>
                            <th>المطلوب</th>
                            <th>الفعلي</th>
                            <th>المعتمد</th>
                            <th>الحالة</th>
                            <th class="text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="overtimeTableBody">
                        <tr>
                            <td colspan="{{ $canApprove ? 10 : 9 }}" class="att-loading">جاري تحميل البيانات...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap p-3 border-top">
                <div class="small text-muted" id="overtimeInfo">—</div>
                <ul class="pagination pagination-sm mb-0" id="overtimePagination"></ul>
            </div>
        </div>

        <div class="att-modal-overlay" id="overtimeFormModal" aria-hidden="true">
            <div class="att-modal-dialog" role="dialog" aria-modal="true">
                <div class="att-modal-header">
                    <div>
                        <h5 class="mb-1">طلب عمل إضافي</h5>
                        <div class="small text-muted">حدد الفترة المخططة وسبب التكليف.</div>
                    </div>
                    <button type="button" class="att-modal-close js-close-modal">×</button>
                </div>

                <form id="overtimeForm">
                    <div class="att-modal-body">
                        <div class="alert alert-danger d-none" id="overtimeFormErrors"></div>

                        <div class="ot-note mb-3">
                            يتم اعتماد الحد الأقل بين الدقائق المعتمدة والدقائق الفعلية المحسوبة بعد تسجيل الانصراف.
                        </div>

                        <div class="row g-3">
                            @if($canManage)
                                <div class="col-12">
                                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                        <label class="form-label mb-0">
                                            الموظفون <span class="text-danger">*</span>
                                        </label>
                                        <span class="badge bg-primary-subtle text-primary ot-selected-count" id="selectedEmployeesCount">
                                            تم اختيار 0
                                        </span>
                                    </div>

                                    <div class="ot-employee-picker">
                                        <div class="ot-employee-picker-toolbar">
                                            <div class="row g-2">
                                                <div class="col-md-4">
                                                    <input
                                                        type="search"
                                                        class="form-control"
                                                        id="employeePickerSearch"
                                                        placeholder="ابحث بالاسم أو الرقم"
                                                    />
                                                </div>
                                                <div class="col-md-3">
                                                    <select class="form-select" id="employeePickerBranch">
                                                        <option value="">كل الفروع</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <select class="form-select" id="employeePickerDepartment">
                                                        <option value="">كل الإدارات</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2 d-flex gap-1">
                                                    <button type="button" class="btn btn-outline-primary flex-grow-1" id="btnSelectVisibleEmployees">
                                                        تحديد الظاهر
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="d-flex justify-content-between align-items-center gap-2 mt-2">
                                                <small class="text-muted" id="visibleEmployeesCount">0 موظف</small>
                                                <button type="button" class="btn btn-sm btn-link text-danger p-0" id="btnClearEmployees">
                                                    إلغاء التحديد
                                                </button>
                                            </div>
                                        </div>

                                        <div class="ot-employee-list" id="overtimeEmployeeList">
                                            <div class="text-muted text-center py-4">جاري تحميل الموظفين...</div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="col-12">
                                <label class="form-label" for="overtimeDate">
                                    التاريخ <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" id="overtimeDate" name="overtime_date" required />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="overtimeStartTime">
                                    وقت البداية <span class="text-danger">*</span>
                                </label>
                                <input type="time" class="form-control" id="overtimeStartTime" name="start_time" required />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="overtimeEndTime">
                                    وقت النهاية <span class="text-danger">*</span>
                                </label>
                                <input type="time" class="form-control" id="overtimeEndTime" name="end_time" required />
                                <div class="text-muted small mt-1">إذا كان أقل من البداية فسيُحسب في اليوم التالي.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="overtimeType">
                                    النوع <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="overtimeType" name="type" required>
                                    <option value="regular_day">بعد الدوام</option>
                                    <option value="rest_day">يوم راحة</option>
                                    <option value="holiday">عطلة رسمية</option>
                                    <option value="emergency">عمل طارئ</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">المدة المتوقعة</label>
                                <div class="form-control bg-light fw-bold" id="plannedDuration">00:00</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="overtimeReason">
                                    سبب العمل الإضافي <span class="text-danger">*</span>
                                </label>
                                <textarea
                                    class="form-control"
                                    id="overtimeReason"
                                    name="reason"
                                    maxlength="2000"
                                    required
                                    placeholder="اذكر المهمة أو سبب الحاجة للعمل بعد الدوام"
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="att-modal-footer">
                        <button type="button" class="btn btn-light js-close-modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary px-4" id="btnSaveOvertime">
                            إرسال للاعتماد
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="att-modal-overlay" id="overtimeDetailsModal" aria-hidden="true">
            <div class="att-modal-dialog" role="dialog" aria-modal="true">
                <div class="att-modal-header">
                    <div>
                        <h5 class="mb-1">تفاصيل العمل الإضافي</h5>
                        <div class="small text-muted">الطلب والتنفيذ والقرار.</div>
                    </div>
                    <button type="button" class="att-modal-close js-close-modal">×</button>
                </div>

                <div class="att-modal-body" id="overtimeDetailsBody">
                    <div class="att-loading">جاري تحميل التفاصيل...</div>
                </div>

                <div class="att-modal-footer">
                    <button type="button" class="btn btn-light js-close-modal">إغلاق</button>
                </div>
            </div>
        </div>

        <div class="att-modal-overlay" id="overtimeApproveModal" aria-hidden="true">
            <div class="att-modal-dialog att-modal-sm" role="dialog" aria-modal="true">
                <div class="att-modal-header">
                    <h5 class="mb-0">اعتماد العمل الإضافي</h5>
                    <button type="button" class="att-modal-close js-close-modal">×</button>
                </div>

                <form id="overtimeApproveForm">
                    <div class="att-modal-body">
                        <input type="hidden" id="approveOvertimeId" />
                        <div class="alert alert-danger d-none" id="approveFormErrors"></div>
                        <div class="mb-3 fw-bold" id="approveEmployeeName"></div>

                        <div class="mb-3">
                            <label class="form-label" for="approvedMinutes">
                                الدقائق المعتمدة <span class="text-danger">*</span>
                            </label>
                            <input
                                type="number"
                                min="1"
                                max="1440"
                                class="form-control"
                                id="approvedMinutes"
                                name="approved_minutes"
                                required
                            />
                            <div class="text-muted small mt-1" id="approveMinutesHelp"></div>
                        </div>

                        <div>
                            <label class="form-label" for="approveNotes">ملاحظات الاعتماد</label>
                            <textarea class="form-control" id="approveNotes" name="decision_notes" maxlength="2000"></textarea>
                        </div>
                    </div>

                    <div class="att-modal-footer">
                        <button type="button" class="btn btn-light js-close-modal">إلغاء</button>
                        <button type="submit" class="btn btn-success" id="btnConfirmApprove">اعتماد</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="att-modal-overlay" id="overtimeRejectModal" aria-hidden="true">
            <div class="att-modal-dialog att-modal-sm" role="dialog" aria-modal="true">
                <div class="att-modal-header">
                    <h5 class="mb-0">رفض طلب العمل الإضافي</h5>
                    <button type="button" class="att-modal-close js-close-modal">×</button>
                </div>

                <form id="overtimeRejectForm">
                    <div class="att-modal-body">
                        <input type="hidden" id="rejectOvertimeId" />
                        <div class="alert alert-danger d-none" id="rejectFormErrors"></div>
                        <div class="mb-3 fw-bold" id="rejectEmployeeName"></div>

                        <label class="form-label" for="rejectNotes">
                            سبب الرفض <span class="text-danger">*</span>
                        </label>
                        <textarea
                            class="form-control"
                            id="rejectNotes"
                            name="decision_notes"
                            maxlength="2000"
                            required
                        ></textarea>
                    </div>

                    <div class="att-modal-footer">
                        <button type="button" class="btn btn-light js-close-modal">إلغاء</button>
                        <button type="submit" class="btn btn-danger" id="btnConfirmReject">رفض الطلب</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="att-modal-overlay" id="overtimeCancelModal" aria-hidden="true">
            <div class="att-modal-dialog att-modal-sm" role="dialog" aria-modal="true">
                <div class="att-modal-header">
                    <h5 class="mb-0">إلغاء الطلب</h5>
                    <button type="button" class="att-modal-close js-close-modal">×</button>
                </div>
                <div class="att-modal-body">
                    <input type="hidden" id="cancelOvertimeId" />
                    <p class="mb-0">هل تريد إلغاء طلب العمل الإضافي المحدد؟</p>
                </div>
                <div class="att-modal-footer">
                    <button type="button" class="btn btn-light js-close-modal">تراجع</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmCancel">إلغاء الطلب</button>
                </div>
            </div>
        </div>

        <div class="att-toast" id="overtimeToast"></div>
    </div>
@endsection

@push('scripts')
    <script>
        jQuery(function ($) {
            'use strict';

            const urls = {
                data: @json(route('app.attendance.overtime.data')),
                options: @json(route('app.attendance.overtime.options')),
                store: @json(route('app.attendance.overtime.store')),
                bulkDecision: @json(route('app.attendance.overtime.bulk-decision')),
                show: @json(route('app.attendance.overtime.show', ['overtimeRequest' => '__ID__'])),
                approve: @json(route('app.attendance.overtime.approve', ['overtimeRequest' => '__ID__'])),
                reject: @json(route('app.attendance.overtime.reject', ['overtimeRequest' => '__ID__'])),
                cancel: @json(route('app.attendance.overtime.cancel', ['overtimeRequest' => '__ID__']))
            };

            const permissions = {
                viewAll: @json($canViewAll),
                manage: @json($canManage),
                approve: @json($canApprove)
            };

            const state = {
                page: 1,
                today: '',
                optionsLoaded: false,
                employees: [],
                visibleEmployeeIds: [],
                selectedEmployees: {},
                selectedRequests: {},
                decisionMode: 'single'
            };

            const tableColumns = permissions.approve ? 10 : 9;

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json'
                }
            });

            function routeUrl(template, id) {
                return template.replace('__ID__', String(id));
            }

            function escapeHtml(value) {
                return $('<div>').text(value == null ? '' : String(value)).html();
            }

            function valueOrDash(value) {
                const text = value == null ? '' : String(value).trim();
                return text === '' ? '—' : escapeHtml(text);
            }

            function dateText(value) {
                if (!value) return '—';
                const parts = String(value).substring(0, 10).split('-');
                return parts.length === 3
                    ? parts[2] + '/' + parts[1] + '/' + parts[0]
                    : escapeHtml(value);
            }

            function timeText(value) {
                return value ? escapeHtml(String(value).substring(11, 16)) : '—';
            }

            function durationText(minutes) {
                const total = Math.max(0, Number(minutes || 0));
                return String(Math.floor(total / 60)).padStart(2, '0') + ':' +
                    String(total % 60).padStart(2, '0');
            }

            function showModal(selector) {
                $(selector).attr('aria-hidden', 'false').show();
                $('body').addClass('att-modal-open');
            }

            function hideModal(selector) {
                $(selector).attr('aria-hidden', 'true').hide();

                if (!$('.att-modal-overlay:visible').length) {
                    $('body').removeClass('att-modal-open');
                }
            }

            function responseMessage(xhr) {
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    return xhr.responseJSON.message;
                }

                return 'تعذر تنفيذ العملية. حاول مرة أخرى.';
            }

            function errorsHtml(xhr) {
                const errors = xhr.responseJSON && xhr.responseJSON.errors
                    ? xhr.responseJSON.errors
                    : null;

                if (!errors) {
                    return escapeHtml(responseMessage(xhr));
                }

                let html = '<ul class="mb-0">';

                $.each(errors, function (_, messages) {
                    $.each(messages, function (_, message) {
                        html += '<li>' + escapeHtml(message) + '</li>';
                    });
                });

                return html + '</ul>';
            }

            function toast(message, type) {
                $('#overtimeToast')
                    .stop(true, true)
                    .removeClass('success error')
                    .addClass(type === 'error' ? 'error' : 'success')
                    .text(message)
                    .fadeIn(180)
                    .delay(2600)
                    .fadeOut(220);
            }

            function statusBadge(item) {
                const classes = {
                    pending: 'att-warning',
                    approved: 'att-info',
                    completed: 'att-success',
                    rejected: 'att-danger',
                    cancelled: 'att-neutral'
                };

                return '<span class="att-badge ' +
                    (classes[item.status] || 'att-neutral') + '">' +
                    escapeHtml(item.status_label) + '</span>';
            }

            function actionButtons(item) {
                let html = '<div class="d-flex justify-content-center gap-1 flex-wrap">' +
                    '<button type="button" class="btn btn-sm btn-outline-secondary js-ot-details" data-id="' +
                    item.id + '">عرض</button>';

                if (permissions.approve && item.can_approve) {
                    html += '<button type="button" class="btn btn-sm btn-outline-success js-ot-approve" ' +
                        'data-id="' + item.id + '" data-name="' +
                        escapeHtml(item.employee ? item.employee.name : '') + '" data-minutes="' +
                        item.requested_minutes + '">اعتماد</button>';

                    html += '<button type="button" class="btn btn-sm btn-outline-danger js-ot-reject" ' +
                        'data-id="' + item.id + '" data-name="' +
                        escapeHtml(item.employee ? item.employee.name : '') + '">رفض</button>';
                }

                if (item.can_cancel) {
                    html += '<button type="button" class="btn btn-sm btn-outline-danger js-ot-cancel" data-id="' +
                        item.id + '">إلغاء</button>';
                }

                return html + '</div>';
            }

            function updateBulkSelectionUi() {
                if (!permissions.approve) return;

                const count = Object.keys(state.selectedRequests).length;
                $('#bulkSelectedCount').text('تم تحديد ' + count + ' طلب');
                $('#overtimeBulkBar')
                    .toggleClass('d-none', count === 0)
                    .toggleClass('d-flex', count > 0);

                const selectable = $('.js-ot-select:not(:disabled)');
                const selectedVisible = selectable.filter(':checked').length;
                $('#selectAllPendingOvertime')
                    .prop('checked', selectable.length > 0 && selectedVisible === selectable.length)
                    .prop(
                        'indeterminate',
                        selectedVisible > 0 && selectedVisible < selectable.length
                    );
            }

            function clearBulkSelection() {
                state.selectedRequests = {};
                $('.js-ot-select, #selectAllPendingOvertime').prop('checked', false);
                updateBulkSelectionUi();
            }

            function renderRows(items, from) {
                if (!items.length) {
                    $('#overtimeTableBody').html(
                        '<tr><td colspan="' + tableColumns + '"><div class="att-empty">' +
                        '<div class="fs-2 mb-2">◷</div>' +
                        '<div class="fw-bold">لا توجد طلبات عمل إضافي</div>' +
                        '</div></td></tr>'
                    );
                    return;
                }

                let html = '';

                $.each(items, function (index, item) {
                    const employee = item.employee || {};

                    html += '<tr>';
                    if (permissions.approve) {
                        html += '<td><input type="checkbox" class="form-check-input js-ot-select" ' +
                            'value="' + item.id + '" ' +
                            (item.can_approve ? '' : 'disabled') + ' /></td>';
                    }
                    html += '<td>' + (Number(from || 1) + index) + '</td>';
                    html += '<td><div class="employee-name">' + valueOrDash(employee.name) +
                        '</div><div class="att-meta">' + valueOrDash(employee.employee_number) +
                        (employee.department ? ' · ' + escapeHtml(employee.department) : '') +
                        '</div></td>';
                    html += '<td><div class="fw-bold">' + dateText(item.overtime_date) +
                        '</div><div class="att-meta">' + escapeHtml(item.type_label) + '</div></td>';
                    html += '<td dir="ltr"><span>' + timeText(item.planned_start_at) +
                        '</span> <span class="ot-time-arrow">←</span> <span>' +
                        timeText(item.planned_end_at) + '</span></td>';
                    html += '<td><span class="fw-bold" dir="ltr">' +
                        escapeHtml(item.requested_duration_label) + '</span></td>';
                    html += '<td><span class="fw-bold" dir="ltr">' +
                        escapeHtml(item.actual_duration_label) + '</span></td>';
                    html += '<td><span class="fw-bold text-success" dir="ltr">' +
                        escapeHtml(item.approved_duration_label) + '</span></td>';
                    html += '<td>' + statusBadge(item) +
                        '<div class="att-meta ot-reason" title="' + escapeHtml(item.reason) + '">' +
                        escapeHtml(item.reason) + '</div></td>';
                    html += '<td>' + actionButtons(item) + '</td>';
                    html += '</tr>';
                });

                $('#overtimeTableBody').html(html);
                updateBulkSelectionUi();
            }

            function renderPagination(response) {
                const current = Number(response.current_page || 1);
                const last = Number(response.last_page || 1);
                let html = '';

                function pageItem(page, label, disabled, active) {
                    return '<li class="page-item ' +
                        (disabled ? 'disabled ' : '') +
                        (active ? 'active' : '') + '">' +
                        '<button type="button" class="page-link js-ot-page" data-page="' + page + '" ' +
                        (disabled ? 'disabled' : '') + '>' + label + '</button></li>';
                }

                html += pageItem(current - 1, '‹', current <= 1, false);

                for (
                    let page = Math.max(1, current - 2);
                    page <= Math.min(last, current + 2);
                    page += 1
                ) {
                    html += pageItem(page, page, false, page === current);
                }

                html += pageItem(current + 1, '›', current >= last, false);
                $('#overtimePagination').html(html);
            }

            function renderSummary(summary) {
                summary = summary || {};
                $('#sumTotal').text(summary.total || 0);
                $('#sumPending').text(summary.pending || 0);
                $('#sumApproved').text(summary.approved || 0);
                $('#sumCompleted').text(summary.completed || 0);
                $('#sumActual').text(durationText(summary.actual_minutes));
                $('#sumApprovedMinutes').text(durationText(summary.approved_minutes));
            }

            function loadRequests(page) {
                state.page = page || 1;
                clearBulkSelection();
                $('#overtimeTableBody').html(
                    '<tr><td colspan="' + tableColumns + '" class="att-loading">جاري تحميل البيانات...</td></tr>'
                );

                $.ajax({
                    url: urls.data,
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        page: state.page,
                        search: String($('#filterSearch').val() || '').trim(),
                        date_from: $('#filterDateFrom').val(),
                        date_to: $('#filterDateTo').val(),
                        employee_id: permissions.viewAll ? $('#filterEmployee').val() : '',
                        status: $('#filterStatus').val(),
                        per_page: $('#filterPerPage').val()
                    }
                }).done(function (response) {
                    const items = Array.isArray(response.data) ? response.data : [];
                    renderRows(items, response.from);
                    renderPagination(response);
                    renderSummary(response.summary);
                    $('#overtimeCount').text((response.total || 0) + ' طلب');
                    $('#overtimeInfo').text(
                        response.total
                            ? 'عرض ' + response.from + ' إلى ' + response.to + ' من ' + response.total
                            : 'لا توجد نتائج'
                    );
                }).fail(function (xhr) {
                    $('#overtimeTableBody').html(
                        '<tr><td colspan="' + tableColumns + '" class="att-loading text-danger">' +
                        escapeHtml(responseMessage(xhr)) + '</td></tr>'
                    );
                });
            }

            function fillSelectOptions(selector, items, emptyLabel) {
                let html = '<option value="">' + escapeHtml(emptyLabel) + '</option>';

                $.each(items, function (_, item) {
                    html += '<option value="' + item.id + '">' +
                        escapeHtml(item.name) + '</option>';
                });

                $(selector).html(html);
            }

            function fillEmployees(items, branches, departments) {
                let filterHtml = '<option value="">كل الموظفين</option>';

                $.each(items, function (_, employee) {
                    const label = employee.employee_number + ' — ' + employee.name;
                    const option = '<option value="' + employee.id + '">' +
                        escapeHtml(label) + '</option>';
                    filterHtml += option;
                });

                $('#filterEmployee').html(filterHtml);
                fillSelectOptions('#employeePickerBranch', branches, 'كل الفروع');
                fillSelectOptions('#employeePickerDepartment', departments, 'كل الإدارات');
                state.employees = items;
                renderEmployeePicker();
            }

            function updateSelectedEmployeesCount() {
                const count = Object.keys(state.selectedEmployees).length;
                $('#selectedEmployeesCount').text('تم اختيار ' + count);
            }

            function filteredEmployees() {
                const search = String($('#employeePickerSearch').val() || '')
                    .trim()
                    .toLocaleLowerCase();
                const branchId = String($('#employeePickerBranch').val() || '');
                const departmentId = String($('#employeePickerDepartment').val() || '');

                return $.grep(state.employees, function (employee) {
                    const haystack = String(
                        (employee.employee_number || '') + ' ' +
                        (employee.name || '') + ' ' +
                        (employee.branch_name || '') + ' ' +
                        (employee.department_name || '')
                    ).toLocaleLowerCase();

                    return (!search || haystack.indexOf(search) !== -1)
                        && (!branchId || String(employee.branch_id || '') === branchId)
                        && (!departmentId || String(employee.department_id || '') === departmentId);
                });
            }

            function renderEmployeePicker() {
                if (!permissions.manage) return;

                const employees = filteredEmployees();
                state.visibleEmployeeIds = $.map(employees, function (employee) {
                    return String(employee.id);
                });
                $('#visibleEmployeesCount').text(employees.length + ' موظف ظاهر');

                if (!employees.length) {
                    $('#overtimeEmployeeList').html(
                        '<div class="text-muted text-center py-4">لا يوجد موظفون مطابقون للبحث.</div>'
                    );
                    updateSelectedEmployeesCount();
                    return;
                }

                let html = '';

                $.each(employees, function (_, employee) {
                    const id = String(employee.id);
                    const meta = [
                        employee.employee_number,
                        employee.branch_name,
                        employee.department_name
                    ].filter(Boolean).join(' · ');

                    html += '<label class="ot-employee-option">' +
                        '<input type="checkbox" class="form-check-input js-employee-pick" ' +
                        'value="' + employee.id + '" ' +
                        (state.selectedEmployees[id] ? 'checked' : '') + ' />' +
                        '<span><span class="d-block fw-bold">' + escapeHtml(employee.name) +
                        '</span><small class="text-muted">' + escapeHtml(meta) +
                        '</small></span></label>';
                });

                $('#overtimeEmployeeList').html(html);
                updateSelectedEmployeesCount();
            }

            function loadOptions(callback) {
                if (state.optionsLoaded) {
                    if (typeof callback === 'function') callback();
                    return;
                }

                $.ajax({
                    url: urls.options,
                    type: 'GET',
                    dataType: 'json'
                }).done(function (response) {
                    const options = response.options || {};
                    const employees = Array.isArray(options.employees)
                        ? options.employees
                        : [];
                    const branches = Array.isArray(options.branches)
                        ? options.branches
                        : [];
                    const departments = Array.isArray(options.departments)
                        ? options.departments
                        : [];

                    fillEmployees(employees, branches, departments);
                    state.today = options.today || '';
                    state.optionsLoaded = true;

                    if (typeof callback === 'function') callback();
                }).fail(function (xhr) {
                    toast(responseMessage(xhr), 'error');
                });
            }

            function calculatePlannedDuration() {
                const start = String($('#overtimeStartTime').val() || '');
                const end = String($('#overtimeEndTime').val() || '');

                if (!start || !end) {
                    $('#plannedDuration').text('00:00');
                    return;
                }

                const startParts = start.split(':');
                const endParts = end.split(':');
                let startMinutes = Number(startParts[0]) * 60 + Number(startParts[1]);
                let endMinutes = Number(endParts[0]) * 60 + Number(endParts[1]);

                if (endMinutes <= startMinutes) {
                    endMinutes += 24 * 60;
                }

                $('#plannedDuration').text(durationText(endMinutes - startMinutes));
            }

            function resetCreateForm() {
                $('#overtimeForm')[0].reset();
                state.selectedEmployees = {};
                $('#overtimeFormErrors').addClass('d-none').empty();
                $('#overtimeDate').val(state.today);
                $('#overtimeStartTime').val('18:00');
                $('#overtimeEndTime').val('20:00');
                $('#overtimeType').val('regular_day');
                $('#btnSaveOvertime').prop('disabled', false).text('إرسال للاعتماد');
                renderEmployeePicker();
                calculatePlannedDuration();
            }

            function detailBox(label, value, extraClass) {
                return '<div class="col-md-6"><div class="att-detail">' +
                    '<div class="att-detail-label">' + escapeHtml(label) + '</div>' +
                    '<div class="att-detail-value ' + (extraClass || '') + '">' +
                    valueOrDash(value) + '</div></div></div>';
            }

            function renderDetails(item) {
                const employee = item.employee || {};
                const attendance = item.attendance || {};
                let html = '<div class="row g-3">';
                html += detailBox('الموظف', employee.name);
                html += detailBox('الرقم الوظيفي', employee.employee_number, 'ltr');
                html += detailBox('التاريخ', dateText(item.overtime_date));
                html += detailBox('النوع', item.type_label);
                html += detailBox('بداية الفترة', item.planned_start_at);
                html += detailBox('نهاية الفترة', item.planned_end_at);
                html += detailBox('المدة المطلوبة', item.requested_duration_label, 'text-primary');
                html += detailBox(
                    'الحد المصرح به',
                    durationText(item.authorized_minutes),
                    'text-primary'
                );
                html += detailBox('المدة الفعلية', item.actual_duration_label);
                html += detailBox('المدة المعتمدة', item.approved_duration_label, 'text-success');
                html += detailBox('الحالة', item.status_label);
                html += detailBox('مقدم الطلب', item.requested_by);
                html += detailBox('المعتمد بواسطة', item.approved_by);
                html += '<div class="col-12"><div class="att-detail">' +
                    '<div class="att-detail-label">سبب العمل الإضافي</div>' +
                    '<div class="att-detail-value">' + valueOrDash(item.reason) + '</div></div></div>';

                if (item.decision_notes) {
                    html += '<div class="col-12"><div class="att-detail">' +
                        '<div class="att-detail-label">ملاحظات القرار</div>' +
                        '<div class="att-detail-value">' + valueOrDash(item.decision_notes) + '</div></div></div>';
                }

                if (item.attendance) {
                    html += detailBox('الحضور الفعلي', attendance.check_in_at);
                    html += detailBox('الانصراف الفعلي', attendance.check_out_at);
                }

                html += '</div>';
                $('#overtimeDetailsBody').html(html);
            }

            $('#btnCreateOvertime').on('click', function () {
                loadOptions(function () {
                    resetCreateForm();
                    showModal('#overtimeFormModal');
                });
            });

            $('#overtimeStartTime, #overtimeEndTime').on('change input', function () {
                calculatePlannedDuration();
            });

            $('#employeePickerSearch').on('input', function () {
                renderEmployeePicker();
            });

            $('#employeePickerBranch, #employeePickerDepartment').on('change', function () {
                renderEmployeePicker();
            });

            $(document).on('change', '.js-employee-pick', function () {
                const id = String($(this).val());

                if ($(this).prop('checked')) {
                    state.selectedEmployees[id] = true;
                } else {
                    delete state.selectedEmployees[id];
                }

                updateSelectedEmployeesCount();
            });

            $('#btnSelectVisibleEmployees').on('click', function () {
                $.each(state.visibleEmployeeIds, function (_, id) {
                    state.selectedEmployees[String(id)] = true;
                });

                renderEmployeePicker();
            });

            $('#btnClearEmployees').on('click', function () {
                state.selectedEmployees = {};
                renderEmployeePicker();
            });

            $('#overtimeForm').on('submit', function (event) {
                event.preventDefault();
                const selectedIds = Object.keys(state.selectedEmployees);

                if (permissions.manage && !selectedIds.length) {
                    $('#overtimeFormErrors')
                        .removeClass('d-none')
                        .html('يجب اختيار موظف واحد على الأقل.');
                    return;
                }

                const button = $('#btnSaveOvertime');
                const formData = $(this).serializeArray();

                $.each(selectedIds, function (_, id) {
                    formData.push({
                        name: 'employee_ids[]',
                        value: id
                    });
                });

                button.prop('disabled', true).text('جاري الإرسال...');
                $('#overtimeFormErrors').addClass('d-none').empty();

                $.ajax({
                    url: urls.store,
                    type: 'POST',
                    dataType: 'json',
                    data: formData
                }).done(function (response) {
                    hideModal('#overtimeFormModal');
                    toast(response.message, 'success');
                    loadRequests(1);
                }).fail(function (xhr) {
                    $('#overtimeFormErrors')
                        .removeClass('d-none')
                        .html(errorsHtml(xhr));
                }).always(function () {
                    button.prop('disabled', false).text('إرسال للاعتماد');
                });
            });

            $(document).on('click', '.js-ot-details', function () {
                const id = $(this).data('id');
                $('#overtimeDetailsBody').html('<div class="att-loading">جاري تحميل التفاصيل...</div>');
                showModal('#overtimeDetailsModal');

                $.ajax({
                    url: routeUrl(urls.show, id),
                    type: 'GET',
                    dataType: 'json'
                }).done(function (response) {
                    renderDetails(response.request || {});
                }).fail(function (xhr) {
                    hideModal('#overtimeDetailsModal');
                    toast(responseMessage(xhr), 'error');
                });
            });

            $(document).on('change', '.js-ot-select', function () {
                const id = String($(this).val());

                if ($(this).prop('checked')) {
                    state.selectedRequests[id] = true;
                } else {
                    delete state.selectedRequests[id];
                }

                updateBulkSelectionUi();
            });

            $('#selectAllPendingOvertime').on('change', function () {
                const checked = $(this).prop('checked');

                $('.js-ot-select:not(:disabled)').each(function () {
                    const id = String($(this).val());
                    $(this).prop('checked', checked);

                    if (checked) {
                        state.selectedRequests[id] = true;
                    } else {
                        delete state.selectedRequests[id];
                    }
                });

                updateBulkSelectionUi();
            });

            $('#btnClearBulkSelection').on('click', function () {
                clearBulkSelection();
            });

            $('#btnBulkApprove').on('click', function () {
                const count = Object.keys(state.selectedRequests).length;
                if (!count) return;

                state.decisionMode = 'bulk';
                $('#approveOvertimeId').val('');
                $('#overtimeApproveModal h5').text('اعتماد جماعي للعمل الإضافي');
                $('#approveEmployeeName').text('عدد الطلبات المحددة: ' + count);
                $('#approvedMinutes')
                    .val('')
                    .prop('required', false)
                    .removeAttr('max');
                $('#approveMinutesHelp').text(
                    'اتركه فارغًا لاعتماد المدة المطلوبة لكل موظف، أو أدخل حدًا موحدًا.'
                );
                $('#approveNotes').val('');
                $('#approveFormErrors').addClass('d-none').empty();
                showModal('#overtimeApproveModal');
            });

            $('#btnBulkReject').on('click', function () {
                const count = Object.keys(state.selectedRequests).length;
                if (!count) return;

                state.decisionMode = 'bulk';
                $('#rejectOvertimeId').val('');
                $('#overtimeRejectModal h5').text('رفض جماعي لطلبات العمل الإضافي');
                $('#rejectEmployeeName').text('عدد الطلبات المحددة: ' + count);
                $('#rejectNotes').val('');
                $('#rejectFormErrors').addClass('d-none').empty();
                showModal('#overtimeRejectModal');
            });

            $(document).on('click', '.js-ot-approve', function () {
                const requestedMinutes = Number($(this).data('minutes') || 0);
                state.decisionMode = 'single';
                $('#approveOvertimeId').val($(this).data('id'));
                $('#overtimeApproveModal h5').text('اعتماد العمل الإضافي');
                $('#approveEmployeeName').text($(this).data('name'));
                $('#approvedMinutes')
                    .val(requestedMinutes)
                    .prop('required', true)
                    .attr('max', requestedMinutes);
                $('#approveMinutesHelp').text(
                    'الحد الأعلى المطلوب: ' + durationText(requestedMinutes) +
                    ' (' + requestedMinutes + ' دقيقة)'
                );
                $('#approveNotes').val('');
                $('#approveFormErrors').addClass('d-none').empty();
                showModal('#overtimeApproveModal');
            });

            $('#overtimeApproveForm').on('submit', function (event) {
                event.preventDefault();
                const id = $('#approveOvertimeId').val();
                const button = $('#btnConfirmApprove');
                const isBulk = state.decisionMode === 'bulk';
                const data = $(this).serializeArray();

                if (isBulk) {
                    data.push({ name: 'decision', value: 'approve' });
                    $.each(Object.keys(state.selectedRequests), function (_, requestId) {
                        data.push({ name: 'request_ids[]', value: requestId });
                    });
                }

                button.prop('disabled', true).text('جاري الاعتماد...');

                $.ajax({
                    url: isBulk ? urls.bulkDecision : routeUrl(urls.approve, id),
                    type: 'POST',
                    dataType: 'json',
                    data: data
                }).done(function (response) {
                    hideModal('#overtimeApproveModal');
                    toast(response.message, 'success');
                    loadRequests(state.page);
                }).fail(function (xhr) {
                    $('#approveFormErrors').removeClass('d-none').html(errorsHtml(xhr));
                }).always(function () {
                    button.prop('disabled', false).text('اعتماد');
                });
            });

            $(document).on('click', '.js-ot-reject', function () {
                state.decisionMode = 'single';
                $('#rejectOvertimeId').val($(this).data('id'));
                $('#overtimeRejectModal h5').text('رفض طلب العمل الإضافي');
                $('#rejectEmployeeName').text($(this).data('name'));
                $('#rejectNotes').val('');
                $('#rejectFormErrors').addClass('d-none').empty();
                showModal('#overtimeRejectModal');
            });

            $('#overtimeRejectForm').on('submit', function (event) {
                event.preventDefault();
                const id = $('#rejectOvertimeId').val();
                const button = $('#btnConfirmReject');
                const isBulk = state.decisionMode === 'bulk';
                const data = $(this).serializeArray();

                if (isBulk) {
                    data.push({ name: 'decision', value: 'reject' });
                    $.each(Object.keys(state.selectedRequests), function (_, requestId) {
                        data.push({ name: 'request_ids[]', value: requestId });
                    });
                }

                button.prop('disabled', true).text('جاري الرفض...');

                $.ajax({
                    url: isBulk ? urls.bulkDecision : routeUrl(urls.reject, id),
                    type: 'POST',
                    dataType: 'json',
                    data: data
                }).done(function (response) {
                    hideModal('#overtimeRejectModal');
                    toast(response.message, 'success');
                    loadRequests(state.page);
                }).fail(function (xhr) {
                    $('#rejectFormErrors').removeClass('d-none').html(errorsHtml(xhr));
                }).always(function () {
                    button.prop('disabled', false).text('رفض الطلب');
                });
            });

            $(document).on('click', '.js-ot-cancel', function () {
                $('#cancelOvertimeId').val($(this).data('id'));
                showModal('#overtimeCancelModal');
            });

            $('#btnConfirmCancel').on('click', function () {
                const id = $('#cancelOvertimeId').val();
                const button = $(this);
                button.prop('disabled', true).text('جاري الإلغاء...');

                $.ajax({
                    url: routeUrl(urls.cancel, id),
                    type: 'POST',
                    dataType: 'json'
                }).done(function (response) {
                    hideModal('#overtimeCancelModal');
                    toast(response.message, 'success');
                    loadRequests(state.page);
                }).fail(function (xhr) {
                    toast(responseMessage(xhr), 'error');
                }).always(function () {
                    button.prop('disabled', false).text('إلغاء الطلب');
                });
            });

            $('#overtimeFilterForm').on('submit', function (event) {
                event.preventDefault();
                loadRequests(1);
            });

            $('#btnResetFilters').on('click', function () {
                $('#overtimeFilterForm')[0].reset();
                loadRequests(1);
            });

            $(document).on('click', '.js-ot-page', function () {
                const page = Number($(this).data('page') || 1);
                if (!$(this).prop('disabled')) loadRequests(page);
            });

            $('.js-close-modal').on('click', function () {
                hideModal($(this).closest('.att-modal-overlay'));
            });

            $('.att-modal-overlay').on('click', function (event) {
                if (event.target === this) hideModal(this);
            });

            $(document).on('keydown', function (event) {
                if (event.key === 'Escape') {
                    $('.att-modal-overlay:visible').each(function () {
                        hideModal(this);
                    });
                }
            });

            loadOptions();
            loadRequests(1);
        });
    </script>
@endpush
