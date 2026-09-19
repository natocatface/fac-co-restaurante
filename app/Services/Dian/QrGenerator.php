<?php

namespace App\Services\Dian;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

/**
 * Genera el código QR exigido en la representación gráfica de la
 * factura electrónica DIAN.
 *
 * El contenido del QR es la URL pública de consulta del documento
 * en el catálogo VPFE (catálogo Habilitación / Producción).
 */
class QrGenerator
{
    /**
     * Devuelve un Data URI (base64) listo para incrustar en un <img src=".."> del PDF.
     */
    public function dataUri(string $qrUrl, int $size = 220): string
    {
        $result = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($qrUrl)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::Medium)
            ->size($size)
            ->margin(5)
            ->build();

        return $result->getDataUri();
    }
}
