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
        Schema::create('staff_concentration_authorizations', function (Blueprint $table) {
            // Relasi ke tabel staff_data dan core_concentrations
            $table->foreignUuid('staff_id')->constrained('staff_data')->cascadeOnDelete();
            $table->foreignUuid('concentration_id')->constrained('core_concentrations')->cascadeOnDelete();

            // Primary key gabungan agar tidak ada duplikasi data (Si A tidak bisa di-assign 2x ke RPL)
            $table->primary(['staff_id', 'concentration_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_concentration_authorizations');
    }
};
