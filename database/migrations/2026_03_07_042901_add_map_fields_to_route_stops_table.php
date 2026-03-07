<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('route_stops', function (Blueprint $table) {
            $table->string('stop_type')->default('stop')->after('stop_name'); // origin, stop, destination, landmark
            $table->string('address')->nullable()->after('stop_type');
            $table->decimal('latitude', 10, 7)->nullable()->after('address');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('mapbox_feature_id')->nullable()->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('route_stops', function (Blueprint $table) {
            $table->dropColumn([
                'stop_type',
                'address',
                'latitude',
                'longitude',
                'mapbox_feature_id',
            ]);
        });
    }
};
