<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_bom_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();

            $table->string('section')->nullable();     // ex: CUTTING LIST, ELECTRICAL, etc
            $table->text('item');                      // the line/item text
            $table->string('qty')->nullable();         // optional

            $table->enum('status', ['not_ready', 'ready', 'done'])->default('not_ready');
            $table->timestamp('done_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable(); // auth user id (optional)

            $table->timestamps();

            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_bom_items');
    }
};