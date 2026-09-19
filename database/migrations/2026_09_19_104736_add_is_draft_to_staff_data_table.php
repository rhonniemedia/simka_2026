<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penanda "draft" untuk form Tambah Data Pegawai yang disimpan per step.
 *
 * Kenapa kolom baru, bukan verification_status = 'draft'?
 * Kolom verification_status sudah default 'draft' untuk SEMUA baris
 * (termasuk data lama yang sudah lengkap), jadi tidak bisa dipakai
 * untuk membedakan "form belum selesai diisi" dari "sudah lengkap tapi
 * belum diverifikasi".
 *
 * - false (default) : data lengkap, tampil di daftar & statistik.
 *                     Semua baris lama otomatis masuk kelompok ini.
 * - true            : baru tersimpan sebagian (step 1 s.d. 3 belum
 *                     tuntas). Disembunyikan dari daftar & statistik.
 *                     Berubah jadi false saat step terakhir disimpan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_data', function (Blueprint $table) {
            $table->boolean('is_draft')->default(false)->index();
        });
    }

    public function down(): void
    {
        Schema::table('staff_data', function (Blueprint $table) {
            $table->dropIndex(['is_draft']);
            $table->dropColumn('is_draft');
        });
    }
};
