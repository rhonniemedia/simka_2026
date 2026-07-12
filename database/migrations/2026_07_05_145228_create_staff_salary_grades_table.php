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
        Schema::create('staff_salary_grades', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employment_id')->constrained('staff_employment_statuses')->cascadeOnDelete(); // jp_id
            $table->foreignUuid('grade_id')->constrained('staff_grades')->cascadeOnDelete();                 // golongan_id
            $table->unsignedInteger('service_period_years'); // mkg
            $table->decimal('base_salary', 12, 2);            // gaji_pokok
            $table->timestamps();

            $table->unique(['employment_id', 'grade_id', 'service_period_years'], 'unique_salary_grade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_salary_grades');
    }
};
