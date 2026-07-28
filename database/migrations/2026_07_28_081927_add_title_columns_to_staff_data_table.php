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
            // Menambahkan gelar depan dan belakang setelah kolom name
            $table->string('front_title')->nullable()->after('name');
            $table->string('back_title')->nullable()->after('front_title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff_data', function (Blueprint $table) {
            $table->dropColumn(['front_title', 'back_title']);
        });
    }
};
