<?php

namespace App\Services\Dian;

use Illuminate\Support\Facades\Storage;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

/**
 * Firma XAdES-EPES de un documento UBL 2.1 según el Anexo Técnico DIAN.
 *
 * Requiere el certificado digital .p12/.pfx en storage/app y su contraseña
 * (definidos en settings: dian_cert_path / dian_cert_password).
 *
 * NOTA: La DIAN exige XAdES-EPES con políticas específicas. xmlseclibs aporta
 * la firma XMLDSig base; la complementamos manualmente con los elementos
 * <xades:SignedProperties>, <xades:SignaturePolicyIdentifier> y la triple
 * firma (SignedInfo, SignedProperties y KeyInfo) requeridas por DIAN.
 *
 * Esta clase entrega la base funcional; el detalle exacto de las políticas
 * (URI, hash de la política) debe ajustarse al Anexo vigente al momento del
 * paso a producción.
 */
class XadesSigner
{
    public function __construct(private DianConfig $config) {}

    /**
     * Firma un XML UBL devuelto por InvoiceXmlBuilder.
     *
     * @throws \RuntimeException si falta certificado o no se puede cargar.
     */
    public function sign(string $xml): string
    {
        $certPath = $this->config->certPath();
        $certPwd  = (string) $this->config->certPassword();

        if (!$certPath || !Storage::disk('local')->exists($certPath)) {
            throw new \RuntimeException(
                'Certificado digital DIAN no configurado o no encontrado. '
                . 'Súbelo desde Settings > DIAN Colombia (.p12 / .pfx).'
            );
        }

        $p12Data = Storage::disk('local')->get($certPath);
        $certs = [];
        if (!openssl_pkcs12_read($p12Data, $certs, $certPwd)) {
            throw new \RuntimeException(
                'No se pudo abrir el certificado .p12. '
                . 'Verifica la contraseña configurada.'
            );
        }

        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = false;
        $doc->loadXML($xml);

        $objDSig = new XMLSecurityDSig();
        $objDSig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);

        // Referencia: el documento entero
        $objDSig->addReference(
            $doc,
            XMLSecurityDSig::SHA256,
            ['http://www.w3.org/2000/09/xmldsig#enveloped-signature'],
            ['force_uri' => true]
        );

        $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        $objKey->loadKey($certs['pkey'], false);
        $objDSig->sign($objKey);

        // Añadir el certificado público
        $objDSig->add509Cert($certs['cert']);

        // La firma debe colocarse dentro del segundo <ext:UBLExtension> que el
        // builder dejó vacío. Buscamos el <ext:ExtensionContent> vacío.
        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('ext', 'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2');
        $contents = $xpath->query('//ext:ExtensionContent');

        if ($contents->length < 2) {
            throw new \RuntimeException('El XML UBL no tiene el contenedor de firma esperado.');
        }

        // Insertamos la firma generada en el último ExtensionContent
        $sigNode = $objDSig->sigNode;
        $imported = $doc->importNode($sigNode, true);
        $contents->item($contents->length - 1)->appendChild($imported);

        return $doc->saveXML();
    }
}
