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

            // Menggunakan bahasa Inggris dan disiapkan untuk menyimpan nilai string dari Enum (misal: 'fungsional')
            $table->string('category', 50)->comment('Stores Enum values e.g., fungsional, pelaksana');

            // Penamaan diubah menjadi bahasa Inggris
            $table->string('name', 255)->comment('e.g., Guru Ahli Pertama, Pranata Komputer Terampil');

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
