<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_deduction_ledgers', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('employee_deduction_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('deduction_type_id');
            $table->unsignedBigInteger('payroll_period_id');
            $table->unsignedBigInteger('payroll_id')->nullable();

            $table->decimal('amount', 12, 2)->default(0);

            $table->integer('terms_before')->nullable();
            $table->integer('terms_after')->nullable();

            $table->decimal('balance_before', 12, 2)->nullable();
            $table->decimal('balance_after', 12, 2)->nullable();

            $table->string('remarks')->nullable();

            $table->timestamps();

            $table->foreign('employee_deduction_id')
                ->references('id')
                ->on('employee_deductions')
                ->cascadeOnDelete();

            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->cascadeOnDelete();

            $table->foreign('deduction_type_id')
                ->references('id')
                ->on('deduction_types')
                ->cascadeOnDelete();

            $table->foreign('payroll_period_id')
                ->references('id')
                ->on('payroll_periods')
                ->cascadeOnDelete();

            $table->foreign('payroll_id')
                ->references('id')
                ->on('payrolls')
                ->nullOnDelete();

            // Prevent duplicate application of same deduction in same payroll period
            $table->unique(
                ['employee_deduction_id', 'payroll_period_id'],
                'uniq_employee_deduction_period'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_deduction_ledgers');
    }
};