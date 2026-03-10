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

            // Rates
            $salary = (float) $employee->salary; // your existing salary column

            $dailyRate  = $this->dailyRate($employee, $salary);
            $hourlyRate = $dailyRate / (float) $employee->work_hours_per_day;
            $perMinuteRate = $hourlyRate / 60;

            // Gross: absences reduce salary => base on days present
            $gross = $dailyRate * $daysPresent;

            // Late deduction: based on salary hourly rate
            $lateDeduction = $minutesLate * $perMinuteRate;

            // Optional: undertime deduction (if you want same logic)
            $minutesUndertime = (int) $logs->sum('minutes_undertime');
            $undertimeDeduction = $minutesUndertime * $perMinuteRate;
// ===================== Employee Deductions =====================
$employeeDeductions = $employee->deductions()
    ->with('type')
    ->where('is_active', 1)
    ->get();

$employeeDeductionTotal = 0;

foreach ($employeeDeductions as $ed) {
    $dt = $ed->type;
    if (!$dt) continue;

    $value = (float) ($ed->amount ?? 0);
    if ($value <= 0) continue;

    $deductionAmount = 0;

    if ($dt->method === 'percent') {
        // amount is percent (e.g. 5 means 5%)
        $deductionAmount = ($value / 100) * $gross;
    } else {
        // fixed peso
        $deductionAmount = $value;
    }

    // frequency handling (monthly split if semi-monthly period)
    if ($dt->frequency === 'monthly') {
        $daysInPeriod = \Carbon\Carbon::parse($period->date_from)
            ->diffInDays(\Carbon\Carbon::parse($period->date_to)) + 1;

        // if your period is 1-15 / 16-end, split monthly deductions by 2
        if ($daysInPeriod <= 16) {
            $deductionAmount = $deductionAmount / 2;
        }
    }

    $employeeDeductionTotal += round($deductionAmount, 2);
}
            // For now: total deductions = late + undertime
            // Later: add statutory + insurance here
           $totalDeductions = round($lateDeduction + $undertimeDeduction + $employeeDeductionTotal, 2);
$net = round($gross - $totalDeductions, 2);

            // Upsert payroll
            $payroll = Payroll::updateOrCreate(
                [
                    'payroll_period_id' => $period->id,
                    'employee_id' => $employee->id,
                ],
                [
                    'gross_pay' => round($gross, 2),
                    'total_deductions' => round($totalDeductions, 2),
                    'net_pay' => round($net, 2),
                    'days_present' => $daysPresent,
                    'minutes_late' => $minutesLate,
                    'minutes_worked' => $minutesWorked,
                ]
            );

            // refresh items
          // refresh items
PayrollItem::where('payroll_id', $payroll->id)->delete();

// Base Pay item (optional)
PayrollItem::create([
    'payroll_id' => $payroll->id,
    'type' => 'earning',
    'name' => 'Base pay',
    'amount' => round($gross, 2),
]);

// Late / undertime
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

// Employee deductions list
foreach ($employeeDeductions as $ed) {
    $dt = $ed->type;
    if (!$dt) continue;

    $value = (float) ($ed->amount ?? 0);
    if ($value <= 0) continue;

    $deductionAmount = ($dt->method === 'percent')
        ? (($value / 100) * $gross)
        : $value;

    if ($dt->frequency === 'monthly') {
        $daysInPeriod = \Carbon\Carbon::parse($period->date_from)
            ->diffInDays(\Carbon\Carbon::parse($period->date_to)) + 1;

        if ($daysInPeriod <= 16) {
            $deductionAmount = $deductionAmount / 2;
        }
    }

    $deductionAmount = round($deductionAmount, 2);

    PayrollItem::create([
        'payroll_id' => $payroll->id,
        'type' => 'deduction',
        'name' => $dt->name,
        'amount' => $deductionAmount,
    ]);
}
        }
    }

    private function dailyRate(Employee $employee, float $salary): float
    {
        if ($employee->salary_type === 'daily') return $salary;
        if ($employee->salary_type === 'hourly') {
            return $salary * (float)$employee->work_hours_per_day;
        }
        // monthly
        return $salary / (int)$employee->work_days_per_month;
    }
}