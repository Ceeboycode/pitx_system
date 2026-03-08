<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('crm_message_attachments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('thread_id')
                ->constrained('crm_threads')
                ->cascadeOnDelete();

            $table->foreignId('message_id')
                ->constrained('crm_messages')
                ->cascadeOnDelete();

            $table->foreignId('uploaded_by_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);

            $table->timestamps();

            $table->index(['thread_id']);
            $table->index(['message_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_message_attachments');
    }
};