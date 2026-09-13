@extends('layouts.tenant')

@section('title', 'اعتماد السلف')
@section('page-title', 'اعتماد السلف')

@section('content')
<style>
    .approval-page { direction: rtl; }
    .approval-table th, .approval-table td { white-space: nowrap; vertical-align: middle; }
    .approval-empty, .approval-loading { padding: 55px 20px !important; text-align: center; }
    .approval-empty { color: #64748b; }
    .approval-loading { color: #2563eb; }
    .approval-alert { display: none; }
    .approval-actions { display: flex; gap: 5px; flex-wrap: wrap; }
    .approval-pagination { display: flex; justify-content: space-between; align-items: center; gap: 15px; flex-wrap: wrap; }
    .approval-page-buttons { display: flex; gap: 5px; direction: ltr; }
    .money { direction: ltr; display: inline-block; font-weight: 700; }
    .summary-box { height: 100%; padding: 15px; border: 1px solid #e2e8f0; border-radius: 14px; background: #f8fafc; }
    .summary-box small { display: block; color: #64748b; margin-bottom: 6px; }
    body.ry-modal-open { overflow: hidden !important; }
    .ry-modal { position: fixed; inset: 0; z-index: 99999; display: none; align-items: center; justify-content: center; padding: 24px; overflow-y: auto; background: rgba(15,23,42,.68); }
    .ry-modal-panel { width: min(950px,100%); max-height: calc(100vh - 48px); display: flex; flex-direction: column; overflow: hidden; background: #fff; border-radius: 20px; box-shadow: 0 25px 70px rgba(15,23,42,.28); }
    .ry-modal-panel.small { width: min(560px,100%); }
    .ry-modal-header, .ry-modal-footer { flex: 0 0 auto; padding: 18px 22px; background: #fff; }
    .ry-modal-header { border-bottom: 1px solid #e9ecef; }
    .ry-modal-footer { border-top: 1px solid #e9ecef; }
    .ry-modal-body { flex: 1 1 auto; min-height: 0; padding: 22px; overflow: auto; }
    .ry-modal-close { width: 40px; height: 40px; border: 0; border-radius: 12px; background: #f1f5f9; color: #334155; font-size: 24px; line-height: 1; }
    @media (max-width:767.98px) { .ry-modal { padding: 8px; align-items: stretch; } .ry-modal-panel { max-height: calc(100vh - 16px); border-radius: 14px; } .ry-modal-header,.ry-modal-footer,.ry-modal-body { padding: 15px; } }
</style>

<div class="approval-page">
    <div id="pageAlert" class="alert approval-alert mb-4"></div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                <div><h5 class="mb-2">اعتماد السلف</h5><p class="text-muted mb-0">مراجعة طلبات السلف وتحديد المبلغ وجدول الأقساط قبل الاعتماد.</p></div>
                <span class="badge bg-warning-subtle text-warning px-3 py-2" id="pendingCount">0 بانتظار الاعتماد</span>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <form id="filterForm">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-5"><label class="form-label">البحث</label><input type="text" class="form-control" id="filterSearch" placeholder="رقم الطلب أو اسم الموظف"></div>
                    <div class="col-lg-3"><label class="form-label">الحالة</label><select class="form-select" id="filterStatus"><option value="submitted">بانتظار الاعتماد</option><option value="approved">معتمدة</option><option value="active">قيد السداد</option><option value="completed">مكتملة</option><option value="rejected">مرفوضة</option><option value="cancelled">ملغاة</option></select></div>
                    <div class="col-lg-2"><label class="form-label">عدد السجلات</label><select class="form-select" id="filterPerPage"><option value="10">10</option><option value="20" selected>20</option><option value="50">50</option></select></div>
                    <div class="col-lg-2"><div class="d-flex gap-2"><button class="btn btn-primary flex-grow-1" type="submit">بحث</button><button class="btn btn-light border" type="button" id="btnReset">إعادة</button></div></div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 p-4"><div class="d-flex justify-content-between align-items-center"><div><h6 class="mb-1">طلبات السلف</h6><small class="text-muted" id="recordsSummary">جاري التحميل...</small></div><span class="badge bg-primary-subtle text-primary px-3 py-2" id="recordsCount">0 طلب</span></div></div>
        <div class="table-responsive"><table class="table table-hover approval-table mb-0"><thead class="table-light"><tr><th>#</th><th>رقم الطلب</th><th>الموظف</th><th>النوع</th><th>المبلغ المطلوب</th><th>الأقساط</th><th>تاريخ الطلب</th><th>الحالة</th><th>الإجراءات</th></tr></thead><tbody id="tableBody"><tr><td colspan="9" class="approval-loading">جاري تحميل البيانات...</td></tr></tbody></table></div>
        <div class="card-footer bg-white border-0 p-4"><div class="approval-pagination"><div class="small text-muted" id="paginationSummary"></div><div class="approval-page-buttons" id="paginationButtons"></div></div></div>
    </div>
</div>

<div class="ry-modal" id="detailsModal">
    <div class="ry-modal-panel"><div class="ry-modal-header"><div class="d-flex justify-content-between align-items-center"><div><h5 class="mb-1">تفاصيل طلب السلفة</h5><small class="text-muted" id="detailsNumber"></small></div><button class="ry-modal-close close-details" type="button">×</button></div></div><div class="ry-modal-body" id="detailsBody"></div><div class="ry-modal-footer"><div class="d-flex justify-content-end"><button class="btn btn-light border close-details" type="button">إغلاق</button></div></div></div>
</div>

<div class="ry-modal" id="approveModal">
    <div class="ry-modal-panel small">
        <div class="ry-modal-header"><div class="d-flex justify-content-between align-items-center"><div><h5 class="mb-1">اعتماد السلفة</h5><small class="text-muted" id="approveNumber"></small></div><button class="ry-modal-close close-approve" type="button">×</button></div></div>
        <form id="approveForm"><div class="ry-modal-body"><div id="approveAlert" class="alert alert-danger d-none"></div><input type="hidden" id="approveUuid"><div class="row g-3">
            <div class="col-12"><div class="alert alert-light border mb-0">المبلغ المطلوب: <strong id="approveRequested"></strong> ر.س</div></div>
            <div class="col-md-6"><label class="form-label">المبلغ المعتمد <span class="text-danger">*</span></label><input type="number" class="form-control" id="approvedAmount" min="1" step="0.01" required><div class="invalid-feedback" data-error-for="approved_amount"></div></div>
            <div class="col-md-6"><label class="form-label">عدد الأقساط <span class="text-danger">*</span></label><input type="number" class="form-control" id="approvedInstallments" min="1" max="60" required><div class="invalid-feedback" data-error-for="installments_count"></div></div>
            <div class="col-12"><label class="form-label">تاريخ أول قسط <span class="text-danger">*</span></label><input type="date" class="form-control" id="approvedFirstDate" required><div class="invalid-feedback" data-error-for="first_installment_date"></div></div>
            <div class="col-12"><div class="alert alert-primary mb-0" id="approvedPreview">قيمة القسط التقديرية: 0.00 ر.س</div></div>
            <div class="col-12"><label class="form-label">ملاحظات الاعتماد</label><textarea class="form-control" id="approvalNotes" rows="3" maxlength="3000"></textarea><div class="invalid-feedback" data-error-for="approval_notes"></div></div>
        </div></div><div class="ry-modal-footer"><div class="d-flex justify-content-end gap-2"><button class="btn btn-light border close-approve" type="button">إلغاء</button><button class="btn btn-success px-4" id="btnApprove" type="submit">اعتماد وإنشاء الأقساط</button></div></div></form>
    </div>
</div>

<div class="ry-modal" id="rejectModal">
    <div class="ry-modal-panel small"><div class="ry-modal-header"><div class="d-flex justify-content-between align-items-center"><div><h5 class="mb-1">رفض طلب السلفة</h5><small class="text-muted" id="rejectNumber"></small></div><button class="ry-modal-close close-reject" type="button">×</button></div></div><form id="rejectForm"><div class="ry-modal-body"><div id="rejectAlert" class="alert alert-danger d-none"></div><input type="hidden" id="rejectUuid"><label class="form-label">سبب الرفض <span class="text-danger">*</span></label><textarea class="form-control" id="rejectionReason" rows="4" minlength="3" maxlength="2000" required></textarea><div class="invalid-feedback" data-error-for="rejection_reason"></div></div><div class="ry-modal-footer"><div class="d-flex justify-content-end gap-2"><button class="btn btn-light border close-reject" type="button">إلغاء</button><button class="btn btn-danger px-4" id="btnReject" type="submit">رفض الطلب</button></div></div></form></div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';
    const urls = {
        data: @json(route('app.payroll.employee-loan-approvals.data')),
        show: @json(route('app.payroll.employee-loan-approvals.show', '__UUID__')),
        approve: @json(route('app.payroll.employee-loan-approvals.approve', '__UUID__')),
        reject: @json(route('app.payroll.employee-loan-approvals.reject', '__UUID__'))
    };
    const state = { page: 1 };
    const csrf = () => $('meta[name="csrf-token"]').attr('content');
    const routeUrl = (url, uuid) => url.replace('__UUID__', encodeURIComponent(uuid));
    const escapeHtml = value => $('<div>').text(value ?? '').html();
    const money = value => Number(value ?? 0).toLocaleString('ar-SA', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    function openModal(id) { $(id).css('display','flex'); $('body').addClass('ry-modal-open'); }
    function closeModal(id) { $(id).hide(); if (!$('.ry-modal:visible').length) $('body').removeClass('ry-modal-open'); }
    function alertPage(message, type='success') { $('#pageAlert').removeClass('alert-success alert-danger').addClass('alert-'+type).text(message).stop(true,true).fadeIn(); window.scrollTo({top:0,behavior:'smooth'}); }
    function statusBadge(item) { const colors={submitted:'warning',approved:'primary',active:'info',completed:'success',rejected:'danger',cancelled:'dark'}; const color=colors[item.status]??'secondary'; return '<span class="badge bg-'+color+'-subtle text-'+color+'">'+escapeHtml(item.status_label)+'</span>'; }
    function requestError(xhr, alertId) {
        const data=xhr.responseJSON??{}, message=data.message??'تعذر تنفيذ العملية.';
        if (alertId) {
            $(alertId).removeClass('d-none').text(message);
            Object.entries(data.errors??{}).forEach(([field,messages])=>{ $('[data-error-for="'+field+'"]').text(Array.isArray(messages)?messages[0]:messages).prev('input,textarea').addClass('is-invalid'); });
        } else alertPage(message,'danger');
    }
    function clearFormErrors(form, alertId) { $(form+' .is-invalid').removeClass('is-invalid'); $(form+' [data-error-for]').empty(); $(alertId).addClass('d-none').empty(); }

    function loadData(page=1) {
        state.page=page; $('#tableBody').html('<tr><td colspan="9" class="approval-loading">جاري تحميل البيانات...</td></tr>');
        $.getJSON(urls.data,{page,search:$('#filterSearch').val(),status:$('#filterStatus').val(),per_page:$('#filterPerPage').val()})
            .done(response=>{ renderRows(response.data??[]); renderPagination(response.meta??{}); })
            .fail(xhr=>{ $('#tableBody').html('<tr><td colspan="9" class="approval-empty text-danger">تعذر تحميل البيانات.</td></tr>'); requestError(xhr); });
    }
    function renderRows(items) {
        if (!items.length) { $('#tableBody').html('<tr><td colspan="9" class="approval-empty">لا توجد طلبات مطابقة.</td></tr>'); return; }
        const start=(state.page-1)*Number($('#filterPerPage').val());
        $('#tableBody').html(items.map((item,index)=>{
            let actions='<button class="btn btn-sm btn-outline-primary btn-view" data-uuid="'+item.uuid+'">عرض</button>';
            if(item.can_approve) actions+='<button class="btn btn-sm btn-success btn-open-approve" data-item="'+encodeURIComponent(JSON.stringify(item))+'">اعتماد</button><button class="btn btn-sm btn-outline-danger btn-open-reject" data-uuid="'+item.uuid+'" data-number="'+escapeHtml(item.request_number)+'">رفض</button>';
            return '<tr><td>'+(start+index+1)+'</td><td dir="ltr">'+escapeHtml(item.request_number)+'</td><td><strong>'+escapeHtml(item.employee?.name)+'</strong><small class="d-block text-muted">'+escapeHtml(item.employee?.employee_number)+'</small></td><td>'+escapeHtml(item.loan_type_label)+'</td><td><span class="money">'+money(item.requested_amount)+'</span></td><td>'+item.installments_count+'</td><td>'+escapeHtml(item.submitted_at?.substring(0,10)??'—')+'</td><td>'+statusBadge(item)+'</td><td><div class="approval-actions">'+actions+'</div></td></tr>';
        }).join(''));
    }
    function renderPagination(meta) {
        const total=Number(meta.total??0),current=Number(meta.current_page??1),last=Number(meta.last_page??1),per=Number(meta.per_page??20);
        $('#recordsCount').text(total+' طلب'); if($('#filterStatus').val()==='submitted') $('#pendingCount').text(total+' بانتظار الاعتماد');
        $('#recordsSummary').text(total?'عرض '+(((current-1)*per)+1)+' إلى '+Math.min(current*per,total)+' من '+total:'لا توجد سجلات'); $('#paginationSummary').text('الصفحة '+current+' من '+last);
        let html='<button class="btn btn-sm btn-light border page-btn" data-page="'+(current-1)+'" '+(current<=1?'disabled':'')+'>السابق</button>';
        for(let p=Math.max(1,current-2);p<=Math.min(last,current+2);p++) html+='<button class="btn btn-sm '+(p===current?'btn-primary':'btn-light border')+' page-btn" data-page="'+p+'">'+p+'</button>';
        html+='<button class="btn btn-sm btn-light border page-btn" data-page="'+(current+1)+'" '+(current>=last?'disabled':'')+'>التالي</button>'; $('#paginationButtons').html(html);
    }
    function detailsHtml(item) {
        let installments='<p class="text-muted">لم يتم إنشاء الأقساط بعد.</p>';
        if(item.installments?.length) installments='<div class="table-responsive"><table class="table"><thead class="table-light"><tr><th>#</th><th>الاستحقاق</th><th>المبلغ</th><th>المتبقي</th><th>الحالة</th></tr></thead><tbody>'+item.installments.map(x=>'<tr><td>'+x.installment_number+'</td><td>'+escapeHtml(x.due_date)+'</td><td>'+money(x.amount)+'</td><td>'+money(x.remaining_amount)+'</td><td>'+escapeHtml(x.status_label)+'</td></tr>').join('')+'</tbody></table></div>';
        return '<div class="row g-3 mb-4"><div class="col-md-4"><div class="summary-box"><small>الموظف</small><strong>'+escapeHtml(item.employee?.name)+'</strong></div></div><div class="col-md-4"><div class="summary-box"><small>المبلغ المطلوب</small><strong>'+money(item.requested_amount)+' ر.س</strong></div></div><div class="col-md-4"><div class="summary-box"><small>الحالة</small><strong>'+escapeHtml(item.status_label)+'</strong></div></div></div><h6>سبب الطلب</h6><div class="p-3 bg-light rounded-3 mb-4">'+escapeHtml(item.reason)+'</div>'+(item.employee_notes?'<h6>ملاحظات الموظف</h6><div class="p-3 bg-light rounded-3 mb-4">'+escapeHtml(item.employee_notes)+'</div>':'')+'<h6>الأقساط</h6>'+installments;
    }
    function preview() { const amount=Number($('#approvedAmount').val()||0),count=Math.max(1,Number($('#approvedInstallments').val()||1)); $('#approvedPreview').text('قيمة القسط التقديرية: '+money(amount/count)+' ر.س'); }

    $(function(){
        loadData();
        $('#filterForm').on('submit',e=>{e.preventDefault();loadData(1);});
        $('#btnReset').on('click',()=>{$('#filterForm')[0].reset();$('#filterStatus').val('submitted');$('#filterPerPage').val('20');loadData(1);});
        $(document).on('click','.page-btn:not(:disabled)',function(){loadData(Number($(this).data('page')));});
        $('.close-details').on('click',()=>closeModal('#detailsModal')); $('.close-approve').on('click',()=>closeModal('#approveModal')); $('.close-reject').on('click',()=>closeModal('#rejectModal'));
        $('.ry-modal').on('click',function(e){if(e.target===this)closeModal('#'+this.id);});
        $(document).on('click','.btn-view',function(){ $.getJSON(routeUrl(urls.show,$(this).data('uuid'))).done(response=>{const item=response.data??response;$('#detailsNumber').text(item.request_number);$('#detailsBody').html(detailsHtml(item));openModal('#detailsModal');}).fail(xhr=>requestError(xhr)); });
        $(document).on('click','.btn-open-approve',function(){ const item=JSON.parse(decodeURIComponent($(this).attr('data-item'))); clearFormErrors('#approveForm','#approveAlert'); $('#approveUuid').val(item.uuid);$('#approveNumber').text(item.request_number);$('#approveRequested').text(money(item.requested_amount));$('#approvedAmount').val(item.requested_amount);$('#approvedInstallments').val(item.installments_count);$('#approvedFirstDate').val(item.first_installment_date??'');$('#approvalNotes').val('');preview();openModal('#approveModal'); });
        $(document).on('click','.btn-open-reject',function(){ clearFormErrors('#rejectForm','#rejectAlert');$('#rejectUuid').val($(this).data('uuid'));$('#rejectNumber').text($(this).data('number'));$('#rejectionReason').val('');openModal('#rejectModal'); });
        $('#approvedAmount,#approvedInstallments').on('input',preview);
        $('#approveForm').on('submit',function(e){e.preventDefault();clearFormErrors('#approveForm','#approveAlert');const $btn=$('#btnApprove').prop('disabled',true).text('جاري الاعتماد...');$.post(routeUrl(urls.approve,$('#approveUuid').val()),{_token:csrf(),approved_amount:$('#approvedAmount').val(),installments_count:$('#approvedInstallments').val(),first_installment_date:$('#approvedFirstDate').val(),approval_notes:$('#approvalNotes').val()||null}).done(response=>{closeModal('#approveModal');alertPage(response.message);loadData(state.page);}).fail(xhr=>requestError(xhr,'#approveAlert')).always(()=>$btn.prop('disabled',false).text('اعتماد وإنشاء الأقساط'));});
        $('#rejectForm').on('submit',function(e){e.preventDefault();clearFormErrors('#rejectForm','#rejectAlert');const $btn=$('#btnReject').prop('disabled',true).text('جاري الرفض...');$.post(routeUrl(urls.reject,$('#rejectUuid').val()),{_token:csrf(),rejection_reason:$('#rejectionReason').val()}).done(response=>{closeModal('#rejectModal');alertPage(response.message);loadData(state.page);}).fail(xhr=>requestError(xhr,'#rejectAlert')).always(()=>$btn.prop('disabled',false).text('رفض الطلب'));});
    });
})();
</script>
@endpush
