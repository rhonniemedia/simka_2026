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
        Schema::create('staff_grades', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('grade_code');  // golongan, contoh: III/a
            $table->string('grade_name');  // pangkat, contoh: Penata Muda
            $table->string('level');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_grades');
    }
};
