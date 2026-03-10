<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectProgressUpdate extends Model
{
    protected $fillable = [
        'project_id','updated_by','percent','note','photo'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}