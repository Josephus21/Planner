<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'employee_id'
        // if you have users.role column, add it here too:
        // 'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function employee()
    {
        // users.employee_id -> employees.id
        return $this->belongsTo(\App\Models\Employee::class, 'employee_id', 'id');
    }

    /**
     * OPTIONAL: keep per-user permissions if you still want overrides.
     * If you don't use permission_user anymore, you can remove this.
     */
    public function permissions()
    {
        return $this->belongsToMany(\App\Models\Permission::class, 'permission_user');
    }

    public function hasPermission(string $key): bool
    {
        // Always use DB value, not session
        // If you DO have users.role column, you can enable this bypass:
        $userRole = $this->role ?? null;
        if (in_array($userRole, ['Developer', 'Admin'], true)) {
            return true;
        }

        // Must be linked to an employee record
        $employee = $this->employee;
        if (!$employee || !$employee->role_id) {
            return false;
        }

        // Role-based permissions (role_permission pivot)
        // Requires: Role model has permissions() belongsToMany
        $role = \App\Models\Role::with('permissions')->find($employee->role_id);
        if (!$role) {
            return false;
        }

        if ($role->permissions->contains('key', $key)) {
            return true;
        }

        // OPTIONAL: allow per-user permission overrides too
        $this->loadMissing('permissions');
        return $this->permissions->contains('key', $key);
    }
}