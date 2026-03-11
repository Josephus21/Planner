<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DeductionType;

class DeductionTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'SSS', 'code' => 'SSS'],
            ['name' => 'PhilHealth', 'code' => 'PHIC'],
            ['name' => 'Insurance', 'code' => 'INS'],
            ['name' => 'Loan', 'code' => 'LOAN'],
            ['name' => 'Others', 'code' => 'OTH'],
        ];

        foreach ($types as $t) {
            DeductionType::firstOrCreate(
                ['code' => $t['code']],
                $t
            );
        }
    }
}