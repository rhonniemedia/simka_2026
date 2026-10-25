<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Kosongkan `decree_file_id` pada gaji berkala yang menunjuk ke berkas
 * yang tidak ada di `staff_documents` (rujukan putus). Berkasnya sudah
 * tidak ada, jadi isi kolom ini tidak bisa dipakai lagi.
 *
 * Nilai yang dikosongkan dicatat di storage/logs/laravel.log
 * (cari: "[Tahap 3] decree_file_id putus") supaya masih bisa dilacak.
 *
 * Jalankan SEBELUM migrasi yang menghapus kolom decree_file_id.
 */
return new class extends Migration
{
    private const SALARY = 'staff_periodic_salary_histories';

    public function up(): void
    {
        $dangling = DB::table(self::SALARY)
            ->whereNotNull('decree_file_id')
            ->where('decree_file_id', '!=', '')
            ->whereNotIn('decree_file_id', function ($q) {
                $q->select('id')->from('staff_documents');
            });

        // Catat dulu sebelum dikosongkan
        foreach ((clone $dangling)->get(['id', 'staff_id', 'decree_number', 'decree_file_id']) as $row) {
            Log::warning(
                "[Tahap 3] decree_file_id putus dikosongkan: "
                    . "salary_history={$row->id}, staff={$row->staff_id}, "
                    . "no_sk={$row->decree_number}, file_id={$row->decree_file_id}"
            );
        }

        $dangling->update(['decree_file_id' => null]);
    }

    public function down(): void
    {
        // Mengembalikan satu rujukan putus yang diketahui saat migrasi ini dibuat.
        // Berkas tujuannya memang tidak ada, jadi ini hanya memulihkan isi kolom.
        DB::table(self::SALARY)
            ->where('id', 'c4ac521a-5ede-43cf-b36f-2c49b64bd8bd')
            ->whereNull('decree_file_id')
            ->update(['decree_file_id' => 'a09a9cf3-aa2d-4fe7-b733-721f2b76625a']);
    }
};
