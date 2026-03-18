<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Services\LeaveCreditService;

class GrantAnnualLeaveCredits extends Command
{
    protected $signature = 'leave:grant-credits';
    protected $description = 'Grant annual and prorated leave credits to eligible employees';

    public function handle(LeaveCreditService $leaveCreditService): int
    {
        Employee::chunk(100, function ($employees) use ($leaveCreditService) {
            foreach ($employees as $employee) {
                $leaveCreditService->grantCredits($employee);
            }
        });

        $this->info('Leave credits granted successfully.');

        return self::SUCCESS;
    }
}