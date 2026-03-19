<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\EmployeeDeductionLedger;

class PayrollGenerator
{
    public function generate(PayrollPeriod $period): void
    {
        $from = Carbon::parse($period->date_from)->startOfDay();
        $to   = Carbon::parse($period->date_to)->endOfDay();

        $employees = Employee::where('status', 'active')->get();
        $payrollHalf = $this->getPayrollHalf($period);

        foreach ($employees as $employee) {
            DB::transaction(function () use ($employee, $period, $from, $to, $payrollHalf) {
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
                $ledgerEntriesToCreate = [];
                $termBasedUpdates = [];

                foreach ($employeeDeductions as $ed) {
                    $dt = $ed->type;

                    if (!$dt) {
                        continue;
                    }

                    $deductionAmount = 0;
                    $shouldDeduct = false;

                    $mode = $ed->deduction_mode ?? 'scheduled';
                    $code = strtoupper((string) ($dt->code ?? ''));

                    /**
                     * Skip if already applied for this payroll period
                     */
                    $alreadyApplied = EmployeeDeductionLedger::where('employee_deduction_id', $ed->id)
                        ->where('payroll_period_id', $period->id)
                        ->exists();

                    if ($alreadyApplied) {
                        continue;
                    }

                    /**
                     * RECURRING DEDUCTIONS
                     * - PHIC + PAGIBIG = first payroll
                     * - SSS = second payroll
                     * - other recurring = every payroll
                     */
                    if ($mode === 'recurring') {
                        if (in_array($code, ['PHIC', 'PAGIBIG'])) {
                            $shouldDeduct = $payrollHalf === 'first';
                        } elseif ($code === 'SSS') {
                            $shouldDeduct = $payrollHalf === 'second';
                        } else {
                            $shouldDeduct = true;
                        }
                    }

                    /**
                     * SCHEDULED DEDUCTIONS
                     * only on a specific payroll period
                     */
                    if ($mode === 'scheduled') {
                        $shouldDeduct = !is_null($ed->payroll_period_id)
                            && (int) $ed->payroll_period_id === (int) $period->id;
                    }

                    /**
                     * TERM-BASED DEDUCTIONS
                     * Loan / Installment
                     */
                    if ($mode === 'term_based') {
                        $remainingTerms = (int) ($ed->remaining_terms ?? 0);
                        $remainingBalance = (float) ($ed->remaining_balance ?? 0);

                        $shouldDeduct = $remainingTerms > 0 && $remainingBalance > 0;
                    }

                    if (!$shouldDeduct) {
                        continue;
                    }

                    $termsBefore = $ed->remaining_terms;
                    $balanceBefore = $ed->remaining_balance;
                    $termsAfter = $termsBefore;
                    $balanceAfter = $balanceBefore;

                    if ($mode === 'term_based') {
                        $deductionAmount = (float) ($ed->amount ?? 0);
                        $remainingBalance = (float) ($ed->remaining_balance ?? 0);

                        if ($deductionAmount <= 0) {
                            continue;
                        }

                        if ($deductionAmount > $remainingBalance) {
                            $deductionAmount = $remainingBalance;
                        }

                        $termsAfter = max(((int) $ed->remaining_terms) - 1, 0);
                        $balanceAfter = max(round(((float) $ed->remaining_balance) - $deductionAmount, 2), 0);

                        $termBasedUpdates[] = [
                            'employee_deduction_id' => $ed->id,
                            'terms_after'           => $termsAfter,
                            'balance_after'         => $balanceAfter,
                            'is_active'             => ($termsAfter > 0 && $balanceAfter > 0) ? 1 : 0,
                        ];
                    } else {
                        $value = $dt->method === 'percent'
                            ? (float) ($ed->rate ?? $ed->amount ?? 0)
                            : (float) ($ed->amount ?? 0);

                        if ($value <= 0) {
                            continue;
                        }

                        if ($dt->method === 'percent') {
                            $deductionAmount = ($value / 100) * $gross;
                        } else {
                            $deductionAmount = $value;
                        }
                    }

                    $deductionAmount = round($deductionAmount, 2);

                    if ($deductionAmount <= 0) {
                        continue;
                    }

                    $employeeDeductionTotal += $deductionAmount;

                    $deductionBreakdown[] = [
                        'name'   => $dt->name,
                        'amount' => $deductionAmount,
                    ];

                    $ledgerEntriesToCreate[] = [
                        'employee_deduction_id' => $ed->id,
                        'employee_id'           => $employee->id,
                        'deduction_type_id'     => $dt->id,
                        'payroll_period_id'     => $period->id,
                        'amount'                => $deductionAmount,
                        'terms_before'          => $termsBefore,
                        'terms_after'           => $termsAfter,
                        'balance_before'        => $balanceBefore,
                        'balance_after'         => $balanceAfter,
                        'remarks'               => 'Applied during payroll generation',
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

                /**
                 * CREATE DEDUCTION LEDGER ENTRIES
                 */
                foreach ($ledgerEntriesToCreate as $entry) {
                    EmployeeDeductionLedger::create([
                        'employee_deduction_id' => $entry['employee_deduction_id'],
                        'employee_id'           => $entry['employee_id'],
                        'deduction_type_id'     => $entry['deduction_type_id'],
                        'payroll_period_id'     => $entry['payroll_period_id'],
                        'payroll_id'            => $payroll->id,
                        'amount'                => $entry['amount'],
                        'terms_before'          => $entry['terms_before'],
                        'terms_after'           => $entry['terms_after'],
                        'balance_before'        => $entry['balance_before'],
                        'balance_after'         => $entry['balance_after'],
                        'remarks'               => $entry['remarks'],
                    ]);
                }

                /**
                 * UPDATE TERM-BASED DEDUCTIONS
                 */
                foreach ($termBasedUpdates as $update) {
                    $employee->deductions()
                        ->where('id', $update['employee_deduction_id'])
                        ->update([
                            'remaining_terms'   => $update['terms_after'],
                            'remaining_balance' => $update['balance_after'],
                            'is_active'         => $update['is_active'],
                        ]);
                }
            });
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
     * Detect whether current payroll period is first or second payroll of the month.
     * Typical semi-monthly setup:
     * - 1 to 15 = first
     * - 16 to end = second
     */
    private function getPayrollHalf(PayrollPeriod $period): string
    {
        $dayFrom = Carbon::parse($period->date_from)->day;

        return $dayFrom <= 15 ? 'first' : 'second';
    }

    /**
     * Compute holiday/rest day pay and OT.
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

            if ($minutesLate > 0 && $minutesLate <= 30) {
                $lateDeduction += $hourlyRate;
            } elseif ($minutesLate > 30 && $minutesLate <= 60) {
                $lateDeduction += ($dailyRate / 2);
            } else {
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