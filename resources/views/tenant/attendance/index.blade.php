@extends('layouts.tenant')

@section('title', 'الحضور والانصراف')
@section('page-title', 'الحضور والانصراف')

@section('content')
  @include('tenant.attendance._styles')

  <div class="attendance-page">
    <div
      class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-4"
    >
      <div>
        <h4 class="mb-1">سجل الحضور والانصراف</h4>
        <p class="text-muted mb-0">متابعة الدوام والتأخير والعمل الإضافي والاعتماد.</p>
      </div>

      <div class="d-flex gap-2 flex-wrap">
        <a
          href="{{ route('app.attendance.shifts.index') }}"
          class="btn btn-outline-primary"
        >
          الورديات والتكليفات
        </a>

        @can('attendance.manage')
          <button
            type="button"
            class="btn btn-primary"
            id="btnCreateAttendance"
          >
            + تسجيل حضور يدوي
          </button>
        @endcan
      </div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-6 col-xl-2">
        <div
          class="att-card att-summary d-flex justify-content-between align-items-center"
        >
          <div>
            <div class="att-summary-label">السجلات</div>
            <div class="att-summary-value" id="sumTotal">0</div>
          </div>
          <span class="att-summary-icon att-info">#</span>
        </div>
      </div>
      <div class="col-6 col-xl-2">
        <div
          class="att-card att-summary d-flex justify-content-between align-items-center"
        >
          <div>
            <div class="att-summary-label">الحضور</div>
            <div class="att-summary-value text-success" id="sumPresent">0</div>
          </div>
          <span class="att-summary-icon att-success">✓</span>
        </div>
      </div>
      <div class="col-6 col-xl-2">
        <div
          class="att-card att-summary d-flex justify-content-between align-items-center"
        >
          <div>
            <div class="att-summary-label">التأخير</div>
            <div class="att-summary-value text-warning" id="sumLate">0</div>
          </div>
          <span class="att-summary-icon att-warning">!</span>
        </div>
      </div>
      <div class="col-6 col-xl-2">
        <div
          class="att-card att-summary d-flex justify-content-between align-items-center"
        >
          <div>
            <div class="att-summary-label">الغياب</div>
            <div class="att-summary-value text-danger" id="sumAbsent">0</div>
          </div>
          <span class="att-summary-icon att-danger">×</span>
        </div>
      </div>
      <div class="col-6 col-xl-2">
        <div
          class="att-card att-summary d-flex justify-content-between align-items-center"
        >
          <div>
            <div class="att-summary-label">غير مكتمل</div>
            <div class="att-summary-value" id="sumIncomplete">0</div>
          </div>
          <span class="att-summary-icon att-neutral">…</span>
        </div>
      </div>
      <div class="col-6 col-xl-2">
        <div
          class="att-card att-summary d-flex justify-content-between align-items-center"
        >
          <div>
            <div class="att-summary-label">بانتظار الاعتماد</div>
            <div class="att-summary-value text-primary" id="sumPending">0</div>
          </div>
          <span class="att-summary-icon att-info">⌛</span>
        </div>
      </div>
    </div>

    <div class="att-card p-3 p-lg-4 mb-4">
      <form id="attendanceFilterForm">
        <div class="row g-3 align-items-end">
          <div class="col-lg-3">
            <label class="filter-label" for="filterSearch">البحث</label>
            <input
              type="search"
              class="form-control"
              id="filterSearch"
              placeholder="اسم أو رقم الموظف"
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
          <div class="col-md-6 col-lg-2">
            <label class="filter-label" for="filterEmployee">الموظف</label>
            <select class="form-select" id="filterEmployee">
              <option value="">كل الموظفين</option>
            </select>
          </div>
          <div class="col-md-6 col-lg-2">
            <label class="filter-label" for="filterShift">الوردية</label>
            <select class="form-select" id="filterShift">
              <option value="">كل الورديات</option>
            </select>
          </div>
          <div class="col-md-6 col-lg-2">
            <label class="filter-label" for="filterStatus">الحالة</label>
            <select class="form-select" id="filterStatus">
              <option value="">كل الحالات</option>
              <option value="present">حاضر</option>
              <option value="late">متأخر</option>
              <option value="absent">غائب</option>
              <option value="on_leave">إجازة</option>
              <option value="holiday">عطلة</option>
              <option value="remote">عمل عن بعد</option>
              <option value="incomplete">غير مكتمل</option>
            </select>
          </div>
          <div class="col-md-6 col-lg-2">
            <label class="filter-label" for="filterApproval">الاعتماد</label>
            <select class="form-select" id="filterApproval">
              <option value="">الكل</option>
              <option value="pending">بانتظار الاعتماد</option>
              <option value="approved">معتمد</option>
              <option value="rejected">مرفوض</option>
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
            <button type="submit" class="btn btn-primary flex-grow-1">
              بحث
            </button>
            <button
              type="button"
              class="btn btn-outline-secondary"
              id="btnResetFilters"
            >
              إعادة
            </button>
          </div>
        </div>
      </form>
    </div>

    <div class="att-card overflow-hidden">
      <div
        class="d-flex justify-content-between align-items-center p-3 border-bottom"
      >
        <h6 class="mb-0">سجلات الدوام</h6>
        <span class="badge bg-primary-subtle text-primary" id="attendanceCount"
          >0 سجل</span
        >
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>#</th>
              <th>الموظف</th>
              <th>التاريخ</th>
              <th>الوردية</th>
              <th>الحضور</th>
              <th>الانصراف</th>
              <th>الحالة</th>
              <th>العمل</th>
              <th>الاعتماد</th>
              <th class="text-center">الإجراءات</th>
            </tr>
          </thead>
          <tbody id="attendanceTableBody">
            <tr>
              <td colspan="10" class="att-loading">جاري تحميل البيانات...</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div
        class="d-flex justify-content-between align-items-center gap-3 flex-wrap p-3 border-top"
      >
        <div class="small text-muted" id="attendanceInfo">—</div>
        <ul
          class="pagination pagination-sm mb-0"
          id="attendancePagination"
        ></ul>
      </div>
    </div>

    <div class="att-modal-overlay" id="attendanceFormModal" aria-hidden="true">
      <div class="att-modal-dialog" role="dialog" aria-modal="true">
        <div class="att-modal-header">
          <div>
            <h5 class="mb-1" id="attendanceFormTitle">تسجيل حضور يدوي</h5>
            <div class="small text-muted">
              يتم احتساب التأخير والساعات تلقائيًا.
            </div>
          </div>
          <button type="button" class="att-modal-close js-close-modal">
            ×
          </button>
        </div>
        <form id="attendanceForm">
          <div class="att-modal-body">
            <input type="hidden" id="attendanceId" />
            <div
              class="alert alert-danger d-none"
              id="attendanceFormErrors"
            ></div>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" for="attendanceEmployee"
                  >الموظف <span class="text-danger">*</span></label
                ><select
                  class="form-select"
                  id="attendanceEmployee"
                  name="employee_id"
                  required
                >
                  <option value="">اختر الموظف</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="attendanceDate"
                  >التاريخ <span class="text-danger">*</span></label
                ><input
                  type="date"
                  class="form-control"
                  id="attendanceDate"
                  name="attendance_date"
                  required
                />
              </div>
              <div class="col-md-6">
                <label class="form-label" for="attendanceShift">الوردية</label
                ><select
                  class="form-select"
                  id="attendanceShift"
                  name="work_shift_id"
                >
                  <option value="">الوردية المكلف بها الموظف</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="attendanceLocation"
                  >موقع العمل</label
                ><select
                  class="form-select"
                  id="attendanceLocation"
                  name="work_location_id"
                >
                  <option value="">موقع الموظف الافتراضي</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label" for="attendanceStatus"
                  >الحالة <span class="text-danger">*</span></label
                ><select
                  class="form-select"
                  id="attendanceStatus"
                  name="status"
                  required
                >
                  <option value="present">حاضر</option>
                  <option value="late">متأخر</option>
                  <option value="absent">غائب</option>
                  <option value="on_leave">إجازة</option>
                  <option value="holiday">عطلة</option>
                  <option value="remote">عمل عن بعد</option>
                  <option value="incomplete">غير مكتمل</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label" for="attendanceCheckIn"
                  >وقت الحضور</label
                ><input
                  type="datetime-local"
                  class="form-control"
                  id="attendanceCheckIn"
                  name="check_in_at"
                />
              </div>
              <div class="col-md-4">
                <label class="form-label" for="attendanceCheckOut"
                  >وقت الانصراف</label
                ><input
                  type="datetime-local"
                  class="form-control"
                  id="attendanceCheckOut"
                  name="check_out_at"
                />
              </div>
              <div class="col-12">
                <label class="form-label" for="attendanceNotes">ملاحظات</label
                ><textarea
                  class="form-control"
                  id="attendanceNotes"
                  name="notes"
                  maxlength="5000"
                ></textarea>
              </div>
            </div>
          </div>
          <div class="att-modal-footer">
            <button type="button" class="btn btn-light js-close-modal">
              إلغاء</button
            ><button
              type="submit"
              class="btn btn-primary px-4"
              id="btnSaveAttendance"
            >
              حفظ السجل
            </button>
          </div>
        </form>
      </div>
    </div>

    <div
      class="att-modal-overlay"
      id="attendanceDetailsModal"
      aria-hidden="true"
    >
      <div class="att-modal-dialog" role="dialog" aria-modal="true">
        <div class="att-modal-header">
          <div>
            <h5 class="mb-1">تفاصيل سجل الحضور</h5>
            <div class="small text-muted">الأوقات والاحتساب والاعتماد.</div>
          </div>
          <button type="button" class="att-modal-close js-close-modal">
            ×
          </button>
        </div>
        <div class="att-modal-body" id="attendanceDetailsBody">
          <div class="att-loading">جاري تحميل التفاصيل...</div>
        </div>
        <div class="att-modal-footer">
          <button type="button" class="btn btn-light js-close-modal">
            إغلاق
          </button>
        </div>
      </div>
    </div>

    <div
      class="att-modal-overlay"
      id="attendanceConfirmModal"
      aria-hidden="true"
    >
      <div
        class="att-modal-dialog att-modal-sm"
        role="dialog"
        aria-modal="true"
      >
        <div class="att-modal-header">
          <h5 class="mb-0" id="attendanceConfirmTitle">تأكيد الإجراء</h5>
          <button type="button" class="att-modal-close js-close-modal">
            ×
          </button>
        </div>
        <div class="att-modal-body">
          <p class="mb-0" id="attendanceConfirmMessage"></p>
        </div>
        <div class="att-modal-footer">
          <button type="button" class="btn btn-light js-close-modal">
            إلغاء</button
          ><button
            type="button"
            class="btn btn-danger"
            id="btnConfirmAttendance"
          >
            تأكيد
          </button>
        </div>
      </div>
    </div>

    <div class="att-toast" id="attendanceToast"></div>
  </div>
@endsection

@push('scripts')
  <script>
    jQuery(function ($) {
      "use strict";

      const urls = {
        data: @json(route('app.attendance.data')),
        options: @json(route('app.attendance.options')),
        store: @json(route('app.attendance.store')),
        show: @json(route('app.attendance.show', ['record' => '__ID__'])),
        update: @json(route('app.attendance.update', ['record' => '__ID__'])),
        approve: @json(route('app.attendance.approve', ['record' => '__ID__'])),
        reopen: @json(route('app.attendance.reopen', ['record' => '__ID__'])),
        destroy: @json(route('app.attendance.destroy', ['record' => '__ID__'])),
      };

      const permissions = {
        manage: @json(auth()->user()->can('attendance.manage')),
        approve: @json(auth()->user()->can('attendance.approve')),
      };

      const state = {
        page: 1,
        optionsLoaded: false,
        confirmAction: null,
        today: "",
      };

      $.ajaxSetup({
        headers: {
          "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
          Accept: "application/json",
        },
      });

      function routeUrl(template, id) {
        return template.replace("__ID__", String(id));
      }
      function escapeHtml(value) {
        return $("<div>")
          .text(value == null ? "" : String(value))
          .html();
      }
      function valueOrDash(value) {
        const text = value == null ? "" : String(value).trim();
        return text === "" ? "—" : escapeHtml(text);
      }
      function dateText(value) {
        if (!value) return "—";
        const text = String(value).substring(0, 10);
        const p = text.split("-");
        return p.length === 3 ? p[2] + "/" + p[1] + "/" + p[0] : escapeHtml(text);
      }
      function timeText(value) {
        return value ? escapeHtml(String(value).substring(11, 16)) : "—";
      }
      function minutesText(value) {
        const n = Number(value || 0);
        return Math.floor(n / 60) + "س " + (n % 60) + "د";
      }
      function showModal(selector) {
        $(selector).attr("aria-hidden", "false").show();
        $("body").addClass("att-modal-open");
      }
      function hideModal(selector) {
        $(selector).attr("aria-hidden", "true").hide();
        if (!$(".att-modal-overlay:visible").length)
          $("body").removeClass("att-modal-open");
      }
      function message(xhr) {
        return xhr.responseJSON && xhr.responseJSON.message
          ? xhr.responseJSON.message
          : "حدث خطأ غير متوقع.";
      }

      function toast(text, type) {
        $("#attendanceToast")
          .stop(true, true)
          .removeClass("success error")
          .addClass(type === "error" ? "error" : "success")
          .text(text)
          .fadeIn(180)
          .delay(2600)
          .fadeOut(220);
      }

      function validationErrors(xhr) {
        const errors =
          xhr.responseJSON && xhr.responseJSON.errors
            ? xhr.responseJSON.errors
            : null;
        let html = "";
        if (errors) {
          html = '<ul class="mb-0">';
          Object.keys(errors).forEach(function (key) {
            errors[key].forEach(function (item) {
              html += "<li>" + escapeHtml(item) + "</li>";
            });
          });
          html += "</ul>";
        } else {
          html = escapeHtml(message(xhr));
        }
        $("#attendanceFormErrors").removeClass("d-none").html(html);
      }

      function statusBadge(item) {
        const cls = {
          present: "att-success",
          late: "att-warning",
          absent: "att-danger",
          on_leave: "att-info",
          holiday: "att-neutral",
          remote: "att-info",
          incomplete: "att-neutral",
        };
        return (
          '<span class="att-badge ' +
          (cls[item.status] || "att-neutral") +
          '">' +
          escapeHtml(item.status_label) +
          "</span>"
        );
      }

      function approvalBadge(item) {
        const cls =
          item.approval_status === "approved"
            ? "att-success"
            : item.approval_status === "rejected"
              ? "att-danger"
              : "att-warning";
        return (
          '<span class="att-badge ' +
          cls +
          '">' +
          escapeHtml(item.approval_status_label) +
          "</span>"
        );
      }

      function actions(item) {
        let html =
          '<div class="d-flex justify-content-center gap-1 flex-wrap"><button type="button" class="btn btn-sm btn-outline-secondary js-att-details" data-id="' +
          item.id +
          '">عرض</button>';
        if (permissions.manage && item.approval_status !== "approved") {
          html +=
            '<button type="button" class="btn btn-sm btn-outline-primary js-att-edit" data-id="' +
            item.id +
            '">تعديل</button>';
          html +=
            '<button type="button" class="btn btn-sm btn-outline-danger js-att-delete" data-id="' +
            item.id +
            '">أرشفة</button>';
        }
        if (
          permissions.approve &&
          item.approval_status !== "approved" &&
          item.status !== "incomplete"
        ) {
          html +=
            '<button type="button" class="btn btn-sm btn-outline-success js-att-approve" data-id="' +
            item.id +
            '">اعتماد</button>';
        }
        if (permissions.approve && item.approval_status === "approved") {
          html +=
            '<button type="button" class="btn btn-sm btn-outline-warning js-att-reopen" data-id="' +
            item.id +
            '">إلغاء الاعتماد</button>';
        }
        return html + "</div>";
      }

      function renderRows(items, from) {
        if (!items.length) {
          $("#attendanceTableBody").html(
            '<tr><td colspan="10"><div class="att-empty"><div class="fs-2 mb-2">◷</div><div class="fw-bold">لا توجد سجلات حضور</div></div></td></tr>',
          );
          return;
        }
        let html = "";
        items.forEach(function (item, index) {
          const employee = item.employee || {};
          const shift = item.shift || {};
          html += "<tr><td>" + (Number(from || 1) + index) + "</td>";
          html +=
            '<td><div class="employee-name">' +
            valueOrDash(employee.name) +
            '</div><div class="att-meta">' +
            valueOrDash(employee.employee_number) +
            (employee.department ? " · " + escapeHtml(employee.department) : "") +
            "</div></td>";
          html += "<td>" + dateText(item.attendance_date) + "</td>";
          html +=
            '<td><div class="fw-bold">' +
            valueOrDash(shift.name) +
            '</div><div class="att-meta">' +
            valueOrDash(shift.code) +
            "</div></td>";
          html +=
            '<td dir="ltr">' +
            timeText(item.check_in_at) +
            '</td><td dir="ltr">' +
            timeText(item.check_out_at) +
            "</td>";
          html +=
            "<td>" +
            statusBadge(item) +
            (item.late_minutes
              ? '<div class="att-meta text-warning">تأخير ' +
                item.late_minutes +
                " د</div>"
              : "") +
            "</td>";
          html +=
            '<td><div class="fw-bold" dir="ltr">' +
            escapeHtml(item.work_duration_label) +
            "</div>" +
            (item.overtime_minutes
              ? '<div class="att-meta text-success">إضافي ' +
                minutesText(item.overtime_minutes) +
                "</div>"
              : "") +
            "</td>";
          html +=
            "<td>" +
            approvalBadge(item) +
            "</td><td>" +
            actions(item) +
            "</td></tr>";
        });
        $("#attendanceTableBody").html(html);
      }

      function renderPagination(response) {
        const current = Number(response.current_page || 1),
          last = Number(response.last_page || 1);
        let html = "";
        function item(page, label, disabled, active) {
          return (
            '<li class="page-item ' +
            (disabled ? "disabled " : "") +
            (active ? "active" : "") +
            '"><button class="page-link js-att-page" type="button" data-page="' +
            page +
            '" ' +
            (disabled ? "disabled" : "") +
            ">" +
            label +
            "</button></li>"
          );
        }
        html += item(current - 1, "‹", current <= 1, false);
        for (
          let page = Math.max(1, current - 2);
          page <= Math.min(last, current + 2);
          page += 1
        )
          html += item(page, page, false, page === current);
        html += item(current + 1, "›", current >= last, false);
        $("#attendancePagination").html(html);
      }

      function renderSummary(summary) {
        summary = summary || {};
        $("#sumTotal").text(summary.total || 0);
        $("#sumPresent").text(summary.present || 0);
        $("#sumLate").text(summary.late || 0);
        $("#sumAbsent").text(summary.absent || 0);
        $("#sumIncomplete").text(summary.incomplete || 0);
        $("#sumPending").text(summary.pending || 0);
      }

      function loadRecords(page) {
        state.page = page || 1;
        $("#attendanceTableBody").html(
          '<tr><td colspan="10" class="att-loading">جاري تحميل البيانات...</td></tr>',
        );
        $.ajax({
          url: urls.data,
          type: "GET",
          data: {
            page: state.page,
            search: String($("#filterSearch").val() || "").trim(),
            date_from: $("#filterDateFrom").val(),
            date_to: $("#filterDateTo").val(),
            employee_id: $("#filterEmployee").val(),
            work_shift_id: $("#filterShift").val(),
            status: $("#filterStatus").val(),
            approval_status: $("#filterApproval").val(),
            per_page: $("#filterPerPage").val(),
          },
          success: function (response) {
            const items = Array.isArray(response.data) ? response.data : [];
            renderRows(items, response.from);
            renderPagination(response);
            renderSummary(response.summary);
            $("#attendanceCount").text((response.total || 0) + " سجل");
            $("#attendanceInfo").text(
              response.total
                ? "عرض " +
                    response.from +
                    " إلى " +
                    response.to +
                    " من " +
                    response.total
                : "لا توجد نتائج",
            );
          },
          error: function (xhr) {
            $("#attendanceTableBody").html(
              '<tr><td colspan="10" class="att-loading text-danger">' +
                escapeHtml(message(xhr)) +
                "</td></tr>",
            );
          },
        });
      }

      function fillSelect($select, items, placeholder, type) {
        let html = '<option value="">' + escapeHtml(placeholder) + "</option>";
        items.forEach(function (item) {
          let label = item.name || item.label || "";
          if (type === "employee") label = item.employee_number + " — " + item.name;
          if (type === "shift") label = item.name + " (" + item.time_range + ")";
          html +=
            '<option value="' + item.id + '">' + escapeHtml(label) + "</option>";
        });
        $select.html(html);
      }

      function loadOptions(callback) {
        if (state.optionsLoaded) {
          if (typeof callback === "function") callback();
          return;
        }
        $.ajax({
          url: urls.options,
          type: "GET",
          success: function (response) {
            const options = response.options || {};
            const employees = Array.isArray(options.employees)
                ? options.employees
                : [],
              shifts = Array.isArray(options.shifts) ? options.shifts : [],
              locations = Array.isArray(options.locations) ? options.locations : [];
            fillSelect($("#filterEmployee"), employees, "كل الموظفين", "employee");
            fillSelect(
              $("#attendanceEmployee"),
              employees,
              "اختر الموظف",
              "employee",
            );
            fillSelect($("#filterShift"), shifts, "كل الورديات", "shift");
            fillSelect(
              $("#attendanceShift"),
              shifts,
              "الوردية المكلف بها الموظف",
              "shift",
            );
            fillSelect(
              $("#attendanceLocation"),
              locations,
              "موقع الموظف الافتراضي",
              "location",
            );
            state.today = options.today || "";
            state.optionsLoaded = true;
            if (typeof callback === "function") callback();
          },
          error: function (xhr) {
            toast(message(xhr), "error");
          },
        });
      }

      function resetForm() {
        $("#attendanceForm")[0].reset();
        $("#attendanceId").val("");
        $("#attendanceEmployee,#attendanceDate").prop("disabled", false);
        $("#attendanceDate").val(state.today);
        $("#attendanceStatus").val("present");
        $("#attendanceFormErrors").addClass("d-none").empty();
        $("#attendanceFormTitle").text("تسجيل حضور يدوي");
        $("#btnSaveAttendance").text("حفظ السجل").prop("disabled", false);
      }

      function fetchRecord(id, callback) {
        $.ajax({
          url: routeUrl(urls.show, id),
          type: "GET",
          success: function (response) {
            if (typeof callback === "function") callback(response.record || {});
          },
          error: function (xhr) {
            toast(message(xhr), "error");
          },
        });
      }

      function openCreate() {
        loadOptions(function () {
          resetForm();
          showModal("#attendanceFormModal");
        });
      }

      function openEdit(id) {
        loadOptions(function () {
          fetchRecord(id, function (item) {
            resetForm();
            $("#attendanceId").val(item.id);
            $("#attendanceEmployee").val(item.employee_id).prop("disabled", true);
            $("#attendanceDate")
              .val(String(item.attendance_date || "").substring(0, 10))
              .prop("disabled", true);
            $("#attendanceShift").val(item.work_shift_id || "");
            $("#attendanceLocation").val(item.work_location_id || "");
            $("#attendanceStatus").val(item.status);
            $("#attendanceCheckIn").val(
              item.check_in_local ? item.check_in_local.replace(" ", "T") : "",
            );
            $("#attendanceCheckOut").val(
              item.check_out_local ? item.check_out_local.replace(" ", "T") : "",
            );
            $("#attendanceNotes").val(item.notes || "");
            $("#attendanceFormTitle").text("تعديل سجل الحضور");
            $("#btnSaveAttendance").text("حفظ التعديلات");
            showModal("#attendanceFormModal");
          });
        });
      }

      function detail(label, value) {
        return (
          '<div class="col-md-6"><div class="att-detail"><div class="att-detail-label">' +
          escapeHtml(label) +
          '</div><div class="att-detail-value">' +
          value +
          "</div></div></div>"
        );
      }

      function openDetails(id) {
        $("#attendanceDetailsBody").html(
          '<div class="att-loading">جاري تحميل التفاصيل...</div>',
        );
        showModal("#attendanceDetailsModal");
        fetchRecord(id, function (item) {
          const employee = item.employee || {},
            shift = item.shift || {},
            location = item.work_location || {},
            approver = item.approved_by || {},
            creator = item.created_by || {};
          let html = '<div class="row g-3">';
          html += detail("الموظف", valueOrDash(employee.full_name));
          html += detail("رقم الموظف", valueOrDash(employee.employee_number));
          html += detail("التاريخ", dateText(item.attendance_date));
          html += detail("الوردية", valueOrDash(shift.name));
          html += detail(
            "المجدول للحضور",
            valueOrDash(item.scheduled_check_in_local),
          );
          html += detail(
            "المجدول للانصراف",
            valueOrDash(item.scheduled_check_out_local),
          );
          html += detail("الحضور الفعلي", valueOrDash(item.check_in_local));
          html += detail("الانصراف الفعلي", valueOrDash(item.check_out_local));
          html += detail("الحالة", valueOrDash(item.status_label));
          html += detail("إجمالي العمل", minutesText(item.work_minutes));
          html += detail("التأخير", minutesText(item.late_minutes));
          html += detail("الخروج المبكر", minutesText(item.early_leave_minutes));
          html += detail("العمل الإضافي", minutesText(item.overtime_minutes));
          html += detail("موقع العمل", valueOrDash(location.name));
          html += detail("الاعتماد", valueOrDash(item.approval_status_label));
          html += detail("اعتمد بواسطة", valueOrDash(approver.name));
          html += detail("أنشئ بواسطة", valueOrDash(creator.name));
          html +=
            '<div class="col-12"><div class="att-detail"><div class="att-detail-label">الملاحظات</div><div class="att-detail-value">' +
            valueOrDash(item.notes) +
            "</div></div></div></div>";
          $("#attendanceDetailsBody").html(html);
        });
      }

      function confirmAction(title, text, cls, callback) {
        state.confirmAction = callback;
        $("#attendanceConfirmTitle").text(title);
        $("#attendanceConfirmMessage").text(text);
        $("#btnConfirmAttendance")
          .removeClass("btn-danger btn-success btn-warning")
          .addClass(cls || "btn-danger")
          .prop("disabled", false)
          .text("تأكيد");
        showModal("#attendanceConfirmModal");
      }

      function postAction(url, method) {
        $.ajax({
          url: url,
          type: method || "POST",
          success: function (response) {
            hideModal("#attendanceConfirmModal");
            toast(response.message || "تم تنفيذ الإجراء.", "success");
            loadRecords(state.page);
          },
          error: function (xhr) {
            $("#btnConfirmAttendance").prop("disabled", false).text("تأكيد");
            toast(message(xhr), "error");
          },
        });
      }

      $("#attendanceFilterForm").on("submit", function (event) {
        event.preventDefault();
        loadRecords(1);
      });
      $("#btnResetFilters").on("click", function () {
        $("#attendanceFilterForm")[0].reset();
        setDefaultDates();
        loadRecords(1);
      });
      $("#btnCreateAttendance").on("click", openCreate);
      $(document).on("click", ".js-close-modal", function () {
        hideModal($(this).closest(".att-modal-overlay"));
      });
      $(".att-modal-overlay").on("click", function (event) {
        if (event.target === this) hideModal(this);
      });
      $(document).on("keydown", function (event) {
        if (event.key === "Escape")
          $(".att-modal-overlay:visible").each(function () {
            hideModal(this);
          });
      });
      $(document).on("click", ".js-att-page", function () {
        const page = Number($(this).data("page"));
        if (page > 0) loadRecords(page);
      });
      $(document).on("click", ".js-att-details", function () {
        openDetails($(this).data("id"));
      });
      $(document).on("click", ".js-att-edit", function () {
        openEdit($(this).data("id"));
      });
      $(document).on("click", ".js-att-approve", function () {
        const id = $(this).data("id");
        confirmAction(
          "اعتماد السجل",
          "سيتم تثبيت السجل ومنع تعديله حتى إلغاء الاعتماد.",
          "btn-success",
          function () {
            postAction(routeUrl(urls.approve, id));
          },
        );
      });
      $(document).on("click", ".js-att-reopen", function () {
        const id = $(this).data("id");
        confirmAction(
          "إلغاء الاعتماد",
          "سيصبح السجل قابلاً للتعديل مرة أخرى.",
          "btn-warning",
          function () {
            postAction(routeUrl(urls.reopen, id));
          },
        );
      });
      $(document).on("click", ".js-att-delete", function () {
        const id = $(this).data("id");
        confirmAction(
          "أرشفة السجل",
          "هل تريد أرشفة سجل الحضور؟",
          "btn-danger",
          function () {
            postAction(routeUrl(urls.destroy, id), "DELETE");
          },
        );
      });
      $("#btnConfirmAttendance").on("click", function () {
        if (typeof state.confirmAction !== "function") return;
        $(this).prop("disabled", true).text("جاري التنفيذ...");
        state.confirmAction();
      });

      $("#attendanceForm").on("submit", function (event) {
        event.preventDefault();
        const id = $("#attendanceId").val();
        const formData = new FormData(this);
        if (id) {
          formData.delete("employee_id");
          formData.delete("attendance_date");
          formData.append("_method", "PUT");
        }
        $("#attendanceFormErrors").addClass("d-none").empty();
        $("#btnSaveAttendance").prop("disabled", true).text("جاري الحفظ...");
        $.ajax({
          url: id ? routeUrl(urls.update, id) : urls.store,
          type: "POST",
          data: formData,
          processData: false,
          contentType: false,
          success: function (response) {
            hideModal("#attendanceFormModal");
            toast(response.message || "تم الحفظ.", "success");
            loadRecords(id ? state.page : 1);
          },
          error: function (xhr) {
            validationErrors(xhr);
            $("#btnSaveAttendance")
              .prop("disabled", false)
              .text(id ? "حفظ التعديلات" : "حفظ السجل");
          },
        });
      });

      function setDefaultDates() {
        const now = new Date();
        const today =
          now.getFullYear() +
          "-" +
          String(now.getMonth() + 1).padStart(2, "0") +
          "-" +
          String(now.getDate()).padStart(2, "0");
        const first =
          now.getFullYear() +
          "-" +
          String(now.getMonth() + 1).padStart(2, "0") +
          "-01";
        $("#filterDateFrom").val(first);
        $("#filterDateTo").val(today);
      }

      setDefaultDates();
      loadOptions(function () {
        loadRecords(1);
      });
    });
  </script>
@endpush
