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
        $table->integer('minutes_late')->default(0);
        $table->integer('minutes_undertime')->default(0);
        $table->integer('minutes_worked')->default(0);

        $table->boolean('is_absent')->default(false);
        $table->string('status')->default('present'); // present/absent/leave/holiday
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            //
        });
    }
};
