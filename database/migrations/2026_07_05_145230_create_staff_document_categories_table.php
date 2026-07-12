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
        Schema::create('staff_document_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->enum('group', [
                'akta',
                'ijazah',
                'kartu_identitas',
                'pak',
                'sertifikat',
                'sk_berkala',
                'sk_fungsional',
                'sk_pangkat',
                'sk_pengangkatan',
                'skp',
                'spmt',
                'lainnya',
            ])->default('lainnya');
            $table->boolean('requires_document_number')->default(false);
            $table->string('linked_table')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_document_categories');
    }
};
