<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\AttendanceLog;
use App\Models\EmployeeScheduleAssignment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Holiday;
class EmployeeDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$user->employee_id) {
            abort(403, 'Your account is not linked to an employee record.');
        }

        $employee = Employee::with('company')->findOrFail((int) $user->employee_id);

        // Optional explicit company guard
        $companyWarning = null;
        if (!$employee->company_id) {
            $companyWarning = 'Your account is not assigned to a company.';
        }

        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth   = now()->endOfMonth()->toDateString();

        $monthLogs = AttendanceLog::where('employee_id', $employee->id)
            ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
            ->get();

        $totalLateMinutes = (int) $monthLogs->sum(function ($log) {
            return (int) ($log->minutes_late ?? 0);
        });

        $totalAbsent = (int) $monthLogs->filter(function ($log) {
            return (bool) ($log->is_absent ?? false);
        })->count();

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

            if (!$isAbsent && ($hasWorkedMinutes || $hasTimeIn || $hasTimeOut)) {
                $daysPresent++;
            }

            $minutesWorked += (int) ($log->minutes_worked ?? 0);
        }

        $hourlyRate = $this->computeHourlyRate(
            $salaryType,
            $salaryAmount,
            $workDaysPerMonth,
            $workHoursPerDay
        );

        $runningPayrollBalance = $this->computeGross(
            $salaryType,
            $salaryAmount,
            $hourlyRate,
            $daysPresent,
            $minutesWorked,
            $workHoursPerDay
        );

        $today = now()->toDateString();

        $currentScheduleAssignment = EmployeeScheduleAssignment::with('schedule')
            ->where('employee_id', $employee->id)
            ->where('effective_from', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $today);
            })
            ->latest('effective_from')
            ->first();

        $schedule = $currentScheduleAssignment ? $currentScheduleAssignment->schedule : null;

        $log = AttendanceLog::firstOrCreate(
            ['employee_id' => $employee->id, 'work_date' => $today],
            []
        );

        foreach (['time_in', 'break_out', 'break_in', 'lunch_out', 'lunch_in', 'time_out'] as $field) {
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
            'totalAbsent',
            'companyWarning'
        ));
    }

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
    $user = Auth::user();
    $employeeId = $user->employee_id ?? null;

    if (!$employeeId) {
        return response()->json([]);
    }

    $employee = Employee::find((int) $employeeId);

    if (!$employee) {
        return response()->json([]);
    }

    $start = Carbon::parse($request->query('start'))->timezone('Asia/Manila')->startOfDay();
    $end   = Carbon::parse($request->query('end'))->timezone('Asia/Manila')->endOfDay();

    $assignments = EmployeeScheduleAssignment::with('schedule')
        ->where('employee_id', $employeeId)
        ->where('effective_from', '<=', $end->toDateString())
        ->where(function ($q) use ($start) {
            $q->whereNull('effective_to')
              ->orWhere('effective_to', '>=', $start->toDateString());
        })
        ->orderBy('effective_from')
        ->get();

    $logs = AttendanceLog::where('employee_id', $employeeId)
        ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
        ->get()
        ->keyBy('work_date');

    $events = [];

    for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
        $d = $date->toDateString();

        $holiday = Holiday::query()
            ->where('is_active', true)
            ->where(function ($q) use ($employee, $d, $date) {
                $q->where(function ($sub) use ($employee, $d) {
                    $sub->whereDate('holiday_date', $d)
                        ->where(function ($companyQ) use ($employee) {
                            $companyQ->whereNull('company_id')
                                     ->orWhere('company_id', $employee->company_id);
                        });
                })->orWhere(function ($sub) use ($employee, $date) {
                    $sub->where('is_recurring', true)
                        ->whereMonth('holiday_date', $date->month)
                        ->whereDay('holiday_date', $date->day)
                        ->where(function ($companyQ) use ($employee) {
                            $companyQ->whereNull('company_id')
                                     ->orWhere('company_id', $employee->company_id);
                        });
                });
            })
            ->first();

        if ($holiday) {
            $events[] = [
                'title' => $holiday->name . ' (' . ucfirst($holiday->type) . ' Holiday)',
                'start' => $date->copy()->startOfDay()->toIso8601String(),
                'end'   => $date->copy()->endOfDay()->toIso8601String(),
                'allDay' => true,
                'backgroundColor' => $holiday->type === 'regular' ? '#dc3545' : '#fd7e14',
                'borderColor' => $holiday->type === 'regular' ? '#dc3545' : '#fd7e14',
            ];

            continue;
        }

        $active = $assignments->filter(function ($a) use ($d) {
            return $a->effective_from <= $d && (is_null($a->effective_to) || $a->effective_to >= $d);
        })->last();

        if (!$active || !$active->schedule) {
            continue;
        }

        $sch = $active->schedule;

        $startDt = Carbon::parse($d . ' ' . $sch->start_time, 'Asia/Manila');
        $endDt   = Carbon::parse($d . ' ' . $sch->end_time, 'Asia/Manila');

        $events[] = [
            'title' => $sch->name . ' (Work)',
            'start' => $startDt->toIso8601String(),
            'end'   => $endDt->toIso8601String(),
        ];

        if ($sch->break_start && $sch->break_end) {
            $events[] = [
                'title' => 'Break',
                'start' => Carbon::parse($d . ' ' . $sch->break_start, 'Asia/Manila')->toIso8601String(),
                'end'   => Carbon::parse($d . ' ' . $sch->break_end, 'Asia/Manila')->toIso8601String(),
            ];
        }

        if ($sch->lunch_start && $sch->lunch_end) {
            $events[] = [
                'title' => 'Lunch',
                'start' => Carbon::parse($d . ' ' . $sch->lunch_start, 'Asia/Manila')->toIso8601String(),
                'end'   => Carbon::parse($d . ' ' . $sch->lunch_end, 'Asia/Manila')->toIso8601String(),
            ];
        }

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
            return round($daysPresent * ($hourlyRate * $workHoursPerDay), 2);
        }
    }
}