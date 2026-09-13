@extends('layouts.tenant')

@section('title', 'هياكل رواتب الموظفين')
@section('page-title', 'هياكل رواتب الموظفين')

@section('content')

<style>
    .salary-structures-page {
        direction: rtl;
    }

    .salary-structures-page table th,
    .salary-structures-page table td {
        white-space: nowrap;
        vertical-align: middle;
    }

    .structure-alert {
        display: none;
    }

    .structure-loading,
    .structure-empty {
        padding: 55px 20px !important;
        text-align: center;
        color: #64748b;
    }

    .structure-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        flex-wrap: wrap;
    }

    .structure-page-buttons {
        display: flex;
        gap: 5px;
        direction: ltr;
    }

    .structure-actions {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }

    body.ry-modal-open {
        overflow: hidden !important;
    }

    .ry-modal {
        position: fixed;
        inset: 0;
        z-index: 99999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 24px;
        overflow-y: auto;
        background: rgba(15, 23, 42, 0.7);
    }

    .ry-modal-panel {
        width: min(1150px, 100%);
        max-height: calc(100vh - 48px);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 25px 70px rgba(15, 23, 42, 0.3);
    }

    .ry-modal-panel.medium {
        width: min(780px, 100%);
    }

    .ry-modal-panel.small {
        width: min(520px, 100%);
    }

    .ry-modal-header,
    .ry-modal-footer {
        flex: 0 0 auto;
        padding: 18px 22px;
        background: #fff;
    }

    .ry-modal-header {
        border-bottom: 1px solid #e5e7eb;
    }

    .ry-modal-footer {
        border-top: 1px solid #e5e7eb;
    }

    .ry-modal-body {
        flex: 1 1 auto;
        min-height: 0;
        padding: 22px;
        overflow-x: hidden;
        overflow-y: auto !important;
        overscroll-behavior: contain;
    }

    .ry-modal-close {
        width: 40px;
        height: 40px;
        border: 0;
        border-radius: 12px;
        background: #f1f5f9;
        color: #334155;
        font-size: 24px;
        line-height: 1;
    }

    .component-list {
        display: grid;
        gap: 12px;
    }

    .component-card {
        border: 1px solid #e2e8f0;
        border-radius: 15px;
        background: #fff;
        overflow: hidden;
        transition: 0.15s ease;
    }

    .component-card.selected {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .component-card.hidden-component {
        display: none;
    }

    .component-card-header {
        padding: 14px 16px;
        background: #f8fafc;
    }

    .component-card-fields {
        display: none;
        padding: 16px;
        border-top: 1px solid #e2e8f0;
    }

    .component-card.selected .component-card-fields {
        display: block;
    }

    .component-type-earning {
        border-right: 4px solid #16a34a;
    }

    .component-type-deduction {
        border-right: 4px solid #dc2626;
    }

    .summary-box {
        height: 100%;
        padding: 16px;
        border-radius: 14px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }

    .summary-value {
        margin-top: 6px;
        font-size: 19px;
        font-weight: 700;
    }

    .details-grid {
        display: grid;
        grid-template-columns:
            repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
    }

    .details-item {
        padding: 14px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 13px;
    }

    .details-item small {
        display: block;
        margin-bottom: 5px;
        color: #64748b;
    }

    .details-components-table th,
    .details-components-table td {
        white-space: nowrap;
        vertical-align: middle;
    }

    @media (max-width: 767.98px) {
        .ry-modal {
            padding: 8px;
            align-items: stretch;
        }

        .ry-modal-panel {
            max-height: calc(100vh - 16px);
            border-radius: 14px;
        }

        .ry-modal-header,
        .ry-modal-body,
        .ry-modal-footer {
            padding: 15px;
        }
    }
</style>


<div class="salary-structures-page">

    <div id="pageAlert"
         class="alert structure-alert mb-4">
    </div>


    <div class="card border-0 shadow-sm rounded-4 mb-4">

        <div class="card-body p-4">

            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">

                <div>
                    <h5 class="mb-2">
                        هياكل رواتب الموظفين
                    </h5>

                    <p class="text-muted mb-0">
                        إدارة الراتب الأساسي والبدلات والاستقطاعات وإصدارات رواتب الموظفين.
                    </p>
                </div>

                @can('payroll.manage')
                    <button type="button"
                            class="btn btn-primary px-4"
                            id="btnCreateStructure">
                        + إضافة هيكل راتب
                    </button>
                @endcan

            </div>

        </div>

    </div>


    <div class="card border-0 shadow-sm rounded-4 mb-4">

        <div class="card-body p-4">

            <form id="filterForm">

                <div class="row g-3 align-items-end">

                    <div class="col-lg-4 col-md-6">

                        <label class="form-label">
                            البحث
                        </label>

                        <input type="text"
                               class="form-control"
                               id="filterSearch"
                               placeholder="اسم الموظف أو الرقم الوظيفي">

                    </div>


                    <div class="col-lg-3 col-md-6">

                        <label class="form-label">
                            الموظف
                        </label>

                        <select class="form-select"
                                id="filterEmployee">

                            <option value="">
                                جميع الموظفين
                            </option>

                        </select>

                    </div>


                    <div class="col-lg-2 col-md-4">

                        <label class="form-label">
                            الحالة
                        </label>

                        <select class="form-select"
                                id="filterStatus">

                            <option value="">
                                جميع الحالات الحالية
                            </option>

                            <option value="draft">
                                مسودة
                            </option>

                            <option value="active">
                                نشط
                            </option>

                            <option value="expired">
                                منتهي
                            </option>

                            <option value="cancelled">
                                ملغي
                            </option>

                            <option value="archived">
                                مؤرشف
                            </option>

                            <option value="all">
                                جميع الحالات
                            </option>

                        </select>

                    </div>


                    <div class="col-lg-1 col-md-4">

                        <label class="form-label">
                            السجلات
                        </label>

                        <select class="form-select"
                                id="filterPerPage">

                            <option value="10">
                                10
                            </option>

                            <option value="15" selected>
                                15
                            </option>

                            <option value="25">
                                25
                            </option>

                            <option value="50">
                                50
                            </option>

                        </select>

                    </div>


                    <div class="col-lg-2 col-md-4">

                        <div class="d-flex gap-2">

                            <button type="submit"
                                    class="btn btn-primary flex-grow-1">
                                بحث
                            </button>

                            <button type="button"
                                    class="btn btn-light border"
                                    id="btnResetFilters">
                                إعادة
                            </button>

                        </div>

                    </div>

                </div>

            </form>

        </div>

    </div>


    <div class="card border-0 shadow-sm rounded-4">

        <div class="card-header bg-white border-0 p-4">

            <div class="d-flex align-items-center justify-content-between gap-3">

                <div>
                    <h6 class="mb-1">
                        قائمة هياكل الرواتب
                    </h6>

                    <small class="text-muted"
                           id="recordsSummary">
                        جاري التحميل...
                    </small>
                </div>

                <span class="badge bg-primary-subtle text-primary px-3 py-2"
                      id="recordsCount">
                    0 سجل
                </span>

            </div>

        </div>


        <div class="table-responsive">

            <table class="table table-hover mb-0">

                <thead class="table-light">

                    <tr>
                        <th>#</th>
                        <th>الموظف</th>
                        <th>الإصدار</th>
                        <th>فترة السريان</th>
                        <th>الراتب الأساسي</th>
                        <th>الاستحقاقات</th>
                        <th>الاستقطاعات</th>
                        <th>صافي الراتب</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>

                </thead>

                <tbody id="structuresTableBody">

                    <tr>
                        <td colspan="10"
                            class="structure-loading">
                            جاري تحميل البيانات...
                        </td>
                    </tr>

                </tbody>

            </table>

        </div>


        <div class="card-footer bg-white border-0 p-4">

            <div class="structure-pagination">

                <div class="small text-muted"
                     id="paginationSummary">
                </div>

                <div class="structure-page-buttons"
                     id="paginationButtons">
                </div>

            </div>

        </div>

    </div>

</div>


{{-- موديل الإضافة والتعديل --}}
<div class="ry-modal"
     id="structureModal">

    <div class="ry-modal-panel">

        <div class="ry-modal-header">

            <div class="d-flex align-items-center justify-content-between gap-3">

                <div>
                    <h5 class="mb-1"
                        id="structureModalTitle">
                        إضافة هيكل راتب
                    </h5>

                    <small class="text-muted">
                        اختر الموظف ومكونات راتبه ثم احفظ الهيكل كمسودة.
                    </small>
                </div>

                <button type="button"
                        class="ry-modal-close close-structure-modal">
                    ×
                </button>

            </div>

        </div>


        <form id="structureForm">

            <div class="ry-modal-body">

                <div id="structureFormAlert"
                     class="alert alert-danger d-none">
                </div>

                <input type="hidden"
                       id="structureId">


                <div class="row g-3 mb-4">

                    <div class="col-lg-5">

                        <label class="form-label">
                            الموظف
                            <span class="text-danger">*</span>
                        </label>

                        <select class="form-select"
                                id="structureEmployee"
                                required>

                            <option value="">
                                اختر الموظف
                            </option>

                        </select>

                    </div>


                    <div class="col-lg-3 col-md-6">

                        <label class="form-label">
                            بداية السريان
                            <span class="text-danger">*</span>
                        </label>

                        <input type="date"
                               class="form-control"
                               id="effectiveFrom"
                               required>

                    </div>


                    <div class="col-lg-3 col-md-6">

                        <label class="form-label">
                            نهاية السريان
                        </label>

                        <input type="date"
                               class="form-control"
                               id="effectiveTo">

                    </div>


                    <div class="col-lg-1 col-md-4">

                        <label class="form-label">
                            العملة
                        </label>

                        <input type="text"
                               class="form-control text-center"
                               id="currencyCode"
                               maxlength="3"
                               dir="ltr"
                               value="SAR"
                               required>

                    </div>


                    <div class="col-12">

                        <label class="form-label">
                            الملاحظات
                        </label>

                        <textarea class="form-control"
                                  id="structureNotes"
                                  rows="2"
                                  maxlength="3000"></textarea>

                    </div>

                </div>


                <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">

                    <div>
                        <h6 class="mb-1">
                            مكونات راتب الموظف
                        </h6>

                        <small class="text-muted">
                            يجب اختيار راتب أساسي واحد على الأقل.
                        </small>
                    </div>

                    <div style="width: min(330px, 100%);">

                        <input type="text"
                               class="form-control"
                               id="componentSearch"
                               placeholder="البحث في مكونات الراتب">

                    </div>

                </div>


                <div id="componentsList"
                     class="component-list">

                    <div class="structure-loading">
                        جاري تحميل مكونات الراتب...
                    </div>

                </div>


                <div class="row g-3 mt-3">

                    <div class="col-lg-3 col-md-6">

                        <div class="summary-box">

                            <small class="text-muted">
                                الراتب الأساسي
                            </small>

                            <div class="summary-value"
                                 id="summaryBasicSalary">
                                0.00
                            </div>

                        </div>

                    </div>


                    <div class="col-lg-3 col-md-6">

                        <div class="summary-box">

                            <small class="text-muted">
                                إجمالي الاستحقاقات
                            </small>

                            <div class="summary-value text-success"
                                 id="summaryEarnings">
                                0.00
                            </div>

                        </div>

                    </div>


                    <div class="col-lg-3 col-md-6">

                        <div class="summary-box">

                            <small class="text-muted">
                                إجمالي الاستقطاعات
                            </small>

                            <div class="summary-value text-danger"
                                 id="summaryDeductions">
                                0.00
                            </div>

                        </div>

                    </div>


                    <div class="col-lg-3 col-md-6">

                        <div class="summary-box">

                            <small class="text-muted">
                                صافي الراتب التقديري
                            </small>

                            <div class="summary-value text-primary"
                                 id="summaryNetSalary">
                                0.00
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <div class="ry-modal-footer">

                <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">

                    <small class="text-muted">
                        سيتم حفظ الهيكل كمسودة قبل الاعتماد.
                    </small>

                    <div class="d-flex gap-2">

                        <button type="button"
                                class="btn btn-light border close-structure-modal">
                            إلغاء
                        </button>

                        <button type="submit"
                                class="btn btn-primary px-4"
                                id="btnSaveStructure">
                            حفظ المسودة
                        </button>

                    </div>

                </div>

            </div>

        </form>

    </div>

</div>


{{-- موديل التفاصيل --}}
<div class="ry-modal"
     id="detailsModal">

    <div class="ry-modal-panel">

        <div class="ry-modal-header">

            <div class="d-flex align-items-center justify-content-between">

                <h5 class="mb-0">
                    تفاصيل هيكل الراتب
                </h5>

                <button type="button"
                        class="ry-modal-close close-details-modal">
                    ×
                </button>

            </div>

        </div>

        <div class="ry-modal-body"
             id="structureDetails">
        </div>

        <div class="ry-modal-footer">

            <div class="d-flex justify-content-end">

                <button type="button"
                        class="btn btn-light border close-details-modal">
                    إغلاق
                </button>

            </div>

        </div>

    </div>

</div>


{{-- موديل الإصدار الجديد --}}
<div class="ry-modal"
     id="revisionModal">

    <div class="ry-modal-panel small">

        <div class="ry-modal-header">

            <div class="d-flex align-items-center justify-content-between">

                <h5 class="mb-0">
                    إنشاء إصدار راتب جديد
                </h5>

                <button type="button"
                        class="ry-modal-close close-revision-modal">
                    ×
                </button>

            </div>

        </div>


        <form id="revisionForm">

            <div class="ry-modal-body">

                <div id="revisionAlert"
                     class="alert alert-danger d-none">
                </div>

                <input type="hidden"
                       id="revisionStructureId">

                <label class="form-label">
                    تاريخ بداية الإصدار الجديد
                    <span class="text-danger">*</span>
                </label>

                <input type="date"
                       class="form-control"
                       id="revisionEffectiveFrom"
                       required>

                <small class="text-muted d-block mt-2">
                    سيتم نسخ مكونات الهيكل الحالي إلى مسودة جديدة.
                </small>

            </div>


            <div class="ry-modal-footer">

                <div class="d-flex justify-content-end gap-2">

                    <button type="button"
                            class="btn btn-light border close-revision-modal">
                        إلغاء
                    </button>

                    <button type="submit"
                            class="btn btn-primary"
                            id="btnCreateRevision">
                        إنشاء الإصدار
                    </button>

                </div>

            </div>

        </form>

    </div>

</div>


{{-- موديل تأكيد العمليات --}}
<div class="ry-modal"
     id="confirmModal">

    <div class="ry-modal-panel small">

        <div class="ry-modal-header">

            <div class="d-flex align-items-center justify-content-between">

                <h5 class="mb-0"
                    id="confirmTitle">
                    تأكيد العملية
                </h5>

                <button type="button"
                        class="ry-modal-close close-confirm-modal">
                    ×
                </button>

            </div>

        </div>


        <div class="ry-modal-body">

            <p id="confirmMessage"
               class="mb-3">
            </p>

            <div id="confirmReasonBox"
                 style="display:none;">

                <label class="form-label">
                    سبب الإلغاء
                </label>

                <textarea class="form-control"
                          id="confirmReason"
                          rows="3"
                          maxlength="1000"></textarea>

            </div>

        </div>


        <div class="ry-modal-footer">

            <div class="d-flex justify-content-end gap-2">

                <button type="button"
                        class="btn btn-light border close-confirm-modal">
                    تراجع
                </button>

                <button type="button"
                        class="btn btn-danger"
                        id="btnConfirmAction">
                    تأكيد
                </button>

            </div>

        </div>

    </div>

</div>

@endsection


@push('scripts')

<script>
(function bootSalaryStructuresPage() {

    if (typeof window.jQuery === 'undefined') {
        window.setTimeout(
            bootSalaryStructuresPage,
            50
        );

        return;
    }

    const $ = window.jQuery;

    $(function () {

        const canManage =
            @json(auth()->user()->can('payroll.manage'));

        const urls = {
            data:
                @json(route('app.payroll.salary-structures.data')),

            options:
                @json(route('app.payroll.salary-structures.options')),

            store:
                @json(route('app.payroll.salary-structures.store')),

            show:
                @json(
                    route(
                        'app.payroll.salary-structures.show',
                        ['salaryStructure' => '__ID__']
                    )
                ),

            update:
                @json(
                    route(
                        'app.payroll.salary-structures.update',
                        ['salaryStructure' => '__ID__']
                    )
                ),

            recalculate:
                @json(
                    route(
                        'app.payroll.salary-structures.recalculate',
                        ['salaryStructure' => '__ID__']
                    )
                ),

            activate:
                @json(
                    route(
                        'app.payroll.salary-structures.activate',
                        ['salaryStructure' => '__ID__']
                    )
                ),

            revision:
                @json(
                    route(
                        'app.payroll.salary-structures.revision',
                        ['salaryStructure' => '__ID__']
                    )
                ),

            cancel:
                @json(
                    route(
                        'app.payroll.salary-structures.cancel',
                        ['salaryStructure' => '__ID__']
                    )
                ),

            destroy:
                @json(
                    route(
                        'app.payroll.salary-structures.destroy',
                        ['salaryStructure' => '__ID__']
                    )
                )
        };


        const state = {
            page: 1,
            lastPage: 1,
            employees: [],
            components: [],
            pendingAction: null,
            optionsLoaded: false
        };


        function csrfToken() {
            return $('meta[name="csrf-token"]')
                .attr('content') ?? '';
        }


        function routeUrl(template, id) {
            return template.replace(
                '__ID__',
                encodeURIComponent(id)
            );
        }


        function escapeHtml(value) {
            return $('<div>')
                .text(value ?? '')
                .html();
        }


        function normalizeBoolean(value) {
            return (
                value === true ||
                value === 1 ||
                value === '1'
            );
        }


        function money(value, currency) {
            const number = Number(value ?? 0);

            return new Intl.NumberFormat(
                'ar-SA',
                {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }
            ).format(
                Number.isFinite(number)
                    ? number
                    : 0
            ) + ' ' + escapeHtml(currency ?? 'SAR');
        }


        function showPageAlert(message, type) {
            $('#pageAlert')
                .removeClass(
                    'alert-success alert-danger alert-warning alert-info'
                )
                .addClass(
                    'alert-' + (type ?? 'success')
                )
                .html(escapeHtml(message))
                .stop(true, true)
                .slideDown(150);

            window.setTimeout(function () {
                $('#pageAlert').slideUp(150);
            }, 5000);
        }


        function errorMessage(xhr) {
            const response =
                xhr.responseJSON ?? {};

            if (response.errors) {
                const messages = [];

                $.each(
                    response.errors,
                    function (_, errors) {
                        $.each(
                            Array.isArray(errors)
                                ? errors
                                : [errors],
                            function (_, message) {
                                messages.push(message);
                            }
                        );
                    }
                );

                if (messages.length) {
                    return messages.join('<br>');
                }
            }

            return escapeHtml(
                response.message ??
                'حدث خطأ أثناء تنفيذ العملية.'
            );
        }


        function openModal($modal) {
            $('body').addClass('ry-modal-open');

            $modal
                .css('display', 'flex')
                .attr('aria-hidden', 'false');

            $modal
                .find('.ry-modal-body')
                .scrollTop(0);
        }


        function closeModal($modal) {
            $modal
                .hide()
                .attr('aria-hidden', 'true');

            if (!$('.ry-modal:visible').length) {
                $('body')
                    .removeClass('ry-modal-open');
            }
        }


        function todayDate() {
            const date = new Date();

            const year =
                date.getFullYear();

            const month =
                String(
                    date.getMonth() + 1
                ).padStart(2, '0');

            const day =
                String(
                    date.getDate()
                ).padStart(2, '0');

            return year + '-' + month + '-' + day;
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
                method: 'GET',
                dataType: 'json'

            }).done(function (response) {
                state.employees =
                    response.employees ?? [];

                state.components =
                    response.components ?? [];

                state.optionsLoaded = true;

                fillEmployeeOptions();

                $('#currencyCode').val(
                    response.currency_code ?? 'SAR'
                );

                if (typeof callback === 'function') {
                    callback();
                }

            }).fail(function (xhr) {
                showPageAlert(
                    $(errorMessage(xhr)).text(),
                    'danger'
                );
            });
        }


        function fillEmployeeOptions() {
            const filterValue =
                $('#filterEmployee').val();

            const formValue =
                $('#structureEmployee').val();

            $('#filterEmployee').html(
                '<option value="">جميع الموظفين</option>'
            );

            $('#structureEmployee').html(
                '<option value="">اختر الموظف</option>'
            );

            $.each(
                state.employees,
                function (_, employee) {
                    const label =
                        employee.name +
                        ' (' +
                        employee.employee_number +
                        ')';

                    $('<option>')
                        .val(employee.id)
                        .text(label)
                        .appendTo(
                            '#filterEmployee'
                        );

                    $('<option>')
                        .val(employee.id)
                        .text(label)
                        .appendTo(
                            '#structureEmployee'
                        );
                }
            );

            if (filterValue) {
                $('#filterEmployee')
                    .val(filterValue);
            }

            if (formValue) {
                $('#structureEmployee')
                    .val(formValue);
            }
        }


        function componentInputHtml(
            component,
            selected
        ) {
            const method =
                component.calculation_method;

            if (method === 'fixed') {
                return `
                    <div class="col-md-6">
                        <label class="form-label">
                            المبلغ
                        </label>

                        <input type="number"
                               class="form-control component-amount"
                               min="0"
                               step="0.01"
                               value="${escapeHtml(
                                   selected?.amount ??
                                   component.default_amount ??
                                   ''
                               )}">
                    </div>
                `;
            }

            if (method === 'percentage') {
                const base = state.components.find(
                    function (item) {
                        return Number(item.id) ===
                            Number(
                                component
                                    .percentage_base_component_id
                            );
                    }
                );

                return `
                    <div class="col-md-6">
                        <label class="form-label">
                            النسبة المئوية
                        </label>

                        <div class="input-group">
                            <input type="number"
                                   class="form-control component-percentage"
                                   min="0"
                                   step="0.0001"
                                   value="${escapeHtml(
                                       selected?.percentage ??
                                       component.default_percentage ??
                                       ''
                                   )}">

                            <span class="input-group-text">
                                %
                            </span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            يحتسب من
                        </label>

                        <input type="text"
                               class="form-control"
                               value="${escapeHtml(
                                   base
                                       ? base.name +
                                           ' (' +
                                           base.code +
                                           ')'
                                       : 'غير محدد'
                               )}"
                               readonly>
                    </div>
                `;
            }

            if (method === 'formula') {
                return `
                    <div class="col-12">
                        <label class="form-label">
                            المعادلة
                        </label>

                        <textarea class="form-control component-formula"
                                  rows="2"
                                  dir="ltr">${escapeHtml(
                                      selected?.formula ??
                                      component.formula ??
                                      ''
                                  )}</textarea>
                    </div>
                `;
            }

            if (method === 'quantity_rate') {
                return `
                    <div class="col-md-6">
                        <label class="form-label">
                            سعر الوحدة
                        </label>

                        <input type="number"
                               class="form-control component-rate"
                               min="0"
                               step="0.01"
                               value="${escapeHtml(
                                   selected?.rate ??
                                   component.default_rate ??
                                   ''
                               )}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            الكمية الافتراضية
                        </label>

                        <input type="number"
                               class="form-control component-quantity"
                               min="0"
                               step="0.0001"
                               value="${escapeHtml(
                                   selected?.quantity ?? ''
                               )}">

                        <small class="text-muted">
                            اتركها فارغة إذا كانت الكمية تأتي من الحضور أو الإضافي.
                        </small>
                    </div>
                `;
            }

            return '';
        }


        function renderComponents(selectedRows) {
            const selectedMap = {};

            $.each(
                selectedRows ?? [],
                function (_, row) {
                    selectedMap[
                        Number(row.salary_component_id)
                    ] = row;
                }
            );

            if (!state.components.length) {
                $('#componentsList').html(`
                    <div class="alert alert-warning mb-0">
                        لا توجد مكونات راتب نشطة. أضف مكونات الراتب أولًا.
                    </div>
                `);

                return;
            }

            let html = '';

            $.each(
                state.components,
                function (_, component) {
                    const selected =
                        selectedMap[
                            Number(component.id)
                        ];

                    const checked =
                        selected &&
                        normalizeBoolean(
                            selected.is_active
                        );

                    const typeClass =
                        component.type === 'earning'
                            ? 'earning'
                            : 'deduction';

                    const typeLabel =
                        component.type === 'earning'
                            ? 'استحقاق'
                            : 'استقطاع';

                    const typeColor =
                        component.type === 'earning'
                            ? 'success'
                            : 'danger';

                    const searchValue = (
                        component.code +
                        ' ' +
                        component.name +
                        ' ' +
                        (
                            component.category_label ??
                            component.category
                        )
                    ).toLowerCase();

                    html += `
                        <div class="component-card
                                    component-type-${typeClass}
                                    ${checked ? 'selected' : ''}"
                             data-component-id="${component.id}"
                             data-component-code="${escapeHtml(component.code)}"
                             data-component-type="${component.type}"
                             data-component-category="${component.category}"
                             data-calculation-method="${component.calculation_method}"
                             data-base-component-id="${component.percentage_base_component_id ?? ''}"
                             data-search="${escapeHtml(searchValue)}">

                            <div class="component-card-header">

                                <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">

                                    <div class="form-check m-0">

                                        <input class="form-check-input component-select"
                                               type="checkbox"
                                               id="salaryComponent${component.id}"
                                               ${checked ? 'checked' : ''}>

                                        <label class="form-check-label fw-bold"
                                               for="salaryComponent${component.id}">
                                            ${escapeHtml(component.name)}
                                        </label>

                                        <div class="small text-muted mt-1"
                                             dir="ltr">
                                            ${escapeHtml(component.code)}
                                        </div>

                                    </div>

                                    <div class="d-flex gap-2 flex-wrap">

                                        <span class="badge bg-${typeColor}-subtle text-${typeColor}">
                                            ${typeLabel}
                                        </span>

                                        <span class="badge bg-light text-dark border">
                                            ${escapeHtml(
                                                component
                                                    .calculation_method_label ??
                                                component
                                                    .calculation_method
                                            )}
                                        </span>

                                    </div>

                                </div>

                            </div>

                            <div class="component-card-fields">

                                <div class="row g-3">
                                    ${componentInputHtml(
                                        component,
                                        selected
                                    )}
                                </div>

                            </div>

                        </div>
                    `;
                }
            );

            $('#componentsList').html(html);

            updateSalarySummary();
        }


        function selectedComponentRows() {
            const rows = [];

            $('.component-card.selected')
                .each(function () {
                    const $card = $(this);

                    rows.push({
                        salary_component_id:
                            Number(
                                $card.data(
                                    'component-id'
                                )
                            ),

                        amount:
                            $card
                                .find('.component-amount')
                                .val() || null,

                        percentage:
                            $card
                                .find('.component-percentage')
                                .val() || null,

                        rate:
                            $card
                                .find('.component-rate')
                                .val() || null,

                        quantity:
                            $card
                                .find('.component-quantity')
                                .val() || null,

                        formula:
                            $.trim(
                                $card
                                    .find('.component-formula')
                                    .val() ?? ''
                            ) || null,

                        is_active: 1
                    });
                });

            return rows;
        }


        function updateSalarySummary() {
            const values = {};
            let basicSalary = 0;
            let totalEarnings = 0;
            let totalDeductions = 0;

            $('.component-card.selected')
                .each(function () {
                    const $card = $(this);

                    const id =
                        Number(
                            $card.data('component-id')
                        );

                    const method =
                        $card.data(
                            'calculation-method'
                        );

                    let value = 0;

                    if (method === 'fixed') {
                        value = Number(
                            $card
                                .find('.component-amount')
                                .val() || 0
                        );
                    }

                    if (method === 'quantity_rate') {
                        value =
                            Number(
                                $card
                                    .find('.component-rate')
                                    .val() || 0
                            ) *
                            Number(
                                $card
                                    .find('.component-quantity')
                                    .val() || 0
                            );
                    }

                    values[id] = value;
                });


            $('.component-card.selected')
                .each(function () {
                    const $card = $(this);

                    if (
                        $card.data(
                            'calculation-method'
                        ) !== 'percentage'
                    ) {
                        return;
                    }

                    const id =
                        Number(
                            $card.data('component-id')
                        );

                    const baseId =
                        Number(
                            $card.data(
                                'base-component-id'
                            )
                        );

                    const percentage =
                        Number(
                            $card
                                .find('.component-percentage')
                                .val() || 0
                        );

                    values[id] =
                        Number(values[baseId] ?? 0) *
                        percentage /
                        100;
                });


            $('.component-card.selected')
                .each(function () {
                    const $card = $(this);

                    const id =
                        Number(
                            $card.data('component-id')
                        );

                    const value =
                        Number(values[id] ?? 0);

                    const type =
                        $card.data('component-type');

                    const category =
                        $card.data(
                            'component-category'
                        );

                    if (category === 'basic_salary') {
                        basicSalary = value;
                    }

                    if (type === 'earning') {
                        totalEarnings += value;
                    } else {
                        totalDeductions += value;
                    }
                });

            const currency =
                $('#currencyCode').val() || 'SAR';

            $('#summaryBasicSalary').html(
                money(basicSalary, currency)
            );

            $('#summaryEarnings').html(
                money(totalEarnings, currency)
            );

            $('#summaryDeductions').html(
                money(totalDeductions, currency)
            );

            $('#summaryNetSalary').html(
                money(
                    totalEarnings - totalDeductions,
                    currency
                )
            );
        }


        function resetStructureForm() {
            $('#structureForm')[0].reset();

            $('#structureId').val('');

            $('#structureEmployee')
                .prop('disabled', false)
                .val('');

            $('#effectiveFrom').val(
                todayDate()
            );

            $('#effectiveTo').val('');

            $('#currencyCode').val('SAR');
            $('#structureNotes').val('');
            $('#componentSearch').val('');

            $('#structureFormAlert')
                .addClass('d-none')
                .empty();

            renderComponents([]);
        }


        function buildStructureFormData() {
            const formData = new FormData();

            formData.append(
                '_token',
                csrfToken()
            );

            formData.append(
                'employee_id',
                $('#structureEmployee').val()
            );

            formData.append(
                'effective_from',
                $('#effectiveFrom').val()
            );

            formData.append(
                'effective_to',
                $('#effectiveTo').val()
            );

            formData.append(
                'currency_code',
                $.trim(
                    $('#currencyCode')
                        .val()
                ).toUpperCase()
            );

            formData.append(
                'notes',
                $.trim(
                    $('#structureNotes')
                        .val()
                )
            );

            const components =
                selectedComponentRows();

            $.each(
                components,
                function (index, component) {
                    $.each(
                        component,
                        function (field, value) {
                            formData.append(
                                'components[' +
                                index +
                                '][' +
                                field +
                                ']',
                                value ?? ''
                            );
                        }
                    );
                }
            );

            return formData;
        }


        function statusBadge(item) {
            const colors = {
                draft: 'warning',
                active: 'success',
                expired: 'secondary',
                cancelled: 'danger'
            };

            const color =
                colors[item.status] ??
                'secondary';

            return `
                <span class="badge bg-${color}-subtle text-${color}">
                    ${escapeHtml(
                        item.status_label ??
                        item.status
                    )}
                </span>
            `;
        }


        function renderActions(item) {
            let html = `
                <button type="button"
                        class="btn btn-sm btn-outline-secondary btn-details"
                        data-id="${item.id}">
                    عرض
                </button>
            `;

            if (!canManage || item.deleted_at) {
                return html;
            }

            if (item.status === 'draft') {
                html += `
                    <button type="button"
                            class="btn btn-sm btn-outline-primary btn-edit"
                            data-id="${item.id}">
                        تعديل
                    </button>

                    <button type="button"
                            class="btn btn-sm btn-outline-info btn-recalculate"
                            data-id="${item.id}">
                        حساب
                    </button>

                    <button type="button"
                            class="btn btn-sm btn-outline-success btn-activate"
                            data-id="${item.id}">
                        اعتماد
                    </button>

                    <button type="button"
                            class="btn btn-sm btn-outline-warning btn-cancel"
                            data-id="${item.id}">
                        إلغاء
                    </button>

                    <button type="button"
                            class="btn btn-sm btn-outline-danger btn-delete"
                            data-id="${item.id}">
                        حذف
                    </button>
                `;
            }

            if (
                item.status === 'active' ||
                item.status === 'expired'
            ) {
                html += `
                    <button type="button"
                            class="btn btn-sm btn-outline-primary btn-revision"
                            data-id="${item.id}">
                        إصدار جديد
                    </button>
                `;
            }

            return html;
        }


        function loadData(page) {
            page = page ?? 1;

            $('#structuresTableBody').html(`
                <tr>
                    <td colspan="10"
                        class="structure-loading">
                        جاري تحميل البيانات...
                    </td>
                </tr>
            `);

            $.ajax({
                url: urls.data,
                method: 'GET',
                dataType: 'json',

                data: {
                    page: page,
                    search:
                        $('#filterSearch').val(),

                    employee_id:
                        $('#filterEmployee').val(),

                    status:
                        $('#filterStatus').val(),

                    per_page:
                        $('#filterPerPage').val()
                }

            }).done(function (response) {
                const items =
                    response.data ?? [];

                if (!items.length) {
                    $('#structuresTableBody').html(`
                        <tr>
                            <td colspan="10"
                                class="structure-empty">
                                لا توجد هياكل رواتب مطابقة.
                            </td>
                        </tr>
                    `);
                } else {
                    let html = '';

                    $.each(
                        items,
                        function (index, item) {
                            const currency =
                                item.currency_code ??
                                'SAR';

                            html += `
                                <tr>
                                    <td>
                                        ${
                                            Number(
                                                response.from ?? 1
                                            ) + index
                                        }
                                    </td>

                                    <td>
                                        <div class="fw-bold">
                                            ${escapeHtml(
                                                item.employee?.name
                                            )}
                                        </div>

                                        <small class="text-muted"
                                               dir="ltr">
                                            ${escapeHtml(
                                                item.employee
                                                    ?.employee_number
                                            )}
                                        </small>
                                    </td>

                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            V${item.version}
                                        </span>
                                    </td>

                                    <td>
                                        <div dir="ltr">
                                            ${escapeHtml(
                                                item.effective_from
                                            )}
                                        </div>

                                        <small class="text-muted"
                                               dir="ltr">
                                            إلى:
                                            ${escapeHtml(
                                                item.effective_to ??
                                                'مستمر'
                                            )}
                                        </small>
                                    </td>

                                    <td>
                                        ${money(
                                            item.basic_salary,
                                            currency
                                        )}
                                    </td>

                                    <td class="text-success fw-bold">
                                        ${money(
                                            item.total_earnings,
                                            currency
                                        )}
                                    </td>

                                    <td class="text-danger fw-bold">
                                        ${money(
                                            item.total_deductions,
                                            currency
                                        )}
                                    </td>

                                    <td class="text-primary fw-bold">
                                        ${money(
                                            item.net_salary,
                                            currency
                                        )}
                                    </td>

                                    <td>
                                        ${statusBadge(item)}
                                    </td>

                                    <td>
                                        <div class="structure-actions">
                                            ${renderActions(item)}
                                        </div>
                                    </td>
                                </tr>
                            `;
                        }
                    );

                    $('#structuresTableBody')
                        .html(html);
                }

                renderPagination(response);

            }).fail(function (xhr) {
                $('#structuresTableBody').html(`
                    <tr>
                        <td colspan="10"
                            class="structure-empty text-danger">
                            تعذر تحميل البيانات.
                        </td>
                    </tr>
                `);

                showPageAlert(
                    $(errorMessage(xhr)).text(),
                    'danger'
                );
            });
        }


        function renderPagination(response) {
            const currentPage =
                Number(response.current_page ?? 1);

            const lastPage =
                Number(response.last_page ?? 1);

            state.page = currentPage;
            state.lastPage = lastPage;

            const total =
                Number(response.total ?? 0);

            $('#recordsCount')
                .text(total + ' سجل');

            $('#recordsSummary')
                .text('إجمالي هياكل الرواتب: ' + total);

            $('#paginationSummary').text(
                total
                    ? 'عرض ' +
                        response.from +
                        ' إلى ' +
                        response.to +
                        ' من ' +
                        total
                    : 'لا توجد سجلات'
            );

            let html = `
                <button type="button"
                        class="btn btn-sm btn-light border btn-page"
                        data-page="${currentPage - 1}"
                        ${currentPage <= 1 ? 'disabled' : ''}>
                    السابق
                </button>
            `;

            const start =
                Math.max(1, currentPage - 2);

            const end =
                Math.min(lastPage, currentPage + 2);

            for (
                let page = start;
                page <= end;
                page++
            ) {
                html += `
                    <button type="button"
                            class="btn btn-sm ${
                                page === currentPage
                                    ? 'btn-primary'
                                    : 'btn-light border'
                            } btn-page"
                            data-page="${page}">
                        ${page}
                    </button>
                `;
            }

            html += `
                <button type="button"
                        class="btn btn-sm btn-light border btn-page"
                        data-page="${currentPage + 1}"
                        ${currentPage >= lastPage ? 'disabled' : ''}>
                    التالي
                </button>
            `;

            $('#paginationButtons').html(html);
        }


        function getStructure(id, callback) {
            $.ajax({
                url: routeUrl(
                    urls.show,
                    id
                ),

                method: 'GET',
                dataType: 'json'

            }).done(function (response) {
                callback(
                    response.salary_structure
                );

            }).fail(function (xhr) {
                showPageAlert(
                    $(errorMessage(xhr)).text(),
                    'danger'
                );
            });
        }


        function renderDetails(item) {
            let componentRows = '';

            $.each(
                item.components ?? [],
                function (_, component) {
                    const color =
                        component.type === 'earning'
                            ? 'success'
                            : 'danger';

                    componentRows += `
                        <tr>
                            <td>
                                <div class="fw-bold">
                                    ${escapeHtml(component.name)}
                                </div>

                                <small class="text-muted"
                                       dir="ltr">
                                    ${escapeHtml(component.code)}
                                </small>
                            </td>

                            <td>
                                <span class="badge bg-${color}-subtle text-${color}">
                                    ${escapeHtml(
                                        component.type_label
                                    )}
                                </span>
                            </td>

                            <td>
                                ${escapeHtml(
                                    component
                                        .calculation_method_label
                                )}
                            </td>

                            <td class="fw-bold">
                                ${money(
                                    component.calculated_value,
                                    item.currency_code
                                )}
                            </td>
                        </tr>
                    `;
                }
            );

            $('#structureDetails').html(`
                <div class="details-grid mb-4">

                    <div class="details-item">
                        <small>الموظف</small>
                        <strong>
                            ${escapeHtml(item.employee?.name)}
                        </strong>
                    </div>

                    <div class="details-item">
                        <small>الرقم الوظيفي</small>
                        <strong dir="ltr">
                            ${escapeHtml(
                                item.employee?.employee_number
                            )}
                        </strong>
                    </div>

                    <div class="details-item">
                        <small>الإصدار</small>
                        <strong>
                            V${item.version}
                        </strong>
                    </div>

                    <div class="details-item">
                        <small>الحالة</small>
                        ${statusBadge(item)}
                    </div>

                    <div class="details-item">
                        <small>بداية السريان</small>
                        <strong dir="ltr">
                            ${escapeHtml(item.effective_from)}
                        </strong>
                    </div>

                    <div class="details-item">
                        <small>نهاية السريان</small>
                        <strong dir="ltr">
                            ${escapeHtml(
                                item.effective_to ?? 'مستمر'
                            )}
                        </strong>
                    </div>

                    <div class="details-item">
                        <small>الراتب الأساسي</small>
                        <strong>
                            ${money(
                                item.basic_salary,
                                item.currency_code
                            )}
                        </strong>
                    </div>

                    <div class="details-item">
                        <small>إجمالي الاستحقاقات</small>
                        <strong class="text-success">
                            ${money(
                                item.total_earnings,
                                item.currency_code
                            )}
                        </strong>
                    </div>

                    <div class="details-item">
                        <small>إجمالي الاستقطاعات</small>
                        <strong class="text-danger">
                            ${money(
                                item.total_deductions,
                                item.currency_code
                            )}
                        </strong>
                    </div>

                    <div class="details-item">
                        <small>صافي الراتب</small>
                        <strong class="text-primary">
                            ${money(
                                item.net_salary,
                                item.currency_code
                            )}
                        </strong>
                    </div>

                </div>

                <div class="table-responsive">

                    <table class="table table-bordered details-components-table mb-0">

                        <thead class="table-light">
                            <tr>
                                <th>المكون</th>
                                <th>النوع</th>
                                <th>طريقة الحساب</th>
                                <th>القيمة المحسوبة</th>
                            </tr>
                        </thead>

                        <tbody>
                            ${
                                componentRows ||
                                `
                                    <tr>
                                        <td colspan="4"
                                            class="text-center text-muted py-4">
                                            لا توجد مكونات.
                                        </td>
                                    </tr>
                                `
                            }
                        </tbody>

                    </table>

                </div>

                ${
                    item.notes
                        ? `
                            <div class="alert alert-light border mt-4 mb-0">
                                <strong>الملاحظات:</strong>
                                ${escapeHtml(item.notes)}
                            </div>
                        `
                        : ''
                }
            `);
        }


        function openConfirm(
            title,
            message,
            buttonText,
            buttonClass,
            callback,
            showReason
        ) {
            state.pendingAction = callback;

            $('#confirmTitle').text(title);
            $('#confirmMessage').text(message);
            $('#confirmReason').val('');

            $('#confirmReasonBox').toggle(
                Boolean(showReason)
            );

            $('#btnConfirmAction')
                .removeClass(
                    'btn-danger btn-success btn-warning btn-primary btn-info'
                )
                .addClass(buttonClass)
                .text(buttonText);

            openModal(
                $('#confirmModal')
            );
        }


        function runPostAction(
            url,
            data,
            successMessage
        ) {
            return $.ajax({
                url: url,
                method: 'POST',

                data: $.extend(
                    {
                        _token: csrfToken()
                    },
                    data ?? {}
                )

            }).done(function (response) {
                closeModal(
                    $('#confirmModal')
                );

                showPageAlert(
                    response.message ??
                    successMessage,
                    'success'
                );

                loadData(state.page);

            }).fail(function (xhr) {
                showPageAlert(
                    $(errorMessage(xhr)).text(),
                    'danger'
                );
            });
        }


        $('#filterForm').on(
            'submit',
            function (event) {
                event.preventDefault();
                loadData(1);
            }
        );


        $('#filterEmployee, #filterStatus, #filterPerPage')
            .on('change', function () {
                loadData(1);
            });


        $('#btnResetFilters').on(
            'click',
            function () {
                $('#filterSearch').val('');
                $('#filterEmployee').val('');
                $('#filterStatus').val('');
                $('#filterPerPage').val('15');

                loadData(1);
            }
        );


        $(document).on(
            'click',
            '.btn-page',
            function () {
                if ($(this).is(':disabled')) {
                    return;
                }

                loadData(
                    Number($(this).data('page'))
                );
            }
        );


        $('#btnCreateStructure').on(
            'click',
            function () {
                loadOptions(function () {
                    resetStructureForm();

                    $('#structureModalTitle')
                        .text('إضافة هيكل راتب');

                    openModal(
                        $('#structureModal')
                    );
                });
            }
        );


        $(document).on(
            'change',
            '.component-select',
            function () {
                $(this)
                    .closest('.component-card')
                    .toggleClass(
                        'selected',
                        $(this).is(':checked')
                    );

                updateSalarySummary();
            }
        );


        $(document).on(
            'input',
            '.component-card input, .component-card textarea',
            updateSalarySummary
        );


        $('#componentSearch').on(
            'input',
            function () {
                const search =
                    $.trim(
                        $(this).val()
                    ).toLowerCase();

                $('.component-card').each(
                    function () {
                        const haystack =
                            String(
                                $(this).data('search')
                            ).toLowerCase();

                        $(this).toggleClass(
                            'hidden-component',
                            search !== '' &&
                            !haystack.includes(search)
                        );
                    }
                );
            }
        );


        $(document).on(
            'click',
            '.btn-edit',
            function () {
                const id =
                    $(this).data('id');

                loadOptions(function () {
                    getStructure(
                        id,
                        function (item) {
                            resetStructureForm();

                            $('#structureModalTitle')
                                .text(
                                    'تعديل مسودة هيكل الراتب'
                                );

                            $('#structureId')
                                .val(item.id);

                            $('#structureEmployee')
                                .val(item.employee_id)
                                .prop(
                                    'disabled',
                                    true
                                );

                            $('#effectiveFrom')
                                .val(
                                    item.effective_from
                                );

                            $('#effectiveTo')
                                .val(
                                    item.effective_to ?? ''
                                );

                            $('#currencyCode')
                                .val(
                                    item.currency_code
                                );

                            $('#structureNotes')
                                .val(
                                    item.notes ?? ''
                                );

                            renderComponents(
                                item.components
                            );

                            openModal(
                                $('#structureModal')
                            );
                        }
                    );
                });
            }
        );


        $('#structureForm').on(
            'submit',
            function (event) {
                event.preventDefault();

                const id =
                    $('#structureId').val();

                const isEdit =
                    id !== '';

                const formData =
                    buildStructureFormData();

                if (isEdit) {
                    formData.append(
                        '_method',
                        'PUT'
                    );
                }

                const $button =
                    $('#btnSaveStructure');

                $('#structureFormAlert')
                    .addClass('d-none')
                    .empty();

                $button
                    .prop('disabled', true)
                    .text('جاري الحفظ...');

                $.ajax({
                    url: isEdit
                        ? routeUrl(
                            urls.update,
                            id
                        )
                        : urls.store,

                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false

                }).done(function (response) {
                    closeModal(
                        $('#structureModal')
                    );

                    showPageAlert(
                        response.message ??
                        'تم حفظ هيكل الراتب.',
                        'success'
                    );

                    loadData(
                        isEdit
                            ? state.page
                            : 1
                    );

                }).fail(function (xhr) {
                    $('#structureFormAlert')
                        .removeClass('d-none')
                        .html(
                            errorMessage(xhr)
                        );

                    $('#structureModal')
                        .find('.ry-modal-body')
                        .scrollTop(0);

                }).always(function () {
                    $button
                        .prop('disabled', false)
                        .text('حفظ المسودة');
                });
            }
        );


        $(document).on(
            'click',
            '.btn-details',
            function () {
                const id =
                    $(this).data('id');

                $('#structureDetails').html(`
                    <div class="structure-loading">
                        جاري تحميل التفاصيل...
                    </div>
                `);

                openModal(
                    $('#detailsModal')
                );

                getStructure(
                    id,
                    renderDetails
                );
            }
        );


        $(document).on(
            'click',
            '.btn-recalculate',
            function () {
                const id =
                    $(this).data('id');

                openConfirm(
                    'إعادة حساب الهيكل',
                    'سيتم إعادة حساب جميع مكونات الراتب والقيم الإجمالية.',
                    'إعادة الحساب',
                    'btn-info',
                    function () {
                        return runPostAction(
                            routeUrl(
                                urls.recalculate,
                                id
                            ),
                            {},
                            'تمت إعادة حساب هيكل الراتب.'
                        );
                    },
                    false
                );
            }
        );


        $(document).on(
            'click',
            '.btn-activate',
            function () {
                const id =
                    $(this).data('id');

                openConfirm(
                    'اعتماد هيكل الراتب',
                    'بعد الاعتماد لن يمكن تعديل هذا الإصدار مباشرة، وسيتم إنهاء الإصدار السابق عند بداية سريان الإصدار الجديد.',
                    'اعتماد وتفعيل',
                    'btn-success',
                    function () {
                        return runPostAction(
                            routeUrl(
                                urls.activate,
                                id
                            ),
                            {},
                            'تم اعتماد هيكل الراتب.'
                        );
                    },
                    false
                );
            }
        );


        $(document).on(
            'click',
            '.btn-cancel',
            function () {
                const id =
                    $(this).data('id');

                openConfirm(
                    'إلغاء مسودة هيكل الراتب',
                    'هل تريد إلغاء هذه المسودة؟',
                    'إلغاء المسودة',
                    'btn-warning',
                    function () {
                        return runPostAction(
                            routeUrl(
                                urls.cancel,
                                id
                            ),
                            {
                                reason:
                                    $('#confirmReason')
                                        .val()
                            },
                            'تم إلغاء المسودة.'
                        );
                    },
                    true
                );
            }
        );


        $(document).on(
            'click',
            '.btn-delete',
            function () {
                const id =
                    $(this).data('id');

                openConfirm(
                    'حذف مسودة هيكل الراتب',
                    'سيتم حذف المسودة ومكوناتها. هل تريد المتابعة؟',
                    'حذف المسودة',
                    'btn-danger',
                    function () {
                        return $.ajax({
                            url: routeUrl(
                                urls.destroy,
                                id
                            ),

                            method: 'POST',

                            data: {
                                _token:
                                    csrfToken(),

                                _method:
                                    'DELETE'
                            }

                        }).done(function (response) {
                            closeModal(
                                $('#confirmModal')
                            );

                            showPageAlert(
                                response.message ??
                                'تم حذف المسودة.',
                                'success'
                            );

                            loadData(
                                state.page
                            );

                        }).fail(function (xhr) {
                            showPageAlert(
                                $(errorMessage(xhr)).text(),
                                'danger'
                            );
                        });
                    },
                    false
                );
            }
        );


        $(document).on(
            'click',
            '.btn-revision',
            function () {
                $('#revisionStructureId')
                    .val(
                        $(this).data('id')
                    );

                $('#revisionEffectiveFrom')
                    .val(
                        todayDate()
                    );

                $('#revisionAlert')
                    .addClass('d-none')
                    .empty();

                openModal(
                    $('#revisionModal')
                );
            }
        );


        $('#revisionForm').on(
            'submit',
            function (event) {
                event.preventDefault();

                const id =
                    $('#revisionStructureId')
                        .val();

                const $button =
                    $('#btnCreateRevision');

                $button
                    .prop('disabled', true)
                    .text('جاري الإنشاء...');

                $.ajax({
                    url: routeUrl(
                        urls.revision,
                        id
                    ),

                    method: 'POST',

                    data: {
                        _token:
                            csrfToken(),

                        effective_from:
                            $('#revisionEffectiveFrom')
                                .val()
                    }

                }).done(function (response) {
                    closeModal(
                        $('#revisionModal')
                    );

                    showPageAlert(
                        response.message ??
                        'تم إنشاء إصدار جديد.',
                        'success'
                    );

                    loadData(1);

                }).fail(function (xhr) {
                    $('#revisionAlert')
                        .removeClass('d-none')
                        .html(
                            errorMessage(xhr)
                        );

                }).always(function () {
                    $button
                        .prop('disabled', false)
                        .text('إنشاء الإصدار');
                });
            }
        );


        $('#btnConfirmAction').on(
            'click',
            function () {
                if (
                    typeof state.pendingAction !==
                    'function'
                ) {
                    return;
                }

                const callback =
                    state.pendingAction;

                state.pendingAction = null;

                const $button =
                    $(this);

                const text =
                    $button.text();

                $button
                    .prop('disabled', true)
                    .text('جاري التنفيذ...');

                const request =
                    callback();

                if (
                    request &&
                    typeof request.always ===
                        'function'
                ) {
                    request.always(function () {
                        $button
                            .prop(
                                'disabled',
                                false
                            )
                            .text(text);
                    });
                } else {
                    $button
                        .prop(
                            'disabled',
                            false
                        )
                        .text(text);
                }
            }
        );


        $('.close-structure-modal')
            .on('click', function () {
                closeModal(
                    $('#structureModal')
                );
            });


        $('.close-details-modal')
            .on('click', function () {
                closeModal(
                    $('#detailsModal')
                );
            });


        $('.close-revision-modal')
            .on('click', function () {
                closeModal(
                    $('#revisionModal')
                );
            });


        $('.close-confirm-modal')
            .on('click', function () {
                state.pendingAction = null;

                closeModal(
                    $('#confirmModal')
                );
            });


        $('.ry-modal').on(
            'click',
            function (event) {
                if (event.target === this) {
                    closeModal($(this));
                }
            }
        );


        loadOptions(function () {
            loadData(1);
        });

    });

})();
</script>

@endpush