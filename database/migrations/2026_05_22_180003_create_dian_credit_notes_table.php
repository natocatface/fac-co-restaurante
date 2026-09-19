<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nota Crédito Electrónica DIAN.
 *
 * Catálogo de motivos (DIAN Anexo Técnico 1.8 / cbc:ResponseCode):
 *   1 = Devolución parcial de los bienes y/o no aceptación parcial del servicio
 *   2 = Anulación de factura electrónica
 *   3 = Rebaja o descuento parcial o total
 *   4 = Ajuste de precio
 *   5 = Otros
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dian_credit_notes', function (Blueprint $table) {
            $table->id();

            // Documento afectado (la factura original)
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();

            // Numeración propia de la nota crédito
            $table->string('prefijo', 10);                              // Generalmente NC
            $table->unsignedBigInteger('numero');
            $table->foreignId('dian_resolution_id')->nullable()
                  ->constrained('dian_resolutions')->nullOnDelete();

            // Motivo (Catálogo DIAN)
            $table->unsignedTinyInteger('reason_code');                 // 1-5
            $table->string('reason_description');

            // Importes
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('iva', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);

            // Trazabilidad DIAN
            $table->string('dian_status', 30)->default('PENDING');
            $table->string('cude', 96)->nullable();                     // CUDE (no CUFE) para notas
            $table->string('dian_zip_id', 50)->nullable();
            $table->string('dian_response_code', 10)->nullable();
            $table->text('dian_description')->nullable();
            $table->text('dian_errors')->nullable();
            $table->string('xml_path')->nullable();
            $table->string('ar_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('qr_url', 500)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['prefijo', 'numero'], 'dian_credit_notes_prefijo_numero_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dian_credit_notes');
    }
};
