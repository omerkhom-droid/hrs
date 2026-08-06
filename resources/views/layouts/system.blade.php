<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'لوحة التحكم') | رؤية يوم</title>

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    <style>
        :root {
            --sidebar-width: 270px;
            --primary: #2f6bff;
            --sidebar: #071633;
            --body-bg: #f5f7fb;
            --border: #e8ecf3;
        }

        body {
            margin: 0;
            background: var(--body-bg);
            font-family: Tahoma, Arial, sans-serif;
            color: #253047;
        }

        /* Sidebar */
        .system-sidebar {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background: var(--sidebar);
            color: #fff;
            z-index: 1050;
            overflow-y: auto;
            transition: .25s;
        }

        .sidebar-brand {
            height: 75px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 22px;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            background: var(--primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: bold;
        }

        .brand-title {
            font-size: 18px;
            font-weight: bold;
        }

        .brand-subtitle {
            color: #8ea0c5;
            font-size: 11px;
        }

        .sidebar-menu {
            padding: 20px 12px;
        }

        .sidebar-title {
            color: #6f82aa;
            font-size: 11px;
            padding: 10px 12px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #b9c4da;
            text-decoration: none;
            padding: 12px 15px;
            margin-bottom: 5px;
            border-radius: 9px;
            transition: .2s;
        }

        .sidebar-link:hover,
        .sidebar-link.active {
            background: rgba(47,107,255,.16);
            color: #fff;
        }

        .sidebar-link.active {
            border-right: 3px solid var(--primary);
        }

        .sidebar-icon {
            width: 23px;
            text-align: center;
            font-size: 18px;
        }

        /* Main */
        .system-main {
            margin-right: var(--sidebar-width);
            min-height: 100vh;
            transition: .25s;
        }

        /* Header */
        .system-header {
            height: 75px;
            background: #fff;
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
            font-size: 18px;
            font-weight: 700;
            margin: 0;
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
            background: #fff;
            border-radius: 10px;
            position: relative;
            cursor: pointer;
        }

        .notification-badge {
            position: absolute;
            top: -4px;
            left: -4px;
            min-width: 18px;
            height: 18px;
            padding: 0 4px;
            background: #ef4444;
            color: #fff;
            border-radius: 10px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-menu-button {
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

        .system-content {
            padding: 28px;
        }

        .notification-dropdown {
            width: 350px;
            border: 0;
            border-radius: 14px;
            box-shadow: 0 15px 40px rgba(20, 34, 66, .15);
            padding: 0;
            overflow: hidden;
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

        .notification-item-title {
            font-size: 13px;
            font-weight: bold;
        }

        .notification-item-text {
            font-size: 12px;
            color: #7b8497;
            margin-top: 3px;
        }

        .sidebar-overlay {
            display: none;
        }

        .mobile-menu {
            display: none;
        }

        @media (max-width: 991px) {
            .system-sidebar {
                transform: translateX(100%);
            }

            .system-sidebar.show {
                transform: translateX(0);
            }

            .system-main {
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

<div id="sidebarOverlay" class="sidebar-overlay"></div>

<aside id="systemSidebar" class="system-sidebar">

    <div class="sidebar-brand">
        <div class="brand-icon">ر</div>

        <div>
            <div class="brand-title">رؤية يوم</div>
            <div class="brand-subtitle">إدارة النظام</div>
        </div>
    </div>

    <nav class="sidebar-menu">

        <div class="sidebar-title">الرئيسية</div>

        <a href="{{ route('system.dashboard') }}"
           class="sidebar-link {{ request()->routeIs('system.dashboard') ? 'active' : '' }}">

            <span class="sidebar-icon">⌂</span>
            <span>لوحة التحكم</span>
        </a>

        {{-- سنضيف هنا العملاء والخطط والاشتراكات لاحقاً --}}

        <div class="sidebar-title mt-3">
            إدارة المنصة
        </div>

        <a href="{{ route('system.tenants.index') }}"
           class="sidebar-link {{ request()->routeIs('system.tenants.*') ? 'active' : '' }}">

            <span class="sidebar-icon">▣</span>
            <span>العملاء</span>
        </a>

        <a href="{{ route('system.plans.index') }}"
           class="sidebar-link {{ request()->routeIs('system.plans.*') ? 'active' : '' }}">

            <span class="sidebar-icon">◆</span>
            <span>الباقات</span>

        </a>
            
        <a href="{{ route('system.subscriptions.index') }}"
           class="sidebar-link {{ request()->routeIs('system.subscriptions.*') ? 'active' : '' }}">

            <span class="sidebar-icon">◉</span>
            <span>الاشتراكات</span>

        </a>
    </nav>

</aside>


<div class="system-main">

    <header class="system-header">

        <div class="d-flex align-items-center gap-3">

            <button type="button"
                    class="header-button mobile-menu"
                    id="sidebarToggle">
                ☰
            </button>

            <h1 class="page-title">
                @yield('page-title', 'لوحة التحكم')
            </h1>

        </div>


        <div class="header-actions">

            {{-- Notifications --}}
            <div class="dropdown">

                <button class="header-button"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false">

                    🔔

                    @if(auth()->user()->unreadNotifications()->count() > 0)
                        <span class="notification-badge">
                            {{ auth()->user()->unreadNotifications()->count() }}
                        </span>
                    @endif

                </button>

                <div class="dropdown-menu dropdown-menu-start notification-dropdown">

                    <div class="notification-header d-flex justify-content-between">
                        <span>الإشعارات</span>

                        <span class="badge bg-primary">
                            {{ auth()->user()->unreadNotifications()->count() }}
                        </span>
                    </div>

                    @forelse(auth()->user()->unreadNotifications()->latest()->limit(5)->get() as $notification)

                        <div class="notification-item">

                            <div class="notification-item-title">
                                {{ $notification->data['title'] ?? 'إشعار جديد' }}
                            </div>

                            <div class="notification-item-text">
                                {{ $notification->data['message'] ?? '' }}
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

                <button class="user-menu-button"
                        type="button"
                        data-bs-toggle="dropdown">

                    <div class="user-avatar">
                        {{ mb_substr(auth()->user()->name, 0, 1) }}
                    </div>

                    <div class="user-info text-end">

                        <div class="user-name">
                            {{ auth()->user()->name }}
                        </div>

                        <div class="user-role">
                            مدير النظام
                        </div>

                    </div>

                    <span>⌄</span>

                </button>


                <div class="dropdown-menu dropdown-menu-start shadow border-0">

                    <div class="px-3 py-2 border-bottom">
                        <strong>{{ auth()->user()->name }}</strong>

                        <div class="text-muted small">
                            {{ auth()->user()->email }}
                        </div>
                    </div>

                    <form method="POST"
                          action="{{ route('system.logout') }}">
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


    <main class="system-content">
        @yield('content')
    </main>

</div>


<script>
document.addEventListener('DOMContentLoaded', function () {

    const sidebar = document.getElementById('systemSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggle = document.getElementById('sidebarToggle');

    if (toggle) {
        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        });
    }

    overlay.addEventListener('click', function () {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
    });

});
</script>

@stack('scripts')

</body>
</html>