<?php

/**
 * Elimina el esquema de facturación electrónica peruana (SUNAT/Greenter)
 * que existía en col_restaurante_db, para reemplazarlo por el esquema
 * de facturación electrónica colombiana (DIAN).
 *
 * IMPORTANTE: esta migración es IDEMPOTENTE. Verifica con information_schema
 * si el índice, las columnas y las tablas existen ANTES de intentar borrarlas,
 * para que funcione tanto sobre una BD que tenía el esquema SUNAT completo
 * como sobre una BD limpia recién creada por Laravel.
 *
 * Migraciones eliminadas (ya removidas del directorio):
 *  - 2026_05_17_120001_add_sunat_fields_to_orders_table
 *  - 2026_05_17_120002_create_document_series_table
 *  - 2026_05_17_120003_create_credit_notes_table
 *  - 2026_05_17_120004_create_daily_summaries_table
 *
 * También limpia las claves "sunat_*" en la tabla settings.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Tablas SUNAT
        Schema::dropIfExists('credit_notes');
        Schema::dropIfExists('daily_summaries');
        Schema::dropIfExists('document_series');

        // 2) Índice y columnas SUNAT en orders
        if (Schema::hasTable('orders')) {

            // ── 2.a Índice único orders_doc_serie_corr_unique
            if ($this->indexExists('orders', 'orders_doc_serie_corr_unique')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->dropUnique('orders_doc_serie_corr_unique');
                });
            }

            // ── 2.b Columnas SUNAT
            $cols = [
                'serie', 'correlativo',
                'subtotal', 'igv',
                'total_gravada', 'total_exonerada', 'total_inafecta', 'total_gratuita',
                'sunat_status', 'sunat_code', 'sunat_description',
                'xml_path', 'cdr_path', 'pdf_path',
                'hash', 'sent_at',
            ];
            $existing = array_values(array_filter(
                $cols,
                fn ($c) => Schema::hasColumn('orders', $c)
            ));
            if (!empty($existing)) {
                Schema::table('orders', function (Blueprint $table) use ($existing) {
                    $table->dropColumn($existing);
                });
            }
        }

        // 3) Limpiar settings SUNAT
        if (Schema::hasTable('settings')) {
            DB::table('settings')->where('key', 'like', 'sunat_%')->delete();
            DB::table('settings')->where('key', 'igv_factor')->delete();
        }

        // 4) Reset de migraciones SUNAT eliminadas en la tabla migrations
        if (Schema::hasTable('migrations')) {
            DB::table('migrations')
                ->whereIn('migration', [
                    '2026_05_17_120001_add_sunat_fields_to_orders_table',
                    '2026_05_17_120002_create_document_series_table',
                    '2026_05_17_120003_create_credit_notes_table',
                    '2026_05_17_120004_create_daily_summaries_table',
                ])
                ->delete();
        }
    }

    public function down(): void
    {
        // Esta migración es destructiva e irreversible: para volver a SUNAT
        // hay que restaurar desde bk_basededatos_pre_dian.sql.
    }

    /**
     * Comprueba si un índice (Key_name) existe en la tabla indicada.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $rows = DB::select(
            'SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?',
            [$indexName]
        );
        return !empty($rows);
    }
};
