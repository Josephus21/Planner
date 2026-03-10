<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeductionType extends Model
{
    use HasFactory;

    protected $table = 'deduction_types';

    protected $fillable = [
        'name',      // SSS, PhilHealth, Insurance, Loan, Others
        'code',      // optional: SSS, PHIC, INS, LOAN, OTH
        'is_active', // optional
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function employeeDeductions()
    {
        return $this->hasMany(EmployeeDeduction::class, 'deduction_type_id');
    }
}