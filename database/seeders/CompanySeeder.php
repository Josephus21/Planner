<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            ['name' => 'Cebu Graphicstar Imaging Corp', 'code' => 'CGIC', 'status' => 'active'],
            ['name' => '7Js Software Solution', 'code' => '7JSS', 'status' => 'active'],
        ];

        foreach ($companies as $company) {
            Company::firstOrCreate(
                ['code' => $company['code']],
                $company
            );
        }
    }
}