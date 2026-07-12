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
        Schema::create('staff_families', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('staff_id')->constrained('staff_data')->cascadeOnDelete();

            $table->string('name');
            $table->enum('relationship', ['spouse', 'child', 'other']);
            $table->string('family_relation_code')->nullable(); // hdk (kode Dapodik)
            $table->enum('gender', ['L', 'P']);

            $table->text('nik_encrypted')->nullable();
            $table->string('nik_hash', 64)->nullable();

            $table->text('birth_place_encrypted')->nullable();
            $table->text('birth_date_encrypted')->nullable();
            $table->string('birth_date_hash', 64)->nullable();

            $table->string('occupation')->nullable(); // pekerjaan, plain untuk statistik
            $table->foreignUuid('education_level_id')->nullable()->constrained('staff_education_levels');
            $table->boolean('is_studying')->nullable(); // status_sekolah: masih sekolah/kuliah atau tidak

            $table->enum('payroll_status', ['included', 'excluded'])->nullable(); // mddg
            $table->text('marriage_date_encrypted')->nullable(); // tgl_perkawinan

            // Audit
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('verification_status', ['draft', 'verified', 'rejected'])->default('draft');

            // Referensi ke id asli di tabel lama (ptk_keluargas), untuk traceability
            // dan agar migrasi data bisa di-rollback secara presisi tanpa truncate.
            $table->string('legacy_id')->nullable()->index();

            $table->timestamps();

            $table->index('birth_date_hash');
            $table->index('nik_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_families');
    }
};
