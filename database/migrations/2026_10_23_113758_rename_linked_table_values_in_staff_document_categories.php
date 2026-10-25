<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Samakan nilai `linked_table` di kategori dokumen dengan nama tabel
 * (bentuk plural), karena `staff_documents.linked_record_type` yang sudah
 * ada memakai nama tabel (mis. 'staff_periodic_salary_histories').
 *
 * Hanya mengubah nilai yang namanya sudah pasti. 'staff_education' dan
 * 'staff_status_history' dikerjakan di migrasi terpisah setelah nama
 * tabelnya dipastikan.
 */
return new class extends Migration
{
    private const TABLE = 'staff_document_categories';

    private const MAP = [
        'staff_grade_history'           => 'staff_grade_histories',
        'staff_periodic_salary_history' => 'staff_periodic_salary_histories',
        'staff_asn_position_history'    => 'staff_asn_positions_histories',
        'staff_family'                  => 'staff_families',
    ];

    public function up(): void
    {
        foreach (self::MAP as $old => $new) {
            DB::table(self::TABLE)
                ->where('linked_table', $old)
                ->update(['linked_table' => $new, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        foreach (self::MAP as $old => $new) {
            DB::table(self::TABLE)
                ->where('linked_table', $new)
                ->update(['linked_table' => $old, 'updated_at' => now()]);
        }
    }
};
