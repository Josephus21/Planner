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
        Schema::create('job_orders', function (Blueprint $table) {
            $table->id();

            $table->string('external_id')->unique(); // API JO PK
            $table->string('jo_no')->nullable();
            $table->string('so_no')->nullable();

            $table->string('customer_name')->nullable();
            $table->string('prepared_by')->nullable();

            $table->text('description')->nullable();

            $table->string('location')->nullable(); // WAREHOUSE - LFP / WAREHOUSE - DPOD
            $table->string('job_type')->nullable(); // LFP or DPOD

            $table->date('order_date')->nullable();
            $table->date('delivery_date')->nullable();

            $table->string('status')->nullable();
            $table->string('sub_status')->nullable();

            $table->decimal('gp_rate', 10, 2)->nullable();

            $table->json('payload')->nullable(); // full API row

            $table->timestamp('fetched_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_orders');
    }
};