<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'work_date',

        'time_in',
        'break_out',
        'break_in',
        'lunch_out',
        'lunch_in',
        'time_out',

        'minutes_late',
        'minutes_worked',
        'minutes_undertime',

        'time_in_latitude',
        'time_in_longitude',
        'time_in_accuracy',
        'time_in_ip_address',

        'break_out_latitude',
        'break_out_longitude',
        'break_out_accuracy',
        'break_out_ip_address',

        'break_in_latitude',
        'break_in_longitude',
        'break_in_accuracy',
        'break_in_ip_address',

        'lunch_out_latitude',
        'lunch_out_longitude',
        'lunch_out_accuracy',
        'lunch_out_ip_address',

        'lunch_in_latitude',
        'lunch_in_longitude',
        'lunch_in_accuracy',
        'lunch_in_ip_address',

        'time_out_latitude',
        'time_out_longitude',
        'time_out_accuracy',
        'time_out_ip_address',
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

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}