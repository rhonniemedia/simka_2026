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
        Schema::create('staff_education_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Foreign Keys
            $table->foreignUuid('staff_id')
                ->constrained('staff_data')
                ->cascadeOnDelete();

            $table->foreignUuid('education_level_id')
                ->constrained('staff_education_levels');

            // Data Pendidikan
            $table->string('institution_name');                  // sat_pendidikan / Nama institusi
            $table->string('province')->nullable();              // provinsi
            $table->string('major')->nullable();                 // jurusan
            $table->date('graduation_date')->nullable();         // tgl_lulus
            $table->string('certificate_number');                // nomor_ijazah
            $table->date('certificate_date');                    // tgl_ijazah
            $table->string('degree_name')->nullable();           // gelar (nama gelar)
            $table->string('degree_abbreviation')->nullable();   // gelar (singkatan)
            $table->enum('degree_position', ['depan', 'belakang'])->nullable(); // depan_belakang / posisi gelar
            $table->boolean('is_linear')->nullable();            // linier
            // Audit
            $table->foreignUuid('created_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignUuid('updated_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->enum('verification_status', ['draft', 'verified', 'rejected'])
                ->default('draft');                             // status

            $table->timestamps();                               // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_education_histories');
    }
};
