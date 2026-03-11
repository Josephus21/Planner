<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Task;
use Illuminate\Support\Facades\Hash;

class HumanResourcesSeeder extends Seeder
{
    public function run(): void
    {
        $department = Department::firstOrCreate(
            ['name' => 'Human Resources'],
            ['status' => 'active']
        );

        $role = Role::firstOrCreate(
            ['title' => 'HR'],
            ['description' => 'Human Resources']
        );

        $employee = Employee::firstOrCreate(
            ['email' => 'hr@planner.com'],
            [
                'fullname' => 'Human Resources',
                'phone_number' => '09123456789',
                'address' => 'Office',
                'birth_date' => '1995-01-01',
                'hire_date' => now()->toDateString(),
                'department_id' => $department->id,
                'role_id' => $role->id,
                'status' => 'active',
                'salary' => 0,
            ]
        );

        User::firstOrCreate(
            ['email' => 'hr@planner.com'],
            [
                'name' => 'HR',
                'password' => Hash::make('password'),
                'employee_id' => $employee->id,
            ]
        );

        Task::firstOrCreate(
            [
                'title' => 'Review employee attendance',
                'assigned_to' => $employee->id,
            ],
            [
                'description' => 'Review daily attendance logs and validate punches.',
                'due_date' => now()->addDays(3)->toDateString(),
                'status' => 'pending',
                'deleted_at' => null,
            ]
        );

        Task::firstOrCreate(
            [
                'title' => 'Prepare payroll summary',
                'assigned_to' => $employee->id,
            ],
            [
                'description' => 'Prepare payroll summary for the current payroll period.',
                'due_date' => now()->addDays(5)->toDateString(),
                'status' => 'pending',
                'deleted_at' => null,
            ]
        );
    }
}