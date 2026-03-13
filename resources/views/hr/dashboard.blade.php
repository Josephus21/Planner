@extends('layouts.dashboard')
<style>

.company-card{
    background-size:cover;
    background-position:center;
    border-radius:12px;
    overflow:hidden;
    position:relative;
    min-height:120px;
}

.company-card .card-overlay{
    position:absolute;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.55);
}

.company-card .card-body{
    position:relative;
    z-index:2;
}

</style>
@section('content')
<div class="page-heading">
    <h3>HR Dashboard</h3>
    <p class="text-subtitle text-muted">Overview of attendance, companies, leave, and payroll.</p>
</div>

<div class="page-content">
<section class="section">

    <div class="row">
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

        @forelse($companyCards as $company)
<div class="col-6 col-lg-2 col-md-4">

    <div class="card company-card text-white"
         style="
           background-image:url('{{ url('logos/'.basename($company->logo)) }}')">

        <div class="card-overlay"></div>

        <div class="card-body position-relative px-3 py-4-4">

            <h6 class="font-semibold">{{ $company->name }}</h6>

            <h3 class="font-extrabold mb-0">
                {{ $company->employees_count ?? 0 }}
            </h3>

        

        </div>

    </div>

</div>
@empty
@endforelse
    </div>

    <div class="row">
        {{-- Recent Attendance Logs --}}
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
                                    <td>{{ optional($log->employee)->fullname ?? 'N/A' }}</td>
                                    <td>
                                        {{ !empty($log->work_date) ? \Carbon\Carbon::parse($log->work_date)->format('M d, Y') : '-' }}
                                    </td>
                                    <td>
                                        {{ !empty($log->time_in) ? \Carbon\Carbon::parse($log->time_in)->format('h:i A') : '-' }}
                                    </td>
                                    <td>
                                        {{ !empty($log->time_out) ? \Carbon\Carbon::parse($log->time_out)->format('h:i A') : '-' }}
                                    </td>
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

        {{-- Quick Stats + Pending Leave --}}
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
                            <div class="fw-bold">{{ optional($leave->employee)->fullname ?? 'Employee' }}</div>
                            <small class="text-muted d-block">
                                {{ $leave->leave_type ?? 'Leave Request' }}
                            </small>
                            @if(!empty($leave->start_date) || !empty($leave->end_date))
                                <small class="text-muted d-block">
                                    {{ !empty($leave->start_date) ? \Carbon\Carbon::parse($leave->start_date)->format('M d, Y') : '-' }}
                                    -
                                    {{ !empty($leave->end_date) ? \Carbon\Carbon::parse($leave->end_date)->format('M d, Y') : '-' }}
                                </small>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted mb-0">No pending leave requests.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Upcoming Birthdays --}}
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
                                    <td>{{ $employee->fullname ?? 'N/A' }}</td>
                                    <td>{{ optional($employee->department)->name ?? 'N/A' }}</td>
                                    <td>
                                        {{ !empty($employee->birth_date) ? \Carbon\Carbon::parse($employee->birth_date)->format('M d') : '-' }}
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

        {{-- Department Summary --}}
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