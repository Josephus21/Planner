<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectBomItem extends Model
{
    protected $fillable = [
        'project_id','section','item','qty','status','done_at','updated_by'
    ];

    protected $casts = [
        'done_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}