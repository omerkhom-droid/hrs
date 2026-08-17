<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'لوحة التحكم') | رؤية يوم</title>

    @vite([
        'resources/sass/app.scss',
        'resources/js/app.js'
    ])

    <style>
        :root {
            --sidebar-width: 270px;
            --primary: #2f6bff;
            --sidebar: #071633;
            --body: #f5f7fb;
            --border: #e8ecf3;
        }

        html,
        body {
            min-height: 100%;
        }

        body {
            margin: 0;
            overflow-x: hidden;
            background: var(--body);
            color: #253047;
            font-family: Tahoma, Arial, sans-serif;
        }

        .tenant-sidebar {
            position: fixed;
            z-index: 1050;
            top: 0;
            right: 0;
            bottom: 0;
            width: var(--sidebar-width);
            overflow-y: auto;
            color: #fff;
            background: var(--sidebar);
            transition: transform .25s ease;
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,.22) transparent;
        }

        .tenant-sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .tenant-sidebar::-webkit-scrollbar-thumb {
            border-radius: 10px;
            background: rgba(255,255,255,.2);
        }

        .sidebar-brand {
            height: 75px;
            padding: 0 22px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            flex: 0 0 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: var(--primary);
            font-size: 22px;
            font-weight: 700;
        }

        .brand-name {
            font-size: 18px;
            font-weight: 700;
        }

        .brand-description {
            color: #8fa2c8;
            font-size: 11px;
        }

        .tenant-box {
            margin: 18px 14px 5px;
            padding: 14px;
            border-radius: 12px;
            background: rgba(255,255,255,.06);
        }

        .tenant-box small {
            color: #7f92b8;
        }

        .tenant-name {
            margin-top: 5px;
            font-size: 13px;
            font-weight: 700;
        }

        .sidebar-menu {
            padding: 15px 12px;
        }

        .sidebar-title {
            padding: 14px 12px 8px;
            color: #677ba3;
            font-size: 11px;
        }

        .sidebar-link {
            min-height: 46px;
            margin-bottom: 5px;
            padding: 11px 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #b8c4da;
            text-decoration: none;
            border-radius: 9px;
            transition: background .2s, color .2s;
        }

        .sidebar-link:hover,
        .sidebar-link.active {
            color: #fff;
            background: rgba(47,107,255,.16);
        }

        .sidebar-link.active {
            padding-right: 11px;
            border-right: 3px solid var(--primary);
        }

        .sidebar-icon {
            width: 22px;
            flex: 0 0 22px;
            text-align: center;
            font-size: 17px;
        }

        .subscription-box {
            margin: 5px 15px 20px;
            padding: 14px;
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 12px;
            font-size: 12px;
        }

        .subscription-box .value {
            margin-top: 5px;
            color: #fff;
            font-weight: 700;
        }

        .subscription-box .date {
            margin-top: 5px;
            color: #8395b8;
        }

        .tenant-main {
            min-height: 100vh;
            margin-right: var(--sidebar-width);
        }

        .tenant-header {
            position: sticky;
            z-index: 1000;
            top: 0;
            height: 75px;
            padding: 0 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fff;
            border-bottom: 1px solid var(--border);
        }

        .page-title {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-button {
            position: relative;
            width: 42px;
            height: 42px;
            padding: 0;
            color: #253047;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 10px;
        }

        .notification-badge {
            position: absolute;
            top: -4px;
            left: -4px;
            min-width: 18px;
            height: 18px;
            padding: 0 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            background: #ef4444;
            border-radius: 10px;
            font-size: 10px;
        }

        .user-button {
            padding: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            background: transparent;
            border: 0;
        }

        .user-avatar {
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            background: #eaf0ff;
            border-radius: 12px;
            font-weight: 700;
        }

        .user-name {
            font-size: 13px;
            font-weight: 700;
        }

        .user-role {
            color: #8993a7;
            font-size: 11px;
        }

        .tenant-content {
            padding: 28px;
        }

        .notification-dropdown {
            width: min(350px, calc(100vw - 30px));
            padding: 0;
            overflow: hidden;
            border: 0;
            border-radius: 14px;
            box-shadow: 0 15px 40px rgba(20,34,66,.15);
        }

        .notification-header {
            padding: 15px 18px;
            border-bottom: 1px solid var(--border);
            font-weight: 700;
        }

        .notification-item {
            padding: 13px 18px;
            border-bottom: 1px solid #f1f3f7;
        }

        .mobile-menu,
        .sidebar-overlay {
            display: none;
        }

        @media (max-width: 991.98px) {
            .tenant-sidebar {
                transform: translateX(100%);
            }

            .tenant-sidebar.show {
                transform: translateX(0);
            }

            .tenant-main {
                margin-right: 0;
            }

            .mobile-menu {
                display: block;
            }

            .user-info {
                display: none;
            }

            .sidebar-overlay.show {
                position: fixed;
                z-index: 1040;
                inset: 0;
                display: block;
                background: rgba(0,0,0,.45);
            }

            .tenant-content {
                padding: 18px;
            }
        }

        @media (max-width: 575.98px) {
            .tenant-header {
                padding: 0 14px;
            }

            .page-title {
                max-width: 160px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .tenant-content {
                padding: 14px;
            }
        }
    </style>

    {{-- تنسيقات الصفحات يجب أن تكون داخل head --}}
    @stack('styles')
</head>

<body>
@php
    $tenantUser = auth()->user();
    $tenant = $tenantUser?->tenant;
    $tenantTimezone = $tenant?->timezone ?: config('app.timezone', 'UTC');

    $currentSubscription = request()
        ->attributes
        ->get('current_subscription');

    $planName = data_get(
        $currentSubscription?->plan_snapshot,
        'name'
    ) ?? $currentSubscription?->plan?->name ?? '-';

    $subscriptionEnd = $currentSubscription?->ends_at
        ? $currentSubscription->ends_at
            ->copy()
            ->timezone($tenantTimezone)
            ->format('Y-m-d')
        : '-';

    $unreadCount = $tenantUser
        ? $tenantUser->unreadNotifications()->count()
        : 0;

    $recentNotifications = $tenantUser
        ? $tenantUser->unreadNotifications()
            ->latest()
            ->limit(5)
            ->get()
        : collect();
@endphp

<div id="sidebarOverlay" class="sidebar-overlay"></div>

<aside id="tenantSidebar" class="tenant-sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">ر</div>
        <div>
            <div class="brand-name">رؤية يوم</div>
            <div class="brand-description">نظام الموارد البشرية</div>
        </div>
    </div>

    <div class="tenant-box">
        <small>الشركة الحالية</small>
        <div class="tenant-name">{{ $tenant?->name ?? 'غير محدد' }}</div>
        <small dir="ltr">{{ $tenant?->code ?? '-' }}</small>
    </div>

    <nav class="sidebar-menu">
        <div class="sidebar-title">الرئيسية</div>

        @can('dashboard.view')
            <a href="{{ route('app.dashboard') }}"
               class="sidebar-link {{ request()->routeIs('app.dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid sidebar-icon"></i>
                <span>لوحة التحكم</span>
            </a>
        @endcan

        @can('users.view')
            <a href="{{ route('app.users.index') }}"
               class="sidebar-link {{ request()->routeIs('app.users.*') ? 'active' : '' }}">
                <i class="bi bi-people sidebar-icon"></i>
                <span>المستخدمون</span>
            </a>
        @endcan

        @can('roles.view')
            <a href="{{ route('app.roles.index') }}"
               class="sidebar-link {{ request()->routeIs('app.roles.*') ? 'active' : '' }}">
                <i class="bi bi-shield-lock sidebar-icon"></i>
                <span>الأدوار والصلاحيات</span>
            </a>
        @endcan

        @if(
            auth()->user()->can('branches.view') ||
            auth()->user()->can('departments.view') ||
            auth()->user()->can('job_titles.view') ||
            auth()->user()->can('work_locations.view')
        )
            <div class="sidebar-title">الهيكل التنظيمي</div>
        @endif

        @can('branches.view')
            <a href="{{ route('app.organization.branches.index') }}"
               class="sidebar-link {{ request()->routeIs('app.organization.branches.*') ? 'active' : '' }}">
                <i class="bi bi-building sidebar-icon"></i>
                <span>الفروع</span>
            </a>
        @endcan

        @can('departments.view')
            <a href="{{ route('app.organization.departments.index') }}"
               class="sidebar-link {{ request()->routeIs('app.organization.departments.*') ? 'active' : '' }}">
                <i class="bi bi-diagram-3 sidebar-icon"></i>
                <span>الإدارات والأقسام</span>
            </a>
        @endcan

        @can('job_titles.view')
            <a href="{{ route('app.organization.job-titles.index') }}"
               class="sidebar-link {{ request()->routeIs('app.organization.job-titles.*') ? 'active' : '' }}">
                <i class="bi bi-person-badge sidebar-icon"></i>
                <span>المسميات الوظيفية</span>
            </a>
        @endcan

        @can('work_locations.view')
            <a href="{{ route('app.organization.work-locations.index') }}"
               class="sidebar-link {{ request()->routeIs('app.organization.work-locations.*') ? 'active' : '' }}">
                <i class="bi bi-geo-alt sidebar-icon"></i>
                <span>مواقع العمل</span>
            </a>
        @endcan

        @if(
            auth()->user()->can('employees.view') ||
            auth()->user()->can('contracts.view') ||
            auth()->user()->can('documents.view')
        )
            <div class="sidebar-title">الموارد البشرية</div>
        @endif

        @can('employees.view')
            <a href="{{ route('app.employees.index') }}"
               class="sidebar-link {{ request()->routeIs('app.employees.*') ? 'active' : '' }}">
                <i class="bi bi-person-vcard sidebar-icon"></i>
                <span>الموظفون</span>
            </a>
        @endcan
        
        @can('contracts.view')
            <a href="{{ route('app.contracts.index') }}"
               class="sidebar-link {{ request()->routeIs('app.contracts.*') ? 'active' : '' }}">

                <span class="sidebar-icon">▣</span>
                <span>عقود الموظفين</span>

            </a>
        @endcan

        @can('documents.view')
            <a
                href="{{ route('app.documents.index') }}"
                class="sidebar-link {{ request()->routeIs('app.documents.*') ? 'active' : '' }}"
            >
                <span>▤</span>
                <span>مستندات الموظفين</span>
            </a>
        @endcan

        {{-- روابط العقود والمستندات ستضاف عند اكتمال وحداتها ومساراتها. --}}
        @can('attendance.view')
            <a
                href="{{ route('app.attendance.shifts.index') }}"
                class="sidebar-link {{ request()->routeIs('app.attendance.shifts.*') || request()->routeIs('app.attendance.policy.*') ? 'active' : '' }}"
            >
                <span>⌚</span>
                <span>الورديات والتكليفات</span>
            </a>
            
            <a
                href="{{ route('app.attendance.index') }}"
                class="sidebar-link {{ request()->routeIs('app.attendance.index') ? 'active' : '' }}"
            >
                <span>◷</span>
                <span>الحضور والانصراف</span>
            </a>

        @endcan
        @if(
            auth()->user()->can('self_service.attendance') ||
            auth()->user()->can('attendance.view')
        )
            <div class="sidebar-title">الدوام والخدمة الذاتية</div>
        @endif

        @can('self_service.attendance')
            <a href="{{ route('app.attendance.self-service.index') }}"
               class="sidebar-link {{ request()->routeIs('app.attendance.self-service.*') ? 'active' : '' }}">
                <i class="bi bi-fingerprint sidebar-icon"></i>
                <span>تسجيل حضوري</span>
            </a>
        @endcan
    </nav>

    <div class="subscription-box">
        <div class="text-white-50">الاشتراك الحالي</div>
        <div class="value">{{ $planName }}</div>
        <div class="date">ينتهي: {{ $subscriptionEnd }}</div>
    </div>
</aside>

<div class="tenant-main">
    <header class="tenant-header">
        <div class="d-flex align-items-center gap-3">
            <button type="button"
                    id="sidebarToggle"
                    class="header-button mobile-menu"
                    aria-label="فتح القائمة">
                <i class="bi bi-list"></i>
            </button>

            <h1 class="page-title">@yield('page-title', 'لوحة التحكم')</h1>
        </div>

        <div class="header-actions">
            <div class="dropdown">
                <button type="button"
                        class="header-button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        aria-label="الإشعارات">
                    <i class="bi bi-bell"></i>

                    @if($unreadCount > 0)
                        <span class="notification-badge">{{ $unreadCount }}</span>
                    @endif
                </button>

                <div class="dropdown-menu dropdown-menu-start notification-dropdown">
                    <div class="notification-header">
                        الإشعارات
                        <span class="badge bg-primary float-start">{{ $unreadCount }}</span>
                    </div>

                    @forelse($recentNotifications as $notification)
                        <div class="notification-item">
                            <div class="fw-semibold small">
                                {{ $notification->data['title'] ?? 'إشعار جديد' }}
                            </div>
                            <div class="text-muted small mt-1">
                                {{ $notification->data['message'] ?? '' }}
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">لا توجد إشعارات جديدة</div>
                    @endforelse
                </div>
            </div>

            <div class="dropdown">
                <button type="button"
                        class="user-button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false">
                    <div class="user-avatar">
                        {{ mb_substr($tenantUser?->name ?? 'م', 0, 1) }}
                    </div>

                    <div class="user-info text-end">
                        <div class="user-name">{{ $tenantUser?->name }}</div>
                        <div class="user-role">مستخدم الشركة</div>
                    </div>

                    <i class="bi bi-chevron-down small"></i>
                </button>

                <div class="dropdown-menu dropdown-menu-start shadow border-0">
                    <div class="px-3 py-2 border-bottom">
                        <strong>{{ $tenantUser?->name }}</strong>
                        <div class="text-muted small" dir="ltr">{{ $tenantUser?->email }}</div>
                    </div>

                    <form method="POST" action="{{ route('app.logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger py-2">
                            <i class="bi bi-box-arrow-right ms-2"></i>
                            تسجيل الخروج
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <main class="tenant-content">
        @yield('content')
    </main>
</div>

{{-- jQuery يحمل مرة واحدة فقط وقبل سكربتات الصفحات. --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
jQuery(function ($) {
    'use strict';

    $('#sidebarToggle').on('click', function () {
        $('#tenantSidebar, #sidebarOverlay').toggleClass('show');
    });

    $('#sidebarOverlay').on('click', function () {
        $('#tenantSidebar, #sidebarOverlay').removeClass('show');
    });

    $('#tenantSidebar .sidebar-link').on('click', function () {
        if ($(window).width() <= 991) {
            $('#tenantSidebar, #sidebarOverlay').removeClass('show');
        }
    });

    $(document).on('keydown', function (event) {
        if (event.key === 'Escape') {
            $('#tenantSidebar, #sidebarOverlay').removeClass('show');
        }
    });
});
</script>

{{-- يستدعى مرة واحدة بعد HTML وjQuery. --}}
@stack('scripts')
</body>
</html>
