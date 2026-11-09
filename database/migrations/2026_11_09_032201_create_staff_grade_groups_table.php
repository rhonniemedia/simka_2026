<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_grade_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();                                // I, II, III, IV
            $table->unsignedTinyInteger('max_periodic_service_years');       // masa kerja maksimal untuk berkala terakhir
            $table->timestamps();
        });

        $now = now();
        $groups = ['I' => 27, 'II' => 33, 'III' => 32, 'IV' => 32];

        foreach ($groups as $code => $maxYears) {
            DB::table('staff_grade_groups')->insert([
                'id'                         => (string) Str::uuid(),
                'code'                       => $code,
                'max_periodic_service_years' => $maxYears,
                'created_at'                 => $now,
                'updated_at'                 => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_grade_groups');
    }
};