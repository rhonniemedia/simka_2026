<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_asn_positions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Kategori jenis jabatan fungsional dan pelaksana
            $table->enum('position_type', [
                'fungsional_keahlian',
                'fungsional_keterampilan',
                'pelaksana'
            ])->comment('Categorizes the non-managerial positions');

            // Peruntukan jabatan (PNS, PPPK, atau keduanya)
            $table->enum('eligibility', ['pns', 'pppk', 'both'])->comment('Specifies if the position is for PNS, PPPK, or both');

            $table->string('name', 255)->comment('e.g., Guru Ahli Pertama, Pranata Komputer Terampil');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_asn_positions');
    }
};
