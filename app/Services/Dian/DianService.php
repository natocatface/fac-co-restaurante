<?php

namespace App\Services\Dian;

use App\Models\Order;
use App\Models\DianEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Orquestador de la facturación electrónica DIAN para una Order.
 *
 *   build XML  →  firmar XAdES  →  guardar XML  →  enviar SOAP
 *              →  guardar ApplicationResponse  →  actualizar Order
 *
 * Si cualquier paso falla, la Order queda en estado ERROR con la descripción
 * del fallo y un registro en dian_events para soporte.
 */
class DianService
{
    public function __construct(
        private ?DianConfig $config = null,
        private ?InvoiceXmlBuilder $xmlBuilder = null,
        private ?XadesSigner $signer = null,
        private ?DianSoapClient $soap = null,
        private ?CufeGenerator $cufe = null,
    ) {
        $this->config     = $this->config     ?? new DianConfig();
        $this->cufe       = $this->cufe       ?? new CufeGenerator($this->config);
        $this->xmlBuilder = $this->xmlBuilder ?? new InvoiceXmlBuilder($this->config, $this->cufe);
        $this->signer     = $this->signer     ?? new XadesSigner($this->config);
        $this->soap       = $this->soap       ?? new DianSoapClient($this->config);
    }

    /**
     * Procesa el envío de una Factura Electrónica de Venta a la DIAN.
     */
    public function sendInvoice(Order $order): Order
    {
        if (!$order->isElectronic()) {
            return $order;
        }
        if (!$this->config->isConfigured()) {
            $order->update([
                'dian_status'      => 'ERROR',
                'dian_description' => 'DIAN no configurada. Completa la sección Settings > DIAN Colombia.',
            ]);
            return $order;
        }

        try {
            // 1) Generar XML UBL
            $xml = $this->xmlBuilder->build($order);
            $cufe = $this->cufe->forInvoice($order);

            // 2) Firmar XAdES
            $signed = $this->signer->sign($xml);

            // 3) Persistir XML firmado
            $fileNameNoExt = $this->buildFileName($order);
            $xmlPath = 'dian/xml_firmado/' . $fileNameNoExt . '.xml';
            Storage::disk('local')->put($xmlPath, $signed);

            $order->update([
                'cufe'        => $cufe,
                'qr_url'      => $this->config->qrBaseUrl() . $cufe,
                'xml_path'    => $xmlPath,
                'dian_status' => 'SIGNED',
            ]);

            DianEvent::log($order, 'SIGN', null, 'XML firmado correctamente', null, null);

            // 4) Enviar a DIAN (SOAP)
            $result = $this->soap->sendBillSync($signed, $fileNameNoExt);

            // 5) Guardar ApplicationResponse si llegó
            $arPath = null;
            if (!empty($result['response_xml'])) {
                $arPath = 'dian/application_response/AR-' . $fileNameNoExt . '.xml';
                Storage::disk('local')->put($arPath, $result['response_xml']);
            }

            $accepted = in_array($result['code'], ['00', '0', 'Procesado Correctamente'], true);

            $order->update([
                'dian_status'        => $accepted ? 'ACCEPTED' : 'REJECTED',
                'dian_response_code' => $result['code'],
                'dian_description'   => $result['description'],
                'dian_zip_id'        => $result['zip_key'],
                'ar_path'            => $arPath,
                'sent_at'            => now(),
                'accepted_at'        => $accepted ? now() : null,
            ]);

            DianEvent::log(
                $order,
                $accepted ? 'ACCEPT' : 'REJECT',
                $result['code'],
                $result['description'],
                $signed,
                $result['raw']
            );

            return $order->refresh();
        } catch (\Throwable $e) {
            Log::error('DIAN envío falló', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);

            $order->update([
                'dian_status'      => 'ERROR',
                'dian_description' => $e->getMessage(),
            ]);

            DianEvent::log($order, 'ERROR', null, $e->getMessage());
            return $order;
        }
    }

    /**
     * Reintenta el envío de una orden previamente fallida.
     */
    public function retry(Order $order): Order
    {
        if (!$order->isElectronic()) return $order;
        $order->update(['dian_status' => 'PENDING']);
        return $this->sendInvoice($order);
    }

    /**
     * Nombre estándar del archivo DIAN:
     *   <NIT>-<TipoDoc01>-<Prefijo><Numero>
     * Ej.: 900123456-01-SETP1
     */
    private function buildFileName(Order $order): string
    {
        $nit = preg_replace('/\D/', '', (string) $this->config->emisor()['nit']);
        return sprintf('%s-01-%s', $nit, $order->full_number);
    }
}
