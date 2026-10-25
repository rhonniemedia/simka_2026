<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Berkas yang `linked_record_*`-nya menunjuk ke baris riwayat yang sudah
 * tidak ada (terhapus permanen) dilepas tautannya, sehingga menjadi berkas
 * "belum bertaut" dan bisa ditautkan ulang dari menu Dokumen.
 *
 * Berkas TIDAK dihapus. Tautan lama dicatat di storage/logs/laravel.log
 * (cari: "[Tahap 5] tautan putus dilepas").
 *
 * Polimorfik tidak punya foreign key, jadi kasus seperti ini bisa terjadi
 * kalau baris induk dihapus lewat jalur yang tidak memicu event model.
 */
return new class extends Migration
{
    private const DOCUMENTS = 'staff_documents';

    /** Sama dengan kunci DocumentCategory::LINKABLE_TABLES */
    private const LINKABLE = [
        'staff_education_histories',
        'staff_family_relations',
        'staff_asn_positions_histories',
        'staff_periodic_salary_histories',
        'staff_grade_histories',
        'staff_status_histories',
    ];

    public function up(): void
    {
        foreach (self::LINKABLE as $table) {
            $orphans = DB::table(self::DOCUMENTS)
                ->where('linked_record_type', $table)
                ->whereNotNull('linked_record_id')
                ->whereNotIn('linked_record_id', function ($q) use ($table) {
                    $q->select('id')->from($table);
                });

            foreach ((clone $orphans)->get(['id', 'staff_id', 'document_name', 'document_number', 'linked_record_id']) as $doc) {
                Log::warning(
                    "[Tahap 5] tautan putus dilepas: document={$doc->id}, staff={$doc->staff_id}, "
                        . "nama={$doc->document_name}, no={$doc->document_number}, "
                        . "dulu={$table}:{$doc->linked_record_id}"
                );
            }

            $orphans->update([
                'linked_record_type' => null,
                'linked_record_id'   => null,
                'updated_at'         => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Tautan lama hanya tercatat di log, tidak bisa dipulihkan otomatis
        // (baris tujuannya memang sudah tidak ada).
    }
};
