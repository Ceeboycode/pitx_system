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
        $creatorId = DB::table('users')->orderBy('id')->value('id');
        $unknownType = DB::table('vehicle_types')
            ->whereRaw('LOWER(TRIM(type_name)) = ?', ['unknown'])
            ->first();

        if (! $unknownType && DB::table('vehicles')->whereNull('vehicle_type_id')->exists()) {
            $unknownTypeId = DB::table('vehicle_types')->insertGetId([
                'type_name' => 'Unknown',
                'is_active' => false,
                'created_by' => $creatorId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $unknownTypeId = $unknownType?->id;
        }

        if ($unknownTypeId) {
            DB::table('vehicles')
                ->whereNull('vehicle_type_id')
                ->update(['vehicle_type_id' => $unknownTypeId]);
        }

        Schema::table('vehicles', function (Blueprint $table): void {
            $table->dropForeign(['vehicle_type_id']);
            $table->foreign('vehicle_type_id')
                ->references('id')
                ->on('vehicle_types')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->dropForeign(['vehicle_type_id']);
            $table->foreign('vehicle_type_id')
                ->references('id')
                ->on('vehicle_types')
                ->nullOnDelete();
        });
    }
};
