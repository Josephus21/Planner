<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeRestDayDate extends Model
{
    protected $fillable = [
        'employee_id',
        'rest_date',
        'is_active',
    ];

    protected $casts = [
        'rest_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}