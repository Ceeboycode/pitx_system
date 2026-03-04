<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatches', function (Blueprint $table) {
            $table->timestamp('arrived_at')->nullable()->after('dispatcher_user_id');
            $table->timestamp('departed_at')->nullable()->after('arrived_at');
            $table->index(['company_id', 'arrived_at']);
            $table->index(['company_id', 'departed_at']);
        });
    }

    public function down(): void
    {
        Schema::table('dispatches', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'arrived_at']);
            $table->dropIndex(['company_id', 'departed_at']);

            $table->dropColumn(['arrived_at', 'departed_at']);
        });
    }
};
