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
        Schema::create('staff_grade_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('staff_id')
                ->constrained('staff_data')
                ->cascadeOnDelete();

            $table->foreignUuid('grade_id')
                ->constrained('staff_grades');

            $table->string('decree_number')->nullable();          // Nomor SK
            $table->date('decree_date')->nullable();              // Tanggal SK
            $table->date('effective_date');                       // TMT
            $table->unsignedSmallInteger('service_period_years')->nullable(); // Masa kerja (tahun)

            $table->string('approval_reference')->nullable();     // Nomor Pertek
            $table->date('approval_date')->nullable();            // Tanggal Pertek
            $table->string('position_at_time')->nullable();       // Snapshot jabatan saat SK diterbitkan

            // Audit
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('verification_status', ['draft', 'verified', 'rejected'])->default('draft');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_grade_histories');
    }
};
