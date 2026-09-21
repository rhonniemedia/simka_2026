<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak audit setiap perubahan status pegawai lewat fitur Mutasi
 * (aktif -> pindah/resign/meninggal/diberhentikan) maupun Reaktivasi
 * (pindah/resign -> aktif kembali).
 *
 * Tabel ini murni log/audit trail: hanya diisi (create), tidak pernah
 * diedit atau dihapus lewat UI. Status pegawai yang "berlaku" tetap
 * dibaca dari staff_data.status & staff_data.status_effective_date;
 * tabel ini hanya menyimpan riwayat bagaimana status itu berubah dari
 * waktu ke waktu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_status_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('staff_id')->constrained('staff_data')->cascadeOnDelete();

            $table->enum('from_status', ['active', 'transferred', 'resigned', 'retired', 'deceased', 'dismissed'])
                ->comment('Status pegawai sebelum perubahan ini');
            $table->enum('to_status', ['active', 'transferred', 'resigned', 'retired', 'deceased', 'dismissed'])
                ->comment('Status pegawai setelah perubahan ini');

            $table->string('decree_number', 255)->comment('Nomor SK dasar perubahan status');
            $table->date('effective_date')->comment('TMT perubahan status');
            $table->text('note')->nullable();

            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['staff_id', 'effective_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_status_histories');
    }
};
