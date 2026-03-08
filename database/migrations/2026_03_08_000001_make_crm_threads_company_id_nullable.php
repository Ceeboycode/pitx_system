<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_threads')) {
            return;
        }

        // Existing databases may already have the column as NOT NULL.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE crm_threads DROP FOREIGN KEY crm_threads_company_id_foreign');
        DB::statement('ALTER TABLE crm_threads MODIFY company_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE crm_threads ADD CONSTRAINT crm_threads_company_id_foreign FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('crm_threads')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE crm_threads DROP FOREIGN KEY crm_threads_company_id_foreign');
        DB::statement('ALTER TABLE crm_threads MODIFY company_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE crm_threads ADD CONSTRAINT crm_threads_company_id_foreign FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE');
    }
};
