<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega los campos necesarios para Facturación Electrónica DIAN (Colombia)
 * a la tabla orders.
 *
 * Catálogo de tipos de documento DIAN (cbc:InvoiceTypeCode):
 *   01 = Factura electrónica de venta (la única que emite este POS por ahora)
 *
 * Estados del ciclo DIAN:
 *   PENDING  – aún no enviada
 *   SIGNED   – XML firmado, todavía no enviado
 *   SENT     – enviada al WS de la DIAN, esperando respuesta
 *   ACCEPTED – aceptada (ApplicationResponse OK)
 *   REJECTED – rechazada por DIAN
 *   ERROR    – error técnico (red, firma, etc.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Numeración DIAN
            $table->string('dian_prefijo', 10)->nullable()->after('document_type');   // p.e. SETP, FE
            $table->unsignedBigInteger('dian_numero')->nullable()->after('dian_prefijo'); // consecutivo
            $table->foreignId('dian_resolution_id')->nullable()->after('dian_numero')
                  ->constrained('dian_resolutions')->nullOnDelete();

            // Importes desglosados (Colombia: IVA general 19%)
            $table->decimal('subtotal', 14, 2)->default(0)->after('total');     // Base gravable
            $table->decimal('iva', 14, 2)->default(0)->after('subtotal');       // IVA calculado
            $table->decimal('ico', 14, 2)->default(0)->after('iva');            // Impuesto al Consumo (8% restaurantes)
            $table->decimal('descuento_total', 14, 2)->default(0)->after('ico');
            $table->decimal('total_a_pagar', 14, 2)->default(0)->after('descuento_total');

            // Identificación del adquiriente (datos fiscales)
            $table->string('client_tipo_documento', 5)->nullable()->after('client_document'); // 13=CC, 31=NIT, 22=CE, 41=PA
            $table->string('client_dv', 2)->nullable()->after('client_tipo_documento');      // dígito de verif (sólo NIT)
            $table->string('client_email')->nullable()->after('client_dv');
            $table->string('client_phone', 40)->nullable()->after('client_email');
            $table->string('client_address')->nullable()->after('client_phone');
            $table->string('client_city_code', 10)->nullable()->after('client_address');     // DIVIPOLA
            $table->string('client_dept_code', 10)->nullable()->after('client_city_code');

            // Trazabilidad DIAN
            $table->string('dian_status', 30)->default('PENDING')->after('client_dept_code');
            $table->string('cufe', 96)->nullable()->after('dian_status');          // SHA-384 = 96 hex chars
            $table->string('dian_zip_id', 50)->nullable()->after('cufe');          // Trackid/ZipKey
            $table->string('dian_response_code', 10)->nullable()->after('dian_zip_id');
            $table->text('dian_description')->nullable()->after('dian_response_code');
            $table->text('dian_errors')->nullable()->after('dian_description');    // Errores devueltos por DIAN
            $table->string('xml_path')->nullable()->after('dian_errors');          // XML firmado
            $table->string('ar_path')->nullable()->after('xml_path');              // ApplicationResponse
            $table->string('pdf_path')->nullable()->after('ar_path');              // Representación gráfica
            $table->string('qr_url', 500)->nullable()->after('pdf_path');          // URL del QR DIAN
            $table->timestamp('sent_at')->nullable()->after('qr_url');
            $table->timestamp('accepted_at')->nullable()->after('sent_at');

            $table->unique(['dian_prefijo', 'dian_numero'], 'orders_dian_prefijo_numero_unique');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            try { $table->dropUnique('orders_dian_prefijo_numero_unique'); } catch (\Throwable $e) {}
            try { $table->dropForeign(['dian_resolution_id']); } catch (\Throwable $e) {}
            $table->dropColumn([
                'dian_prefijo', 'dian_numero', 'dian_resolution_id',
                'subtotal', 'iva', 'ico', 'descuento_total', 'total_a_pagar',
                'client_tipo_documento', 'client_dv', 'client_email',
                'client_phone', 'client_address', 'client_city_code', 'client_dept_code',
                'dian_status', 'cufe', 'dian_zip_id', 'dian_response_code',
                'dian_description', 'dian_errors',
                'xml_path', 'ar_path', 'pdf_path', 'qr_url',
                'sent_at', 'accepted_at',
            ]);
        });
    }
};
