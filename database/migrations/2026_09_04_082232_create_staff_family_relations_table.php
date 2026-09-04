<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel pivot: menautkan satu orang (staff_family_members) ke satu
     * atau lebih staff. Data yang sifatnya "relatif terhadap staff"
     * (relationship, kode HDK, status tunjangan, tanggal nikah) tinggal
     * di sini, bukan di staff_family_members - karena bisa berbeda
     * makna/nilai untuk tiap staff yang menautkannya.
     */
    public function up(): void
    {
        Schema::create('staff_family_relations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('staff_id')->constrained('staff_data')->cascadeOnDelete();
            $table->foreignUuid('family_member_id')->constrained('staff_family_members')->cascadeOnDelete();

            $table->enum('relationship', ['husband', 'wife', 'child', 'other']);
            $table->string('family_relation_code')->nullable(); // kode HDK Dapodik

            $table->enum('payroll_status', ['included', 'excluded'])->nullable();
            $table->text('marriage_date_encrypted')->nullable(); // khusus relationship = husband/wife

            $table->enum('verification_status', ['draft', 'verified', 'rejected'])->default('draft');

            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();

            // Referensi 1:1 ke baris asal di staff_families (legacy).
            $table->string('legacy_id')->nullable()->index();

            $table->timestamps();

            // Satu staff tidak boleh menautkan orang yang sama dua kali.
            $table->unique(['staff_id', 'family_member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_family_relations');
    }
};
