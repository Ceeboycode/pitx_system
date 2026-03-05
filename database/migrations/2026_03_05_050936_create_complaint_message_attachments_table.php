<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('complaint_message_attachments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('complaint_message_id')
                ->constrained('complaint_messages')
                ->cascadeOnDelete();

            $table->string('file_path', 255);
            $table->string('original_name', 255);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');

            $table->foreignId('uploaded_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['complaint_message_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_message_attachments');
    }
};