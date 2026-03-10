<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {

            // NOTE: use Schema::hasColumn to avoid duplicate column errors
            if (!Schema::hasColumn('employees', 'salary_type')) {
                $table->enum('salary_type', ['monthly','daily','hourly'])->default('monthly');
            }

            if (!Schema::hasColumn('employees', 'salary_amount')) {
                $table->decimal('salary_amount', 12, 2)->nullable();
            }

            if (!Schema::hasColumn('employees', 'work_hours_per_day')) {
                $table->decimal('work_hours_per_day', 5, 2)->default(8);
            }

            if (!Schema::hasColumn('employees', 'work_days_per_month')) {
                $table->integer('work_days_per_month')->default(26);
            }

            if (!Schema::hasColumn('employees', 'late_policy')) {
                $table->enum('late_policy', ['none','per_minute','per_hour'])->default('per_minute');
            }

            if (!Schema::hasColumn('employees', 'late_deduction_rate')) {
                $table->decimal('late_deduction_rate', 12, 2)->default(0);
            }

            // If you are using Schedule table instead of shift fields, you can SKIP these
            if (!Schema::hasColumn('employees', 'shift_start')) {
                $table->time('shift_start')->nullable();
            }

            if (!Schema::hasColumn('employees', 'shift_end')) {
                $table->time('shift_end')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Only drop columns that actually exist (safe rollback)
            $cols = [
                'salary_type',
                'salary_amount',
                'work_hours_per_day',
                'work_days_per_month',
                'late_policy',
                'late_deduction_rate',
                'shift_start',
                'shift_end',
            ];

            foreach ($cols as $col) {
                if (Schema::hasColumn('employees', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};