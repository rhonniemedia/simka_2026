<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_grades', function (Blueprint $table) {
            $table->foreignUuid('grade_group_id')
                ->nullable()
                ->after('level')
                ->constrained('staff_grade_groups')
                ->nullOnDelete();
        });

        // Backfill: 'III.d' -> bagian sebelum titik = 'III'
        DB::statement("
            UPDATE staff_grades g
            JOIN staff_grade_groups gg ON gg.code = SUBSTRING_INDEX(g.grade_code, '.', 1)
            SET g.grade_group_id = gg.id
        ");
    }

    public function down(): void
    {
        Schema::table('staff_grades', function (Blueprint $table) {
            $table->dropConstrainedForeignId('grade_group_id');
        });
    }
};
