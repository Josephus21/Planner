<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\AttendanceLog;
use App\Models\EmployeeScheduleAssignment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeDashboardController extends Controller
{
    public function index(Request $request)
    {
        // Your users table has employee_id (varchar). We'll resolve employee from logged-in user.
        $user = Auth::user();

        $employee = Employee::findOrFail((int) $user->employee_id);

        // Month range (current month)
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth   = now()->endOfMonth()->toDateString();

        // Fetch this month's attendance logs
        $monthLogs = AttendanceLog::where('employee_id', $employee->id)
            ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
            ->get();

        // ===== CARD 1: Total late minutes this month =====
        $totalLateMinutes = (int) $monthLogs->sum(function ($log) {
            return (int) ($log->minutes_late ?? 0);
        });

        // ===== CARD 2: Total absent this month =====
        // If you have is_absent column, count it.
        // If not, it will default to 0 (safe).
        $totalAbsent = (int) $monthLogs->filter(function ($log) {
            return (bool) ($log->is_absent ?? false);
        })->count();

        // ===== CARD 3: Running Payroll Balance (gross so far this month) =====
        // We compute using employee salary settings and attendance minutes/days.
        $salaryType = strtolower($employee->salary_type ?? 'monthly');
        $salaryAmount = (float) ($employee->salary_amount ?? $employee->salary ?? 0);
        $workDaysPerMonth = (int) ($employee->work_days_per_month ?? 26);
        $workHoursPerDay  = (float) ($employee->work_hours_per_day ?? 8);

        $daysPresent = 0;
$minutesWorked = 0;

foreach ($monthLogs as $log) {
    $hasWorkedMinutes = (int) ($log->minutes_worked ?? 0) > 0;
    $hasTimeIn = !empty($log->time_in);
    $hasTimeOut = !empty($log->time_out);
    $isAbsent = (bool) ($log->is_absent ?? false);

    // Count present only if there is actual attendance
    if (!$isAbsent && ($hasWorkedMinutes || $hasTimeIn || $hasTimeOut)) {
        $daysPresent++;
    }

    $minutesWorked += (int) ($log->minutes_worked ?? 0);
}

        // Compute hourly rate (same logic as your PayrollGenerator)
        $hourlyRate = $this->computeHourlyRate($salaryType, $salaryAmount, $workDaysPerMonth, $workHoursPerDay);

        // Gross so far
        $runningPayrollBalance = $this->computeGross(
            $salaryType,
            $salaryAmount,
            $hourlyRate,
            $daysPresent,
            $minutesWorked,
            $workHoursPerDay
        );

        // ===== Today's schedule =====
        $today = now()->toDateString();

        $currentScheduleAssignment = EmployeeScheduleAssignment::with('schedule')
            ->where('employee_id', $employee->id)
            ->where('effective_from', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $today);
            })
            ->latest('effective_from')
            ->first();

        $schedule = $currentScheduleAssignment ? $currentScheduleAssignment->schedule : null;

        // ===== Today's attendance log (create if missing) =====
        $log = AttendanceLog::firstOrCreate(
            ['employee_id' => $employee->id, 'work_date' => $today],
            []
        );

        // Ensure datetime fields are Carbon in the view
        // (If your AttendanceLog model has $casts for these, you can remove this)
        foreach (['time_in','break_out','break_in','lunch_out','lunch_in','time_out'] as $field) {
            if (!empty($log->{$field}) && !$log->{$field} instanceof Carbon) {
                $log->{$field} = Carbon::parse($log->{$field});
            }
        }

        return view('employee_dashboard.index', compact(
    'employee',
    'schedule',
    'log',
    'totalLateMinutes',
    'runningPayrollBalance',
    'totalAbsent'
));
    }

    // ===== helper methods copied from PayrollGenerator logic =====

    protected function computeHourlyRate(string $salaryType, float $salaryAmount, int $workDaysPerMonth, float $workHoursPerDay): float
    {
        $salaryType = strtolower($salaryType);

        if ($salaryAmount <= 0) return 0;

        if ($workDaysPerMonth <= 0) $workDaysPerMonth = 26;
        if ($workHoursPerDay <= 0) $workHoursPerDay = 8;

        if ($salaryType === 'hourly') {
            return round($salaryAmount, 6);
        } elseif ($salaryType === 'daily') {
            return round($salaryAmount / $workHoursPerDay, 6);
        } else {
            return round(($salaryAmount / $workDaysPerMonth) / $workHoursPerDay, 6);
        }
    }

    public function scheduleEvents(Request $request)
{
    $employeeId = Auth::user()->employee_id ?? null;

    // If your auth structure is different, replace this with your actual employee lookup
    // Example: $employeeId = Employee::where('user_id', Auth::id())->value('id');

    if (!$employeeId) {
        return response()->json([]);
    }

    // FullCalendar sends ?start=YYYY-MM-DD&end=YYYY-MM-DD
    $start = Carbon::parse($request->query('start'))->timezone('Asia/Manila')->startOfDay();
    $end   = Carbon::parse($request->query('end'))->timezone('Asia/Manila')->endOfDay();

    // Get assignments that overlap the requested range
    $assignments = EmployeeScheduleAssignment::with('schedule')
        ->where('employee_id', $employeeId)
        ->where('effective_from', '<=', $end->toDateString())
        ->where(function ($q) use ($start) {
            $q->whereNull('effective_to')
              ->orWhere('effective_to', '>=', $start->toDateString());
        })
        ->orderBy('effective_from')
        ->get();

    // Optional: attendance logs (so you can see time-in/time-out on the calendar)
    $logs = AttendanceLog::where('employee_id', $employeeId)
        ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
        ->get()
        ->keyBy('work_date');

    $events = [];

    // Build a daily event per day in the visible calendar range
    for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
        $d = $date->toDateString();

        // Find active assignment for this date (latest effective_from wins)
        $active = $assignments->filter(function ($a) use ($d) {
            return $a->effective_from <= $d && (is_null($a->effective_to) || $a->effective_to >= $d);
        })->last();

        if (!$active || !$active->schedule) continue;

        $sch = $active->schedule;

        $startDt = Carbon::parse($d.' '.$sch->start_time, 'Asia/Manila');
        $endDt   = Carbon::parse($d.' '.$sch->end_time, 'Asia/Manila');

        // Main schedule event
        $events[] = [
            'title' => $sch->name . ' (Work)',
            'start' => $startDt->toIso8601String(),
            'end'   => $endDt->toIso8601String(),
        ];

        // Break event (optional)
        if ($sch->break_start && $sch->break_end) {
            $events[] = [
                'title' => 'Break',
                'start' => Carbon::parse($d.' '.$sch->break_start, 'Asia/Manila')->toIso8601String(),
                'end'   => Carbon::parse($d.' '.$sch->break_end, 'Asia/Manila')->toIso8601String(),
            ];
        }

        // Lunch event (optional)
        if ($sch->lunch_start && $sch->lunch_end) {
            $events[] = [
                'title' => 'Lunch',
                'start' => Carbon::parse($d.' '.$sch->lunch_start, 'Asia/Manila')->toIso8601String(),
                'end'   => Carbon::parse($d.' '.$sch->lunch_end, 'Asia/Manila')->toIso8601String(),
            ];
        }

        // Attendance markers (optional)
        if (isset($logs[$d])) {
            $log = $logs[$d];

            if ($log->time_in) {
                $events[] = [
                    'title' => 'Time In',
                    'start' => Carbon::parse($log->time_in, 'Asia/Manila')->toIso8601String(),
                ];
            }
            if ($log->time_out) {
                $events[] = [
                    'title' => 'Time Out',
                    'start' => Carbon::parse($log->time_out, 'Asia/Manila')->toIso8601String(),
                ];
            }
        }
    }

    return response()->json($events);
}

    protected function computeGross(
        string $salaryType,
        float $salaryAmount,
        float $hourlyRate,
        int $daysPresent,
        int $minutesWorked,
        float $workHoursPerDay
    ): float {
        $salaryType = strtolower($salaryType);

        if ($hourlyRate <= 0) return 0;

        if ($salaryType === 'hourly') {
            return round(($minutesWorked / 60) * $hourlyRate, 2);
        } elseif ($salaryType === 'daily') {
            return round($daysPresent * ($hourlyRate * $workHoursPerDay), 2);
        } else {
            // monthly simple pro-rate by days present
            return round($daysPresent * ($hourlyRate * $workHoursPerDay), 2);
        }
    }
}