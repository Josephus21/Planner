<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeDeductionLedger extends Model
{
    protected $fillable = [
        'employee_deduction_id',
        'employee_id',
        'deduction_type_id',
        'payroll_period_id',
        'payroll_id',
        'amount',
        'terms_before',
        'terms_after',
        'balance_before',
        'balance_after',
        'remarks',
    ];

    public function employeeDeduction()
    {
        return $this->belongsTo(EmployeeDeduction::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function deductionType()
    {
        return $this->belongsTo(DeductionType::class);
    }

    public function payrollPeriod()
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }
}