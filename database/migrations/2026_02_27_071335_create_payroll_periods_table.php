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
    Schema::create('payroll_periods', function (Blueprint $table) {
        $table->id();
        $table->date('date_from');
        $table->date('date_to');
        $table->enum('status', ['draft','posted'])->default('draft');
        $table->timestamps();

        $table->unique(['date_from','date_to']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_periods');
    }
};
