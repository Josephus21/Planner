<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE projects
            MODIFY COLUMN status
            ENUM('pending','ongoing','on-hold','done','cancelled')
            NOT NULL DEFAULT 'pending'
        ");
    }

    public function down(): void
    {
        // If any rows are on-hold, convert them before shrinking enum
        DB::statement("
            UPDATE projects
            SET status = 'pending'
            WHERE status = 'on-hold'
        ");

        DB::statement("
            ALTER TABLE projects
            MODIFY COLUMN status
            ENUM('pending','ongoing','done','cancelled')
            NOT NULL DEFAULT 'pending'
        ");
    }
};