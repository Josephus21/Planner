<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quality_checklists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_category_id');
            $table->string('item');                 // checklist label
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_required')->default(false); // optional
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('project_category_id')
                ->references('id')->on('project_categories')
                ->onDelete('cascade');

            $table->index(['project_category_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_checklists');
    }
};