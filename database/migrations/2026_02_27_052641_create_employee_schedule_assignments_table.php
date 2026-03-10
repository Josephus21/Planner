<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('employee_schedule_assignments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('schedule_id');

            $table->date('effective_from');
            $table->date('effective_to')->nullable();

            $table->timestamps();

            // ✅ shorter index name to avoid MySQL 64-char limit
            $table->index(['employee_id', 'effective_from', 'effective_to'], 'esa_emp_eff_idx');

            $table->foreign('employee_id')
                ->references('id')->on('employees')
                ->onDelete('cascade');

            $table->foreign('schedule_id')
                ->references('id')->on('schedules')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_schedule_assignments');
    }
};