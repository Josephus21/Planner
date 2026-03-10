<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            // JO fields
            $table->string('jo_no')->nullable()->index()->after('external_id');

            // Additional listing fields
            $table->string('sub_status')->nullable()->index()->after('status');
            $table->date('delivery_date')->nullable()->index()->after('order_date');

            $table->string('prepared_by')->nullable()->index()->after('customer_name');
            $table->text('description')->nullable()->after('prepared_by');

            $table->decimal('gp_rate', 18, 6)->nullable()->after('total');
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn([
                'jo_no',
                'sub_status',
                'delivery_date',
                'prepared_by',
                'description',
                'gp_rate',
            ]);
        });
    }
};