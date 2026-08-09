<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <meta name="csrf-token"
          content="{{ csrf_token() }}">

    <title>
        @yield('title', 'لوحة التحكم') | رؤية يوم
    </title>

    @vite([
        'resources/sass/app.scss',
        'resources/js/app.js'
    ])
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.min.css">

    <style>

        :root {
            --sidebar-width: 270px;
            --primary: #2f6bff;
            --sidebar: #071633;
            --body: #f5f7fb;
            --border: #e8ecf3;
        }

        body {
            margin: 0;
            background: var(--body);
            font-family: Tahoma, Arial, sans-serif;
            color: #253047;
        }

        .tenant-sidebar {
            position: fixed;
            right: 0;
            top: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background: var(--sidebar);
            color: white;
            z-index: 1050;
            overflow-y: auto;
            transition: .25s;
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
            border-radius: 12px;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: bold;
        }

        .brand-name {
            font-weight: bold;
            font-size: 18px;
        }

        .brand-description {
            color: #8fa2c8;
            font-size: 11px;
        }

        .tenant-box {
            margin: 18px 14px 5px;
            padding: 14px;
            background: rgba(255,255,255,.06);
            border-radius: 12px;
        }

        .tenant-box small {
            color: #7f92b8;
        }

        .tenant-name {
            font-size: 13px;
            font-weight: bold;
            margin-top: 5px;
        }

        .sidebar-menu {
            padding: 15px 12px;
        }

        .sidebar-title {
            color: #677ba3;
            font-size: 11px;
            padding: 12px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #b8c4da;
            text-decoration: none;
            border-radius: 9px;
            padding: 12px 15px;
            margin-bottom: 5px;
            transition: .2s;
        }

        .sidebar-link:hover,
        .sidebar-link.active {
            background: rgba(47,107,255,.16);
            color: white;
        }

        .sidebar-link.active {
            border-right: 3px solid var(--primary);
        }

        .sidebar-icon {
            width: 22px;
            text-align: center;
        }

        .subscription-box {
            margin: 15px;
            padding: 14px;
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 12px;
            font-size: 12px;
        }

        .subscription-box .value {
            color: white;
            font-weight: bold;
            margin-top: 5px;
        }

        .subscription-box .date {
            color: #8395b8;
            margin-top: 5px;
        }

        .tenant-main {
            margin-right: var(--sidebar-width);
            min-height: 100vh;
        }

        .tenant-header {
            height: 75px;
            background: white;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .page-title {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-button {
            width: 42px;
            height: 42px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: white;
            position: relative;
        }

        .notification-badge {
            position: absolute;
            top: -4px;
            left: -4px;
            min-width: 18px;
            height: 18px;
            background: #ef4444;
            color: white;
            border-radius: 10px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-button {
            border: 0;
            background: transparent;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: #eaf0ff;
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .user-name {
            font-size: 13px;
            font-weight: bold;
        }

        .user-role {
            font-size: 11px;
            color: #8993a7;
        }

        .tenant-content {
            padding: 28px;
        }

        .notification-dropdown {
            width: 350px;
            border: 0;
            border-radius: 14px;
            box-shadow: 0 15px 40px rgba(20,34,66,.15);
            overflow: hidden;
            padding: 0;
        }

        .notification-header {
            padding: 15px 18px;
            border-bottom: 1px solid var(--border);
            font-weight: bold;
        }

        .notification-item {
            padding: 13px 18px;
            border-bottom: 1px solid #f1f3f7;
        }

        .mobile-menu {
            display: none;
        }

        .sidebar-overlay {
            display: none;
        }

        @media (max-width: 991px) {

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
                display: block;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,.4);
                z-index: 1040;
            }
        }

    </style>

</head>

<body>

@php

    $tenantUser = auth()->user();

    $tenant = $tenantUser->tenant;

    $currentSubscription =
        request()
            ->attributes
            ->get('current_subscription');

    $planName =
        data_get(
            $currentSubscription?->plan_snapshot,
            'name'
        )
        ?? $currentSubscription?->plan?->name
        ?? '-';

    $subscriptionEnd =
        $currentSubscription?->ends_at
            ? $currentSubscription
                ->ends_at
                ->copy()
                ->timezone($tenant->timezone)
                ->format('Y-m-d')
            : '-';

    $unreadCount =
        $tenantUser
            ->unreadNotifications()
            ->count();

    $recentNotifications =
        $tenantUser
            ->unreadNotifications()
            ->latest()
            ->limit(5)
            ->get();

@endphp


<div id="sidebarOverlay"
     class="sidebar-overlay">
</div>


<aside id="tenantSidebar"
       class="tenant-sidebar">

    <div class="sidebar-brand">

        <div class="brand-icon">
            ر
        </div>

        <div>

            <div class="brand-name">
                رؤية يوم
            </div>

            <div class="brand-description">
                نظام الموارد البشرية
            </div>

        </div>

    </div>


    <div class="tenant-box">

        <small>
            الشركة الحالية
        </small>

        <div class="tenant-name">
            {{ $tenant->name }}
        </div>

        <small dir="ltr">
            {{ $tenant->code }}
        </small>

    </div>


    <nav class="sidebar-menu">

        <div class="sidebar-title">
            الرئيسية
        </div>


        <a href="{{ route('app.dashboard') }}"
           class="sidebar-link
           {{ request()->routeIs('app.dashboard') ? 'active' : '' }}">

            <span class="sidebar-icon">
                ◫
            </span>

            <span>
                لوحة التحكم
            </span>

        </a>

        @can('users.view')

            <a
                href="{{ route('app.users.index') }}"
                class="sidebar-link
                    {{ request()->routeIs('app.users.*') ? 'active' : '' }}"
            >
                <i class="bi bi-people"></i>
                <span>المستخدمون</span>
            </a>

        @endcan

        {{-- HR Modules سنضيفها بعد الصلاحيات --}}

    </nav>


    <div class="subscription-box">

        <div class="text-white-50">
            الاشتراك الحالي
        </div>

        <div class="value">
            {{ $planName }}
        </div>

        <div class="date">
            ينتهي:
            {{ $subscriptionEnd }}
        </div>

    </div>

</aside>


<div class="tenant-main">

    <header class="tenant-header">

        <div class="d-flex align-items-center gap-3">

            <button type="button"
                    id="sidebarToggle"
                    class="header-button mobile-menu">

                ☰

            </button>

            <h1 class="page-title">
                @yield('page-title', 'لوحة التحكم')
            </h1>

        </div>


        <div class="header-actions">

            {{-- Notifications --}}
            <div class="dropdown">

                <button type="button"
                        class="header-button"
                        data-bs-toggle="dropdown">

                    🔔

                    @if($unreadCount > 0)

                        <span class="notification-badge">
                            {{ $unreadCount }}
                        </span>

                    @endif

                </button>


                <div class="dropdown-menu dropdown-menu-start notification-dropdown">

                    <div class="notification-header">

                        الإشعارات

                        <span class="badge bg-primary float-start">
                            {{ $unreadCount }}
                        </span>

                    </div>


                    @forelse($recentNotifications as $notification)

                        <div class="notification-item">

                            <div class="fw-semibold small">

                                {{ $notification->data['title']
                                    ?? 'إشعار جديد' }}

                            </div>

                            <div class="text-muted small mt-1">

                                {{ $notification->data['message']
                                    ?? '' }}

                            </div>

                        </div>

                    @empty

                        <div class="text-center text-muted py-4">
                            لا توجد إشعارات جديدة
                        </div>

                    @endforelse

                </div>

            </div>


            {{-- User --}}
            <div class="dropdown">

                <button type="button"
                        class="user-button"
                        data-bs-toggle="dropdown">

                    <div class="user-avatar">

                        {{ mb_substr(
                            $tenantUser->name,
                            0,
                            1
                        ) }}

                    </div>


                    <div class="user-info text-end">

                        <div class="user-name">
                            {{ $tenantUser->name }}
                        </div>

                        <div class="user-role">
                            مستخدم الشركة
                        </div>

                    </div>

                    <span>
                        ⌄
                    </span>

                </button>


                <div class="dropdown-menu dropdown-menu-start shadow border-0">

                    <div class="px-3 py-2 border-bottom">

                        <strong>
                            {{ $tenantUser->name }}
                        </strong>

                        <div class="text-muted small"
                             dir="ltr">

                            {{ $tenantUser->email }}

                        </div>

                    </div>


                    <form method="POST"
                          action="{{ route('app.logout') }}">

                        @csrf

                        <button type="submit"
                                class="dropdown-item text-danger py-2">

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


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const sidebar =
            document.getElementById(
                'tenantSidebar'
            );

        const overlay =
            document.getElementById(
                'sidebarOverlay'
            );

        const toggle =
            document.getElementById(
                'sidebarToggle'
            );


        if (toggle) {

            toggle.addEventListener(
                'click',
                function () {

                    sidebar.classList.toggle(
                        'show'
                    );

                    overlay.classList.toggle(
                        'show'
                    );

                }
            );

        }


        overlay.addEventListener(
            'click',
            function () {

                sidebar.classList.remove(
                    'show'
                );

                overlay.classList.remove(
                    'show'
                );

            }
        );

    }
);

</script>


@stack('scripts')

</body>

</html>