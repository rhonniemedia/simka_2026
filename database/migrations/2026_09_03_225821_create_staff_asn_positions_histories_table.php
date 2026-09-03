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
        Schema::create('staff_asn_positions_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Foreign key to the main staffs table
            $table->foreignUuid('staff_id')->constrained('staff_data')->cascadeOnDelete();

            // Foreign key to the master staff ASN positions table
            $table->foreignUuid('staff_asn_position_id')->constrained('staff_asn_positions')->cascadeOnDelete();

            $table->string('decree_number', 255);
            $table->date('decree_date');
            $table->date('effective_date');

            // Indicator for the currently active position
            $table->boolean('is_active')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_asn_positions_histories');
    }
};
