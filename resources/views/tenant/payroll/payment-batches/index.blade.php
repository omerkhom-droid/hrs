@extends('layouts.tenant')

@section('title', 'دفعات تحويل الرواتب')

@section('content')
<div class="container-fluid py-4">

    {{-- رأس الصفحة --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="mb-1">
                دفعات تحويل الرواتب
            </h4>

            <p class="text-muted mb-0">
                إدارة دفعات الرواتب وفحص جاهزية الحسابات البنكية.
            </p>
        </div>

        @can('payroll.manage')
            <a
                href="{{ route('app.payroll-payment-batches.create') }}"
                class="btn btn-primary"
            >
                <i class="fas fa-plus me-1"></i>
                إنشاء دفعة تحويل
            </a>
        @endcan
    </div>


    {{-- رسائل النظام --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>
        </div>
    @endif

    @if (session('warning'))
        <div class="alert alert-warning alert-dismissible fade show">
            {{ session('warning') }}

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>
        </div>
    @endif


    {{-- فلاتر البحث --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form
                method="GET"
                action="{{ route('app.payroll-payment-batches.index') }}"
            >
                <div class="row g-3 align-items-end">

                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <label class="form-label">
                            البحث
                        </label>

                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            value="{{ $filters['search'] ?? '' }}"
                            placeholder="رقم الدفعة أو المرجع أو الاسم"
                        >
                    </div>

                    <div class="col-xl-2 col-lg-4 col-md-6">
                        <label class="form-label">
                            الحالة
                        </label>

                        <select
                            name="status"
                            class="form-select"
                        >
                            <option value="">
                                جميع الحالات
                            </option>

                            @foreach ([
                                'draft' => 'مسودة',
                                'validated' => 'تم الفحص',
                                'submitted' => 'تم الإرسال',
                                'processing' => 'قيد المعالجة',
                                'partially_paid' => 'تحويل جزئي',
                                'paid' => 'تم التحويل',
                                'failed' => 'فشلت',
                                'cancelled' => 'ملغاة',
                            ] as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    @selected(
                                        ($filters['status'] ?? '') === $value
                                    )
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-lg-4 col-md-6">
                        <label class="form-label">
                            من تاريخ
                        </label>

                        <input
                            type="date"
                            name="date_from"
                            class="form-control"
                            value="{{ $filters['date_from'] ?? '' }}"
                        >
                    </div>

                    <div class="col-xl-2 col-lg-4 col-md-6">
                        <label class="form-label">
                            إلى تاريخ
                        </label>

                        <input
                            type="date"
                            name="date_to"
                            class="form-control"
                            value="{{ $filters['date_to'] ?? '' }}"
                        >
                    </div>

                    <div class="col-xl-3 col-lg-8 col-md-12">
                        <div class="d-flex gap-2">
                            <button
                                type="submit"
                                class="btn btn-primary flex-grow-1"
                            >
                                <i class="fas fa-search me-1"></i>
                                بحث
                            </button>

                            <a
                                href="{{ route('app.payroll-payment-batches.index') }}"
                                class="btn btn-outline-secondary"
                            >
                                إعادة ضبط
                            </a>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>


    {{-- جدول الدفعات --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    سجل دفعات التحويل
                </h5>

                <span class="text-muted small">
                    عدد النتائج:
                    {{ number_format($batches->total()) }}
                </span>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>رقم الدفعة</th>
                            <th>اسم الدفعة</th>
                            <th>تاريخ التحويل</th>
                            <th>الموظفون</th>
                            <th>الجاهزون</th>
                            <th>الاستثناءات</th>
                            <th>الإجمالي</th>
                            <th>الحالة</th>
                            <th class="text-center">الإجراءات</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($batches as $batch)
                            <tr>
                                <td>
                                    <a
                                        href="{{ route(
                                            'app.payroll-payment-batches.show',
                                            $batch
                                        ) }}"
                                        class="text-decoration-none fw-semibold"
                                    >
                                        {{ $batch->batch_number }}
                                    </a>

                                    @if ($batch->payment_reference)
                                        <div class="small text-muted mt-1">
                                            {{ $batch->payment_reference }}
                                        </div>
                                    @endif
                                </td>

                                <td>
                                    <div class="fw-semibold">
                                        {{ $batch->name }}
                                    </div>

                                    @if ($batch->payrollRun)
                                        <div class="small text-muted mt-1">
                                            المسير:
                                            {{ $batch->payrollRun->run_number
                                                ?? $batch->payrollRun->name
                                                ?? '#' . $batch->payroll_run_id }}
                                        </div>
                                    @endif
                                </td>

                                <td>
                                    {{ $batch->payment_date
                                        ? $batch->payment_date->format('Y-m-d')
                                        : '—' }}
                                </td>

                                <td>
                                    {{ number_format(
                                        $batch->employees_count ?? 0
                                    ) }}
                                </td>

                                <td>
                                    <span class="text-success fw-semibold">
                                        {{ number_format(
                                            $batch->ready_employees_count ?? 0
                                        ) }}
                                    </span>
                                </td>

                                <td>
                                    @if (
                                        ($batch->exception_employees_count ?? 0) > 0
                                    )
                                        <span class="badge bg-danger">
                                            {{ number_format(
                                                $batch->exception_employees_count
                                            ) }}
                                        </span>
                                    @else
                                        <span class="text-muted">
                                            0
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    <span class="fw-bold">
                                        {{ number_format(
                                            (float) $batch->total_amount,
                                            2
                                        ) }}
                                    </span>

                                    <span class="small text-muted">
                                        {{ $batch->currency_code }}
                                    </span>
                                </td>

                                <td>
                                    @php
                                        $statusClasses = [
                                            'draft' => 'bg-secondary',
                                            'validated' => 'bg-info text-dark',
                                            'submitted' => 'bg-primary',
                                            'processing' => 'bg-warning text-dark',
                                            'partially_paid' => 'bg-warning text-dark',
                                            'paid' => 'bg-success',
                                            'failed' => 'bg-danger',
                                            'cancelled' => 'bg-dark',
                                        ];

                                        $statusLabels = [
                                            'draft' => 'مسودة',
                                            'validated' => 'تم الفحص',
                                            'submitted' => 'تم الإرسال',
                                            'processing' => 'قيد المعالجة',
                                            'partially_paid' => 'تحويل جزئي',
                                            'paid' => 'تم التحويل',
                                            'failed' => 'فشلت',
                                            'cancelled' => 'ملغاة',
                                        ];
                                    @endphp

                                    <span class="badge {{
                                        $statusClasses[$batch->status]
                                            ?? 'bg-secondary'
                                    }}">
                                        {{ $batch->status_label
                                            ?? $statusLabels[$batch->status]
                                            ?? 'غير محدد' }}
                                    </span>
                                </td>

                                <td class="text-center">
                                    <a
                                        href="{{ route(
                                            'app.payroll-payment-batches.show',
                                            $batch
                                        ) }}"
                                        class="btn btn-sm btn-outline-primary"
                                        title="عرض التفاصيل"
                                    >
                                        <i class="fas fa-eye"></i>
                                        عرض
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="9"
                                    class="text-center py-5"
                                >
                                    <div class="text-muted">
                                        <i class="fas fa-money-check-alt fa-3x mb-3"></i>

                                        <p class="mb-2">
                                            لا توجد دفعات تحويل رواتب.
                                        </p>

                                        @can('payroll.manage')
                                            <a
                                                href="{{ route(
                                                    'app.payroll-payment-batches.create'
                                                ) }}"
                                                class="btn btn-primary btn-sm"
                                            >
                                                إنشاء أول دفعة
                                            </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($batches->hasPages())
            <div class="card-footer bg-white">
                {{ $batches->links() }}
            </div>
        @endif
    </div>
</div>
@endsection