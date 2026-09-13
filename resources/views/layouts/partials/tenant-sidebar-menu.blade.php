<style>
    .sidebar-menu {
        padding: 14px 12px 24px;
    }

    .sidebar-section {
        margin-top: 14px;
        padding-top: 14px;
        border-top: 1px solid rgba(255, 255, 255, .07);
    }

    .sidebar-section:first-child {
        margin-top: 0;
        padding-top: 0;
        border-top: 0;
    }

    .sidebar-title {
        padding: 4px 13px 9px;
        color: #7186ad;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .2px;
    }

    .sidebar-link {
        position: relative;
        min-height: 44px;
        margin-bottom: 4px;
        padding: 10px 13px;
        display: flex;
        align-items: center;
        gap: 11px;
        color: #b8c4da;
        text-decoration: none;
        border-radius: 10px;
        transition: background .2s ease, color .2s ease, transform .2s ease;
    }

    .sidebar-link:hover {
        color: #fff;
        background: rgba(255, 255, 255, .07);
        transform: translateX(-2px);
    }

    .sidebar-link.active {
        color: #fff;
        background: linear-gradient(
            90deg,
            rgba(47, 107, 255, .30),
            rgba(47, 107, 255, .12)
        );
        box-shadow: inset -3px 0 0 var(--primary);
    }

    .sidebar-link.active::after {
        content: '';
        position: absolute;
        left: 12px;
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #60a5fa;
    }

    .sidebar-icon {
        width: 22px;
        height: 22px;
        flex: 0 0 22px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #8ea4ce;
        font-size: 17px;
    }

    .sidebar-link:hover .sidebar-icon,
    .sidebar-link.active .sidebar-icon {
        color: #fff;
    }

    .sidebar-link-label {
        min-width: 0;
        flex: 1 1 auto;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .sidebar-counter {
        min-width: 22px;
        height: 22px;
        padding: 0 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        background: #ef4444;
        border-radius: 11px;
        font-size: 10px;
        font-weight: 700;
    }
</style>

@php
    $sidebarUser = auth()->user();

    $canAdministration =
        $sidebarUser->can('users.view') ||
        $sidebarUser->can('roles.view');

    $canOrganization =
        $sidebarUser->can('branches.view') ||
        $sidebarUser->can('departments.view') ||
        $sidebarUser->can('job_titles.view') ||
        $sidebarUser->can('work_locations.view');

    $canHumanResources =
        $sidebarUser->can('employees.view') ||
        $sidebarUser->can('contracts.view') ||
        $sidebarUser->can('documents.view');

    $canAttendance =
        $sidebarUser->can('attendance.view') ||
        $sidebarUser->can('attendance.manage');

    $canLeaves =
        $sidebarUser->can('leave.view') ||
        $sidebarUser->can('leave.manage') ||
        $sidebarUser->can('leave.approve') ||
        $sidebarUser->can('attendance.view') ||
        $sidebarUser->can('attendance.manage');

    $canPayroll =
        $sidebarUser->can('payroll.view') ||
        $sidebarUser->can('payroll.manage') ||
        $sidebarUser->can('reports.view') ||
        $sidebarUser->can('loans.view') ||
        $sidebarUser->can('loans.manage') ||
        $sidebarUser->can('loans.approve');

    $canBanking =
        $sidebarUser->can('settings.update') ||
        $sidebarUser->can('payroll.manage') ||
        $sidebarUser->can('employees.update');

    $canSelfService =
        $sidebarUser->can('self_service.attendance') ||
        $sidebarUser->can('self_service.leave') ||
        $sidebarUser->can('self_service.payslips') ||
        $sidebarUser->can('self_service.loans');

    $pendingLeaveRequestsCount = 0;
    $pendingLoanRequestsCount = 0;

    if ($sidebarUser->can('leave.approve')) {
        $pendingLeaveRequestsCount =
            \App\Models\LeaveRequest::query()
                ->where('tenant_id', $sidebarUser->tenant_id)
                ->pending()
                ->count();
    }

    if ($sidebarUser->can('loans.approve')) {
        $pendingLoanRequestsCount =
            \App\Models\EmployeeLoan::query()
                ->where('tenant_id', $sidebarUser->tenant_id)
                ->where('status', 'submitted')
                ->count();
    }
@endphp

<nav class="sidebar-menu">
    <div class="sidebar-section">
        <div class="sidebar-title">الرئيسية</div>

        @can('dashboard.view')
            <a href="{{ route('app.dashboard') }}"
               class="sidebar-link {{ request()->routeIs('app.dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2 sidebar-icon"></i>
                <span class="sidebar-link-label">لوحة التحكم</span>
            </a>
        @endcan
    </div>

    @if($canAdministration)
        <div class="sidebar-section">
            <div class="sidebar-title">إدارة النظام</div>

            @can('users.view')
                <a href="{{ route('app.users.index') }}"
                   class="sidebar-link {{ request()->routeIs('app.users.*') ? 'active' : '' }}">
                    <i class="bi bi-people sidebar-icon"></i>
                    <span class="sidebar-link-label">المستخدمون</span>
                </a>
            @endcan

            @can('roles.view')
                <a href="{{ route('app.roles.index') }}"
                   class="sidebar-link {{ request()->routeIs('app.roles.*') ? 'active' : '' }}">
                    <i class="bi bi-shield-check sidebar-icon"></i>
                    <span class="sidebar-link-label">الأدوار والصلاحيات</span>
                </a>
            @endcan
        </div>
    @endif

    @if($canOrganization)
        <div class="sidebar-section">
            <div class="sidebar-title">الهيكل التنظيمي</div>

            @can('branches.view')
                <a href="{{ route('app.organization.branches.index') }}"
                   class="sidebar-link {{ request()->routeIs('app.organization.branches.*') ? 'active' : '' }}">
                    <i class="bi bi-buildings sidebar-icon"></i>
                    <span class="sidebar-link-label">الفروع</span>
                </a>
            @endcan

            @can('departments.view')
                <a href="{{ route('app.organization.departments.index') }}"
                   class="sidebar-link {{ request()->routeIs('app.organization.departments.*') ? 'active' : '' }}">
                    <i class="bi bi-diagram-3 sidebar-icon"></i>
                    <span class="sidebar-link-label">الإدارات والأقسام</span>
                </a>
            @endcan

            @can('job_titles.view')
                <a href="{{ route('app.organization.job-titles.index') }}"
                   class="sidebar-link {{ request()->routeIs('app.organization.job-titles.*') ? 'active' : '' }}">
                    <i class="bi bi-person-badge sidebar-icon"></i>
                    <span class="sidebar-link-label">المسميات الوظيفية</span>
                </a>
            @endcan

            @can('work_locations.view')
                <a href="{{ route('app.organization.work-locations.index') }}"
                   class="sidebar-link {{ request()->routeIs('app.organization.work-locations.*') ? 'active' : '' }}">
                    <i class="bi bi-geo-alt sidebar-icon"></i>
                    <span class="sidebar-link-label">مواقع العمل</span>
                </a>
            @endcan
        </div>
    @endif

    @if($canHumanResources)
        <div class="sidebar-section">
            <div class="sidebar-title">الموارد البشرية</div>

            @can('employees.view')
                <a href="{{ route('app.employees.index') }}"
                   class="sidebar-link {{ request()->routeIs('app.employees.*') ? 'active' : '' }}">
                    <i class="bi bi-person-vcard sidebar-icon"></i>
                    <span class="sidebar-link-label">الموظفون</span>
                </a>
            @endcan

            @can('contracts.view')
                <a href="{{ route('app.contracts.index') }}"
                   class="sidebar-link {{ request()->routeIs('app.contracts.*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-text sidebar-icon"></i>
                    <span class="sidebar-link-label">عقود الموظفين</span>
                </a>
            @endcan

            @can('documents.view')
                <a href="{{ route('app.documents.index') }}"
                   class="sidebar-link {{ request()->routeIs('app.documents.*') ? 'active' : '' }}">
                    <i class="bi bi-folder2-open sidebar-icon"></i>
                    <span class="sidebar-link-label">مستندات الموظفين</span>
                </a>
            @endcan
        </div>
    @endif

    @if($canAttendance)
        <div class="sidebar-section">
            <div class="sidebar-title">الدوام والحضور</div>

            @can('attendance.view')
                <a href="{{ route('app.attendance.shifts.index') }}"
                   class="sidebar-link {{ request()->routeIs('app.attendance.shifts.*') || request()->routeIs('app.attendance.policy.*') ? 'active' : '' }}">
                    <i class="bi bi-clock-history sidebar-icon"></i>
                    <span class="sidebar-link-label">الورديات والتكليفات</span>
                </a>

                <a href="{{ route('app.attendance.index') }}"
                   class="sidebar-link {{ request()->routeIs('app.attendance.index') ? 'active' : '' }}">
                    <i class="bi bi-calendar2-check sidebar-icon"></i>
                    <span class="sidebar-link-label">الحضور والانصراف</span>
                </a>

                <a href="{{ route('app.attendance.overtime.index') }}"
                   class="sidebar-link {{ request()->routeIs('app.attendance.overtime.*') ? 'active' : '' }}">
                    <i class="bi bi-hourglass-split sidebar-icon"></i>
                    <span class="sidebar-link-label">العمل الإضافي</span>
                </a>
            @endcan

        </div>
    @endif

    @if($canLeaves)
        <div class="sidebar-section">
            <div class="sidebar-title">الإجازات والعطلات</div>

            @can('leave.manage')
                <a href="{{ route('app.leave-types.index') }}"
                   class="sidebar-link {{ request()->routeIs('app.leave-types.*') ? 'active' : '' }}">
                    <i class="bi bi-sliders sidebar-icon"></i>
                    <span class="sidebar-link-label">أنواع الإجازات</span>
                </a>

                <a href="{{ route('app.leave-balances.index') }}"
                   class="sidebar-link {{ request()->routeIs('app.leave-balances.*') ? 'active' : '' }}">
                    <i class="bi bi-calendar2-week sidebar-icon"></i>
                    <span class="sidebar-link-label">أرصدة الإجازات</span>
                </a>
            @endcan

            @can('leave.view')
                <a href="{{ route('app.leaves.requests.index') }}"
                   class="sidebar-link {{ request()->routeIs('app.leaves.requests.*') ? 'active' : '' }}">
                    <i class="bi bi-journal-text sidebar-icon"></i>
                    <span class="sidebar-link-label">طلبات الإجازات</span>
                </a>
            @endcan

            @can('leave.approve')
                <a href="{{ route('app.leaves.approvals.index') }}"
                   class="sidebar-link {{ request()->routeIs('app.leaves.approvals.*') ? 'active' : '' }}">
                    <i class="bi bi-check2-square sidebar-icon"></i>
                    <span class="sidebar-link-label">اعتماد الإجازات</span>

                    @if($pendingLeaveRequestsCount > 0)
                        <span class="sidebar-counter">{{ $pendingLeaveRequestsCount }}</span>
                    @endif
                </a>
            @endcan

            @if(
                $sidebarUser->can('attendance.view') ||
                $sidebarUser->can('attendance.manage') ||
                $sidebarUser->can('leave.view') ||
                $sidebarUser->can('leave.manage')
            )
                <a href="{{ route('app.holidays.index') }}"
                   class="sidebar-link {{ request()->routeIs('app.holidays.*') ? 'active' : '' }}">
                    <i class="bi bi-calendar-event sidebar-icon"></i>
                    <span class="sidebar-link-label">العطلات الرسمية</span>
                </a>
            @endif
        </div>
    @endif

    @if($canPayroll)
        <div class="sidebar-section">
            <div class="sidebar-title">الرواتب والسلف</div>

            @can('payroll.view')
                <a href="{{ route('app.payroll.salary-components.index') }}"
                   class="sidebar-link {{ request()->routeIs('app.payroll.salary-components.*') ? 'active' : '' }}">
                    <i class="bi bi-cash-coin sidebar-icon"></i>
                    <span class="sidebar-link-label">مكونات الراتب</span>
                </a>

                <a href="{{ route('app.payroll.salary-structures.index') }}"
                   class="sidebar-link {{ request()->routeIs('app.payroll.salary-structures.*') ? 'active' : '' }}">
                    <i class="bi bi-card-list sidebar-icon"></i>
                    <span class="sidebar-link-label">هياكل رواتب الموظفين</span>
                </a>

                <a href="{{ route('app.payroll.periods.index') }}"
                   class="sidebar-link {{ request()->routeIs('app.payroll.periods.*') ? 'active' : '' }}">
                    <i class="bi bi-calendar3 sidebar-icon"></i>
                    <span class="sidebar-link-label">فترات الرواتب</span>
                </a>

                <a href="{{ route('app.payroll.runs.index') }}"
                   class="sidebar-link {{ request()->routeIs('app.payroll.runs.*') ? 'active' : '' }}">
                    <i class="bi bi-calculator sidebar-icon"></i>
                    <span class="sidebar-link-label">تشغيل الرواتب</span>
                </a>

                <a href="{{ route('app.payroll.adjustments.index') }}"
                   class="sidebar-link {{ request()->routeIs('app.payroll.adjustments.*') ? 'active' : '' }}">
                    <i class="bi bi-plus-slash-minus sidebar-icon"></i>
                    <span class="sidebar-link-label">تسويات الرواتب</span>
                </a>

                <a href="{{ route('app.payroll-payment-batches.index') }}"
                   class="sidebar-link {{ request()->routeIs('app.payroll-payment-batches.*') ? 'active' : '' }}">
                    <i class="bi bi-bank sidebar-icon"></i>
                    <span class="sidebar-link-label">دفعات تحويل الرواتب</span>
                </a>
            @endcan

            @can('reports.view')
                <a href="{{ route('app.payroll.reports.index') }}"
                   class="sidebar-link {{ request()->routeIs('app.payroll.reports.*') ? 'active' : '' }}">
                    <i class="bi bi-bar-chart-line sidebar-icon"></i>
                    <span class="sidebar-link-label">تقارير الرواتب</span>
                </a>
            @endcan

            @if($sidebarUser->can('loans.view') || $sidebarUser->can('loans.manage'))
                <a href="{{ route('app.payroll.employee-loans.index') }}"
                   class="sidebar-link {{ request()->routeIs('app.payroll.employee-loans.*') ? 'active' : '' }}">
                    <i class="bi bi-wallet2 sidebar-icon"></i>
                    <span class="sidebar-link-label">إدارة السلف</span>
                </a>
            @endif

            @can('loans.approve')
                <a href="{{ route('app.payroll.employee-loan-approvals.index') }}"
                   class="sidebar-link {{ request()->routeIs('app.payroll.employee-loan-approvals.*') ? 'active' : '' }}">
                    <i class="bi bi-check2-circle sidebar-icon"></i>
                    <span class="sidebar-link-label">اعتماد السلف</span>

                    @if($pendingLoanRequestsCount > 0)
                        <span class="sidebar-counter">{{ $pendingLoanRequestsCount }}</span>
                    @endif
                </a>
            @endcan
        </div>
    @endif

    @if($canBanking)
        <div class="sidebar-section">
            <div class="sidebar-title">الإعدادات البنكية</div>

            @canany(['settings.update', 'payroll.manage'])
                <a href="{{ route('app.payroll.settings.index') }}"
                   class="sidebar-link {{ request()->routeIs('app.payroll.settings.*') ? 'active' : '' }}">
                    <i class="bi bi-bank2 sidebar-icon"></i>
                    <span class="sidebar-link-label">إعدادات بنك الرواتب</span>
                </a>
            @endcanany

            @canany(['employees.update', 'payroll.manage'])
                <a href="{{ route('app.payroll.bank-accounts.index') }}"
                   class="sidebar-link {{ request()->routeIs('app.payroll.bank-accounts.*') ? 'active' : '' }}">
                    <i class="bi bi-credit-card-2-front sidebar-icon"></i>
                    <span class="sidebar-link-label">حسابات الموظفين البنكية</span>
                </a>
            @endcanany
        </div>
    @endif

    @if($canSelfService)
        <div class="sidebar-section">
            <div class="sidebar-title">الخدمة الذاتية</div>

            @can('self_service.attendance')
                <a href="{{ route('app.attendance.self-service.index') }}"
                   class="sidebar-link {{ request()->routeIs('app.attendance.self-service.*') ? 'active' : '' }}">
                    <i class="bi bi-fingerprint sidebar-icon"></i>
                    <span class="sidebar-link-label">تسجيل حضوري</span>
                </a>
            @endcan

            @can('self_service.leave')
                <a href="{{ route('app.self-service.leave.index') }}"
                   class="sidebar-link {{ request()->routeIs('app.self-service.leave.*') ? 'active' : '' }}">
                    <i class="bi bi-calendar2-heart sidebar-icon"></i>
                    <span class="sidebar-link-label">إجازاتي</span>
                </a>
            @endcan

            @can('self_service.payslips')
                <a href="{{ route('app.self-service.payslips.index') }}"
                   class="sidebar-link {{ request()->routeIs('app.self-service.payslips.*') ? 'active' : '' }}">
                    <i class="bi bi-receipt sidebar-icon"></i>
                    <span class="sidebar-link-label">قسائم رواتبي</span>
                </a>
            @endcan

            @can('self_service.loans')
                <a href="{{ route('app.self-service.loans.index') }}"
                   class="sidebar-link {{ request()->routeIs('app.self-service.loans.*') ? 'active' : '' }}">
                    <i class="bi bi-wallet sidebar-icon"></i>
                    <span class="sidebar-link-label">سلفي المالية</span>
                </a>
            @endcan
        </div>
    @endif
</nav>
