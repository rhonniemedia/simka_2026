<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Simpan TMT status SEBELUM peristiwa (nilai staff_data.status_effective_date
 * saat riwayat dibuat), supaya kalau riwayat dibatalkan/dihapus dan tidak ada
 * riwayat lain, TMT lama pegawai bisa dipulihkan dan tidak hilang.
 */
return new class extends Migration
{
    private const TABLE = 'staff_status_histories';

    public function up(): void
    {
        if (! Schema::hasColumn(self::TABLE, 'previous_effective_date')) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->date('previous_effective_date')->nullable()->after('effective_date');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn(self::TABLE, 'previous_effective_date')) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->dropColumn('previous_effective_date');
            });
        }
    }
};
