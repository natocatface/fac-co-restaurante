<?php

namespace Database\Seeders;

use App\Models\DocumentSeries;
use Illuminate\Database\Seeder;

class DocumentSeriesSeeder extends Seeder
{
    public function run(): void
    {
        $series = [
            // Comprobantes
            ['document_code' => '03', 'document_type' => 'boleta',       'serie' => 'B001'],
            ['document_code' => '01', 'document_type' => 'factura',      'serie' => 'F001'],

            // Notas de crédito (la serie debe coincidir con el tipo del afectado)
            ['document_code' => '07', 'document_type' => 'nota_credito_boleta',  'serie' => 'BC01'],
            ['document_code' => '07', 'document_type' => 'nota_credito_factura', 'serie' => 'FC01'],
        ];

        foreach ($series as $s) {
            DocumentSeries::updateOrCreate(
                ['serie' => $s['serie']],
                array_merge($s, ['last_number' => 0, 'is_active' => true])
            );
        }
    }
}
