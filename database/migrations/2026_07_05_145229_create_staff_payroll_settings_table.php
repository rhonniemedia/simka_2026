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
        Schema::create('staff_payroll_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('treasurer')->nullable();  // bendahara
            $table->string('pns_payment_reference')->nullable();  // pp_pns
            $table->string('pppk_payment_reference')->nullable(); // pp_pppk
            $table->date('kp4_form_date')->nullable();            // tgl_kp4
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_payroll_settings');
    }
};
