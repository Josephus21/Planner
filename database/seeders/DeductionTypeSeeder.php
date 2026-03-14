<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DeductionType;

class DeductionTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'SSS',          'code' => 'SSS',     'method' => 'fixed',   'frequency' => 'monthly'],
            ['name' => 'PhilHealth',   'code' => 'PHIC',    'method' => 'percent', 'frequency' => 'monthly'],
            ['name' => 'Pag-Ibig',     'code' => 'PAGIBIG', 'method' => 'fixed',   'frequency' => 'monthly'],

            ['name' => 'Cash Advance', 'code' => 'CA',      'method' => 'fixed',   'frequency' => 'per_payroll'],
            ['name' => 'Insurance',    'code' => 'INS',     'method' => 'fixed',   'frequency' => 'monthly'],
            ['name' => 'Loan',         'code' => 'LOAN',    'method' => 'fixed',   'frequency' => 'per_payroll'],

            /*
            |--------------------------------------------------------------------------
            | Installment Deduction
            |--------------------------------------------------------------------------
            */
            [
                'name' => 'Installment',
                'code' => 'INST',
                'method' => 'fixed',
                'frequency' => 'per_payroll'
            ],

            ['name' => 'Others',       'code' => 'OTH',     'method' => 'fixed',   'frequency' => 'per_payroll'],
        ];

        foreach ($types as $t) {
            DeductionType::updateOrCreate(
                ['code' => $t['code']],
                $t
            );
        }
    }
}