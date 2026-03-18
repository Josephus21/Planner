<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OvertimeRequestDate extends Model
{
    protected $fillable = [
        'overtime_request_id',
        'ot_date',
        'start_time',
        'end_time',
        'break_minutes',
        'planned_hours',
    ];

    protected $casts = [
        'ot_date' => 'date',
    ];

    public function overtimeRequest()
    {
        return $this->belongsTo(OvertimeRequest::class);
    }
}