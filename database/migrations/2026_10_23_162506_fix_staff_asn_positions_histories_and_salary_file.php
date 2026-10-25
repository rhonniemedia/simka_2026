<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * TAHAP 3
 *  1. staff_asn_positions_histories: FK master jabatan cascade -> restrict,
 *     nomor/tanggal SK jadi nullable, tambah kolom audit + verifikasi, tambah index.
 *  2. staff_periodic_salary_histories: hapus decree_file_id
 *     (berkas sekarang disimpan lewat staff_documents).
 *
 * Setiap langkah punya pengaman (hasColumn / cek FK), jadi aman dijalankan ulang
 * kalau sebelumnya berhenti di tengah (DDL MySQL tidak bisa di-rollback otomatis).
 */
return new class extends Migration
{
    private const HISTORIES = 'staff_asn_positions_histories';
    private const SALARY    = 'staff_periodic_salary_histories';

    public function up(): void
    {
        $this->fixPositionHistories();
        $this->dropSalaryFileColumn();
    }

    public function down(): void
    {
        // Hanya mengembalikan kolom yang dihapus. Perubahan FK dan kolom audit
        // sengaja dibiarkan, karena mengembalikannya ke cascade berbahaya.
        if (! Schema::hasColumn(self::SALARY, 'decree_file_id')) {
            Schema::table(self::SALARY, function (Blueprint $table) {
                $table->string('decree_file_id')->nullable()->after('decree_date');
            });
        }
    }

    private function fixPositionHistories(): void
    {
        // 1a. FK master jabatan: cascade -> restrict
        $fk = collect(Schema::getForeignKeys(self::HISTORIES))
            ->first(fn($f) => $f['columns'] === ['staff_asn_position_id']);

        if ($fk && strtolower($fk['on_delete']) === 'cascade') {
            Schema::table(self::HISTORIES, function (Blueprint $table) {
                $table->dropForeign(['staff_asn_position_id']);
            });
            $fk = null;
        }

        if (! $fk) {
            Schema::table(self::HISTORIES, function (Blueprint $table) {
                $table->foreign('staff_asn_position_id')
                    ->references('id')->on('staff_asn_positions')
                    ->restrictOnDelete();
            });
        }

        // 1b. Nomor & tanggal SK boleh kosong (data lama sering tidak lengkap)
        Schema::table(self::HISTORIES, function (Blueprint $table) {
            $table->string('decree_number', 255)->nullable()->change();
            $table->date('decree_date')->nullable()->change();
        });

        // 1c. Kolom audit & verifikasi (sama seperti tabel riwayat lain)
        Schema::table(self::HISTORIES, function (Blueprint $table) {
            if (! Schema::hasColumn(self::HISTORIES, 'created_by')) {
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn(self::HISTORIES, 'updated_by')) {
                $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn(self::HISTORIES, 'verification_status')) {
                $table->enum('verification_status', ['draft', 'verified', 'rejected'])->default('draft');
            }
        });

        // 1d. Index untuk pencarian jabatan aktif per pegawai
        if (! Schema::hasIndex(self::HISTORIES, ['staff_id', 'is_active'])) {
            Schema::table(self::HISTORIES, function (Blueprint $table) {
                $table->index(['staff_id', 'is_active']);
            });
        }
    }

    private function dropSalaryFileColumn(): void
    {
        if (! Schema::hasColumn(self::SALARY, 'decree_file_id')) {
            return;
        }

        // Jangan buang data: hentikan jika ada baris yang decree_file_id-nya
        // terisi tetapi belum punya berkas bertaut di staff_documents.
        $unmigrated = DB::table(self::SALARY . ' as h')
            ->whereNotNull('h.decree_file_id')
            ->where('h.decree_file_id', '!=', '')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('staff_documents as d')
                    ->whereColumn('d.linked_record_id', 'h.id')
                    // Data lama memakai nama tabel (plural), alias baru bentuk tunggal:
                    // keduanya dianggap sudah bertaut.
                    ->whereIn('d.linked_record_type', [
                        'staff_periodic_salary_history',
                        'staff_periodic_salary_histories',
                    ]);
            })
            ->count();

        if ($unmigrated > 0) {
            throw new RuntimeException(
                "{$unmigrated} baris decree_file_id belum dipindahkan ke staff_documents. "
                    . 'Pindahkan dulu sebelum kolom ini dihapus.'
            );
        }

        Schema::table(self::SALARY, function (Blueprint $table) {
            $table->dropColumn('decree_file_id');
        });
    }
};
