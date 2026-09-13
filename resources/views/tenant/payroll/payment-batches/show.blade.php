@extends('layouts.tenant')

@section('title', 'تفاصيل دفعة التحويل')

@section('content')
<div class="container-fluid py-4">

    {{-- رأس الصفحة --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <h4 class="mb-0">
                    {{ $batch->name }}
                </h4>

                @php
                    $batchStatusClasses = [
                        'draft' => 'bg-secondary',
                        'validated' => 'bg-info text-dark',
                        'submitted' => 'bg-primary',
                        'processing' => 'bg-warning text-dark',
                        'partially_paid' => 'bg-warning text-dark',
                        'paid' => 'bg-success',
                        'failed' => 'bg-danger',
                        'cancelled' => 'bg-dark',
                    ];
                @endphp

                <span class="badge {{
                    $batchStatusClasses[$batch->status]
                        ?? 'bg-secondary'
                }}">
                    {{ $batch->status_label }}
                </span>
            </div>

            <div class="text-muted">
                {{ $batch->batch_number }}

                @if ($batch->payment_reference)
                    <span class="mx-1">•</span>
                    {{ $batch->payment_reference }}
                @endif
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a
                href="{{ route('app.payroll-payment-batches.index') }}"
                class="btn btn-outline-secondary"
            >
                <i class="fas fa-arrow-right me-1"></i>
                العودة
            </a>

            @can('payroll.manage')
                @if (in_array($batch->status, ['draft', 'validated'], true))
                    <form
                        method="POST"
                        action="{{ route(
                            'app.payroll-payment-batches.validate',
                            $batch
                        ) }}"
                        class="d-inline validate-batch-form"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            <i class="fas fa-check-circle me-1"></i>
                            إعادة فحص الدفعة
                        </button>
                    </form>
                @endif

                @if ($batch->can_cancel)
                    <button
                        type="button"
                        class="btn btn-outline-danger"
                        data-bs-toggle="modal"
                        data-bs-target="#cancelBatchModal"
                    >
                        <i class="fas fa-times me-1"></i>
                        إلغاء الدفعة
                    </button>
                @endif
            @endcan

            @can('payroll.process')
                @if ($batch->can_generate)
                    <form
                        method="POST"
                        action="{{ route(
                            'app.payroll-payment-batches.generate-file',
                            $batch
                        ) }}"
                        class="d-inline"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="btn btn-success"
                        >
                            <i class="fas fa-file-csv me-1"></i>
                            إنشاء ملف البنك
                        </button>
                    </form>
                @endif

                @if ($batch->hasGeneratedFile())
                    <a
                        href="{{ route(
                            'app.payroll-payment-batches.download-file',
                            $batch
                        ) }}"
                        class="btn btn-outline-success"
                    >
                        <i class="fas fa-download me-1"></i>
                        تحميل ملف البنك
                    </a>
                @endif
            @endcan
        </div>
    </div>


    {{-- الرسائل --}}
    @foreach (['success', 'warning', 'error'] as $messageType)
        @if (session($messageType))
            <div class="alert alert-{{
                $messageType === 'error'
                    ? 'danger'
                    : $messageType
            }} alert-dismissible fade show">
                {{ session($messageType) }}

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                ></button>
            </div>
        @endif
    @endforeach

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    {{-- بطاقات الملخص --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-2">
                        إجمالي مبلغ الدفعة
                    </div>

                    <div class="fs-4 fw-bold">
                        {{ number_format(
                            (float) $batch->total_amount,
                            2
                        ) }}

                        <span class="fs-6 text-muted">
                            {{ $batch->currency_code }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-2">
                        عدد الموظفين
                    </div>

                    <div class="fs-4 fw-bold">
                        {{ number_format(
                            $batch->employees_count ?? 0
                        ) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-2">
                        جاهزون للتحويل
                    </div>

                    <div class="fs-4 fw-bold text-success">
                        {{ number_format(
                            $batch->ready_employees_count ?? 0
                        ) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-2">
                        الاستثناءات
                    </div>

                    <div class="fs-4 fw-bold text-danger">
                        {{ number_format(
                            $batch->exception_employees_count ?? 0
                        ) }}
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- تنبيه الاستثناءات --}}
    @if (($batch->exception_employees_count ?? 0) > 0)
        <div class="alert alert-warning d-flex gap-3 align-items-start">
            <i class="fas fa-exclamation-triangle mt-1"></i>

            <div>
                <div class="fw-semibold">
                    الدفعة تحتوي على حسابات تحتاج إلى معالجة
                </div>

                <div class="small mt-1">
                    صحّح بيانات الحسابات البنكية للموظفين، ثم أعد فحص الدفعة.
                </div>
            </div>
        </div>
    @elseif ($batch->status === 'validated')
        <div class="alert alert-success d-flex gap-3 align-items-start">
            <i class="fas fa-check-circle mt-1"></i>

            <div>
                <div class="fw-semibold">
                    جميع الموظفين جاهزون للتحويل
                </div>

                <div class="small mt-1">
                    تم فحص الحسابات البنكية ولم تظهر استثناءات.
                </div>
            </div>
        </div>
    @endif


    <div class="row g-4 mb-4">

        {{-- بيانات الدفعة --}}
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">
                        بيانات الدفعة
                    </h5>
                </div>

                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="small text-muted mb-1">
                                رقم الدفعة
                            </div>

                            <div class="fw-semibold">
                                {{ $batch->batch_number }}
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="small text-muted mb-1">
                                مرجع الدفع
                            </div>

                            <div class="fw-semibold">
                                {{ $batch->payment_reference ?: '—' }}
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="small text-muted mb-1">
                                تاريخ التحويل
                            </div>

                            <div class="fw-semibold">
                                {{ $batch->payment_date
                                    ? $batch->payment_date->format('Y-m-d')
                                    : '—' }}
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="small text-muted mb-1">
                                مسير الرواتب
                            </div>

                            <div class="fw-semibold">
                                {{ $batch->payrollRun?->run_number
                                    ?? $batch->payrollRun?->name
                                    ?? '#' . $batch->payroll_run_id }}
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="small text-muted mb-1">
                                فترة الرواتب
                            </div>

                            <div class="fw-semibold">
                                {{ $batch->payrollRun?->payrollPeriod?->name
                                    ?? '—' }}
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="small text-muted mb-1">
                                صيغة الملف
                            </div>

                            <div class="fw-semibold">
                                {{ strtoupper($batch->file_format) }}
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="small text-muted mb-1">
                                أنشئت بواسطة
                            </div>

                            <div class="fw-semibold">
                                {{ $batch->createdBy?->name ?? '—' }}
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="small text-muted mb-1">
                                تاريخ الإنشاء
                            </div>

                            <div class="fw-semibold">
                                {{ $batch->created_at?->format('Y-m-d H:i') }}
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="small text-muted mb-1">
                                آخر فحص
                            </div>

                            <div class="fw-semibold">
                                {{ $batch->validated_at
                                    ? $batch->validated_at->format('Y-m-d H:i')
                                    : 'لم يتم الفحص' }}
                            </div>
                        </div>

                        @if ($batch->notes)
                            <div class="col-12">
                                <div class="small text-muted mb-1">
                                    الملاحظات
                                </div>

                                <div class="border rounded bg-light p-3">
                                    {{ $batch->notes }}
                                </div>
                            </div>
                        @endif

                        @if ($batch->status === 'cancelled')
                            <div class="col-12">
                                <div class="alert alert-danger mb-0">
                                    <div class="fw-semibold mb-1">
                                        سبب الإلغاء
                                    </div>

                                    {{ $batch->cancellation_reason ?: 'غير مسجل' }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>


        {{-- حساب الشركة --}}
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">
                        حساب الشركة
                    </h5>
                </div>

                <div class="card-body">
                    <div class="mb-3">
                        <div class="small text-muted mb-1">
                            البنك
                        </div>

                        <div class="fw-semibold">
                            {{ $batch->source_bank_name ?: '—' }}
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="small text-muted mb-1">
                            رقم الحساب
                        </div>

                        <div class="fw-semibold">
                            @if ($batch->source_account_last_four)
                                •••• ••••
                                {{ $batch->source_account_last_four }}
                            @else
                                —
                            @endif
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="small text-muted mb-1">
                            الآيبان
                        </div>

                        <div class="fw-semibold">
                            @if ($batch->source_iban_last_four)
                                SA•• •••• •••• ••••
                                {{ $batch->source_iban_last_four }}
                            @else
                                —
                            @endif
                        </div>
                    </div>

                    <div>
                        <div class="small text-muted mb-1">
                            SWIFT
                        </div>

                        <div class="fw-semibold">
                            {{ $batch->source_swift_code ?: '—' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>


    {{-- عناصر الدفعة --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <h5 class="mb-0">
                    موظفو دفعة التحويل
                </h5>

                <div class="d-flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-secondary item-filter active"
                        data-filter="all"
                    >
                        الكل
                    </button>

                    <button
                        type="button"
                        class="btn btn-sm btn-outline-success item-filter"
                        data-filter="ready"
                    >
                        الجاهزون
                    </button>

                    <button
                        type="button"
                        class="btn btn-sm btn-outline-danger item-filter"
                        data-filter="exception"
                    >
                        الاستثناءات
                    </button>

                    <button
                        type="button"
                        class="btn btn-sm btn-outline-dark item-filter"
                        data-filter="excluded"
                    >
                        المستبعدون
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>الموظف</th>
                            <th>طريقة الدفع</th>
                            <th>البنك</th>
                            <th>الآيبان</th>
                            <th>المبلغ</th>
                            <th>الحالة</th>
                            <th>الملاحظة</th>
                            <th class="text-center">الإجراءات</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($batch->items as $item)
                            @php
                                $itemStatusClasses = [
                                    'pending' => 'bg-secondary',
                                    'ready' => 'bg-success',
                                    'exception' => 'bg-danger',
                                    'submitted' => 'bg-primary',
                                    'paid' => 'bg-success',
                                    'failed' => 'bg-danger',
                                    'excluded' => 'bg-dark',
                                    'cancelled' => 'bg-secondary',
                                ];
                            @endphp

                            <tr
                                class="batch-item-row"
                                data-status="{{ $item->status }}"
                            >
                                <td>
                                    <div class="fw-semibold">
                                        {{ $item->employee_name }}
                                    </div>

                                    <div class="small text-muted">
                                        {{ $item->employee_number }}
                                    </div>
                                </td>

                                <td>
                                    {{ $item->payment_method_label }}
                                </td>

                                <td>
                                    <div>
                                        {{ $item->bank_name ?: '—' }}
                                    </div>

                                    @if ($item->account_holder_name)
                                        <div class="small text-muted">
                                            {{ $item->account_holder_name }}
                                        </div>
                                    @endif
                                </td>

                                <td dir="ltr">
                                    {{ $item->masked_iban ?: '—' }}
                                </td>

                                <td>
                                    <span class="fw-semibold">
                                        {{ number_format(
                                            (float) $item->amount,
                                            2
                                        ) }}
                                    </span>

                                    <span class="small text-muted">
                                        {{ $item->currency_code }}
                                    </span>
                                </td>

                                <td>
                                    <span class="badge {{
                                        $itemStatusClasses[$item->status]
                                            ?? 'bg-secondary'
                                    }}">
                                        {{ $item->status_label }}
                                    </span>
                                </td>

                                <td style="min-width: 220px;">
                                    @if ($item->exception_message)
                                        <span class="{{
                                            $item->status === 'exception'
                                                ? 'text-danger'
                                                : 'text-muted'
                                        }}">
                                            {{ $item->exception_message }}
                                        </span>
                                    @else
                                        <span class="text-muted">
                                            —
                                        </span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    @can('payroll.manage')
                                        @if (
                                            $batch->status === 'draft'
                                            && $item->status !== 'excluded'
                                        )
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger btn-exclude-item"
                                                data-action="{{ route(
                                                    'app.payroll-payment-batches.items.exclude',
                                                    [
                                                        $batch,
                                                        $item,
                                                    ]
                                                ) }}"
                                                data-employee="{{ $item->employee_name }}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#excludeItemModal"
                                            >
                                                استبعاد
                                            </button>
                                        @elseif (
                                            $batch->status === 'draft'
                                            && $item->status === 'excluded'
                                        )
                                            <form
                                                method="POST"
                                                action="{{ route(
                                                    'app.payroll-payment-batches.items.restore',
                                                    [
                                                        $batch,
                                                        $item,
                                                    ]
                                                ) }}"
                                                class="d-inline restore-item-form"
                                            >
                                                @csrf

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-outline-success"
                                                >
                                                    إعادة
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-muted">
                                                —
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-muted">
                                            —
                                        </span>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="8"
                                    class="text-center text-muted py-5"
                                >
                                    لا يوجد موظفون في هذه الدفعة.
                                </td>
                            </tr>
                        @endforelse

                        <tr
                            id="emptyFilterRow"
                            class="d-none"
                        >
                            <td
                                colspan="8"
                                class="text-center text-muted py-5"
                            >
                                لا توجد نتائج مطابقة للحالة المختارة.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


{{-- نافذة استبعاد موظف --}}
@can('payroll.manage')
    <div
        class="modal fade"
        id="excludeItemModal"
        tabindex="-1"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form
                    method="POST"
                    id="excludeItemForm"
                >
                    @csrf

                    <div class="modal-header">
                        <h5 class="modal-title">
                            استبعاد موظف
                        </h5>

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                        ></button>
                    </div>

                    <div class="modal-body">
                        <p>
                            سيتم استبعاد:
                            <strong id="excludeEmployeeName"></strong>
                        </p>

                        <label
                            for="excludeReason"
                            class="form-label"
                        >
                            سبب الاستبعاد
                            <span class="text-danger">*</span>
                        </label>

                        <textarea
                            name="reason"
                            id="excludeReason"
                            class="form-control"
                            rows="4"
                            minlength="5"
                            maxlength="2000"
                            required
                        ></textarea>
                    </div>

                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-light border"
                            data-bs-dismiss="modal"
                        >
                            تراجع
                        </button>

                        <button
                            type="submit"
                            class="btn btn-danger"
                        >
                            تأكيد الاستبعاد
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    {{-- نافذة إلغاء الدفعة --}}
    @if ($batch->can_cancel)
        <div
            class="modal fade"
            id="cancelBatchModal"
            tabindex="-1"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form
                        method="POST"
                        action="{{ route(
                            'app.payroll-payment-batches.cancel',
                            $batch
                        ) }}"
                    >
                        @csrf

                        <div class="modal-header">
                            <h5 class="modal-title">
                                إلغاء دفعة التحويل
                            </h5>

                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                            ></button>
                        </div>

                        <div class="modal-body">
                            <div class="alert alert-warning">
                                لن تتمكن من استخدام هذه الدفعة بعد إلغائها.
                            </div>

                            <label
                                for="cancellationReason"
                                class="form-label"
                            >
                                سبب الإلغاء
                                <span class="text-danger">*</span>
                            </label>

                            <textarea
                                name="reason"
                                id="cancellationReason"
                                class="form-control"
                                rows="4"
                                minlength="5"
                                maxlength="2000"
                                required
                            ></textarea>
                        </div>

                        <div class="modal-footer">
                            <button
                                type="button"
                                class="btn btn-light border"
                                data-bs-dismiss="modal"
                            >
                                تراجع
                            </button>

                            <button
                                type="submit"
                                class="btn btn-danger"
                            >
                                تأكيد الإلغاء
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endcan
@endsection


@push('scripts')
<script>
$(function () {
    /*
     * فلترة عناصر الدفعة.
     */
    $('.item-filter').on('click', function () {
        const filter = $(this).data('filter');
        let visibleRows = 0;

        $('.item-filter').removeClass('active');
        $(this).addClass('active');

        $('.batch-item-row').each(function () {
            const status = $(this).data('status');
            const visible =
                filter === 'all'
                || status === filter;

            $(this).toggle(visible);

            if (visible) {
                visibleRows++;
            }
        });

        $('#emptyFilterRow').toggleClass(
            'd-none',
            visibleRows > 0
        );
    });


    /*
     * تجهيز نموذج الاستبعاد.
     */
    $('.btn-exclude-item').on('click', function () {
        const action = $(this).data('action');
        const employee = $(this).data('employee');

        $('#excludeItemForm').attr('action', action);
        $('#excludeEmployeeName').text(employee);
        $('#excludeReason').val('');
    });


    /*
     * منع تكرار إرسال النماذج.
     */
    $(
        '.validate-batch-form, ' +
        '.restore-item-form, ' +
        '#excludeItemForm, ' +
        '#cancelBatchModal form'
    ).on('submit', function () {
        const $button = $(this).find(
            'button[type="submit"]'
        );

        $button
            .prop('disabled', true)
            .prepend(
                '<span class="spinner-border ' +
                'spinner-border-sm me-1"></span>'
            );
    });
});
</script>
@endpush