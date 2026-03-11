<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Administration', 'status' => 'active'],
            ['name' => 'Human Resources', 'status' => 'active'],
            ['name' => 'IT', 'status' => 'active'],
            ['name' => 'Finance', 'status' => 'active'],
            ['name' => 'Operations', 'status' => 'active'],
        ];

        foreach ($departments as $department) {
            Department::firstOrCreate(
                ['name' => $department['name']],
                ['status' => $department['status']]
            );
        }
    }
}