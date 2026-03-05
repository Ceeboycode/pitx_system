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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->unique();
            $table->string('company_code')->unique();

            $table->string('company_email')->nullable()->index();
            $table->string('company_phone')->nullable()->index();
            $table->string('company_address')->nullable();
            $table->enum('business_type', ['corporate', 'sole_proprietorship'])->nullable()->index();
            $table->string('registration_number')->nullable()->index();
            $table->string('authorized_representative_name')->nullable();
            $table->string('authorized_representative_position')->nullable();
            $table->string('authorized_representative_contact')->nullable();


            $table->enum('status', [
                'draft',
                'docs_completed',
                'for_verification',
                'verified',
                'needs_revision',
                'rejected',
            ])->default('draft')->index();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('deleted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
