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
        Schema::create('staff_periodic_salary_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('staff_id')
                ->constrained('staff_data')
                ->cascadeOnDelete();

            $table->date('effective_date');                 // TMT
            $table->string('decree_number')->nullable();    // Nomor SK
            $table->date('decree_date')->nullable();        // Tanggal SK
            $table->string('decree_file_id')->nullable();   // File

            $table->text('base_salary_encrypted')->nullable(); // Gaji pokok (terenkripsi)

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
        Schema::dropIfExists('staff_periodic_salary_histories');
    }
};
