<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::create('payrolls', function (Blueprint $table) {
        $table->id();
        $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
        $table->foreignId('employee_id')->constrained()->cascadeOnDelete();

        $table->decimal('gross_pay', 12, 2)->default(0);
        $table->decimal('total_deductions', 12, 2)->default(0);
        $table->decimal('net_pay', 12, 2)->default(0);

        // useful stats for payslip
        $table->integer('days_present')->default(0);
        $table->integer('minutes_late')->default(0);
        $table->integer('minutes_worked')->default(0);

        $table->timestamps();

        $table->unique(['payroll_period_id','employee_id']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
