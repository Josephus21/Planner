<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectCategoryChecklist extends Model
{
    use HasFactory;

    // Your REAL table
    protected $table = 'quality_checklists';

    protected $fillable = [
        'category_id',
        'item',
        'sort',
        'required',
        'active'
    ];

    protected $casts = [
        'required' => 'boolean',
        'active' => 'boolean',
        'sort' => 'integer'
    ];

    public function category()
    {
        return $this->belongsTo(ProjectCategory::class, 'category_id');
    }
}