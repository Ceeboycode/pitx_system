<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Append 'platform_message' to the existing ENUM without touching existing values
        DB::statement("
            ALTER TABLE crm_threads
            MODIFY COLUMN category
            ENUM('facilities', 'terminal_operations', 'commuter_app', 'other', 'platform_message')
            NOT NULL
        ");
    }

    public function down(): void
    {
        // Remove 'platform_message' — only safe if no rows use it
        DB::statement("
            ALTER TABLE crm_threads
            MODIFY COLUMN category
            ENUM('facilities', 'terminal_operations', 'commuter_app', 'other')
            NOT NULL
        ");
    }
};
