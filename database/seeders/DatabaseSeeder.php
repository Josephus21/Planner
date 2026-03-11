<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            DepartmentSeeder::class,
            AdminSeeder::class,
            HumanResourcesSeeder::class,
            DeductionTypeSeeder::class,
            CompanySeeder::class,
        ]);
    }
}