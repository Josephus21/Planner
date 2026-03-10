<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payroll extends Model
{
    use HasFactory, SoftDeletes;

    // IMPORTANT: use the NEW table
    protected $table = 'payrolls';

    protected $fillable = [
        'payroll_period_id',
        'employee_id',
        'gross_pay',
        'total_deductions',
        'net_pay',
        'days_present',
        'minutes_late',
        'minutes_worked',
    ];

    protected $casts = [
        'gross_pay' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function period()
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function items()
{
    return $this->hasMany(\App\Models\PayrollItem::class);
}
}