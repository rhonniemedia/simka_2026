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
        Schema::create('staff_data_vault', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('staff_id')->unique()->constrained('staff_data')->cascadeOnDelete();

            // --- Identitas ---
            $table->text('nik_encrypted');
            $table->string('nik_hash', 64)->unique();

            // NIP ditambahkan ke Vault
            $table->text('nip_encrypted')->nullable();
            $table->string('nip_hash', 64)->nullable()->unique();

            $table->text('nuptk_encrypted')->nullable();
            $table->string('nuptk_hash', 64)->nullable();

            $table->text('pob_encrypted')->nullable(); // t_lahir

            $table->text('dob_encrypted')->nullable();  // tgl_lahir
            $table->string('dob_hash', 64)->nullable();

            $table->text('religion_encrypted')->nullable();
            $table->string('religion_hash', 64)->nullable();

            // --- Finansial ---
            $table->text('npwp_encrypted')->nullable();
            $table->text('bank_account_encrypted')->nullable(); // no_rek
            $table->text('base_salary_encrypted')->nullable();  // besaran_gaji

            // --- Kontak ---
            $table->text('phone_number_encrypted');
            $table->string('phone_number_hash', 64)->nullable();

            $table->text('email_encrypted')->nullable();
            $table->string('email_hash', 64)->nullable();

            // --- Alamat terstruktur ---
            $table->text('address_encrypted');        // a_jalan
            $table->text('rt_encrypted')->nullable();
            $table->text('rw_encrypted')->nullable();
            $table->text('village_encrypted');         // a_desa_kelurahan

            $table->text('district_encrypted');        // a_kecamatan
            $table->string('district_hash', 64)->nullable();

            $table->text('regency_encrypted');          // a_kabupaten_kota
            $table->text('province_encrypted');         // a_provinsi

            // Audit
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('verification_status', ['draft', 'verified', 'rejected'])->default('draft');

            $table->timestamps();

            // Indexing (Unique constraints otomatis membuat index, tapi jika NUPTK/lainnya bukan unique, tetap butuh index)
            $table->index('nik_hash');
            $table->index('nip_hash');
            $table->index('nuptk_hash');
            $table->index('dob_hash');
            $table->index('religion_hash');
            $table->index('phone_number_hash');
            $table->index('email_hash');
            $table->index('district_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_data_vaults');
    }
};
