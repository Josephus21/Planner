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
       // employee_deductions
Schema::create('employee_deductions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
    $table->foreignId('deduction_type_id')->constrained()->cascadeOnDelete();
    $table->decimal('amount', 12, 2)->nullable(); // for fixed
    $table->decimal('rate', 8, 4)->nullable();    // for percent (0.0500 = 5%)
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->unique(['employee_id','deduction_type_id']); // one per employee
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_deductions');
    }
};
