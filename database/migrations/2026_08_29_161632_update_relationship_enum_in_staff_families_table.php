<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tambahkan nilai sementara 'spouse' tetap dipertahankan
        // agar data lama tidak error saat ALTER ENUM.
        DB::statement("
            ALTER TABLE staff_families
            MODIFY relationship
            ENUM('spouse', 'husband', 'wife', 'child', 'other')
            NOT NULL
        ");

        // 2. Ubah data berdasarkan kode HDK:
        // 1 = Suami
        // 2 = Istri
        // 3 = Anak
        DB::table('staff_families')
            ->where('family_relation_code', '1')
            ->update(['relationship' => 'husband']);

        DB::table('staff_families')
            ->where('family_relation_code', '2')
            ->update(['relationship' => 'wife']);

        DB::table('staff_families')
            ->where('family_relation_code', '3')
            ->update(['relationship' => 'child']);

        // 3. Data lama 'spouse' yang tidak memiliki kode HDK
        // diarahkan ke 'other'.
        DB::table('staff_families')
            ->where('relationship', 'spouse')
            ->update(['relationship' => 'other']);

        // 4. Setelah data bersih, hapus 'spouse' dari ENUM.
        DB::statement("
            ALTER TABLE staff_families
            MODIFY relationship
            ENUM('husband', 'wife', 'child', 'other')
            NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE staff_families
            MODIFY relationship
            ENUM('spouse', 'husband', 'wife', 'child', 'other')
            NOT NULL
        ");

        // Kembalikan husband/wife menjadi spouse
        DB::table('staff_families')
            ->whereIn('relationship', ['husband', 'wife'])
            ->update(['relationship' => 'spouse']);

        DB::statement("
            ALTER TABLE staff_families
            MODIFY relationship
            ENUM('spouse', 'child', 'other')
            NOT NULL
        ");
    }
};
