<!DOCTYPE html>
<html lang="en">
<head>
    @stack('styles')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GFX - HRIS</title>

    <link rel="shortcut icon" href="{{ asset('mazer/dist/assets/compiled/svg/favicon.svg') }}" type="image/x-icon">
    <link rel="stylesheet" href="{{ asset('mazer/dist/assets/compiled/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('mazer/dist/assets/compiled/css/app-dark.css') }}">
    <link rel="stylesheet" href="{{ asset('mazer/dist/assets/compiled/css/iconly.css') }}">
    <link rel="stylesheet" href="{{ asset('mazer/dist/assets/extensions/simple-datatables/style.css') }}">
    <link rel="stylesheet" href="{{ asset('mazer/dist/assets/compiled/css/table-datatable.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>

<body>
<script src="{{ asset('mazer/dist/assets/static/js/initTheme.js') }}"></script>

@php
    $user = auth()->user();
    $role = session('role');
    $isDeveloper = ($role === 'Developer');
    $isPlanner = ($role === 'Planner');

    function canMenu($user, $isDeveloper, $permissionKey) {
        if ($isDeveloper) return true;
        if (!$user) return false;
        if (!method_exists($user, 'hasPermission')) return false;
        return $user->hasPermission($permissionKey);
    }

    function canMenuAny($user, $isDeveloper, array $keys) {
        if ($isDeveloper) return true;
        if (!$user) return false;
        if (!method_exists($user, 'hasPermission')) return false;

        foreach ($keys as $k) {
            if ($user->hasPermission($k)) return true;
        }

        return false;
    }

    $adminOpen = request()->is('employees*')
        || request()->is('users*')
        || request()->is('vehicles*')
        || request()->is('schedules*')
        || request()->is('project-categories*')
        || request()->is('presences*');

    $hrOpen = request()->is('attendance-reports*')
        || request()->is('payrolls*')
        || request()->is('leave-requests*');

    $settingsOpen = request()->is('departments*')
        || request()->is('roles*');
@endphp

<style>
.user-switch{
    padding: 6px 10px;
    border-radius: 8px;
    transition: all .2s ease;
}

.user-switch:hover{
    background: #f4f6f9;
    transform: translateY(-1px);
}

.avatar{
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 600;
}

.sidebar-title{
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    color: #6c757d;
    margin-top: 16px;
    margin-bottom: 8px;
}

.menu .sidebar-item .sidebar-link span{
    font-size: 14px;
}
</style>

<div id="app">
    <div id="sidebar">
        <div class="sidebar-wrapper active">
            <div class="sidebar-header position-relative">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="logo text-center">
                        <a href="{{ route('dashboard') }}"
                           class="d-flex align-items-center justify-content-center text-decoration-none user-switch">

                            <div class="avatar bg-primary text-white me-2">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>

                            <div class="text-start">
                                <div class="fw-semibold text-dark">{{ auth()->user()->name }}</div>
                                <small class="text-muted"></small>
                            </div>
                        </a>
                    </div>

                    <div class="sidebar-toggler x">
                        <a href="#" class="sidebar-hide d-xl-none d-block">
                            <i class="bi bi-x bi-middle"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="sidebar-menu">
                <ul class="menu">

                    {{-- DASHBOARD --}}
                    <li class="sidebar-title">DASHBOARD</li>

                    @if($isPlanner)
                        @if(canMenuAny($user, $isDeveloper, ['projects.view']))
                            <li class="sidebar-item {{ request()->is('planner-dashboard*') ? 'active' : '' }}">
                                <a href="{{ route('planner.dashboard') }}" class="sidebar-link">
                                    <i class="bi bi-calendar2-week"></i>
                                    <span>Dashboard</span>
                                </a>
                            </li>
                        @endif
                    @else
                        @if(canMenu($user, $isDeveloper, 'dashboard.view'))
                            <li class="sidebar-item {{ request()->is('dashboard') ? 'active' : '' }}">
                                <a href="{{ route('dashboard') }}" class="sidebar-link">
                                    <i class="bi bi-grid-fill"></i>
                                    <span>Dashboard</span>
                                </a>
                            </li>
                        @endif
                    @endif

{{-- NEWS FEED --}}
                    @if(canMenuAny($user, $isDeveloper, ['feed.view','posts.create','comments.create','reactions.create']))
                        <li class="sidebar-item {{ request()->is('feed*') ? 'active' : '' }}">
                            <a href="{{ route('feed.index') }}" class="sidebar-link">
                                <i class="bi bi-newspaper"></i>
                                <span>Feed</span>
                            </a>
                        </li>
                    @endif

                    {{-- TASK --}}
                    <li class="sidebar-title">TASK</li>

                    @if(canMenuAny($user, $isDeveloper, ['tasks.view','tasks.create','tasks.edit','tasks.delete']))
                        <li class="sidebar-item {{ request()->is('tasks*') ? 'active' : '' }}">
                            <a href="{{ url('/tasks') }}" class="sidebar-link">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Task</span>
                            </a>
                        </li>
                    @endif

                    @if(canMenuAny($user, $isDeveloper, ['sales_orders.view']))
                        <li class="sidebar-item {{ request()->is('sales-orders*') ? 'active' : '' }}">
                            <a href="{{ route('sales-orders.index') }}" class="sidebar-link">
                                <i class="bi bi-receipt"></i>
                                <span>Job Order (Signage)</span>
                            </a>
                        </li>
                    @endif

                    @if(canMenuAny($user, $isDeveloper, ['job_orders.view']))
                        <li class="sidebar-item {{ request()->is('job-orders*') ? 'active' : '' }}">
                            <a href="{{ route('job-orders.index') }}" class="sidebar-link">
                                <i class="bi bi-box-seam"></i>
                                <span>Job Order (LFP-DPOD)</span>
                            </a>
                        </li>
                    @endif

{{-- PROJECTS --}}
                    @if(canMenuAny($user, $isDeveloper, ['projects.view','projects.create','projects.edit','projects.delete']))
                        <li class="sidebar-item {{ request()->is('projects*') ? 'active' : '' }}">
                            <a href="{{ route('projects.index') }}" class="sidebar-link">
                                <i class="bi bi-kanban-fill"></i>
                                <span>Projects</span>
                            </a>
                        </li>
                    @endif

                    {{-- ADMINISTRATOR DRAWER --}}
                    @if(
                        canMenuAny($user, $isDeveloper, ['employees.view','employees.create','employees.edit','employees.delete']) ||
                        canMenu($user, $isDeveloper, 'users.manage') ||
                        canMenuAny($user, $isDeveloper, ['vehicles.view','vehicles.create','vehicles.edit','vehicles.delete']) ||
                        canMenuAny($user, $isDeveloper, ['schedules.view','schedules.create','schedules.edit','schedules.delete']) ||
                        canMenuAny($user, $isDeveloper, ['project_categories.view','project_categories.create','project_categories.edit','project_categories.delete']) ||
                        canMenuAny($user, $isDeveloper, ['presences.view','presences.create','presences.edit','presences.delete'])
                    )
                        <li class="sidebar-item has-sub {{ $adminOpen ? 'active open' : '' }}">
                            <a href="#" class="sidebar-link">
                                <i class="bi bi-person-gear"></i>
                                <span>Administrator</span>
                            </a>
                            <ul class="submenu {{ $adminOpen ? 'active' : '' }}">

                                @if(canMenuAny($user, $isDeveloper, ['employees.view','employees.create','employees.edit','employees.delete']))
                                    <li class="submenu-item {{ request()->is('employees*') ? 'active' : '' }}">
                                        <a href="{{ url('/employees') }}">Employees</a>
                                    </li>
                                @endif

                                @if(canMenu($user, $isDeveloper, 'users.manage'))
                                    <li class="submenu-item {{ request()->is('users*') ? 'active' : '' }}">
                                        <a href="{{ route('users.index') }}">Users</a>
                                    </li>
                                @endif

                                @if(canMenuAny($user, $isDeveloper, ['vehicles.view','vehicles.create','vehicles.edit','vehicles.delete']))
                                    <li class="submenu-item {{ request()->is('vehicles*') ? 'active' : '' }}">
                                        <a href="{{ route('vehicles.index') }}">Vehicle</a>
                                    </li>
                                @endif

                                @if(canMenuAny($user, $isDeveloper, ['schedules.view','schedules.create','schedules.edit','schedules.delete']))
                                    <li class="submenu-item {{ request()->is('schedules*') ? 'active' : '' }}">
                                        <a href="{{ url('/schedules') }}">Schedules</a>
                                    </li>
                                @endif

                                @if(canMenuAny($user, $isDeveloper, ['project_categories.view','project_categories.create','project_categories.edit','project_categories.delete']))
                                    <li class="submenu-item {{ request()->is('project-categories*') ? 'active' : '' }}">
                                        <a href="{{ route('project-categories.index') }}">Project Categories</a>
                                    </li>
                                @endif

                                @if(canMenuAny($user, $isDeveloper, ['presences.view','presences.create','presences.edit','presences.delete']))
                                    <li class="submenu-item {{ request()->is('presences*') ? 'active' : '' }}">
                                        <a href="{{ url('/presences') }}">Presence</a>
                                    </li>
                                @endif

                            </ul>
                        </li>
                    @endif


                    {{-- HR DRAWER --}}
                    @if(
                        canMenuAny($user, $isDeveloper, ['attendance_reports.view','attendance_reports.create','attendance_reports.edit','attendance_reports.delete']) ||
                        canMenuAny($user, $isDeveloper, ['payrolls.view','payrolls.create','payrolls.edit','payrolls.delete']) ||
                        canMenuAny($user, $isDeveloper, ['leave_requests.view','leave_requests.create','leave_requests.edit','leave_requests.delete'])
                    )
                        <li class="sidebar-item has-sub {{ $hrOpen ? 'active open' : '' }}">
                            <a href="#" class="sidebar-link">
                                <i class="bi bi-people-fill"></i>
                                <span>HR</span>
                            </a>
                            <ul class="submenu {{ $hrOpen ? 'active' : '' }}">

                                @if(canMenuAny($user, $isDeveloper, ['attendance_reports.view','attendance_reports.create','attendance_reports.edit','attendance_reports.delete']))
                                    <li class="submenu-item {{ request()->is('attendance-reports*') ? 'active' : '' }}">
                                        <a href="{{ url('/attendance-reports') }}">Attendance Report</a>
                                    </li>
                                @endif

                                @if(canMenuAny($user, $isDeveloper, ['payrolls.view','payrolls.create','payrolls.edit','payrolls.delete']))
                                    <li class="submenu-item {{ request()->is('payrolls*') ? 'active' : '' }}">
                                        <a href="{{ url('/payrolls') }}">Payrolls</a>
                                    </li>
                                @endif

                                @if(canMenuAny($user, $isDeveloper, ['leave_requests.view','leave_requests.create','leave_requests.edit','leave_requests.delete']))
                                    <li class="submenu-item {{ request()->is('leave-requests*') ? 'active' : '' }}">
                                        <a href="{{ url('/leave-requests') }}">Leave Request</a>
                                    </li>
                                @endif

                            </ul>
                        </li>
                    @endif


                    {{-- SETTINGS DRAWER --}}
                    @if(
                        canMenuAny($user, $isDeveloper, ['departments.view','departments.create','departments.edit','departments.delete']) ||
                        canMenuAny($user, $isDeveloper, ['roles.view','roles.create','roles.edit','roles.delete'])
                    )
                        <li class="sidebar-item has-sub {{ $settingsOpen ? 'active open' : '' }}">
                            <a href="#" class="sidebar-link">
                                <i class="bi bi-gear-fill"></i>
                                <span>Settings</span>
                            </a>
                            <ul class="submenu {{ $settingsOpen ? 'active' : '' }}">

                                @if(canMenuAny($user, $isDeveloper, ['departments.view','departments.create','departments.edit','departments.delete']))
                                    <li class="submenu-item {{ request()->is('departments*') ? 'active' : '' }}">
                                        <a href="{{ url('/departments') }}">Departments</a>
                                    </li>
                                @endif

                                @if(canMenuAny($user, $isDeveloper, ['roles.view','roles.create','roles.edit','roles.delete']))
                                    <li class="submenu-item {{ request()->is('roles*') ? 'active' : '' }}">
                                        <a href="{{ url('/roles') }}">Roles</a>
                                    </li>
                                @endif

                            </ul>
                        </li>
                    @endif


                    {{-- LOGOUT --}}
                    <li class="sidebar-title">ACCOUNT</li>
                    <li class="sidebar-item">
                        <a href="{{ url('/logout') }}" class="sidebar-link">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Logout</span>
                        </a>
                    </li>

                </ul>
            </div>
        </div>
    </div>

    <div id="main">
        @yield('content')

        <footer>
            <div class="footer clearfix mb-0 text-muted">
                <div class="float-end">
                    <p><a>7Js Software Solution</a></p>
                </div>
            </div>
        </footer>
    </div>
</div>

<script src="{{ asset('mazer/dist/assets/static/js/components/dark.js') }}"></script>
<script src="{{ asset('mazer/dist/assets/extensions/perfect-scrollbar/perfect-scrollbar.min.js') }}"></script>
<script src="{{ asset('mazer/dist/assets/compiled/js/app.js') }}"></script>

<script src="{{ asset('mazer/dist/assets/extensions/apexcharts/apexcharts.min.js') }}"></script>
<script src="{{ asset('mazer/dist/assets/static/js/pages/dashboard.js') }}"></script>

<script src="{{ asset('mazer/dist/assets/extensions/simple-datatables/umd/simple-datatables.js') }}"></script>
<script src="{{ asset('mazer/dist/assets/static/js/pages/simple-datatables.js') }}"></script>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    flatpickr('.date', { dateFormat: "Y-m-d" });
    flatpickr('.datetime', { dateFormat: "Y-m-d H:i:s", enableTime: true });
</script>

@stack('scripts')
</body>
</html>