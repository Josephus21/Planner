<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {

            // ? category
            $table->unsignedBigInteger('category_id')->nullable()->after('driver_id');

            // ? vehicle used
            $table->string('vehicle_used')->nullable()->after('category_id');

            // ? permit
            $table->boolean('needs_permit')->default(false)->after('vehicle_used');
            $table->string('permit_path')->nullable()->after('needs_permit');

            // ? safety officer
            $table->boolean('needs_safety_officer')->default(false)->after('permit_path');
            $table->unsignedBigInteger('safety_officer_id')->nullable()->after('needs_safety_officer');

            // foreign keys
            $table->foreign('category_id')->references('id')->on('project_categories')->nullOnDelete();
            $table->foreign('safety_officer_id')->references('id')->on('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {

            // drop FKs first
            try { $table->dropForeign(['category_id']); } catch (\Throwable $e) {}
            try { $table->dropForeign(['safety_officer_id']); } catch (\Throwable $e) {}

            $table->dropColumn([
                'category_id',
                'vehicle_used',
                'needs_permit',
                'permit_path',
                'needs_safety_officer',
                'safety_officer_id',
            ]);
        });
    }
};