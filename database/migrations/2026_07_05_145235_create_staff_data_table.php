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
        Schema::create('staff_data', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();

            $table->foreignUuid('employment_id')->constrained('staff_employment_statuses'); // jp_id
            $table->foreignUuid('personnel_id')->constrained('staff_personnel_types');        // jptk_id
            $table->foreignUuid('position_id')->constrained('staff_positions');        // jab_id
            $table->foreignUuid('concentration_id')->nullable()->constrained('core_concentrations');

            // Masa kerja tambahan sebelum PNS (peninjauan masa kerja)
            $table->string('prior_service_period')->nullable();             // pmk
            $table->date('prior_service_period_effective_date')->nullable(); // tmt_mk

            $table->enum('gender', ['L', 'P']);
            $table->string('marital_dependents')->nullable(); // kawin_tanggungan, contoh: K/2

            $table->string('photo')->nullable();
            $table->enum('status', ['active', 'inactive', 'retired', 'resigned'])->default('active');
            $table->date('status_effective_date')->nullable(); // tmt_status

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
        Schema::dropIfExists('staff_data');
    }
};
