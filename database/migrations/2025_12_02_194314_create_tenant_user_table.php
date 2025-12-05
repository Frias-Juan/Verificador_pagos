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
        Schema::create('tenant_user', function (Blueprint $table) {
            // CAMBIO 1: tenant_id como string (no foreignId)
            $table->string('tenant_id');
            
            // CAMBIO 2: user_id como unsignedBigInteger (correcto)
            $table->unsignedBigInteger('user_id');
            
            $table->string('role_in_tenant')->default('admin'); 
            $table->timestamps();

            // Foreign key corregida para tenant_id
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
                
            // Foreign key para user_id
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->unique(['tenant_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_user');
    }
};
