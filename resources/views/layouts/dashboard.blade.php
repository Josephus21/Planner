<!DOCTYPE html>
<html lang="en">
<head>
    @stack('styles')

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planner</title>

    <link rel="shortcut icon" href="{{ asset('mazer/dist/assets/compiled/svg/favicon.svg') }}" type="image/x-icon">
    <link rel="stylesheet" href="{{ asset('mazer/dist/assets/compiled/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('mazer/dist/assets/compiled/css/app-dark.css') }}">
    <link rel="stylesheet" href="{{ asset('mazer/dist/assets/compiled/css/iconly.css') }}">
</head>

<body>

<script src="{{ asset('mazer/dist/assets/static/js/initTheme.js') }}"></script>

@php
$user = auth()->user();
$role = session('role');

$isDeveloper = ($role === 'Developer');
$isPlanner = ($role === 'Planner');

function canMenu($user,$isDeveloper,$permission){
    if($isDeveloper) return true;
    if(!$user) return false;
    if(!method_exists($user,'hasPermission')) return false;
    return $user->hasPermission($permission);
}

function canMenuAny($user,$isDeveloper,$permissions){
    if($isDeveloper) return true;
    if(!$user) return false;
    if(!method_exists($user,'hasPermission')) return false;

    foreach($permissions as $p){
        if($user->hasPermission($p)) return true;
    }
    return false;
}
@endphp


<style>

.sidebar-title{
    font-size:12px;
    letter-spacing:1px;
    font-weight:700;
    color:#6c757d;
    margin-top:15px;
}

.user-switch{
    padding:6px 10px;
    border-radius:8px;
}

.avatar{
    width:32px;
    height:32px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:600;
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
{{ strtoupper(substr(auth()->user()->name,0,1)) }}
</div>

<div class="text-start">
<div class="fw-semibold text-dark">{{ auth()->user()->name }}</div>
</div>

</a>

</div>

<div class="sidebar-toggler x">
<a href="#" class="sidebar-hide d-xl-none d-block">
<i class="bi bi-x"></i>
</a>
</div>

</div>
</div>



<div class="sidebar-menu">

<ul class="menu">

{{-- DASHBOARD --}}
<li class="sidebar-title">DASHBOARD</li>

@if(canMenu($user,$isDeveloper,'dashboard.view'))
<li class="sidebar-item {{ request()->is('dashboard') ? 'active':'' }}">
<a href="{{ route('dashboard') }}" class="sidebar-link">
<i class="bi bi-grid-fill"></i>
<span>Dashboard</span>
</a>
</li>
@endif



{{-- TASK --}}
<li class="sidebar-title">TASK</li>

@if(canMenuAny($user,$isDeveloper,['tasks.view']))
<li class="sidebar-item {{ request()->is('tasks*') ? 'active':'' }}">
<a href="{{ url('/tasks') }}" class="sidebar-link">
<i class="bi bi-check-circle-fill"></i>
<span>Task</span>
</a>
</li>
@endif


@if(canMenuAny($user,$isDeveloper,['sales_orders.view']))
<li class="sidebar-item {{ request()->is('sales-orders*') ? 'active':'' }}">
<a href="{{ route('sales-orders.index') }}" class="sidebar-link">
<i class="bi bi-receipt"></i>
<span>Job Order (Signage)</span>
</a>
</li>
@endif


@if(canMenuAny($user,$isDeveloper,['job_orders.view']))
<li class="sidebar-item {{ request()->is('job-orders*') ? 'active':'' }}">
<a href="{{ route('job-orders.index') }}" class="sidebar-link">
<i class="bi bi-box-seam"></i>
<span>Job Order (LFP-DPOD)</span>
</a>
</li>
@endif




{{-- ADMINISTRATOR --}}
<li class="sidebar-title">ADMINISTRATOR</li>


@if(canMenuAny($user,$isDeveloper,['employees.view']))
<li class="sidebar-item {{ request()->is('employees*') ? 'active':'' }}">
<a href="{{ url('/employees') }}" class="sidebar-link">
<i class="bi bi-people-fill"></i>
<span>Employees</span>
</a>
</li>
@endif


@if(canMenu($user,$isDeveloper,'users.manage'))
<li class="sidebar-item {{ request()->is('users*') ? 'active':'' }}">
<a href="{{ route('users.index') }}" class="sidebar-link">
<i class="bi bi-people"></i>
<span>Users</span>
</a>
</li>
@endif


@if(canMenuAny($user,$isDeveloper,['vehicles.view']))
<li class="sidebar-item {{ request()->is('vehicles*') ? 'active':'' }}">
<a href="{{ route('vehicles.index') }}" class="sidebar-link">
<i class="bi bi-truck"></i>
<span>Vehicle</span>
</a>
</li>
@endif


@if(canMenuAny($user,$isDeveloper,['schedules.view']))
<li class="sidebar-item {{ request()->is('schedules*') ? 'active':'' }}">
<a href="{{ url('/schedules') }}" class="sidebar-link">
<i class="bi bi-calendar-week"></i>
<span>Schedules</span>
</a>
</li>
@endif


@if(canMenuAny($user,$isDeveloper,['project_categories.view']))
<li class="sidebar-item {{ request()->is('project-categories*') ? 'active':'' }}">
<a href="{{ route('project-categories.index') }}" class="sidebar-link">
<i class="bi bi-tags-fill"></i>
<span>Project Categories</span>
</a>
</li>
@endif


@if(canMenuAny($user,$isDeveloper,['presences.view']))
<li class="sidebar-item {{ request()->is('presences*') ? 'active':'' }}">
<a href="{{ url('/presences') }}" class="sidebar-link">
<i class="bi bi-table"></i>
<span>Presence</span>
</a>
</li>
@endif




{{-- HR --}}
<li class="sidebar-title">HR</li>

@if(canMenuAny($user,$isDeveloper,['attendance_reports.view']))
<li class="sidebar-item {{ request()->is('attendance-reports*') ? 'active':'' }}">
<a href="{{ url('/attendance-reports') }}" class="sidebar-link">
<i class="bi bi-clipboard-data"></i>
<span>Attendance Report</span>
</a>
</li>
@endif


@if(canMenuAny($user,$isDeveloper,['payrolls.view']))
<li class="sidebar-item {{ request()->is('payrolls*') ? 'active':'' }}">
<a href="{{ url('/payrolls') }}" class="sidebar-link">
<i class="bi bi-cash-stack"></i>
<span>Payrolls</span>
</a>
</li>
@endif


@if(canMenuAny($user,$isDeveloper,['leave_requests.view']))
<li class="sidebar-item {{ request()->is('leave-requests*') ? 'active':'' }}">
<a href="{{ url('/leave-requests') }}" class="sidebar-link">
<i class="bi bi-calendar2-x"></i>
<span>Leave Request</span>
</a>
</li>
@endif




{{-- SETTINGS --}}
<li class="sidebar-title">SETTINGS</li>

@if(canMenuAny($user,$isDeveloper,['departments.view']))
<li class="sidebar-item {{ request()->is('departments*') ? 'active':'' }}">
<a href="{{ url('/departments') }}" class="sidebar-link">
<i class="bi bi-briefcase-fill"></i>
<span>Departments</span>
</a>
</li>
@endif


@if(canMenuAny($user,$isDeveloper,['roles.view']))
<li class="sidebar-item {{ request()->is('roles*') ? 'active':'' }}">
<a href="{{ url('/roles') }}" class="sidebar-link">
<i class="bi bi-tag"></i>
<span>Roles</span>
</a>
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
<p>7Js Software Solution</p>
</div>
</div>
</footer>

</div>

</div>


<script src="{{ asset('mazer/dist/assets/extensions/perfect-scrollbar/perfect-scrollbar.min.js') }}"></script>
<script src="{{ asset('mazer/dist/assets/compiled/js/app.js') }}"></script>

@stack('scripts')

</body>
</html>