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

            $table->foreignUuid('staff_id')
                ->constrained('staff_data')
                ->cascadeOnDelete();

            $table->foreignUuid('education_level_id')
                ->constrained('staff_education_levels');

            $table->string('institution_name');      // Nama institusi
            $table->string('major')->nullable();     // Jurusan
            $table->string('certificate_number');
            $table->date('certificate_date');
            $table->string('degree_title')->nullable(); // Gelar
            $table->boolean('is_linear')->nullable();   // Linier

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
        Schema::dropIfExists('staff_education_histories');
    }
};
