@extends('layouts.tenant')

@section('title', 'الورديات والتكليفات')
@section('page-title', 'الورديات والتكليفات')

@section('content')
  @include('tenant.attendance._styles')

  <div class="attendance-page">
    <div
      class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-4"
    >
      <div>
        <h4 class="mb-1">الورديات وتكليف الموظفين</h4>
        <p class="text-muted mb-0">إعداد ساعات الدوام والسياسة وربط كل موظف بورديته.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a
          href="{{ route('app.attendance.index') }}"
          class="btn btn-outline-secondary"
          >سجل الحضور</a
        >
        @can('attendance.manage')
          <button type="button" class="btn btn-outline-primary" id="btnPolicy">
            سياسة الحضور
          </button>
          <button
            type="button"
            class="btn btn-outline-primary"
            id="btnAssignShift"
          >
            تكليف موظف
          </button>
          <button type="button" class="btn btn-primary" id="btnCreateShift">
            + إضافة وردية
          </button>
        @endcan
      </div>
    </div>

    <div class="att-card p-3 p-lg-4 mb-4">
      <form id="shiftFilterForm">
        <div class="row g-3 align-items-end">
          <div class="col-lg-5">
            <label class="filter-label" for="shiftSearch">البحث</label
            ><input
              type="search"
              class="form-control"
              id="shiftSearch"
              placeholder="اسم أو كود الوردية"
            />
          </div>
          <div class="col-md-4 col-lg-2">
            <label class="filter-label" for="shiftTypeFilter">النوع</label
            ><select class="form-select" id="shiftTypeFilter">
              <option value="">كل الأنواع</option>
              <option value="regular">ثابتة</option>
              <option value="flexible">مرنة</option>
              <option value="night">ليلية</option>
            </select>
          </div>
          <div class="col-md-4 col-lg-2">
            <label class="filter-label" for="shiftActiveFilter">الحالة</label
            ><select class="form-select" id="shiftActiveFilter">
              <option value="">الكل</option>
              <option value="1">نشطة</option>
              <option value="0">غير نشطة</option>
            </select>
          </div>
          <div class="col-md-4 col-lg-1">
            <label class="filter-label" for="shiftPerPage">العدد</label
            ><select class="form-select" id="shiftPerPage">
              <option>15</option>
              <option>25</option>
              <option>50</option>
            </select>
          </div>
          <div class="col-lg-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1">
              بحث</button
            ><button
              type="button"
              class="btn btn-outline-secondary"
              id="resetShiftFilters"
            >
              إعادة
            </button>
          </div>
        </div>
      </form>
    </div>

    <div class="att-card overflow-hidden mb-4">
      <div
        class="d-flex justify-content-between align-items-center p-3 border-bottom"
      >
        <h6 class="mb-0">قائمة الورديات</h6>
        <span class="badge bg-primary-subtle text-primary" id="shiftCount"
          >0 وردية</span
        >
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>#</th>
              <th>الوردية</th>
              <th>النوع</th>
              <th>الوقت</th>
              <th>الاستراحة</th>
              <th>ساعات العمل</th>
              <th>أيام العمل</th>
              <th>الموظفون</th>
              <th>الحالة</th>
              <th class="text-center">الإجراءات</th>
            </tr>
          </thead>
          <tbody id="shiftTableBody">
            <tr>
              <td colspan="10" class="att-loading">جاري تحميل الورديات...</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div
        class="d-flex justify-content-between align-items-center gap-3 flex-wrap p-3 border-top"
      >
        <div class="small text-muted" id="shiftInfo">—</div>
        <ul class="pagination pagination-sm mb-0" id="shiftPagination"></ul>
      </div>
    </div>

    <div class="att-card overflow-hidden">
      <div
        class="d-flex justify-content-between align-items-center gap-3 flex-wrap p-3 border-bottom"
      >
        <div>
          <h6 class="mb-1">تكليفات الموظفين</h6>
          <div class="small text-muted">سجل تاريخي لتغييرات الورديات.</div>
        </div>
        <div class="d-flex gap-2">
          <input
            type="search"
            class="form-control form-control-sm"
            id="assignmentSearch"
            placeholder="بحث عن موظف"
          /><select class="form-select form-select-sm" id="assignmentPeriod">
            <option value="current">التكليفات الحالية</option>
            <option value="all">كل التكليفات</option></select
          ><button
            type="button"
            class="btn btn-sm btn-outline-primary"
            id="searchAssignments"
          >
            بحث
          </button>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>#</th>
              <th>الموظف</th>
              <th>الوردية</th>
              <th>من تاريخ</th>
              <th>إلى تاريخ</th>
              <th>الحالة</th>
              <th class="text-center">الإجراءات</th>
            </tr>
          </thead>
          <tbody id="assignmentTableBody">
            <tr>
              <td colspan="7" class="att-loading">جاري تحميل التكليفات...</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div
        class="d-flex justify-content-between align-items-center gap-3 flex-wrap p-3 border-top"
      >
        <div class="small text-muted" id="assignmentInfo">—</div>
        <ul
          class="pagination pagination-sm mb-0"
          id="assignmentPagination"
        ></ul>
      </div>
    </div>

    <div class="att-modal-overlay" id="shiftFormModal" aria-hidden="true">
      <div class="att-modal-dialog" role="dialog" aria-modal="true">
        <div class="att-modal-header">
          <div>
            <h5 class="mb-1" id="shiftFormTitle">إضافة وردية</h5>
            <div class="small text-muted">
              حدد الوقت والأيام وسيتم حساب الساعات تلقائيًا.
            </div>
          </div>
          <button type="button" class="att-modal-close js-close-modal">
            ×
          </button>
        </div>
        <form id="shiftForm">
          <div class="att-modal-body">
            <input type="hidden" id="shiftId" />
            <div class="alert alert-danger d-none" id="shiftErrors"></div>
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label" for="shiftCode"
                  >الكود <span class="text-danger">*</span></label
                ><input
                  type="text"
                  class="form-control"
                  id="shiftCode"
                  name="code"
                  maxlength="50"
                  required
                />
              </div>
              <div class="col-md-8">
                <label class="form-label" for="shiftName"
                  >اسم الوردية <span class="text-danger">*</span></label
                ><input
                  type="text"
                  class="form-control"
                  id="shiftName"
                  name="name"
                  maxlength="255"
                  required
                />
              </div>
              <div class="col-md-6">
                <label class="form-label" for="shiftNameEn"
                  >الاسم الإنجليزي</label
                ><input
                  type="text"
                  class="form-control"
                  id="shiftNameEn"
                  name="name_en"
                  maxlength="255"
                  dir="ltr"
                />
              </div>
              <div class="col-md-6">
                <label class="form-label" for="shiftType"
                  >نوع الوردية <span class="text-danger">*</span></label
                ><select
                  class="form-select"
                  id="shiftType"
                  name="shift_type"
                  required
                >
                  <option value="regular">ثابتة</option>
                  <option value="flexible">مرنة</option>
                  <option value="night">ليلية</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label" for="shiftStart"
                  >وقت البداية <span class="text-danger">*</span></label
                ><input
                  type="time"
                  class="form-control"
                  id="shiftStart"
                  name="start_time"
                  required
                />
              </div>
              <div class="col-md-4">
                <label class="form-label" for="shiftEnd"
                  >وقت النهاية <span class="text-danger">*</span></label
                ><input
                  type="time"
                  class="form-control"
                  id="shiftEnd"
                  name="end_time"
                  required
                />
              </div>
              <div class="col-md-4">
                <label class="form-label" for="shiftBreak"
                  >الاستراحة بالدقائق <span class="text-danger">*</span></label
                ><input
                  type="number"
                  class="form-control"
                  id="shiftBreak"
                  name="break_minutes"
                  min="0"
                  max="720"
                  value="60"
                  required
                />
              </div>
              <div class="col-12">
                <label class="form-label d-block"
                  >أيام العمل <span class="text-danger">*</span></label
                >
                <div class="d-flex gap-2 flex-wrap" id="shiftWorkDays">
                  <label class="day-check"
                    ><input type="checkbox" name="work_days[]" value="0" />
                    الأحد</label
                  ><label class="day-check"
                    ><input type="checkbox" name="work_days[]" value="1" />
                    الاثنين</label
                  ><label class="day-check"
                    ><input type="checkbox" name="work_days[]" value="2" />
                    الثلاثاء</label
                  ><label class="day-check"
                    ><input type="checkbox" name="work_days[]" value="3" />
                    الأربعاء</label
                  ><label class="day-check"
                    ><input type="checkbox" name="work_days[]" value="4" />
                    الخميس</label
                  ><label class="day-check"
                    ><input type="checkbox" name="work_days[]" value="5" />
                    الجمعة</label
                  ><label class="day-check"
                    ><input type="checkbox" name="work_days[]" value="6" />
                    السبت</label
                  >
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-check form-switch mt-4">
                  <input
                    class="form-check-input"
                    type="checkbox"
                    id="shiftCrosses"
                    name="crosses_midnight"
                    value="1"
                  /><label class="form-check-label" for="shiftCrosses"
                    >تنتهي في اليوم التالي</label
                  >
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-check form-switch mt-4">
                  <input
                    class="form-check-input"
                    type="checkbox"
                    id="shiftDefault"
                    name="is_default"
                    value="1"
                  /><label class="form-check-label" for="shiftDefault"
                    >الوردية الافتراضية</label
                  >
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-check form-switch mt-4">
                  <input
                    class="form-check-input"
                    type="checkbox"
                    id="shiftActive"
                    name="is_active"
                    value="1"
                    checked
                  /><label class="form-check-label" for="shiftActive"
                    >وردية نشطة</label
                  >
                </div>
              </div>
            </div>
          </div>
          <div class="att-modal-footer">
            <button type="button" class="btn btn-light js-close-modal">
              إلغاء</button
            ><button type="submit" class="btn btn-primary px-4" id="saveShift">
              حفظ الوردية
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="att-modal-overlay" id="assignmentModal" aria-hidden="true">
      <div
        class="att-modal-dialog att-modal-sm"
        role="dialog"
        aria-modal="true"
      >
        <div class="att-modal-header">
          <div>
            <h5 class="mb-1">تكليف موظف بورديّة</h5>
            <div class="small text-muted">يحفظ النظام تاريخ كل تغيير.</div>
          </div>
          <button type="button" class="att-modal-close js-close-modal">
            ×
          </button>
        </div>
        <form id="assignmentForm">
          <div class="att-modal-body">
            <div class="alert alert-danger d-none" id="assignmentErrors"></div>
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label" for="assignmentEmployee"
                  >الموظف <span class="text-danger">*</span></label
                ><select
                  class="form-select"
                  id="assignmentEmployee"
                  name="employee_id"
                  required
                >
                  <option value="">اختر الموظف</option>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label" for="assignmentShift"
                  >الوردية <span class="text-danger">*</span></label
                ><select
                  class="form-select"
                  id="assignmentShift"
                  name="work_shift_id"
                  required
                >
                  <option value="">اختر الوردية</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="assignmentFrom"
                  >من تاريخ <span class="text-danger">*</span></label
                ><input
                  type="date"
                  class="form-control"
                  id="assignmentFrom"
                  name="effective_from"
                  required
                />
              </div>
              <div class="col-md-6">
                <label class="form-label" for="assignmentTo">إلى تاريخ</label
                ><input
                  type="date"
                  class="form-control"
                  id="assignmentTo"
                  name="effective_to"
                />
              </div>
              <div class="col-12">
                <label class="form-label" for="assignmentNotes">ملاحظات</label
                ><textarea
                  class="form-control"
                  id="assignmentNotes"
                  name="notes"
                  maxlength="2000"
                ></textarea>
              </div>
              <input type="hidden" name="is_primary" value="1" />
            </div>
          </div>
          <div class="att-modal-footer">
            <button type="button" class="btn btn-light js-close-modal">
              إلغاء</button
            ><button type="submit" class="btn btn-primary" id="saveAssignment">
              حفظ التكليف
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="att-modal-overlay" id="policyModal" aria-hidden="true">
      <div class="att-modal-dialog" role="dialog" aria-modal="true">
        <div class="att-modal-header">
          <div>
            <h5 class="mb-1">سياسة الحضور الأساسية</h5>
            <div class="small text-muted">
              تطبق قواعدها على جميع الورديات المرتبطة.
            </div>
          </div>
          <button type="button" class="att-modal-close js-close-modal">
            ×
          </button>
        </div>
        <form id="policyForm">
          <div class="att-modal-body">
            <div class="alert alert-danger d-none" id="policyErrors"></div>
            <div class="row g-3">
              <div class="col-md-8">
                <label class="form-label" for="policyName">اسم السياسة</label
                ><input
                  type="text"
                  class="form-control"
                  id="policyName"
                  name="name"
                  required
                />
              </div>
              <div class="col-md-4">
                <label class="form-label" for="policyTimezone"
                  >المنطقة الزمنية</label
                ><select
                  class="form-select"
                  id="policyTimezone"
                  name="timezone"
                  required
                ></select>
              </div>
              <div class="col-md-4">
                <label class="form-label" for="policyLateGrace"
                  >سماح التأخير بالدقائق</label
                ><input
                  type="number"
                  class="form-control"
                  id="policyLateGrace"
                  name="late_grace_minutes"
                  min="0"
                  max="1440"
                  required
                />
              </div>
              <div class="col-md-4">
                <label class="form-label" for="policyEarlyGrace"
                  >سماح الخروج المبكر</label
                ><input
                  type="number"
                  class="form-control"
                  id="policyEarlyGrace"
                  name="early_leave_grace_minutes"
                  min="0"
                  max="1440"
                  required
                />
              </div>
              <div class="col-md-4">
                <label class="form-label" for="policyOvertime"
                  >بدء الإضافي بعد الانصراف</label
                ><input
                  type="number"
                  class="form-control"
                  id="policyOvertime"
                  name="overtime_after_minutes"
                  min="0"
                  max="1440"
                  required
                />
              </div>
              <div class="col-md-4">
                <label class="form-label" for="policyEarlyCheckIn"
                  >السماح بالحضور المبكر</label
                ><input
                  type="number"
                  class="form-control"
                  id="policyEarlyCheckIn"
                  name="early_check_in_minutes"
                  min="0"
                  max="1440"
                  required
                />
              </div>
              <div class="col-md-4">
                <label class="form-label" for="policyLateCheckOut"
                  >السماح بالانصراف المتأخر</label
                ><input
                  type="number"
                  class="form-control"
                  id="policyLateCheckOut"
                  name="late_check_out_minutes"
                  min="0"
                  max="1440"
                  required
                />
              </div>
              <div class="col-md-4">
                <label class="form-label" for="policyRounding"
                  >تقريب الوقت</label
                ><select
                  class="form-select"
                  id="policyRounding"
                  name="rounding_rule"
                >
                  <option value="none">بدون تقريب</option>
                  <option value="nearest_5">أقرب 5 دقائق</option>
                  <option value="nearest_10">أقرب 10 دقائق</option>
                  <option value="nearest_15">أقرب 15 دقيقة</option>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label d-block">عطلة نهاية الأسبوع</label>
                <div class="d-flex gap-2 flex-wrap" id="policyWeekend">
                  <label class="day-check"
                    ><input type="checkbox" name="weekend_days[]" value="0" />
                    الأحد</label
                  ><label class="day-check"
                    ><input type="checkbox" name="weekend_days[]" value="1" />
                    الاثنين</label
                  ><label class="day-check"
                    ><input type="checkbox" name="weekend_days[]" value="2" />
                    الثلاثاء</label
                  ><label class="day-check"
                    ><input type="checkbox" name="weekend_days[]" value="3" />
                    الأربعاء</label
                  ><label class="day-check"
                    ><input type="checkbox" name="weekend_days[]" value="4" />
                    الخميس</label
                  ><label class="day-check"
                    ><input type="checkbox" name="weekend_days[]" value="5" />
                    الجمعة</label
                  ><label class="day-check"
                    ><input type="checkbox" name="weekend_days[]" value="6" />
                    السبت</label
                  >
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-check form-switch">
                  <input
                    class="form-check-input"
                    type="checkbox"
                    id="policyAllowWeb"
                    name="allow_web"
                    value="1"
                  /><label class="form-check-label" for="policyAllowWeb"
                    >السماح من الويب</label
                  >
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-check form-switch">
                  <input
                    class="form-check-input"
                    type="checkbox"
                    id="policyAllowMobile"
                    name="allow_mobile"
                    value="1"
                  /><label class="form-check-label" for="policyAllowMobile"
                    >السماح من الجوال</label
                  >
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-check form-switch">
                  <input
                    class="form-check-input"
                    type="checkbox"
                    id="policyGeofence"
                    name="require_geofence"
                    value="1"
                  /><label class="form-check-label" for="policyGeofence"
                    >اشتراط النطاق الجغرافي</label
                  >
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-check form-switch">
                  <input
                    class="form-check-input"
                    type="checkbox"
                    id="policyOutside"
                    name="allow_outside_geofence"
                    value="1"
                  /><label class="form-check-label" for="policyOutside"
                    >السماح خارج النطاق</label
                  >
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-check form-switch">
                  <input
                    class="form-check-input"
                    type="checkbox"
                    id="policyPhoto"
                    name="require_photo"
                    value="1"
                  /><label class="form-check-label" for="policyPhoto"
                    >طلب صورة</label
                  >
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-check form-switch">
                  <input
                    class="form-check-input"
                    type="checkbox"
                    id="policyAutoOut"
                    name="auto_check_out"
                    value="1"
                  /><label class="form-check-label" for="policyAutoOut"
                    >انصراف تلقائي</label
                  >
                </div>
              </div>
            </div>
          </div>
          <div class="att-modal-footer">
            <button type="button" class="btn btn-light js-close-modal">
              إلغاء</button
            ><button type="submit" class="btn btn-primary" id="savePolicy">
              حفظ السياسة
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="att-modal-overlay" id="shiftConfirmModal" aria-hidden="true">
      <div class="att-modal-dialog att-modal-sm">
        <div class="att-modal-header">
          <h5 class="mb-0" id="shiftConfirmTitle">تأكيد الإجراء</h5>
          <button type="button" class="att-modal-close js-close-modal">
            ×
          </button>
        </div>
        <div class="att-modal-body">
          <p class="mb-0" id="shiftConfirmMessage"></p>
        </div>
        <div class="att-modal-footer">
          <button type="button" class="btn btn-light js-close-modal">
            إلغاء</button
          ><button type="button" class="btn btn-danger" id="confirmShiftAction">
            تأكيد
          </button>
        </div>
      </div>
    </div>
    <div class="att-toast" id="shiftToast"></div>
  </div>
@endsection

@push('scripts')
  <script>
    jQuery(function ($) {
      "use strict";

      const urls = {
        data: @json(route('app.attendance.shifts.data')),
        options: @json(route('app.attendance.shifts.options')),
        store: @json(route('app.attendance.shifts.store')),
        show: @json(route('app.attendance.shifts.show', ['shift' => '__ID__'])),
        update: @json(route('app.attendance.shifts.update', ['shift' => '__ID__'])),
        destroy: @json(route('app.attendance.shifts.destroy', ['shift' => '__ID__'])),
        assignmentsData: @json(route('app.attendance.shifts.assignments.data')),
        assign: @json(route('app.attendance.shifts.assignments.store')),
        endAssignment: @json(route('app.attendance.shifts.assignments.end', ['assignment' => '__ID__'])),
        policyUpdate: @json(route('app.attendance.policy.update')),
      };
      const permissions = { manage: @json(auth()->user()->can('attendance.manage')) };
      const state = {
        shiftPage: 1,
        assignmentPage: 1,
        loaded: false,
        options: {},
        confirmAction: null,
      };
      const dayNames = [
        "الأحد",
        "الاثنين",
        "الثلاثاء",
        "الأربعاء",
        "الخميس",
        "الجمعة",
        "السبت",
      ];

      $.ajaxSetup({
        headers: {
          "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
          Accept: "application/json",
        },
      });
      function routeUrl(t, id) {
        return t.replace("__ID__", String(id));
      }
      function esc(v) {
        return $("<div>")
          .text(v == null ? "" : String(v))
          .html();
      }
      function dash(v) {
        const t = v == null ? "" : String(v).trim();
        return t === "" ? "—" : esc(t);
      }
      function dateText(v) {
        if (!v) return "مستمر";
        const p = String(v).substring(0, 10).split("-");
        return p.length === 3 ? p[2] + "/" + p[1] + "/" + p[0] : esc(v);
      }
      function duration(v) {
        const n = Number(v || 0);
        return Math.floor(n / 60) + "س " + (n % 60) + "د";
      }
      function showModal(s) {
        $(s).attr("aria-hidden", "false").show();
        $("body").addClass("att-modal-open");
      }
      function hideModal(s) {
        $(s).attr("aria-hidden", "true").hide();
        if (!$(".att-modal-overlay:visible").length)
          $("body").removeClass("att-modal-open");
      }
      function msg(xhr) {
        return xhr.responseJSON && xhr.responseJSON.message
          ? xhr.responseJSON.message
          : "حدث خطأ غير متوقع.";
      }
      function toast(text, type) {
        $("#shiftToast")
          .stop(true, true)
          .removeClass("success error")
          .addClass(type === "error" ? "error" : "success")
          .text(text)
          .fadeIn(180)
          .delay(2600)
          .fadeOut(220);
      }
      function errors(xhr, selector) {
        const data =
          xhr.responseJSON && xhr.responseJSON.errors
            ? xhr.responseJSON.errors
            : null;
        let html = "";
        if (data) {
          html = '<ul class="mb-0">';
          Object.keys(data).forEach(function (k) {
            data[k].forEach(function (x) {
              html += "<li>" + esc(x) + "</li>";
            });
          });
          html += "</ul>";
        } else html = esc(msg(xhr));
        $(selector).removeClass("d-none").html(html);
      }

      function pagination(target, response, cls) {
        const current = Number(response.current_page || 1),
          last = Number(response.last_page || 1);
        let html = "";
        function one(p, label, dis, active) {
          return (
            '<li class="page-item ' +
            (dis ? "disabled " : "") +
            (active ? "active" : "") +
            '"><button type="button" class="page-link ' +
            cls +
            '" data-page="' +
            p +
            '" ' +
            (dis ? "disabled" : "") +
            ">" +
            label +
            "</button></li>"
          );
        }
        html += one(current - 1, "‹", current <= 1, false);
        for (
          let p = Math.max(1, current - 2);
          p <= Math.min(last, current + 2);
          p += 1
        )
          html += one(p, p, false, p === current);
        html += one(current + 1, "›", current >= last, false);
        $(target).html(html);
      }

      function shiftActions(item) {
        if (!permissions.manage) return "—";
        return (
          '<div class="d-flex justify-content-center gap-1"><button type="button" class="btn btn-sm btn-outline-primary js-shift-edit" data-id="' +
          item.id +
          '">تعديل</button><button type="button" class="btn btn-sm btn-outline-danger js-shift-delete" data-id="' +
          item.id +
          '">أرشفة</button></div>'
        );
      }

      function loadShifts(page) {
        state.shiftPage = page || 1;
        $("#shiftTableBody").html(
          '<tr><td colspan="10" class="att-loading">جاري التحميل...</td></tr>',
        );
        $.ajax({
          url: urls.data,
          type: "GET",
          data: {
            page: state.shiftPage,
            search: String($("#shiftSearch").val() || "").trim(),
            shift_type: $("#shiftTypeFilter").val(),
            is_active: $("#shiftActiveFilter").val(),
            per_page: $("#shiftPerPage").val(),
          },
          success: function (r) {
            const items = Array.isArray(r.data) ? r.data : [];
            let html = "";
            if (!items.length)
              html =
                '<tr><td colspan="10"><div class="att-empty"><div class="fs-2 mb-2">◷</div><div class="fw-bold">لا توجد ورديات</div></div></td></tr>';
            items.forEach(function (x, i) {
              const days = (x.work_days || [])
                .map(function (d) {
                  return dayNames[d] || d;
                })
                .join("، ");
              html +=
                "<tr><td>" +
                (Number(r.from || 1) + i) +
                '</td><td><div class="employee-name">' +
                esc(x.name) +
                (x.is_default
                  ? ' <span class="badge bg-primary-subtle text-primary">افتراضية</span>'
                  : "") +
                '</div><div class="att-meta">' +
                esc(x.code) +
                '</div></td><td><span class="att-badge att-info">' +
                esc(x.shift_type_label) +
                '</span></td><td dir="ltr">' +
                esc(x.time_range) +
                (x.crosses_midnight
                  ? '<div class="att-meta">اليوم التالي</div>'
                  : "") +
                "</td><td>" +
                x.break_minutes +
                " د</td><td>" +
                duration(x.working_minutes) +
                '</td><td class="small">' +
                dash(days) +
                "</td><td>" +
                x.active_assignments_count +
                '</td><td><span class="att-badge ' +
                (x.is_active ? "att-success" : "att-neutral") +
                '">' +
                (x.is_active ? "نشطة" : "غير نشطة") +
                "</span></td><td>" +
                shiftActions(x) +
                "</td></tr>";
            });
            $("#shiftTableBody").html(html);
            pagination("#shiftPagination", r, "js-shift-page");
            $("#shiftCount").text((r.total || 0) + " وردية");
            $("#shiftInfo").text(
              r.total
                ? "عرض " + r.from + " إلى " + r.to + " من " + r.total
                : "لا توجد نتائج",
            );
          },
          error: function (xhr) {
            $("#shiftTableBody").html(
              '<tr><td colspan="10" class="att-loading text-danger">' +
                esc(msg(xhr)) +
                "</td></tr>",
            );
          },
        });
      }

      function loadAssignments(page) {
        state.assignmentPage = page || 1;
        $("#assignmentTableBody").html(
          '<tr><td colspan="7" class="att-loading">جاري التحميل...</td></tr>',
        );
        $.ajax({
          url: urls.assignmentsData,
          type: "GET",
          data: {
            page: state.assignmentPage,
            search: String($("#assignmentSearch").val() || "").trim(),
            period: $("#assignmentPeriod").val(),
            per_page: 15,
          },
          success: function (r) {
            const items = Array.isArray(r.data) ? r.data : [];
            let html = "";
            if (!items.length)
              html =
                '<tr><td colspan="7"><div class="att-empty"><div class="fw-bold">لا توجد تكليفات</div></div></td></tr>';
            items.forEach(function (x, i) {
              const e = x.employee || {},
                s = x.shift || {};
              html +=
                "<tr><td>" +
                (Number(r.from || 1) + i) +
                '</td><td><div class="employee-name">' +
                dash(e.name) +
                '</div><div class="att-meta">' +
                dash(e.employee_number) +
                (e.department ? " · " + esc(e.department) : "") +
                '</div></td><td><div class="fw-bold">' +
                dash(s.name) +
                '</div><div class="att-meta" dir="ltr">' +
                (s.start_time ? esc(String(s.start_time).substring(0, 5)) : "—") +
                " - " +
                (s.end_time ? esc(String(s.end_time).substring(0, 5)) : "—") +
                "</div></td><td>" +
                dateText(x.effective_from) +
                "</td><td>" +
                dateText(x.effective_to) +
                '</td><td><span class="att-badge ' +
                (x.is_current ? "att-success" : "att-neutral") +
                '">' +
                (x.is_current ? "حالي" : "منتهي") +
                '</span></td><td class="text-center">' +
                (permissions.manage && x.is_current
                  ? '<button type="button" class="btn btn-sm btn-outline-danger js-end-assignment" data-id="' +
                    x.id +
                    '">إنهاء</button>'
                  : "—") +
                "</td></tr>";
            });
            $("#assignmentTableBody").html(html);
            pagination("#assignmentPagination", r, "js-assignment-page");
            $("#assignmentInfo").text(
              r.total
                ? "عرض " + r.from + " إلى " + r.to + " من " + r.total
                : "لا توجد نتائج",
            );
          },
          error: function (xhr) {
            $("#assignmentTableBody").html(
              '<tr><td colspan="7" class="att-loading text-danger">' +
                esc(msg(xhr)) +
                "</td></tr>",
            );
          },
        });
      }

      function fill($select, items, placeholder, kind) {
        let html = '<option value="">' + esc(placeholder) + "</option>";
        items.forEach(function (x) {
          let label = x.name;
          if (kind === "employee") label = x.employee_number + " — " + x.name;
          if (kind === "shift") label = x.name + " (" + x.time_range + ")";
          if (kind === "timezone") label = x.label;
          html +=
            '<option value="' +
            esc(x.id || x.value) +
            '">' +
            esc(label) +
            "</option>";
        });
        $select.html(html);
      }

      function loadOptions(callback, force) {
        if (state.loaded && !force) {
          if (typeof callback === "function") callback();
          return;
        }
        $.ajax({
          url: urls.options,
          type: "GET",
          success: function (r) {
            state.options = r.options || {};
            const o = state.options;
            fill(
              $("#assignmentEmployee"),
              o.employees || [],
              "اختر الموظف",
              "employee",
            );
            fill($("#assignmentShift"), o.shifts || [], "اختر الوردية", "shift");
            fill(
              $("#policyTimezone"),
              o.timezones || [],
              "اختر المنطقة الزمنية",
              "timezone",
            );
            state.loaded = true;
            if (typeof callback === "function") callback();
          },
          error: function (xhr) {
            toast(msg(xhr), "error");
          },
        });
      }

      function resetShiftForm() {
        $("#shiftForm")[0].reset();
        $("#shiftId").val("");
        $("#shiftActive").prop("checked", true);
        $("#shiftBreak").val(60);
        $("#shiftWorkDays input").prop("checked", false);
        [0, 1, 2, 3, 4].forEach(function (d) {
          $('#shiftWorkDays input[value="' + d + '"]').prop("checked", true);
        });
        $("#shiftErrors").addClass("d-none").empty();
        $("#shiftFormTitle").text("إضافة وردية");
        $("#saveShift").text("حفظ الوردية").prop("disabled", false);
      }
      function fetchShift(id, cb) {
        $.ajax({
          url: routeUrl(urls.show, id),
          type: "GET",
          success: function (r) {
            if (typeof cb === "function") cb(r.shift || {});
          },
          error: function (xhr) {
            toast(msg(xhr), "error");
          },
        });
      }
      function openShift(id) {
        resetShiftForm();
        if (!id) {
          showModal("#shiftFormModal");
          return;
        }
        fetchShift(id, function (x) {
          $("#shiftId").val(x.id);
          $("#shiftCode").val(x.code);
          $("#shiftName").val(x.name);
          $("#shiftNameEn").val(x.name_en || "");
          $("#shiftType").val(x.shift_type);
          $("#shiftStart").val(String(x.start_time).substring(0, 5));
          $("#shiftEnd").val(String(x.end_time).substring(0, 5));
          $("#shiftBreak").val(x.break_minutes);
          $("#shiftCrosses").prop("checked", Boolean(x.crosses_midnight));
          $("#shiftDefault").prop("checked", Boolean(x.is_default));
          $("#shiftActive").prop("checked", Boolean(x.is_active));
          $("#shiftWorkDays input").prop("checked", false);
          (x.work_days || []).forEach(function (d) {
            $('#shiftWorkDays input[value="' + d + '"]').prop("checked", true);
          });
          $("#shiftFormTitle").text("تعديل الوردية");
          $("#saveShift").text("حفظ التعديلات");
          showModal("#shiftFormModal");
        });
      }

      function openAssignment() {
        loadOptions(function () {
          $("#assignmentForm")[0].reset();
          $("#assignmentErrors").addClass("d-none").empty();
          const n = new Date();
          $("#assignmentFrom").val(
            n.getFullYear() +
              "-" +
              String(n.getMonth() + 1).padStart(2, "0") +
              "-" +
              String(n.getDate()).padStart(2, "0"),
          );
          $("#saveAssignment").prop("disabled", false).text("حفظ التكليف");
          showModal("#assignmentModal");
        });
      }
      function setPolicyForm() {
        const p = state.options.policy || {};
        $("#policyName").val(p.name || "");
        $("#policyTimezone").val(p.timezone || "Asia/Riyadh");
        $("#policyLateGrace").val(p.late_grace_minutes ?? 10);
        $("#policyEarlyGrace").val(p.early_leave_grace_minutes ?? 5);
        $("#policyOvertime").val(p.overtime_after_minutes ?? 0);
        $("#policyEarlyCheckIn").val(p.early_check_in_minutes ?? 120);
        $("#policyLateCheckOut").val(p.late_check_out_minutes ?? 240);
        $("#policyRounding").val(p.rounding_rule || "none");
        $("#policyAllowWeb").prop("checked", Boolean(p.allow_web));
        $("#policyAllowMobile").prop("checked", Boolean(p.allow_mobile));
        $("#policyGeofence").prop("checked", Boolean(p.require_geofence));
        $("#policyOutside").prop("checked", Boolean(p.allow_outside_geofence));
        $("#policyPhoto").prop("checked", Boolean(p.require_photo));
        $("#policyAutoOut").prop("checked", Boolean(p.auto_check_out));
        $("#policyWeekend input").prop("checked", false);
        (p.weekend_days || []).forEach(function (d) {
          $('#policyWeekend input[value="' + d + '"]').prop("checked", true);
        });
        $("#policyErrors").addClass("d-none").empty();
        $("#savePolicy").prop("disabled", false).text("حفظ السياسة");
      }

      function confirmAction(title, text, cls, cb) {
        state.confirmAction = cb;
        $("#shiftConfirmTitle").text(title);
        $("#shiftConfirmMessage").text(text);
        $("#confirmShiftAction")
          .removeClass("btn-danger btn-success btn-warning")
          .addClass(cls || "btn-danger")
          .prop("disabled", false)
          .text("تأكيد");
        showModal("#shiftConfirmModal");
      }
      function actionRequest(url, method, data) {
        $.ajax({
          url: url,
          type: method || "POST",
          data: data || {},
          success: function (r) {
            hideModal("#shiftConfirmModal");
            toast(r.message || "تم تنفيذ الإجراء.", "success");
            loadShifts(state.shiftPage);
            loadAssignments(state.assignmentPage);
            loadOptions(null, true);
          },
          error: function (xhr) {
            $("#confirmShiftAction").prop("disabled", false).text("تأكيد");
            toast(msg(xhr), "error");
          },
        });
      }

      $("#shiftFilterForm").on("submit", function (e) {
        e.preventDefault();
        loadShifts(1);
      });
      $("#resetShiftFilters").on("click", function () {
        $("#shiftFilterForm")[0].reset();
        loadShifts(1);
      });
      $("#searchAssignments").on("click", function () {
        loadAssignments(1);
      });
      $("#btnCreateShift").on("click", function () {
        openShift(null);
      });
      $("#btnAssignShift").on("click", openAssignment);
      $("#btnPolicy").on("click", function () {
        loadOptions(function () {
          setPolicyForm();
          showModal("#policyModal");
        });
      });
      $(document).on("click", ".js-close-modal", function () {
        hideModal($(this).closest(".att-modal-overlay"));
      });
      $(".att-modal-overlay").on("click", function (e) {
        if (e.target === this) hideModal(this);
      });
      $(document).on("keydown", function (e) {
        if (e.key === "Escape")
          $(".att-modal-overlay:visible").each(function () {
            hideModal(this);
          });
      });
      $(document).on("click", ".js-shift-page", function () {
        loadShifts(Number($(this).data("page")));
      });
      $(document).on("click", ".js-assignment-page", function () {
        loadAssignments(Number($(this).data("page")));
      });
      $(document).on("click", ".js-shift-edit", function () {
        openShift($(this).data("id"));
      });
      $(document).on("click", ".js-shift-delete", function () {
        const id = $(this).data("id");
        confirmAction(
          "أرشفة الوردية",
          "لا يمكن أرشفة وردية مرتبطة بتكليف حالي.",
          "btn-danger",
          function () {
            actionRequest(routeUrl(urls.destroy, id), "DELETE");
          },
        );
      });
      $(document).on("click", ".js-end-assignment", function () {
        const id = $(this).data("id");
        confirmAction(
          "إنهاء التكليف",
          "سيتم إنهاء التكليف بتاريخ اليوم.",
          "btn-warning",
          function () {
            actionRequest(routeUrl(urls.endAssignment, id));
          },
        );
      });
      $("#confirmShiftAction").on("click", function () {
        if (typeof state.confirmAction !== "function") return;
        $(this).prop("disabled", true).text("جاري التنفيذ...");
        state.confirmAction();
      });

      $("#shiftForm").on("submit", function (e) {
        e.preventDefault();
        const id = $("#shiftId").val(),
          fd = new FormData(this);
        if (!fd.has("crosses_midnight")) fd.append("crosses_midnight", "0");
        if (!fd.has("is_default")) fd.append("is_default", "0");
        if (!fd.has("is_active")) fd.append("is_active", "0");
        fd.append("attendance_policy_id", state.options.policy.id);
        if (id) fd.append("_method", "PUT");
        $("#shiftErrors").addClass("d-none").empty();
        $("#saveShift").prop("disabled", true).text("جاري الحفظ...");
        $.ajax({
          url: id ? routeUrl(urls.update, id) : urls.store,
          type: "POST",
          data: fd,
          processData: false,
          contentType: false,
          success: function (r) {
            hideModal("#shiftFormModal");
            toast(r.message || "تم الحفظ.", "success");
            state.loaded = false;
            loadOptions(function () {
              loadShifts(id ? state.shiftPage : 1);
            });
          },
          error: function (xhr) {
            errors(xhr, "#shiftErrors");
            $("#saveShift")
              .prop("disabled", false)
              .text(id ? "حفظ التعديلات" : "حفظ الوردية");
          },
        });
      });
      $("#assignmentForm").on("submit", function (e) {
        e.preventDefault();
        const fd = new FormData(this);
        $("#assignmentErrors").addClass("d-none").empty();
        $("#saveAssignment").prop("disabled", true).text("جاري الحفظ...");
        $.ajax({
          url: urls.assign,
          type: "POST",
          data: fd,
          processData: false,
          contentType: false,
          success: function (r) {
            hideModal("#assignmentModal");
            toast(r.message || "تم حفظ التكليف.", "success");
            loadAssignments(1);
            loadShifts(state.shiftPage);
          },
          error: function (xhr) {
            errors(xhr, "#assignmentErrors");
            $("#saveAssignment").prop("disabled", false).text("حفظ التكليف");
          },
        });
      });
      $("#policyForm").on("submit", function (e) {
        e.preventDefault();
        const fd = new FormData(this);
        [
          "allow_web",
          "allow_mobile",
          "require_geofence",
          "allow_outside_geofence",
          "require_photo",
          "auto_check_out",
        ].forEach(function (name) {
          if (!fd.has(name)) fd.append(name, "0");
        });
        fd.append("_method", "PUT");
        $("#policyErrors").addClass("d-none").empty();
        $("#savePolicy").prop("disabled", true).text("جاري الحفظ...");
        $.ajax({
          url: urls.policyUpdate,
          type: "POST",
          data: fd,
          processData: false,
          contentType: false,
          success: function (r) {
            hideModal("#policyModal");
            toast(r.message || "تم حفظ السياسة.", "success");
            state.loaded = false;
            loadOptions();
          },
          error: function (xhr) {
            errors(xhr, "#policyErrors");
            $("#savePolicy").prop("disabled", false).text("حفظ السياسة");
          },
        });
      });

      loadOptions(function () {
        loadShifts(1);
        loadAssignments(1);
      });
    });
  </script>
@endpush
