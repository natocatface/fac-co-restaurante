<?php

namespace App\Services\Dian;

use App\Models\Setting;

/**
 * Lectura centralizada de la configuración DIAN almacenada en la tabla `settings`.
 *
 * Las claves se editan desde Configuración > DIAN Colombia en el panel.
 */
class DianConfig
{
    /** Endpoint del servicio web DIAN según ambiente (1=Producción, 2=Habilitación). */
    public const WSDL_HABILITACION = 'https://vpfe-hab.dian.gov.co/WcfDianCustomerServices.svc?wsdl';
    public const WSDL_PRODUCCION   = 'https://vpfe.dian.gov.co/WcfDianCustomerServices.svc?wsdl';

    public const ENDPOINT_HABILITACION = 'https://vpfe-hab.dian.gov.co/WcfDianCustomerServices.svc';
    public const ENDPOINT_PRODUCCION   = 'https://vpfe.dian.gov.co/WcfDianCustomerServices.svc';

    /** URL pública de consulta del documento (donde apunta el QR). */
    public const QR_URL_HABILITACION = 'https://catalogo-vpfe-hab.dian.gov.co/document/searchqr?documentkey=';
    public const QR_URL_PRODUCCION   = 'https://catalogo-vpfe.dian.gov.co/document/searchqr?documentkey=';

    /** @var array<string,string> Cache de las settings cargadas. */
    private array $cache = [];

    public function __construct()
    {
        $this->cache = Setting::pluck('value', 'key')->toArray();
    }

    public function get(string $key, ?string $default = null): ?string
    {
        return $this->cache[$key] ?? $default;
    }

    public function environment(): int
    {
        return (int) ($this->get('dian_environment') ?? 2);
    }

    public function isProduction(): bool
    {
        return $this->environment() === 1;
    }

    public function endpoint(): string
    {
        return $this->isProduction() ? self::ENDPOINT_PRODUCCION : self::ENDPOINT_HABILITACION;
    }

    public function wsdl(): string
    {
        return $this->isProduction() ? self::WSDL_PRODUCCION : self::WSDL_HABILITACION;
    }

    public function qrBaseUrl(): string
    {
        return $this->isProduction() ? self::QR_URL_PRODUCCION : self::QR_URL_HABILITACION;
    }

    /** Factor de IVA: 0.19 si IVA general 19%. */
    public function ivaFactor(): float
    {
        return ((float) ($this->get('iva_rate') ?? 19)) / 100;
    }

    /** Factor de Impuesto al Consumo (restaurantes). 0 si no aplica. */
    public function icoFactor(): float
    {
        return ((float) ($this->get('ico_rate') ?? 0)) / 100;
    }

    /** Datos del emisor empaquetados. */
    public function emisor(): array
    {
        return [
            'nit'                    => $this->get('dian_company_nit'),
            'dv'                     => $this->get('dian_company_dv'),
            'razon_social'           => $this->get('dian_company_razon_social'),
            'nombre_comercial'       => $this->get('dian_company_nombre_comercial'),
            'tipo_documento'         => $this->get('dian_company_tipo_documento', '31'),
            'tipo_persona'           => $this->get('dian_company_tipo_persona', '1'),
            'regimen'                => $this->get('dian_company_regimen', '49'),
            'responsabilidad_fiscal' => $this->get('dian_company_responsabilidad', 'R-99-PN'),
            'address'                => $this->get('dian_company_address'),
            'city_code'              => $this->get('dian_company_city_code', '11001'),
            'dept_code'              => $this->get('dian_company_dept_code', '11'),
            'country_code'           => $this->get('dian_company_country_code', 'CO'),
            'phone'                  => $this->get('dian_company_phone'),
            'email'                  => $this->get('dian_company_email'),
            'actividad_economica'    => $this->get('dian_company_actividad_economica'),
            'municipio_nombre'       => $this->get('dian_company_municipio_nombre'),
        ];
    }

    /** Software registrado en la DIAN. */
    public function software(): array
    {
        return [
            'id'  => $this->get('dian_software_id'),
            'pin' => $this->get('dian_software_pin'),
        ];
    }

    public function testSetId(): ?string
    {
        return $this->get('dian_test_set_id');
    }

    public function certPath(): ?string
    {
        return $this->get('dian_cert_path');
    }

    public function certPassword(): ?string
    {
        return $this->get('dian_cert_password');
    }

    public function isConfigured(): bool
    {
        return !empty($this->get('dian_company_nit'))
            && !empty($this->get('dian_company_razon_social'));
    }
}
