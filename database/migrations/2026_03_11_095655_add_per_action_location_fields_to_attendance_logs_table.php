<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $actions = [
                'time_in',
                'break_out',
                'break_in',
                'lunch_out',
                'lunch_in',
                'time_out',
            ];

            foreach ($actions as $action) {
                $table->decimal("{$action}_latitude", 10, 7)->nullable()->after($action);
                $table->decimal("{$action}_longitude", 10, 7)->nullable()->after("{$action}_latitude");
                $table->decimal("{$action}_accuracy", 8, 2)->nullable()->after("{$action}_longitude");
                $table->string("{$action}_ip_address", 45)->nullable()->after("{$action}_accuracy");
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropColumn([
                'time_in_latitude',
                'time_in_longitude',
                'time_in_accuracy',
                'time_in_ip_address',

                'break_out_latitude',
                'break_out_longitude',
                'break_out_accuracy',
                'break_out_ip_address',

                'break_in_latitude',
                'break_in_longitude',
                'break_in_accuracy',
                'break_in_ip_address',

                'lunch_out_latitude',
                'lunch_out_longitude',
                'lunch_out_accuracy',
                'lunch_out_ip_address',

                'lunch_in_latitude',
                'lunch_in_longitude',
                'lunch_in_accuracy',
                'lunch_in_ip_address',

                'time_out_latitude',
                'time_out_longitude',
                'time_out_accuracy',
                'time_out_ip_address',
            ]);
        });
    }
};