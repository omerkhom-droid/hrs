@extends('layouts.system')

@section('title', 'لوحة تحكم المنصة')
@section('page-title', 'لوحة التحكم')

@section('content')

<div class="container-fluid">

    {{-- Welcome --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">

        <div class="card-body p-4">

            <div
                class="d-flex justify-content-between align-items-center flex-wrap gap-3"
            >

                <div>

                    <h4 class="mb-1">
                        مرحبًا، {{ auth()->user()->name }}
                    </h4>

                    <div class="text-muted">
                        إدارة منصة رؤية يوم
                    </div>

                </div>

                <span
                    class="badge bg-success-subtle text-success px-3 py-2"
                >
                    النظام متاح
                </span>

            </div>

        </div>

    </div>


    {{-- Statistics --}}
    <div class="row g-3 mb-4">

        <div class="col-xl-2 col-md-4 col-sm-6">

            <div class="card border-0 shadow-sm rounded-4 h-100">

                <div class="card-body p-4">

                    <div class="text-muted small mb-2">
                        إجمالي العملاء
                    </div>

                    <h3 class="mb-0">
                        {{ number_format($stats['tenants_total']) }}
                    </h3>

                </div>

            </div>

        </div>


        <div class="col-xl-2 col-md-4 col-sm-6">

            <div class="card border-0 shadow-sm rounded-4 h-100">

                <div class="card-body p-4">

                    <div class="text-muted small mb-2">
                        العملاء الفعالون
                    </div>

                    <h3 class="mb-0 text-success">
                        {{ number_format($stats['tenants_active']) }}
                    </h3>

                </div>

            </div>

        </div>


        <div class="col-xl-2 col-md-4 col-sm-6">

            <div class="card border-0 shadow-sm rounded-4 h-100">

                <div class="card-body p-4">

                    <div class="text-muted small mb-2">
                        الاشتراكات الفعالة
                    </div>

                    <h3 class="mb-0 text-primary">
                        {{ number_format($stats['subscriptions_active']) }}
                    </h3>

                </div>

            </div>

        </div>


        <div class="col-xl-2 col-md-4 col-sm-6">

            <div class="card border-0 shadow-sm rounded-4 h-100">

                <div class="card-body p-4">

                    <div class="text-muted small mb-2">
                        الفترة التجريبية
                    </div>

                    <h3 class="mb-0 text-info">
                        {{ number_format($stats['subscriptions_trial']) }}
                    </h3>

                </div>

            </div>

        </div>


        <div class="col-xl-2 col-md-4 col-sm-6">

            <div class="card border-0 shadow-sm rounded-4 h-100">

                <div class="card-body p-4">

                    <div class="text-muted small mb-2">
                        الباقات الفعالة
                    </div>

                    <h3 class="mb-0">
                        {{ number_format($stats['plans_active']) }}
                    </h3>

                </div>

            </div>

        </div>


        <div class="col-xl-2 col-md-4 col-sm-6">

            <div class="card border-0 shadow-sm rounded-4 h-100">

                <div class="card-body p-4">

                    <div class="text-muted small mb-2">
                        تنتهي خلال 30 يوم
                    </div>

                    <h3 class="mb-0 text-warning">
                        {{ number_format($stats['expiring_soon']) }}
                    </h3>

                </div>

            </div>

        </div>

    </div>


    <div class="row g-4">

        {{-- Latest Tenants --}}
        <div class="col-xl-6">

            <div class="card border-0 shadow-sm rounded-4 h-100">

                <div class="card-header bg-white border-0 p-4">

                    <div
                        class="d-flex justify-content-between align-items-center"
                    >

                        <h6 class="mb-0">
                            أحدث العملاء
                        </h6>

                        <a
                            href="{{ route('system.tenants.index') }}"
                            class="btn btn-sm btn-outline-primary"
                        >
                            عرض الكل
                        </a>

                    </div>

                </div>


                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">

                        <thead class="table-light">
                            <tr>
                                <th>الكود</th>
                                <th>العميل</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>

                        <tbody>

                        @forelse($latestTenants as $tenant)

                            <tr>

                                <td dir="ltr">
                                    {{ $tenant->code }}
                                </td>

                                <td>
                                    {{ $tenant->name }}
                                </td>

                                <td>

                                    @if($tenant->status === 'active')

                                        <span
                                            class="badge bg-success-subtle text-success"
                                        >
                                            فعال
                                        </span>

                                    @else

                                        <span
                                            class="badge bg-secondary-subtle text-secondary"
                                        >
                                            {{ $tenant->status }}
                                        </span>

                                    @endif

                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td
                                    colspan="3"
                                    class="text-center text-muted py-4"
                                >
                                    لا يوجد عملاء.
                                </td>
                            </tr>

                        @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>


        {{-- Expiring Subscriptions --}}
        <div class="col-xl-6">

            <div class="card border-0 shadow-sm rounded-4 h-100">

                <div class="card-header bg-white border-0 p-4">

                    <div
                        class="d-flex justify-content-between align-items-center"
                    >

                        <h6 class="mb-0">
                            الاشتراكات القريبة من الانتهاء
                        </h6>

                        <a
                            href="{{ route('system.subscriptions.index') }}"
                            class="btn btn-sm btn-outline-primary"
                        >
                            عرض الكل
                        </a>

                    </div>

                </div>


                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">

                        <thead class="table-light">
                            <tr>
                                <th>العميل</th>
                                <th>الباقة</th>
                                <th>تاريخ الانتهاء</th>
                            </tr>
                        </thead>

                        <tbody>

                        @forelse($expiringSubscriptions as $subscription)

                            <tr>

                                <td>
                                    {{
                                        $subscription->tenant?->name
                                        ?? '—'
                                    }}
                                </td>

                                <td>
                                    {{
                                        $subscription->plan?->name
                                        ?? '—'
                                    }}
                                </td>

                                <td dir="ltr">
                                    {{
                                        $subscription->ends_at
                                            ?->format('Y-m-d')
                                        ?? '—'
                                    }}
                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="3"
                                    class="text-center text-muted py-4"
                                >
                                    لا توجد اشتراكات قريبة من الانتهاء.
                                </td>

                            </tr>

                        @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection