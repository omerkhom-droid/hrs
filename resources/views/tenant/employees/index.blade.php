@extends('layouts.tenant')

@section('title', 'إدارة الموظفين')
@section('page-title', 'إدارة الموظفين')

@php
    $tenantTimezone = auth()->user()->tenant?->timezone
        ?: 'Asia/Riyadh';

    $employeeTimezones = [
        'Asia/Riyadh' => 'السعودية — الرياض',
        'Asia/Kuwait' => 'الكويت',
        'Asia/Bahrain' => 'البحرين',
        'Asia/Qatar' => 'قطر',
        'Asia/Dubai' => 'الإمارات — دبي',
        'Asia/Muscat' => 'عُمان — مسقط',
        'Asia/Baghdad' => 'العراق — بغداد',
        'Asia/Amman' => 'الأردن — عمّان',
        'Asia/Beirut' => 'لبنان — بيروت',
        'Asia/Damascus' => 'سوريا — دمشق',
        'Asia/Aden' => 'اليمن — عدن',
        'Africa/Cairo' => 'مصر — القاهرة',
        'Africa/Khartoum' => 'السودان — الخرطوم',
        'Africa/Tripoli' => 'ليبيا — طرابلس',
        'Africa/Tunis' => 'تونس',
        'Africa/Algiers' => 'الجزائر',
        'Africa/Casablanca' => 'المغرب — الدار البيضاء',
        'Africa/Mogadishu' => 'الصومال — مقديشو',
        'Europe/Istanbul' => 'تركيا — إسطنبول',
        'Europe/London' => 'بريطانيا — لندن',
        'Europe/Paris' => 'فرنسا — باريس',
        'UTC' => 'التوقيت العالمي UTC',
    ];
@endphp

@push('styles')
<style>
    .emp-page {
        --emp-primary: #0d6efd;
        --emp-border: #e5eaf1;
        --emp-muted: #6c757d;
    }

    .emp-card {
        background: #fff;
        border: 1px solid var(--emp-border);
        border-radius: 16px;
        box-shadow: 0 6px 22px rgba(16, 40, 80, .05);
    }

    .emp-filter-label {
        display: block;
        margin-bottom: 7px;
        color: #495057;
        font-size: .82rem;
        font-weight: 600;
    }

    .emp-avatar {
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
        border-radius: 12px;
        object-fit: cover;
        background: #eaf2ff;
        color: var(--emp-primary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
    }

    .emp-table > :not(caption) > * > * {
        padding: .9rem .75rem;
        vertical-align: middle;
        border-color: #edf0f4;
        white-space: nowrap;
    }

    .emp-table thead th {
        color: #495057;
        background: #f8fafc;
        font-size: .83rem;
        font-weight: 700;
    }

    .emp-status {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 5px 10px;
        font-size: .76rem;
        font-weight: 700;
    }

    .emp-status-active { background: #dff6ea; color: #087443; }
    .emp-status-probation { background: #fff3cd; color: #856404; }
    .emp-status-leave { background: #e7f1ff; color: #0b5ed7; }
    .emp-status-suspended { background: #ffe5d0; color: #a84600; }
    .emp-status-terminated { background: #fde2e4; color: #a61b29; }
    .emp-status-draft { background: #e9ecef; color: #495057; }

    .emp-empty {
        padding: 60px 20px !important;
        text-align: center;
        color: var(--emp-muted);
    }

    .emp-loading {
        padding: 50px 20px !important;
        text-align: center;
        color: var(--emp-muted);
    }

    .emp-modal-open {
        overflow: hidden !important;
    }

    .emp-modal-overlay {
        position: fixed;
        inset: 0;
        z-index: 1080;
        display: none;
        overflow: hidden;
        padding: 16px;
        background: rgba(7, 20, 43, .58);
    }

    .emp-modal-dialog {
        width: min(1180px, 100%);
        height: calc(100vh - 32px);
        max-height: calc(100vh - 32px);
        margin: 0 auto;
        display: flex;
        align-items: stretch;
    }

    .emp-modal-dialog.emp-modal-sm {
        width: min(760px, 100%);
    }

    .emp-modal-content {
        width: 100%;
        height: 100%;
        max-height: 100%;
        min-height: 0;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 24px 70px rgba(0, 0, 0, .24);
    }

    #employeeModal #employeeForm {
        width: 100%;
        min-height: 0;
        flex: 1 1 auto;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .emp-modal-header,
    .emp-modal-footer {
        flex: 0 0 auto;
        padding: 16px 20px;
        background: #fff;
    }

    .emp-modal-header {
        border-bottom: 1px solid var(--emp-border);
    }

    .emp-modal-footer {
        border-top: 1px solid var(--emp-border);
    }

    .emp-modal-body {
        min-height: 0;
        flex: 1 1 auto;
        overflow-y: auto !important;
        overscroll-behavior: contain;
        scrollbar-gutter: stable;
        -webkit-overflow-scrolling: touch;
        touch-action: pan-y;
        padding: 20px;
        background: #f8fafc;
    }

    .emp-modal-close {
        border: 0;
        background: transparent;
        color: #6c757d;
        font-size: 1.7rem;
        line-height: 1;
    }

    .emp-section {
        padding: 18px;
        margin-bottom: 16px;
        background: #fff;
        border: 1px solid var(--emp-border);
        border-radius: 14px;
    }

    .emp-section-title {
        margin-bottom: 16px;
        color: #172b4d;
        font-size: .98rem;
        font-weight: 700;
    }

    .emp-form-label {
        margin-bottom: 6px;
        color: #495057;
        font-size: .82rem;
        font-weight: 600;
    }

    .emp-required::after {
        content: ' *';
        color: #dc3545;
    }

    .emp-photo-preview {
        width: 96px;
        height: 96px;
        border: 1px dashed #b9c5d4;
        border-radius: 16px;
        object-fit: cover;
        background: #f8fafc;
    }

    .emp-detail-item {
        height: 100%;
        padding: 13px;
        background: #fff;
        border: 1px solid var(--emp-border);
        border-radius: 12px;
    }

    .emp-detail-label {
        margin-bottom: 5px;
        color: var(--emp-muted);
        font-size: .76rem;
    }

    .emp-pagination .page-link {
        min-width: 36px;
        text-align: center;
        border-radius: 8px !important;
        margin: 0 2px;
    }

    .emp-toast-container {
        position: fixed;
        z-index: 1200;
        top: 20px;
        left: 20px;
        width: min(390px, calc(100% - 40px));
    }

    .emp-toast {
        display: none;
        margin-bottom: 10px;
        padding: 13px 15px;
        color: #fff;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .18);
    }

    @media (max-width: 767.98px) {
        .emp-modal-overlay { padding: 0; }
        .emp-modal-dialog {
            width: 100%;
            height: 100vh;
            height: 100dvh;
            max-height: 100vh;
            max-height: 100dvh;
        }
        .emp-modal-content { border-radius: 0; }
        .emp-modal-body { padding: 12px; }
        .emp-section { padding: 14px; }
    }
</style>
@endpush

@section('content')
<div class="emp-page" id="employeesPage">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-1">الموظفون</h4>
            <div class="text-muted small">
                إدارة الملفات الوظيفية والربط بالهيكل التنظيمي وحسابات الدخول
            </div>
        </div>

        @can('employees.create')
            <button type="button" class="btn btn-primary px-4" id="btnAddEmployee">
                إضافة موظف جديد
            </button>
        @endcan
    </div>

    <div class="emp-card p-3 mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-xl-3 col-md-6">
                <label class="emp-filter-label" for="filterSearch">البحث</label>
                <input type="search"
                       class="form-control"
                       id="filterSearch"
                       placeholder="الاسم، الرقم، الهوية، الجوال أو البريد">
            </div>

            <div class="col-xl-2 col-md-6">
                <label class="emp-filter-label" for="filterBranch">الفرع</label>
                <select class="form-select" id="filterBranch">
                    <option value="">جميع الفروع</option>
                </select>
            </div>

            <div class="col-xl-2 col-md-6">
                <label class="emp-filter-label" for="filterDepartment">الإدارة</label>
                <select class="form-select" id="filterDepartment">
                    <option value="">جميع الإدارات</option>
                </select>
            </div>

            <div class="col-xl-2 col-md-6">
                <label class="emp-filter-label" for="filterStatus">الحالة</label>
                <select class="form-select" id="filterStatus">
                    <option value="">جميع الحالات</option>
                    <option value="draft">مسودة</option>
                    <option value="probation">فترة تجربة</option>
                    <option value="active">على رأس العمل</option>
                    <option value="on_leave">في إجازة</option>
                    <option value="suspended">موقوف</option>
                    <option value="terminated">منتهي الخدمة</option>
                </select>
            </div>

            <div class="col-xl-2 col-md-6">
                <label class="emp-filter-label" for="filterType">نوع التوظيف</label>
                <select class="form-select" id="filterType">
                    <option value="">جميع الأنواع</option>
                    <option value="full_time">دوام كامل</option>
                    <option value="part_time">دوام جزئي</option>
                    <option value="contract">عقد</option>
                    <option value="temporary">مؤقت</option>
                    <option value="intern">متدرب</option>
                    <option value="consultant">مستشار</option>
                </select>
            </div>

            <div class="col-xl-2 col-md-6">
                <label class="emp-filter-label" for="filterArchive">السجلات</label>
                <select class="form-select" id="filterArchive">
                    <option value="active">الحالية فقط</option>
                    <option value="only">المؤرشفة فقط</option>
                    <option value="with">الحالية والمؤرشفة</option>
                </select>
            </div>

            <div class="col-xl-1 col-md-6">
                <label class="emp-filter-label" for="filterPerPage">العدد</label>
                <select class="form-select" id="filterPerPage">
                    <option value="10">10</option>
                    <option value="15" selected>15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>

            <div class="col-12 d-flex gap-2 justify-content-end">
                <button type="button" class="btn btn-primary px-4" id="btnSearchEmployees">
                    بحث
                </button>
                <button type="button" class="btn btn-outline-secondary" id="btnResetEmployees">
                    إعادة ضبط
                </button>
            </div>
        </div>
    </div>

    <div class="emp-card overflow-hidden">
        <div class="d-flex justify-content-between align-items-center px-3 py-3 border-bottom">
            <h6 class="mb-0">قائمة الموظفين</h6>
            <span class="badge bg-primary-subtle text-primary" id="employeesCount">0 موظف</span>
        </div>

        <div class="table-responsive">
            <table class="table emp-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الموظف</th>
                        <th>الهيكل التنظيمي</th>
                        <th>تاريخ التعيين</th>
                        <th>نوع التوظيف</th>
                        <th>الحالة</th>
                        <th>حساب الدخول</th>
                        <th class="text-center">الإجراءات</th>
                    </tr>
                </thead>
                <tbody id="employeesTableBody">
                    <tr>
                        <td colspan="8" class="emp-loading">جاري تحميل الموظفين...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 p-3 border-top">
            <div class="small text-muted" id="employeesRange">لا توجد نتائج</div>
            <nav aria-label="صفحات الموظفين">
                <ul class="pagination pagination-sm emp-pagination mb-0" id="employeesPagination"></ul>
            </nav>
        </div>
    </div>
</div>

<div class="emp-toast-container" id="employeeToastContainer"></div>

{{-- نافذة إضافة وتعديل الموظف --}}
<div class="emp-modal-overlay" id="employeeModal" aria-hidden="true">
    <div class="emp-modal-dialog">
        <div class="emp-modal-content" role="dialog" aria-modal="true" aria-labelledby="employeeModalTitle">
            <div class="emp-modal-header d-flex justify-content-between align-items-center gap-3">
                <div>
                    <h5 class="mb-1" id="employeeModalTitle">إضافة موظف جديد</h5>
                    <div class="text-muted small">أدخل البيانات الأساسية والتنظيمية للموظف</div>
                </div>
                <button type="button" class="emp-modal-close js-close-employee-modal" aria-label="إغلاق">&times;</button>
            </div>

            <form id="employeeForm" enctype="multipart/form-data" novalidate>
                <input type="hidden" id="employeeId" value="">

                <div class="emp-modal-body">
                    <div class="alert alert-danger d-none" id="employeeFormErrors"></div>

                    <div class="emp-section">
                        <div class="emp-section-title">البيانات الوظيفية والتنظيمية</div>
                        <div class="row g-3">
                            <div class="col-lg-3 col-md-6">
                                <label class="emp-form-label emp-required">الرقم الوظيفي</label>
                                <input type="text" class="form-control" name="employee_number" maxlength="50">
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <label class="emp-form-label">كود الحضور</label>
                                <input type="text" class="form-control" name="attendance_code" maxlength="50">
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <label class="emp-form-label emp-required">نوع التوظيف</label>
                                <select class="form-select" name="employment_type">
                                    <option value="full_time">دوام كامل</option>
                                    <option value="part_time">دوام جزئي</option>
                                    <option value="contract">عقد</option>
                                    <option value="temporary">مؤقت</option>
                                    <option value="intern">متدرب</option>
                                    <option value="consultant">مستشار</option>
                                </select>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <label class="emp-form-label emp-required">الحالة الوظيفية</label>
                                <select class="form-select" name="employment_status" id="employmentStatus">
                                    <option value="draft">مسودة</option>
                                    <option value="probation">فترة تجربة</option>
                                    <option value="active">على رأس العمل</option>
                                    <option value="on_leave">في إجازة</option>
                                    <option value="suspended">موقوف</option>
                                    <option value="terminated">منتهي الخدمة</option>
                                </select>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <label class="emp-form-label">الفرع</label>
                                <select class="form-select" name="branch_id" id="employeeBranch"></select>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <label class="emp-form-label">الإدارة / القسم</label>
                                <select class="form-select" name="department_id" id="employeeDepartment"></select>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <label class="emp-form-label">المسمى الوظيفي</label>
                                <select class="form-select" name="job_title_id" id="employeeJobTitle"></select>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <label class="emp-form-label">موقع العمل</label>
                                <select class="form-select" name="work_location_id" id="employeeWorkLocation"></select>
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <label class="emp-form-label">المدير المباشر</label>
                                <select class="form-select" name="manager_id" id="employeeManager"></select>
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <label class="emp-form-label emp-required">تاريخ التعيين</label>
                                <input type="date" class="form-control" name="hire_date">
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <label class="emp-form-label">نهاية فترة التجربة</label>
                                <input type="date" class="form-control" name="probation_end_date">
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <label class="emp-form-label">تاريخ التثبيت</label>
                                <input type="date" class="form-control" name="confirmation_date">
                            </div>

                            <div class="col-lg-4 col-md-6 emp-termination-fields d-none">
                                <label class="emp-form-label emp-required">تاريخ انتهاء الخدمة</label>
                                <input type="date" class="form-control" name="termination_date">
                            </div>

                            <div class="col-12 emp-termination-fields d-none">
                                <label class="emp-form-label emp-required">سبب انتهاء الخدمة</label>
                                <textarea class="form-control" name="termination_reason" rows="2" maxlength="2000"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="emp-section">
                        <div class="emp-section-title">الاسم والهوية الشخصية</div>
                        <div class="row g-3">
                            <div class="col-lg-3 col-md-6">
                                <label class="emp-form-label emp-required">الاسم الأول</label>
                                <input type="text" class="form-control" name="first_name">
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <label class="emp-form-label">اسم الأب</label>
                                <input type="text" class="form-control" name="father_name">
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <label class="emp-form-label">اسم الجد</label>
                                <input type="text" class="form-control" name="grandfather_name">
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <label class="emp-form-label emp-required">اسم العائلة</label>
                                <input type="text" class="form-control" name="family_name">
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <label class="emp-form-label">الاسم بالإنجليزية</label>
                                <input type="text" class="form-control" name="name_en" dir="ltr">
                            </div>

                            <div class="col-lg-2 col-md-6">
                                <label class="emp-form-label">نوع الهوية</label>
                                <select class="form-select" name="identity_type">
                                    <option value="">غير محدد</option>
                                    <option value="national_id">هوية وطنية</option>
                                    <option value="iqama">إقامة</option>
                                    <option value="passport">جواز سفر</option>
                                    <option value="gcc">هوية خليجية</option>
                                    <option value="other">أخرى</option>
                                </select>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <label class="emp-form-label">رقم الهوية</label>
                                <input type="text" class="form-control" name="identity_number" maxlength="100" dir="ltr">
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <label class="emp-form-label">انتهاء الهوية</label>
                                <input type="date" class="form-control" name="identity_expiry_date">
                            </div>

                            <div class="col-lg-2 col-md-6">
                                <label class="emp-form-label">الجنسية</label>
                                <input type="text" class="form-control" name="nationality_code" maxlength="2" placeholder="SA" dir="ltr">
                            </div>

                            <div class="col-lg-2 col-md-6">
                                <label class="emp-form-label">الجنس</label>
                                <select class="form-select" name="gender">
                                    <option value="">غير محدد</option>
                                    <option value="male">ذكر</option>
                                    <option value="female">أنثى</option>
                                </select>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <label class="emp-form-label">تاريخ الميلاد</label>
                                <input type="date" class="form-control" name="birth_date">
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <label class="emp-form-label">الحالة الاجتماعية</label>
                                <select class="form-select" name="marital_status">
                                    <option value="">غير محدد</option>
                                    <option value="single">أعزب</option>
                                    <option value="married">متزوج</option>
                                    <option value="divorced">مطلق</option>
                                    <option value="widowed">أرمل</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="emp-section">
                        <div class="emp-section-title">التواصل والعنوان</div>
                        <div class="row g-3">
                            <div class="col-lg-3 col-md-6">
                                <label class="emp-form-label">البريد الشخصي</label>
                                <input type="email" class="form-control" name="personal_email" dir="ltr">
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <label class="emp-form-label">البريد الوظيفي</label>
                                <input type="email" class="form-control" name="work_email" dir="ltr">
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <label class="emp-form-label">الجوال الشخصي</label>
                                <input type="text" class="form-control" name="personal_phone" maxlength="50" dir="ltr">
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <label class="emp-form-label">هاتف العمل</label>
                                <input type="text" class="form-control" name="work_phone" maxlength="50" dir="ltr">
                            </div>

                            <div class="col-lg-2 col-md-6">
                                <label class="emp-form-label emp-required">الدولة</label>
                                <input type="text" class="form-control" name="country_code" maxlength="2" value="SA" dir="ltr">
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <label class="emp-form-label">المدينة</label>
                                <input type="text" class="form-control" name="city">
                            </div>

                            <div class="col-lg-6 col-md-12">
                                <label class="emp-form-label">العنوان</label>
                                <input type="text" class="form-control" name="address" maxlength="2000">
                            </div>
                        </div>
                    </div>

                    <div class="emp-section">
                        <div class="emp-section-title">جهة اتصال الطوارئ</div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="emp-form-label">الاسم</label>
                                <input type="text" class="form-control" name="emergency_contact_name">
                            </div>

                            <div class="col-md-4">
                                <label class="emp-form-label">صلة القرابة</label>
                                <input type="text" class="form-control" name="emergency_contact_relation" maxlength="100">
                            </div>

                            <div class="col-md-4">
                                <label class="emp-form-label">رقم التواصل</label>
                                <input type="text" class="form-control" name="emergency_contact_phone" maxlength="50" dir="ltr">
                            </div>
                        </div>
                    </div>

                    <div class="emp-section mb-0">
                        <div class="emp-section-title">حساب الدخول والصورة والملاحظات</div>
                        <div class="row g-3 align-items-start">
                            <div class="col-lg-4 col-md-6">
                                <label class="emp-form-label">ربط حساب مستخدم</label>
                                <select class="form-select" name="user_id" id="employeeUser"></select>
                                <div class="form-text">يظهر فقط المستخدمون غير المرتبطين بموظف آخر.</div>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <label class="emp-form-label">المنطقة الزمنية</label>
                                <select class="form-select" name="timezone" id="employeeTimezone">
                                    <option value="">
                                        نفس توقيت الشركة —
                                        {{ $employeeTimezones[$tenantTimezone] ?? $tenantTimezone }}
                                    </option>

                                    @if(!array_key_exists($tenantTimezone, $employeeTimezones))
                                        <option value="{{ $tenantTimezone }}">
                                            {{ $tenantTimezone }}
                                        </option>
                                    @endif

                                    @foreach($employeeTimezones as $timezoneValue => $timezoneLabel)
                                        <option value="{{ $timezoneValue }}">
                                            {{ $timezoneLabel }}
                                        </option>
                                    @endforeach
                                </select>

                                <div class="form-text">
                                    اتركها على توقيت الشركة إلا إذا كان الموظف يعمل من دولة أخرى.
                                </div>
                            </div>

                            <div class="col-lg-5 col-md-12">
                                <div class="d-flex align-items-center gap-3">
                                    <img class="emp-photo-preview" id="employeePhotoPreview"
                                         alt="صورة الموظف"
                                         src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='96' height='96'%3E%3Crect width='100%25' height='100%25' fill='%23f3f6fa'/%3E%3Ctext x='50%25' y='54%25' text-anchor='middle' font-size='13' fill='%23758396'%3Eالصورة%3C/text%3E%3C/svg%3E">
                                    <div class="flex-grow-1">
                                        <label class="emp-form-label">صورة الموظف</label>
                                        <input type="file" class="form-control" name="photo" id="employeePhoto" accept=".jpg,.jpeg,.png,.webp">
                                        <div class="form-check mt-2 d-none" id="removePhotoContainer">
                                            <input class="form-check-input" type="checkbox" name="remove_photo" value="1" id="removeEmployeePhoto">
                                            <label class="form-check-label small" for="removeEmployeePhoto">حذف الصورة الحالية</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="emp-form-label">ملاحظات</label>
                                <textarea class="form-control" name="notes" rows="3" maxlength="5000"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="emp-modal-footer d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary js-close-employee-modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary px-4" id="btnSaveEmployee">
                        حفظ البيانات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- نافذة تفاصيل الموظف --}}
<div class="emp-modal-overlay" id="employeeDetailsModal" aria-hidden="true">
    <div class="emp-modal-dialog emp-modal-sm">
        <div class="emp-modal-content" role="dialog" aria-modal="true" aria-labelledby="employeeDetailsTitle">
            <div class="emp-modal-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0" id="employeeDetailsTitle">تفاصيل الموظف</h5>
                <button type="button" class="emp-modal-close js-close-details-modal" aria-label="إغلاق">&times;</button>
            </div>
            <div class="emp-modal-body" id="employeeDetailsBody">
                <div class="text-center text-muted py-5">جاري تحميل البيانات...</div>
            </div>
            <div class="emp-modal-footer d-flex justify-content-end">
                <button type="button" class="btn btn-outline-secondary js-close-details-modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
jQuery(function ($) {
    'use strict';

    const urls = {
        data: @json(route('app.employees.data')),
        options: @json(route('app.employees.options')),
        store: @json(route('app.employees.store')),
        show: @json(route('app.employees.show', ['employee' => '__ID__'])),
        update: @json(route('app.employees.update', ['employee' => '__ID__'])),
        destroy: @json(route('app.employees.destroy', ['employee' => '__ID__'])),
        restore: @json(route('app.employees.restore', ['employee' => '__ID__']))
    };

    const permissions = {
        create: @json(auth()->user()->can('employees.create')),
        update: @json(auth()->user()->can('employees.update')),
        archive: @json(auth()->user()->can('employees.archive'))
    };

    const blankPhoto = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='96' height='96'%3E%3Crect width='100%25' height='100%25' fill='%23f3f6fa'/%3E%3Ctext x='50%25' y='54%25' text-anchor='middle' font-size='13' fill='%23758396'%3Eالصورة%3C/text%3E%3C/svg%3E";

    let currentPage = 1;
    let optionsData = {
        branches: [],
        departments: [],
        job_titles: [],
        work_locations: [],
        managers: [],
        users: []
    };

    const $employeeModal = $('#employeeModal');
    const $employeeDetailsModal = $('#employeeDetailsModal');

    $employeeModal.appendTo('body');
    $employeeDetailsModal.appendTo('body');

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        }
    });

    function urlWithId(template, id) {
        return template.replace('__ID__', id);
    }

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : value).html();
    }

    function dateOnly(value) {
        if (!value) {
            return '';
        }

        return String(value).substring(0, 10);
    }

    function displayValue(value, fallback) {
        return value === null || value === undefined || value === ''
            ? (fallback || '—')
            : value;
    }

    function showToast(message, type) {
        const background = type === 'error' ? '#b42318' : '#087443';
        const $toast = $('<div class="emp-toast"></div>')
            .css('background', background)
            .text(message);

        $('#employeeToastContainer').append($toast);
        $toast.fadeIn(180);

        setTimeout(function () {
            $toast.fadeOut(250, function () {
                $toast.remove();
            });
        }, 3500);
    }

    function showAjaxError(xhr, fallback) {
        let message = fallback || 'تعذر تنفيذ العملية.';

        if (xhr.responseJSON && xhr.responseJSON.message) {
            message = xhr.responseJSON.message;
        }

        if (xhr.status === 401) {
            message = 'انتهت جلسة الدخول. يرجى تسجيل الدخول مجددًا.';
        }

        showToast(message, 'error');
    }

    function openOverlay($overlay) {
        $('.emp-modal-overlay:visible')
            .not($overlay)
            .hide()
            .attr('aria-hidden', 'true');

        $('body').addClass('emp-modal-open');

        $overlay
            .stop(true, true)
            .show()
            .attr('aria-hidden', 'false');

        $overlay.find('.emp-modal-body').scrollTop(0);
    }

    function closeOverlay($overlay) {
        $overlay
            .stop(true, true)
            .hide()
            .attr('aria-hidden', 'true');

        if (!$('.emp-modal-overlay:visible').length) {
            $('body').removeClass('emp-modal-open');
        }
    }

    function setSelectOptions($select, rows, placeholder, selectedValue, textCallback) {
        const value = selectedValue == null ? '' : String(selectedValue);

        $select.empty().append(
            $('<option>').val('').text(placeholder)
        );

        $.each(rows || [], function (_, row) {
            const text = textCallback
                ? textCallback(row)
                : row.name;

            $select.append(
                $('<option>').val(row.id).text(text)
            );
        });

        $select.val(value);
    }

    function loadOptions(employeeId, callback) {
        $.ajax({
            url: urls.options,
            type: 'GET',
            data: employeeId ? { employee_id: employeeId } : {},
            success: function (response) {
                optionsData = response.options || optionsData;
                renderFilterOptions();

                if (typeof callback === 'function') {
                    callback();
                }
            },
            error: function (xhr) {
                showAjaxError(xhr, 'تعذر تحميل القوائم التنظيمية.');
            }
        });
    }

    function departmentsForBranch(branchId) {
        if (!branchId) {
            return optionsData.departments;
        }

        return $.grep(optionsData.departments, function (row) {
            return !row.branch_id || String(row.branch_id) === String(branchId);
        });
    }

    function jobTitlesForDepartment(departmentId) {
        if (!departmentId) {
            return optionsData.job_titles;
        }

        return $.grep(optionsData.job_titles, function (row) {
            return !row.department_id || String(row.department_id) === String(departmentId);
        });
    }

    function locationsForBranch(branchId) {
        if (!branchId) {
            return optionsData.work_locations;
        }

        return $.grep(optionsData.work_locations, function (row) {
            return !row.branch_id || String(row.branch_id) === String(branchId);
        });
    }

    function renderFilterOptions() {
        const branchValue = $('#filterBranch').val();
        const departmentValue = $('#filterDepartment').val();

        setSelectOptions(
            $('#filterBranch'),
            optionsData.branches,
            'جميع الفروع',
            branchValue
        );

        setSelectOptions(
            $('#filterDepartment'),
            departmentsForBranch(branchValue),
            'جميع الإدارات',
            departmentValue
        );
    }

    function renderEmployeeFormOptions(selected) {
        selected = selected || {};

        setSelectOptions(
            $('#employeeBranch'),
            optionsData.branches,
            'بدون فرع',
            selected.branch_id
        );

        setSelectOptions(
            $('#employeeDepartment'),
            departmentsForBranch(selected.branch_id),
            'بدون إدارة',
            selected.department_id
        );

        setSelectOptions(
            $('#employeeJobTitle'),
            jobTitlesForDepartment(selected.department_id),
            'بدون مسمى وظيفي',
            selected.job_title_id
        );

        setSelectOptions(
            $('#employeeWorkLocation'),
            locationsForBranch(selected.branch_id),
            'بدون موقع عمل',
            selected.work_location_id
        );

        setSelectOptions(
            $('#employeeManager'),
            optionsData.managers,
            'بدون مدير مباشر',
            selected.manager_id,
            function (row) {
                return row.name + ' (' + row.employee_number + ')';
            }
        );

        setSelectOptions(
            $('#employeeUser'),
            optionsData.users,
            'بدون حساب دخول',
            selected.user_id,
            function (row) {
                return row.name + ' - ' + row.email;
            }
        );
    }

    function statusBadge(status, label) {
        const classes = {
            draft: 'emp-status-draft',
            probation: 'emp-status-probation',
            active: 'emp-status-active',
            on_leave: 'emp-status-leave',
            suspended: 'emp-status-suspended',
            terminated: 'emp-status-terminated'
        };

        return '<span class="emp-status ' + (classes[status] || 'emp-status-draft') + '">' +
            escapeHtml(label || status) +
            '</span>';
    }

    function employeeInitial(name) {
        name = String(name || '').trim();
        return name ? name.charAt(0) : 'م';
    }

    function employeeAvatar(employee) {
        if (employee.photo_url) {
            return '<img class="emp-avatar" src="' + escapeHtml(employee.photo_url) + '" alt="">';
        }

        return '<span class="emp-avatar">' + escapeHtml(employeeInitial(employee.name)) + '</span>';
    }

    function renderEmployeeRows(rows) {
        const $body = $('#employeesTableBody').empty();

        if (!rows || !rows.length) {
            $body.html('<tr><td colspan="8" class="emp-empty">لا توجد سجلات مطابقة لمعايير البحث.</td></tr>');
            return;
        }

        $.each(rows, function (index, employee) {
            const branch = employee.branch ? employee.branch.name : '—';
            const department = employee.department ? employee.department.name : '—';
            const jobTitle = employee.job_title ? employee.job_title.name : '—';
            const rowNumber = ((currentPage - 1) * parseInt($('#filterPerPage').val(), 10)) + index + 1;
            let actions = '';

            actions += '<button type="button" class="btn btn-sm btn-outline-secondary btn-employee-details" data-id="' + employee.id + '">التفاصيل</button> ';

            if (!employee.is_archived && permissions.update) {
                actions += '<button type="button" class="btn btn-sm btn-outline-primary btn-employee-edit" data-id="' + employee.id + '">تعديل</button> ';
            }

            if (!employee.is_archived && permissions.archive) {
                actions += '<button type="button" class="btn btn-sm btn-outline-danger btn-employee-archive" data-id="' + employee.id + '" data-name="' + escapeHtml(employee.name) + '">أرشفة</button>';
            }

            if (employee.is_archived && permissions.archive) {
                actions += '<button type="button" class="btn btn-sm btn-outline-success btn-employee-restore" data-id="' + employee.id + '">استعادة</button>';
            }

            const account = employee.has_login_account
                ? (employee.user_is_active
                    ? '<span class="text-success small fw-semibold">حساب فعال</span>'
                    : '<span class="text-danger small fw-semibold">حساب معطل</span>')
                : '<span class="text-muted small">غير مرتبط</span>';

            $body.append(
                '<tr class="' + (employee.is_archived ? 'table-light opacity-75' : '') + '">' +
                    '<td>' + rowNumber + '</td>' +
                    '<td>' +
                        '<div class="d-flex align-items-center gap-2">' +
                            employeeAvatar(employee) +
                            '<div>' +
                                '<div class="fw-semibold">' + escapeHtml(employee.name) + '</div>' +
                                '<div class="small text-muted" dir="ltr">' + escapeHtml(employee.employee_number) + '</div>' +
                            '</div>' +
                        '</div>' +
                    '</td>' +
                    '<td>' +
                        '<div class="fw-semibold">' + escapeHtml(jobTitle) + '</div>' +
                        '<div class="small text-muted">' + escapeHtml(department) + ' · ' + escapeHtml(branch) + '</div>' +
                    '</td>' +
                    '<td dir="ltr">' + escapeHtml(displayValue(employee.hire_date)) + '</td>' +
                    '<td>' + escapeHtml(employee.employment_type_label) + '</td>' +
                    '<td>' + statusBadge(employee.employment_status, employee.employment_status_label) + '</td>' +
                    '<td>' + account + '</td>' +
                    '<td class="text-center"><div class="d-flex justify-content-center gap-1">' + actions + '</div></td>' +
                '</tr>'
            );
        });
    }

    function renderPagination(response) {
        const $pagination = $('#employeesPagination').empty();
        const current = parseInt(response.current_page || 1, 10);
        const last = parseInt(response.last_page || 1, 10);
        const start = Math.max(1, current - 2);
        const end = Math.min(last, current + 2);

        function addPage(label, page, disabled, active) {
            const $item = $('<li class="page-item"></li>')
                .toggleClass('disabled', !!disabled)
                .toggleClass('active', !!active);

            const $button = $('<button type="button" class="page-link"></button>')
                .text(label)
                .attr('data-page', page);

            $item.append($button);
            $pagination.append($item);
        }

        addPage('السابق', current - 1, current <= 1, false);

        for (let page = start; page <= end; page++) {
            addPage(page, page, false, page === current);
        }

        addPage('التالي', current + 1, current >= last, false);

        $('#employeesCount').text((response.total || 0) + ' موظف');

        if (response.total) {
            $('#employeesRange').text(
                'عرض ' + response.from + ' إلى ' + response.to + ' من أصل ' + response.total
            );
        } else {
            $('#employeesRange').text('لا توجد نتائج');
        }
    }

    function loadEmployees(page) {
        currentPage = page || 1;

        $('#employeesTableBody').html(
            '<tr><td colspan="8" class="emp-loading">جاري تحميل الموظفين...</td></tr>'
        );

        $.ajax({
            url: urls.data,
            type: 'GET',
            data: {
                page: currentPage,
                search: $('#filterSearch').val(),
                branch_id: $('#filterBranch').val(),
                department_id: $('#filterDepartment').val(),
                employment_status: $('#filterStatus').val(),
                employment_type: $('#filterType').val(),
                archive_status: $('#filterArchive').val(),
                per_page: $('#filterPerPage').val()
            },
            success: function (response) {
                currentPage = parseInt(response.current_page || 1, 10);
                renderEmployeeRows(response.data || []);
                renderPagination(response);
            },
            error: function (xhr) {
                $('#employeesTableBody').html(
                    '<tr><td colspan="8" class="emp-empty text-danger">تعذر تحميل بيانات الموظفين.</td></tr>'
                );
                showAjaxError(xhr, 'تعذر تحميل بيانات الموظفين.');
            }
        });
    }

    function resetEmployeeForm() {
        const form = $('#employeeForm')[0];
        form.reset();
        $('#employeeId').val('');
        $('#employeeFormErrors').addClass('d-none').empty();
        $('#employeeForm .is-invalid').removeClass('is-invalid');
        $('#employeeForm .invalid-feedback.emp-server-error').remove();
        $('#employeePhotoPreview').attr('src', blankPhoto);
        $('#removePhotoContainer').addClass('d-none');
        $('#removeEmployeePhoto').prop('checked', false);
        $('[name="country_code"]').val('SA');
        $('[name="employment_type"]').val('full_time');
        $('[name="employment_status"]').val('probation');
        toggleTerminationFields();
    }

    function toggleTerminationFields() {
        const terminated = $('#employmentStatus').val() === 'terminated';
        $('.emp-termination-fields').toggleClass('d-none', !terminated);
    }

    function openCreateEmployee() {
        resetEmployeeForm();
        $('#employeeModalTitle').text('إضافة موظف جديد');
        $('#btnSaveEmployee').text('حفظ البيانات');

        renderEmployeeFormOptions({});
        openOverlay($employeeModal);

        loadOptions(null, function () {
            renderEmployeeFormOptions({});
        });
    }

    function setEmployeeFormValues(employee) {
        const dateFields = [
            'identity_expiry_date',
            'birth_date',
            'hire_date',
            'probation_end_date',
            'confirmation_date',
            'termination_date'
        ];

        $.each(employee, function (key, value) {
            const $field = $('#employeeForm [name="' + key + '"]');

            if (!$field.length || key === 'photo') {
                return;
            }

            if (dateFields.includes(key)) {
                value = dateOnly(value);
            }

            $field.val(value == null ? '' : value);
        });

        renderEmployeeFormOptions(employee);
        $('#employeePhotoPreview').attr('src', employee.photo_url || blankPhoto);
        $('#removePhotoContainer').toggleClass('d-none', !employee.photo_url);
        toggleTerminationFields();
    }

    function openEditEmployee(id) {
        resetEmployeeForm();
        $('#employeeModalTitle').text('تعديل بيانات الموظف');
        $('#btnSaveEmployee').text('حفظ التعديلات');

        openOverlay($employeeModal);

        $.ajax({
            url: urlWithId(urls.show, id),
            type: 'GET',
            success: function (response) {
                const employee = response.employee;
                $('#employeeId').val(employee.id);

                loadOptions(employee.id, function () {
                    setEmployeeFormValues(employee);
                });
            },
            error: function (xhr) {
                closeOverlay($employeeModal);
                showAjaxError(xhr, 'تعذر تحميل بيانات الموظف.');
            }
        });
    }

    function showFormErrors(xhr) {
        const errors = xhr.responseJSON && xhr.responseJSON.errors
            ? xhr.responseJSON.errors
            : {};
        const messages = [];

        $('#employeeForm .is-invalid').removeClass('is-invalid');
        $('#employeeForm .invalid-feedback.emp-server-error').remove();

        $.each(errors, function (field, fieldMessages) {
            const inputName = field.split('.')[0];
            const $field = $('#employeeForm [name="' + inputName + '"]').first();
            const message = Array.isArray(fieldMessages) ? fieldMessages[0] : fieldMessages;

            messages.push(message);

            if ($field.length) {
                $field.addClass('is-invalid');
                $('<div class="invalid-feedback emp-server-error"></div>')
                    .text(message)
                    .insertAfter($field);
            }
        });

        if (!messages.length && xhr.responseJSON && xhr.responseJSON.message) {
            messages.push(xhr.responseJSON.message);
        }

        $('#employeeFormErrors')
            .removeClass('d-none')
            .html(messages.length
                ? $('<div>').text(messages.join(' ')).html()
                : 'يرجى مراجعة البيانات المدخلة.');

        $('#employeeModal .emp-modal-body').animate({ scrollTop: 0 }, 180);
    }

    function saveEmployee() {
        const id = $('#employeeId').val();
        const formData = new FormData($('#employeeForm')[0]);
        const url = id ? urlWithId(urls.update, id) : urls.store;

        if (id) {
            formData.append('_method', 'PUT');
        }

        $('#btnSaveEmployee').prop('disabled', true).text('جاري الحفظ...');
        $('#employeeFormErrors').addClass('d-none').empty();

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                closeOverlay($employeeModal);
                showToast(response.message || 'تم حفظ بيانات الموظف بنجاح.', 'success');
                loadOptions(null);
                loadEmployees(id ? currentPage : 1);
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    showFormErrors(xhr);
                } else {
                    showAjaxError(xhr, 'تعذر حفظ بيانات الموظف.');
                }
            },
            complete: function () {
                $('#btnSaveEmployee')
                    .prop('disabled', false)
                    .text(id ? 'حفظ التعديلات' : 'حفظ البيانات');
            }
        });
    }

    function detailItem(label, value, dir) {
        return '<div class="col-md-6">' +
            '<div class="emp-detail-item">' +
                '<div class="emp-detail-label">' + escapeHtml(label) + '</div>' +
                '<div class="fw-semibold" ' + (dir ? 'dir="' + dir + '"' : '') + '>' +
                    escapeHtml(displayValue(value)) +
                '</div>' +
            '</div>' +
        '</div>';
    }

    function showEmployeeDetails(id) {
        $('#employeeDetailsBody').html(
            '<div class="text-center text-muted py-5">جاري تحميل البيانات...</div>'
        );
        openOverlay($employeeDetailsModal);

        $.ajax({
            url: urlWithId(urls.show, id),
            type: 'GET',
            success: function (response) {
                const employee = response.employee;
                let html = '<div class="text-center mb-4">' +
                    '<img class="emp-photo-preview mb-2" src="' + escapeHtml(employee.photo_url || blankPhoto) + '" alt="">' +
                    '<h5 class="mb-1">' + escapeHtml(employee.display_name || employee.full_name) + '</h5>' +
                    '<div class="text-muted" dir="ltr">' + escapeHtml(employee.employee_number) + '</div>' +
                    '<div class="mt-2">' + statusBadge(employee.employment_status, employee.employment_status_label) + '</div>' +
                '</div><div class="row g-3">';

                html += detailItem('المسمى الوظيفي', employee.job_title ? employee.job_title.name : null);
                html += detailItem('الإدارة', employee.department ? employee.department.name : null);
                html += detailItem('الفرع', employee.branch ? employee.branch.name : null);
                html += detailItem('موقع العمل', employee.work_location ? employee.work_location.name : null);
                html += detailItem('المدير المباشر', employee.manager ? (employee.manager.full_name || employee.manager.display_name) : null);
                html += detailItem('نوع التوظيف', employee.employment_type_label);
                html += detailItem('تاريخ التعيين', dateOnly(employee.hire_date), 'ltr');
                html += detailItem('رقم الهوية', employee.identity_number, 'ltr');
                html += detailItem('البريد الوظيفي', employee.work_email, 'ltr');
                html += detailItem('هاتف العمل', employee.work_phone, 'ltr');
                html += detailItem('الجوال الشخصي', employee.personal_phone, 'ltr');
                html += detailItem('حساب الدخول', employee.user ? employee.user.email : null, 'ltr');
                html += '</div>';

                $('#employeeDetailsBody').html(html);
            },
            error: function (xhr) {
                closeOverlay($employeeDetailsModal);
                showAjaxError(xhr, 'تعذر تحميل تفاصيل الموظف.');
            }
        });
    }

    function archiveEmployee(id, name) {
        if (!window.confirm('هل تريد أرشفة الموظف: ' + name + '؟')) {
            return;
        }

        $.ajax({
            url: urlWithId(urls.destroy, id),
            type: 'POST',
            data: { _method: 'DELETE' },
            success: function (response) {
                showToast(response.message || 'تمت أرشفة الموظف.', 'success');
                loadEmployees(currentPage);
            },
            error: function (xhr) {
                showAjaxError(xhr, 'تعذر أرشفة الموظف.');
            }
        });
    }

    function restoreEmployee(id) {
        if (!window.confirm('هل تريد استعادة هذا الموظف؟')) {
            return;
        }

        $.ajax({
            url: urlWithId(urls.restore, id),
            type: 'POST',
            success: function (response) {
                showToast(response.message || 'تمت استعادة الموظف.', 'success');
                loadEmployees(currentPage);
            },
            error: function (xhr) {
                showAjaxError(xhr, 'تعذر استعادة الموظف.');
            }
        });
    }

    $('#btnAddEmployee').on('click', openCreateEmployee);
    $('#btnSearchEmployees').on('click', function () { loadEmployees(1); });

    $('#filterSearch').on('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            loadEmployees(1);
        }
    });

    $('#filterStatus, #filterType, #filterArchive, #filterPerPage').on('change', function () {
        loadEmployees(1);
    });

    $('#filterBranch').on('change', function () {
        setSelectOptions(
            $('#filterDepartment'),
            departmentsForBranch($(this).val()),
            'جميع الإدارات',
            ''
        );
        loadEmployees(1);
    });

    $('#filterDepartment').on('change', function () {
        loadEmployees(1);
    });

    $('#btnResetEmployees').on('click', function () {
        $('#filterSearch').val('');
        $('#filterBranch').val('');
        renderFilterOptions();
        $('#filterDepartment').val('');
        $('#filterStatus').val('');
        $('#filterType').val('');
        $('#filterArchive').val('active');
        $('#filterPerPage').val('15');
        loadEmployees(1);
    });

    $('#employeesPagination').on('click', '.page-link', function () {
        const $item = $(this).closest('.page-item');

        if ($item.hasClass('disabled') || $item.hasClass('active')) {
            return;
        }

        loadEmployees(parseInt($(this).attr('data-page'), 10));
    });

    $('#employeesTableBody')
        .on('click', '.btn-employee-details', function () {
            showEmployeeDetails($(this).data('id'));
        })
        .on('click', '.btn-employee-edit', function () {
            openEditEmployee($(this).data('id'));
        })
        .on('click', '.btn-employee-archive', function () {
            archiveEmployee($(this).data('id'), $(this).data('name'));
        })
        .on('click', '.btn-employee-restore', function () {
            restoreEmployee($(this).data('id'));
        });

    $('#employeeBranch').on('change', function () {
        const branchId = $(this).val();

        setSelectOptions(
            $('#employeeDepartment'),
            departmentsForBranch(branchId),
            'بدون إدارة',
            ''
        );

        setSelectOptions(
            $('#employeeJobTitle'),
            optionsData.job_titles,
            'بدون مسمى وظيفي',
            ''
        );

        setSelectOptions(
            $('#employeeWorkLocation'),
            locationsForBranch(branchId),
            'بدون موقع عمل',
            ''
        );
    });

    $('#employeeDepartment').on('change', function () {
        setSelectOptions(
            $('#employeeJobTitle'),
            jobTitlesForDepartment($(this).val()),
            'بدون مسمى وظيفي',
            ''
        );
    });

    $('#employmentStatus').on('change', toggleTerminationFields);

    $('#employeePhoto').on('change', function () {
        const file = this.files && this.files[0];

        if (!file) {
            return;
        }

        const reader = new FileReader();
        reader.onload = function (event) {
            $('#employeePhotoPreview').attr('src', event.target.result);
        };
        reader.readAsDataURL(file);
        $('#removeEmployeePhoto').prop('checked', false);
    });

    $('#removeEmployeePhoto').on('change', function () {
        if ($(this).is(':checked')) {
            $('#employeePhoto').val('');
            $('#employeePhotoPreview').attr('src', blankPhoto);
        }
    });

    $('#employeeForm').on('submit', function (event) {
        event.preventDefault();
        saveEmployee();
    });

    $('.js-close-employee-modal').on('click', function () {
        closeOverlay($employeeModal);
    });

    $('.js-close-details-modal').on('click', function () {
        closeOverlay($employeeDetailsModal);
    });

    $('.emp-modal-overlay').on('mousedown', function (event) {
        if (event.target === this) {
            closeOverlay($(this));
        }
    });

    $(document).on('keydown', function (event) {
        if (event.key === 'Escape') {
            closeOverlay($('.emp-modal-overlay:visible').last());
        }
    });

    loadOptions(null, function () {
        loadEmployees(1);
    });
});
</script>
@endpush