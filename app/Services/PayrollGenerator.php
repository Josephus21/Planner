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
                        /**
                         * SPECIAL HANDLING FOR SSS
                         * Use gross pay bracket lookup instead of fixed amount / percent.
                         */
                        if ($code === 'SSS') {
                            $deductionAmount = $this->getSssDeductionByGross($gross);
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
                        'remarks'               => $code === 'SSS'
                            ? 'Applied during payroll generation (SSS based on gross pay bracket)'
                            : 'Applied during payroll generation',
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

    /**
     * SSS employee deduction based on gross pay bracket.
     * This uses the employee total share:
     * ee_sss + mpf_ee = sss_mpf_ee_tot
     */
    private function getSssDeductionByGross(float $gross): float
    {
        $gross = round($gross, 2);

        $table = [
            ['from' => 0.00,    'to' => 5249.99,  'ee_total' => 250.00],
            ['from' => 5250.00, 'to' => 5749.99,  'ee_total' => 275.00],
            ['from' => 5750.00, 'to' => 6249.99,  'ee_total' => 300.00],
            ['from' => 6250.00, 'to' => 6749.99,  'ee_total' => 325.00],
            ['from' => 6750.00, 'to' => 7249.99,  'ee_total' => 350.00],
            ['from' => 7250.00, 'to' => 7749.99,  'ee_total' => 375.00],
            ['from' => 7750.00, 'to' => 8249.99,  'ee_total' => 400.00],
            ['from' => 8250.00, 'to' => 8749.99,  'ee_total' => 425.00],
            ['from' => 8750.00, 'to' => 9249.99,  'ee_total' => 450.00],
            ['from' => 9250.00, 'to' => 9749.99,  'ee_total' => 475.00],
            ['from' => 9750.00, 'to' => 10249.99, 'ee_total' => 500.00],
            ['from' => 10250.00,'to' => 10749.99, 'ee_total' => 525.00],
            ['from' => 10750.00,'to' => 11249.99, 'ee_total' => 550.00],
            ['from' => 11250.00,'to' => 11749.99, 'ee_total' => 575.00],
            ['from' => 11750.00,'to' => 12249.99, 'ee_total' => 600.00],
            ['from' => 12250.00,'to' => 12749.99, 'ee_total' => 625.00],
            ['from' => 12750.00,'to' => 13249.99, 'ee_total' => 650.00],
            ['from' => 13250.00,'to' => 13749.99, 'ee_total' => 675.00],
            ['from' => 13750.00,'to' => 14249.99, 'ee_total' => 700.00],
            ['from' => 14250.00,'to' => 14749.99, 'ee_total' => 725.00],
            ['from' => 14750.00,'to' => 15249.99, 'ee_total' => 750.00],
            ['from' => 15250.00,'to' => 15749.99, 'ee_total' => 775.00],
            ['from' => 15750.00,'to' => 16249.99, 'ee_total' => 800.00],
            ['from' => 16250.00,'to' => 16749.99, 'ee_total' => 825.00],
            ['from' => 16750.00,'to' => 17249.99, 'ee_total' => 850.00],
            ['from' => 17250.00,'to' => 17749.99, 'ee_total' => 875.00],
            ['from' => 17750.00,'to' => 18249.99, 'ee_total' => 900.00],
            ['from' => 18250.00,'to' => 18749.99, 'ee_total' => 925.00],
            ['from' => 18750.00,'to' => 19249.99, 'ee_total' => 950.00],
            ['from' => 19250.00,'to' => 19749.99, 'ee_total' => 975.00],
            ['from' => 19750.00,'to' => 20249.99, 'ee_total' => 1000.00],
            ['from' => 20250.00,'to' => 20749.99, 'ee_total' => 1025.00],
            ['from' => 20750.00,'to' => 21249.99, 'ee_total' => 1050.00],
            ['from' => 21250.00,'to' => 21749.99, 'ee_total' => 1075.00],
            ['from' => 21750.00,'to' => 22249.99, 'ee_total' => 1100.00],
            ['from' => 22250.00,'to' => 22749.99, 'ee_total' => 1125.00],
            ['from' => 22750.00,'to' => 23249.99, 'ee_total' => 1150.00],
            ['from' => 23250.00,'to' => 23749.99, 'ee_total' => 1175.00],
            ['from' => 23750.00,'to' => 24249.99, 'ee_total' => 1200.00],
            ['from' => 24250.00,'to' => 24749.99, 'ee_total' => 1225.00],
            ['from' => 24750.00,'to' => 25249.99, 'ee_total' => 1250.00],
            ['from' => 25250.00,'to' => 25749.99, 'ee_total' => 1275.00],
            ['from' => 25750.00,'to' => 26249.99, 'ee_total' => 1300.00],
            ['from' => 26250.00,'to' => 26749.99, 'ee_total' => 1325.00],
            ['from' => 26750.00,'to' => 27249.99, 'ee_total' => 1350.00],
            ['from' => 27250.00,'to' => 27749.99, 'ee_total' => 1375.00],
            ['from' => 27750.00,'to' => 28249.99, 'ee_total' => 1400.00],
            ['from' => 28250.00,'to' => 28749.99, 'ee_total' => 1425.00],
            ['from' => 28750.00,'to' => 29249.99, 'ee_total' => 1450.00],
            ['from' => 29250.00,'to' => 29749.99, 'ee_total' => 1475.00],
            ['from' => 29750.00,'to' => 30249.99, 'ee_total' => 1500.00],
            ['from' => 30250.00,'to' => 30749.99, 'ee_total' => 1525.00],
            ['from' => 30750.00,'to' => 31249.99, 'ee_total' => 1550.00],
            ['from' => 31250.00,'to' => 31749.99, 'ee_total' => 1575.00],
            ['from' => 31750.00,'to' => 32249.99, 'ee_total' => 1600.00],
            ['from' => 32250.00,'to' => 32749.99, 'ee_total' => 1625.00],
            ['from' => 32750.00,'to' => 33249.99, 'ee_total' => 1650.00],
            ['from' => 33250.00,'to' => 33749.99, 'ee_total' => 1675.00],
            ['from' => 33750.00,'to' => 999999.99, 'ee_total' => 1700.00],
        ];

        foreach ($table as $row) {
            if ($gross >= $row['from'] && $gross <= $row['to']) {
                return (float) $row['ee_total'];
            }
        }

        return 0.00;
    }
}