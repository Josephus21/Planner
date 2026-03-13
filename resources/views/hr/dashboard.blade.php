@extends('layouts.dashboard')

@section('content')
<div class="page-heading">
    <h3>HR Manager Dashboard</h3>
    <p class="text-subtitle text-muted">Overview of employees, attendance, leave, and payroll.</p>
</div>

<div class="page-content">
<section class="section">

    {{-- TOP CARDS --}}
    <div class="row">
        <div class="col-6 col-lg-2 col-md-4">
            <div class="card">
                <div class="card-body px-3 py-4-5">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted font-semibold">Employees</h6>
                            <h4 class="font-extrabold mb-0">{{ $totalEmployees ?? 0 }}</h4>
                        </div>
                        <div class="avatar avatar-lg bg-light-primary">
                            <span class="avatar-content"><i class="bi bi-people-fill text-primary fs-3"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-2 col-md-4">
            <div class="card">
                <div class="card-body px-3 py-4-5">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted font-semibold">Present Today</h6>
                            <h4 class="font-extrabold mb-0">{{ $presentToday ?? 0 }}</h4>
                        </div>
                        <div class="avatar avatar-lg bg-light-success">
                            <span class="avatar-content"><i class="bi bi-check-circle-fill text-success fs-3"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-2 col-md-4">
            <div class="card">
                <div class="card-body px-3 py-4-5">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted font-semibold">Late Today</h6>
                            <h4 class="font-extrabold mb-0">{{ $lateToday ?? 0 }}</h4>
                        </div>
                        <div class="avatar avatar-lg bg-light-warning">
                            <span class="avatar-content"><i class="bi bi-alarm-fill text-warning fs-3"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-2 col-md-4">
            <div class="card">
                <div class="card-body px-3 py-4-5">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted font-semibold">On Leave</h6>
                            <h4 class="font-extrabold mb-0">{{ $onLeaveToday ?? 0 }}</h4>
                        </div>
                        <div class="avatar avatar-lg bg-light-danger">
                            <span class="avatar-content"><i class="bi bi-calendar-x-fill text-danger fs-3"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-2 col-md-4">
            <div class="card">
                <div class="card-body px-3 py-4-5">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted font-semibold">Pending Leave</h6>
                            <h4 class="font-extrabold mb-0">{{ $pendingLeaveRequests ?? 0 }}</h4>
                        </div>
                        <div class="avatar avatar-lg bg-light-info">
                            <span class="avatar-content"><i class="bi bi-hourglass-split text-info fs-3"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-2 col-md-4">
            <div class="card">
                <div class="card-body px-3 py-4-5">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted font-semibold">Payroll Period</h6>
                            <h4 class="font-extrabold mb-0">{{ $payrollCount ?? 0 }}</h4>
                        </div>
                        <div class="avatar avatar-lg bg-light-secondary">
                            <span class="avatar-content"><i class="bi bi-cash-stack text-secondary fs-3"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- QUICK ACTIONS --}}
    <div class="card">
        <div class="card-header">
            <h4>Quick Actions</h4>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('employees.create') }}" class="btn btn-primary">Add Employee</a>
                <a href="{{ route('schedules.index') }}" class="btn btn-outline-primary">Assign Schedule</a>
                <a href="{{ route('leave-requests.index') }}" class="btn btn-outline-success">Approve Leave</a>
                <a href="{{ route('payrolls.index') }}" class="btn btn-outline-warning">Generate Payroll</a>
                <a href="{{ route('attendance.reports.index') }}" class="btn btn-outline-info">Attendance Report</a>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- ATTENDANCE OVERVIEW --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4>Attendance Overview</h4>
                </div>
                <div class="card-body">
                    <div id="attendance-chart"></div>
                </div>
            </div>
        </div>

        {{-- EMPLOYEES BY DEPARTMENT --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h4>Employees by Department</h4>
                </div>
                <div class="card-body">
                    <div id="department-chart"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- PENDING LEAVE REQUESTS --}}
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4>Pending Leave Requests</h4>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendingLeaves ?? [] as $leave)
                                <tr>
                                    <td>{{ $leave->employee->full_name ?? 'N/A' }}</td>
                                    <td>{{ $leave->leave_type ?? 'Leave' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($leave->created_at)->format('M d, Y') }}</td>
                                    <td><span class="badge bg-warning">Pending</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No pending requests.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- UPCOMING BIRTHDAYS --}}
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4>Upcoming Birthdays</h4>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Birthday</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($upcomingBirthdays ?? [] as $employee)
                                <tr>
                                    <td>{{ $employee->full_name }}</td>
                                    <td>{{ $employee->department->name ?? 'N/A' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($employee->birthdate)->format('M d') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No upcoming birthdays.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- RECENT ATTENDANCE --}}
    <div class="card">
        <div class="card-header">
            <h4>Recent Attendance Logs</h4>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Date</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Late Minutes</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentAttendance ?? [] as $log)
                        <tr>
                            <td>{{ $log->employee->full_name ?? 'N/A' }}</td>
                            <td>{{ \Carbon\Carbon::parse($log->work_date)->format('M d, Y') }}</td>
                            <td>{{ $log->time_in ?? '-' }}</td>
                            <td>{{ $log->time_out ?? '-' }}</td>
                            <td>{{ $log->minutes_late ?? 0 }}</td>
                            <td>
                                @if(($log->status ?? '') === 'present')
                                    <span class="badge bg-success">Present</span>
                                @elseif(($log->status ?? '') === 'late')
                                    <span class="badge bg-warning">Late</span>
                                @elseif(($log->status ?? '') === 'absent')
                                    <span class="badge bg-danger">Absent</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($log->status ?? 'N/A') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No attendance logs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</section>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    const attendanceChart = new ApexCharts(document.querySelector("#attendance-chart"), {
        chart: {
            type: 'bar',
            height: 300
        },
        series: [{
            name: 'Employees',
            data: [
                {{ $presentToday ?? 0 }},
                {{ $lateToday ?? 0 }},
                {{ $absentToday ?? 0 }},
                {{ $onLeaveToday ?? 0 }}
            ]
        }],
        xaxis: {
            categories: ['Present', 'Late', 'Absent', 'On Leave']
        }
    });
    attendanceChart.render();

    const departmentChart = new ApexCharts(document.querySelector("#department-chart"), {
        chart: {
            type: 'donut',
            height: 300
        },
        series: {!! json_encode($departmentCounts ?? [10, 8, 6, 4]) !!},
        labels: {!! json_encode($departmentLabels ?? ['HR', 'Admin', 'Production', 'Sales']) !!}
    });
    departmentChart.render();
</script>
@endpush