<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('company_id');
            $table->string('category_selected');
            $table->string('detected_type')->nullable();
            $table->enum('status', ['MISSING', 'PROCESSING', 'VALID', 'INVALID', 'EXPIRED', 'EXPIRING_SOON', 'MANUAL_REVIEW', 'NEEDS_CONFIRMATION'])
                ->default('PROCESSING');
            $table->json('extracted_data')->nullable();
            $table->text('ai_feedback')->nullable();
            $table->string('source_file_url')->nullable();
            $table->string('file_hash_sha256', 64)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->string('original_filename')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->decimal('ocr_confidence', 5, 2)->nullable();
            $table->decimal('ai_confidence', 5, 2)->nullable();
            $table->string('summary_file_path')->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->index('status');
            $table->unique(['company_id', 'file_hash_sha256'], 'documents_company_filehash_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
