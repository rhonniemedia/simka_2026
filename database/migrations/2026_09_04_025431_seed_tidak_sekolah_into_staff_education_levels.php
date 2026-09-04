<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menambahkan baris "Tidak Sekolah" yang belum ada di tabel
     * staff_education_levels. Diberi level '0' agar tampil paling awal,
     * sebelum "Taman Kanak-kanak" (level '1'), saat data diurutkan dengan
     * `orderByRaw('CAST(level AS UNSIGNED) ASC')`.
     */
    public function up(): void
    {
        $alreadyExists = DB::table('staff_education_levels')
            ->where('alias', 'Tidak Sekolah')
            ->orWhere('name', 'Tidak Sekolah')
            ->exists();

        if ($alreadyExists) {
            return;
        }

        DB::table('staff_education_levels')->insert([
            'id'         => (string) Str::uuid(),
            'name'       => 'Tidak Sekolah',
            'alias'      => 'Tidak Sekolah',
            'level'      => '0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('staff_education_levels')
            ->where('name', 'Tidak Sekolah')
            ->where('level', '0')
            ->delete();
    }
};
