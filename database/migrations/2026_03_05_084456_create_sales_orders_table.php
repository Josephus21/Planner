<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();

            // external identifiers
            $table->string('external_id')->unique();     // SO id from API
            $table->string('so_no')->nullable()->index(); // e.g. SO-000123

            // lightweight fields for listing
            $table->string('customer_name')->nullable();
            $table->date('order_date')->nullable();
            $table->string('status')->nullable()->index();
            $table->decimal('total', 14, 2)->nullable();

            // store full payload from API
            $table->json('payload')->nullable();

            // useful for refresh logic
            $table->timestamp('fetched_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
