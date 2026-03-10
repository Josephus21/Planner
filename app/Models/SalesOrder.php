<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    protected $table = 'sales_orders';

    protected $fillable = [
        'external_id',
        'jo_no',
        'so_no',
        'customer_name',
        'prepared_by',
        'description',
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
        'order_date' => 'date',
        'delivery_date' => 'date',
        'fetched_at' => 'datetime',
    ];
}