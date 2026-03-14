<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_rest_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('day_name', 20); // monday, tuesday, etc
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['employee_id', 'day_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_rest_days');
    }
};