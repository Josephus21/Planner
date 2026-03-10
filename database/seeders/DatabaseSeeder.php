<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
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
        \App\Models\DeductionType::firstOrCreate(['code' => $t['code']], $t);
    }
}
}