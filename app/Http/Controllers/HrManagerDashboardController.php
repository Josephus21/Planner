<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\AttendanceLog;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\Department;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class HrManagerDashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::now('Asia/Manila')->toDateString();
        $now   = Carbon::now('Asia/Manila');

        $lateToday = 0;
        $absentToday = 0;
        $payrollCount = 0;
        $pendingLeaves = collect();
        $recentAttendance = collect();
        $upcomingBirthdays = collect();
        $departmentLabels = collect();
        $departmentCounts = collect();
        $companyCards = collect();

        // Late today
        if (class_exists(AttendanceLog::class) && Schema::hasTable('attendance_logs')) {
            if (Schema::hasColumn('attendance_logs', 'minutes_late')) {
                $lateToday = AttendanceLog::where('work_date', $today)
                    ->where('minutes_late', '>', 0)
                    ->count();
            }

            if (method_exists(new AttendanceLog, 'employee')) {
                $recentAttendance = AttendanceLog::with('employee')
                    ->orderByDesc('work_date')
                    ->orderByDesc('id')
                    ->take(10)
                    ->get();
            } else {
                $recentAttendance = AttendanceLog::orderByDesc('work_date')
                    ->orderByDesc('id')
                    ->take(10)
                    ->get();
            }
        }

        // Absent today
        if (
            class_exists(Employee::class) &&
            Schema::hasTable('employees') &&
            method_exists(new Employee, 'attendanceLogs')
        ) {
            $absentQuery = Employee::query();

            if (Schema::hasColumn('employees', 'status')) {
                $absentQuery->where('status', 'active');
            }

            $absentToday = $absentQuery
                ->whereDoesntHave('attendanceLogs', function ($q) use ($today) {
                    $q->where('work_date', $today);

                    if (Schema::hasColumn('attendance_logs', 'time_in')) {
                        $q->whereNotNull('time_in');
                    }
                })
                ->count();
        }

        // Company employee counts via employee_companies pivot
        if (
            class_exists(Company::class) &&
            Schema::hasTable('companies') &&
            method_exists(new Company, 'employees')
        ) {
            $companyCards = Company::withCount(['employees' => function ($q) {
                if (Schema::hasColumn('employees', 'status')) {
                    $q->where('employees.status', 'active');
                }
            }])
            ->orderBy('name')
            ->get();
        }

        // Pending leave requests
        if (class_exists(LeaveRequest::class) && Schema::hasTable('leave_requests')) {
            if (Schema::hasColumn('leave_requests', 'status')) {
                $pendingLeavesQuery = LeaveRequest::where('status', 'pending');

                if (method_exists(new LeaveRequest, 'employee')) {
                    $pendingLeavesQuery->with('employee');
                }

                $pendingLeaves = $pendingLeavesQuery
                    ->latest()
                    ->take(5)
                    ->get();
            }
        }

        // Payroll count
        if (class_exists(Payroll::class) && Schema::hasTable('payrolls')) {
            $payrollCount = Payroll::count();
        }

        // Upcoming birthdays
        if (
            class_exists(Employee::class) &&
            Schema::hasTable('employees') &&
            Schema::hasColumn('employees', 'birth_date')
        ) {
            $birthdayQuery = Employee::whereNotNull('birth_date');

            if (method_exists(new Employee, 'department')) {
                $birthdayQuery->with('department');
            }

            if (Schema::hasColumn('employees', 'status')) {
                $birthdayQuery->where('status', 'active');
            }

            $upcomingBirthdays = $birthdayQuery
                ->get()
                ->filter(function ($employee) {
                    return !empty($employee->birth_date);
                })
                ->sortBy(function ($employee) use ($now) {
                    $birthday = Carbon::parse($employee->birth_date)->year($now->year);

                    if ($birthday->lt($now)) {
                        $birthday->addYear();
                    }

                    return $birthday->timestamp;
                })
                ->take(5)
                ->values();
        }

        // Department summary
        if (
            class_exists(Department::class) &&
            Schema::hasTable('departments') &&
            method_exists(new Department, 'employees')
        ) {
            $departments = Department::withCount(['employees' => function ($q) {
                if (Schema::hasColumn('employees', 'status')) {
                    $q->where('status', 'active');
                }
            }])->get();

            $departmentLabels = $departments->pluck('name');
            $departmentCounts = $departments->pluck('employees_count');
        }

        return view('hr.dashboard', compact(
            'lateToday',
            'absentToday',
            'payrollCount',
            'pendingLeaves',
            'recentAttendance',
            'upcomingBirthdays',
            'departmentLabels',
            'departmentCounts',
            'companyCards'
        ));
    }
}