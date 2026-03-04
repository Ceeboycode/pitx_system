<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('plate_number')->index();
            $table->unsignedInteger('pax_count')->default(0);
            $table->string('bay_number')->nullable()->index();
            $table->text('remarks')->nullable();
            $table->foreignId('dispatcher_user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamp('dispatched_at')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['company_id', 'status']);
            $table->index(['vehicle_id', 'status']);
            $table->index(['dispatcher_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatches');
    }
};
