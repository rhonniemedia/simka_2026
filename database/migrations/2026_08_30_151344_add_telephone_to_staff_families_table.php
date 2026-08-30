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
        Schema::table('staff_families', function (Blueprint $table) {
            // Menambahkan kolom telephone_encrypted dan telephone_hash
            $table->text('telephone_encrypted')->nullable()->after('birth_date_hash');
            $table->string('telephone_hash', 64)->nullable()->after('telephone_encrypted');

            // Menambahkan index untuk pencarian hash
            $table->index('telephone_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff_families', function (Blueprint $table) {
            // Hapus index terlebih dahulu
            $table->dropIndex(['telephone_hash']);

            // Hapus kolom
            $table->dropColumn(['telephone_encrypted', 'telephone_hash']);
        });
    }
};
