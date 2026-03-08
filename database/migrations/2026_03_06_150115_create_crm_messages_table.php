<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('crm_messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('thread_id')
                ->constrained('crm_threads')
                ->cascadeOnDelete();

            $table->foreignId('sender_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->text('body');
            $table->boolean('is_internal')->default(false);

            $table->timestamps();

            $table->index(['thread_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_messages');
    }
};