<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectQualityCheck extends Model
{
    protected $fillable = [
        'project_id',
        'quality_checklist_id',
        'checked_by',
        'checked_at',
    ];
}