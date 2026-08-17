@extends('layouts.tenant')

@section('title', 'مستندات الموظفين')
@section('page-title', 'مستندات الموظفين')

@section('content')
    <style>
        .documents-page {
            --docs-primary: #1d4ed8;
            --docs-border: #e2e8f0;
            --docs-muted: #64748b;
            color: #0f172a;
        }

        .documents-page .docs-card {
            background: #fff;
            border: 1px solid var(--docs-border);
            border-radius: 18px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .05);
        }

        .documents-page .summary-card {
            min-height: 112px;
            padding: 20px;
        }

        .documents-page .summary-label {
            color: var(--docs-muted);
            font-size: 13px;
        }

        .documents-page .summary-value {
            margin-top: 8px;
            font-size: 28px;
            font-weight: 800;
        }

        .documents-page .summary-icon {
            display: grid;
            width: 44px;
            height: 44px;
            place-items: center;
            border-radius: 14px;
            font-size: 20px;
        }

        .documents-page .filter-label,
        .documents-page .form-label {
            margin-bottom: 7px;
            color: #334155;
            font-size: 13px;
            font-weight: 700;
        }

        .documents-page .form-control,
        .documents-page .form-select {
            min-height: 42px;
            border-color: #dbe3ef;
            border-radius: 10px;
        }

        .documents-page textarea.form-control {
            min-height: 90px;
        }

        .documents-page .table > :not(caption) > * > * {
            padding: 13px 12px;
            vertical-align: middle;
            border-color: #edf2f7;
        }

        .documents-page .table thead th {
            white-space: nowrap;
            color: #475569;
            background: #f8fafc;
            font-size: 13px;
        }

        .documents-page .employee-name {
            color: #0f172a;
            font-weight: 800;
        }

        .documents-page .employee-meta,
        .documents-page .document-meta {
            margin-top: 3px;
            color: var(--docs-muted);
            font-size: 12px;
        }

        .documents-page .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
        }

        .documents-page .status-valid,
        .documents-page .status-verified {
            color: #047857;
            background: #d1fae5;
        }

        .documents-page .status-expiring {
            color: #b45309;
            background: #fef3c7;
        }

        .documents-page .status-expired {
            color: #b91c1c;
            background: #fee2e2;
        }

        .documents-page .status-neutral,
        .documents-page .status-unverified {
            color: #475569;
            background: #e2e8f0;
        }

        .documents-page .action-btn {
            min-width: 36px;
            border-radius: 9px;
        }

        .documents-page .empty-state {
            padding: 54px 20px;
            text-align: center;
            color: var(--docs-muted);
        }

        .documents-page .loading-row {
            padding: 45px 20px;
            text-align: center;
            color: var(--docs-muted);
        }

        .documents-page .pagination .page-link {
            min-width: 38px;
            margin: 0 2px;
            border-radius: 9px;
            text-align: center;
        }

        .docs-modal-overlay {
            position: fixed;
            z-index: 1080;
            inset: 0;
            display: none;
            overflow-x: hidden;
            overflow-y: auto;
            padding: 28px 14px;
            background: rgba(15, 23, 42, .64);
        }

        .docs-modal-dialog {
            width: min(900px, 100%);
            margin: 0 auto;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .28);
        }

        .docs-modal-dialog.docs-modal-sm {
            width: min(500px, 100%);
        }

        .docs-modal-header,
        .docs-modal-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 18px 22px;
        }

        .docs-modal-header {
            position: sticky;
            z-index: 2;
            top: -28px;
            border-bottom: 1px solid var(--docs-border);
            border-radius: 18px 18px 0 0;
            background: #fff;
        }

        .docs-modal-footer {
            position: sticky;
            z-index: 2;
            bottom: -28px;
            justify-content: flex-end;
            border-top: 1px solid var(--docs-border);
            border-radius: 0 0 18px 18px;
            background: #fff;
        }

        .docs-modal-body {
            padding: 22px;
        }

        .docs-modal-close {
            width: 38px;
            height: 38px;
            border: 0;
            border-radius: 10px;
            color: #475569;
            background: #f1f5f9;
            font-size: 22px;
            line-height: 1;
        }

        body.docs-modal-open {
            overflow: hidden;
        }

        .documents-page .file-box {
            padding: 18px;
            border: 1px dashed #93c5fd;
            border-radius: 14px;
            background: #eff6ff;
        }

        .documents-page .detail-item {
            height: 100%;
            padding: 14px;
            border: 1px solid #e8edf5;
            border-radius: 12px;
            background: #fbfdff;
        }

        .documents-page .detail-label {
            margin-bottom: 7px;
            color: var(--docs-muted);
            font-size: 12px;
        }

        .documents-page .detail-value {
            overflow-wrap: anywhere;
            color: #0f172a;
            font-weight: 700;
        }

        .docs-toast {
            position: fixed;
            z-index: 1100;
            top: 24px;
            left: 24px;
            display: none;
            max-width: 420px;
            padding: 14px 18px;
            border-radius: 12px;
            color: #fff;
            box-shadow: 0 14px 36px rgba(15, 23, 42, .24);
        }

        .docs-toast.success {
            background: #047857;
        }

        .docs-toast.error {
            background: #b91c1c;
        }

        @media (max-width: 767.98px) {
            .documents-page .summary-card {
                min-height: 96px;
            }

            .docs-modal-overlay {
                padding: 0;
            }

            .docs-modal-dialog,
            .docs-modal-dialog.docs-modal-sm {
                width: 100%;
                min-height: 100vh;
                border-radius: 0;
            }

            .docs-modal-header {
                top: 0;
                border-radius: 0;
            }

            .docs-modal-footer {
                bottom: 0;
                border-radius: 0;
            }
        }
    </style>

    <div class="documents-page">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-4">
            <div>
                <h4 class="mb-1">مستندات الموظفين</h4>
                <p class="text-muted mb-0">
                    حفظ المستندات ومتابعة الاعتماد وتواريخ الانتهاء بأمان.
                </p>
            </div>

            @can('documents.manage')
                <button type="button" class="btn btn-primary px-4" id="btnCreateDocument">
                    + رفع مستند
                </button>
            @endcan
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-3">
                <div class="docs-card summary-card d-flex align-items-center justify-content-between">
                    <div>
                        <div class="summary-label">إجمالي المستندات</div>
                        <div class="summary-value" id="summaryTotal">0</div>
                    </div>
                    <span class="summary-icon text-primary bg-primary-subtle">▤</span>
                </div>
            </div>

            <div class="col-6 col-xl-3">
                <div class="docs-card summary-card d-flex align-items-center justify-content-between">
                    <div>
                        <div class="summary-label">المعتمدة</div>
                        <div class="summary-value text-success" id="summaryVerified">0</div>
                    </div>
                    <span class="summary-icon text-success bg-success-subtle">✓</span>
                </div>
            </div>

            <div class="col-6 col-xl-3">
                <div class="docs-card summary-card d-flex align-items-center justify-content-between">
                    <div>
                        <div class="summary-label">تنتهي خلال 30 يومًا</div>
                        <div class="summary-value text-warning" id="summaryExpiring">0</div>
                    </div>
                    <span class="summary-icon text-warning bg-warning-subtle">!</span>
                </div>
            </div>

            <div class="col-6 col-xl-3">
                <div class="docs-card summary-card d-flex align-items-center justify-content-between">
                    <div>
                        <div class="summary-label">منتهية</div>
                        <div class="summary-value text-danger" id="summaryExpired">0</div>
                    </div>
                    <span class="summary-icon text-danger bg-danger-subtle">×</span>
                </div>
            </div>
        </div>

        <div class="docs-card p-3 p-lg-4 mb-4">
            <form id="documentsFilterForm">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-4">
                        <label class="filter-label" for="filterSearch">البحث</label>
                        <input
                            type="search"
                            class="form-control"
                            id="filterSearch"
                            placeholder="اسم الموظف، رقم الموظف أو المستند"
                        >
                    </div>

                    <div class="col-md-6 col-lg-2">
                        <label class="filter-label" for="filterEmployee">الموظف</label>
                        <select class="form-select" id="filterEmployee">
                            <option value="">كل الموظفين</option>
                        </select>
                    </div>

                    <div class="col-md-6 col-lg-2">
                        <label class="filter-label" for="filterType">نوع المستند</label>
                        <select class="form-select" id="filterType">
                            <option value="">كل الأنواع</option>
                        </select>
                    </div>

                    <div class="col-md-6 col-lg-2">
                        <label class="filter-label" for="filterExpiry">حالة الانتهاء</label>
                        <select class="form-select" id="filterExpiry">
                            <option value="">كل الحالات</option>
                            <option value="valid">ساري</option>
                            <option value="expiring">قارب على الانتهاء</option>
                            <option value="expired">منتهي</option>
                            <option value="no_expiry">بدون انتهاء</option>
                        </select>
                    </div>

                    <div class="col-md-6 col-lg-2">
                        <label class="filter-label" for="filterVerification">الاعتماد</label>
                        <select class="form-select" id="filterVerification">
                            <option value="">الكل</option>
                            <option value="verified">معتمد</option>
                            <option value="unverified">غير معتمد</option>
                        </select>
                    </div>

                    <div class="col-md-6 col-lg-2">
                        <label class="filter-label" for="filterArchive">الأرشيف</label>
                        <select class="form-select" id="filterArchive">
                            <option value="active">الحالية</option>
                            <option value="only">المؤرشفة</option>
                            <option value="with">الكل</option>
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

                    <div class="col-lg-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">بحث</button>
                        <button type="button" class="btn btn-outline-secondary" id="btnResetFilters">
                            إعادة
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="docs-card overflow-hidden">
            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap p-3 border-bottom">
                <h6 class="mb-0">قائمة المستندات</h6>
                <span class="badge bg-primary-subtle text-primary" id="documentsCount">0 مستند</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الموظف</th>
                            <th>المستند</th>
                            <th>الرقم</th>
                            <th>الانتهاء</th>
                            <th>الاعتماد</th>
                            <th>الملف</th>
                            <th class="text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="documentsTableBody">
                        <tr>
                            <td colspan="8" class="loading-row">جاري تحميل البيانات...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap p-3 border-top">
                <div class="small text-muted" id="documentsInfo">—</div>
                <nav aria-label="صفحات المستندات">
                    <ul class="pagination pagination-sm mb-0" id="documentsPagination"></ul>
                </nav>
            </div>
        </div>

        <div class="docs-modal-overlay" id="documentFormModal" aria-hidden="true">
            <div class="docs-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="documentFormTitle">
                <div class="docs-modal-header">
                    <div>
                        <h5 class="mb-1" id="documentFormTitle">رفع مستند</h5>
                        <div class="small text-muted">أدخل بيانات المستند واختر الملف.</div>
                    </div>
                    <button type="button" class="docs-modal-close js-close-modal" aria-label="إغلاق">×</button>
                </div>

                <form id="documentForm" enctype="multipart/form-data">
                    <div class="docs-modal-body">
                        <input type="hidden" id="documentId">

                        <div class="alert alert-danger d-none" id="documentFormErrors"></div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="documentEmployeeId">
                                    الموظف <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="documentEmployeeId" name="employee_id" required>
                                    <option value="">اختر الموظف</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="documentType">
                                    نوع المستند <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="documentType" name="document_type" required>
                                    <option value="">اختر النوع</option>
                                </select>
                            </div>

                            <div class="col-md-8">
                                <label class="form-label" for="documentTitle">
                                    عنوان المستند <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="documentTitle"
                                    name="title"
                                    maxlength="255"
                                    required
                                >
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="documentNumber">رقم المستند</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="documentNumber"
                                    name="document_number"
                                    maxlength="100"
                                >
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="documentIssueDate">تاريخ الإصدار</label>
                                <input
                                    type="date"
                                    class="form-control"
                                    id="documentIssueDate"
                                    name="issue_date"
                                >
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="documentExpiryDate">تاريخ الانتهاء</label>
                                <input
                                    type="date"
                                    class="form-control"
                                    id="documentExpiryDate"
                                    name="expiry_date"
                                >
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="documentIssuer">جهة الإصدار</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="documentIssuer"
                                    name="issuing_authority"
                                    maxlength="255"
                                >
                            </div>

                            <div class="col-12">
                                <div class="file-box">
                                    <label class="form-label" for="documentFile">
                                        ملف المستند <span class="text-danger" id="fileRequiredMark">*</span>
                                    </label>
                                    <input
                                        type="file"
                                        class="form-control"
                                        id="documentFile"
                                        name="file"
                                        accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx"
                                    >
                                    <div class="small text-muted mt-2">
                                        PDF أو صورة أو Word أو Excel، بحد أقصى 10 MB.
                                        <span id="currentFileText"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="documentNotes">ملاحظات</label>
                                <textarea
                                    class="form-control"
                                    id="documentNotes"
                                    name="notes"
                                    maxlength="5000"
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="docs-modal-footer">
                        <button type="button" class="btn btn-light js-close-modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary px-4" id="btnSaveDocument">
                            حفظ المستند
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="docs-modal-overlay" id="documentDetailsModal" aria-hidden="true">
            <div class="docs-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="documentDetailsTitle">
                <div class="docs-modal-header">
                    <div>
                        <h5 class="mb-1" id="documentDetailsTitle">تفاصيل المستند</h5>
                        <div class="small text-muted">بيانات الملف والموظف والاعتماد.</div>
                    </div>
                    <button type="button" class="docs-modal-close js-close-modal" aria-label="إغلاق">×</button>
                </div>

                <div class="docs-modal-body" id="documentDetailsBody">
                    <div class="loading-row">جاري تحميل التفاصيل...</div>
                </div>

                <div class="docs-modal-footer">
                    <button type="button" class="btn btn-light js-close-modal">إغلاق</button>
                    <a href="#" class="btn btn-outline-primary" id="detailsDownloadLink">تنزيل الملف</a>
                    <a href="#" class="btn btn-primary d-none" id="detailsPreviewLink" target="_blank">
                        معاينة
                    </a>
                </div>
            </div>
        </div>

        <div class="docs-modal-overlay" id="documentConfirmModal" aria-hidden="true">
            <div class="docs-modal-dialog docs-modal-sm" role="dialog" aria-modal="true" aria-labelledby="documentConfirmTitle">
                <div class="docs-modal-header">
                    <h5 class="mb-0" id="documentConfirmTitle">تأكيد الإجراء</h5>
                    <button type="button" class="docs-modal-close js-close-modal" aria-label="إغلاق">×</button>
                </div>

                <div class="docs-modal-body">
                    <p class="mb-0" id="documentConfirmMessage"></p>
                </div>

                <div class="docs-modal-footer">
                    <button type="button" class="btn btn-light js-close-modal">إلغاء</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmDocumentAction">تأكيد</button>
                </div>
            </div>
        </div>

        <div class="docs-toast" id="documentsToast" role="status" aria-live="polite"></div>
    </div>
@endsection

@push('scripts')
    <script>
        jQuery(function ($) {
            'use strict';

            const urls = {
                data: @json(route('app.documents.data')),
                options: @json(route('app.documents.options')),
                store: @json(route('app.documents.store')),
                show: @json(route('app.documents.show', ['document' => '__ID__'])),
                update: @json(route('app.documents.update', ['document' => '__ID__'])),
                verify: @json(route('app.documents.verify', ['document' => '__ID__'])),
                unverify: @json(route('app.documents.unverify', ['document' => '__ID__'])),
                restore: @json(route('app.documents.restore', ['document' => '__ID__'])),
                destroy: @json(route('app.documents.destroy', ['document' => '__ID__'])),
            };

            const permissions = {
                manage: @json(auth()->user()->can('documents.manage')),
            };

            const state = {
                page: 1,
                optionsLoaded: false,
                confirmAction: null,
            };

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json',
                },
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

            function formatDate(value) {
                if (!value) {
                    return '—';
                }

                const parts = String(value).substring(0, 10).split('-');

                if (parts.length !== 3) {
                    return escapeHtml(value);
                }

                return escapeHtml(parts[2] + '/' + parts[1] + '/' + parts[0]);
            }

            function showModal(selector) {
                $(selector).attr('aria-hidden', 'false').show();
                $('body').addClass('docs-modal-open');
            }

            function hideModal(selector) {
                $(selector).attr('aria-hidden', 'true').hide();

                if (!$('.docs-modal-overlay:visible').length) {
                    $('body').removeClass('docs-modal-open');
                }
            }

            function showToast(message, type) {
                const $toast = $('#documentsToast');
                $toast
                    .stop(true, true)
                    .removeClass('success error')
                    .addClass(type === 'error' ? 'error' : 'success')
                    .text(message)
                    .fadeIn(180)
                    .delay(2800)
                    .fadeOut(250);
            }

            function responseMessage(xhr) {
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    return xhr.responseJSON.message;
                }

                return 'حدث خطأ غير متوقع، يرجى المحاولة مرة أخرى.';
            }

            function renderValidationErrors(xhr) {
                const errors = xhr.responseJSON && xhr.responseJSON.errors
                    ? xhr.responseJSON.errors
                    : null;

                if (!errors) {
                    $('#documentFormErrors')
                        .removeClass('d-none')
                        .text(responseMessage(xhr));
                    return;
                }

                let html = '<ul class="mb-0">';

                Object.keys(errors).forEach(function (field) {
                    errors[field].forEach(function (message) {
                        html += '<li>' + escapeHtml(message) + '</li>';
                    });
                });

                html += '</ul>';
                $('#documentFormErrors').removeClass('d-none').html(html);
            }

            function expiryBadge(item) {
                const classes = {
                    valid: 'status-valid',
                    expiring: 'status-expiring',
                    expired: 'status-expired',
                    no_expiry: 'status-neutral',
                };

                return '<span class="status-badge ' +
                    (classes[item.expiry_status] || 'status-neutral') +
                    '">' + escapeHtml(item.expiry_status_label) + '</span>';
            }

            function verificationBadge(item) {
                if (item.is_verified) {
                    return '<span class="status-badge status-verified">✓ معتمد</span>';
                }

                return '<span class="status-badge status-unverified">غير معتمد</span>';
            }

            function actionButtons(item) {
                let html = '<div class="d-flex justify-content-center gap-1 flex-wrap">';

                html += '<button type="button" class="btn btn-sm btn-outline-secondary action-btn js-document-details" ' +
                    'data-id="' + item.id + '" title="التفاصيل">عرض</button>';

                if (item.is_previewable && item.preview_url) {
                    html += '<a class="btn btn-sm btn-outline-primary action-btn" href="' +
                        escapeHtml(item.preview_url) + '" target="_blank" title="معاينة">فتح</a>';
                }

                html += '<a class="btn btn-sm btn-outline-primary action-btn" href="' +
                    escapeHtml(item.download_url) + '" title="تنزيل">تنزيل</a>';

                if (permissions.manage && !item.is_archived) {
                    html += '<button type="button" class="btn btn-sm btn-outline-primary action-btn js-document-edit" ' +
                        'data-id="' + item.id + '" title="تعديل">تعديل</button>';

                    if (item.is_verified) {
                        html += '<button type="button" class="btn btn-sm btn-outline-warning action-btn js-document-unverify" ' +
                            'data-id="' + item.id + '">إلغاء الاعتماد</button>';
                    } else {
                        html += '<button type="button" class="btn btn-sm btn-outline-success action-btn js-document-verify" ' +
                            'data-id="' + item.id + '">اعتماد</button>';
                    }

                    html += '<button type="button" class="btn btn-sm btn-outline-danger action-btn js-document-archive" ' +
                        'data-id="' + item.id + '">أرشفة</button>';
                }

                if (permissions.manage && item.is_archived) {
                    html += '<button type="button" class="btn btn-sm btn-outline-success action-btn js-document-restore" ' +
                        'data-id="' + item.id + '">استعادة</button>';
                }

                html += '</div>';
                return html;
            }

            function renderRows(items, from) {
                if (!items.length) {
                    $('#documentsTableBody').html(
                        '<tr><td colspan="8"><div class="empty-state">' +
                        '<div class="fs-2 mb-2">▤</div>' +
                        '<div class="fw-bold mb-1">لا توجد مستندات</div>' +
                        '<div class="small">غيّر عوامل البحث أو ارفع مستندًا جديدًا.</div>' +
                        '</div></td></tr>'
                    );
                    return;
                }

                let html = '';

                items.forEach(function (item, index) {
                    const employee = item.employee || {};

                    html += '<tr>';
                    html += '<td>' + (Number(from || 1) + index) + '</td>';
                    html += '<td><div class="employee-name">' + valueOrDash(employee.name) + '</div>' +
                        '<div class="employee-meta">' + valueOrDash(employee.employee_number) +
                        (employee.department ? ' · ' + escapeHtml(employee.department) : '') + '</div></td>';
                    html += '<td><div class="fw-bold">' + escapeHtml(item.title) + '</div>' +
                        '<div class="document-meta">' + escapeHtml(item.document_type_label) + '</div></td>';
                    html += '<td dir="ltr">' + valueOrDash(item.document_number) + '</td>';
                    html += '<td><div>' + formatDate(item.expiry_date) + '</div><div class="mt-1">' + expiryBadge(item) + '</div></td>';
                    html += '<td>' + verificationBadge(item) + '</td>';
                    html += '<td><div class="small fw-bold">' + escapeHtml(item.original_name) + '</div>' +
                        '<div class="document-meta">' + escapeHtml(item.formatted_file_size) + '</div></td>';
                    html += '<td>' + actionButtons(item) + '</td>';
                    html += '</tr>';
                });

                $('#documentsTableBody').html(html);
            }

            function renderPagination(response) {
                const current = Number(response.current_page || 1);
                const last = Number(response.last_page || 1);
                let html = '';

                function pageItem(page, label, disabled, active) {
                    return '<li class="page-item ' + (disabled ? 'disabled ' : '') + (active ? 'active' : '') + '">' +
                        '<button type="button" class="page-link js-documents-page" data-page="' + page + '" ' +
                        (disabled ? 'disabled' : '') + '>' + label + '</button></li>';
                }

                html += pageItem(current - 1, '‹', current <= 1, false);

                const start = Math.max(1, current - 2);
                const end = Math.min(last, current + 2);

                for (let page = start; page <= end; page += 1) {
                    html += pageItem(page, page, false, page === current);
                }

                html += pageItem(current + 1, '›', current >= last, false);
                $('#documentsPagination').html(html);
            }

            function renderSummary(summary) {
                summary = summary || {};
                $('#summaryTotal').text(summary.total || 0);
                $('#summaryVerified').text(summary.verified || 0);
                $('#summaryExpiring').text(summary.expiring || 0);
                $('#summaryExpired').text(summary.expired || 0);
            }

            function loadDocuments(page) {
                state.page = page || 1;
                $('#documentsTableBody').html(
                    '<tr><td colspan="8" class="loading-row">جاري تحميل البيانات...</td></tr>'
                );

                $.ajax({
                    url: urls.data,
                    type: 'GET',
                    data: {
                        page: state.page,
                        search: String($('#filterSearch').val() || '').trim(),
                        employee_id: $('#filterEmployee').val(),
                        document_type: $('#filterType').val(),
                        expiry_status: $('#filterExpiry').val(),
                        verification: $('#filterVerification').val(),
                        archive_status: $('#filterArchive').val(),
                        per_page: $('#filterPerPage').val(),
                    },
                    success: function (response) {
                        const items = Array.isArray(response.data) ? response.data : [];
                        renderRows(items, response.from);
                        renderPagination(response);
                        renderSummary(response.summary);
                        $('#documentsCount').text((response.total || 0) + ' مستند');

                        if (response.total) {
                            $('#documentsInfo').text(
                                'عرض ' + response.from + ' إلى ' + response.to + ' من ' + response.total
                            );
                        } else {
                            $('#documentsInfo').text('لا توجد نتائج');
                        }
                    },
                    error: function (xhr) {
                        $('#documentsTableBody').html(
                            '<tr><td colspan="8" class="loading-row text-danger">' +
                            escapeHtml(responseMessage(xhr)) + '</td></tr>'
                        );
                    },
                });
            }

            function fillSelect($select, items, placeholder, selected) {
                let html = '<option value="">' + escapeHtml(placeholder) + '</option>';

                items.forEach(function (item) {
                    const label = item.employee_number
                        ? item.employee_number + ' — ' + item.name
                        : item.label;

                    html += '<option value="' + escapeHtml(item.id || item.value) + '" ' +
                        (String(selected || '') === String(item.id || item.value) ? 'selected' : '') + '>' +
                        escapeHtml(label) + '</option>';
                });

                $select.html(html);
            }

            function loadOptions(callback) {
                if (state.optionsLoaded) {
                    if (typeof callback === 'function') {
                        callback();
                    }
                    return;
                }

                $.ajax({
                    url: urls.options,
                    type: 'GET',
                    success: function (response) {
                        const options = response.options || {};
                        const employees = Array.isArray(options.employees) ? options.employees : [];
                        const types = Array.isArray(options.document_types) ? options.document_types : [];

                        fillSelect($('#filterEmployee'), employees, 'كل الموظفين');
                        fillSelect($('#documentEmployeeId'), employees, 'اختر الموظف');
                        fillSelect($('#filterType'), types, 'كل الأنواع');
                        fillSelect($('#documentType'), types, 'اختر النوع');

                        state.optionsLoaded = true;

                        if (typeof callback === 'function') {
                            callback();
                        }
                    },
                    error: function (xhr) {
                        showToast(responseMessage(xhr), 'error');
                    },
                });
            }

            function resetDocumentForm() {
                $('#documentForm')[0].reset();
                $('#documentId').val('');
                $('#documentEmployeeId').prop('disabled', false);
                $('#documentFile').prop('required', true);
                $('#fileRequiredMark').removeClass('d-none');
                $('#currentFileText').text('');
                $('#documentFormErrors').addClass('d-none').empty();
                $('#documentFormTitle').text('رفع مستند');
                $('#btnSaveDocument').text('حفظ المستند').prop('disabled', false);
            }

            function openCreateDocument() {
                loadOptions(function () {
                    resetDocumentForm();
                    showModal('#documentFormModal');
                });
            }

            function fetchDocument(id, callback) {
                $.ajax({
                    url: routeUrl(urls.show, id),
                    type: 'GET',
                    success: function (response) {
                        if (typeof callback === 'function') {
                            callback(response.document || {});
                        }
                    },
                    error: function (xhr) {
                        showToast(responseMessage(xhr), 'error');
                    },
                });
            }

            function openEditDocument(id) {
                loadOptions(function () {
                    fetchDocument(id, function (item) {
                        resetDocumentForm();
                        $('#documentId').val(item.id);
                        $('#documentEmployeeId').val(item.employee_id).prop('disabled', true);
                        $('#documentType').val(item.document_type);
                        $('#documentTitle').val(item.title);
                        $('#documentNumber').val(item.document_number || '');
                        $('#documentIssueDate').val(item.issue_date ? String(item.issue_date).substring(0, 10) : '');
                        $('#documentExpiryDate').val(item.expiry_date ? String(item.expiry_date).substring(0, 10) : '');
                        $('#documentIssuer').val(item.issuing_authority || '');
                        $('#documentNotes').val(item.notes || '');
                        $('#documentFile').prop('required', false);
                        $('#fileRequiredMark').addClass('d-none');
                        $('#currentFileText').text('الملف الحالي: ' + (item.original_name || '—'));
                        $('#documentFormTitle').text('تعديل المستند');
                        $('#btnSaveDocument').text('حفظ التعديلات');
                        showModal('#documentFormModal');
                    });
                });
            }

            function detailItem(label, value) {
                return '<div class="col-md-6"><div class="detail-item">' +
                    '<div class="detail-label">' + escapeHtml(label) + '</div>' +
                    '<div class="detail-value">' + value + '</div>' +
                    '</div></div>';
            }

            function openDocumentDetails(id) {
                $('#documentDetailsBody').html(
                    '<div class="loading-row">جاري تحميل التفاصيل...</div>'
                );
                showModal('#documentDetailsModal');

                fetchDocument(id, function (item) {
                    const employee = item.employee || {};
                    const verifier = item.verified_by || {};
                    const uploader = item.uploaded_by || {};
                    let html = '<div class="row g-3">';

                    html += detailItem('الموظف', valueOrDash(employee.full_name || employee.display_name));
                    html += detailItem('رقم الموظف', valueOrDash(employee.employee_number));
                    html += detailItem('نوع المستند', valueOrDash(item.document_type_label));
                    html += detailItem('عنوان المستند', valueOrDash(item.title));
                    html += detailItem('رقم المستند', valueOrDash(item.document_number));
                    html += detailItem('جهة الإصدار', valueOrDash(item.issuing_authority));
                    html += detailItem('تاريخ الإصدار', formatDate(item.issue_date));
                    html += detailItem('تاريخ الانتهاء', formatDate(item.expiry_date));
                    html += detailItem('حالة الانتهاء', valueOrDash(item.expiry_status_label));
                    html += detailItem('الاعتماد', item.is_verified ? 'معتمد' : 'غير معتمد');
                    html += detailItem('اعتمد بواسطة', valueOrDash(verifier.name));
                    html += detailItem('رفع بواسطة', valueOrDash(uploader.name));
                    html += detailItem('اسم الملف', valueOrDash(item.original_name));
                    html += detailItem('حجم الملف', valueOrDash(item.formatted_file_size));
                    html += '<div class="col-12"><div class="detail-item">' +
                        '<div class="detail-label">الملاحظات</div>' +
                        '<div class="detail-value">' + valueOrDash(item.notes) + '</div>' +
                        '</div></div>';
                    html += '</div>';

                    $('#documentDetailsBody').html(html);
                    $('#detailsDownloadLink').attr('href', item.download_url || '#');

                    if (item.preview_url) {
                        $('#detailsPreviewLink').attr('href', item.preview_url).removeClass('d-none');
                    } else {
                        $('#detailsPreviewLink').attr('href', '#').addClass('d-none');
                    }
                });
            }

            function confirmAction(title, message, buttonClass, callback) {
                state.confirmAction = callback;
                $('#documentConfirmTitle').text(title);
                $('#documentConfirmMessage').text(message);
                $('#btnConfirmDocumentAction')
                    .removeClass('btn-danger btn-success btn-warning btn-primary')
                    .addClass(buttonClass || 'btn-danger')
                    .prop('disabled', false)
                    .text('تأكيد');
                showModal('#documentConfirmModal');
            }

            function postAction(url, method) {
                const ajaxMethod = method || 'POST';

                $.ajax({
                    url: url,
                    type: ajaxMethod,
                    success: function (response) {
                        hideModal('#documentConfirmModal');
                        showToast(response.message || 'تم تنفيذ الإجراء بنجاح.', 'success');
                        loadDocuments(state.page);
                    },
                    error: function (xhr) {
                        $('#btnConfirmDocumentAction').prop('disabled', false).text('تأكيد');
                        showToast(responseMessage(xhr), 'error');
                    },
                });
            }

            $('#documentsFilterForm').on('submit', function (event) {
                event.preventDefault();
                loadDocuments(1);
            });

            $('#btnResetFilters').on('click', function () {
                $('#documentsFilterForm')[0].reset();
                loadDocuments(1);
            });

            $('#btnCreateDocument').on('click', openCreateDocument);

            $(document).on('click', '.js-close-modal', function () {
                hideModal($(this).closest('.docs-modal-overlay'));
            });

            $('.docs-modal-overlay').on('click', function (event) {
                if (event.target === this) {
                    hideModal(this);
                }
            });

            $(document).on('keydown', function (event) {
                if (event.key === 'Escape') {
                    $('.docs-modal-overlay:visible').each(function () {
                        hideModal(this);
                    });
                }
            });

            $(document).on('click', '.js-documents-page', function () {
                const page = Number($(this).data('page'));

                if (page > 0) {
                    loadDocuments(page);
                }
            });

            $(document).on('click', '.js-document-details', function () {
                openDocumentDetails($(this).data('id'));
            });

            $(document).on('click', '.js-document-edit', function () {
                openEditDocument($(this).data('id'));
            });

            $(document).on('click', '.js-document-verify', function () {
                const id = $(this).data('id');
                confirmAction(
                    'اعتماد المستند',
                    'هل تريد اعتماد هذا المستند بعد التحقق من بياناته؟',
                    'btn-success',
                    function () {
                        postAction(routeUrl(urls.verify, id));
                    }
                );
            });

            $(document).on('click', '.js-document-unverify', function () {
                const id = $(this).data('id');
                confirmAction(
                    'إلغاء اعتماد المستند',
                    'هل تريد إلغاء اعتماد هذا المستند؟',
                    'btn-warning',
                    function () {
                        postAction(routeUrl(urls.unverify, id));
                    }
                );
            });

            $(document).on('click', '.js-document-archive', function () {
                const id = $(this).data('id');
                confirmAction(
                    'أرشفة المستند',
                    'سيختفي المستند من القائمة الحالية مع بقاء الملف قابلًا للاستعادة.',
                    'btn-danger',
                    function () {
                        postAction(routeUrl(urls.destroy, id), 'DELETE');
                    }
                );
            });

            $(document).on('click', '.js-document-restore', function () {
                const id = $(this).data('id');
                confirmAction(
                    'استعادة المستند',
                    'هل تريد استعادة هذا المستند إلى القائمة الحالية؟',
                    'btn-success',
                    function () {
                        postAction(routeUrl(urls.restore, id));
                    }
                );
            });

            $('#btnConfirmDocumentAction').on('click', function () {
                if (typeof state.confirmAction !== 'function') {
                    return;
                }

                $(this).prop('disabled', true).text('جاري التنفيذ...');
                state.confirmAction();
            });

            $('#documentForm').on('submit', function (event) {
                event.preventDefault();

                const id = $('#documentId').val();
                const formData = new FormData(this);

                if (id) {
                    formData.delete('employee_id');
                }

                $('#documentFormErrors').addClass('d-none').empty();
                $('#btnSaveDocument').prop('disabled', true).text('جاري الحفظ...');

                $.ajax({
                    url: id ? routeUrl(urls.update, id) : urls.store,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        hideModal('#documentFormModal');
                        showToast(response.message || 'تم حفظ المستند بنجاح.', 'success');
                        loadDocuments(id ? state.page : 1);
                    },
                    error: function (xhr) {
                        renderValidationErrors(xhr);
                        $('#btnSaveDocument')
                            .prop('disabled', false)
                            .text(id ? 'حفظ التعديلات' : 'حفظ المستند');
                    },
                });
            });

            loadOptions(function () {
                loadDocuments(1);
            });
        });
    </script>
@endpush
