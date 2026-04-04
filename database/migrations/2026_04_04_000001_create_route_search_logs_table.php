<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('route_search_logs', function (Blueprint $table) {
            $table->id();

            // Nullable so logs survive if the user is deleted
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('origin');
            $table->string('destination');

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['origin', 'destination']); // for frequency analytics
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_search_logs');
    }
};
