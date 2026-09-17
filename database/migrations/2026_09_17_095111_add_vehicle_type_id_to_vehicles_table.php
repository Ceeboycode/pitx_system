<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add nullable vehicle_type_id
        Schema::table('vehicles', function (Blueprint $table) {
            $table->foreignId('vehicle_type_id')->nullable()->constrained('vehicle_types')->nullOnDelete()->after('route_id');
        });

        // 2. Data Migration
        $vehicles = DB::table('vehicles')->whereNotNull('vehicle_type')->get();
        $typeCache = [];

        foreach ($vehicles as $vehicle) {
            $typeName = $this->normalizeTypeName($vehicle->vehicle_type);
            
            if (!isset($typeCache[$typeName])) {
                $type = DB::table('vehicle_types')->where('type_name', $typeName)->first();
                if (!$type) {
                    $id = DB::table('vehicle_types')->insertGetId([
                        'type_name' => $typeName,
                        'is_active' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $typeCache[$typeName] = $id;
                } else {
                    $typeCache[$typeName] = $type->id;
                }
            }

            DB::table('vehicles')->where('id', $vehicle->id)->update([
                'vehicle_type_id' => $typeCache[$typeName]
            ]);
        }

        // 3. Drop old vehicle_type string column (using a separate Schema::table closure)
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('vehicle_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('vehicle_type')->nullable()->after('route_id');
        });

        // Data migration rollback
        $vehicles = DB::table('vehicles')->whereNotNull('vehicle_type_id')->get();
        foreach ($vehicles as $vehicle) {
            $type = DB::table('vehicle_types')->where('id', $vehicle->vehicle_type_id)->first();
            if ($type) {
                DB::table('vehicles')->where('id', $vehicle->id)->update([
                    'vehicle_type' => $type->type_name
                ]);
            }
        }

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropForeign(['vehicle_type_id']);
            $table->dropColumn('vehicle_type_id');
        });
    }

    private function normalizeTypeName(string $name): string
    {
        $normalized = str_replace('_', ' ', strtolower($name));
        return ucwords($normalized);
    }
};
