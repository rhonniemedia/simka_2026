<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menautkan berkas SK pangkat lama (linked_record_id NULL) ke
 * staff_grade_histories memakai kunci: staff_id + golongan.
 *
 * Berkas lama tidak punya nomor SK, tetapi document_name-nya berisi golongan
 * (mis. "Pangkat IV.b"). Golongan itu dinormalisasi lalu dicocokkan dengan
 * staff_grades.grade_code (mis. "IV/b") milik riwayat pegawai yang sama.
 *
 * Hanya pasangan yang pasti yang ditautkan:
 *  - satu dokumen cocok dengan tepat satu riwayat
 *  - satu riwayat cocok dengan tepat satu dokumen
 *  - riwayat tersebut belum punya berkas SK pangkat aktif
 *
 * File fisik tidak dipindah; file_path tidak diubah.
 * Tidak ada rollback otomatis: backup tabel staff_documents sebelum menjalankan.
 */
return new class extends Migration
{
    private const CATEGORY_CODE = 'sk_pangkat';
    private const HISTORY_TABLE = 'staff_grade_histories';

    /** "Pangkat IV.b" / "IV/b" / "iv b" => "iv/b" */
    private function normalize(?string $value): string
    {
        $value = preg_replace('/^\s*pangkat\s+/i', '', (string) $value);
        $value = preg_replace('/[^a-z0-9]+/i', '/', $value);

        return strtolower(trim($value, '/'));
    }

    public function up(): void
    {
        $categoryId = DB::table('staff_document_categories')
            ->where('code', self::CATEGORY_CODE)
            ->value('id');

        if (! $categoryId) {
            echo "Kategori " . self::CATEGORY_CODE . " tidak ditemukan, dilewati.\n";

            return;
        }

        $documents = DB::table('staff_documents')
            ->where('staff_document_category_id', $categoryId)
            ->whereNull('linked_record_id')
            ->whereNull('deleted_at')
            ->get(['id', 'staff_id', 'document_name']);

        // Riwayat yang sudah punya berkas SK aktif tidak boleh ditimpa
        $historiesWithDoc = DB::table('staff_documents')
            ->where('staff_document_category_id', $categoryId)
            ->where('linked_record_type', self::HISTORY_TABLE)
            ->whereNotNull('linked_record_id')
            ->whereNull('deleted_at')
            ->pluck('linked_record_id')
            ->flip();

        // Peta (staff_id|golongan) => daftar id riwayat
        $historyMap = DB::table(self::HISTORY_TABLE . ' as h')
            ->join('staff_grades as g', 'g.id', '=', 'h.grade_id')
            ->get(['h.id', 'h.staff_id', 'g.grade_code'])
            ->groupBy(fn($h) => $h->staff_id . '|' . $this->normalize($h->grade_code));

        // Tahap 1: dokumen -> riwayat (harus tepat satu)
        $candidates = [];   // doc_id => history_id
        $noGrade = 0;
        $noMatch = 0;
        $ambiguous = 0;

        foreach ($documents as $doc) {
            $grade = $this->normalize($doc->document_name);

            if ($grade === '') {
                $noGrade++;
                continue;
            }

            $matches = $historyMap->get($doc->staff_id . '|' . $grade);

            if (! $matches || $matches->isEmpty()) {
                $noMatch++;
                continue;
            }

            if ($matches->count() > 1) {
                $ambiguous++;
                continue;
            }

            $candidates[$doc->id] = $matches->first()->id;
        }

        // Tahap 2: riwayat -> dokumen (harus tepat satu, dan belum punya berkas)
        $docsPerHistory = array_count_values($candidates);
        $linked = 0;
        $skippedDuplicate = 0;
        $skippedHasDoc = 0;

        DB::transaction(function () use ($candidates, $docsPerHistory, $historiesWithDoc, &$linked, &$skippedDuplicate, &$skippedHasDoc) {
            foreach ($candidates as $docId => $historyId) {
                if ($docsPerHistory[$historyId] > 1) {
                    $skippedDuplicate++;
                    continue;
                }

                if (isset($historiesWithDoc[$historyId])) {
                    $skippedHasDoc++;
                    continue;
                }

                DB::table('staff_documents')
                    ->where('id', $docId)
                    ->update([
                        'linked_record_type' => self::HISTORY_TABLE,
                        'linked_record_id'   => $historyId,
                        'updated_at'         => now(),
                    ]);

                $linked++;
            }
        });

        echo sprintf(
            "[%s] belum bertaut: %d | ditautkan: %d | golongan tak terbaca: %d | tidak ada riwayat cocok: %d | ambigu: %d | satu riwayat >1 dokumen: %d | riwayat sudah punya berkas: %d\n",
            self::CATEGORY_CODE,
            $documents->count(),
            $linked,
            $noGrade,
            $noMatch,
            $ambiguous,
            $skippedDuplicate,
            $skippedHasDoc
        );
    }

    public function down(): void
    {
        //
    }
};
