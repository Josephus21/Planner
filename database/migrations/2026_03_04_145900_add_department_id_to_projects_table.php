<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedBigInteger('department_id')->nullable()->after('planner_id');

            // optional FK if you have departments table
            // $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // optional FK drop if you added it
            // $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });
    }
};