<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\EmployeeScheduleAssignment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function punch(Request $request)
    {
        $data = $request->validate([
            'action' => 'required|in:time_in,break_out,break_in,lunch_out,lunch_in,time_out',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
        ]);

        $user = auth()->user();

        $employee = $user->employee_id ? Employee::find((int) $user->employee_id) : null;

        if (!$employee) {
            return back()->withErrors([
                'employee' => 'Your account is not linked to an employee record.'
            ]);
        }

        $now = Carbon::now('Asia/Manila');
        $today = $now->toDateString();
        $action = $data['action'];

        $assignment = EmployeeScheduleAssignment::with('schedule')
            ->where('employee_id', $employee->id)
            ->where('effective_from', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $today);
            })
            ->latest('effective_from')
            ->first();

        $schedule = $assignment ? $assignment->schedule : null;

        if (!$schedule) {
            return back()->withErrors([
                'schedule' => 'No schedule assigned for today. Attendance punch is disabled.',
            ]);
        }

        $dt = function (?string $time) use ($today) {
            if (!$time) {
                return null;
            }

            return Carbon::parse($today . ' ' . $time, 'Asia/Manila');
        };

        $scheduleStart = $dt($schedule->start_time);
        $scheduleEnd   = $dt($schedule->end_time);

        if (!$scheduleStart || !$scheduleEnd) {
            return back()->withErrors([
                'schedule' => 'Today’s schedule is missing start/end time. Please contact HR/Admin.',
            ]);
        }

        $log = AttendanceLog::firstOrCreate(
            [
                'employee_id' => $employee->id,
                'work_date'   => $today,
            ],
            []
        );

        if (!is_null($log->{$action})) {
            return back()->withErrors([
                'attendance' => strtoupper(str_replace('_', ' ', $action)) . ' already recorded.',
            ]);
        }

        if ($action === 'time_in') {
            $earliest = $scheduleStart->copy()->subMinutes(10);

            if ($now->lt($earliest)) {
                return back()->withErrors([
                    'time_in' => 'You can only Time In starting ' . $earliest->format('h:i A') . ' (10 mins before start).',
                ]);
            }
        }

        if (in_array($action, ['break_out', 'break_in'], true)) {
            if (empty($schedule->break_start) || empty($schedule->break_end)) {
                return back()->withErrors([
                    'break' => 'Break time is not configured for today’s schedule.',
                ]);
            }
        }

        if (in_array($action, ['lunch_out', 'lunch_in'], true)) {
            if (empty($schedule->lunch_start) || empty($schedule->lunch_end)) {
                return back()->withErrors([
                    'lunch' => 'Lunch time is not configured for today’s schedule.',
                ]);
            }
        }

        $rules = [
            'break_out' => 'time_in',
            'break_in'  => 'break_out',
            'lunch_out' => 'time_in',
            'lunch_in'  => 'lunch_out',
            'time_out'  => 'time_in',
        ];

        if (isset($rules[$action]) && is_null($log->{$rules[$action]})) {
            return back()->withErrors([
                'attendance' => 'You must do ' . strtoupper(str_replace('_', ' ', $rules[$action])) . ' first.',
            ]);
        }

        if ($log->time_out) {
            return back()->withErrors([
                'attendance' => 'You already Time Out today. No more punches allowed.',
            ]);
        }

        $log->{$action} = $now;

        // Save location per action
        $log->{$action . '_latitude'} = $data['latitude'] ?? null;
        $log->{$action . '_longitude'} = $data['longitude'] ?? null;
        $log->{$action . '_accuracy'} = $data['accuracy'] ?? null;
        $log->{$action . '_ip_address'} = $request->ip();

        $log->save();

        $this->recomputeComputedFields($log, $employee);

        return back()->with(
            'success',
            strtoupper(str_replace('_', ' ', $action)) . ' recorded at ' . $now->format('h:i A')
        );
    }

    /**
     * Recompute minutes_late, minutes_worked, minutes_undertime based on schedule + punches.
     */
    protected function recomputeComputedFields(AttendanceLog $log, Employee $employee): void
    {
        $workDate = Carbon::parse($log->work_date)->toDateString();

        $assignment = EmployeeScheduleAssignment::with('schedule')
            ->where('employee_id', $employee->id)
            ->where('effective_from', '<=', $workDate)
            ->where(function ($q) use ($workDate) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $workDate);
            })
            ->latest('effective_from')
            ->first();

        $schedule = $assignment ? $assignment->schedule : null;

        if (!$schedule) {
            $log->minutes_late = 0;
            $log->minutes_worked = 0;
            $log->minutes_undertime = 0;
            $log->save();
            return;
        }

        $dt = function (?string $time) use ($workDate) {
            if (!$time) {
                return null;
            }

            return Carbon::parse($workDate . ' ' . $time, 'Asia/Manila');
        };

        $scheduleStart = $dt($schedule->start_time);
        $scheduleEnd   = $dt($schedule->end_time);

        $requiredMinutes = (int) round(((float) ($employee->work_hours_per_day ?? 8)) * 60);

        // minutes_late
        $minutesLate = 0;
        if ($log->time_in && $scheduleStart) {
            $timeIn = $log->time_in instanceof Carbon
                ? $log->time_in
                : Carbon::parse($log->time_in, 'Asia/Manila');

            if ($timeIn->gt($scheduleStart)) {
                $minutesLate = $scheduleStart->diffInMinutes($timeIn);
            }
        }

        // minutes_worked
        $minutesWorked = 0;

        if ($log->time_in) {
            $timeIn = $log->time_in instanceof Carbon
                ? $log->time_in
                : Carbon::parse($log->time_in, 'Asia/Manila');

            $timeOut = $log->time_out
                ? ($log->time_out instanceof Carbon
                    ? $log->time_out
                    : Carbon::parse($log->time_out, 'Asia/Manila'))
                : Carbon::now('Asia/Manila');

            if ($timeOut->lt($timeIn)) {
                $timeOut = $timeIn;
            }

            $minutesWorked = $timeIn->diffInMinutes($timeOut);

            if ($log->break_out && $log->break_in) {
                $breakOut = $log->break_out instanceof Carbon
                    ? $log->break_out
                    : Carbon::parse($log->break_out, 'Asia/Manila');

                $breakIn = $log->break_in instanceof Carbon
                    ? $log->break_in
                    : Carbon::parse($log->break_in, 'Asia/Manila');

                if ($breakIn->gt($breakOut)) {
                    $minutesWorked -= $breakOut->diffInMinutes($breakIn);
                }
            }

            if ($log->lunch_out && $log->lunch_in) {
                $lunchOut = $log->lunch_out instanceof Carbon
                    ? $log->lunch_out
                    : Carbon::parse($log->lunch_out, 'Asia/Manila');

                $lunchIn = $log->lunch_in instanceof Carbon
                    ? $log->lunch_in
                    : Carbon::parse($log->lunch_in, 'Asia/Manila');

                if ($lunchIn->gt($lunchOut)) {
                    $minutesWorked -= $lunchOut->diffInMinutes($lunchIn);
                }
            }

            if ($minutesWorked < 0) {
                $minutesWorked = 0;
            }
        }

        // minutes_undertime
        $minutesUndertime = 0;
        if ($log->time_out) {
            $minutesUndertime = max(0, $requiredMinutes - $minutesWorked);
        }

        $log->minutes_late = (int) $minutesLate;
        $log->minutes_worked = (int) $minutesWorked;
        $log->minutes_undertime = (int) $minutesUndertime;

        $log->save();
    }
}