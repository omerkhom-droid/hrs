@extends('layouts.tenant')

@section('title', 'مكونات الراتب')
@section('page-title', 'مكونات الراتب')

@section('content')

<style>
    .salary-page {
        direction: rtl;
    }

    .salary-table th,
    .salary-table td {
        white-space: nowrap;
        vertical-align: middle;
    }

    .salary-code {
        direction: ltr;
        display: inline-block;
    }

    .salary-empty {
        padding: 55px 20px !important;
        text-align: center;
        color: #6c757d;
    }

    .salary-loading {
        padding: 55px 20px !important;
        text-align: center;
        color: #0d6efd;
    }

    .salary-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        flex-wrap: wrap;
    }

    .salary-page-buttons {
        display: flex;
        gap: 5px;
        direction: ltr;
    }

    .salary-alert {
        display: none;
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
        background: rgba(15, 23, 42, 0.68);
    }

    .ry-modal-panel {
        width: min(1050px, 100%);
        max-height: calc(100vh - 48px);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 25px 70px rgba(15, 23, 42, 0.28);
    }

    .ry-modal-panel.ry-modal-small {
        width: min(520px, 100%);
    }

    .ry-modal-header,
    .ry-modal-footer {
        flex: 0 0 auto;
        padding: 18px 22px;
        background: #fff;
    }

    .ry-modal-header {
        border-bottom: 1px solid #e9ecef;
    }

    .ry-modal-footer {
        border-top: 1px solid #e9ecef;
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

    .ry-modal-close:hover {
        background: #e2e8f0;
    }

    .method-section {
        display: none;
        padding: 16px;
        border: 1px dashed #cbd5e1;
        border-radius: 14px;
        background: #f8fafc;
    }

    .salary-switch-box {
        height: 100%;
        min-height: 58px;
        display: flex;
        align-items: center;
        padding: 12px 14px;
        border: 1px solid #dee2e6;
        border-radius: 12px;
        background: #fff;
    }

    .salary-switch-box .form-check {
        width: 100%;
        margin: 0;
    }

    .salary-switch-box .form-check-input {
        cursor: pointer;
    }

    .salary-switch-box .form-check-label {
        cursor: pointer;
    }

    .salary-system-row {
        background: #f8fafc;
    }

    .salary-actions {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
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
        .ry-modal-footer,
        .ry-modal-body {
            padding: 15px;
        }
    }
</style>

<div class="salary-page">

    <div id="pageAlert"
         class="alert salary-alert mb-4"
         role="alert">
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">

        <div class="card-body p-4">

            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">

                <div>
                    <h5 class="mb-2">
                        مكونات الراتب
                    </h5>

                    <p class="text-muted mb-0">
                        إدارة البدلات والاستقطاعات والمكافآت والمكونات الأساسية للرواتب.
                    </p>
                </div>

                @can('payroll.manage')
                    <button type="button"
                            class="btn btn-primary px-4"
                            id="btnCreateComponent">
                        + إضافة مكون راتب
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
                               placeholder="الكود أو اسم مكون الراتب">

                    </div>


                    <div class="col-lg-2 col-md-3">

                        <label class="form-label">
                            النوع
                        </label>

                        <select class="form-select"
                                id="filterType">

                            <option value="">
                                جميع الأنواع
                            </option>

                            <option value="earning">
                                استحقاق
                            </option>

                            <option value="deduction">
                                استقطاع
                            </option>

                        </select>

                    </div>


                    <div class="col-lg-2 col-md-3">

                        <label class="form-label">
                            طريقة الحساب
                        </label>

                        <select class="form-select"
                                id="filterMethod">

                            <option value="">
                                جميع الطرق
                            </option>

                            <option value="fixed">
                                مبلغ ثابت
                            </option>

                            <option value="percentage">
                                نسبة مئوية
                            </option>

                            <option value="formula">
                                معادلة
                            </option>

                            <option value="quantity_rate">
                                كمية × سعر
                            </option>

                        </select>

                    </div>


                    <div class="col-lg-2 col-md-3">

                        <label class="form-label">
                            الحالة
                        </label>

                        <select class="form-select"
                                id="filterStatus">

                            <option value="active">
                                النشطة
                            </option>

                            <option value="inactive">
                                غير النشطة
                            </option>

                            <option value="archived">
                                المؤرشفة
                            </option>

                            <option value="all">
                                جميع الحالات
                            </option>

                        </select>

                    </div>


                    <div class="col-lg-2 col-md-3">

                        <label class="form-label">
                            عدد السجلات
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


                    <div class="col-12">

                        <div class="d-flex gap-2 flex-wrap">

                            <button type="submit"
                                    class="btn btn-primary px-4">
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
                        قائمة مكونات الراتب
                    </h6>

                    <small class="text-muted"
                           id="recordsSummary">
                        جاري التحميل...
                    </small>
                </div>

                <span class="badge bg-primary-subtle text-primary px-3 py-2"
                      id="recordsCount">
                    0 مكون
                </span>

            </div>

        </div>


        <div class="table-responsive">

            <table class="table table-hover salary-table mb-0">

                <thead class="table-light">

                    <tr>
                        <th>#</th>
                        <th>المكون</th>
                        <th>النوع</th>
                        <th>التصنيف</th>
                        <th>طريقة الحساب</th>
                        <th>القيمة الافتراضية</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>

                </thead>

                <tbody id="componentsTableBody">

                    <tr>
                        <td colspan="8"
                            class="salary-loading">
                            جاري تحميل البيانات...
                        </td>
                    </tr>

                </tbody>

            </table>

        </div>


        <div class="card-footer bg-white border-0 p-4">

            <div class="salary-pagination">

                <div class="text-muted small"
                     id="paginationSummary">
                </div>

                <div class="salary-page-buttons"
                     id="paginationButtons">
                </div>

            </div>

        </div>

    </div>

</div>


{{-- إضافة وتعديل مكون الراتب --}}
<div class="ry-modal"
     id="componentModal"
     aria-hidden="true">

    <div class="ry-modal-panel"
         role="dialog"
         aria-modal="true">

        <div class="ry-modal-header">

            <div class="d-flex align-items-center justify-content-between gap-3">

                <div>
                    <h5 class="mb-1"
                        id="componentModalTitle">
                        إضافة مكون راتب
                    </h5>

                    <small class="text-muted">
                        أدخل تعريف المكون وطريقة احتسابه.
                    </small>
                </div>

                <button type="button"
                        class="ry-modal-close btn-close-component"
                        aria-label="إغلاق">
                    ×
                </button>

            </div>

        </div>


        <form id="componentForm">

            <div class="ry-modal-body">

                <div id="formAlert"
                     class="alert alert-danger d-none">
                </div>

                <input type="hidden"
                       id="componentId">


                <div class="row g-3">

                    <div class="col-lg-4">

                        <label class="form-label">
                            كود المكون
                            <span class="text-danger">*</span>
                        </label>

                        <input type="text"
                               class="form-control"
                               id="componentCode"
                               maxlength="50"
                               dir="ltr"
                               required
                               placeholder="BASIC">

                        <div class="invalid-feedback"
                             data-error-for="code">
                        </div>

                    </div>


                    <div class="col-lg-4">

                        <label class="form-label">
                            الاسم العربي
                            <span class="text-danger">*</span>
                        </label>

                        <input type="text"
                               class="form-control"
                               id="componentName"
                               maxlength="255"
                               required
                               placeholder="الراتب الأساسي">

                        <div class="invalid-feedback"
                             data-error-for="name">
                        </div>

                    </div>


                    <div class="col-lg-4">

                        <label class="form-label">
                            الاسم الإنجليزي
                        </label>

                        <input type="text"
                               class="form-control"
                               id="componentNameEn"
                               maxlength="255"
                               dir="ltr"
                               placeholder="Basic Salary">

                        <div class="invalid-feedback"
                             data-error-for="name_en">
                        </div>

                    </div>


                    <div class="col-lg-4">

                        <label class="form-label">
                            نوع المكون
                            <span class="text-danger">*</span>
                        </label>

                        <select class="form-select"
                                id="componentType"
                                required>

                            <option value="earning">
                                استحقاق
                            </option>

                            <option value="deduction">
                                استقطاع
                            </option>

                        </select>

                        <div class="invalid-feedback"
                             data-error-for="type">
                        </div>

                    </div>


                    <div class="col-lg-4">

                        <label class="form-label">
                            التصنيف
                            <span class="text-danger">*</span>
                        </label>

                        <select class="form-select"
                                id="componentCategory"
                                required>

                            <option value="basic_salary">
                                راتب أساسي
                            </option>

                            <option value="allowance">
                                بدل
                            </option>

                            <option value="bonus">
                                مكافأة
                            </option>

                            <option value="commission">
                                عمولة
                            </option>

                            <option value="overtime">
                                ساعات إضافية
                            </option>

                            <option value="reimbursement">
                                تعويض مصروفات
                            </option>

                            <option value="tax">
                                ضريبة
                            </option>

                            <option value="insurance">
                                تأمينات
                            </option>

                            <option value="loan">
                                قرض أو سلفة
                            </option>

                            <option value="absence">
                                غياب
                            </option>

                            <option value="penalty">
                                جزاء
                            </option>

                            <option value="other">
                                أخرى
                            </option>

                        </select>

                        <div class="invalid-feedback"
                             data-error-for="category">
                        </div>

                    </div>


                    <div class="col-lg-4">

                        <label class="form-label">
                            طريقة الحساب
                            <span class="text-danger">*</span>
                        </label>

                        <select class="form-select"
                                id="calculationMethod"
                                required>

                            <option value="fixed">
                                مبلغ ثابت
                            </option>

                            <option value="percentage">
                                نسبة مئوية
                            </option>

                            <option value="formula">
                                معادلة حسابية
                            </option>

                            <option value="quantity_rate">
                                كمية × سعر
                            </option>

                        </select>

                        <div class="invalid-feedback"
                             data-error-for="calculation_method">
                        </div>

                    </div>


                    <div class="col-12">

                        <div class="method-section"
                             id="fixedMethodSection">

                            <div class="row g-3">

                                <div class="col-md-6">

                                    <label class="form-label">
                                        المبلغ الافتراضي
                                        <span class="text-danger">*</span>
                                    </label>

                                    <input type="number"
                                           class="form-control"
                                           id="defaultAmount"
                                           min="0"
                                           step="0.01"
                                           placeholder="0.00">

                                    <div class="invalid-feedback"
                                         data-error-for="default_amount">
                                    </div>

                                </div>

                            </div>

                        </div>


                        <div class="method-section"
                             id="percentageMethodSection">

                            <div class="row g-3">

                                <div class="col-md-6">

                                    <label class="form-label">
                                        النسبة المئوية
                                        <span class="text-danger">*</span>
                                    </label>

                                    <div class="input-group">

                                        <input type="number"
                                               class="form-control"
                                               id="percentageRate"
                                               min="0"
                                               step="0.0001"
                                               placeholder="0">

                                        <span class="input-group-text">
                                            %
                                        </span>

                                    </div>

                                    <div class="invalid-feedback"
                                         data-error-for="percentage_rate">
                                    </div>

                                </div>


                                <div class="col-md-6">

                                    <label class="form-label">
                                        المكون الأساسي للنسبة
                                        <span class="text-danger">*</span>
                                    </label>

                                    <select class="form-select"
                                            id="percentageBaseComponent">

                                        <option value="">
                                            اختر المكون الأساسي
                                        </option>

                                    </select>

                                    <div class="invalid-feedback"
                                         data-error-for="percentage_base_component_id">
                                    </div>

                                </div>

                            </div>

                        </div>


                        <div class="method-section"
                             id="formulaMethodSection">

                            <label class="form-label">
                                المعادلة الحسابية
                                <span class="text-danger">*</span>
                            </label>

                            <textarea class="form-control"
                                      id="formulaExpression"
                                      rows="3"
                                      dir="ltr"
                                      placeholder="BASIC * 0.10"></textarea>

                            <small class="text-muted d-block mt-2">
                                استخدم أكواد مكونات الراتب داخل المعادلة.
                            </small>

                            <div class="invalid-feedback"
                                 data-error-for="formula_expression">
                            </div>

                        </div>


                        <div class="method-section"
                             id="quantityRateMethodSection">

                            <div class="row g-3">

                                <div class="col-md-6">

                                    <label class="form-label">
                                        الكمية الافتراضية
                                    </label>

                                    <input type="number"
                                           class="form-control"
                                           id="defaultQuantity"
                                           min="0"
                                           step="0.0001"
                                           placeholder="0">

                                    <div class="invalid-feedback"
                                         data-error-for="default_quantity">
                                    </div>

                                </div>


                                <div class="col-md-6">

                                    <label class="form-label">
                                        سعر الوحدة
                                        <span class="text-danger">*</span>
                                    </label>

                                    <input type="number"
                                           class="form-control"
                                           id="unitRate"
                                           min="0"
                                           step="0.01"
                                           placeholder="0.00">

                                    <div class="invalid-feedback"
                                         data-error-for="unit_rate">
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    <div class="col-md-6">

                        <label class="form-label">
                            ترتيب العرض
                        </label>

                        <input type="number"
                               class="form-control"
                               id="sortOrder"
                               min="0"
                               step="1"
                               value="0">

                        <div class="invalid-feedback"
                             data-error-for="sort_order">
                        </div>

                    </div>


                    <div class="col-md-6">

                        <label class="form-label">
                            الوصف
                        </label>

                        <textarea class="form-control"
                                  id="componentDescription"
                                  rows="2"
                                  placeholder="وصف مختصر لمكون الراتب"></textarea>

                    </div>


                    <div class="col-12">

                        <hr class="my-2">

                        <h6 class="mb-3">
                            إعدادات المكون
                        </h6>

                    </div>


                    <div class="col-lg-3 col-md-4 col-sm-6">

                        <div class="salary-switch-box">

                            <div class="form-check form-switch">

                                <input class="form-check-input"
                                       type="checkbox"
                                       id="isTaxable">

                                <label class="form-check-label"
                                       for="isTaxable">
                                    خاضع للضريبة
                                </label>

                            </div>

                        </div>

                    </div>


                    <div class="col-lg-3 col-md-4 col-sm-6">

                        <div class="salary-switch-box">

                            <div class="form-check form-switch">

                                <input class="form-check-input"
                                       type="checkbox"
                                       id="isInsurable">

                                <label class="form-check-label"
                                       for="isInsurable">
                                    خاضع للتأمينات
                                </label>

                            </div>

                        </div>

                    </div>


                    <div class="col-lg-3 col-md-4 col-sm-6">

                        <div class="salary-switch-box">

                            <div class="form-check form-switch">

                                <input class="form-check-input"
                                       type="checkbox"
                                       id="isOvertimeBase">

                                <label class="form-check-label"
                                       for="isOvertimeBase">
                                    يدخل في أساس الإضافي
                                </label>

                            </div>

                        </div>

                    </div>


                    <div class="col-lg-3 col-md-4 col-sm-6">

                        <div class="salary-switch-box">

                            <div class="form-check form-switch">

                                <input class="form-check-input"
                                       type="checkbox"
                                       id="isProratable"
                                       checked>

                                <label class="form-check-label"
                                       for="isProratable">
                                    يقبل الاحتساب النسبي
                                </label>

                            </div>

                        </div>

                    </div>


                    <div class="col-lg-3 col-md-4 col-sm-6">

                        <div class="salary-switch-box">

                            <div class="form-check form-switch">

                                <input class="form-check-input"
                                       type="checkbox"
                                       id="isRecurring"
                                       checked>

                                <label class="form-check-label"
                                       for="isRecurring">
                                    مكون متكرر
                                </label>

                            </div>

                        </div>

                    </div>


                    <div class="col-lg-3 col-md-4 col-sm-6">

                        <div class="salary-switch-box">

                            <div class="form-check form-switch">

                                <input class="form-check-input"
                                       type="checkbox"
                                       id="requiresInput">

                                <label class="form-check-label"
                                       for="requiresInput">
                                    يحتاج إدخالًا يدويًا
                                </label>

                            </div>

                        </div>

                    </div>


                    <div class="col-lg-3 col-md-4 col-sm-6">

                        <div class="salary-switch-box">

                            <div class="form-check form-switch">

                                <input class="form-check-input"
                                       type="checkbox"
                                       id="affectsNet"
                                       checked>

                                <label class="form-check-label"
                                       for="affectsNet">
                                    يؤثر على صافي الراتب
                                </label>

                            </div>

                        </div>

                    </div>


                    <div class="col-lg-3 col-md-4 col-sm-6">

                        <div class="salary-switch-box">

                            <div class="form-check form-switch">

                                <input class="form-check-input"
                                       type="checkbox"
                                       id="isActive"
                                       checked>

                                <label class="form-check-label"
                                       for="isActive">
                                    نشط ومتاح للاستخدام
                                </label>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <div class="ry-modal-footer">

                <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">

                    <small class="text-muted">
                        الحقول التي تحمل علامة
                        <span class="text-danger">*</span>
                        مطلوبة.
                    </small>

                    <div class="d-flex gap-2">

                        <button type="button"
                                class="btn btn-light border btn-close-component">
                            إلغاء
                        </button>

                        <button type="submit"
                                class="btn btn-primary px-4"
                                id="btnSaveComponent">
                            حفظ البيانات
                        </button>

                    </div>

                </div>

            </div>

        </form>

    </div>

</div>


{{-- تأكيد الأرشفة أو الاستعادة --}}
<div class="ry-modal"
     id="confirmModal"
     aria-hidden="true">

    <div class="ry-modal-panel ry-modal-small"
         role="dialog"
         aria-modal="true">

        <div class="ry-modal-header">

            <div class="d-flex align-items-center justify-content-between">

                <h5 class="mb-0"
                    id="confirmTitle">
                    تأكيد العملية
                </h5>

                <button type="button"
                        class="ry-modal-close btn-close-confirm">
                    ×
                </button>

            </div>

        </div>


        <div class="ry-modal-body">

            <p class="mb-0"
               id="confirmMessage">
            </p>

        </div>


        <div class="ry-modal-footer">

            <div class="d-flex justify-content-end gap-2">

                <button type="button"
                        class="btn btn-light border btn-close-confirm">
                    إلغاء
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
(function bootSalaryComponentsPage() {

    if (typeof window.jQuery === 'undefined') {
        window.setTimeout(bootSalaryComponentsPage, 50);
        return;
    }

    const $ = window.jQuery;

    $(function () {

        const canManage = @json(auth()->user()->can('payroll.manage'));

        const urls = {
            data:
                @json(route('app.payroll.salary-components.data')),

            options:
                @json(route('app.payroll.salary-components.options')),

            store:
                @json(route('app.payroll.salary-components.store')),

            show:
                @json(
                    route(
                        'app.payroll.salary-components.show',
                        ['salaryComponent' => '__ID__']
                    )
                ),

            update:
                @json(
                    route(
                        'app.payroll.salary-components.update',
                        ['salaryComponent' => '__ID__']
                    )
                ),

            destroy:
                @json(
                    route(
                        'app.payroll.salary-components.destroy',
                        ['salaryComponent' => '__ID__']
                    )
                ),

            restore:
                @json(
                    route(
                        'app.payroll.salary-components.restore',
                        ['salaryComponent' => '__ID__']
                    )
                )
        };


        const state = {
            page: 1,
            lastPage: 1,
            pendingAction: null
        };


        const typeLabels = {
            earning: 'استحقاق',
            deduction: 'استقطاع'
        };


        const categoryLabels = {
            basic_salary: 'راتب أساسي',
            allowance: 'بدل',
            bonus: 'مكافأة',
            commission: 'عمولة',
            overtime: 'ساعات إضافية',
            reimbursement: 'تعويض مصروفات',
            tax: 'ضريبة',
            insurance: 'تأمينات',
            loan: 'قرض أو سلفة',
            absence: 'غياب',
            penalty: 'جزاء',
            other: 'أخرى'
        };


        const methodLabels = {
            fixed: 'مبلغ ثابت',
            percentage: 'نسبة مئوية',
            formula: 'معادلة',
            quantity_rate: 'كمية × سعر'
        };


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


        function money(value) {

            const number = Number(value ?? 0);

            if (!Number.isFinite(number)) {
                return '0.00';
            }

            return new Intl.NumberFormat(
                'ar-SA',
                {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }
            ).format(number);
        }


        function routeUrl(template, id) {

            return template.replace(
                '__ID__',
                encodeURIComponent(id)
            );
        }


        function csrfToken() {

            return $('meta[name="csrf-token"]')
                .attr('content') ?? '';
        }


        function showPageAlert(message, type) {

            const $alert = $('#pageAlert');

            $alert
                .removeClass(
                    'alert-success alert-danger alert-warning alert-info'
                )
                .addClass('alert-' + (type ?? 'success'))
                .html(escapeHtml(message))
                .stop(true, true)
                .slideDown(150);

            window.setTimeout(function () {
                $alert.slideUp(150);
            }, 5000);
        }


        function showFormAlert(message) {

            $('#formAlert')
                .removeClass('d-none')
                .html(escapeHtml(message));
        }


        function clearFormErrors() {

            $('#formAlert')
                .addClass('d-none')
                .empty();

            $('#componentForm')
                .find('.is-invalid')
                .removeClass('is-invalid');

            $('#componentForm')
                .find('[data-error-for]')
                .empty();
        }


        function showValidationErrors(errors) {

            clearFormErrors();

            let firstMessage = null;

            $.each(errors ?? {}, function (field, messages) {

                const message = Array.isArray(messages)
                    ? messages[0]
                    : messages;

                if (!firstMessage) {
                    firstMessage = message;
                }

                const aliases = {
                    fixed_amount: 'default_amount',
                    percentage_value: 'percentage_rate',
                    formula: 'formula_expression',
                    rate_amount: 'unit_rate',
                    quantity_default: 'default_quantity'
                };

                const errorField = aliases[field] ?? field;

                const $feedback = $(
                    '[data-error-for="' + errorField + '"]'
                );

                $feedback.text(message);

                $feedback
                    .closest('.col-lg-4, .col-md-6, .method-section')
                    .find('input, select, textarea')
                    .first()
                    .addClass('is-invalid');
            });

            if (firstMessage) {
                showFormAlert(firstMessage);
            }
        }


        function ajaxError(xhr, formMode) {

            const response = xhr.responseJSON ?? {};

            if (
                formMode &&
                xhr.status === 422 &&
                response.errors
            ) {
                showValidationErrors(response.errors);
                return;
            }

            const message =
                response.message ??
                'حدث خطأ أثناء تنفيذ العملية.';

            if (formMode) {
                showFormAlert(message);
            } else {
                showPageAlert(message, 'danger');
            }
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

            $modal.hide()
                .attr('aria-hidden', 'true');

            if ($('.ry-modal:visible').length === 0) {
                $('body').removeClass('ry-modal-open');
            }
        }


        function resetComponentForm() {

            $('#componentForm')[0].reset();

            $('#componentId').val('');
            $('#componentCode').prop('readonly', false);

            $('#componentType').val('earning');
            $('#componentCategory').val('allowance');
            $('#calculationMethod').val('fixed');

            $('#sortOrder').val(0);

            $('#isProratable').prop('checked', true);
            $('#isRecurring').prop('checked', true);
            $('#affectsNet').prop('checked', true);
            $('#isActive').prop('checked', true);

            clearFormErrors();
            toggleMethodSections();
        }


        function toggleMethodSections() {

            const method = $('#calculationMethod').val();

            $('.method-section').hide();

            if (method === 'fixed') {
                $('#fixedMethodSection').show();
            }

            if (method === 'percentage') {
                $('#percentageMethodSection').show();
            }

            if (method === 'formula') {
                $('#formulaMethodSection').show();
            }

            if (method === 'quantity_rate') {
                $('#quantityRateMethodSection').show();
            }
        }


        function valueFrom(item, names, fallback) {

            let result = fallback;

            $.each(names, function (_, name) {

                if (
                    item &&
                    item[name] !== undefined &&
                    item[name] !== null
                ) {
                    result = item[name];
                    return false;
                }
            });

            return result;
        }


        function loadOptions(selectedId) {

            return $.ajax({
                url: urls.options,
                method: 'GET',
                dataType: 'json'
            }).done(function (response) {

                const payload = response.data ?? response;

                const components =
                    payload.base_components ??
                    payload.percentage_base_components ??
                    payload.components ??
                    payload.salary_components ??
                    [];

                const currentId = String(
                    $('#componentId').val() ?? ''
                );

                const $select =
                    $('#percentageBaseComponent');

                $select.html(
                    '<option value="">اختر المكون الأساسي</option>'
                );

                $.each(components, function (_, component) {

                    if (
                        currentId !== '' &&
                        String(component.id) === currentId
                    ) {
                        return;
                    }

                    const label =
                        component.name +
                        ' (' +
                        component.code +
                        ')';

                    $('<option>')
                        .val(component.id)
                        .text(label)
                        .appendTo($select);
                });

                if (selectedId) {
                    $select.val(String(selectedId));
                }

            }).fail(function (xhr) {
                ajaxError(xhr, true);
            });
        }


        function renderValue(item) {

            const method = item.calculation_method;

            if (method === 'fixed') {

                return money(
                    valueFrom(
                        item,
                        ['default_amount', 'fixed_amount'],
                        0
                    )
                );
            }

            if (method === 'percentage') {

                return money(
                    valueFrom(
                        item,
                        ['percentage_rate', 'percentage_value', 'percentage'],
                        0
                    )
                ) + '%';
            }

            if (method === 'formula') {

                return '<span dir="ltr">' +
                    escapeHtml(
                        valueFrom(
                            item,
                            ['formula_expression', 'formula'],
                            '-'
                        )
                    ) +
                    '</span>';
            }

            if (method === 'quantity_rate') {

                return money(
                    valueFrom(
                        item,
                        ['unit_rate', 'rate_amount', 'quantity_rate'],
                        0
                    )
                ) + ' / وحدة';
            }

            return '-';
        }


        function renderActions(item) {

            if (!canManage) {
                return '<span class="text-muted">—</span>';
            }

            if (item.deleted_at) {

                return `
                    <button type="button"
                            class="btn btn-sm btn-outline-success btn-restore"
                            data-id="${item.id}"
                            data-name="${escapeHtml(item.name)}">
                        استعادة
                    </button>
                `;
            }

            if (normalizeBoolean(item.is_system)) {

                return `
                    <span class="badge bg-secondary-subtle text-secondary">
                        مكون نظام
                    </span>
                `;
            }

            return `
                <button type="button"
                        class="btn btn-sm btn-outline-primary btn-edit"
                        data-id="${item.id}">
                    تعديل
                </button>

                <button type="button"
                        class="btn btn-sm btn-outline-danger btn-archive"
                        data-id="${item.id}"
                        data-name="${escapeHtml(item.name)}">
                    أرشفة
                </button>
            `;
        }


        function renderRows(items, from) {

            const $body = $('#componentsTableBody');

            if (!items.length) {

                $body.html(`
                    <tr>
                        <td colspan="8"
                            class="salary-empty">
                            لا توجد مكونات راتب مطابقة للبحث.
                        </td>
                    </tr>
                `);

                return;
            }

            let html = '';

            $.each(items, function (index, item) {

                const typeClass =
                    item.type === 'earning'
                        ? 'success'
                        : 'danger';

                const statusHtml = item.deleted_at
                    ? `
                        <span class="badge bg-secondary-subtle text-secondary">
                            مؤرشف
                        </span>
                    `
                    : normalizeBoolean(item.is_active)
                        ? `
                            <span class="badge bg-success-subtle text-success">
                                نشط
                            </span>
                        `
                        : `
                            <span class="badge bg-warning-subtle text-warning">
                                غير نشط
                            </span>
                        `;

                const systemBadge =
                    normalizeBoolean(item.is_system)
                        ? `
                            <span class="badge bg-primary-subtle text-primary me-1">
                                نظام
                            </span>
                        `
                        : '';

                html += `
                    <tr class="${normalizeBoolean(item.is_system) ? 'salary-system-row' : ''}">

                        <td>
                            ${Number(from ?? 1) + index}
                        </td>

                        <td>
                            <div class="fw-bold mb-1">
                                ${escapeHtml(item.name)}
                                ${systemBadge}
                            </div>

                            <small class="text-muted salary-code">
                                ${escapeHtml(item.code)}
                            </small>
                        </td>

                        <td>
                            <span class="badge bg-${typeClass}-subtle text-${typeClass}">
                                ${escapeHtml(
                                    item.type_label ??
                                    typeLabels[item.type] ??
                                    item.type
                                )}
                            </span>
                        </td>

                        <td>
                            ${escapeHtml(
                                item.category_label ??
                                categoryLabels[item.category] ??
                                item.category
                            )}
                        </td>

                        <td>
                            ${escapeHtml(
                                item.calculation_method_label ??
                                methodLabels[item.calculation_method] ??
                                item.calculation_method
                            )}
                        </td>

                        <td>
                            ${renderValue(item)}
                        </td>

                        <td>
                            ${statusHtml}
                        </td>

                        <td>
                            <div class="salary-actions">
                                ${renderActions(item)}
                            </div>
                        </td>

                    </tr>
                `;
            });

            $body.html(html);
        }


        function renderPagination(paginator) {

            const currentPage =
                Number(paginator.current_page ?? 1);

            const lastPage =
                Number(paginator.last_page ?? 1);

            state.page = currentPage;
            state.lastPage = lastPage;

            const from = paginator.from ?? 0;
            const to = paginator.to ?? 0;
            const total = paginator.total ?? 0;

            $('#recordsCount')
                .text(total + ' مكون');

            $('#recordsSummary')
                .text('إجمالي السجلات: ' + total);

            $('#paginationSummary')
                .text(
                    total
                        ? 'عرض ' + from + ' إلى ' + to + ' من ' + total
                        : 'لا توجد سجلات'
                );

            let buttons = '';

            buttons += `
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

            for (let page = start; page <= end; page++) {

                buttons += `
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

            buttons += `
                <button type="button"
                        class="btn btn-sm btn-light border btn-page"
                        data-page="${currentPage + 1}"
                        ${currentPage >= lastPage ? 'disabled' : ''}>
                    التالي
                </button>
            `;

            $('#paginationButtons').html(buttons);
        }


        function loadData(page) {

            page = page ?? 1;

            $('#componentsTableBody').html(`
                <tr>
                    <td colspan="8"
                        class="salary-loading">
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
                    search: $('#filterSearch').val(),
                    type: $('#filterType').val(),
                    calculation_method:
                        $('#filterMethod').val(),
                    status: $('#filterStatus').val(),
                    per_page: $('#filterPerPage').val()
                }

            }).done(function (response) {

                let paginator = response;

                if (
                    response.data &&
                    !Array.isArray(response.data) &&
                    Array.isArray(response.data.data)
                ) {
                    paginator = response.data;
                }

                const items =
                    Array.isArray(paginator.data)
                        ? paginator.data
                        : [];

                renderRows(
                    items,
                    paginator.from ?? 1
                );

                renderPagination(paginator);

            }).fail(function (xhr) {

                $('#componentsTableBody').html(`
                    <tr>
                        <td colspan="8"
                            class="salary-empty text-danger">
                            تعذر تحميل مكونات الراتب.
                        </td>
                    </tr>
                `);

                ajaxError(xhr, false);
            });
        }


        function appendAliases(
            formData,
            names,
            value
        ) {

            $.each(names, function (_, name) {
                formData.append(name, value);
            });
        }


        function buildFormData() {

            const formData = new FormData();

            formData.append(
                '_token',
                csrfToken()
            );

            formData.append(
                'code',
                $.trim($('#componentCode').val()).toUpperCase()
            );

            formData.append(
                'name',
                $.trim($('#componentName').val())
            );

            formData.append(
                'name_en',
                $.trim($('#componentNameEn').val())
            );

            formData.append(
                'description',
                $.trim($('#componentDescription').val())
            );

            formData.append(
                'type',
                $('#componentType').val()
            );

            formData.append(
                'category',
                $('#componentCategory').val()
            );

            formData.append(
                'calculation_method',
                $('#calculationMethod').val()
            );

            appendAliases(
                formData,
                ['default_amount', 'fixed_amount'],
                $('#defaultAmount').val()
            );

            appendAliases(
                formData,
                [
                    'percentage_rate',
                    'percentage_value',
                    'percentage'
                ],
                $('#percentageRate').val()
            );

            formData.append(
                'percentage_base_component_id',
                $('#percentageBaseComponent').val()
            );

            appendAliases(
                formData,
                ['formula_expression', 'formula'],
                $.trim($('#formulaExpression').val())
            );

            appendAliases(
                formData,
                ['default_quantity', 'quantity_default'],
                $('#defaultQuantity').val()
            );

            appendAliases(
                formData,
                ['unit_rate', 'rate_amount', 'quantity_rate'],
                $('#unitRate').val()
            );

            formData.append(
                'sort_order',
                $('#sortOrder').val() || 0
            );

            appendAliases(
                formData,
                ['is_taxable'],
                $('#isTaxable').is(':checked') ? 1 : 0
            );

            appendAliases(
                formData,
                [
                    'is_insurable',
                    'is_insurance_eligible',
                    'is_social_insurance_subject'
                ],
                $('#isInsurable').is(':checked') ? 1 : 0
            );

            appendAliases(
                formData,
                [
                    'is_overtime_base',
                    'included_in_overtime_base'
                ],
                $('#isOvertimeBase').is(':checked') ? 1 : 0
            );

            appendAliases(
                formData,
                ['is_proratable', 'is_prorated'],
                $('#isProratable').is(':checked') ? 1 : 0
            );

            formData.append(
                'is_recurring',
                $('#isRecurring').is(':checked') ? 1 : 0
            );

            appendAliases(
                formData,
                ['requires_input', 'requires_manual_input'],
                $('#requiresInput').is(':checked') ? 1 : 0
            );

            appendAliases(
                formData,
                ['affects_net', 'affects_net_salary'],
                $('#affectsNet').is(':checked') ? 1 : 0
            );

            formData.append(
                'is_active',
                $('#isActive').is(':checked') ? 1 : 0
            );

            return formData;
        }


        function fillComponentForm(item) {

            $('#componentId').val(item.id);

            $('#componentCode')
                .val(item.code)
                .prop(
                    'readonly',
                    normalizeBoolean(item.is_system)
                );

            $('#componentName').val(item.name ?? '');
            $('#componentNameEn').val(item.name_en ?? '');

            $('#componentDescription')
                .val(item.description ?? '');

            $('#componentType').val(item.type);
            $('#componentCategory').val(item.category);

            $('#calculationMethod')
                .val(item.calculation_method);

            $('#defaultAmount').val(
                valueFrom(
                    item,
                    ['default_amount', 'fixed_amount'],
                    ''
                )
            );

            $('#percentageRate').val(
                valueFrom(
                    item,
                    [
                        'percentage_rate',
                        'percentage_value',
                        'percentage'
                    ],
                    ''
                )
            );

            $('#formulaExpression').val(
                valueFrom(
                    item,
                    ['formula_expression', 'formula'],
                    ''
                )
            );

            $('#defaultQuantity').val(
                valueFrom(
                    item,
                    ['default_quantity', 'quantity_default'],
                    ''
                )
            );

            $('#unitRate').val(
                valueFrom(
                    item,
                    ['unit_rate', 'rate_amount', 'quantity_rate'],
                    ''
                )
            );

            $('#sortOrder').val(item.sort_order ?? 0);

            $('#isTaxable').prop(
                'checked',
                normalizeBoolean(item.is_taxable)
            );

            $('#isInsurable').prop(
                'checked',
                normalizeBoolean(
                    valueFrom(
                        item,
                        [
                            'is_insurable',
                            'is_insurance_eligible',
                            'is_social_insurance_subject'
                        ],
                        false
                    )
                )
            );

            $('#isOvertimeBase').prop(
                'checked',
                normalizeBoolean(
                    valueFrom(
                        item,
                        [
                            'is_overtime_base',
                            'included_in_overtime_base'
                        ],
                        false
                    )
                )
            );

            $('#isProratable').prop(
                'checked',
                normalizeBoolean(
                    valueFrom(
                        item,
                        ['is_proratable', 'is_prorated'],
                        false
                    )
                )
            );

            $('#isRecurring').prop(
                'checked',
                normalizeBoolean(item.is_recurring)
            );

            $('#requiresInput').prop(
                'checked',
                normalizeBoolean(
                    valueFrom(
                        item,
                        ['requires_input', 'requires_manual_input'],
                        false
                    )
                )
            );

            $('#affectsNet').prop(
                'checked',
                normalizeBoolean(
                    valueFrom(
                        item,
                        ['affects_net', 'affects_net_salary'],
                        true
                    )
                )
            );

            $('#isActive').prop(
                'checked',
                normalizeBoolean(item.is_active)
            );

            toggleMethodSections();

            return loadOptions(
                item.percentage_base_component_id
            );
        }


        function openConfirm(
            title,
            message,
            buttonText,
            buttonClass,
            callback
        ) {

            state.pendingAction = callback;

            $('#confirmTitle').text(title);
            $('#confirmMessage').text(message);

            $('#btnConfirmAction')
                .removeClass(
                    'btn-danger btn-success btn-warning btn-primary'
                )
                .addClass(buttonClass)
                .text(buttonText);

            openModal($('#confirmModal'));
        }


        $('#calculationMethod')
            .on('change', toggleMethodSections);


        $('#filterForm').on('submit', function (event) {

            event.preventDefault();
            loadData(1);
        });


        $('#filterPerPage, #filterType, #filterMethod, #filterStatus')
            .on('change', function () {
                loadData(1);
            });


        $('#btnResetFilters').on('click', function () {

            $('#filterSearch').val('');
            $('#filterType').val('');
            $('#filterMethod').val('');
            $('#filterStatus').val('active');
            $('#filterPerPage').val('15');

            loadData(1);
        });


        $(document).on('click', '.btn-page', function () {

            if ($(this).is(':disabled')) {
                return;
            }

            loadData(
                Number($(this).data('page'))
            );
        });


        $('#btnCreateComponent').on('click', function () {

            resetComponentForm();

            $('#componentModalTitle')
                .text('إضافة مكون راتب');

            $('#btnSaveComponent')
                .text('حفظ البيانات');

            openModal($('#componentModal'));
            loadOptions();
        });


        $(document).on('click', '.btn-edit', function () {

            const id = $(this).data('id');

            resetComponentForm();

            $('#componentModalTitle')
                .text('تعديل مكون الراتب');

            $('#btnSaveComponent')
                .text('حفظ التعديلات');

            openModal($('#componentModal'));

            $('#btnSaveComponent')
                .prop('disabled', true)
                .text('جاري التحميل...');

            $.ajax({
                url: routeUrl(urls.show, id),
                method: 'GET',
                dataType: 'json'

            }).done(function (response) {

                const item =
                    response.salary_component ??
                    response.component ??
                    response.data ??
                    response;

                fillComponentForm(item);

            }).fail(function (xhr) {

                closeModal($('#componentModal'));
                ajaxError(xhr, false);

            }).always(function () {

                $('#btnSaveComponent')
                    .prop('disabled', false)
                    .text('حفظ التعديلات');
            });
        });


        $('.btn-close-component').on('click', function () {
            closeModal($('#componentModal'));
        });


        $('.btn-close-confirm').on('click', function () {
            state.pendingAction = null;
            closeModal($('#confirmModal'));
        });


        $('.ry-modal').on('click', function (event) {

            if (event.target === this) {
                closeModal($(this));
            }
        });


        $('#componentForm').on('submit', function (event) {

            event.preventDefault();

            clearFormErrors();

            const id = $('#componentId').val();
            const isEdit = id !== '';

            const formData = buildFormData();

            if (isEdit) {
                formData.append('_method', 'PUT');
            }

            const $button = $('#btnSaveComponent');

            $button
                .prop('disabled', true)
                .text('جاري الحفظ...');

            $.ajax({
                url: isEdit
                    ? routeUrl(urls.update, id)
                    : urls.store,

                method: 'POST',
                data: formData,
                processData: false,
                contentType: false

            }).done(function (response) {

                closeModal($('#componentModal'));

                showPageAlert(
                    response.message ??
                    (
                        isEdit
                            ? 'تم تحديث مكون الراتب بنجاح.'
                            : 'تم إنشاء مكون الراتب بنجاح.'
                    ),
                    'success'
                );

                loadData(
                    isEdit ? state.page : 1
                );

            }).fail(function (xhr) {
                ajaxError(xhr, true);

            }).always(function () {

                $button
                    .prop('disabled', false)
                    .text(
                        isEdit
                            ? 'حفظ التعديلات'
                            : 'حفظ البيانات'
                    );
            });
        });


        $(document).on('click', '.btn-archive', function () {

            const id = $(this).data('id');
            const name = $(this).data('name');

            openConfirm(
                'أرشفة مكون الراتب',
                'هل تريد أرشفة مكون الراتب "' + name + '"؟',
                'أرشفة',
                'btn-danger',
                function () {

                    return $.ajax({
                        url: routeUrl(urls.destroy, id),
                        method: 'POST',

                        data: {
                            _token: csrfToken(),
                            _method: 'DELETE'
                        }

                    }).done(function (response) {

                        closeModal($('#confirmModal'));

                        showPageAlert(
                            response.message ??
                            'تمت أرشفة مكون الراتب بنجاح.',
                            'success'
                        );

                        loadData(state.page);

                    }).fail(function (xhr) {
                        ajaxError(xhr, false);
                    });
                }
            );
        });


        $(document).on('click', '.btn-restore', function () {

            const id = $(this).data('id');
            const name = $(this).data('name');

            openConfirm(
                'استعادة مكون الراتب',
                'هل تريد استعادة مكون الراتب "' + name + '"؟',
                'استعادة',
                'btn-success',
                function () {

                    return $.ajax({
                        url: routeUrl(urls.restore, id),
                        method: 'POST',

                        data: {
                            _token: csrfToken()
                        }

                    }).done(function (response) {

                        closeModal($('#confirmModal'));

                        showPageAlert(
                            response.message ??
                            'تمت استعادة مكون الراتب بنجاح.',
                            'success'
                        );

                        loadData(state.page);

                    }).fail(function (xhr) {
                        ajaxError(xhr, false);
                    });
                }
            );
        });


        $('#btnConfirmAction').on('click', function () {

            if (
                typeof state.pendingAction !== 'function'
            ) {
                return;
            }

            const callback = state.pendingAction;
            state.pendingAction = null;

            const $button = $(this);
            const originalText = $button.text();

            $button
                .prop('disabled', true)
                .text('جاري التنفيذ...');

            const request = callback();

            if (
                request &&
                typeof request.always === 'function'
            ) {
                request.always(function () {

                    $button
                        .prop('disabled', false)
                        .text(originalText);
                });

                return;
            }

            $button
                .prop('disabled', false)
                .text(originalText);
        });


        toggleMethodSections();
        loadData(1);

    });

})();
</script>

@endpush