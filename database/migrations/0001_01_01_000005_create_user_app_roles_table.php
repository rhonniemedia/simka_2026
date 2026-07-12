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
        Schema::create('user_app_roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('app_id')->constrained('core_apps')->cascadeOnDelete();
            $table->foreignUuid('role_id')->constrained('user_roles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'app_id', 'role_id'], 'unique_user_app_role');
            $table->index(['user_id', 'app_id'], 'idx_user_app');
            $table->index('app_id', 'idx_app');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_app_roles');
    }
};
