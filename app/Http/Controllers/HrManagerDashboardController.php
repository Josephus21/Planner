<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\AttendanceLog;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\Department;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class HrManagerDashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::now('Asia/Manila')->toDateString();
        $now   = Carbon::now('Asia/Manila');

        $totalEmployees = 0;
        $presentToday = 0;
        $lateToday = 0;
        $absentToday = 0;
        $onLeaveToday = 0;
        $pendingLeaveRequests = 0;
        $payrollCount = 0;
        $pendingLeaves = collect();
        $recentAttendance = collect();
        $upcomingBirthdays = collect();
        $departmentLabels = collect();
        $departmentCounts = collect();

        // Employees count
        if (class_exists(Employee::class) && Schema::hasTable('employees')) {
            $employeeQuery = Employee::query();

            if (Schema::hasColumn('employees', 'status')) {
                $totalEmployees = (clone $employeeQuery)
                    ->where('status', 'active')
                    ->count();
            } else {
                $totalEmployees = (clone $employeeQuery)->count();
            }
        }

        // Attendance stats
        if (class_exists(AttendanceLog::class) && Schema::hasTable('attendance_logs')) {
            $presentQuery = AttendanceLog::where('work_date', $today);

            if (Schema::hasColumn('attendance_logs', 'time_in')) {
                $presentQuery->whereNotNull('time_in');
            }

            $presentToday = $presentQuery->count();

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

        // Leave stats
        if (class_exists(LeaveRequest::class) && Schema::hasTable('leave_requests')) {
            if (
                Schema::hasColumn('leave_requests', 'status') &&
                Schema::hasColumn('leave_requests', 'start_date') &&
                Schema::hasColumn('leave_requests', 'end_date')
            ) {
                $onLeaveToday = LeaveRequest::where('status', 'approved')
                    ->whereDate('start_date', '<=', $today)
                    ->whereDate('end_date', '>=', $today)
                    ->count();
            }

            if (Schema::hasColumn('leave_requests', 'status')) {
                $pendingLeaveRequests = LeaveRequest::where('status', 'pending')->count();

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
            $departments = Department::withCount('employees')->get();
            $departmentLabels = $departments->pluck('name');
            $departmentCounts = $departments->pluck('employees_count');
        }

        return view('hr.dashboard', compact(
            'totalEmployees',
            'presentToday',
            'lateToday',
            'absentToday',
            'onLeaveToday',
            'pendingLeaveRequests',
            'payrollCount',
            'pendingLeaves',
            'recentAttendance',
            'upcomingBirthdays',
            'departmentLabels',
            'departmentCounts'
        ));
    }
}