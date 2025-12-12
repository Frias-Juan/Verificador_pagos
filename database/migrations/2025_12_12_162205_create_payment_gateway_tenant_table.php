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
        Schema::create('payment_gateway_tenant', function (Blueprint $table) {
            $table->id();
            
            // 🚀 CORRECCIÓN 1: Definir la columna como string
            $table->string('tenant_id');
            
            // Clave foránea a la PaymentGateway (Pasarela)
            // Esto asume que payment_gateway_id es un bigInteger, lo cual es estándar.
            $table->foreignId('payment_gateway_id')->constrained()->onDelete('cascade');
            
            // 🚀 CORRECCIÓN 2: Definir la clave foránea para el campo string
            // Asume que la clave primaria en la tabla 'tenants' se llama 'id' (si es un string UUID/slug)
            $table->foreign('tenant_id')
                  ->references('id') // O el nombre real de la clave primaria string en la tabla 'tenants'
                  ->on('tenants')
                  ->onDelete('cascade');
            
            // Asegurar que la combinación sea única
            $table->unique(['tenant_id', 'payment_gateway_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_tenant');
    }
};