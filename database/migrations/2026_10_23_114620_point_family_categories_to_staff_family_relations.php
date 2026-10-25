<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Kategori dokumen keluarga (Akta Nikah, Akta Anak, Kartu Suami, dst.)
 * ditautkan ke tabel hubungan pegawai-anggota keluarga:
 * `staff_family_relations` (punya staff_id, family_member_id, relationship).
 *
 * Nilai lama 'staff_families' menunjuk ke tabel yang tidak ada lagi
 * (sudah menjadi staff_families_legacy).
 */
return new class extends Migration
{
    private const TABLE = 'staff_document_categories';

    public function up(): void
    {
        DB::table(self::TABLE)
            ->where('linked_table', 'staff_families')
            ->update(['linked_table' => 'staff_family_relations', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table(self::TABLE)
            ->where('linked_table', 'staff_family_relations')
            ->update(['linked_table' => 'staff_families', 'updated_at' => now()]);
    }
};
