<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {

            // These already exist in your earlier migration(s) — keep safe checks
            if (!Schema::hasColumn('employees', 'salary_type')) {
                $table->enum('salary_type', ['monthly','daily','hourly'])->default('monthly');
            }

            if (!Schema::hasColumn('employees', 'work_days_per_month')) {
                $table->integer('work_days_per_month')->default(26);
            }

            if (!Schema::hasColumn('employees', 'work_hours_per_day')) {
                $table->decimal('work_hours_per_day', 5, 2)->default(8);
            }

            // OPTIONAL: keep only if you truly want per-employee shift fields
            if (!Schema::hasColumn('employees', 'shift_start')) {
                $table->time('shift_start')->nullable();
            }

            if (!Schema::hasColumn('employees', 'shift_end')) {
                $table->time('shift_end')->nullable();
            }

            // If your intention for this migration was actually to link schedules, do this instead:
            // if (!Schema::hasColumn('employees', 'schedule_id')) {
            //     $table->foreignId('schedule_id')->nullable()->constrained()->nullOnDelete();
            // }
        });
    }

    public function down(): void
    {
        // Usually safer not to drop shared columns that might be owned by earlier migrations.
        // If this migration ONLY introduced schedule_id, then drop schedule_id here.
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'schedule_id')) {
                $table->dropConstrainedForeignId('schedule_id');
            }
        });
    }
};