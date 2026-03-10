<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeDeduction extends Model
{
    use HasFactory;

    protected $table = 'employee_deductions';

    protected $fillable = [
        'employee_id',
        'deduction_type_id',
        'amount',        // fixed amount per payroll period (recommended)
        'is_active',
        // optional columns if you have them:
        // 'frequency',  // 'per_period' | 'monthly'
        // 'percentage', // if you want % of gross
        // 'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function type()
    {
        return $this->belongsTo(DeductionType::class, 'deduction_type_id');
    }
}