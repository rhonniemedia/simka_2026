<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TAHAP 4: lengkapi staff_status_histories agar setara tabel riwayat lain.
 *
 * Tabel ini mencatat perpindahan status pegawai
 * (from_status -> to_status: active, transferred, resigned, retired,
 *  deceased, dismissed). Tabel masih kosong saat migrasi ini dibuat.
 */
return new class extends Migration
{
    private const TABLE = 'staff_status_histories';

    public function up(): void
    {
        // 1. Nomor SK boleh kosong (data lama sering tidak lengkap)
        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->string('decree_number', 255)->nullable()->change();
        });

        // 2. Kolom tambahan
        Schema::table(self::TABLE, function (Blueprint $table) {
            if (! Schema::hasColumn(self::TABLE, 'decree_date')) {
                $table->date('decree_date')->nullable()->after('decree_number');
            }
            if (! Schema::hasColumn(self::TABLE, 'destination')) {
                // Instansi/unit tujuan, dipakai untuk mutasi keluar (transferred)
                $table->string('destination', 255)->nullable()->after('effective_date');
            }
            if (! Schema::hasColumn(self::TABLE, 'updated_by')) {
                $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn(self::TABLE, 'verification_status')) {
                $table->enum('verification_status', ['draft', 'verified', 'rejected'])->default('draft');
            }
        });

        // 3. Index untuk mencari riwayat terbaru per pegawai
        if (! Schema::hasIndex(self::TABLE, ['staff_id', 'effective_date'])) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->index(['staff_id', 'effective_date']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex(self::TABLE, ['staff_id', 'effective_date'])) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->dropIndex(['staff_id', 'effective_date']);
            });
        }

        if (Schema::hasColumn(self::TABLE, 'updated_by')) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->dropConstrainedForeignId('updated_by');
            });
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            foreach (['verification_status', 'destination', 'decree_date'] as $column) {
                if (Schema::hasColumn(self::TABLE, $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // decree_number sengaja tidak dikembalikan ke NOT NULL:
        // baris tanpa nomor SK akan membuat rollback gagal.
    }
};
