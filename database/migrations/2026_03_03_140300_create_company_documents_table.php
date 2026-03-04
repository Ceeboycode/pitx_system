<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('company_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            // Document type controls requirements
            $table->enum('doc_type', [
                'SEC_CERT',
                'DTI_CERT',
                'MAYORS_PERMIT',
                'BIR_2303',
                'AUTHORIZATION_LETTER', // or BOARD_RESOLUTION
            ])->index();

            // Storage info
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();

            // Optional document meta (handy for searching/expiry checks)
            $table->string('document_number')->nullable()->index();
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable()->index();

            // Verification workflow
            $table->enum('status', ['pending', 'verified', 'invalid', 'expired'])
                ->default('pending')
                ->index();

            $table->text('remarks')->nullable();

            // Auditing
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();

            // Prevent duplicates (one current doc per type per company)
            $table->unique(['company_id', 'doc_type']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_documents');
    }
};
