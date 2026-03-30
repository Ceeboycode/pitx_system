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
        Schema::create('dispatch_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatch_id')->constrained('dispatches')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete(); // Company user requesting change
            $table->string('requested_field'); // 'departed_at', 'driver_user_id', 'pax_count', 'vehicle_id'
            $table->json('old_value')->nullable(); // Previous value
            $table->json('requested_value'); // New requested value
            $table->text('reason'); // Requester's explanation
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); // Internal user who approved/rejected
            $table->text('rejection_reason')->nullable(); // Why it was rejected
            $table->timestamp('approved_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Indices
            $table->index(['dispatch_id', 'status']);
            $table->index('requested_by');
            $table->index('approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispatch_change_requests');
    }
};
