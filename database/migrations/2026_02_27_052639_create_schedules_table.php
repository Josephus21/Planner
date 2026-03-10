<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "Regular 8AM-5PM"

            $table->time('start_time');
            $table->time('end_time');

            $table->time('break_start')->nullable();
            $table->time('break_end')->nullable();

            $table->time('lunch_start')->nullable();
            $table->time('lunch_end')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};