<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // create employee first
        $employee = Employee::firstOrCreate(
            ['email' => 'admin@planner.com'],
            [
                'fullname' => 'System Administrator',
                'phone_number' => '0000000000',
                'address' => 'System',
                'birth_date' => '1990-01-01',
                'hire_date' => now(),
                'department_id' => 1,
                'role_id' => 1,
                'status' => 'active',
                'salary' => 0
            ]
        );

        // create user linked to employee
        User::firstOrCreate(
            ['email' => 'admin@planner.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('admin123'),
                'employee_id' => $employee->id
            ]
        );
    }
}