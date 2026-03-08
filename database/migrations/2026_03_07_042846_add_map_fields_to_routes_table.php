<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->string('origin_name')->nullable()->after('route_name');
            $table->decimal('origin_lat', 10, 7)->nullable()->after('origin_name');
            $table->decimal('origin_lng', 10, 7)->nullable()->after('origin_lat');

            $table->string('destination_name')->nullable()->after('origin_lng');
            $table->decimal('destination_lat', 10, 7)->nullable()->after('destination_name');
            $table->decimal('destination_lng', 10, 7)->nullable()->after('destination_lat');

            $table->unsignedInteger('distance_meters')->nullable()->after('destination_lng');
            $table->unsignedInteger('duration_seconds')->nullable()->after('distance_meters');

            $table->longText('route_geometry')->nullable()->after('duration_seconds');
        });
    }

    public function down(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->dropColumn([
                'origin_name',
                'origin_lat',
                'origin_lng',
                'destination_name',
                'destination_lat',
                'destination_lng',
                'distance_meters',
                'duration_seconds',
                'route_geometry',
            ]);
        });
    }
};
