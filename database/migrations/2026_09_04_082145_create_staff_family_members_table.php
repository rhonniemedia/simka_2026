<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel ini menyimpan data ORANG (bukan data relasi ke staff).
     * Satu baris = satu orang fisik, dideduplikasi berdasarkan nik_hash.
     * Orang yang sama boleh ditautkan ke banyak staff lewat
     * staff_family_relations (misal: anak dari pasangan suami-istri
     * yang sama-sama menjadi pegawai).
     */
    public function up(): void
    {
        Schema::create('staff_family_members', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name');
            $table->enum('gender', ['L', 'P']);

            $table->text('nik_encrypted')->nullable();
            $table->string('nik_hash', 64)->nullable()->unique();

            $table->text('birth_place_encrypted')->nullable();
            $table->text('birth_date_encrypted')->nullable();
            $table->string('birth_date_hash', 64)->nullable();

            $table->text('telephone_encrypted')->nullable();
            $table->string('telephone_hash', 64)->nullable();

            $table->string('occupation')->nullable();
            $table->foreignUuid('education_level_id')->nullable()->constrained('staff_education_levels');
            $table->boolean('is_studying')->nullable();

            // Diisi kalau orang ini ternyata juga pegawai (NIK cocok dengan staff_data_vault).
            // Berguna untuk deteksi pasangan suami-istri yang sama-sama staff,
            // agar admin bisa menentukan payroll_status di relasinya.
            $table->foreignUuid('linked_staff_id')->nullable()->constrained('staff_data')->nullOnDelete();

            $table->enum('verification_status', ['draft', 'verified', 'rejected'])->default('draft');

            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();

            // Referensi ke id baris asal (staff_families) yang pertama kali
            // membuat orang ini, untuk keperluan audit/traceability.
            $table->string('legacy_id')->nullable()->index();

            $table->timestamps();

            $table->index('birth_date_hash');
            $table->index('telephone_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_family_members');
    }
};
