<?php

namespace App\Services\Dian;

use App\Models\Order;
use Carbon\Carbon;

/**
 * Construye el XML UBL 2.1 de una Factura Electrónica de Venta DIAN,
 * según el Anexo Técnico Caja de Herramientas Facturación Electrónica
 * (cbc:CustomizationID=10, UBL 2.1 Colombia DIAN v1.0).
 *
 * El XML se devuelve SIN firmar; la firma XAdES la añade XadesSigner.
 */
class InvoiceXmlBuilder
{
    public function __construct(
        private DianConfig $config,
        private CufeGenerator $cufeGen,
    ) {}

    public function build(Order $order): string
    {
        $emisor = $this->config->emisor();
        $sw     = $this->config->software();

        $cufe = $this->cufeGen->forInvoice($order);
        $order->cufe = $cufe;   // Sólo cacheo en memoria; el servicio lo persiste

        $now      = Carbon::parse($order->created_at);
        $issueDate = $now->format('Y-m-d');
        $issueTime = $now->format('H:i:s-05:00');

        $lines = $this->buildLines($order);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>' . PHP_EOL;
        $xml .= '<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"' . PHP_EOL;
        $xml .= '         xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"' . PHP_EOL;
        $xml .= '         xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2"' . PHP_EOL;
        $xml .= '         xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2"' . PHP_EOL;
        $xml .= '         xmlns:sts="dian:gov:co:facturaelectronica:Structures-2-1"' . PHP_EOL;
        $xml .= '         xmlns:xades="http://uri.etsi.org/01903/v1.3.2#">' . PHP_EOL;

        // UBLExtensions: contenedor para la firma (la rellena XadesSigner)
        $xml .= '  <ext:UBLExtensions>' . PHP_EOL;
        $xml .= $this->dianExtension($order, $cufe);
        $xml .= '    <ext:UBLExtension>' . PHP_EOL;
        $xml .= '      <ext:ExtensionContent></ext:ExtensionContent>' . PHP_EOL;
        $xml .= '    </ext:UBLExtension>' . PHP_EOL;
        $xml .= '  </ext:UBLExtensions>' . PHP_EOL;

        $xml .= '  <cbc:UBLVersionID>UBL 2.1</cbc:UBLVersionID>' . PHP_EOL;
        $xml .= '  <cbc:CustomizationID>10</cbc:CustomizationID>' . PHP_EOL;
        $xml .= '  <cbc:ProfileID>DIAN 2.1</cbc:ProfileID>' . PHP_EOL;
        $xml .= '  <cbc:ProfileExecutionID>' . $this->config->environment() . '</cbc:ProfileExecutionID>' . PHP_EOL;
        $xml .= '  <cbc:ID>' . htmlspecialchars($order->full_number) . '</cbc:ID>' . PHP_EOL;
        $xml .= '  <cbc:UUID schemeID="' . $this->config->environment() . '" schemeName="CUFE-SHA384">' . $cufe . '</cbc:UUID>' . PHP_EOL;
        $xml .= '  <cbc:IssueDate>' . $issueDate . '</cbc:IssueDate>' . PHP_EOL;
        $xml .= '  <cbc:IssueTime>' . $issueTime . '</cbc:IssueTime>' . PHP_EOL;
        $xml .= '  <cbc:InvoiceTypeCode>01</cbc:InvoiceTypeCode>' . PHP_EOL;
        $xml .= '  <cbc:Note>Factura Electrónica de Venta</cbc:Note>' . PHP_EOL;
        $xml .= '  <cbc:DocumentCurrencyCode>COP</cbc:DocumentCurrencyCode>' . PHP_EOL;
        $xml .= '  <cbc:LineCountNumeric>' . $order->details->count() . '</cbc:LineCountNumeric>' . PHP_EOL;

        // Bloques principales
        $xml .= $this->supplierParty($emisor);
        $xml .= $this->customerParty($order);
        $xml .= $this->paymentMeans($order);
        $xml .= $this->taxTotals($order);
        $xml .= $this->legalMonetaryTotal($order);
        $xml .= $lines;

        $xml .= '</Invoice>' . PHP_EOL;
        return $xml;
    }

    /**
     * Bloque DIAN obligatorio dentro de UBLExtensions: software ID, software
     * security code (HASH del NIT+ID software+PIN) y QR.
     */
    private function dianExtension(Order $order, string $cufe): string
    {
        $emisor = $this->config->emisor();
        $sw     = $this->config->software();

        $softwareSecurityCode = hash('sha384',
            ($sw['id'] ?? '') . ($sw['pin'] ?? '') . $order->full_number
        );

        $xml  = '    <ext:UBLExtension>' . PHP_EOL;
        $xml .= '      <ext:ExtensionContent>' . PHP_EOL;
        $xml .= '        <sts:DianExtensions>' . PHP_EOL;
        $xml .= '          <sts:InvoiceControl>' . PHP_EOL;
        $xml .= '            <sts:InvoiceAuthorization>' . htmlspecialchars(
            (string) ($this->config->get('dian_resolucion_numero') ?? '')
        ) . '</sts:InvoiceAuthorization>' . PHP_EOL;
        $xml .= '            <sts:AuthorizationPeriod>' . PHP_EOL;
        $xml .= '              <cbc:StartDate>' . htmlspecialchars(
            (string) ($this->config->get('dian_resolucion_fecha_desde') ?? '2024-01-01')
        ) . '</cbc:StartDate>' . PHP_EOL;
        $xml .= '              <cbc:EndDate>' . htmlspecialchars(
            (string) ($this->config->get('dian_resolucion_fecha_hasta') ?? '2030-12-31')
        ) . '</cbc:EndDate>' . PHP_EOL;
        $xml .= '            </sts:AuthorizationPeriod>' . PHP_EOL;
        $xml .= '            <sts:AuthorizedInvoices>' . PHP_EOL;
        $xml .= '              <sts:Prefix>' . htmlspecialchars((string) $order->dian_prefijo) . '</sts:Prefix>' . PHP_EOL;
        $xml .= '              <sts:From>' . htmlspecialchars((string) ($this->config->get('dian_resolucion_rango_desde') ?? '1')) . '</sts:From>' . PHP_EOL;
        $xml .= '              <sts:To>' . htmlspecialchars((string) ($this->config->get('dian_resolucion_rango_hasta') ?? '5000000')) . '</sts:To>' . PHP_EOL;
        $xml .= '            </sts:AuthorizedInvoices>' . PHP_EOL;
        $xml .= '          </sts:InvoiceControl>' . PHP_EOL;
        $xml .= '          <sts:InvoiceSource><cbc:IdentificationCode listAgencyID="6" listAgencyName="United Nations Economic Commission for Europe" listSchemeURI="urn:oasis:names:specification:ubl:codelist:gc:CountryIdentificationCode-2.0">CO</cbc:IdentificationCode></sts:InvoiceSource>' . PHP_EOL;
        $xml .= '          <sts:SoftwareProvider>' . PHP_EOL;
        $xml .= '            <sts:ProviderID schemeID="' . htmlspecialchars((string) ($emisor['dv'] ?? '0')) . '" schemeAgencyID="195" schemeAgencyName="CO, DIAN (Dirección de Impuestos y Aduanas Nacionales)" schemeName="31">' . htmlspecialchars((string) $emisor['nit']) . '</sts:ProviderID>' . PHP_EOL;
        $xml .= '            <sts:SoftwareID schemeAgencyID="195" schemeAgencyName="CO, DIAN (Dirección de Impuestos y Aduanas Nacionales)">' . htmlspecialchars((string) ($sw['id'] ?? '')) . '</sts:SoftwareID>' . PHP_EOL;
        $xml .= '          </sts:SoftwareProvider>' . PHP_EOL;
        $xml .= '          <sts:SoftwareSecurityCode schemeAgencyID="195" schemeAgencyName="CO, DIAN (Dirección de Impuestos y Aduanas Nacionales)">' . $softwareSecurityCode . '</sts:SoftwareSecurityCode>' . PHP_EOL;
        $xml .= '          <sts:AuthorizationProvider><sts:AuthorizationProviderID schemeAgencyID="195" schemeAgencyName="CO, DIAN (Dirección de Impuestos y Aduanas Nacionales)" schemeID="4" schemeName="31">800197268</sts:AuthorizationProviderID></sts:AuthorizationProvider>' . PHP_EOL;
        $xml .= '          <sts:QRCode>' . htmlspecialchars($this->config->qrBaseUrl() . $cufe) . '</sts:QRCode>' . PHP_EOL;
        $xml .= '        </sts:DianExtensions>' . PHP_EOL;
        $xml .= '      </ext:ExtensionContent>' . PHP_EOL;
        $xml .= '    </ext:UBLExtension>' . PHP_EOL;
        return $xml;
    }

    private function supplierParty(array $emisor): string
    {
        $xml  = '  <cac:AccountingSupplierParty>' . PHP_EOL;
        $xml .= '    <cbc:AdditionalAccountID>' . htmlspecialchars((string) $emisor['tipo_persona']) . '</cbc:AdditionalAccountID>' . PHP_EOL;
        $xml .= '    <cac:Party>' . PHP_EOL;
        $xml .= '      <cac:PartyName><cbc:Name>' . htmlspecialchars((string) ($emisor['nombre_comercial'] ?: $emisor['razon_social'])) . '</cbc:Name></cac:PartyName>' . PHP_EOL;
        $xml .= '      <cac:PhysicalLocation><cac:Address>' . PHP_EOL;
        $xml .= '        <cbc:ID>' . htmlspecialchars((string) $emisor['city_code']) . '</cbc:ID>' . PHP_EOL;
        $xml .= '        <cbc:CityName>' . htmlspecialchars((string) $emisor['municipio_nombre']) . '</cbc:CityName>' . PHP_EOL;
        $xml .= '        <cbc:CountrySubentityCode>' . htmlspecialchars((string) $emisor['dept_code']) . '</cbc:CountrySubentityCode>' . PHP_EOL;
        $xml .= '        <cac:AddressLine><cbc:Line>' . htmlspecialchars((string) $emisor['address']) . '</cbc:Line></cac:AddressLine>' . PHP_EOL;
        $xml .= '        <cac:Country><cbc:IdentificationCode>' . htmlspecialchars((string) $emisor['country_code']) . '</cbc:IdentificationCode></cac:Country>' . PHP_EOL;
        $xml .= '      </cac:Address></cac:PhysicalLocation>' . PHP_EOL;
        $xml .= '      <cac:PartyTaxScheme>' . PHP_EOL;
        $xml .= '        <cbc:RegistrationName>' . htmlspecialchars((string) $emisor['razon_social']) . '</cbc:RegistrationName>' . PHP_EOL;
        $xml .= '        <cbc:CompanyID schemeAgencyID="195" schemeAgencyName="CO, DIAN (Dirección de Impuestos y Aduanas Nacionales)" schemeID="' . htmlspecialchars((string) $emisor['dv']) . '" schemeName="' . htmlspecialchars((string) $emisor['tipo_documento']) . '">' . htmlspecialchars((string) $emisor['nit']) . '</cbc:CompanyID>' . PHP_EOL;
        $xml .= '        <cbc:TaxLevelCode listName="' . htmlspecialchars((string) $emisor['responsabilidad_fiscal']) . '">' . htmlspecialchars((string) $emisor['responsabilidad_fiscal']) . '</cbc:TaxLevelCode>' . PHP_EOL;
        $xml .= '        <cac:TaxScheme><cbc:ID>01</cbc:ID><cbc:Name>IVA</cbc:Name></cac:TaxScheme>' . PHP_EOL;
        $xml .= '      </cac:PartyTaxScheme>' . PHP_EOL;
        $xml .= '      <cac:PartyLegalEntity>' . PHP_EOL;
        $xml .= '        <cbc:RegistrationName>' . htmlspecialchars((string) $emisor['razon_social']) . '</cbc:RegistrationName>' . PHP_EOL;
        $xml .= '        <cbc:CompanyID schemeAgencyID="195" schemeAgencyName="CO, DIAN (Dirección de Impuestos y Aduanas Nacionales)" schemeID="' . htmlspecialchars((string) $emisor['dv']) . '" schemeName="' . htmlspecialchars((string) $emisor['tipo_documento']) . '">' . htmlspecialchars((string) $emisor['nit']) . '</cbc:CompanyID>' . PHP_EOL;
        $xml .= '        <cac:CorporateRegistrationScheme><cbc:ID>' . htmlspecialchars((string) $emisor['nit']) . '</cbc:ID></cac:CorporateRegistrationScheme>' . PHP_EOL;
        $xml .= '      </cac:PartyLegalEntity>' . PHP_EOL;
        $xml .= '      <cac:Contact><cbc:ElectronicMail>' . htmlspecialchars((string) $emisor['email']) . '</cbc:ElectronicMail></cac:Contact>' . PHP_EOL;
        $xml .= '    </cac:Party>' . PHP_EOL;
        $xml .= '  </cac:AccountingSupplierParty>' . PHP_EOL;
        return $xml;
    }

    private function customerParty(Order $order): string
    {
        $name    = $order->client_name ?: 'Consumidor Final';
        $docType = $order->client_tipo_documento ?: '13';        // 13 = CC por defecto
        $docNum  = preg_replace('/\D/', '', (string) ($order->client_document ?: '222222222222'));
        $email   = $order->client_email ?: 'consumidorfinal@dian.gov.co';
        $address = $order->client_address ?: 'Calle 1 # 1-1';
        $city    = $order->client_city_code ?: '11001';
        $dept    = $order->client_dept_code ?: '11';

        $xml  = '  <cac:AccountingCustomerParty>' . PHP_EOL;
        $xml .= '    <cbc:AdditionalAccountID>' . ($docType === '31' ? '1' : '2') . '</cbc:AdditionalAccountID>' . PHP_EOL;
        $xml .= '    <cac:Party>' . PHP_EOL;
        $xml .= '      <cac:PartyName><cbc:Name>' . htmlspecialchars($name) . '</cbc:Name></cac:PartyName>' . PHP_EOL;
        $xml .= '      <cac:PhysicalLocation><cac:Address>' . PHP_EOL;
        $xml .= '        <cbc:ID>' . htmlspecialchars($city) . '</cbc:ID>' . PHP_EOL;
        $xml .= '        <cbc:CityName>Ciudad</cbc:CityName>' . PHP_EOL;
        $xml .= '        <cbc:CountrySubentityCode>' . htmlspecialchars($dept) . '</cbc:CountrySubentityCode>' . PHP_EOL;
        $xml .= '        <cac:AddressLine><cbc:Line>' . htmlspecialchars($address) . '</cbc:Line></cac:AddressLine>' . PHP_EOL;
        $xml .= '        <cac:Country><cbc:IdentificationCode>CO</cbc:IdentificationCode></cac:Country>' . PHP_EOL;
        $xml .= '      </cac:Address></cac:PhysicalLocation>' . PHP_EOL;
        $xml .= '      <cac:PartyTaxScheme>' . PHP_EOL;
        $xml .= '        <cbc:RegistrationName>' . htmlspecialchars($name) . '</cbc:RegistrationName>' . PHP_EOL;
        $xml .= '        <cbc:CompanyID schemeAgencyID="195" schemeAgencyName="CO, DIAN (Dirección de Impuestos y Aduanas Nacionales)" schemeID="' . htmlspecialchars((string) ($order->client_dv ?? '0')) . '" schemeName="' . htmlspecialchars($docType) . '">' . htmlspecialchars($docNum) . '</cbc:CompanyID>' . PHP_EOL;
        $xml .= '        <cbc:TaxLevelCode listName="49">R-99-PN</cbc:TaxLevelCode>' . PHP_EOL;
        $xml .= '        <cac:TaxScheme><cbc:ID>ZZ</cbc:ID><cbc:Name>No causa</cbc:Name></cac:TaxScheme>' . PHP_EOL;
        $xml .= '      </cac:PartyTaxScheme>' . PHP_EOL;
        $xml .= '      <cac:PartyLegalEntity>' . PHP_EOL;
        $xml .= '        <cbc:RegistrationName>' . htmlspecialchars($name) . '</cbc:RegistrationName>' . PHP_EOL;
        $xml .= '        <cbc:CompanyID schemeAgencyID="195" schemeAgencyName="CO, DIAN (Dirección de Impuestos y Aduanas Nacionales)" schemeID="' . htmlspecialchars((string) ($order->client_dv ?? '0')) . '" schemeName="' . htmlspecialchars($docType) . '">' . htmlspecialchars($docNum) . '</cbc:CompanyID>' . PHP_EOL;
        $xml .= '      </cac:PartyLegalEntity>' . PHP_EOL;
        $xml .= '      <cac:Contact><cbc:ElectronicMail>' . htmlspecialchars($email) . '</cbc:ElectronicMail></cac:Contact>' . PHP_EOL;
        $xml .= '    </cac:Party>' . PHP_EOL;
        $xml .= '  </cac:AccountingCustomerParty>' . PHP_EOL;
        return $xml;
    }

    private function paymentMeans(Order $order): string
    {
        // 1 = Contado, 2 = Crédito
        $paymentMeansID = '10';   // 10 = Efectivo, 47 = Tarjeta crédito, 48 = Débito
        if ($order->payment_method === 'card') $paymentMeansID = '47';
        if ($order->payment_method === 'transfer') $paymentMeansID = '42';

        $xml  = '  <cac:PaymentMeans>' . PHP_EOL;
        $xml .= '    <cbc:ID>1</cbc:ID>' . PHP_EOL;
        $xml .= '    <cbc:PaymentMeansCode>' . $paymentMeansID . '</cbc:PaymentMeansCode>' . PHP_EOL;
        $xml .= '    <cbc:PaymentDueDate>' . $order->created_at->format('Y-m-d') . '</cbc:PaymentDueDate>' . PHP_EOL;
        $xml .= '  </cac:PaymentMeans>' . PHP_EOL;
        return $xml;
    }

    private function taxTotals(Order $order): string
    {
        $iva = (float) $order->iva;
        $base = (float) $order->subtotal;

        $xml  = '  <cac:TaxTotal>' . PHP_EOL;
        $xml .= '    <cbc:TaxAmount currencyID="COP">' . $this->m($iva) . '</cbc:TaxAmount>' . PHP_EOL;
        $xml .= '    <cac:TaxSubtotal>' . PHP_EOL;
        $xml .= '      <cbc:TaxableAmount currencyID="COP">' . $this->m($base) . '</cbc:TaxableAmount>' . PHP_EOL;
        $xml .= '      <cbc:TaxAmount currencyID="COP">' . $this->m($iva) . '</cbc:TaxAmount>' . PHP_EOL;
        $xml .= '      <cac:TaxCategory>' . PHP_EOL;
        $xml .= '        <cbc:Percent>' . $this->m($this->config->ivaFactor() * 100) . '</cbc:Percent>' . PHP_EOL;
        $xml .= '        <cac:TaxScheme><cbc:ID>01</cbc:ID><cbc:Name>IVA</cbc:Name></cac:TaxScheme>' . PHP_EOL;
        $xml .= '      </cac:TaxCategory>' . PHP_EOL;
        $xml .= '    </cac:TaxSubtotal>' . PHP_EOL;
        $xml .= '  </cac:TaxTotal>' . PHP_EOL;
        return $xml;
    }

    private function legalMonetaryTotal(Order $order): string
    {
        $base   = (float) $order->subtotal;
        $iva    = (float) $order->iva;
        $total  = (float) $order->total;

        $xml  = '  <cac:LegalMonetaryTotal>' . PHP_EOL;
        $xml .= '    <cbc:LineExtensionAmount currencyID="COP">' . $this->m($base) . '</cbc:LineExtensionAmount>' . PHP_EOL;
        $xml .= '    <cbc:TaxExclusiveAmount currencyID="COP">' . $this->m($base) . '</cbc:TaxExclusiveAmount>' . PHP_EOL;
        $xml .= '    <cbc:TaxInclusiveAmount currencyID="COP">' . $this->m($base + $iva) . '</cbc:TaxInclusiveAmount>' . PHP_EOL;
        $xml .= '    <cbc:PayableAmount currencyID="COP">' . $this->m($total) . '</cbc:PayableAmount>' . PHP_EOL;
        $xml .= '  </cac:LegalMonetaryTotal>' . PHP_EOL;
        return $xml;
    }

    private function buildLines(Order $order): string
    {
        $xml = '';
        $factor = 1 + $this->config->ivaFactor();
        $i = 0;
        foreach ($order->details as $d) {
            $i++;
            $priceWithTax  = (float) $d->price;
            $priceWithout  = round($priceWithTax / $factor, 2);
            $lineBase      = round($priceWithout * $d->quantity, 2);
            $lineIva       = round($lineBase * $this->config->ivaFactor(), 2);
            $lineTotal     = round($lineBase + $lineIva, 2);

            $xml .= '  <cac:InvoiceLine>' . PHP_EOL;
            $xml .= '    <cbc:ID>' . $i . '</cbc:ID>' . PHP_EOL;
            $xml .= '    <cbc:InvoicedQuantity unitCode="NIU">' . number_format($d->quantity, 2, '.', '') . '</cbc:InvoicedQuantity>' . PHP_EOL;
            $xml .= '    <cbc:LineExtensionAmount currencyID="COP">' . $this->m($lineBase) . '</cbc:LineExtensionAmount>' . PHP_EOL;
            $xml .= '    <cac:TaxTotal>' . PHP_EOL;
            $xml .= '      <cbc:TaxAmount currencyID="COP">' . $this->m($lineIva) . '</cbc:TaxAmount>' . PHP_EOL;
            $xml .= '      <cac:TaxSubtotal>' . PHP_EOL;
            $xml .= '        <cbc:TaxableAmount currencyID="COP">' . $this->m($lineBase) . '</cbc:TaxableAmount>' . PHP_EOL;
            $xml .= '        <cbc:TaxAmount currencyID="COP">' . $this->m($lineIva) . '</cbc:TaxAmount>' . PHP_EOL;
            $xml .= '        <cac:TaxCategory>' . PHP_EOL;
            $xml .= '          <cbc:Percent>' . $this->m($this->config->ivaFactor() * 100) . '</cbc:Percent>' . PHP_EOL;
            $xml .= '          <cac:TaxScheme><cbc:ID>01</cbc:ID><cbc:Name>IVA</cbc:Name></cac:TaxScheme>' . PHP_EOL;
            $xml .= '        </cac:TaxCategory>' . PHP_EOL;
            $xml .= '      </cac:TaxSubtotal>' . PHP_EOL;
            $xml .= '    </cac:TaxTotal>' . PHP_EOL;
            $xml .= '    <cac:Item>' . PHP_EOL;
            $xml .= '      <cbc:Description>' . htmlspecialchars((string) ($d->product->name ?? 'Producto')) . '</cbc:Description>' . PHP_EOL;
            $xml .= '      <cac:StandardItemIdentification><cbc:ID schemeID="999">' . ($d->product_id ?? 0) . '</cbc:ID></cac:StandardItemIdentification>' . PHP_EOL;
            $xml .= '    </cac:Item>' . PHP_EOL;
            $xml .= '    <cac:Price>' . PHP_EOL;
            $xml .= '      <cbc:PriceAmount currencyID="COP">' . $this->m($priceWithout) . '</cbc:PriceAmount>' . PHP_EOL;
            $xml .= '      <cbc:BaseQuantity unitCode="NIU">1</cbc:BaseQuantity>' . PHP_EOL;
            $xml .= '    </cac:Price>' . PHP_EOL;
            $xml .= '  </cac:InvoiceLine>' . PHP_EOL;
        }
        return $xml;
    }

    private function m($v): string { return number_format((float) $v, 2, '.', ''); }
}
