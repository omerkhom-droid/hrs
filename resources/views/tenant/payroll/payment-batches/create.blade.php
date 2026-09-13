@extends('layouts.tenant')

@section('title', 'إنشاء دفعة تحويل رواتب')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="mb-1">
                إنشاء دفعة تحويل رواتب
            </h4>

            <p class="text-muted mb-0">
                اختر مسير رواتب معتمد لإنشاء دفعة التحويل وفحص حسابات الموظفين.
            </p>
        </div>

        <a
            href="{{ route('app.payroll-payment-batches.index') }}"
            class="btn btn-outline-secondary"
        >
            <i class="fas fa-arrow-right me-1"></i>
            العودة إلى الدفعات
        </a>
    </div>


    @if ($errors->any())
        <div class="alert alert-danger">
            <h6 class="alert-heading">
                تعذر إنشاء الدفعة
            </h6>

            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>
                        {{ $error }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif


    <form
        method="POST"
        action="{{ route('app.payroll-payment-batches.store') }}"
        id="paymentBatchForm"
    >
        @csrf

        <div class="row g-4">

            <div class="col-xl-8">

                {{-- بيانات المسير --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">
                            بيانات مسير الرواتب
                        </h5>
                    </div>

                    <div class="card-body">
                        <div class="mb-4">
                            <label
                                for="payroll_run_id"
                                class="form-label"
                            >
                                مسير الرواتب
                                <span class="text-danger">*</span>
                            </label>

                            <select
                                name="payroll_run_id"
                                id="payroll_run_id"
                                class="form-select @error('payroll_run_id') is-invalid @enderror"
                                required
                            >
                                <option value="">
                                    اختر مسير الرواتب المعتمد
                                </option>

                                @foreach ($payrollRuns as $run)
                                    @php
                                        $periodName =
                                            $run->period?->name
                                            ?? 'فترة غير محددة';

                                        $runNumber =
                                            $run->run_number
                                            ?? '#' . $run->id;

                                        $netAmount =
                                            $run->total_net_salary;
                                    @endphp

                                    <option
                                        value="{{ $run->id }}"
                                        data-number="{{ $runNumber }}"
                                        data-period="{{ $periodName }}"
                                        data-amount="{{ $netAmount }}"
                                        data-currency="{{ $run->currency_code ?? 'SAR' }}"
                                        @selected(
                                            (string) old('payroll_run_id')
                                            === (string) $run->id
                                        )
                                    >
                                        {{ $runNumber }}
                                        — {{ $periodName }}

                                        @if ($netAmount !== null)
                                            — {{ number_format(
                                                (float) $netAmount,
                                                2
                                            ) }}
                                            {{ $run->currency_code ?? 'SAR' }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>

                            @error('payroll_run_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            @if ($payrollRuns->isEmpty())
                                <div class="alert alert-warning mt-3 mb-0">
                                    لا توجد مسيرات رواتب معتمدة ومتاحة لإنشاء دفعة تحويل.
                                </div>
                            @endif
                        </div>


                        {{-- معلومات المسير المختار --}}
                        <div
                            class="border rounded bg-light p-3 mb-4 d-none"
                            id="selectedRunDetails"
                        >
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="small text-muted">
                                        رقم المسير
                                    </div>

                                    <div
                                        class="fw-semibold mt-1"
                                        id="selectedRunNumber"
                                    >
                                        —
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="small text-muted">
                                        فترة الرواتب
                                    </div>

                                    <div
                                        class="fw-semibold mt-1"
                                        id="selectedRunPeriod"
                                    >
                                        —
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="small text-muted">
                                        صافي الرواتب
                                    </div>

                                    <div
                                        class="fw-semibold mt-1"
                                        id="selectedRunAmount"
                                    >
                                        —
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="mb-4">
                            <label
                                for="name"
                                class="form-label"
                            >
                                اسم دفعة التحويل
                            </label>

                            <input
                                type="text"
                                name="name"
                                id="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') }}"
                                maxlength="255"
                                placeholder="يُنشأ الاسم تلقائيًا عند تركه فارغًا"
                            >

                            @error('name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>


                        <div class="row g-3">
                            <div class="col-md-6">
                                <label
                                    for="payment_date"
                                    class="form-label"
                                >
                                    تاريخ التحويل
                                    <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="date"
                                    name="payment_date"
                                    id="payment_date"
                                    class="form-control @error('payment_date') is-invalid @enderror"
                                    value="{{ old('payment_date', now()->format('Y-m-d')) }}"
                                    required
                                >

                                @error('payment_date')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label
                                    for="currency_code"
                                    class="form-label"
                                >
                                    العملة
                                    <span class="text-danger">*</span>
                                </label>

                                <select
                                    name="currency_code"
                                    id="currency_code"
                                    class="form-select @error('currency_code') is-invalid @enderror"
                                    required
                                >
                                    @foreach ([
                                        'SAR' => 'ريال سعودي',
                                        'AED' => 'درهم إماراتي',
                                        'BHD' => 'دينار بحريني',
                                        'EGP' => 'جنيه مصري',
                                        'KWD' => 'دينار كويتي',
                                        'OMR' => 'ريال عماني',
                                        'QAR' => 'ريال قطري',
                                        'USD' => 'دولار أمريكي',
                                        'EUR' => 'يورو',
                                    ] as $code => $label)
                                        <option
                                            value="{{ $code }}"
                                            @selected(
                                                old('currency_code', 'SAR')
                                                === $code
                                            )
                                        >
                                            {{ $code }} — {{ $label }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('currency_code')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>


                {{-- إعدادات الملف --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">
                            إعدادات ملف التحويل
                        </h5>
                    </div>

                    <div class="card-body">
                        <div class="mb-4">
                            <label
                                for="file_format"
                                class="form-label"
                            >
                                صيغة ملف التحويل
                                <span class="text-danger">*</span>
                            </label>

                            <select
                                name="file_format"
                                id="file_format"
                                class="form-select @error('file_format') is-invalid @enderror"
                                required
                            >
                                <option
                                    value="bank_csv"
                                    @selected(
                                        old('file_format', 'bank_csv')
                                        === 'bank_csv'
                                    )
                                >
                                    ملف بنكي CSV
                                </option>

                                <option
                                    value="csv"
                                    @selected(old('file_format') === 'csv')
                                >
                                    CSV عام
                                </option>

                                <option
                                    value="txt"
                                    @selected(old('file_format') === 'txt')
                                >
                                    ملف نصي TXT
                                </option>

                                <option
                                    value="sif"
                                    @selected(old('file_format') === 'sif')
                                >
                                    ملف حماية الأجور SIF
                                </option>
                            </select>

                            @error('file_format')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div>
                            <label
                                for="notes"
                                class="form-label"
                            >
                                الملاحظات
                            </label>

                            <textarea
                                name="notes"
                                id="notes"
                                class="form-control @error('notes') is-invalid @enderror"
                                rows="4"
                                maxlength="5000"
                                placeholder="أي ملاحظات داخلية على دفعة التحويل"
                            >{{ old('notes') }}</textarea>

                            @error('notes')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                </div>

            </div>


            {{-- ملخص العملية --}}
            <div class="col-xl-4">
                <div
                    class="card border-0 shadow-sm position-sticky"
                    style="top: 1rem;"
                >
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">
                            ماذا سيحدث؟
                        </h5>
                    </div>

                    <div class="card-body">
                        <div class="d-flex gap-3 mb-3">
                            <div class="text-primary">
                                <i class="fas fa-file-invoice-dollar fa-lg"></i>
                            </div>

                            <div>
                                <div class="fw-semibold">
                                    إنشاء دفعة مسودة
                                </div>

                                <div class="small text-muted">
                                    ستُنشأ الدفعة من عناصر مسير الرواتب المحدد.
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-3 mb-3">
                            <div class="text-primary">
                                <i class="fas fa-university fa-lg"></i>
                            </div>

                            <div>
                                <div class="fw-semibold">
                                    فحص الحسابات البنكية
                                </div>

                                <div class="small text-muted">
                                    سيتم التحقق من حساب وآيبان وطريقة دفع كل موظف.
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-3 mb-4">
                            <div class="text-warning">
                                <i class="fas fa-shield-alt fa-lg"></i>
                            </div>

                            <div>
                                <div class="fw-semibold">
                                    دون إرسال إلى البنك
                                </div>

                                <div class="small text-muted">
                                    إنشاء الدفعة لا ينفذ أي تحويل أو إرسال تلقائي.
                                </div>
                            </div>
                        </div>

                        <hr>

                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                            id="submitButton"
                            @disabled($payrollRuns->isEmpty())
                        >
                            <span class="normal-content">
                                <i class="fas fa-check me-1"></i>
                                إنشاء وفحص الدفعة
                            </span>

                            <span class="loading-content d-none">
                                <span
                                    class="spinner-border spinner-border-sm me-1"
                                ></span>
                                جارٍ إنشاء الدفعة...
                            </span>
                        </button>

                        <a
                            href="{{ route(
                                'app.payroll-payment-batches.index'
                            ) }}"
                            class="btn btn-light border w-100 mt-2"
                        >
                            إلغاء
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>
@endsection


@push('scripts')
<script>
$(function () {
    const $runSelect = $('#payroll_run_id');
    const $details = $('#selectedRunDetails');
    const $form = $('#paymentBatchForm');
    const $submitButton = $('#submitButton');

    function updateRunDetails() {
        const $option = $runSelect.find('option:selected');
        const runId = $option.val();

        if (!runId) {
            $details.addClass('d-none');
            return;
        }

        const number = $option.data('number') || '—';
        const period = $option.data('period') || '—';
        const amount = $option.data('amount');
        const currency = $option.data('currency') || 'SAR';

        $('#selectedRunNumber').text(number);
        $('#selectedRunPeriod').text(period);

        if (
            amount !== undefined
            && amount !== null
            && amount !== ''
        ) {
            const formattedAmount = Number(amount).toLocaleString(
                'en-US',
                {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }
            );

            $('#selectedRunAmount').text(
                formattedAmount + ' ' + currency
            );
        } else {
            $('#selectedRunAmount').text('—');
        }

        /*
         * تغيير العملة تلقائيًا حسب عملة المسير،
         * مع إمكانية تعديلها قبل الحفظ.
         */
        if (currency) {
            $('#currency_code').val(currency);
        }

        $details.removeClass('d-none');
    }

    $runSelect.on('change', updateRunDetails);

    updateRunDetails();

    $form.on('submit', function () {
        if (!this.checkValidity()) {
            return;
        }

        $submitButton.prop('disabled', true);
        $submitButton
            .find('.normal-content')
            .addClass('d-none');

        $submitButton
            .find('.loading-content')
            .removeClass('d-none');
    });
});
</script>
@endpush