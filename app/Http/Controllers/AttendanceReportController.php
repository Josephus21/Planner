<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AttendanceReportController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $companyId = (int) $user->company_id;
        $myEmployeeId = $user->employee_id ? (int) $user->employee_id : null;

        if (!$companyId) {
            return back()->withErrors([
                'company' => 'Your account is not assigned to a company.',
            ]);
        }

        if (!$myEmployeeId) {
            return back()->withErrors([
                'employee' => 'Your account is not linked to an employee record.',
            ]);
        }

        // Make sure the employee belongs to the same company
        $myEmployeeExists = DB::table('employees')
            ->where('id', $myEmployeeId)
            ->where('company_id', $companyId)
            ->exists();

        if (!$myEmployeeExists) {
            return back()->withErrors([
                'employee' => 'Your employee record does not belong to your assigned company.',
            ]);
        }

        /**
         * employees.role_id -> roles.title
         * Restrict employee lookup to the current company
         */
        $roleTitle = DB::table('employees as e')
            ->leftJoin('roles as r', 'r.id', '=', 'e.role_id')
            ->where('e.id', $myEmployeeId)
            ->where('e.company_id', $companyId)
            ->value('r.title');

        $roleNorm = Str::lower(trim((string) $roleTitle));

        // Developer can view all employees, but only within their own company
        $canViewAll = ($roleNorm === 'developer');

        // period: daily | weekly | monthly
        $period = $request->get('period', 'daily');
        $date   = $request->get('date', Carbon::today('Asia/Manila')->toDateString());

        $base = Carbon::parse($date, 'Asia/Manila');

        if ($period === 'weekly') {
            $start = $base->copy()->startOfWeek(Carbon::MONDAY);
            $end   = $base->copy()->endOfWeek(Carbon::SUNDAY);
        } elseif ($period === 'monthly') {
            $start = $base->copy()->startOfMonth();
            $end   = $base->copy()->endOfMonth();
        } else {
            $start = $base->copy()->startOfDay();
            $end   = $base->copy()->endOfDay();
        }

        $lateGraceMinutes = (int) $request->get('late_grace', 0);
        $overbreakGrace   = (int) $request->get('overbreak_grace', 0);
        $overlunchGrace   = (int) $request->get('overlunch_grace', 0);

        $query = DB::table('attendance_logs as al')
            ->join('employees as e', function ($join) use ($companyId) {
                $join->on('e.id', '=', 'al.employee_id')
                    ->where('e.company_id', '=', $companyId);
            })
            ->leftJoin('employee_schedule_assignments as esa', function ($join) use ($companyId) {
                $join->on('esa.employee_id', '=', 'al.employee_id')
                    ->where('esa.company_id', '=', $companyId)
                    ->whereRaw('esa.effective_from <= al.work_date')
                    ->whereRaw('(esa.effective_to is null OR esa.effective_to >= al.work_date)');
            })
            ->leftJoin('schedules as s', function ($join) use ($companyId) {
                $join->on('s.id', '=', 'esa.schedule_id')
                    ->where('s.company_id', '=', $companyId);
            })
            ->where('al.company_id', $companyId)
            ->whereBetween('al.work_date', [$start->toDateString(), $end->toDateString()]);

        // Non-developers only see their own logs
        if (!$canViewAll) {
            $query->where('al.employee_id', '=', $myEmployeeId);
        }

        $rows = $query
            ->select([
                'al.id',
                'al.work_date',
                'e.id as employee_id',
                'e.fullname',
                's.name as schedule_name',

                'al.time_in',
                'al.time_in_location',

                'al.break_out',
                'al.break_in',

                'al.lunch_out',
                'al.lunch_in',

                'al.time_out',
                'al.time_out_location',

                's.start_time',
                's.break_start',
                's.break_end',
                's.lunch_start',
                's.lunch_end',
                's.end_time',

                DB::raw("
                    CASE
                      WHEN al.time_in is null OR s.start_time is null THEN null
                      ELSE TIMESTAMPDIFF(MINUTE, CONCAT(al.work_date,' ', s.start_time), al.time_in)
                    END as late_minutes
                "),

                DB::raw("
                    CASE
                      WHEN al.break_out is null OR al.break_in is null THEN null
                      ELSE TIMESTAMPDIFF(MINUTE, al.break_out, al.break_in)
                    END as break_minutes
                "),

                DB::raw("
                    CASE
                      WHEN s.break_start is null OR s.break_end is null THEN null
                      ELSE TIMESTAMPDIFF(MINUTE, CONCAT(al.work_date,' ', s.break_start), CONCAT(al.work_date,' ', s.break_end))
                    END as sched_break_minutes
                "),

                DB::raw("
                    CASE
                      WHEN al.lunch_out is null OR al.lunch_in is null THEN null
                      ELSE TIMESTAMPDIFF(MINUTE, al.lunch_out, al.lunch_in)
                    END as lunch_minutes
                "),

                DB::raw("
                    CASE
                      WHEN s.lunch_start is null OR s.lunch_end is null THEN null
                      ELSE TIMESTAMPDIFF(MINUTE, CONCAT(al.work_date,' ', s.lunch_start), CONCAT(al.work_date,' ', s.lunch_end))
                    END as sched_lunch_minutes
                "),

                DB::raw("
                    CASE
                      WHEN al.time_out is null OR s.end_time is null THEN null
                      ELSE TIMESTAMPDIFF(MINUTE, al.time_out, CONCAT(al.work_date,' ', s.end_time))
                    END as undertime_minutes
                "),
            ])
            ->orderBy('al.work_date')
            ->orderBy('e.fullname')
            ->get();

        $report = $rows->map(function ($r) use ($lateGraceMinutes, $overbreakGrace, $overlunchGrace) {
            $lateMin = is_null($r->late_minutes) ? null : (int) $r->late_minutes;
            $breakMin = is_null($r->break_minutes) ? null : (int) $r->break_minutes;
            $schedBreak = is_null($r->sched_break_minutes) ? null : (int) $r->sched_break_minutes;
            $lunchMin = is_null($r->lunch_minutes) ? null : (int) $r->lunch_minutes;
            $schedLunch = is_null($r->sched_lunch_minutes) ? null : (int) $r->sched_lunch_minutes;
            $undertime = is_null($r->undertime_minutes) ? null : (int) $r->undertime_minutes;

            $isLate = (!is_null($lateMin) && $lateMin > $lateGraceMinutes);
            $overBreak = (!is_null($breakMin) && !is_null($schedBreak) && $breakMin > ($schedBreak + $overbreakGrace));
            $overLunch = (!is_null($lunchMin) && !is_null($schedLunch) && $lunchMin > ($schedLunch + $overlunchGrace));
            $isUndertime = (!is_null($undertime) && $undertime > 0);

            $missing = [];
            if (is_null($r->time_in)) $missing[] = 'TIME IN';
            if (is_null($r->time_out)) $missing[] = 'TIME OUT';

            return (object) array_merge((array) $r, [
                'is_late' => $isLate,
                'over_break' => $overBreak,
                'over_lunch' => $overLunch,
                'is_undertime' => $isUndertime,
                'missing' => $missing,
            ]);
        });

        $summary = [
            'total_logs' => $report->count(),
            'late' => $report->where('is_late', true)->count(),
            'overbreak' => $report->where('over_break', true)->count(),
            'overlunch' => $report->where('over_lunch', true)->count(),
            'undertime' => $report->where('is_undertime', true)->count(),
            'missing_punch' => $report->filter(fn ($r) => count($r->missing) > 0)->count(),
        ];

        return view('attendance_reports.index', [
            'period' => $period,
            'date' => $base->toDateString(),
            'start' => $start,
            'end' => $end,
            'lateGraceMinutes' => $lateGraceMinutes,
            'overbreakGrace' => $overbreakGrace,
            'overlunchGrace' => $overlunchGrace,
            'summary' => $summary,
            'report' => $report,
            'canViewAll' => $canViewAll,
            'roleTitle' => $roleTitle,
        ]);
    }
}