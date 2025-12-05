<?php

// En la nueva migración (e.g., 2025_12_05_xxxxxx_add_fk_to_tenants_table.php)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // ✅ AÑADIR LA CLAVE FORÁNEA AHORA QUE AMBAS TABLAS EXISTEN
            $table->foreign('payment_gateways_id')
                  ->references('id')
                  ->on('payment_gateways')
                  ->onDelete('set null'); // Recomendación: usar set null o restrict, ya que un tenant no debería ser eliminado si tiene una gateway asociada.
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['payment_gateways_id']);
        });
    }
};
