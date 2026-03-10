<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    public function projects()
    {
        return $this->hasMany(Project::class, 'category_id');
    }

    public function checklists()
    {
        return $this->hasMany(QualityChecklist::class, 'project_category_id', 'id')
            ->where('is_active', 1)
            ->orderBy('sort_order');
    }
}