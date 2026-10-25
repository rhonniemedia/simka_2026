<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Lanjutan penyamaan `linked_table` dengan nama tabel sebenarnya.
 * Nama tabel sudah dipastikan lewat SHOW TABLES:
 *   - staff_education_histories
 *   - staff_status_histories
 *
 * Nilai untuk kategori keluarga ('staff_families') BELUM diubah di sini,
 * karena tabel itu tidak ada di database (yang ada: staff_families_legacy
 * dan staff_family_members) dan perlu dikonfirmasi dulu.
 */
return new class extends Migration
{
    private const TABLE = 'staff_document_categories';

    private const MAP = [
        'staff_education'      => 'staff_education_histories',
        'staff_status_history' => 'staff_status_histories',
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
