<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_overtime_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('overtime_request_id')->nullable()->constrained()->nullOnDelete();

            $table->date('ot_date');
            $table->time('start_time');
            $table->time('end_time');

            $table->decimal('break_minutes', 8, 2)->default(0);
            $table->decimal('approved_hours', 8, 2)->default(0);

            $table->enum('status', ['approved', 'cancelled'])->default('approved');
            $table->timestamps();

            $table->index(['employee_id', 'ot_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_overtime_schedules');
    }
};