<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mengubah kolom-kolom staff_data_vault yang tadinya wajib diisi
 * (NOT NULL) menjadi nullable.
 *
 * Latar belakang: form Tambah Data Pegawai sekarang menyimpan progresif
 * per step (lihat DataController::storeStep()) - baris staff_data_vault
 * sudah dibuat sejak Step 1 selesai (hanya berisi NIK, dsb), padahal
 * kolom alamat & telepon baru diisi di Step 3. Kalau kolom-kolom ini
 * tetap NOT NULL, insert baris draft di Step 1 akan langsung gagal di
 * database.
 *
 * Kolom yang TIDAK diubah (tetap wajib/opsional seperti semula):
 * - nik_encrypted / nik_hash: tetap wajib, karena memang diisi di
 *   Step 1 sejak awal, jadi tidak pernah kosong saat baris dibuat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_data_vault', function (Blueprint $table) {
            $table->text('address_encrypted')->nullable()->change();
            $table->text('village_encrypted')->nullable()->change();
            $table->text('district_encrypted')->nullable()->change();
            $table->text('regency_encrypted')->nullable()->change();
            $table->text('province_encrypted')->nullable()->change();
            $table->text('phone_number_encrypted')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Catatan: rollback ini hanya aman kalau TIDAK ada baris draft
        // (belum lengkap) yang tersimpan saat ini - kolom NOT NULL tidak
        // bisa dipasang ulang kalau masih ada baris dengan nilai NULL.
        Schema::table('staff_data_vault', function (Blueprint $table) {
            $table->text('address_encrypted')->nullable(false)->change();
            $table->text('village_encrypted')->nullable(false)->change();
            $table->text('district_encrypted')->nullable(false)->change();
            $table->text('regency_encrypted')->nullable(false)->change();
            $table->text('province_encrypted')->nullable(false)->change();
            $table->text('phone_number_encrypted')->nullable(false)->change();
        });
    }
};
