<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('staff_asn_positions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kategori_jabatan', 100)->comment('Contoh: Jabatan Fungsional, Jabatan Pelaksana');
            $table->string('nama_jabatan', 255)->comment('Contoh: Guru Ahli Pertama, Pranata Komputer Terampil');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_asn_positions');
    }
};
