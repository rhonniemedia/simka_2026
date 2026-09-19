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
        Schema::table('staff_data', function (Blueprint $table) {
            // Mengubah kolom yang diisi di Step 2 menjadi nullable agar Step 1 bisa disimpan sebagai draft
            $table->foreignUuid('employment_id')->nullable()->change();
            $table->foreignUuid('personnel_id')->nullable()->change();
            $table->foreignUuid('position_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff_data', function (Blueprint $table) {
            $table->foreignUuid('employment_id')->nullable(false)->change();
            $table->foreignUuid('personnel_id')->nullable(false)->change();
            $table->foreignUuid('position_id')->nullable(false)->change();
        });
    }
};
