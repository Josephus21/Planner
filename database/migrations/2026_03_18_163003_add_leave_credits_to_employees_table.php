<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('sick_leave_balance', 8, 2)->default(0)->after('schedule_id');
            $table->decimal('vacation_leave_balance', 8, 2)->default(0)->after('sick_leave_balance');
            $table->date('leave_credits_last_given_at')->nullable()->after('vacation_leave_balance');
            $table->year('leave_credits_year')->nullable()->after('leave_credits_last_given_at');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'sick_leave_balance',
                'vacation_leave_balance',
                'leave_credits_last_given_at',
                'leave_credits_year',
            ]);
        });
    }
};