<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('route_stops', function (Blueprint $table) {
            // Only add stop_order if it doesn't already exist
            if (!Schema::hasColumn('route_stops', 'stop_order')) {
                $table->unsignedInteger('stop_order')->default(1)->after('mapbox_feature_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('route_stops', function (Blueprint $table) {
            if (Schema::hasColumn('route_stops', 'stop_order')) {
                $table->dropColumn('stop_order');
            }
        });
    }
};
