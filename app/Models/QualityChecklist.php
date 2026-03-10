<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QualityChecklist extends Model
{
    protected $table = 'quality_checklists';

    protected $fillable = [
        'project_category_id',
        'item',
        'sort_order',
        'is_required',
        'is_active',
        'is_done',
        'done_at',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active'   => 'boolean',
        'is_done'     => 'boolean',
        'done_at'     => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(ProjectCategory::class, 'project_category_id');
    }
}