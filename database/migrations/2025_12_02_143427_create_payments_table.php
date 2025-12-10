<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->unsignedBigInteger('payment_gateway_id');
            $table->string('tenant_id');
            
            // Información del pago
            $table->decimal('amount', 10, 2);
            $table->date('payment_date');
            $table->string('remitter');
            $table->string('phone_number')->nullable();
            $table->string('reference');
            $table->string('bank');
            
            // Datos de notificación (para SMS automático)
            $table->text('notification_data')->nullable(); // JSON con datos del SMS
            $table->string('notification_source')->nullable(); // sms, manual, email
            
            // Estado y verificación
            $table->string('status')->default('pending'); 
            $table->boolean('verified')->default(false);
            $table->date('verified_on')->nullable();
            
            $table->timestamps();

            $table->foreign('payment_gateway_id')
                  ->references('id')
                  ->on('payment_gateways')
                  ->onDelete('cascade');
                  
            $table->foreign('tenant_id')
                  ->references('id')
                  ->on('tenants')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};