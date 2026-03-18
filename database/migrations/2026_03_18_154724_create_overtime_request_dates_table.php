<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtime_request_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('overtime_request_id')->constrained()->cascadeOnDelete();

            $table->date('ot_date');
            $table->time('start_time');
            $table->time('end_time');

            $table->decimal('break_minutes', 8, 2)->default(0);
            $table->decimal('planned_hours', 8, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_request_dates');
    }
};