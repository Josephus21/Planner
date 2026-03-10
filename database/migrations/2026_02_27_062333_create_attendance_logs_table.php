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
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();

            // Link to employee
            $table->foreignId('employee_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // Work date (separate for uniqueness)
            $table->date('work_date');

            // Attendance punches (nullable until used)
            $table->dateTime('time_in')->nullable();
            $table->dateTime('break_out')->nullable();
            $table->dateTime('break_in')->nullable();
            $table->dateTime('lunch_out')->nullable();
            $table->dateTime('lunch_in')->nullable();
            $table->dateTime('time_out')->nullable();

            $table->timestamps();

            // One log per employee per day
            $table->unique(['employee_id', 'work_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};