<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Kategori berkas untuk perubahan status pegawai (menu Mutasi), semuanya
 * menaut ke staff_status_histories. Kategori 'sk_mutasi' (Pindah) dan
 * 'sk_pensiun' sudah ada. Dilewati jika `code` sudah ada (aman dijalankan ulang).
 *
 * Group memakai nilai yang sudah ada di enum DocumentGroup
 * ('sk_mutasi' dan 'lainnya'), jadi enum tidak perlu diubah.
 */
return new class extends Migration
{
    private const TABLE = 'staff_document_categories';

    public function up(): void
    {
        $now = now();

        $categories = [
            ['code' => 'sk_pengunduran_diri', 'name' => 'SK Pengunduran Diri', 'group' => 'sk_mutasi'],
            ['code' => 'sk_pemberhentian',    'name' => 'SK Pemberhentian',    'group' => 'sk_mutasi'],
            ['code' => 'surat_kematian',      'name' => 'Surat Kematian',      'group' => 'lainnya'],
            ['code' => 'sk_reaktivasi',       'name' => 'SK Reaktivasi',       'group' => 'sk_mutasi'],
        ];

        foreach ($categories as $c) {
            if (DB::table(self::TABLE)->where('code', $c['code'])->exists()) {
                continue;
            }

            DB::table(self::TABLE)->insert([
                'id'                       => (string) Str::uuid(),
                'name'                     => $c['name'],
                'code'                     => $c['code'],
                'group'                    => $c['group'],
                'requires_document_number' => true,
                'linked_table'             => 'staff_status_histories',
                'is_active'                => true,
                'created_at'               => $now,
                'updated_at'               => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Hanya menghapus kategori yang belum dipakai berkas mana pun.
        foreach (['sk_pengunduran_diri', 'sk_pemberhentian', 'surat_kematian', 'sk_reaktivasi'] as $code) {
            $id = DB::table(self::TABLE)->where('code', $code)->value('id');

            if ($id && ! DB::table('staff_documents')->where('staff_document_category_id', $id)->exists()) {
                DB::table(self::TABLE)->where('id', $id)->delete();
            }
        }
    }
};
