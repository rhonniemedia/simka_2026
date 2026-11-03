<?php

use Database\Seeders\StaffDocumentCategorySeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Kategori legacy. Hasil cek: tidak ada dokumen yang memakainya. */
    private const LEGACY = [
        'ijazah_tk',
        'ijazah_sd',
        'ijazah_smp',
        'ijazah_sma',
        'ijazah_d1',
        'ijazah_d2',
        'ijazah_d3',
        'ijazah_s1',
        'ijazah_s2',
        'ijazah_s3',
        'sk_fungsional',
        'akta_kelahiran_suami',
        'akta_kelahiran_istri',
        'kartu_suami',
        'kartu_istri',
    ];

    public function up(): void
    {
        // 1. Hapus legacy + PAK lama (code NULL). FK staff_documents menolak jika masih terpakai.
        DB::table('staff_document_categories')
            ->whereNull('code')
            ->orWhereIn('code', self::LEGACY)
            ->delete();

        // 2. Tambah kolom baru
        Schema::table('staff_document_categories', function (Blueprint $table) {
            $table->string('group_code', 50)->nullable()->after('code');
            $table->text('description')->nullable()->after('name');
            $table->boolean('requires_document_date')->default(false)->after('requires_document_number');
            $table->unsignedSmallInteger('sort_order')->default(0)->after('requires_document_date');
        });

        // 3. Backfill group_code, deskripsi, sort_order, dan buat kartu_pasangan
        (new StaffDocumentCategorySeeder())->run();

        // 4. Pastikan tidak ada kategori yang terlewat sebelum dikunci
        $orphans = DB::table('staff_document_categories')->whereNull('group_code')->pluck('code')->all();
        if ($orphans) {
            throw new RuntimeException(
                'Kategori belum dipetakan ke group_code: ' . implode(', ', $orphans) . '. Tambahkan ke seeder lalu ulangi.'
            );
        }

        // 5. Finalisasi. MODIFY mentah agar jalan di MySQL 5.7 tanpa doctrine/dbal.
        Schema::table('staff_document_categories', function (Blueprint $table) {
            $table->dropColumn('group');
        });
        DB::statement('ALTER TABLE staff_document_categories MODIFY code VARCHAR(50) NOT NULL');
        DB::statement('ALTER TABLE staff_document_categories MODIFY group_code VARCHAR(50) NOT NULL');
    }

    public function down(): void
    {
        // Baris legacy yang sudah dihapus tidak dikembalikan.
        Schema::table('staff_document_categories', function (Blueprint $table) {
            $table->string('group', 50)->default('lainnya')->after('code');
        });
        DB::statement('UPDATE staff_document_categories SET `group` = group_code');

        Schema::table('staff_document_categories', function (Blueprint $table) {
            $table->dropColumn(['group_code', 'description', 'requires_document_date', 'sort_order']);
        });
        DB::statement('ALTER TABLE staff_document_categories MODIFY code VARCHAR(50) NULL');
    }
};
