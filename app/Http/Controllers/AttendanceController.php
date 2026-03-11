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
        $companyId = (int) $user->company_id;

        if (!$companyId) {
            return back()->withErrors([
                'company' => 'Your account is not assigned to a company.',
            ]);
        }

        $employee = $user->employee_id
            ? Employee::forCompany($companyId)->find((int) $user->employee_id)
            : null;

        if (!$employee) {
            return back()->withErrors([
                'employee' => 'Your account is not linked to a valid employee record for your company.',
            ]);
        }

        $now = Carbon::now('Asia/Manila');
        $today = $now->toDateString();
        $action = $data['action'];

        $assignment = EmployeeScheduleAssignment::with([
                'schedule' => fn ($q) => $q->forCompany($companyId)
            ])
            ->forCompany($companyId)
            ->where('employee_id', $employee->id)
            ->where('effective_from', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $today);
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
                'company_id'  => $companyId,
                'employee_id' => $employee->id,
                'work_date'   => $today,
            ],
            [
                'company_id' => $companyId,
            ]
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

        $latitude = isset($data['latitude']) ? (float) $data['latitude'] : null;
        $longitude = isset($data['longitude']) ? (float) $data['longitude'] : null;
        $accuracy = isset($data['accuracy']) ? (float) $data['accuracy'] : null;

        $locationName = null;
        if (!is_null($latitude) && !is_null($longitude)) {
            $locationName = $this->getLocationName($latitude, $longitude);
        }

        $log->{$action} = $now;
        $log->{$action . '_latitude'} = $latitude;
        $log->{$action . '_longitude'} = $longitude;
        $log->{$action . '_accuracy'} = $accuracy;
        $log->{$action . '_location'} = $locationName;
        $log->{$action . '_ip_address'} = $request->ip();

        $log->save();

        $this->recomputeComputedFields($log, $employee, $companyId);

        return back()->with(
            'success',
            strtoupper(str_replace('_', ' ', $action)) . ' recorded at ' . $now->format('h:i A')
            . ($locationName ? ' - Location: ' . $locationName : '')
        );
    }

    private function getLocationName(float $lat, float $lng): ?string
    {
        try {
            $url = "https://nominatim.openstreetmap.org/reverse?lat={$lat}&lon={$lng}&format=json&addressdetails=1";

            $opts = [
                "http" => [
                    "method" => "GET",
                    "header" => "User-Agent: AttendanceSystem/1.0\r\n"
                ]
            ];

            $context = stream_context_create($opts);
            $response = @file_get_contents($url, false, $context);

            if ($response === false) {
                return null;
            }

            $data = json_decode($response, true);

            if (!is_array($data) || !isset($data['address'])) {
                return null;
            }

            $address = $data['address'];

            $city =
                $address['city'] ??
                $address['town'] ??
                $address['municipality'] ??
                $address['village'] ??
                $address['county'] ??
                null;

            $state = $address['state'] ?? null;

            if ($city && $state) {
                return $city . ', ' . $state;
            }

            if ($city) {
                return $city;
            }

            return $data['display_name'] ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function recomputeComputedFields(AttendanceLog $log, Employee $employee, int $companyId): void
    {
        $workDate = Carbon::parse($log->work_date)->toDateString();

        $assignment = EmployeeScheduleAssignment::with([
                'schedule' => fn ($q) => $q->forCompany($companyId)
            ])
            ->forCompany($companyId)
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
        $requiredMinutes = (int) round(((float) ($employee->work_hours_per_day ?? 8)) * 60);

        $minutesLate = 0;
        if ($log->time_in && $scheduleStart) {
            $timeIn = $log->time_in instanceof Carbon
                ? $log->time_in
                : Carbon::parse($log->time_in, 'Asia/Manila');

            if ($timeIn->gt($scheduleStart)) {
                $minutesLate = $scheduleStart->diffInMinutes($timeIn);
            }
        }

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