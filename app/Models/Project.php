<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'title','description',
        'project_image',
        'date_from','date_to',
        'planner_id','driver_id','department_id', 
        'status','progress',
        // ? new
    'category_id','vehicle_used',
    'needs_permit','permit_path',
    'needs_safety_officer','safety_officer_id','bom_path',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to'   => 'date',
    ];

    public function planner()
    {
        return $this->belongsTo(Employee::class, 'planner_id');
    }

    public function driver()
    {
        return $this->belongsTo(Employee::class, 'driver_id');
    }

    public function installers()
    {
        return $this->belongsToMany(Employee::class, 'project_installers', 'project_id', 'employee_id')
            ->withTimestamps();
    }

    public function updates()
    {
        return $this->hasMany(ProjectProgressUpdate::class)->latest();
    }
    public function category()
{
    return $this->belongsTo(\App\Models\ProjectCategory::class, 'category_id');
}

public function safetyOfficer()
{
    return $this->belongsTo(\App\Models\Employee::class, 'safety_officer_id');
}

public function subcons()
{
    return $this->belongsToMany(\App\Models\Employee::class, 'project_subcon', 'project_id', 'employee_id')
        ->withTimestamps();
}

public function vehicles()
{
    return $this->belongsToMany(\App\Models\Vehicle::class, 'project_vehicle')
        ->withTimestamps();
}
public function salesOrder()
{
    return $this->belongsTo(\App\Models\SalesOrder::class);
}
public function bomItems()
{
    return $this->hasMany(\App\Models\ProjectBomItem::class)->orderBy('id');
}

}