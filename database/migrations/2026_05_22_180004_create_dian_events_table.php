<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bitácora de eventos / respuestas DIAN para auditoría.
 *
 * Cada vez que se envía un documento o se consulta su estado se registra aquí
 * (timestamps, payload de request/response, código devuelto). Esto facilita
 * el soporte y la trazabilidad ante una inspección.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dian_events', function (Blueprint $table) {
            $table->id();
            $table->string('documentable_type');                      // App\Models\Order o DianCreditNote
            $table->unsignedBigInteger('documentable_id');
            $table->index(['documentable_type', 'documentable_id'], 'dian_events_morph_idx');

            $table->string('event_type', 40);                         // SEND | STATUS | ACCEPT | REJECT | ERROR
            $table->string('response_code', 10)->nullable();
            $table->text('description')->nullable();
            $table->longText('request_payload')->nullable();          // XML/SOAP enviado
            $table->longText('response_payload')->nullable();         // Respuesta cruda

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dian_events');
    }
};
