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
    Schema::table('employees', function (Blueprint $table) {
        // salary basis
        $table->enum('salary_type', ['monthly','daily','hourly'])->default('monthly');
        $table->decimal('salary_amount', 12, 2)->nullable(); // rename from salary or map to it

        // schedule assumptions
        $table->decimal('work_hours_per_day', 5, 2)->default(8); // 8 hours
        $table->integer('work_days_per_month')->default(26); // adjust if you want

        // late penalty policy
        $table->enum('late_policy', ['none','per_minute','per_hour'])->default('per_minute');
        $table->decimal('late_deduction_rate', 12, 2)->default(0); // pesos per minute or per hour based on policy

        // undertime/absent policy (optional)
        $table->decimal('undertime_deduction_rate', 12, 2)->default(0); // pesos per minute or hour
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            //
        });
    }
};
