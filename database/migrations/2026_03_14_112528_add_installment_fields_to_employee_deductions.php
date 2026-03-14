<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_deductions', function (Blueprint $table) {

            $table->decimal('total_amount', 12, 2)->nullable();
            $table->integer('installment_terms')->nullable(); 
            $table->integer('remaining_terms')->nullable();
            $table->decimal('remaining_balance', 12, 2)->nullable();

        });
    }

    public function down(): void
    {
        Schema::table('employee_deductions', function (Blueprint $table) {

            $table->dropColumn([
                'total_amount',
                'installment_terms',
                'remaining_terms',
                'remaining_balance'
            ]);

        });
    }
};