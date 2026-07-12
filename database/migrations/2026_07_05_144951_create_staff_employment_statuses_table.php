<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Repo: app-kepegawaian
     * Domain: staff
     * Referensi jenis pegawai (dulu: list_pegawais).
     *
     * Berbeda dari staff_ptk_types di bawah - ini soal STATUS
     * kepegawaian (PNS/PPPK/Honorer/dst), bukan soal Guru vs
     * Tenaga Kependidikan.
     */
    public function up(): void
    {
        Schema::create('staff_employment_statuses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('alias');
            $table->string('code');
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_employment_statuses');
    }
};
