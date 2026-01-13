<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('plate_number')->unique();
            $table->string('body_number')->unique();
            $table->foreignId('vehicle_type_id')
                ->constrained('vehicle_types')
                ->onDelete('cascade');
            $table->foreignId('company_id')
                ->constrained('companies')
                ->onDelete('cascade');
            $table->foreignId('route_id')
                ->constrained('routes')
                ->onDelete('cascade');
            $table->integer('capacity');

            // $table->foreignId('user_id')
            //     ->constrained('users')
            //     ->onDelete('cascade');
                
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
