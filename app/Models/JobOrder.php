<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobOrder extends Model
{
    protected $fillable = [
        'external_id',
        'jo_no',
        'so_no',
        'customer_name',
        'prepared_by',
        'description',
        'location',
        'job_type',
        'order_date',
        'delivery_date',
        'status',
        'sub_status',
        'gp_rate',
        'payload',
        'fetched_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'fetched_at' => 'datetime',
        'order_date' => 'date',
        'delivery_date' => 'date',
    ];
}