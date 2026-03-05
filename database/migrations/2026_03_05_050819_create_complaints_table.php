<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();

            $table->string('reference_no', 30)->unique();

            // Who filed it (commuter)
            $table->foreignId('commuter_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Category
            $table->foreignId('complaint_category_id')
                ->constrained('complaint_categories')
                ->restrictOnDelete();

            // Optional title/summary
            $table->string('subject', 150)->nullable();

            // Main complaint details
            $table->text('description');

            // Optional incident info
            $table->dateTime('incident_at')->nullable();
            $table->string('incident_location', 255)->nullable();

            // Assigned staff (admin/customer relations/terminal staff)
            $table->foreignId('assigned_to_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Workflow
            $table->enum('status', ['submitted', 'under_review', 'resolved', 'rejected'])
                ->default('submitted');

            // Outcome fields
            $table->text('resolution_notes')->nullable();
            $table->string('rejected_reason', 255)->nullable();
            $table->dateTime('resolved_at')->nullable();

            $table->timestamps();

            // Helpful indexes
            $table->index(['status', 'created_at']);
            $table->index(['commuter_user_id', 'created_at']);
            $table->index(['assigned_to_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};