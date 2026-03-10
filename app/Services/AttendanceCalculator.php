<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\AttendanceLog;
use App\Models\Employee;

class AttendanceCalculator
{
    public function compute(AttendanceLog $log): array
    {
        $employee = $log->employee()->first(); // or $log->employee if relationship exists
        if (!$employee) {
            return [
                'minutes_worked' => 0,
                'minutes_late' => 0,
                'minutes_undertime' => 0,
                'is_absent' => true,
                'status' => 'absent',
            ];
        }

        // If no time_in and no time_out, treat absent (you can adjust for leave/holiday later)
        if (!$log->time_in && !$log->time_out) {
            return [
                'minutes_worked' => 0,
                'minutes_late' => 0,
                'minutes_undertime' => 0,
                'is_absent' => true,
                'status' => 'absent',
            ];
        }

        $workDate = Carbon::parse($log->work_date)->startOfDay();

        $shiftStart = Carbon::parse($log->work_date.' '.$employee->shift_start);
        $shiftEnd   = Carbon::parse($log->work_date.' '.$employee->shift_end);

        // Late
        $minutesLate = 0;
        if ($log->time_in) {
            $timeIn = Carbon::parse($log->time_in);
            if ($timeIn->greaterThan($shiftStart)) {
                $minutesLate = $shiftStart->diffInMinutes($timeIn);
            }
        }

        // Undertime
        $minutesUndertime = 0;
        if ($log->time_out) {
            $timeOut = Carbon::parse($log->time_out);
            if ($timeOut->lessThan($shiftEnd)) {
                $minutesUndertime = $timeOut->diffInMinutes($shiftEnd);
            }
        }

        // Worked minutes (sum segments)
        $minutesWorked = 0;

        $segments = [
            [$log->time_in, $log->break_out],
            [$log->break_in, $log->lunch_out],
            [$log->lunch_in, $log->time_out],
        ];

        $hasAnySegment = false;
        foreach ($segments as [$start, $end]) {
            if ($start && $end) {
                $hasAnySegment = true;
                $s = Carbon::parse($start);
                $e = Carbon::parse($end);
                if ($e->greaterThan($s)) {
                    $minutesWorked += $s->diffInMinutes($e);
                }
            }
        }

        // Fallback: if segments incomplete but has time_in and time_out
        if (!$hasAnySegment && $log->time_in && $log->time_out) {
            $timeIn = Carbon::parse($log->time_in);
            $timeOut = Carbon::parse($log->time_out);

            if ($timeOut->greaterThan($timeIn)) {
                $minutesWorked = $timeIn->diffInMinutes($timeOut);

                // subtract breaks if present
                if ($log->break_out && $log->break_in) {
                    $minutesWorked -= Carbon::parse($log->break_out)->diffInMinutes(Carbon::parse($log->break_in));
                }
                if ($log->lunch_out && $log->lunch_in) {
                    $minutesWorked -= Carbon::parse($log->lunch_out)->diffInMinutes(Carbon::parse($log->lunch_in));
                }
                $minutesWorked = max(0, $minutesWorked);
            }
        }

        return [
            'minutes_worked' => max(0, (int)$minutesWorked),
            'minutes_late' => (int)$minutesLate,
            'minutes_undertime' => (int)$minutesUndertime,
            'is_absent' => false,
            'status' => 'present',
        ];
    }
}