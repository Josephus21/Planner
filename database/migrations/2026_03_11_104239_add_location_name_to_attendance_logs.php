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
        Schema::table('attendance_logs', function (Blueprint $table) {

            $table->string('time_in_location')->nullable();
            $table->string('break_out_location')->nullable();
            $table->string('break_in_location')->nullable();
            $table->string('lunch_out_location')->nullable();
            $table->string('lunch_in_location')->nullable();
            $table->string('time_out_location')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {

            $table->dropColumn([
                'time_in_location',
                'break_out_location',
                'break_in_location',
                'lunch_out_location',
                'lunch_in_location',
                'time_out_location',
            ]);

        });
    }
};