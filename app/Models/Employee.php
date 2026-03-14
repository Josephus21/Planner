<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'fullname',
        'email',
        'phone_number',
        'address',
        'department_id',
        'company_id',
        'salary',
        'birth_date',
        'hire_date',
        'status',
          'salary_type',
        'role_id'
    ];

 public function department()
{
    return $this->belongsTo(\App\Models\Department::class, 'department_id');
}

   
public function role()
{
    return $this->belongsTo(\App\Models\Role::class, 'role_id', 'id');
}

public function employee()
{
    return $this->belongsTo(\App\Models\Employee::class, 'employee_id', 'id');
}

public function attendanceLogs()
{
    return $this->hasMany(\App\Models\AttendanceLog::class, 'employee_id', 'id');
}
public function deductions()
{
    return $this->hasMany(\App\Models\EmployeeDeduction::class);
}
public function tasks()
{
    return $this->hasMany(\App\Models\Task::class);
}

public function company()
{
    return $this->belongsTo(\App\Models\Company::class);
}
public function companies()
{
    return $this->belongsToMany(Company::class, 'employee_companies')->withTimestamps();
}
public function isMonthly(): bool
{
    return $this->salary_type === 'monthly';
}

public function isDaily(): bool
{
    return $this->salary_type === 'daily';
}


}
