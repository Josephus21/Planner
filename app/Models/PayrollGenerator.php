<?php

namespace App\Services;

use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\Employee;
use App\Models\AttendanceLog;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PayrollGenerator
{
    /**
     * Generate / recompute payroll for a given period
     */
    public function generate(PayrollPeriod $period): void
    {
        DB::transaction(function () use ($period) {

            // Only active employees are computed
            $employees = Employee::where('status', 'active')->get();

            foreach ($employees as $employee) {
                $this->computeEmployee($period, $employee);
            }
        });
    }

    /**
     * Compute payroll for one employee in one period
     */
    protected function computeEmployee(PayrollPeriod $period, Employee $employee): void
    {
        $dateFrom = Carbon::parse($period->date_from)->startOfDay();
        $dateTo   = Carbon::parse($period->date_to)->endOfDay();

        // Pull attendance logs inside range
        $logs = AttendanceLog::where('employee_id', $employee->id)
            ->whereBetween('work_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->get();

        // Summaries
        $daysPresent = 0;
        $minutesLate = 0;
        $minutesWorked = 0;
        $minutesUndertime = 0;

        foreach ($logs as $log) {
            // If you have an is_absent column
            $isAbsent = (bool) ($log->is_absent ?? false);

            if (!$isAbsent) {
                $daysPresent++;
            }

            $minutesLate += (int) ($log->minutes_late ?? 0);
            $minutesWorked += (int) ($log->minutes_worked ?? 0);
            $minutesUndertime += (int) ($log->minutes_undertime ?? 0);
        }

        // Salary base
        $salaryType = $employee->salary_type ?? 'monthly';

        // Prefer salary_amount if you have it, else fallback to salary
        $salaryAmount = (float) ($employee->salary_amount ?? $employee->salary ?? 0);

        $workDaysPerMonth = (int) ($employee->work_days_per_month ?? 26);
        $workHoursPerDay  = (float) ($employee->work_hours_per_day ?? 8);

        // Compute rate
        $hourlyRate = $this->computeHourlyRate($salaryType, $salaryAmount, $workDaysPerMonth, $workHoursPerDay);

        // ===== Gross Pay =====
        // For hourly: pay only by worked minutes
        // For daily: pay by days present * daily rate
        // For monthly: pro-rate based on days present (simple approach)
        $gross = $this->computeGross($salaryType, $salaryAmount, $hourlyRate, $daysPresent, $minutesWorked, $workHoursPerDay);

        // ===== Deductions (Late + Undertime) =====
        // You said: "salary hourly late" → means deduction based on hourlyRate
        $lateDeduction = $this->computeLateDeduction($employee, $hourlyRate, $minutesLate);

        // undertime: also based on hourlyRate (minutesUndertime)
        $undertimeDeduction = round(($minutesUndertime / 60) * $hourlyRate, 2);

        $totalDeductions = round($lateDeduction + $undertimeDeduction, 2);
        $net = round($gross - $totalDeductions, 2);

        // Upsert payroll
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

        // Refresh payroll items
        PayrollItem::where('payroll_id', $payroll->id)->delete();

        // Earnings item (optional breakdown)
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

        // OPTIONAL: statutory deductions if you already created employee_deductions / deduction_types
        // If you want to apply them here, tell me your schema and I’ll add it cleanly.
    }

    /**
     * Compute hourly rate based on salary type
     */
    protected function computeHourlyRate(string $salaryType, float $salaryAmount, int $workDaysPerMonth, float $workHoursPerDay): float
    {
        $salaryType = strtolower($salaryType);

        if ($salaryAmount <= 0) {
            return 0;
        }

        if ($workDaysPerMonth <= 0) {
            $workDaysPerMonth = 26;
        }

        if ($workHoursPerDay <= 0) {
            $workHoursPerDay = 8;
        }

        if ($salaryType === 'hourly') {
            return round($salaryAmount, 6); // salaryAmount is already hourly
        } elseif ($salaryType === 'daily') {
            return round($salaryAmount / $workHoursPerDay, 6);
        } else {
            return round(($salaryAmount / $workDaysPerMonth) / $workHoursPerDay, 6); // monthly -> daily -> hourly
        }
    }

    /**
     * Compute gross pay
     */
    protected function computeGross(
        string $salaryType,
        float $salaryAmount,
        float $hourlyRate,
        int $daysPresent,
        int $minutesWorked,
        float $workHoursPerDay
    ): float {
        $salaryType = strtolower($salaryType);

        if ($hourlyRate <= 0) {
            return 0;
        }

        if ($salaryType === 'hourly') {
            return round(($minutesWorked / 60) * $hourlyRate, 2);
        } elseif ($salaryType === 'daily') {
            return round($daysPresent * ($hourlyRate * $workHoursPerDay), 2);
        } else {
            // monthly: simplest approach = pro-rate by days present
            // if you want *perfect* payroll-grade computation (working days in period etc.), we can refine this.
            return round($daysPresent * ($hourlyRate * $workHoursPerDay), 2);
        }
    }

    /**
     * Compute late deduction based on employee policy
     * You said: salary hourly late → we default to per-minute using hourlyRate.
     */
    protected function computeLateDeduction(Employee $employee, float $hourlyRate, int $minutesLate): float
    {
        if ($hourlyRate <= 0 || $minutesLate <= 0) {
            return 0;
        }

        // If you added these fields, honor them. Otherwise default to per_minute.
        $policy = strtolower($employee->late_policy ?? 'per_minute');

        if ($policy === 'none') {
            return 0;
        }

        // If late_deduction_rate is configured, use it (override)
        // Example meaning:
        // - per_minute: amount per minute
        // - per_hour: amount per hour
        $customRate = (float) ($employee->late_deduction_rate ?? 0);

        if ($customRate > 0) {
            return $policy === 'per_hour'
                ? round(($minutesLate / 60) * $customRate, 2)
                : round($minutesLate * $customRate, 2);
        }

        // Default: per minute based on hourly rate
        // per_minute = hourlyRate / 60
        return $policy === 'per_hour'
            ? round(($minutesLate / 60) * $hourlyRate, 2)
            : round($minutesLate * ($hourlyRate / 60), 2);
    }
}