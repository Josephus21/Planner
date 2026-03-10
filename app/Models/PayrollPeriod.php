<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PayrollPeriod extends Model
{
    use HasFactory;

    protected $table = 'payroll_periods';

    protected $fillable = [
        'date_from',
        'date_to',
        'status',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to'   => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    // One period has many payroll records (one per employee)
    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS (HELPFUL FOR UI)
    |--------------------------------------------------------------------------
    */

    // Example: "Feb 01, 2026 - Feb 15, 2026"
    public function getLabelAttribute(): string
    {
        return Carbon::parse($this->date_from)->format('M d, Y')
            . ' - ' .
            Carbon::parse($this->date_to)->format('M d, Y');
    }

    // Check if period is posted (locked)
    public function getIsPostedAttribute(): bool
    {
        return $this->status === 'posted';
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    public function scopeLatestFirst($query)
    {
        return $query->orderByDesc('date_from');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    // Check if a date falls inside this period
    public function containsDate($date): bool
    {
        $date = Carbon::parse($date);

        return $date->between(
            Carbon::parse($this->date_from),
            Carbon::parse($this->date_to)
        );
    }

    // Total net payroll for this period
    public function totalNetPay(): float
    {
        return (float) $this->payrolls()->sum('net_pay');
    }

    // Total gross payroll
    public function totalGrossPay(): float
    {
        return (float) $this->payrolls()->sum('gross_pay');
    }

    // Total deductions
    public function totalDeductions(): float
    {
        return (float) $this->payrolls()->sum('total_deductions');
    }
}