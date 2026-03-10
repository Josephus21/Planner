<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('quality_checklists', function (Blueprint $table) {
            if (!Schema::hasColumn('quality_checklists', 'is_done')) {
                $table->tinyInteger('is_done')->default(0)->after('is_active');
            }
            if (!Schema::hasColumn('quality_checklists', 'done_at')) {
                $table->timestamp('done_at')->nullable()->after('is_done');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quality_checklists', function (Blueprint $table) {
            if (Schema::hasColumn('quality_checklists', 'done_at')) $table->dropColumn('done_at');
            if (Schema::hasColumn('quality_checklists', 'is_done')) $table->dropColumn('is_done');
        });
    }
};