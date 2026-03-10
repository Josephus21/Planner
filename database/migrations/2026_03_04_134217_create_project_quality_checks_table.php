<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_quality_checks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('quality_checklist_id');
            $table->unsignedBigInteger('checked_by')->nullable(); // user id
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'quality_checklist_id']);

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('quality_checklist_id')->references('id')->on('quality_checklists')->onDelete('cascade');
            $table->foreign('checked_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_quality_checks');
    }
};