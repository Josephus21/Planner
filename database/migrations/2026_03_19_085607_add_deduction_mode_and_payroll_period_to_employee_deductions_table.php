<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_deductions', function (Blueprint $table) {
            $table->string('deduction_mode')->default('scheduled')->after('deduction_type_id');
            $table->unsignedBigInteger('payroll_period_id')->nullable()->after('remaining_balance');

            $table->foreign('payroll_period_id')
                ->references('id')
                ->on('payroll_periods')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employee_deductions', function (Blueprint $table) {
            $table->dropForeign(['payroll_period_id']);
            $table->dropColumn(['deduction_mode', 'payroll_period_id']);
        });
    }
};