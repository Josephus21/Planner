<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Employee;
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

            $daysPresent = $logs->where('status', 'present')->count();
            $minutesLate = (int) $logs->sum('minutes_late');
            $minutesWorked = (int) $logs->sum('minutes_worked');
            $minutesUndertime = (int) $logs->sum('minutes_undertime');

            $salary = (float) $employee->salary;

            $dailyRate = $this->dailyRate($employee, $salary);

            $workHoursPerDay = (float) ($employee->work_hours_per_day ?: 8);
            if ($workHoursPerDay <= 0) {
                $workHoursPerDay = 8;
            }

            $hourlyRate = $dailyRate / $workHoursPerDay;
            $perMinuteRate = $hourlyRate / 60;

            // Gross pay based on attendance
            $gross = $dailyRate * $daysPresent;

            // Attendance-based deductions
            $lateDeduction = $this->calculateLateDeduction($logs, $dailyRate, $hourlyRate);
            $undertimeDeduction = $minutesUndertime * $perMinuteRate;

            // Employee deductions
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

                // Only PhilHealth should be percent
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
                    'name' => $dt->name,
                    'amount' => $deductionAmount,
                ];
            }

            $totalDeductions = round(
                $lateDeduction + $undertimeDeduction + $employeeDeductionTotal,
                2
            );

            $net = round($gross - $totalDeductions, 2);

            $payroll = Payroll::updateOrCreate(
                [
                    'payroll_period_id' => $period->id,
                    'employee_id' => $employee->id,
                ],
                [
                    'gross_pay' => round($gross, 2),
                    'total_deductions' => $totalDeductions,
                    'net_pay' => $net,
                    'days_present' => $daysPresent,
                    'minutes_late' => $minutesLate,
                    'minutes_worked' => $minutesWorked,
                ]
            );

            PayrollItem::where('payroll_id', $payroll->id)->delete();

            PayrollItem::create([
                'payroll_id' => $payroll->id,
                'type' => 'earning',
                'name' => 'Base pay',
                'amount' => round($gross, 2),
            ]);

            if ($lateDeduction > 0) {
                PayrollItem::create([
                    'payroll_id' => $payroll->id,
                    'type' => 'deduction',
                    'name' => 'Late deduction',
                    'amount' => round($lateDeduction, 2),
                ]);
            }

            if ($undertimeDeduction > 0) {
                PayrollItem::create([
                    'payroll_id' => $payroll->id,
                    'type' => 'deduction',
                    'name' => 'Undertime deduction',
                    'amount' => round($undertimeDeduction, 2),
                ]);
            }

            foreach ($deductionBreakdown as $item) {
                PayrollItem::create([
                    'payroll_id' => $payroll->id,
                    'type' => 'deduction',
                    'name' => $item['name'],
                    'amount' => $item['amount'],
                ]);
            }
        }
    }

    private function calculateLateDeduction($logs, float $dailyRate, float $hourlyRate): float
    {
        $lateDeduction = 0;

        foreach ($logs as $log) {
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
            // Optional: keep this if you want a fallback for > 60 minutes
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
            return $salary * (float) $employee->work_hours_per_day;
        }

        return $salary / max((int) $employee->work_days_per_month, 1);
    }
}