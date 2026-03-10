<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->text('description')->nullable();

            // main project photo
            $table->string('project_image')->nullable();

            // timeline
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();

            // people
            $table->unsignedBigInteger('planner_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();

            // status + progress
            $table->enum('status', ['pending', 'ongoing', 'done', 'cancelled'])->default('pending');
            $table->unsignedTinyInteger('progress')->default(0); // 0-100

            $table->timestamps();

            $table->foreign('planner_id')->references('id')->on('employees')->nullOnDelete();
            $table->foreign('driver_id')->references('id')->on('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};