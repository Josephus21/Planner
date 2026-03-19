<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;

class PayrollGenerator
{
    public function generate(PayrollPeriod $period): void
    {
        $from = Carbon::parse($period->date_from)->startOfDay();
        $to   = Carbon::parse($period->date_to)->endOfDay();

        $employees = Employee::where('status', 'active')->get();

        foreach ($employees as $employee) {
            $logs = $employee->attendanceLogs()
                ->whereBetween('work_date', [$from->toDateString(), $to->toDateString()])
                ->get();

            /**
             * Only COMPLETE present logs should count in payroll attendance figures.
             */
            $completePresentLogs = $logs->filter(function ($log) {
                return $this->isCompletePresentLog($log);
            });

            $daysPresent      = $completePresentLogs->count();
            $minutesLate      = (int) $completePresentLogs->sum('minutes_late');
            $minutesWorked    = (int) $completePresentLogs->sum('minutes_worked');
            $minutesUndertime = (int) $completePresentLogs->sum('minutes_undertime');

            $salary = (float) ($employee->salary ?? 0);

            $dailyRate = $this->dailyRate($employee, $salary);

            $workHoursPerDay = (float) ($employee->work_hours_per_day ?: 8);
            if ($workHoursPerDay <= 0) {
                $workHoursPerDay = 8;
            }

            $hourlyRate    = $dailyRate / $workHoursPerDay;
            $perMinuteRate = $hourlyRate / 60;

            /**
             * BASE PAY
             * Only count ordinary COMPLETE present days here.
             * Holiday / rest day earnings are added separately to avoid double counting.
             */
            $normalPresentDays = $logs->filter(function ($log) use ($employee) {
                if (!$this->isCompletePresentLog($log)) {
                    return false;
                }

                $holiday = $this->getHolidayForDate($employee, $log->work_date);

                $isRegularHoliday = $holiday && strtolower((string) $holiday->type) === 'regular';
                $isSpecialHoliday = $holiday && strtolower((string) $holiday->type) === 'special';
                $isRestDay        = $this->isRestDay($employee, $log->work_date);

                return !$isRegularHoliday && !$isSpecialHoliday && !$isRestDay;
            })->count();

            /**
             * ONLY VACATION LEAVE IS PAID
             * This assumes approved vacation leave logs are saved as:
             * status = 'leave'
             * leave_type = 'Vacation'
             * is_paid = true
             */
            $paidVacationLeaveDays = $logs->filter(function ($log) {
                return ($log->status ?? null) === 'leave'
                    && strtolower((string) ($log->leave_type ?? '')) === 'vacation'
                    && (bool) ($log->is_paid ?? false) === true;
            })->count();

            $basePay = round($dailyRate * $normalPresentDays, 2);
            $paidVacationLeavePay = round($dailyRate * $paidVacationLeaveDays, 2);

            /**
             * HOLIDAY / REST DAY EARNINGS
             */
            $earningBreakdown = $this->calculateHolidayPremiums(
                $logs,
                $employee,
                $dailyRate,
                $hourlyRate
            );

            $holidayPremiumTotal = round(collect($earningBreakdown)->sum('amount'), 2);

            /**
             * GROSS
             */
            $gross = round($basePay + $paidVacationLeavePay + $holidayPremiumTotal, 2);

            /**
             * ATTENDANCE-BASED DEDUCTIONS
             * Only COMPLETE present logs should affect late/undertime.
             */
            $lateDeduction      = $this->calculateLateDeduction($completePresentLogs, $dailyRate, $hourlyRate);
            $undertimeDeduction = round($minutesUndertime * $perMinuteRate, 2);

            /**
             * EMPLOYEE DEDUCTIONS
             */
            $employeeDeductions = $employee->deductions()
                ->with('type')
                ->where('is_active', 1)
                ->get();

            $employeeDeductionTotal = 0;
            $deductionBreakdown = [];

            foreach ($employeeDeductions as $ed) {
                $dt = $ed->type;

                if (!$dt) {
                    continue;
                }

                $value = (float) ($ed->amount ?? 0);

                if ($value <= 0) {
                    continue;
                }

                $deductionAmount = 0;

                if ($dt->method === 'percent') {
                    $deductionAmount = ($value / 100) * $gross;
                } else {
                    $deductionAmount = $value;
                }

                // Split monthly deductions for semi-monthly payroll
                if ($dt->frequency === 'monthly') {
                    $daysInPeriod = Carbon::parse($period->date_from)
                        ->diffInDays(Carbon::parse($period->date_to)) + 1;

                    if ($daysInPeriod <= 16) {
                        $deductionAmount = $deductionAmount / 2;
                    }
                }

                $deductionAmount = round($deductionAmount, 2);

                $employeeDeductionTotal += $deductionAmount;

                $deductionBreakdown[] = [
                    'name'   => $dt->name,
                    'amount' => $deductionAmount,
                ];
            }

            $totalDeductions = round(
                $lateDeduction + $undertimeDeduction + $employeeDeductionTotal,
                2
            );

            $net = round($gross - $totalDeductions, 2);

            /**
             * SAVE PAYROLL
             */
            $payroll = Payroll::updateOrCreate(
                [
                    'payroll_period_id' => $period->id,
                    'employee_id'       => $employee->id,
                ],
                [
                    'gross_pay'         => $gross,
                    'total_deductions'  => $totalDeductions,
                    'net_pay'           => $net,
                    'days_present'      => $daysPresent,
                    'minutes_late'      => $minutesLate,
                    'minutes_worked'    => $minutesWorked,
                ]
            );

            PayrollItem::where('payroll_id', $payroll->id)->delete();

            /**
             * EARNINGS
             */
            if ($basePay > 0) {
                PayrollItem::create([
                    'payroll_id' => $payroll->id,
                    'type'       => 'earning',
                    'name'       => 'Base pay',
                    'amount'     => $basePay,
                ]);
            }

            if ($paidVacationLeavePay > 0) {
                PayrollItem::create([
                    'payroll_id' => $payroll->id,
                    'type'       => 'earning',
                    'name'       => 'Paid Vacation Leave',
                    'amount'     => $paidVacationLeavePay,
                ]);
            }

            foreach ($earningBreakdown as $item) {
                PayrollItem::create([
                    'payroll_id' => $payroll->id,
                    'type'       => 'earning',
                    'name'       => $item['name'],
                    'amount'     => $item['amount'],
                ]);
            }

            /**
             * DEDUCTIONS
             */
            if ($lateDeduction > 0) {
                PayrollItem::create([
                    'payroll_id' => $payroll->id,
                    'type'       => 'deduction',
                    'name'       => 'Late deduction',
                    'amount'     => round($lateDeduction, 2),
                ]);
            }

            if ($undertimeDeduction > 0) {
                PayrollItem::create([
                    'payroll_id' => $payroll->id,
                    'type'       => 'deduction',
                    'name'       => 'Undertime deduction',
                    'amount'     => $undertimeDeduction,
                ]);
            }

            foreach ($deductionBreakdown as $item) {
                PayrollItem::create([
                    'payroll_id' => $payroll->id,
                    'type'       => 'deduction',
                    'name'       => $item['name'],
                    'amount'     => $item['amount'],
                ]);
            }
        }
    }

    /**
     * A present log is considered complete only if:
     * - status = present
     * - time_in exists
     * - time_out exists
     * - minutes_worked > 0
     */
    private function isCompletePresentLog($log): bool
    {
        return ($log->status ?? null) === 'present'
            && !empty($log->time_in)
            && !empty($log->time_out)
            && (int) ($log->minutes_worked ?? 0) > 0;
    }

    /**
     * Compute holiday/rest day pay and OT.
     *
     * Rules:
     * - Regular working day:
     *   - First 8 hrs = already included in base pay (100%)
     *   - Beyond 8 hrs = 125%
     *
     * - Special holiday OR rest day:
     *   - Daily: first 8 hrs = 130%
     *   - Monthly: first 8 hrs = 30% premium only
     *   - Beyond 8 hrs: 169% for both daily/monthly
     *
     * - Regular holiday:
     *   - Daily: first 8 hrs = 200%
     *   - Monthly: first 8 hrs = 100% premium only
     *   - Beyond 8 hrs: 260% for both daily/monthly
     */
    private function calculateHolidayPremiums($logs, Employee $employee, float $dailyRate, float $hourlyRate): array
    {
        $items = [];

        foreach ($logs as $log) {
            if (!$this->isCompletePresentLog($log)) {
                continue;
            }

            $minutesWorked = (int) ($log->minutes_worked ?? 0);
            if ($minutesWorked <= 0) {
                continue;
            }

            $workedHours   = $minutesWorked / 60;
            $regularHours  = min($workedHours, 8);
            $overtimeHours = max($workedHours - 8, 0);

            $holiday = $this->getHolidayForDate($employee, $log->work_date);

            $isRegularHoliday = $holiday && strtolower((string) $holiday->type) === 'regular';
            $isSpecialHoliday = $holiday && strtolower((string) $holiday->type) === 'special';
            $isRestDay        = $this->isRestDay($employee, $log->work_date);

            /**
             * REGULAR HOLIDAY
             */
            if ($isRegularHoliday) {
                if ($regularHours > 0) {
                    $amount = $employee->salary_type === 'daily'
                        ? ($regularHours * $hourlyRate * 2.00)
                        : ($regularHours * $hourlyRate * 1.00);

                    $items[] = [
                        'name'   => 'Regular holiday pay (' . Carbon::parse($log->work_date)->format('M d, Y') . ')',
                        'amount' => round($amount, 2),
                    ];
                }

                if ($overtimeHours > 0) {
                    $items[] = [
                        'name'   => 'Regular holiday OT (' . Carbon::parse($log->work_date)->format('M d, Y') . ')',
                        'amount' => round($overtimeHours * $hourlyRate * 2.60, 2),
                    ];
                }

                continue;
            }

            /**
             * SPECIAL HOLIDAY OR REST DAY
             */
            if ($isSpecialHoliday || $isRestDay) {
                if ($regularHours > 0) {
                    $amount = $employee->salary_type === 'daily'
                        ? ($regularHours * $hourlyRate * 1.30)
                        : ($regularHours * $hourlyRate * 0.30);

                    $label = $isSpecialHoliday && $isRestDay
                        ? 'Special holiday / Rest day pay'
                        : ($isSpecialHoliday ? 'Special holiday pay' : 'Rest day pay');

                    $items[] = [
                        'name'   => $label . ' (' . Carbon::parse($log->work_date)->format('M d, Y') . ')',
                        'amount' => round($amount, 2),
                    ];
                }

                if ($overtimeHours > 0) {
                    $label = $isSpecialHoliday && $isRestDay
                        ? 'Special holiday / Rest day OT'
                        : ($isSpecialHoliday ? 'Special holiday OT' : 'Rest day OT');

                    $items[] = [
                        'name'   => $label . ' (' . Carbon::parse($log->work_date)->format('M d, Y') . ')',
                        'amount' => round($overtimeHours * $hourlyRate * 1.69, 2),
                    ];
                }

                continue;
            }

            /**
             * ORDINARY WORKING DAY
             * First 8 hours already covered by base pay.
             * Only add OT beyond 8 hours at 125%.
             */
            if ($overtimeHours > 0) {
                $items[] = [
                    'name'   => 'Regular day OT (' . Carbon::parse($log->work_date)->format('M d, Y') . ')',
                    'amount' => round($overtimeHours * $hourlyRate * 1.25, 2),
                ];
            }
        }

        return $items;
    }

    /**
     * Existing holidays table lookup.
     * Supports:
     * - global holiday (company_id is null)
     * - company-specific holiday
     */
    private function getHolidayForDate(Employee $employee, $workDate): ?Holiday
    {
        return Holiday::query()
            ->whereDate('holiday_date', $workDate)
            ->where('is_active', 1)
            ->where(function ($q) use ($employee) {
                $q->whereNull('company_id')
                  ->orWhere('company_id', $employee->company_id);
            })
            ->first();
    }

    /**
     * Uses employee rest day relationship.
     */
    private function isRestDay(Employee $employee, $workDate): bool
    {
        $dayName = strtolower(Carbon::parse($workDate)->format('l'));

        return $employee->restDays()
            ->where('is_active', 1)
            ->where('day_name', $dayName)
            ->exists();
    }

    private function calculateLateDeduction($logs, float $dailyRate, float $hourlyRate): float
    {
        $lateDeduction = 0;

        foreach ($logs as $log) {
            if (!$this->isCompletePresentLog($log)) {
                continue;
            }

            $minutesLate = (float) ($log->minutes_late ?? 0);

            if ($minutesLate <= 0) {
                continue;
            }

            // 1 second to 30 minutes = 1 hour deduction
            if ($minutesLate > 0 && $minutesLate <= 30) {
                $lateDeduction += $hourlyRate;
            }
            // More than 30 minutes up to 1 hour = half-day deduction
            elseif ($minutesLate > 30 && $minutesLate <= 60) {
                $lateDeduction += ($dailyRate / 2);
            }
            // More than 1 hour = full-day deduction
            else {
                $lateDeduction += $dailyRate;
            }
        }

        return round($lateDeduction, 2);
    }

    private function dailyRate(Employee $employee, float $salary): float
    {
        if ($employee->salary_type === 'daily') {
            return $salary;
        }

        if ($employee->salary_type === 'hourly') {
            return $salary * (float) ($employee->work_hours_per_day ?: 8);
        }

        return $salary / max((int) ($employee->work_days_per_month ?: 1), 1);
    }
}