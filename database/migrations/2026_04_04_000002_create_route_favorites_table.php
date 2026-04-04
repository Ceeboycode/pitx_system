<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('route_favorites', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // Stored as free text — independent of the routes table
            // so favorites survive route edits/deletions
            $table->string('origin');
            $table->string('destination');

            $table->timestamps();

            // One favorite per origin/destination pair per user
            $table->unique(['user_id', 'origin', 'destination']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_favorites');
    }
};
