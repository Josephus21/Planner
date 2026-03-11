<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeScheduleAssignment extends Model
{
    protected $fillable = [
        'employee_id',
        'schedule_id',
        'effective_from',
        'effective_to',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }
}