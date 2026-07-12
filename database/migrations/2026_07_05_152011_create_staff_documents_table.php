<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Relasi Utama
            $table->foreignUuid('staff_id')
                ->constrained('staff_data')
                ->cascadeOnDelete();

            // [REKOMENDASI 1] Perbaikan nama foreign key
            $table->foreignUuid('staff_document_category_id')
                ->constrained('staff_document_categories');

            // Metadata Dokumen SK / Ijazah
            $table->string('document_number')->nullable();
            $table->date('document_date')->nullable();

            // [REKOMENDASI 2] Polymorphic Relation bawaan Laravel
            $table->nullableUuidMorphs('linked_record');

            // Informasi File
            $table->string('document_name');
            $table->string('file_path');

            // [REKOMENDASI 3] Tambahan Metadata File
            $table->string('original_filename')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('file_size')->nullable();

            // Audit Trail
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('verification_status', ['draft', 'verified', 'rejected'])->default('draft');

            $table->timestamps();

            // [REKOMENDASI 4] Lapisan keamanan anti-hilang
            $table->softDeletes();

            // Index
            $table->index(['staff_id', 'staff_document_category_id'], 'idx_staff_category');
            // Index 'linked_record' tidak perlu ditulis manual lagi karena sudah dibuat otomatis oleh nullableUuidMorphs()
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_documents');
    }
};
