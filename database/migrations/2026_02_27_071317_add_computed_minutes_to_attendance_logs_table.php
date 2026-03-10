<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {

            if (!Schema::hasColumn('attendance_logs', 'minutes_worked')) {
                $table->integer('minutes_worked')->default(0);
            }

            if (!Schema::hasColumn('attendance_logs', 'minutes_late')) {
                $table->integer('minutes_late')->default(0);
            }

            if (!Schema::hasColumn('attendance_logs', 'minutes_undertime')) {
                $table->integer('minutes_undertime')->default(0);
            }

            if (!Schema::hasColumn('attendance_logs', 'is_absent')) {
                $table->boolean('is_absent')->default(false);
            }

            if (!Schema::hasColumn('attendance_logs', 'status')) {
                $table->string('status')->default('present');
            }
        });
    }

    public function down(): void
    {
        // Don't drop shared columns here unless this migration truly "owns" them.
        // If you want rollback capability, drop ONLY if you're sure they were created by this migration.
    }
};