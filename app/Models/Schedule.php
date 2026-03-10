<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'break_start',
        'break_end',
        'lunch_start',
        'lunch_end',
    ];

    /**
     * Employees assigned to this schedule (via assignments)
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeScheduleAssignment::class, 'schedule_id');
    }
}