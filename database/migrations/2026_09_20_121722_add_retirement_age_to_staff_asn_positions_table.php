<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Batas usia pensiun (tahun) per jabatan ASN.
 *
 * Aturan awal (dipakai untuk mengisi data yang sudah ada):
 *   - Fungsional Keahlian Guru (Guru Ahli Pertama/Muda/Madya/Utama) : 60 tahun
 *   - Selain itu (pelaksana, fungsional keterampilan, keahlian non-guru) : 58 tahun
 *
 * Nilainya disimpan di master supaya pengecualian di kemudian hari cukup
 * diubah di data, tanpa menyentuh kode.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_asn_positions', function (Blueprint $table) {
            $table->unsignedTinyInteger('retirement_age')
                ->default(58)
                ->after('name')
                ->comment('Batas usia pensiun (tahun) untuk jabatan ini');
        });

        // Isi data yang sudah ada sesuai aturan di atas. Baris lain tetap 58 (default).
        DB::table('staff_asn_positions')
            ->where('position_type', 'fungsional_keahlian')
            ->where(function ($query) {
                $query->where('name', 'Guru')
                    ->orWhere('name', 'like', 'Guru %');
            })
            ->update(['retirement_age' => 60]);
    }

    public function down(): void
    {
        Schema::table('staff_asn_positions', function (Blueprint $table) {
            $table->dropColumn('retirement_age');
        });
    }
};
