<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatches', function (Blueprint $table) {
            $table->foreignId('driver_user_id')
                ->nullable()
                ->after('dispatcher_user_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['driver_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('dispatches', function (Blueprint $table) {
            $table->dropIndex(['driver_user_id', 'status']);
            $table->dropConstrainedForeignId('driver_user_id');
        });
    }
};
