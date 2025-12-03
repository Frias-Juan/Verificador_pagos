<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDomainsTable extends Migration
{
    public function up(): void
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->id(); // Crea la columna 'id' (BIGINT UNSIGNED)
            
            // Define 'tenant_id' UNA SOLA VEZ, con el tipo correcto (unsignedBigInteger)
            $table->unsignedBigInteger('tenant_id'); 
            
            $table->string('domain', 255)->unique();
            
            $table->timestamps();

            // Referencia la única columna 'tenant_id' definida arriba
            $table->foreign('tenant_id')->references('id')->on('tenants')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
}
