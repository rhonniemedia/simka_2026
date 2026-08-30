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
        // 1. Pastikan tidak ada data yang masih tertinggal menggunakan 'spouse'.
        // Data yang tersisa akan diarahkan ke 'other' sebagai langkah pengamanan.
        DB::table('staff_families')
            ->where('relationship', 'spouse')
            ->update(['relationship' => 'other']);

        // 2. Hapus 'spouse' dari ENUM.
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
        // Kembalikan opsi 'spouse' ke dalam ENUM jika sewaktu-waktu di-rollback
        DB::statement("
            ALTER TABLE staff_families 
            MODIFY relationship 
            ENUM('spouse', 'husband', 'wife', 'child', 'other') 
            NOT NULL
        ");

        // Catatan down(): Kita tidak mengembalikan data 'other' ke 'spouse' 
        // karena tidak bisa membedakan mana 'other' yang asli dan mana yang hasil konversi.
    }
};
