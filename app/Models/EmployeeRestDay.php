<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeRestDay extends Model
{
    protected $fillable = [
        'employee_id',
        'day_name',
        'is_active'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}