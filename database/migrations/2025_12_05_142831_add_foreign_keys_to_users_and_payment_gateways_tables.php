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
        Schema::create('none', function (Blueprint $table) {
            $table->id();
            // 1. Añadir la clave foránea a la tabla 'users' que referencia a 'payment_gateways'
        // Esto asume que la columna 'payment_gateway_id' ya existe en tu migración original de 'users'.
        if (Schema::hasColumn('users', 'payment_gateway_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('payment_gateway_id')
                      ->nullable() // Si el usuario puede no tener un gateway asignado inicialmente
                      ->constrained('payment_gateways')
                      ->onDelete('cascade'); // O 'set null' si usas nullable()
            });
        }

        // 2. Añadir la clave foránea a la tabla 'payment_gateways' que referencia a 'users'
        // Esto asume que la columna 'user_id' ya existe en tu migración original de 'payment_gateways'.
        if (Schema::hasColumn('payment_gateways', 'user_id')) {
            Schema::table('payment_gateways', function (Blueprint $table) {
                $table->foreignId('user_id')
                      ->nullable() // Un gateway puede existir antes de ser asignado a un usuario específico?
                      ->constrained('users')
                      ->onDelete('cascade');
            });
        }
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('none');
    }
};
