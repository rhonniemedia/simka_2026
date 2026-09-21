<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menyesuaikan enum staff_data.status untuk fitur Mutasi:
 *   active, transferred (Pindah), resigned (Mengundurkan Diri),
 *   retired (Pensiun), deceased (Meninggal Dunia), dismissed (Diberhentikan).
 *
 * Nilai lama 'inactive' dihapus dari enum; data lama yang berstatus
 * 'inactive' dipetakan ke 'deceased' (sesuai kesepakatan), karena selama
 * ini 'inactive' dipakai sebagai status "keluar" generik tanpa makna
 * spesifik.
 *
 * Catatan: mengubah definisi ENUM di MySQL harus lewat ALTER TABLE
 * mentah (Schema::table()->enum()->change() tidak bisa mengubah daftar
 * nilai enum yang sudah ada), makanya dua tahap:
 *   1) perluas dulu daftar nilainya (union lama + baru) supaya data lama
 *      tetap valid saat dipetakan,
 *   2) migrasikan datanya,
 *   3) baru persempit ke daftar final.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Perluas sementara: union nilai lama & baru.
        DB::statement("
            ALTER TABLE staff_data
            MODIFY status ENUM('active','inactive','transferred','resigned','retired','deceased','dismissed')
            NOT NULL DEFAULT 'active'
        ");

        // 2. Migrasikan data lama 'inactive' -> 'deceased'.
        DB::table('staff_data')->where('status', 'inactive')->update(['status' => 'deceased']);

        // 3. Persempit ke daftar final.
        DB::statement("
            ALTER TABLE staff_data
            MODIFY status ENUM('active','transferred','resigned','retired','deceased','dismissed')
            NOT NULL DEFAULT 'active'
        ");
    }

    public function down(): void
    {
        // 1. Perluas lagi sementara supaya data baru muat saat dipetakan mundur.
        DB::statement("
            ALTER TABLE staff_data
            MODIFY status ENUM('active','inactive','transferred','resigned','retired','deceased','dismissed')
            NOT NULL DEFAULT 'active'
        ");

        // 2. Kembalikan status yang tidak dikenal skema lama ke 'inactive'.
        DB::table('staff_data')
            ->whereIn('status', ['transferred', 'deceased', 'dismissed'])
            ->update(['status' => 'inactive']);

        // 3. Kembalikan ke daftar lama.
        DB::statement("
            ALTER TABLE staff_data
            MODIFY status ENUM('active','inactive','retired','resigned')
            NOT NULL DEFAULT 'active'
        ");
    }
};
