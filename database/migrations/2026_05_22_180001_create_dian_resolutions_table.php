<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Resolución de numeración DIAN.
 *
 * La DIAN asigna a cada empresa una resolución con:
 *  - Número de resolución
 *  - Prefijo (puede ser vacío en Pro / SETP en Habilitación)
 *  - Rango de numeración consecutiva (desde / hasta)
 *  - Vigencia (fecha desde / hasta)
 *  - Clave técnica (TestSetId / clave de prueba)
 *
 * Se puede tener varias filas (p.e. resolución de PROD, resolución de habilitación
 * y resoluciones históricas) pero solo UNA debe estar activa por ambiente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dian_resolutions', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('environment')->default(2);   // 1=Producción, 2=Habilitación
            $table->string('numero_resolucion', 30);                  // Nº oficial DIAN
            $table->date('fecha_resolucion')->nullable();
            $table->string('prefijo', 10)->default('SETP');           // Prefijo (vacío permitido en POS)
            $table->unsignedBigInteger('rango_desde');
            $table->unsignedBigInteger('rango_hasta');
            $table->date('vigencia_desde')->nullable();
            $table->date('vigencia_hasta')->nullable();
            $table->string('clave_tecnica', 150)->nullable();         // Solo para set de habilitación
            $table->unsignedBigInteger('consecutivo_actual')->default(0); // Último consecutivo emitido
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dian_resolutions');
    }
};
