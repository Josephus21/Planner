<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    protected $fillable = [
        'employee_id','work_date',
        'time_in',
        'break_out','break_in',
        'lunch_out','lunch_in',
        'time_out',
    ];

    protected $casts = [
        'work_date' => 'date',
        'time_in' => 'datetime',
        'break_out' => 'datetime',
        'break_in' => 'datetime',
        'lunch_out' => 'datetime',
        'lunch_in' => 'datetime',
        'time_out' => 'datetime',
    ];
}