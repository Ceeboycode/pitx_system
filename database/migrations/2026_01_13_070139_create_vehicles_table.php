<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();

            // Company
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();

            // Route
            $table->foreignId('route_id')->nullable()->constrained('routes')->nullOnDelete();

            // Vehicle Info
            $table->string('vehicle_type');           // e.g. Bus, Mini-bus, UV Express
            $table->string('plate_number')->unique();
            $table->string('body_number')->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->string('color')->nullable();
            $table->string('engine_number')->nullable();
            $table->string('chassis_number')->nullable();
            $table->string('make_model')->nullable();       // e.g. Hino / RK8JSKA

            // Status
            $table->string('status')->default('active'); // active, inactive, suspended

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
