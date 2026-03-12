<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deduction_types', function (Blueprint $table) {
            $table->string('method')->default('fixed')->after('code');
            $table->string('frequency')->default('per_payroll')->after('method');
        });
    }

    public function down(): void
    {
        Schema::table('deduction_types', function (Blueprint $table) {
            $table->dropColumn(['method', 'frequency']);
        });
    }
};