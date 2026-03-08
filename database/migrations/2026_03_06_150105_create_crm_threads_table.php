<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('crm_threads', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('created_by_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('assigned_to_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->enum('category', ['compliance', 'system']);
            $table->string('subject');
            $table->boolean('is_closed')->default(false);
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('last_message_at')->nullable();

            $table->json('details')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'is_closed', 'last_message_at']);
            $table->index(['category', 'created_at']);
            $table->index(['assigned_to_user_id', 'is_closed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_threads');
    }
};
