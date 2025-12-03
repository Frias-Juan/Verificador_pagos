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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_gateway_id');  
            $table->unsignedBigInteger('tenant_id');
            $table->decimal('amount', 10, 2);
            $table->date('payment_date');
            $table->boolean('verified')->default(false);
            $table->timestamps();
            $table->foreign('payment_gateway_id')->references('id')->on('payment_gateways')->cascadeOnDelete();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
