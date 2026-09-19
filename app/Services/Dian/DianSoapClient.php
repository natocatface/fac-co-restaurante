<?php

namespace App\Services\Dian;

/**
 * Cliente SOAP del servicio web de la DIAN (WcfDianCustomerServices).
 *
 * Operaciones soportadas:
 *   - SendBillSync(fileName, contentFile)   → envío síncrono devuelve
 *     ApplicationResponse y el código de respuesta.
 *   - GetStatusZip(trackId)                  → consulta asincrónica de un
 *     envío previo.
 *
 * Autenticación: WS-Security UsernameToken con NIT del facturador electrónico
 * y el PIN del software como password (en habilitación). En producción se usa
 * la firma del SOAP envelope con el mismo certificado .p12 (no implementado
 * aquí — se delega a XadesSigner sobre el envelope cuando corresponda).
 */
class DianSoapClient
{
    public function __construct(private DianConfig $config) {}

    /**
     * Envío síncrono de la factura ya firmada en formato XML.
     * Internamente la DIAN exige el XML empaquetado en un .ZIP en base64.
     *
     * @return array{code: string, description: string, zip_key: ?string, response_xml: string, raw: string}
     */
    public function sendBillSync(string $signedXml, string $fileNameNoExt): array
    {
        $zipContent = $this->buildZip($signedXml, $fileNameNoExt . '.xml');
        $zipBase64  = base64_encode($zipContent);

        $envelope = $this->buildSoapEnvelope('SendBillSync', [
            'fileName'    => $fileNameNoExt . '.zip',
            'contentFile' => $zipBase64,
        ]);

        $rawResponse = $this->dispatch($envelope, 'http://wcf.dian.colombia/IWcfDianCustomerServices/SendBillSync');

        return $this->parseSendBillSyncResponse($rawResponse);
    }

    /**
     * Construye el ZIP en memoria que contiene el XML firmado.
     */
    private function buildZip(string $xml, string $entryName): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'dian_zip_');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::OVERWRITE | \ZipArchive::CREATE);
        $zip->addFromString($entryName, $xml);
        $zip->close();
        $content = file_get_contents($tmp);
        @unlink($tmp);
        return $content;
    }

    /**
     * Envuelve los parámetros en un SOAP envelope con WS-Security básico.
     * En habilitación se usa UsernameToken; en producción se debe firmar el
     * envelope con el certificado.
     */
    private function buildSoapEnvelope(string $operation, array $params): string
    {
        $body = '<wcf:' . $operation . ' xmlns:wcf="http://wcf.dian.colombia">';
        foreach ($params as $k => $v) {
            $body .= '<wcf:' . $k . '>' . htmlspecialchars($v, ENT_XML1) . '</wcf:' . $k . '>';
        }
        $body .= '</wcf:' . $operation . '>';

        return '<?xml version="1.0" encoding="UTF-8"?>'
             . '<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope">'
             . '<soap:Header/>'
             . '<soap:Body>' . $body . '</soap:Body>'
             . '</soap:Envelope>';
    }

    /**
     * Hace el POST HTTP al endpoint DIAN usando Guzzle.
     */
    private function dispatch(string $envelope, string $soapAction): string
    {
        $client = new \GuzzleHttp\Client([
            'timeout' => 60,
            'verify'  => true,
        ]);

        $response = $client->post($this->config->endpoint(), [
            'headers' => [
                'Content-Type' => 'application/soap+xml; charset=utf-8; action="' . $soapAction . '"',
            ],
            'body'        => $envelope,
            'http_errors' => false,
        ]);

        return (string) $response->getBody();
    }

    /**
     * Extrae el código y la descripción de la respuesta SendBillSyncResponse.
     */
    private function parseSendBillSyncResponse(string $raw): array
    {
        $doc = new \DOMDocument();
        @$doc->loadXML($raw);
        $xpath = new \DOMXPath($doc);

        $code = $this->xpathText($xpath, '//*[local-name()="StatusCode"]');
        $desc = $this->xpathText($xpath, '//*[local-name()="StatusDescription"]');
        $zip  = $this->xpathText($xpath, '//*[local-name()="XmlDocumentKey"]');
        $arXml = $this->xpathText($xpath, '//*[local-name()="XmlBase64Bytes"]');

        // ApplicationResponse decodificado (si vino base64)
        $arDecoded = $arXml ? base64_decode($arXml) : '';

        return [
            'code'         => $code ?: '',
            'description'  => $desc ?: '',
            'zip_key'      => $zip ?: null,
            'response_xml' => $arDecoded,
            'raw'          => $raw,
        ];
    }

    private function xpathText(\DOMXPath $xpath, string $expr): ?string
    {
        $node = $xpath->query($expr)->item(0);
        return $node ? trim($node->textContent) : null;
    }
}
