@extends('layouts.system')

@section('title', 'لوحة التحكم')
@section('page-title', 'لوحة التحكم')

@section('content')

<div class="row g-4">

    <div class="col-12">

        <div class="card border-0 shadow-sm rounded-4">

            <div class="card-body p-4">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                    <div>
                        <h4 class="mb-2">
                            مرحباً، {{ auth()->user()->name }}
                        </h4>

                        <div class="text-muted">
                            أهلاً بك في لوحة إدارة منصة رؤية يوم
                        </div>
                    </div>

                    <div class="badge bg-success-subtle text-success px-3 py-2">
                        النظام يعمل
                    </div>

                </div>

            </div>

        </div>

    </div>


    <div class="col-md-4">

        <div class="card border-0 shadow-sm rounded-4 h-100">

            <div class="card-body p-4">

                <div class="text-muted small mb-2">
                    حالة النظام
                </div>

                <h5 class="text-success mb-0">
                    يعمل بشكل طبيعي
                </h5>

            </div>

        </div>

    </div>


    <div class="col-md-4">

        <div class="card border-0 shadow-sm rounded-4 h-100">

            <div class="card-body p-4">

                <div class="text-muted small mb-2">
                    آخر تسجيل دخول
                </div>

                <h6 class="mb-0">

                    {{ auth()->user()->last_login_at
                        ? auth()->user()->last_login_at->format('Y-m-d H:i')
                        : '---'
                    }}

                </h6>

            </div>

        </div>

    </div>


    <div class="col-md-4">

        <div class="card border-0 shadow-sm rounded-4 h-100">

            <div class="card-body p-4">

                <div class="text-muted small mb-2">
                    الإشعارات الجديدة
                </div>

                <h3 class="mb-0 text-primary">
                    {{ auth()->user()->unreadNotifications()->count() }}
                </h3>

            </div>

        </div>

    </div>

</div>

@endsection