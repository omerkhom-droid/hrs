@extends('layouts.tenant')

@section('title', 'عقود الموظفين')
@section('page-title', 'عقود الموظفين')

@section('content')
  <style>
    .contracts-page {
      --c-primary: #0d6efd;
      --c-border: #e5eaf1;
      --c-muted: #6c757d;
    }
    .contracts-card {
      background: #fff;
      border: 1px solid var(--c-border);
      border-radius: 16px;
      box-shadow: 0 6px 22px rgba(16, 40, 80, 0.05);
    }
    .contracts-stat {
      min-width: 120px;
      padding: 11px 16px;
      background: #eef5ff;
      border-radius: 12px;
      color: #0b5ed7;
      text-align: center;
      font-weight: 700;
    }
    .contracts-filter-label,
    .contracts-form-label {
      display: block;
      margin-bottom: 7px;
      color: #495057;
      font-size: 0.82rem;
      font-weight: 600;
    }
    .contracts-required::after {
      content: " *";
      color: #dc3545;
    }
    .contracts-table > :not(caption) > * > * {
      padding: 0.9rem 0.75rem;
      vertical-align: middle;
      border-color: #edf0f4;
      white-space: nowrap;
    }
    .contracts-table thead th {
      color: #495057;
      background: #f8fafc;
      font-size: 0.83rem;
      font-weight: 700;
    }
    .contracts-status {
      display: inline-flex;
      align-items: center;
      border-radius: 999px;
      padding: 5px 10px;
      font-size: 0.76rem;
      font-weight: 700;
    }
    .contracts-status-draft {
      background: #e9ecef;
      color: #495057;
    }
    .contracts-status-active {
      background: #dff6ea;
      color: #087443;
    }
    .contracts-status-suspended {
      background: #fff0d8;
      color: #9a5100;
    }
    .contracts-status-expired {
      background: #e7f1ff;
      color: #0b5ed7;
    }
    .contracts-status-terminated {
      background: #fde2e4;
      color: #a61b29;
    }
    .contracts-status-cancelled {
      background: #f1e8ff;
      color: #6f42c1;
    }
    .contracts-avatar {
      width: 42px;
      height: 42px;
      flex: 0 0 42px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 12px;
      background: #eaf2ff;
      color: var(--c-primary);
      font-weight: 700;
    }
    .contracts-empty,
    .contracts-loading {
      padding: 55px 20px !important;
      text-align: center;
      color: var(--c-muted);
    }
    .contracts-action {
      width: 145px;
      margin-inline: auto;
    }
    .contracts-pagination .page-link {
      min-width: 36px;
      margin: 0 2px;
      border-radius: 8px !important;
      text-align: center;
    }
    .contracts-modal-open {
      overflow: hidden !important;
    }
    .contracts-modal-overlay {
      position: fixed;
      inset: 0;
      z-index: 1080;
      display: none;
      overflow: hidden;
      padding: 16px;
      background: rgba(7, 20, 43, 0.58);
    }
    .contracts-modal-dialog {
      width: min(1100px, 100%);
      height: calc(100vh - 32px);
      max-height: calc(100dvh - 32px);
      margin: 0 auto;
      display: flex;
      align-items: stretch;
    }
    .contracts-modal-dialog.modal-md {
      width: min(760px, 100%);
    }
    .contracts-modal-dialog.modal-sm {
      width: min(520px, 100%);
      height: auto;
      max-height: calc(100dvh - 32px);
      margin-top: min(16vh, 120px);
    }
    .contracts-modal-content {
      width: 100%;
      min-height: 0;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      background: #fff;
      border-radius: 18px;
      box-shadow: 0 24px 70px rgba(0, 0, 0, 0.24);
    }
    #contractForm,
    #terminateContractForm {
      width: 100%;
      min-height: 0;
      flex: 1 1 auto;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }
    .contracts-modal-header,
    .contracts-modal-footer {
      flex: 0 0 auto;
      padding: 16px 20px;
      background: #fff;
    }
    .contracts-modal-header {
      border-bottom: 1px solid var(--c-border);
    }
    .contracts-modal-footer {
      border-top: 1px solid var(--c-border);
    }
    .contracts-modal-body {
      min-height: 0;
      flex: 1 1 auto;
      overflow-y: auto !important;
      overscroll-behavior: contain;
      -webkit-overflow-scrolling: touch;
      padding: 20px;
      background: #f8fafc;
    }
    .contracts-modal-close {
      border: 0;
      background: transparent;
      color: #6c757d;
      font-size: 1.7rem;
      line-height: 1;
    }
    .contracts-section {
      padding: 18px;
      margin-bottom: 16px;
      background: #fff;
      border: 1px solid var(--c-border);
      border-radius: 14px;
    }
    .contracts-section-title {
      margin-bottom: 16px;
      color: #172b4d;
      font-size: 0.98rem;
      font-weight: 700;
    }
    .contracts-total {
      padding: 15px;
      background: #eaf7f0;
      border: 1px solid #cdebdc;
      border-radius: 12px;
      color: #087443;
    }
    .contracts-detail {
      height: 100%;
      padding: 13px;
      background: #fff;
      border: 1px solid var(--c-border);
      border-radius: 12px;
    }
    .contracts-detail-label {
      margin-bottom: 5px;
      color: var(--c-muted);
      font-size: 0.76rem;
    }
    .contracts-toast-container {
      position: fixed;
      z-index: 1200;
      top: 20px;
      left: 20px;
      width: min(390px, calc(100% - 40px));
    }
    .contracts-toast {
      display: none;
      padding: 14px 16px;
      margin-bottom: 10px;
      border-radius: 12px;
      color: #fff;
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.18);
    }
    .contracts-toast-success {
      background: #198754;
    }
    .contracts-toast-error {
      background: #dc3545;
    }
    @media (max-width: 767.98px) {
      .contracts-modal-overlay {
        padding: 0;
      }
      .contracts-modal-dialog,
      .contracts-modal-dialog.modal-sm {
        width: 100%;
        height: 100dvh;
        max-height: 100dvh;
        margin: 0;
      }
      .contracts-modal-content {
        border-radius: 0;
      }
    }
  </style>

  <div class="contracts-page">
    <div class="contracts-toast-container" id="contractsToastContainer"></div>

    <div
      class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4"
    >
      <div>
        <h4 class="mb-1">عقود الموظفين</h4>
        <div class="text-muted small">
          إدارة العقود والرواتب الثابتة ودورة حياة العقد.
        </div>
      </div>
      @can('contracts.create')
        <button type="button" class="btn btn-primary" id="btnCreateContract">
          + إضافة عقد
        </button>
      @endcan
    </div>

    <div class="contracts-card p-3 mb-4">
      <div class="row g-3 align-items-end">
        <div class="col-xl-3 col-lg-4 col-md-6">
          <label class="contracts-filter-label" for="filterContractSearch"
            >البحث</label
          >
          <input
            type="search"
            class="form-control"
            id="filterContractSearch"
            placeholder="رقم العقد أو اسم الموظف"
          />
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
          <label class="contracts-filter-label" for="filterContractEmployee"
            >الموظف</label
          >
          <select class="form-select" id="filterContractEmployee">
            <option value="">جميع الموظفين</option>
          </select>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
          <label class="contracts-filter-label" for="filterContractStatus"
            >الحالة</label
          >
          <select class="form-select" id="filterContractStatus">
            <option value="">جميع الحالات</option>
            <option value="draft">مسودة</option>
            <option value="active">فعال</option>
            <option value="suspended">موقوف</option>
            <option value="expired">منتهي</option>
            <option value="terminated">منهى</option>
            <option value="cancelled">ملغي</option>
          </select>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
          <label class="contracts-filter-label" for="filterContractType"
            >نوع العقد</label
          >
          <select class="form-select" id="filterContractType">
            <option value="">جميع الأنواع</option>
            <option value="indefinite">غير محدد المدة</option>
            <option value="fixed_term">محدد المدة</option>
            <option value="temporary">مؤقت</option>
            <option value="seasonal">موسمي</option>
            <option value="part_time">دوام جزئي</option>
            <option value="training">تدريب</option>
          </select>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
          <label class="contracts-filter-label" for="filterContractExpiring"
            >ينتهي خلال</label
          >
          <select class="form-select" id="filterContractExpiring">
            <option value="">بدون تحديد</option>
            <option value="30">30 يومًا</option>
            <option value="60">60 يومًا</option>
            <option value="90">90 يومًا</option>
            <option value="180">180 يومًا</option>
          </select>
        </div>
        <div class="col-xl-1 col-lg-4 col-md-6">
          <label class="contracts-filter-label" for="filterContractPerPage"
            >العدد</label
          >
          <select class="form-select" id="filterContractPerPage">
            <option value="10">10</option>
            <option value="15" selected>15</option>
            <option value="25">25</option>
            <option value="50">50</option>
          </select>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
          <label class="contracts-filter-label" for="filterContractArchive"
            >السجلات</label
          >
          <select class="form-select" id="filterContractArchive">
            <option value="active">الحالية</option>
            <option value="with">الحالية والمؤرشفة</option>
            <option value="only">المؤرشفة فقط</option>
          </select>
        </div>
        <div class="col-xl-3 col-lg-4 col-md-6 d-flex gap-2">
          <button
            type="button"
            class="btn btn-primary flex-grow-1"
            id="btnSearchContracts"
          >
            بحث
          </button>
          <button
            type="button"
            class="btn btn-outline-secondary"
            id="btnResetContractFilters"
          >
            إعادة
          </button>
        </div>
      </div>
    </div>

    <div class="contracts-card overflow-hidden">
      <div
        class="d-flex justify-content-between align-items-center flex-wrap gap-3 p-3 border-bottom"
      >
        <h6 class="mb-0">قائمة العقود</h6>
        <div class="contracts-stat" id="contractsCount">0 عقد</div>
      </div>
      <div class="table-responsive">
        <table class="table contracts-table mb-0">
          <thead>
            <tr>
              <th>#</th>
              <th>الموظف</th>
              <th>رقم العقد</th>
              <th>نوع العقد</th>
              <th>المدة</th>
              <th>إجمالي الراتب</th>
              <th>الحالة</th>
              <th class="text-center">الإجراءات</th>
            </tr>
          </thead>
          <tbody id="contractsTableBody">
            <tr>
              <td colspan="8" class="contracts-loading">
                جاري تحميل العقود...
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div
        class="d-flex justify-content-between align-items-center flex-wrap gap-3 p-3 border-top"
      >
        <div class="small text-muted" id="contractsRange">لا توجد نتائج</div>
        <ul
          class="pagination contracts-pagination mb-0"
          id="contractsPagination"
        ></ul>
      </div>
    </div>
  </div>

  <div class="contracts-modal-overlay" id="contractModal" aria-hidden="true">
    <div class="contracts-modal-dialog">
      <div
        class="contracts-modal-content"
        role="dialog"
        aria-modal="true"
        aria-labelledby="contractModalTitle"
      >
        <form id="contractForm" novalidate>
          <div
            class="contracts-modal-header d-flex justify-content-between align-items-center"
          >
            <div>
              <h5 class="mb-1" id="contractModalTitle">إضافة عقد</h5>
              <div class="text-muted small">
                يُحفظ العقد أولًا كمسودة قبل التفعيل.
              </div>
            </div>
            <button
              type="button"
              class="contracts-modal-close js-close-contract-modal"
              aria-label="إغلاق"
            >
              &times;
            </button>
          </div>
          <div class="contracts-modal-body">
            <input type="hidden" id="contractId" />
            <div
              class="alert alert-danger d-none"
              id="contractFormErrors"
            ></div>
            <div class="contracts-section">
              <div class="contracts-section-title">بيانات العقد الأساسية</div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label
                    class="contracts-form-label contracts-required"
                    for="contractEmployee"
                    >الموظف</label
                  ><select
                    class="form-select"
                    id="contractEmployee"
                    name="employee_id"
                    required
                  >
                    <option value="">اختر الموظف</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="contracts-form-label" for="contractNumber"
                    >رقم العقد</label
                  ><input
                    type="text"
                    class="form-control"
                    id="contractNumber"
                    name="contract_number"
                    maxlength="50"
                    dir="ltr"
                    placeholder="يُولد تلقائيًا"
                  />
                </div>
                <div class="col-md-3">
                  <label
                    class="contracts-form-label contracts-required"
                    for="contractType"
                    >نوع العقد</label
                  ><select
                    class="form-select"
                    id="contractType"
                    name="contract_type"
                    required
                  >
                    <option value="fixed_term">محدد المدة</option>
                    <option value="indefinite">غير محدد المدة</option>
                    <option value="temporary">مؤقت</option>
                    <option value="seasonal">موسمي</option>
                    <option value="part_time">دوام جزئي</option>
                    <option value="training">تدريب</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label
                    class="contracts-form-label contracts-required"
                    for="contractStartDate"
                    >تاريخ البداية</label
                  ><input
                    type="date"
                    class="form-control"
                    id="contractStartDate"
                    name="start_date"
                    required
                  />
                </div>
                <div class="col-md-4" id="contractEndDateContainer">
                  <label
                    class="contracts-form-label contracts-required"
                    for="contractEndDate"
                    >تاريخ النهاية</label
                  ><input
                    type="date"
                    class="form-control"
                    id="contractEndDate"
                    name="end_date"
                  />
                </div>
                <div class="col-md-4">
                  <label
                    class="contracts-form-label"
                    for="contractProbationEndDate"
                    >نهاية فترة التجربة</label
                  ><input
                    type="date"
                    class="form-control"
                    id="contractProbationEndDate"
                    name="probation_end_date"
                  />
                </div>
                <div class="col-md-6">
                  <label class="contracts-form-label" for="contractSignedAt"
                    >تاريخ ووقت التوقيع</label
                  ><input
                    type="datetime-local"
                    class="form-control"
                    id="contractSignedAt"
                    name="signed_at"
                  />
                </div>
              </div>
            </div>
            <div class="contracts-section">
              <div class="contracts-section-title">الراتب والبدلات</div>
              <div class="row g-3">
                <div class="col-md-3">
                  <label
                    class="contracts-form-label contracts-required"
                    for="contractBasicSalary"
                    >الراتب الأساسي</label
                  ><input
                    type="number"
                    class="form-control contract-money"
                    id="contractBasicSalary"
                    name="basic_salary"
                    min="0"
                    step="0.01"
                    required
                  />
                </div>
                <div class="col-md-3">
                  <label
                    class="contracts-form-label"
                    for="contractHousingAllowance"
                    >بدل السكن</label
                  ><input
                    type="number"
                    class="form-control contract-money"
                    id="contractHousingAllowance"
                    name="housing_allowance"
                    min="0"
                    step="0.01"
                  />
                </div>
                <div class="col-md-3">
                  <label
                    class="contracts-form-label"
                    for="contractTransportAllowance"
                    >بدل النقل</label
                  ><input
                    type="number"
                    class="form-control contract-money"
                    id="contractTransportAllowance"
                    name="transport_allowance"
                    min="0"
                    step="0.01"
                  />
                </div>
                <div class="col-md-3">
                  <label
                    class="contracts-form-label"
                    for="contractOtherAllowances"
                    >بدلات أخرى</label
                  ><input
                    type="number"
                    class="form-control contract-money"
                    id="contractOtherAllowances"
                    name="other_allowances"
                    min="0"
                    step="0.01"
                  />
                </div>
                <div class="col-md-3">
                  <label
                    class="contracts-form-label contracts-required"
                    for="contractCurrency"
                    >العملة</label
                  ><input
                    type="text"
                    class="form-control"
                    id="contractCurrency"
                    name="currency_code"
                    maxlength="3"
                    dir="ltr"
                    required
                  />
                </div>
                <div class="col-md-3">
                  <label
                    class="contracts-form-label contracts-required"
                    for="contractPayFrequency"
                    >دورية الدفع</label
                  ><select
                    class="form-select"
                    id="contractPayFrequency"
                    name="pay_frequency"
                    required
                  >
                    <option value="monthly">شهري</option>
                    <option value="daily">يومي</option>
                    <option value="hourly">بالساعة</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <div
                    class="contracts-total h-100 d-flex justify-content-between align-items-center"
                  >
                    <span>إجمالي الراتب الثابت</span
                    ><strong id="contractGrossSalary" dir="ltr"
                      >0.00 SAR</strong
                    >
                  </div>
                </div>
              </div>
            </div>
            <div class="contracts-section">
              <div class="contracts-section-title">الدوام والإجازات</div>
              <div class="row g-3">
                <div class="col-md-3">
                  <label
                    class="contracts-form-label contracts-required"
                    for="contractHoursPerDay"
                    >ساعات العمل يوميًا</label
                  ><input
                    type="number"
                    class="form-control"
                    id="contractHoursPerDay"
                    name="working_hours_per_day"
                    min="0.5"
                    max="24"
                    step="0.5"
                    required
                  />
                </div>
                <div class="col-md-3">
                  <label
                    class="contracts-form-label contracts-required"
                    for="contractDaysPerWeek"
                    >أيام العمل أسبوعيًا</label
                  ><input
                    type="number"
                    class="form-control"
                    id="contractDaysPerWeek"
                    name="working_days_per_week"
                    min="1"
                    max="7"
                    required
                  />
                </div>
                <div class="col-md-3">
                  <label
                    class="contracts-form-label contracts-required"
                    for="contractAnnualLeave"
                    >الإجازة السنوية</label
                  >
                  <div class="input-group">
                    <input
                      type="number"
                      class="form-control"
                      id="contractAnnualLeave"
                      name="annual_leave_days"
                      min="0"
                      max="365"
                      required
                    /><span class="input-group-text">يوم</span>
                  </div>
                </div>
                <div class="col-md-3">
                  <label
                    class="contracts-form-label contracts-required"
                    for="contractNoticePeriod"
                    >مدة الإشعار</label
                  >
                  <div class="input-group">
                    <input
                      type="number"
                      class="form-control"
                      id="contractNoticePeriod"
                      name="notice_period_days"
                      min="0"
                      max="3650"
                      required
                    /><span class="input-group-text">يوم</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="contracts-section" id="contractRenewalSection">
              <div class="contracts-section-title">التجديد</div>
              <div class="row g-3 align-items-center">
                <div class="col-md-6">
                  <div class="form-check form-switch">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      id="contractAutoRenew"
                      name="auto_renew"
                      value="1"
                    /><label class="form-check-label" for="contractAutoRenew"
                      >تجديد العقد تلقائيًا</label
                    >
                  </div>
                </div>
                <div class="col-md-6" id="contractRenewalNoticeContainer">
                  <label
                    class="contracts-form-label contracts-required"
                    for="contractRenewalNotice"
                    >التنبيه قبل التجديد</label
                  >
                  <div class="input-group">
                    <input
                      type="number"
                      class="form-control"
                      id="contractRenewalNotice"
                      name="renewal_notice_days"
                      min="0"
                      max="3650"
                      required
                    /><span class="input-group-text">يوم</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="contracts-section mb-0">
              <div class="contracts-section-title">الشروط والملاحظات</div>
              <div class="row g-3">
                <div class="col-12">
                  <label class="contracts-form-label" for="contractTerms"
                    >شروط العقد</label
                  ><textarea
                    class="form-control"
                    id="contractTerms"
                    name="terms"
                    rows="4"
                  ></textarea>
                </div>
                <div class="col-12">
                  <label class="contracts-form-label" for="contractNotes"
                    >ملاحظات داخلية</label
                  ><textarea
                    class="form-control"
                    id="contractNotes"
                    name="notes"
                    rows="3"
                    maxlength="5000"
                  ></textarea>
                </div>
              </div>
            </div>
          </div>
          <div class="contracts-modal-footer d-flex justify-content-end gap-2">
            <button
              type="button"
              class="btn btn-outline-secondary js-close-contract-modal"
            >
              إلغاء</button
            ><button type="submit" class="btn btn-primary" id="btnSaveContract">
              حفظ المسودة
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div
    class="contracts-modal-overlay"
    id="contractDetailsModal"
    aria-hidden="true"
  >
    <div class="contracts-modal-dialog modal-md">
      <div
        class="contracts-modal-content"
        role="dialog"
        aria-modal="true"
        aria-labelledby="contractDetailsTitle"
      >
        <div
          class="contracts-modal-header d-flex justify-content-between align-items-center"
        >
          <h5 class="mb-0" id="contractDetailsTitle">تفاصيل العقد</h5>
          <button
            type="button"
            class="contracts-modal-close js-close-details-modal"
            aria-label="إغلاق"
          >
            &times;
          </button>
        </div>
        <div class="contracts-modal-body" id="contractDetailsBody">
          <div class="text-center text-muted py-5">جاري تحميل البيانات...</div>
        </div>
        <div class="contracts-modal-footer d-flex justify-content-end">
          <button
            type="button"
            class="btn btn-outline-secondary js-close-details-modal"
          >
            إغلاق
          </button>
        </div>
      </div>
    </div>
  </div>

  <div
    class="contracts-modal-overlay"
    id="terminateContractModal"
    aria-hidden="true"
  >
    <div class="contracts-modal-dialog modal-md">
      <div
        class="contracts-modal-content"
        role="dialog"
        aria-modal="true"
        aria-labelledby="terminateContractTitle"
      >
        <form id="terminateContractForm" novalidate>
          <div
            class="contracts-modal-header d-flex justify-content-between align-items-center"
          >
            <h5 class="mb-0" id="terminateContractTitle">إنهاء العقد</h5>
            <button
              type="button"
              class="contracts-modal-close js-close-terminate-modal"
              aria-label="إغلاق"
            >
              &times;
            </button>
          </div>
          <div class="contracts-modal-body">
            <input type="hidden" id="terminateContractId" />
            <div
              class="alert alert-danger d-none"
              id="terminateContractErrors"
            ></div>
            <div class="alert alert-warning">
              سيؤدي إنهاء آخر عقد فعال إلى تحديث حالة الموظف إلى منتهي الخدمة.
            </div>
            <div class="mb-3">
              <label
                class="contracts-form-label contracts-required"
                for="contractTerminationDate"
                >تاريخ الإنهاء</label
              ><input
                type="date"
                class="form-control"
                id="contractTerminationDate"
                name="termination_date"
                max="{{ now()->toDateString() }}"
                required
              />
            </div>
            <div>
              <label
                class="contracts-form-label contracts-required"
                for="contractTerminationReason"
                >سبب الإنهاء</label
              ><textarea
                class="form-control"
                id="contractTerminationReason"
                name="termination_reason"
                rows="5"
                maxlength="5000"
                required
              ></textarea>
            </div>
          </div>
          <div class="contracts-modal-footer d-flex justify-content-end gap-2">
            <button
              type="button"
              class="btn btn-outline-secondary js-close-terminate-modal"
            >
              إلغاء</button
            ><button
              type="submit"
              class="btn btn-danger"
              id="btnConfirmTerminate"
            >
              تأكيد الإنهاء
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div
    class="contracts-modal-overlay"
    id="contractConfirmModal"
    aria-hidden="true"
  >
    <div class="contracts-modal-dialog modal-sm">
      <div
        class="contracts-modal-content"
        role="dialog"
        aria-modal="true"
        aria-labelledby="contractConfirmTitle"
      >
        <div class="contracts-modal-header">
          <h5 class="mb-0" id="contractConfirmTitle">تأكيد العملية</h5>
        </div>
        <div class="contracts-modal-body">
          <p class="mb-0" id="contractConfirmMessage"></p>
        </div>
        <div class="contracts-modal-footer d-flex justify-content-end gap-2">
          <button
            type="button"
            class="btn btn-outline-secondary"
            id="btnCancelContractConfirm"
          >
            إلغاء</button
          ><button
            type="button"
            class="btn btn-danger"
            id="btnAcceptContractConfirm"
          >
            تأكيد
          </button>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  <script>
    jQuery(function ($) {
      "use strict";

      const urls = {
        data: @json(route('app.contracts.data')),
        options: @json(route('app.contracts.options')),
        store: @json(route('app.contracts.store')),
        show: @json(route('app.contracts.show', ['contract' => '__ID__'])),
        update: @json(route('app.contracts.update', ['contract' => '__ID__'])),
        activate: @json(route('app.contracts.activate', ['contract' => '__ID__'])),
        suspend: @json(route('app.contracts.suspend', ['contract' => '__ID__'])),
        resume: @json(route('app.contracts.resume', ['contract' => '__ID__'])),
        terminate: @json(route('app.contracts.terminate', ['contract' => '__ID__'])),
        cancel: @json(route('app.contracts.cancel', ['contract' => '__ID__'])),
        restore: @json(route('app.contracts.restore', ['contract' => '__ID__'])),
        destroy: @json(route('app.contracts.destroy', ['contract' => '__ID__'])),
      };
      const permissions = {
        create: @json(auth()->user()->can('contracts.create')),
        update: @json(auth()->user()->can('contracts.update')),
        end: @json(auth()->user()->can('contracts.end')),
      };
      const todayText = @json(now()->toDateString());
      let currentPage = 1,
        confirmCallback = null,
        searchTimer = null;
      let optionsData = { employees: [], default_currency: "SAR" };
      const $contractModal = $("#contractModal"),
        $detailsModal = $("#contractDetailsModal"),
        $terminateModal = $("#terminateContractModal"),
        $confirmModal = $("#contractConfirmModal");
      $contractModal.appendTo("body");
      $detailsModal.appendTo("body");
      $terminateModal.appendTo("body");
      $confirmModal.appendTo("body");

      $.ajaxSetup({
        headers: {
          "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
          Accept: "application/json",
        },
      });
      function urlWithId(template, id) {
        return template.replace("__ID__", id);
      }
      function escapeHtml(value) {
        return $("<div>")
          .text(value == null ? "" : value)
          .html();
      }
      function displayValue(value, fallback) {
        return value === null || value === undefined || value === ""
          ? fallback || "—"
          : value;
      }
      function dateOnly(value) {
        return value ? String(value).substring(0, 10) : "";
      }
      function dateTimeLocal(value) {
        return value ? String(value).substring(0, 16) : "";
      }
      function money(value) {
        return Number(value || 0).toLocaleString("en-US", {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        });
      }
      function showToast(message, type) {
        const $toast = $('<div class="contracts-toast"></div>')
          .addClass(
            type === "error" ? "contracts-toast-error" : "contracts-toast-success",
          )
          .text(message)
          .appendTo("#contractsToastContainer");
        $toast.stop(true, true).fadeIn(180);
        window.setTimeout(function () {
          $toast.fadeOut(250, function () {
            $(this).remove();
          });
        }, 4000);
      }
      function showAjaxError(xhr, fallback) {
        let message = fallback || "تعذر تنفيذ العملية.";
        if (xhr.responseJSON && xhr.responseJSON.message) {
          message = xhr.responseJSON.message;
        }
        if (xhr.status === 401) {
          message = "انتهت جلسة الدخول. يرجى تسجيل الدخول مجددًا.";
        }
        showToast(message, "error");
      }
      function openOverlay($overlay) {
        $(".contracts-modal-overlay:visible")
          .not($overlay)
          .hide()
          .attr("aria-hidden", "true");
        $("body").addClass("contracts-modal-open");
        $overlay
          .stop(true, true)
          .show()
          .attr({ "aria-hidden": "false", "aria-modal": "true" });
        $overlay.find(".contracts-modal-body").scrollTop(0);
      }
      function closeOverlay($overlay) {
        $overlay
          .stop(true, true)
          .hide()
          .attr("aria-hidden", "true")
          .removeAttr("aria-modal");
        if (!$(".contracts-modal-overlay:visible").length) {
          $("body").removeClass("contracts-modal-open");
        }
      }
      function setEmployeeOptions($select, placeholder, selectedValue) {
        const selected = selectedValue == null ? "" : String(selectedValue);
        $select.empty().append($("<option>").val("").text(placeholder));
        $.each(optionsData.employees || [], function (_, employee) {
          $select.append(
            $("<option>")
              .val(employee.id)
              .text(employee.name + " (" + employee.employee_number + ")"),
          );
        });
        $select.val(selected);
      }
      function renderEmployeeOptions() {
        setEmployeeOptions(
          $("#filterContractEmployee"),
          "جميع الموظفين",
          $("#filterContractEmployee").val(),
        );
        setEmployeeOptions(
          $("#contractEmployee"),
          "اختر الموظف",
          $("#contractEmployee").val(),
        );
      }
      function loadOptions(callback) {
        $.ajax({
          url: urls.options,
          type: "GET",
          success: function (response) {
            optionsData = response.options || optionsData;
            renderEmployeeOptions();
            if (typeof callback === "function") {
              callback();
            }
          },
          error: function (xhr) {
            showAjaxError(xhr, "تعذر تحميل قائمة الموظفين.");
          },
        });
      }
      function statusBadge(status, label) {
        const allowed = [
          "draft",
          "active",
          "suspended",
          "expired",
          "terminated",
          "cancelled",
        ];
        const cssStatus = allowed.indexOf(status) >= 0 ? status : "draft";
        return (
          '<span class="contracts-status contracts-status-' +
          cssStatus +
          '">' +
          escapeHtml(label || status) +
          "</span>"
        );
      }
      function employeeInitial(name) {
        name = String(name || "").trim();
        return name ? name.charAt(0) : "م";
      }
      function actionOptions(contract) {
        let html =
          '<option value="">اختر إجراء</option><option value="details">التفاصيل</option>';
        if (contract.is_archived) {
          return permissions.update
            ? html + '<option value="restore">استعادة</option>'
            : html;
        }
        if (contract.status === "draft" && permissions.update) {
          html +=
            '<option value="edit">تعديل</option><option value="activate">تفعيل</option><option value="cancel">إلغاء المسودة</option><option value="archive">أرشفة</option>';
        }
        if (contract.status === "active") {
          if (permissions.update) {
            html += '<option value="suspend">إيقاف مؤقت</option>';
          }
          if (permissions.end) {
            html += '<option value="terminate">إنهاء العقد</option>';
          }
        }
        if (contract.status === "suspended") {
          if (permissions.update) {
            html += '<option value="resume">استئناف</option>';
          }
          if (permissions.end) {
            html += '<option value="terminate">إنهاء العقد</option>';
          }
        }
        if (
          permissions.update &&
          ["expired", "cancelled"].indexOf(contract.status) >= 0
        ) {
          html += '<option value="archive">أرشفة</option>';
        }
        return html;
      }
      function renderRows(rows) {
        const $body = $("#contractsTableBody").empty();
        if (!rows || !rows.length) {
          $body.html(
            '<tr><td colspan="8" class="contracts-empty">لا توجد عقود مطابقة لمعايير البحث.</td></tr>',
          );
          return;
        }
        $.each(rows, function (index, contract) {
          const employee = contract.employee || {};
          const number =
            (currentPage - 1) * parseInt($("#filterContractPerPage").val(), 10) +
            index +
            1;
          const period = contract.end_date
            ? dateOnly(contract.start_date) + " — " + dateOnly(contract.end_date)
            : dateOnly(contract.start_date) + " — غير محدد";
          $body.append(
            '<tr class="' +
              (contract.is_archived ? "table-light opacity-75" : "") +
              '"><td>' +
              number +
              '</td><td><div class="d-flex align-items-center gap-2"><span class="contracts-avatar">' +
              escapeHtml(employeeInitial(employee.name)) +
              '</span><div><div class="fw-semibold">' +
              escapeHtml(displayValue(employee.name)) +
              '</div><div class="small text-muted" dir="ltr">' +
              escapeHtml(displayValue(employee.employee_number)) +
              '</div></div></div></td><td dir="ltr" class="fw-semibold">' +
              escapeHtml(contract.contract_number) +
              "</td><td>" +
              escapeHtml(contract.contract_type_label) +
              '</td><td dir="ltr">' +
              escapeHtml(period) +
              '</td><td dir="ltr" class="fw-semibold">' +
              money(contract.gross_salary) +
              " " +
              escapeHtml(contract.currency_code) +
              "</td><td>" +
              statusBadge(contract.status, contract.status_label) +
              '</td><td class="text-center"><select class="form-select form-select-sm contracts-action js-contract-action" data-id="' +
              contract.id +
              '" data-name="' +
              escapeHtml(employee.name) +
              '">' +
              actionOptions(contract) +
              "</select></td></tr>",
          );
        });
      }
      function renderPagination(response) {
        const $pagination = $("#contractsPagination").empty(),
          current = parseInt(response.current_page || 1, 10),
          last = parseInt(response.last_page || 1, 10),
          start = Math.max(1, current - 2),
          end = Math.min(last, current + 2);
        function addPage(label, page, disabled, active) {
          const $item = $('<li class="page-item"></li>')
            .toggleClass("disabled", !!disabled)
            .toggleClass("active", !!active);
          $item.append(
            $('<button type="button" class="page-link"></button>')
              .text(label)
              .attr("data-page", page),
          );
          $pagination.append($item);
        }
        addPage("السابق", current - 1, current <= 1, false);
        for (let page = start; page <= end; page++) {
          addPage(page, page, false, page === current);
        }
        addPage("التالي", current + 1, current >= last, false);
        $("#contractsCount").text((response.total || 0) + " عقد");
        $("#contractsRange").text(
          response.total
            ? "عرض " +
                response.from +
                " إلى " +
                response.to +
                " من أصل " +
                response.total
            : "لا توجد نتائج",
        );
      }
      function loadContracts(page) {
        currentPage = page || 1;
        $("#contractsTableBody").html(
          '<tr><td colspan="8" class="contracts-loading">جاري تحميل العقود...</td></tr>',
        );
        $.ajax({
          url: urls.data,
          type: "GET",
          data: {
            page: currentPage,
            search: $("#filterContractSearch").val(),
            employee_id: $("#filterContractEmployee").val(),
            status: $("#filterContractStatus").val(),
            contract_type: $("#filterContractType").val(),
            expiring_days: $("#filterContractExpiring").val(),
            archive_status: $("#filterContractArchive").val(),
            per_page: $("#filterContractPerPage").val(),
          },
          success: function (response) {
            currentPage = parseInt(response.current_page || 1, 10);
            renderRows(response.data || []);
            renderPagination(response);
          },
          error: function (xhr) {
            $("#contractsTableBody").html(
              '<tr><td colspan="8" class="contracts-empty text-danger">تعذر تحميل بيانات العقود.</td></tr>',
            );
            showAjaxError(xhr, "تعذر تحميل بيانات العقود.");
          },
        });
      }
      function clearErrors($form, $box) {
        $box.addClass("d-none").empty();
        $form.find(".is-invalid").removeClass("is-invalid");
        $form.find(".invalid-feedback.contract-server-error").remove();
      }
      function showErrors($form, $box, errors) {
        const messages = [];
        $.each(errors || {}, function (field, fieldMessages) {
          const message =
            fieldMessages && fieldMessages.length
              ? fieldMessages[0]
              : "القيمة غير صحيحة.";
          const $field = $form.find('[name="' + field + '"]').first();
          messages.push(message);
          if ($field.length) {
            $field.addClass("is-invalid");
            const $target = $field.closest(".input-group").length
              ? $field.closest(".input-group")
              : $field;
            $('<div class="invalid-feedback contract-server-error"></div>')
              .text(message)
              .insertAfter($target);
          }
        });
        if (messages.length) {
          $box.removeClass("d-none").html(
            messages
              .map(function (message) {
                return "<div>" + escapeHtml(message) + "</div>";
              })
              .join(""),
          );
        }
      }
      function toggleType() {
        const indefinite = $("#contractType").val() === "indefinite";
        $("#contractEndDateContainer").toggleClass("d-none", indefinite);
        $("#contractEndDate")
          .prop("disabled", indefinite)
          .prop("required", !indefinite);
        $("#contractRenewalSection").toggleClass("d-none", indefinite);
        if (indefinite) {
          $("#contractEndDate").val("");
          $("#contractAutoRenew").prop("checked", false);
        }
      }
      function toggleAutoRenew() {
        $("#contractRenewalNoticeContainer").toggleClass(
          "d-none",
          !$("#contractAutoRenew").is(":checked"),
        );
      }
      function syncDates() {
        const start = $("#contractStartDate").val();
        $("#contractEndDate, #contractProbationEndDate").attr("min", start || "");
      }
      function updateGross() {
        let total = 0;
        $(".contract-money").each(function () {
          total += Number($(this).val() || 0);
        });
        $("#contractGrossSalary").text(
          money(total) +
            " " +
            ($("#contractCurrency").val() || "SAR").toUpperCase(),
        );
      }
      function resetForm() {
        $("#contractForm")[0].reset();
        $("#contractId").val("");
        $("#contractModalTitle").text("إضافة عقد");
        $("#btnSaveContract").text("حفظ المسودة");
        $("#contractEmployee").prop("disabled", false);
        $("#contractType").val("fixed_term");
        $("#contractStartDate").val(todayText);
        $("#contractEndDate, #contractProbationEndDate").val("");
        $(
          "#contractBasicSalary, #contractHousingAllowance, #contractTransportAllowance, #contractOtherAllowances",
        ).val("0");
        $("#contractCurrency").val(optionsData.default_currency || "SAR");
        $("#contractPayFrequency").val("monthly");
        $("#contractHoursPerDay").val("8");
        $("#contractDaysPerWeek").val("5");
        $("#contractAnnualLeave").val("21");
        $("#contractNoticePeriod, #contractRenewalNotice").val("30");
        $("#contractAutoRenew").prop("checked", false);
        clearErrors($("#contractForm"), $("#contractFormErrors"));
        toggleType();
        toggleAutoRenew();
        updateGross();
        syncDates();
      }
      function openCreate() {
        resetForm();
        renderEmployeeOptions();
        openOverlay($contractModal);
      }
      function populateForm(contract) {
        $("#contractId").val(contract.id);
        $("#contractModalTitle").text("تعديل مسودة العقد");
        $("#btnSaveContract").text("حفظ التعديلات");
        renderEmployeeOptions();
        $("#contractEmployee").val(contract.employee_id).prop("disabled", true);
        $("#contractNumber").val(contract.contract_number || "");
        $("#contractType").val(contract.contract_type || "fixed_term");
        $("#contractStartDate").val(dateOnly(contract.start_date));
        $("#contractEndDate").val(dateOnly(contract.end_date));
        $("#contractProbationEndDate").val(dateOnly(contract.probation_end_date));
        $("#contractSignedAt").val(dateTimeLocal(contract.signed_at));
        $("#contractBasicSalary").val(contract.basic_salary || "0");
        $("#contractHousingAllowance").val(contract.housing_allowance || "0");
        $("#contractTransportAllowance").val(contract.transport_allowance || "0");
        $("#contractOtherAllowances").val(contract.other_allowances || "0");
        $("#contractCurrency").val(contract.currency_code || "SAR");
        $("#contractPayFrequency").val(contract.pay_frequency || "monthly");
        $("#contractHoursPerDay").val(contract.working_hours_per_day || "8");
        $("#contractDaysPerWeek").val(contract.working_days_per_week || "5");
        $("#contractAnnualLeave").val(contract.annual_leave_days || "21");
        $("#contractNoticePeriod").val(contract.notice_period_days || "30");
        $("#contractAutoRenew").prop("checked", !!contract.auto_renew);
        $("#contractRenewalNotice").val(contract.renewal_notice_days || "30");
        $("#contractTerms").val(contract.terms || "");
        $("#contractNotes").val(contract.notes || "");
        toggleType();
        toggleAutoRenew();
        updateGross();
        syncDates();
      }
      function openEdit(id) {
        resetForm();
        openOverlay($contractModal);
        $("#btnSaveContract").prop("disabled", true).text("جاري التحميل...");
        $.ajax({
          url: urlWithId(urls.show, id),
          type: "GET",
          success: function (response) {
            populateForm(response.contract);
            $("#btnSaveContract").prop("disabled", false).text("حفظ التعديلات");
          },
          error: function (xhr) {
            closeOverlay($contractModal);
            showAjaxError(xhr, "تعذر تحميل بيانات العقد.");
          },
        });
      }
      function detailItem(label, value, dir) {
        return (
          '<div class="col-md-6"><div class="contracts-detail"><div class="contracts-detail-label">' +
          escapeHtml(label) +
          '</div><div class="fw-semibold" ' +
          (dir ? 'dir="' + dir + '"' : "") +
          ">" +
          escapeHtml(displayValue(value)) +
          "</div></div></div>"
        );
      }
      function detailFull(label, value) {
        return (
          '<div class="col-12"><div class="contracts-detail"><div class="contracts-detail-label">' +
          escapeHtml(label) +
          '</div><div class="fw-semibold" style="white-space:pre-line">' +
          escapeHtml(displayValue(value)) +
          "</div></div></div>"
        );
      }
      function showDetails(id) {
        $("#contractDetailsBody").html(
          '<div class="text-center text-muted py-5">جاري تحميل البيانات...</div>',
        );
        openOverlay($detailsModal);
        $.ajax({
          url: urlWithId(urls.show, id),
          type: "GET",
          success: function (response) {
            const c = response.contract,
              e = c.employee || {};
            let html =
              '<div class="text-center mb-4"><h5 class="mb-1">' +
              escapeHtml(e.full_name || e.display_name || "—") +
              '</h5><div class="text-muted mb-2" dir="ltr">' +
              escapeHtml(c.contract_number) +
              "</div>" +
              statusBadge(c.status, c.status_label) +
              '</div><div class="row g-3">';
            html +=
              detailItem("رقم الموظف", e.employee_number, "ltr") +
              detailItem("نوع العقد", c.contract_type_label) +
              detailItem("تاريخ البداية", dateOnly(c.start_date), "ltr") +
              detailItem(
                "تاريخ النهاية",
                dateOnly(c.end_date) || "غير محدد",
                "ltr",
              ) +
              detailItem(
                "نهاية فترة التجربة",
                dateOnly(c.probation_end_date),
                "ltr",
              ) +
              detailItem("دورية الدفع", c.pay_frequency_label);
            html +=
              detailItem(
                "الراتب الأساسي",
                money(c.basic_salary) + " " + c.currency_code,
                "ltr",
              ) +
              detailItem(
                "بدل السكن",
                money(c.housing_allowance) + " " + c.currency_code,
                "ltr",
              ) +
              detailItem(
                "بدل النقل",
                money(c.transport_allowance) + " " + c.currency_code,
                "ltr",
              ) +
              detailItem(
                "بدلات أخرى",
                money(c.other_allowances) + " " + c.currency_code,
                "ltr",
              ) +
              detailItem(
                "إجمالي الراتب",
                money(c.gross_salary) + " " + c.currency_code,
                "ltr",
              );
            html +=
              detailItem("ساعات العمل يوميًا", c.working_hours_per_day, "ltr") +
              detailItem("أيام العمل أسبوعيًا", c.working_days_per_week, "ltr") +
              detailItem("الإجازة السنوية", c.annual_leave_days + " يوم") +
              detailItem("مدة الإشعار", c.notice_period_days + " يوم") +
              detailItem("التجديد التلقائي", c.auto_renew ? "نعم" : "لا") +
              detailItem("تاريخ التوقيع", dateOnly(c.signed_at), "ltr");
            if (c.termination_date) {
              html +=
                detailItem("تاريخ الإنهاء", dateOnly(c.termination_date), "ltr") +
                detailFull("سبب الإنهاء", c.termination_reason);
            }
            html +=
              detailFull("شروط العقد", c.terms) +
              detailFull("الملاحظات", c.notes) +
              "</div>";
            $("#contractDetailsBody").html(html);
          },
          error: function (xhr) {
            closeOverlay($detailsModal);
            showAjaxError(xhr, "تعذر تحميل تفاصيل العقد.");
          },
        });
      }
      function openConfirm(message, callback, danger) {
        confirmCallback = callback;
        $("#contractConfirmMessage").text(message);
        $("#btnAcceptContractConfirm")
          .toggleClass("btn-danger", danger !== false)
          .toggleClass("btn-primary", danger === false);
        openOverlay($confirmModal);
      }
      function runAction(url, method) {
        $.ajax({
          url: url,
          type: method || "POST",
          success: function (response) {
            showToast(response.message || "تم تنفيذ العملية.", "success");
            loadContracts(currentPage);
          },
          error: function (xhr) {
            showAjaxError(xhr, "تعذر تنفيذ العملية على العقد.");
          },
        });
      }
      function openTerminate(id, name) {
        $("#terminateContractForm")[0].reset();
        $("#terminateContractId").val(id);
        $("#terminateContractTitle").text("إنهاء عقد: " + (name || "الموظف"));
        $("#contractTerminationDate").val(todayText);
        clearErrors($("#terminateContractForm"), $("#terminateContractErrors"));
        openOverlay($terminateModal);
      }

      $("#contractForm").on("submit", function (event) {
        event.preventDefault();
        const id = $("#contractId").val(),
          $form = $(this),
          url = id ? urlWithId(urls.update, id) : urls.store;
        const formData = $.grep($form.serializeArray(), function (field) {
          return field.name !== "auto_renew";
        });
        formData.push({
          name: "auto_renew",
          value: $("#contractAutoRenew").is(":checked") ? "1" : "0",
        });
        if (id) {
          formData.push({ name: "_method", value: "PUT" });
        }
        clearErrors($form, $("#contractFormErrors"));
        $("#btnSaveContract").prop("disabled", true).text("جاري الحفظ...");
        $.ajax({
          url: url,
          type: "POST",
          data: $.param(formData),
          success: function (response) {
            closeOverlay($contractModal);
            showToast(response.message || "تم حفظ العقد بنجاح.", "success");
            loadContracts(id ? currentPage : 1);
          },
          error: function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
              showErrors($form, $("#contractFormErrors"), xhr.responseJSON.errors);
              $(".contracts-modal-body", $contractModal).scrollTop(0);
            } else {
              showAjaxError(xhr, "تعذر حفظ العقد.");
            }
          },
          complete: function () {
            $("#btnSaveContract")
              .prop("disabled", false)
              .text(id ? "حفظ التعديلات" : "حفظ المسودة");
          },
        });
      });
      $("#terminateContractForm").on("submit", function (event) {
        event.preventDefault();
        const id = $("#terminateContractId").val(),
          $form = $(this);
        clearErrors($form, $("#terminateContractErrors"));
        $("#btnConfirmTerminate").prop("disabled", true).text("جاري الإنهاء...");
        $.ajax({
          url: urlWithId(urls.terminate, id),
          type: "POST",
          data: $form.serialize(),
          success: function (response) {
            closeOverlay($terminateModal);
            showToast(response.message || "تم إنهاء العقد.", "success");
            loadContracts(currentPage);
          },
          error: function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
              showErrors(
                $form,
                $("#terminateContractErrors"),
                xhr.responseJSON.errors,
              );
            } else {
              showAjaxError(xhr, "تعذر إنهاء العقد.");
            }
          },
          complete: function () {
            $("#btnConfirmTerminate").prop("disabled", false).text("تأكيد الإنهاء");
          },
        });
      });
      $(document).on("change", ".js-contract-action", function () {
        const $select = $(this),
          action = $select.val(),
          id = $select.data("id"),
          name = $select.data("name");
        $select.val("");
        if (!action) {
          return;
        }
        if (action === "details") {
          showDetails(id);
          return;
        }
        if (action === "edit") {
          openEdit(id);
          return;
        }
        if (action === "terminate") {
          openTerminate(id, name);
          return;
        }
        const actions = {
          activate: {
            message:
              "هل تريد تفعيل هذا العقد؟ بعد التفعيل لن يمكن تعديل بياناته المالية.",
            url: urlWithId(urls.activate, id),
            method: "POST",
            danger: false,
          },
          suspend: {
            message: "هل تريد إيقاف العقد مؤقتًا؟",
            url: urlWithId(urls.suspend, id),
            method: "POST",
          },
          resume: {
            message: "هل تريد استئناف العقد الموقوف؟",
            url: urlWithId(urls.resume, id),
            method: "POST",
            danger: false,
          },
          cancel: {
            message: "هل تريد إلغاء مسودة العقد؟",
            url: urlWithId(urls.cancel, id),
            method: "POST",
          },
          archive: {
            message: "هل تريد أرشفة هذا العقد؟",
            url: urlWithId(urls.destroy, id),
            method: "DELETE",
          },
          restore: {
            message: "هل تريد استعادة هذا العقد؟",
            url: urlWithId(urls.restore, id),
            method: "POST",
            danger: false,
          },
        };
        const selected = actions[action];
        if (selected) {
          openConfirm(
            selected.message,
            function () {
              runAction(selected.url, selected.method);
            },
            selected.danger,
          );
        }
      });
      $("#btnAcceptContractConfirm").on("click", function () {
        const callback = confirmCallback;
        confirmCallback = null;
        closeOverlay($confirmModal);
        if (typeof callback === "function") {
          callback();
        }
      });
      $("#btnCancelContractConfirm").on("click", function () {
        confirmCallback = null;
        closeOverlay($confirmModal);
      });
      $("#btnCreateContract").on("click", openCreate);
      $(".js-close-contract-modal").on("click", function () {
        closeOverlay($contractModal);
      });
      $(".js-close-details-modal").on("click", function () {
        closeOverlay($detailsModal);
      });
      $(".js-close-terminate-modal").on("click", function () {
        closeOverlay($terminateModal);
      });
      $(".contracts-modal-overlay").on("mousedown", function (event) {
        if ($(event.target).is(this) && !$(this).is($confirmModal)) {
          closeOverlay($(this));
        }
      });
      $(document).on("keydown", function (event) {
        if (event.key === "Escape") {
          const $visible = $(".contracts-modal-overlay:visible").last();
          if ($visible.length && !$visible.is($confirmModal)) {
            closeOverlay($visible);
          }
        }
      });
      $("#contractType").on("change", function () {
        toggleType();
        toggleAutoRenew();
      });
      $("#contractAutoRenew").on("change", toggleAutoRenew);
      $("#contractStartDate").on("change", syncDates);
      $(".contract-money, #contractCurrency").on("input change", updateGross);
      $("#btnSearchContracts").on("click", function () {
        loadContracts(1);
      });
      $("#btnResetContractFilters").on("click", function () {
        $(
          "#filterContractSearch, #filterContractEmployee, #filterContractStatus, #filterContractType, #filterContractExpiring",
        ).val("");
        $("#filterContractArchive").val("active");
        $("#filterContractPerPage").val("15");
        loadContracts(1);
      });
      $("#filterContractSearch")
        .on("input", function () {
          window.clearTimeout(searchTimer);
          searchTimer = window.setTimeout(function () {
            loadContracts(1);
          }, 450);
        })
        .on("keydown", function (event) {
          if (event.key === "Enter") {
            event.preventDefault();
            window.clearTimeout(searchTimer);
            loadContracts(1);
          }
        });
      $(
        "#filterContractEmployee, #filterContractStatus, #filterContractType, #filterContractExpiring, #filterContractArchive, #filterContractPerPage",
      ).on("change", function () {
        loadContracts(1);
      });
      $(document).on("click", "#contractsPagination .page-link", function () {
        const $item = $(this).closest(".page-item");
        if (!$item.hasClass("disabled") && !$item.hasClass("active")) {
          loadContracts(parseInt($(this).attr("data-page"), 10));
        }
      });
      loadOptions(function () {
        resetForm();
        loadContracts(1);
      });
    });
  </script>
@endpush