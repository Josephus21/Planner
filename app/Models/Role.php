<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
    ];

    /**
     * Many-to-many: roles <-> permissions
     * Pivot table: role_permission (role_id, permission_id)
     */
    public function permissions()
    {
        return $this->belongsToMany(\App\Models\Permission::class, 'role_permission', 'role_id', 'permission_id')
            ->withTimestamps();
    }

    /**
     * One-to-many: role -> employees
     * employees.role_id -> roles.id
     */
    public function employees()
    {
        return $this->hasMany(\App\Models\Employee::class, 'role_id', 'id');
    }

    /**
     * Helper: quickly check if role has a permission key
     */
    public function hasPermission(string $key): bool
    {
        $this->loadMissing('permissions');
        return $this->permissions->contains('key', $key);
    }
}