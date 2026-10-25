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
        Schema::table('staff_grade_histories', function (Blueprint $table) {
            $table->unsignedTinyInteger('service_period_months')
                ->nullable()
                ->after('service_period_years')
                ->comment('Masa kerja kepangkatan dalam bulan (0-11)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff_grade_histories', function (Blueprint $table) {
            $table->dropColumn('service_period_months');
        });
    }
};
