<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Nomor SK pensiun boleh dikosongkan di form Pensiun, jadi kategori
 * 'sk_pensiun' tidak lagi mensyaratkan nomor dokumen (kalau tidak, upload
 * berkas tanpa nomor akan ditolak oleh StaffDocumentService).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('staff_document_categories')
            ->where('code', 'sk_pensiun')
            ->update(['requires_document_number' => false, 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('staff_document_categories')
            ->where('code', 'sk_pensiun')
            ->update(['requires_document_number' => true, 'updated_at' => now()]);
    }
};
