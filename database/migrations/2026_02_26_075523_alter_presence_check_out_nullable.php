<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('presence', function (Blueprint $table) {
            $table->dateTime('check_out')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('presence', function (Blueprint $table) {
            $table->dateTime('check_out')->nullable(false)->change();
        });
    }
};