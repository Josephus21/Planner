<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Permission;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $department = Department::firstOrCreate(
            ['name' => 'Administration'],
            ['status' => 'active']
        );

        $role = Role::firstOrCreate(
            ['title' => 'Admin'],
            ['description' => 'System Administrator']
        );

        $employee = Employee::firstOrCreate(
            ['email' => 'admin@planner.com'],
            [
                'fullname' => 'System Administrator',
                'phone_number' => '0000000000',
                'address' => 'System',
                'birth_date' => '1990-01-01',
                'hire_date' => now()->toDateString(),
                'department_id' => $department->id,
                'role_id' => $role->id,
                'status' => 'active',
                'salary' => 0,
            ]
        );

        $user = User::firstOrCreate(
            ['email' => 'admin@planner.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'employee_id' => $employee->id,
            ]
        );

        $user->employee_id = $employee->id;
        $user->save();

        // OPTIONAL: assign all permissions to Admin role
        // only works if you already have a role-permission pivot table and relationship
        if (method_exists($role, 'permissions')) {
            $role->permissions()->sync(Permission::pluck('id')->toArray());
        }
    }
}