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
        // deduction_types
Schema::create('deduction_types', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->enum('method', ['fixed','percent'])->default('fixed');
    $table->enum('frequency', ['monthly','per_payroll'])->default('monthly');
    $table->boolean('is_taxable')->default(false); // optional
    $table->timestamps();
});


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deduction_types');
    }
};
