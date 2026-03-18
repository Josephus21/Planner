<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OvertimeRequest extends Model
{
    protected $fillable = [
        'employee_id',
        'company_id',
        'title',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'remarks',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function dates()
    {
        return $this->hasMany(OvertimeRequestDate::class);
    }
}