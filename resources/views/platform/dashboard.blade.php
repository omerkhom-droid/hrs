@extends('layouts.app')

@section('content')
<div class="container-fluid px-4" dir="rtl">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1">لوحة تحكم رؤية يوم</h3>
            <span class="text-muted">
                إدارة منصة الموارد البشرية
            </span>
        </div>
    </div>

    <div class="row g-3 mb-4">

        <div class="col-lg col-md-4 col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <small class="text-muted">إجمالي العملاء</small>
                    <h3 class="mt-2 mb-0">
                        {{ $stats['tenants'] }}
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-lg col-md-4 col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <small class="text-muted">العملاء النشطون</small>
                    <h3 class="mt-2 mb-0 text-success">
                        {{ $stats['active'] }}
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-lg col-md-4 col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <small class="text-muted">الفترة التجريبية</small>
                    <h3 class="mt-2 mb-0 text-primary">
                        {{ $stats['trial'] }}
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-lg col-md-4 col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <small class="text-muted">موقوف</small>
                    <h3 class="mt-2 mb-0 text-danger">
                        {{ $stats['suspended'] }}
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-lg col-md-4 col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <small class="text-muted">تنتهي خلال 30 يوم</small>
                    <h3 class="mt-2 mb-0 text-warning">
                        {{ $stats['expiring'] }}
                    </h3>
                </div>
            </div>
        </div>

    </div>

    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white py-3">
            <h5 class="mb-0">أحدث العملاء</h5>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>العميل</th>
                        <th>الخطة</th>
                        <th>الحالة</th>
                        <th>تاريخ التسجيل</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($recentTenants as $tenant)
                        <tr>
                            <td>
                                <strong>{{ $tenant->name }}</strong>
                                <div class="small text-muted">
                                    {{ $tenant->email }}
                                </div>
                            </td>

                            <td>
                                {{ $tenant->latestSubscription?->plan?->name_ar ?? '-' }}
                            </td>

                            <td>
                                @if($tenant->status === 'active')
                                    <span class="badge bg-success">
                                        نشط
                                    </span>

                                @elseif($tenant->status === 'trial')
                                    <span class="badge bg-primary">
                                        تجريبي
                                    </span>

                                @elseif($tenant->status === 'suspended')
                                    <span class="badge bg-danger">
                                        موقوف
                                    </span>

                                @else
                                    <span class="badge bg-secondary">
                                        {{ $tenant->status }}
                                    </span>
                                @endif
                            </td>

                            <td>
                                {{ $tenant->created_at->format('Y-m-d') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4"
                                class="text-center text-muted py-4">
                                لا يوجد عملاء حتى الآن
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

</div>
@endsection