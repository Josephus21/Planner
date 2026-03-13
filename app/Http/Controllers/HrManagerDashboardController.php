<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\AttendanceLog;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\Department;
use Carbon\Carbon;

class HrManagerDashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::now('Asia/Manila')->toDateString();
        $now = Carbon::now('Asia/Manila');

        $totalEmployees = Employee::where('status', 'active')->count();

        $presentToday = AttendanceLog::where('work_date', $today)
            ->whereNotNull('time_in')
            ->count();

        $lateToday = AttendanceLog::where('work_date', $today)
            ->where('minutes_late', '>', 0)
            ->count();

        $absentToday = Employee::where('status', 'active')
            ->whereDoesntHave('attendanceLogs', function ($q) use ($today) {
                $q->where('work_date', $today);
            })
            ->count();

        $onLeaveToday = LeaveRequest::where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->count();

        $pendingLeaveRequests = LeaveRequest::where('status', 'pending')->count();

        $payrollCount = Payroll::count();

        $pendingLeaves = LeaveRequest::with('employee')
            ->where('status', 'pending')
            ->latest()
            ->take(5)
            ->get();

        $recentAttendance = AttendanceLog::with('employee')
            ->latest('work_date')
            ->take(10)
            ->get();

        $upcomingBirthdays = Employee::with('department')
            ->whereNotNull('birthdate')
            ->get()
            ->sortBy(function ($employee) use ($now) {
                $birthday = Carbon::parse($employee->birthdate)->year($now->year);
                if ($birthday->lt($now)) {
                    $birthday->addYear();
                }
                return $birthday->timestamp;
            })
            ->take(5);

        $departments = Department::withCount('employees')->get();
        $departmentLabels = $departments->pluck('name');
        $departmentCounts = $departments->pluck('employees_count');

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