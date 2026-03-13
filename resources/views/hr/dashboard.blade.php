@extends('layouts.dashboard')

@section('content')
<div class="page-heading">
    <h3>HR Dashboard</h3>
    <p class="text-subtitle text-muted">Overview of employees, attendance, leave, and payroll.</p>
</div>

<div class="page-content">
<section class="section">

    <div class="row">
        <div class="col-6 col-lg-2 col-md-4">
            <div class="card">
                <div class="card-body px-3 py-4-4">
                    <h6 class="text-muted font-semibold">Employees</h6>
                    <h4 class="font-extrabold mb-0">{{ $totalEmployees ?? 0 }}</h4>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-2 col-md-4">
            <div class="card">
                <div class="card-body px-3 py-4-4">
                    <h6 class="text-muted font-semibold">Present Today</h6>
                    <h4 class="font-extrabold mb-0">{{ $presentToday ?? 0 }}</h4>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-2 col-md-4">
            <div class="card">
                <div class="card-body px-3 py-4-4">
                    <h6 class="text-muted font-semibold">Late Today</h6>
                    <h4 class="font-extrabold mb-0">{{ $lateToday ?? 0 }}</h4>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-2 col-md-4">
            <div class="card">
                <div class="card-body px-3 py-4-4">
                    <h6 class="text-muted font-semibold">Absent Today</h6>
                    <h4 class="font-extrabold mb-0">{{ $absentToday ?? 0 }}</h4>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-2 col-md-4">
            <div class="card">
                <div class="card-body px-3 py-4-4">
                    <h6 class="text-muted font-semibold">On Leave</h6>
                    <h4 class="font-extrabold mb-0">{{ $onLeaveToday ?? 0 }}</h4>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-2 col-md-4">
            <div class="card">
                <div class="card-body px-3 py-4-4">
                    <h6 class="text-muted font-semibold">Pending Leave</h6>
                    <h4 class="font-extrabold mb-0">{{ $pendingLeaveRequests ?? 0 }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4>Recent Attendance Logs</h4>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Date</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Late Minutes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentAttendance as $log)
                                <tr>
                                    <td>
                                        {{ optional($log->employee)->full_name
                                            ?? optional($log->employee)->name
                                            ?? optional($log->employee)->first_name
                                            ?? 'N/A' }}
                                    </td>
                                    <td>{{ $log->work_date ?? '-' }}</td>
                                    <td>{{ $log->time_in ?? '-' }}</td>
                                    <td>{{ $log->time_out ?? '-' }}</td>
                                    <td>{{ $log->minutes_late ?? 0 }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No attendance logs found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h4>Quick Stats</h4>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block">Payroll Records</small>
                        <strong>{{ $payrollCount ?? 0 }}</strong>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted d-block">Departments</small>
                        <strong>{{ isset($departmentLabels) ? $departmentLabels->count() : 0 }}</strong>
                    </div>

                    <div>
                        <small class="text-muted d-block">Upcoming Birthdays</small>
                        <strong>{{ isset($upcomingBirthdays) ? $upcomingBirthdays->count() : 0 }}</strong>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4>Pending Leave Requests</h4>
                </div>
                <div class="card-body">
                    @forelse($pendingLeaves as $leave)
                        <div class="mb-3 border-bottom pb-2">
                            <div class="fw-bold">
                                {{ optional($leave->employee)->full_name
                                    ?? optional($leave->employee)->name
                                    ?? optional($leave->employee)->first_name
                                    ?? 'Employee' }}
                            </div>
                            <small class="text-muted">
                                {{ $leave->leave_type ?? 'Leave Request' }}
                            </small>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No pending leave requests.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4>Upcoming Birthdays</h4>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Birthday</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($upcomingBirthdays as $employee)
                                <tr>
                                    <td>
                                        {{ $employee->full_name
                                            ?? $employee->name
                                            ?? $employee->first_name
                                            ?? 'N/A' }}
                                    </td>
                                    <td>{{ optional($employee->department)->name ?? 'N/A' }}</td>
                                    <td>
                                        {{ !empty($employee->birthdate) ? \Carbon\Carbon::parse($employee->birthdate)->format('M d') : '-' }}
                                    </td>
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

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4>Department Summary</h4>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Employees</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($departmentLabels ?? collect()) as $index => $label)
                                <tr>
                                    <td>{{ $label }}</td>
                                    <td>{{ $departmentCounts[$index] ?? 0 }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted">No department data found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</section>
</div>
@endsection